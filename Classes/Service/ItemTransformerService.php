<?php

namespace Ipf\Bib\Service;

use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Utility\ReferenceReader;
use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ItemTransformerService
{
    /**
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Prepares database publication data for displaying.
     *
     * @param array $publication
     *
     * @return Reference The processed publication object
     */
    public function transformPublication(array $publication): Reference
    {
        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $this->configuration);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $reference = GeneralUtility::makeInstance(Reference::class);
        $reference
            ->setUid((int) $publication['uid'])
            ->setTstamp((int) $publication['tstamp'])
            ->setCrdate((int) $publication['crdate'])
            ->setBibtype((int) $publication['bibtype'])
            ->setCiteid($publication['citeid'] ?? '')
            ->setTitle($publication['title'] ?? '')
            ->setJournal($publication['journal'] ?? '')
            ->setYear((int) $publication['year'])
            ->setMonth((int) $publication['month'])
            ->setDay((int) $publication['day'])
            ->setVolume($publication['volume'] ?? '')
            ->setNumber($publication['number'] ?? '')
            ->setNumber2($publication['number2'] ?? '')
            ->setPages($publication['pages'] ?? '')
            ->setAbstract($publication['abstract'] ?? '')
            ->setAffiliation($publication['affiliation'] ?? '')
            ->setNote((string) $publication['note'])
            ->setAnnotation((string) $publication['annotation'])
            ->setKeywords((string) $publication['keywords'])
            ->setTags((string) $publication['tags'])
            ->setFileUrl(Utility::fix_html_ampersand((string) $publication['file_url']))
            ->setWebUrl(Utility::fix_html_ampersand((string) $publication['web_url']))
            ->setWebUrlDate((string) $publication['web_url_date'])
            ->setWebUrl2(Utility::fix_html_ampersand((string) $publication['web_url']))
            ->setWebUrl2Date((string) $publication['web_url_date'])
            ->setMisc((string) $publication['misc'])
            ->setMisc2((string) $publication['misc2'])
            ->setEditor((string) $publication['editor'])
            ->setPublisher((string) $publication['publisher'])
            ->setAddress((string) $publication['address'])
            ->setHowpublished((string) $publication['howpublished'])
            ->setSeries((string) $publication['series'])
            ->setEdition((string) $publication['edition'])
            ->setChapter((string) $publication['chapter'])
            ->setBooktitle((string) $publication['booktitle'])
            ->setSchool((string) $publication['school'])
            ->setInstitute((string) $publication['institute'])
            ->setOrganization((string) $publication['organization'])
            ->setInstitution((string) $publication['institution'])
            ->setEventPlace((string) $publication['event_place'])
            ->setEventName((string) $publication['event_name'])
            ->setEventDate((string) $publication['event_date'])
            ->setState((int) $publication['state'])
            ->setType((string) $publication['type'])
            ->setLanguage((string) $publication['language'])
            ->setISBN((string) $publication['ISBN'])
            ->setISSN((string) $publication['ISSN'])
            ->setDOI((string) $publication['DOI'])
            ->setExtern((bool) $publication['extern'])
            ->setReviewed((bool) $publication['reviewed'])
            ->setInLibrary((bool) $publication['in_library'])
            ->setDOIUrl('')
            ->setHidden((bool) $publication['hidden'])
            ->setBorrowedBy((string) $publication['borrowed_by']);

        // Bibtype
        $publicationData['bibtype_short'] = $referenceReader->allBibTypes[$reference->getBibtype()];

        // Day
        if ($reference->getDay() > 0 && $reference->getDay() <= 31) {
        } else {
            $reference->setDay(0);
        }

        // Month
        if (($reference->getMonth() > 0) && ($reference->getMonth() <= 12)) {
            $tme = mktime(0, 0, 0, $reference->getMonth(), 15, 2008);
            $reference->setMonth($tme);
        } else {
            $reference->setMonth(0);
        }

        // Copy field values
        $url_max = 40;
        if (is_numeric($this->conf['max_url_string_length'])) {
            $url_max = (int) $this->conf['max_url_string_length'];
        }

        // Multi fields
        $multi = [
            'authors' => ReferenceReader::$authorFields,
        ];
        foreach ($multi as $table => $fields) {
            $elements = &$publicationData[$table];
            if (!is_array($elements)) {
                continue;
            }
            foreach ($elements as &$element) {
                foreach ($fields as $field) {
                    $val = $element[$field];
                    // Check restrictions
                    if (strlen($val) > 0) {
                        if ($this->checkFieldRestriction($table, $field, $val)) {
                            $val = '';
                            $element[$field] = $val;
                        }
                    }
                }
            }
        }

        // Format the author string
        $publicationData['authors'] = $this->getItemAuthorsHtml($reference->getAuthors());

        // store editor's data before processing it
        $cleanEditors = $publicationData['editor'];

        // Editors
        if (strlen($publicationData['editor']) > 0) {
            $editors = Utility::explodeAuthorString($publicationData['editor']);
            $lst = [];
            foreach ($editors as $editor) {
                $app = '';
                if (strlen($editor['forename']) > 0) {
                    $app .= $editor['forename'].' ';
                }
                if (strlen($editor['surname']) > 0) {
                    $app .= $editor['surname'];
                }
                $app = $contentObjectRenderer->stdWrap($app, $this->conf['field.']['editor_each.']);
                $lst[] = $app;
            }
            $and = ' '.LocalizationUtility::translate('label_and', 'bib').' ';

            $publicationData['editor'] = Utility::implode_and_last(
                $lst,
                ', ',
                $and
            );

            // reset processed data @todo check if the above block may be removed
            $publicationData['editor'] = $cleanEditors;
        }

        // Automatic url
        $order = GeneralUtility::trimExplode(',', $this->conf['auto_url_order'], true);
        $reference->setAutoUrl($this->getAutoUrl($publicationData, $order));
        $publicationData['auto_url_short'] = Utility::crop_middle((string) $reference->getAutoUrl(), $url_max);

        return $reference;
    }

    /**
     * Returns TRUE if the field/value combination is restricted
     * and should not be displayed.
     *
     * @param string $table
     * @param string $field
     * @param string $value
     * @param bool   $showHidden
     *
     * @return bool TRUE (restricted) or FALSE (not restricted)
     */
    protected function checkFieldRestriction(string $table, string $field, string $value, bool $showHidden = false): bool
    {
        // No value no restriction
        if (0 === strlen($value)) {
            return false;
        }

        // Field is hidden
        if (!$showHidden && $this->configuration['hide_fields'][$field]) {
            return true;
        }

        // Are there restrictions at all?
        $restrictions = $this->configuration['restrict'][$table];
        if (!is_array($restrictions) || (0 === count($restrictions))) {
            return false;
        }

        // Check Field restrictions
        if (is_array($restrictions[$field])) {
            // Show by default
            $show = true;

            // Hide on 'hide all'
            if ($restrictions[$field]['hide_all']) {
                $show = false;
            }

            // Hide if any extensions matches
            if ($show && is_array($restrictions[$field]['hide_ext'])) {
                foreach ($restrictions[$field]['hide_ext'] as $ext) {
                    // Sanitize input
                    $len = strlen($ext);
                    if (($len > 0) && (strlen($value) >= $len)) {
                        $uext = strtolower(substr($value, -$len));

                        if ($uext === $ext) {
                            $show = false;
                            break;
                        }
                    }
                }
            }

            // Enable if usergroup matches
            if (!$show && isset($restrictions[$field]['fe_groups'])) {
                $groups = $restrictions[$field]['fe_groups'];
                if (\Ipf\Bib\Utility\Utility::check_fe_user_groups($groups)) {
                    $show = true;
                }
            }

            // Restricted !
            if (!$show) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the authors string for a publication.
     *
     * @param array $authors
     *
     * @return string
     */
    protected function getItemAuthorsHtml($authors)
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectBackup = $contentObjectRenderer->data;

        // Format the author string$this->
        $separator = $this->configuration['separator'];
        if (isset($separator) && !empty($separator)) {
            $name_separator = $separator;
        } else {
            $name_separator = ' '.LocalizationUtility::translate('label_and', 'bib');
        }
        $max_authors = abs((int) $this->configuration['max_authors']);
        $lastAuthor = count($authors) - 1;
        $cutAuthors = false;
        if (($max_authors > 0) && (count($authors) > $max_authors)) {
            $cutAuthors = true;
            if (count($authors) === ($max_authors + 1)) {
                $lastAuthor = $max_authors - 2;
            } else {
                $lastAuthor = $max_authors - 1;
            }
            $name_separator = '';
        }
        $lastAuthor = max($lastAuthor, 0);

        $highlightAuthors = $this->configuration['highlight_authors'] ? true : false;

        $link_fields = $this->configuration['author_sep'];
        $a_sep = $this->configuration['author_sep'];
        $authorTemplate = $this->configuration['author_tmpl'];

        $filter_authors = [];
        if ($highlightAuthors) {
            // Collect filter authors
            foreach ($this->configuration['filters'] as $filter) {
                if (is_array($filter['author']['authors'])) {
                    $filter_authors = array_merge(
                        $filter_authors,
                        $filter['author']['authors']
                    );
                }
            }
        }

        $icon_img = $this->configuration['author_icon_img'];

        $elements = [];
        // Iterate through authors
        for ($i_a = 0; $i_a <= $lastAuthor; ++$i_a) {
            $author = $authors[$i_a];

            // Init cObj data
            $contentObjectRenderer->data = $author;
            $contentObjectRenderer->data['url'] = htmlspecialchars_decode($author['url'], ENT_QUOTES);

            // The forename
            $authorForename = trim($author['forename']);
            if (strlen($authorForename) > 0) {
                $authorForename = Utility::filter_pub_html_display($authorForename);
                $authorForename = $contentObjectRenderer->stdWrap($authorForename, $this->conf['authors.']['forename.']);
            }

            // The surname
            $authorSurname = trim($author['surname']);
            if (strlen($authorSurname) > 0) {
                $authorSurname = Utility::filter_pub_html_display($authorSurname);
                $authorSurname = $contentObjectRenderer->stdWrap($authorSurname, $this->conf['authors.']['surname.']);
            }

            // The link icon
            $cr_link = false;
            $authorIcon = '';

            if (is_array($this->configuration['author_lfields'])) {
                foreach ($this->configuration['author_lfields'] as $field) {
                    $val = trim(strval($author[$field]));
                    if ((strlen($val) > 0) && ('0' != $val)) {
                        $cr_link = true;
                        break;
                    }
                }
            }
            if ($cr_link && (strlen($icon_img) > 0)) {
                $wrap = $this->conf['authors.']['url_icon.'];
                if (is_array($wrap)) {
                    if (is_array($wrap['typolink.'])) {
                        $title = $this->pi_getLL('link_author_info', 'Author info', true);
                        $wrap['typolink.']['title'] = $title;
                    }
                    $authorIcon = $contentObjectRenderer->stdWrap($icon_img, $wrap);
                }
            }

            // Append author name
            if (!empty($authorSurname)) {
                $elements[] = $authorSurname.', '.$authorForename;
            }

            // Append 'et al.'
            if ($cutAuthors && ($i_a === $lastAuthor)) {
                // Append et al.
                $etAl = $this->pi_getLL('label_et_al', 'et al.', true);
                $etAl = (strlen($etAl) > 0) ? ' '.$etAl : '';

                if (strlen($etAl) > 0) {
                    // Highlight "et al." on demand
                    if ($highlightAuthors) {
                        $authorsSize = count($authors);
                        for ($j = $lastAuthor + 1; $j < $authorsSize; ++$j) {
                            $a_et = $authors[$j];
                            foreach ($filter_authors as $fa) {
                                if ($a_et['surname'] === $fa['surname']) {
                                    if (!$fa['forename'] || ($a_et['forename'] === $fa['forename'])) {
                                        $wrap = $this->conf['authors.']['highlight.'];
                                        $j = count($authors);
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $wrap = $this->conf['authors.']['et_al.'];
                    $etAl = $contentObjectRenderer->stdWrap($etAl, $wrap);
                    $elements[] = $etAl;
                }
            }
        }

        $res = Utility::implode_and_last($elements, $a_sep, $name_separator);
        // Restore cObj data
        $contentObjectRenderer->data = $contentObjectBackup;

        return $res;
    }

    /**
     * Prepares the virtual auto_url from the data and field order.
     *
     * @param array $processedPublicationData The processed publication data
     * @param array $order
     *
     * @return string The generated url
     */
    protected function getAutoUrl($processedPublicationData, $order)
    {
        $url = '';

        foreach ($order as $field) {
            if (0 === strlen($processedPublicationData[$field])) {
                continue;
            }
            if ($this->checkFieldRestriction('ref', $field, $processedPublicationData[$field])) {
                continue;
            }

            switch ($field) {
                case 'file_url':
                    if (!$processedPublicationData['_file_nexist']) {
                        $url = $processedPublicationData[$field];
                    }
                    break;
                case 'DOI':
                    $url = $processedPublicationData['DOI_url'];
                    break;
                default:
                    $url = $processedPublicationData[$field];
            }

            if (strlen($url) > 0) {
                break;
            }
        }

        return $url;
    }

    /**
     * Returns the html interpretation of the publication
     * item as it is defined in the html template.
     *
     * @param Reference $publicationData
     * @param string    $template
     *
     * @return string HTML string for a single item in the list view
     */
    public function getItemHtml(Reference $publicationData, string $template)
    {
        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $this->configuration);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $all_base = 'rnd'.(string) rand().'rnd';
        $all_wrap = $all_base;

        // Prepare the translator
        // Remove empty field marker from the template
        $fields = $referenceReader->getPublicationFields();
        $fields[] = 'file_url_short';
        $fields[] = 'web_url_short';
        $fields[] = 'web_url2_short';
        $fields[] = 'auto_url';
        $fields[] = 'auto_url_short';

        // Reference wrap
        $all_wrap = $contentObjectRenderer->stdWrap($all_wrap, $this->conf['reference.']);

        // Embrace hidden references with wrap
        if ((true === $publicationData->isHidden()) && is_array($this->conf['editor.']['list.']['hidden.'])) {
            $all_wrap = $contentObjectRenderer->stdWrap($all_wrap, $this->conf['editor.']['list.']['hidden.']);
        }

        // remove empty divs
        $template = preg_replace("/<div[^>]*>[\s\r\n]*<\/div>/", PHP_EOL, $template);
        // remove multiple line breaks
        $template = preg_replace("/\n+/", PHP_EOL, $template);

        return $template;
    }
}
