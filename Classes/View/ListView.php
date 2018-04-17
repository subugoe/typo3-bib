<?php

namespace Ipf\Bib\View;

use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Modes\Display;
use Ipf\Bib\Modes\Enumeration;
use Ipf\Bib\Modes\Sort;
use Ipf\Bib\Navigation\AuthorNavigation;
use Ipf\Bib\Navigation\PageNavigation;
use Ipf\Bib\Navigation\SearchNavigation;
use Ipf\Bib\Navigation\StatisticsNavigation;
use Ipf\Bib\Navigation\YearNavigation;
use Ipf\Bib\Service\ItemTransformerService;
use Ipf\Bib\Utility\ReferenceReader;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ListView extends View
{
    /**
     * @var array
     */
    private $extConf;

    /**
     * @var array
     */
    private $conf;

    public function initialize(array $configuration, array $localConfiguration)
    {
        $this->extConf = $configuration;
        $this->conf = $localConfiguration;
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/List/List.html');
        $this->view->setPartialRootPaths([10 => 'EXT:bib/Resources/Private/Partials/']);

        $this->view->assign('configuration', $configuration);
        $this->view->assign('searchNavigation', $this->setupSearchNavigation());
        $this->view->assign('yearNavigation', $this->setupYearNavigation());
        $this->view->assign('authorNavigation', $this->setupAuthorNavigation());
        $this->view->assign('preferenceNavigation', $this->setupPreferenceNavigation());
        $this->view->assign('pageNavigation', $this->setupPageNavigation());
        $this->view->assign('statisticsNavigation', $this->setupStatisticsNavigation());
        $this->view->assign('items', $this->setupItems());

        return $this->view->render();
    }

    private function setupSearchNavigation()
    {
        $searchNavigation = GeneralUtility::makeInstance(SearchNavigation::class, $this->extConf);
        $searchNavigation->hook_init();

        return $searchNavigation->get();
    }

    /**
     * Returns the year navigation bar.
     *
     * @return string An HTML string with the year navigation bar
     */
    private function setupYearNavigation(): string
    {
        $trans = '';

        if ($this->extConf['show_nav_year']) {
            return GeneralUtility::makeInstance(YearNavigation::class)->translator();
        }

        return $trans;
    }

    /**
     * Sets up the author navigation bar.
     */
    private function setupAuthorNavigation(): string
    {
        $trans = '';

        if ($this->extConf['show_nav_author']) {
            return $this->extConf['author_navi']['obj']->get();
        }

        return $trans;
    }

    /**
     * Sets up the preferences navigation bar.
     */
    private function setupPreferenceNavigation()
    {
        $trans = '';

        if ($this->extConf['show_nav_pref']) {
            return $this->extConf['pref_navi']['obj']->translator();
        }

        return $trans;
    }

    /**
     * Sets up the page navigation bar.
     */
    private function setupPageNavigation(): string
    {
        $trans = '';

        if ($this->extConf['show_nav_page']) {
            return GeneralUtility::makeInstance(PageNavigation::class)->get();
        }

        return $trans;
    }

    /**
     * Setup the statistic element.
     */
    private function setupStatisticsNavigation(): string
    {
        $trans = '';

        if ($this->extConf['show_nav_stat']) {
            return GeneralUtility::makeInstance(StatisticsNavigation::class)->get();
        }

        return $trans;
    }

    /**
     * Setup items in the html-template.
     */
    private function setupItems(): array
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $itemTransformer = GeneralUtility::makeInstance(ItemTransformerService::class, [$this->extConf]);
        $items = [];

        $itemTransformer->prepareItemSetup();

        // Initialize the label translator
        $labelTranslator = [];
        $labels = [
            'abstract',
            'annotation',
            'chapter',
            'doc_number',
            'doi',
            'edition',
            'editor',
            'ISBN',
            'ISSN',
            'keywords',
            'tags',
            'note',
            'of_series',
            'page',
            'publisher',
            'references',
            'report_number',
            'volume',
        ];

        foreach ($labels as $label) {
            $upperCaseLabel = strtoupper($label);
            $labelValue = LocalizationUtility::translate('label_'.$label, 'bib');
            $labelValue = $contentObjectRenderer->stdWrap($labelValue, $this->conf['label.'][$label.'.']);
            $labelTranslator[$upperCaseLabel] = $labelValue;
        }

        // block templates
        $itemTemplate = [];

        // Initialize the enumeration template
        $enumerationIdentifier = 'page';
        switch (intval($this->extConf['enum_style'])) {
            case Enumeration::ENUM_ALL:
                $enumerationIdentifier = 'all';
                break;
            case Enumeration::ENUM_BULLET:
                $enumerationIdentifier = 'bullet';
                break;
            case Enumeration::ENUM_EMPTY:
                $enumerationIdentifier = 'empty';
                break;
            case Enumeration::ENUM_FILE_ICON:
                $enumerationIdentifier = 'file_icon';
                break;
        }
        $enumerationBase = strval($this->conf['enum.'][$enumerationIdentifier]);
        $enumerationWrap = $this->conf['enum.'][$enumerationIdentifier.'.'];

        // Warning cfg
        $editMode = $this->extConf['edit_mode'];

        if (Display::D_Y_SPLIT === (int) $this->extConf['d_mode']) {
            $this->extConf['split_years'] = true;
        }

        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $this->extConf);

        return $referenceReader->getAllReferences();

        // Determine publication numbers
        $publicationsBefore = 0;
        if ((Display::D_Y_NAV === (int) $this->extConf['d_mode']) && is_numeric($this->extConf['year'])) {
            foreach ($this->stat['year_hist'] as $y => $n) {
                if ($y === $this->extConf['year']) {
                    break;
                }
                $publicationsBefore += $n;
            }
        }

        $prevBibType = -1;
        $prevYear = -1;

        // Initialize counters
        $limit_start = (int) $this->extConf['filters']['br_page']['limit']['start'];
        $i_page = $this->stat['num_page'] - $limit_start;
        $i_page_delta = -1;
        if (Sort::SORT_ASC == $this->extConf['date_sorting']) {
            $i_page = $limit_start + 1;
            $i_page_delta = 1;
        }

        $i_subpage = 1;
        $i_bibtype = 1;

        /** @var Reference $pub */
        foreach ($references as $pub) {
            $warnings = [];

            // All publications counter
            $i_all = $publicationsBefore + $i_page;

            // Determine evenOdd
            if ($this->extConf['split_bibtypes']) {
                if ($pub->getBibtype() !== $prevBibType) {
                    $i_bibtype = 1;
                }
                $evenOdd = $i_bibtype % 2;
            } else {
                $evenOdd = $i_subpage % 2;
            }

            // Initialize the translator
            $translator = [];

            $enum = $enumerationBase;
            $enum = str_replace('###I_ALL###', (string) $i_all, $enum);
            $enum = str_replace('###I_PAGE###', (string) $i_page, $enum);
            if (!(false === strpos($enum, '###FILE_URL_ICON###'))) {
                $repl = $this->getFileUrlIcon();
                $enum = str_replace('###FILE_URL_ICON###', $repl, $enum);
            }
            $translator['###ENUM_NUMBER###'] = $contentObjectRenderer->stdWrap($enum, $enumerationWrap);

            // Row classes
            $eo = $evenOdd ? 'even' : 'odd';

            $translator['###ROW_CLASS###'] = $this->conf['classes.'][$eo];

            $translator['###NUMBER_CLASS###'] = 'tx_bib-enum';

            // Manipulators
            $translator['###MANIPULATORS###'] = '';
            $manip_all = [];
            $subst_sub = '';
            if ($editMode) {
                if ($this->checkFEauthorRestriction($pub)) {
                    $subst_sub = ['', ''];
                    //   $manip_all[] = $this->getEditManipulator($pub);
                    // $manip_all[] = $this->getHideManipulator($pub);
                    $manip_all = \Ipf\Bib\Utility\Utility::html_layout_table([$manip_all]);

                    $translator['###MANIPULATORS###'] = $contentObjectRenderer->stdWrap(
                        $manip_all,
                        $this->conf['editor.']['list.']['manipulators.']['all.']
                    );
                }
            }

            // Year separator label
            $years = [];
            if ($this->extConf['split_years'] && ($pub['year'] !== $prevYear)) {
                $years[] = $contentObjectRenderer->stdWrap(strval($pub['year']), $this->conf['label.']['year.']);
                $prevBibType = -1;
            }

            // Bibtype separator label
            if ($this->extConf['split_bibtypes'] && ($pub['bibtype'] !== $prevBibType)) {
                $bibStr = $contentObjectRenderer->stdWrap(
                    LocalizationUtility::translate('bibtype_plural_'.$pub['bibtype'], 'bib'),
                    $this->conf['label.']['bibtype.']
                );
                $items['bibstr'] = $bibStr;
            }

            // Append string for item data
            $append = '';
            if ((count($warnings) > 0) && $editMode) {
                $charset = $this->extConf['charset']['upper'];
                foreach ($warnings as $err) {
                    $msg = htmlspecialchars($err['msg'], ENT_QUOTES, $charset);
                    $append .= $contentObjectRenderer->stdWrap($msg, $warningConfiguration['msg.']);
                }
                $append = $contentObjectRenderer->stdWrap($append, $warningConfiguration['all_wrap.']);
            }
            $translator['###ITEM_APPEND###'] = $append;

            // Apply translator
            $listViewTemplate = $translator;

            // Pass to item processor
            $items[] = $itemTransformer->getItemHtml($pub, implode('', $listViewTemplate));

            $references[] = $pub;

            // Update counters
            $i_page += $i_page_delta;
            ++$i_subpage;
            ++$i_bibtype;

            $prevBibType = $pub->getBibtype();
            $prevYear = $pub->getYear();
        }

        return $items;
    }

    /**
     * Returns the file url icon.
     *
     * @return string The html icon img tag
     */
    private function getFileUrlIcon()
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $fileSources = $this->icon_src['files'];

        $src = strval($fileSources['.empty_default']);
        $alt = 'default';

        // Acquire file type
        $url = '';

        if (strlen($url) > 0) {
            $src = $fileSources['.default'];

            foreach ($fileSources as $ext => $file) {
                $len = strlen($ext);
                if (strlen($url) >= $len) {
                    $sub = strtolower(substr($url, -$len));
                    if ($sub === $ext) {
                        $src = $file;
                        $alt = substr($ext, 1);
                        break;
                    }
                }
            }
        }

        if (strlen($src) > 0) {
            $imageTag = '<img src="'.$src.'"';
            $imageTag .= ' alt="'.$alt.'"';
            $fileIconClass = $this->conf['enum.']['file_icon_class'];
            if (is_string($fileIconClass)) {
                $imageTag .= ' class="'.$fileIconClass.'"';
            }
            $imageTag .= '/>';
        } else {
            $imageTag = '&nbsp;';
        }

        $wrap = $this->conf['enum.']['file_icon_image.'];
        if (is_array($wrap)) {
            if (is_array($wrap['typolink.'])) {
                $title = LocalizationUtility::translate('link_get_file', 'bib');
                $wrap['typolink.']['title'] = $title;
            }
            $imageTag = $contentObjectRenderer->stdWrap($imageTag, $wrap);
        }

        return $imageTag;
    }

    /**
     * This method checks if the current FE user is allowed to edit
     * this publication.
     *
     * The FE user is allowed to edit if he is an author of the publication.
     * This check is only done if FE_edit_own_records is set to 1 in TS,
     * otherwise all publications can be editited.
     *
     * @todo code duplication in here, better extend $extConf['edit_mode'] in some way
     * @todo put conf['FE_edit_own_records'] check in extConf[], so it is not checked every time
     * @todo make TS also a FlexForm value
     *
     * @param Reference $publication
     *
     * @return bool TRUE (allowed) FALSE (restricted)
     */
    protected function checkFEauthorRestriction(Reference $publication)
    {
        /** @var \TYPO3\CMS\Backend\FrontendBackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];

        // always allow BE users with sufficient rights
        if (is_object($beUser)) {
            if ($beUser->isAdmin()) {
                return true;
            }
            if ($beUser->check('tables_modify', ReferenceReader::REFERENCE_TABLE)) {
                return true;
            }
        }

        // Is FE-user editing only for own records enabled? (set via TS)
        if (isset($this->conf['FE_edit_own_records']) && 0 !== (int) $this->conf['FE_edit_own_records']) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(ReferenceReader::AUTHOR_TABLE);
            $results = $queryBuilder->select('fe_user_id')
                ->from(ReferenceReader::AUTHOR_TABLE, 'a')
                ->from(ReferenceReader::AUTHORSHIP_TABLE, 'm')
                ->where($queryBuilder->expr()->eq('a.uid', 'm.author_id'))
                ->andWhere($queryBuilder->expr()->eq('m.pub_id', $publication->getUid()))
                ->execute()
                ->fetchAll();

            foreach ($results as $result) {
                // check if author equals FE user and allow editing
                if ($result['fe_user_id'] === $GLOBALS['TSFE']->fe_user->id) {
                    return true;
                }
            }

            return false;
        }

        // default behavior, FE user can edit all records
        return true;
    }

    /**
     * Returns the edit button.
     *
     * @param array $publication
     *
     * @return string
     */
    protected function getEditManipulator($publication)
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $label = $this->pi_getLL('manipulators_edit', 'Edit', true);
        $res = $this->get_link(
             '',
             [
                 'action' => [
                     'edit' => 1,
                 ],
                 'uid' => $publication['uid'],
             ],
             true,
             [
                 'title' => $label,
                 'class' => 'edit-record',
             ]
         );

        $res = $contentObjectRenderer->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['edit.']);

        return $res;
    }

    /**
     * Returns the hide button.
     *
     * @param array $publication
     *
     * @return string
     */
    protected function getHideManipulator($publication)
    {
        if (0 === (int) $publication['hidden']) {
            $label = $this->pi_getLL('manipulators_hide', 'Hide', true);
            $class = 'hide';
        } else {
            $label = $this->pi_getLL('manipulators_reveal', 'Reveal', true);
            $class = 'reveal';
        }

        $action = [$class => 1];

        $res = $this->get_link(
            '',
            [
                'action' => $action,
                'uid' => $publication['uid'],
            ],
            true,
            [
                'title' => $label,
                'class' => $class.'-record',
            ]
        );

        return $this->cObj->stdWrap($res, $this->conf['editor.']['list.']['manipulators.']['hide.']);
    }
}
