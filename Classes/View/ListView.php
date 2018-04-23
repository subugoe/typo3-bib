<?php

namespace Ipf\Bib\View;

use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Modes\Display;
use Ipf\Bib\Modes\Sort;
use Ipf\Bib\Navigation\AuthorNavigation;
use Ipf\Bib\Navigation\PageNavigation;
use Ipf\Bib\Navigation\PreferenceNavigation;
use Ipf\Bib\Navigation\SearchNavigation;
use Ipf\Bib\Navigation\StatisticsNavigation;
use Ipf\Bib\Navigation\YearNavigation;
use Ipf\Bib\Utility\ReferenceReader;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ListView extends View
{
    public function initialize()
    {
        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/List/List.html');

        $this->view->assign('configuration', $this->configuration);
        $this->view->assign('searchNavigation', $this->setupSearchNavigation());

        if ($this->configuration['show_nav_year']) {
            $this->view->assign('yearNavigation', $this->setupYearNavigation());
        }

        if ($this->configuration['show_nav_author']) {
            $this->view->assign('authorNavigation', $this->setupAuthorNavigation());
        }

        if ($this->configuration['show_nav_pref']) {
            $this->view->assign('preferenceNavigation', $this->setupPreferenceNavigation());
        }

        if ($this->configuration['show_nav_page']) {
            $this->view->assign('pageNavigation', $this->setupPageNavigation());
        }

        if ($this->configuration['show_nav_stat']) {
            $this->view->assign('statisticsNavigation', $this->setupStatisticsNavigation());
        }

        $this->view->assign('items', $this->setupItems());

        return $this->view->render();
    }

    private function setupSearchNavigation()
    {
        $searchNavigation = GeneralUtility::makeInstance(SearchNavigation::class, $this->configuration, $this->conf);
        $searchNavigation->hook_filter();
        $searchNavigation->initialize();

        return $searchNavigation->get();
    }

    /**
     * Returns the year navigation bar.
     *
     * @return string An HTML string with the year navigation bar
     */
    private function setupYearNavigation(): string
    {
        $yearNavigation = GeneralUtility::makeInstance(YearNavigation::class, $this->configuration, $this->conf);
        $yearNavigation->initialize();

        return $yearNavigation->get();
    }

    /**
     * Sets up the author navigation bar.
     */
    private function setupAuthorNavigation(): string
    {
        $authorNavigation = GeneralUtility::makeInstance(AuthorNavigation::class, $this->configuration, $this->conf);

        $authorNavigation->initialize();
        $authorNavigation->hook_filter();

        return $authorNavigation->get();
    }

    /**
     * Sets up the preferences navigation bar.
     */
    private function setupPreferenceNavigation()
    {
        $preferenceNavigation = GeneralUtility::makeInstance(PreferenceNavigation::class, $this->configuration, $this->conf);

        return $preferenceNavigation->get();
    }

    /**
     * Sets up the page navigation bar.
     */
    private function setupPageNavigation(): string
    {
        return GeneralUtility::makeInstance(PageNavigation::class)->get();
    }

    /**
     * Setup the statistic element.
     */
    private function setupStatisticsNavigation(): string
    {
        return GeneralUtility::makeInstance(StatisticsNavigation::class, $this->configuration, $this->conf)->get();
    }

    /**
     * Setup items in the html-template.
     */
    private function setupItems(): array
    {
        $getPostVariables = GeneralUtility::_GP('tx_bib_pi1');

        $offset = $getPostVariables['@widget_0']['currentPage'] ?? 0;
        $limit = $this->configuration['sub_page']['ipp'];

        if ($offset > 0) {
            $offset = $offset * $limit;
        }

        // Warning cfg
        $editMode = $this->configuration['edit_mode'];

        if (Display::D_Y_SPLIT === (int) $this->configuration['d_mode']) {
            $this->configuration['split_years'] = true;
        }

        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $this->configuration);

        return $referenceReader->getAllReferences($offset, $limit);

        // Determine publication numbers
        $publicationsBefore = 0;
        if ((Display::D_Y_NAV === (int) $this->configuration['d_mode']) && is_numeric($this->configuration['year'])) {
            foreach ($this->stat['year_hist'] as $y => $n) {
                if ($y === $this->configuration['year']) {
                    break;
                }
                $publicationsBefore += $n;
            }
        }

        $prevBibType = -1;
        $prevYear = -1;

        // Initialize counters
        $limit_start = (int) $this->configuration['filters']['br_page']['limit']['start'];
        $i_page = $this->stat['num_page'] - $limit_start;
        $i_page_delta = -1;
        if (Sort::SORT_ASC == $this->configuration['date_sorting']) {
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
            if ($this->configuration['split_bibtypes']) {
                if ($pub->getBibtype() !== $prevBibType) {
                    $i_bibtype = 1;
                }
            }

            // Manipulators
            $manip_all = [];
            $subst_sub = '';
            if ($editMode) {
                if ($this->checkFEauthorRestriction($pub)) {
                    $subst_sub = ['', ''];
                }
            }

            // Year separator label
            $years = [];
            if ($this->configuration['split_years'] && ($pub['year'] !== $prevYear)) {
                $prevBibType = -1;
            }

            // Bibtype separator label
            if ($this->configuration['split_bibtypes'] && ($pub['bibtype'] !== $prevBibType)) {
                $bibStr = $contentObjectRenderer->stdWrap(
                    LocalizationUtility::translate('bibtype_plural_'.$pub['bibtype'], 'bib'),
                    $this->conf['label.']['bibtype.']
                );
                $items['bibstr'] = $bibStr;
            }

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
}
