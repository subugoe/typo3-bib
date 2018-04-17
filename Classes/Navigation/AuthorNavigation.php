<?php

namespace Ipf\Bib\Navigation;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Goettingen State Library
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use Ipf\Bib\Domain\Model\Author;
use Ipf\Bib\Utility\ReferenceReader;
use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class AuthorNavigation.
 */
class AuthorNavigation extends Navigation
{
    /**
     * @var array
     */
    public $extConf;

    /**
     * Initialize.
     */
    public function initialize(array $configuration)
    {
        parent::initialize($configuration);
        if (is_array($pi1->conf['authorNav.'])) {
            $this->conf = &$pi1->conf['authorNav.'];
        }

        $this->extConf = [];
        if (is_array($configuration['author_navi'])) {
            $this->extConf = $configuration['author_navi'];
        }

        $this->sel_link_title = $this->languageService->getLL('authorNav_authorLinkTitle');
    }

    /**
     * Hook in to pi1 at init stage.
     */
    public function hook_init(array $configuration): array
    {
        $configuration['link_vars']['author_letter'] = '';
        $pvar = $this->pi1->piVars['author_letter'];
        if (strlen($pvar) > 0) {
            $configuration['author_navi']['sel_letter'] = $pvar;
            $configuration['link_vars']['author_letter'] = $pvar;
        }

        $configuration['link_vars']['author'] = '';
        $pvar = $this->pi1->piVars['author'];
        $configuration['author_navi']['sel_author'] = '0';
        if (strlen($pvar) > 0) {
            $configuration['author_navi']['sel_author'] = $pvar;
            $configuration['link_vars']['author'] = $pvar;
        }

        return $configuration;
    }

    /**
     * Hook in to pi1 at filter stage.
     */
    public function hook_filter(array $configuration): array
    {
        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $configuration);

        // Init statistics
        $this->pi1->stat['authors'] = [];

        $filter = [];

        // Fetch all surnames and initialize letters
        $this->pi1->stat['authors']['surnames'] = $referenceReader->getSurnamesOfAllAuthors();
        $this->pi1->stat['authors']['sel_surnames'] = [];
        $this->initializeLetters($this->pi1->stat['authors']['surnames']);

        // Filter for selected author letter
        // with a temporary filter
        if (strlen($configuration['sel_letter']) > 0) {
            $filters = $configuration['filters'];

            $txt = $configuration['sel_letter'];
            $spec = htmlentities($txt, ENT_QUOTES);
            $pats = [$txt.'%'];
            if ($spec != $txt) {
                $pats[] = $spec.'%';
            }

            // Append surname letter to filter
            foreach ($pats as $pat) {
                $filter[] = ['surname' => $pat];
            }

            $filters['temp'] = [];
            $filters['temp']['author'] = [];
            $filters['temp']['author']['authors'] = $filter;

            // Fetch selected surnames
            $referenceReader->set_filters($filters);
            $this->pi1->stat['authors']['sel_surnames'] = $referenceReader->getSurnamesOfAllAuthors();

            // Remove ampersand strings from surname list
            $lst = [];
            $spec = false;
            $sel_up = mb_strtoupper($configuration['sel_letter']);
            $sel_low = mb_strtolower($configuration['sel_letter']);
            /** @var Author $author */
            foreach ($this->pi1->stat['authors']['sel_surnames'] as $author) {
                if (!(false === strpos($author->getSurName(), '&'))) {
                    $author->setSurName(html_entity_decode($author->getSurName(), ENT_COMPAT));
                    $spec = true;
                }
                // check if first letter matches
                $ll = mb_substr($author->getSurName(), 0, 1);
                if (($ll !== $sel_up) && ($ll !== $sel_low)) {
                    continue;
                }
                if (!in_array($author->getSurName(), $lst)) {
                    $lst[] = $author->getSurName();
                }
            }
            if ($spec) {
                usort($lst, 'strcoll');
            }
            $this->pi1->stat['authors']['sel_surnames'] = $lst;

            // Restore filter
            $referenceReader->set_filters($configuration['filters']);
        }

        // Setup filter for selected author
        if ('0' != $configuration['sel_author']) {
            $spec = htmlentities($configuration['sel_author'], ENT_QUOTES);

            // Check if the selected author is available
            if (in_array($configuration['sel_author'], $this->pi1->stat['authors']['sel_surnames'])
                || in_array($spec, $this->pi1->stat['authors']['sel_surnames'])
            ) {
                $pats = [$configuration['sel_author']];
                if ($spec != $configuration['sel_author']) {
                    $pats[] = $spec;
                }

                // Reset filter with the surname only
                $filter = [];
                foreach ($pats as $pat) {
                    $filter[] = ['surname' => $pat];
                }
            } else {
                $configuration['sel_author'] = '0';
            }
        }

        // Append filter
        if (count($filter) > 0) {
            $configuration['filters']['author'] = [];
            $configuration['filters']['author']['author'] = [];
            $configuration['filters']['author']['author']['authors'] = $filter;

            $referenceReader->set_filters($configuration['filters']);
        }

        return $configuration;
    }

    /**
     * Initialize letters.
     *
     * @param array $names
     */
    protected function initializeLetters(array $names)
    {
        $extConf = &$this->extConf;

        // Acquire letter
        $letters = $this->first_letters($names);

        // Acquire selected letter
        $selectedLetter = strval($extConf['sel_letter']);
        $idx = $this->string_index($selectedLetter, $letters, '');
        if ($idx < 0) {
            $selectedLetter = '';
        } else {
            $selectedLetter = $letters[$idx];
        }

        $extConf['letters'] = $letters;
        $extConf['sel_letter'] = $selectedLetter;
    }

    /**
     * Returns the first letters of all strings in a list.
     *
     * @param array  $names
     * @param string $charset
     *
     * @return array
     */
    protected function first_letters($names, $charset = 'UTF-8')
    {
        // Acquire letters
        $letters = [];
        /** @var Author $author */
        foreach ($names as $author) {
            $ll = mb_substr($author->getSurName(), 0, 1, $charset);
            if ('&' == $ll) {
                $match = preg_match('/^(&[^;]{1,7};)/', $author->getSurName(), $grp);
                if ($match) {
                    $ll = html_entity_decode($grp[1], ENT_QUOTES, $charset);
                } else {
                    $ll = false;
                }
            }
            $up = mb_strtoupper($ll, $charset);
            if ($up != $ll) {
                $ll = $up;
            }
            if ($ll && !in_array($ll, $letters)) {
                $letters[] = $ll;
            }
        }
        usort($letters, 'strcoll');

        return $letters;
    }

    /**
     * Returns the position of a string in a list.
     *
     * @param string $string
     * @param array  $list
     * @param mixed  $null
     * @param string $charset
     *
     * @return int|mixed
     */
    protected function string_index(string $string, array $list, $null)
    {
        $sel1 = $string;
        $sel2 = htmlentities($sel1, ENT_QUOTES);
        $sel3 = html_entity_decode($sel1, ENT_QUOTES);

        $index = -1;
        if ($sel1 != $null) {
            $index = array_search($sel1, $list);
            if (false === $index) {
                $index = array_search($sel2, $list);
            }
            if (false === $index) {
                $index = array_search($sel3, $list);
            }
            if (false === $index) {
                $index = -1;
            }
        }

        return $index;
    }

    /**
     * Creates a text for a given index.
     */
    protected function sel_get_text(int $index): string
    {
        $txt = strval($this->pi1->stat['authors']['sel_surnames'][$index]);
        $txt = htmlspecialchars($txt, ENT_QUOTES, $this->pi1->extConf['charset']['upper']);

        return $txt;
    }

    /**
     * Creates a link for the selection.
     *
     * @param string $text
     * @param int    $ii
     *
     * @return string
     */
    protected function sel_get_link($text, $ii)
    {
        $arg = strval($this->pi1->stat['authors']['sel_surnames'][$ii]);
        $title = str_replace('%a', $text, $this->sel_link_title);
        $lnk = $this->pi1->get_link(
            $text,
            [
                'author' => $arg,
            ],
            true,
            [
                'title' => $title,
            ]
        );

        return $lnk;
    }

    /**
     * Returns content.
     */
    public function get(): string
    {
        $selectedSurnames = []; // $this->pi1->stat['authors']['sel_surnames']

        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Navigation/Author.html');
        // find the index of the selected name
        $this->extConf['sel_name_idx'] = $this->string_index(
            (string) $this->extConf['sel_author'],
            $selectedSurnames,
            '0'
        );

        $this->view
            ->assign('letterSelection', $this->getLetterSelection())
            ->assign('selection', $this->getAuthorSelection())
            ->assign('surnameSelection', $this->getHtmlSelectFormField())
        ;

        return $this->view->render();
    }

    /**
     * Returns the author surname letter selection.
     */
    protected function getLetterSelection(): array
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $letterConfiguration = is_array($this->conf['letters.']) ? $this->conf['letters.'] : [];

        if (0 === count($this->extConf['letters'])) {
            return [];
        }

        return $this->extConf['letters'];

        // Create list
        $titleTemplate = LocalizationUtility::translate('authorNav_LetterLinkTitle', 'bib');
        // Iterate through letters
        $letterSelection = [];
        foreach ($this->extConf['letters'] as $letter) {
            if ($letter === $this->extConf['sel_letter']) {
                $txt = $contentObjectRenderer->stdWrap($txt, $letterConfiguration['current.']);
            }
            $letterSelection[] = $txt;
        }

        // Compose
        $txt = $txt.'-'.$lst;
        $txt = $contentObjectRenderer->stdWrap($txt, $letterConfiguration['all_wrap.']);

        return $letterSelection;
    }

    /**
     * The author surname select.
     *
     * @return string
     */
    protected function getAuthorSelection()
    {
        $configurationSelection = is_array($this->conf['selection.']) ? $this->conf['selection.'] : [];
        $contenObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        // Selection
        $cur = $this->extConf['sel_name_idx'];
        $max = count($this->pi1->stat['authors']['sel_surnames']) - 1;

        $indices = [0, $cur, $max];

        $numSel = 3;
        if (array_key_exists('authors', $configurationSelection)) {
            $numSel = abs(intval($configurationSelection['authors']));
        }

        $sel = $this->selection($configurationSelection, $indices, $numSel);

        // All and Separator
        $sep = ' - ';
        if (isset($configurationSelection['all_sep'])) {
            $sep = $configurationSelection['all_sep'];
        }
        $sep = $contenObjectRenderer->stdWrap($sep, $configurationSelection['all_sep.']);

        $txt = LocalizationUtility::translate('authorNav_all_authors', 'bib');
        if ($cur < 0) {
            $txt = $contenObjectRenderer->stdWrap($txt, $configurationSelection['current.']);
        } else {
            $txt = $this->pi1->get_link($txt, ['author' => '0']);
        }

        // All together
        if (count($this->pi1->stat['authors']['sel_surnames']) > 0) {
            $all = $txt.$sep.$sel;
        } else {
            $all = '&nbsp;';
        }

        $all = $contenObjectRenderer->stdWrap($all, $configurationSelection['all_wrap.']);

        return $all;
    }

    /**
     * The author surname select form field.
     *
     * @return string
     */
    protected function getHtmlSelectFormField()
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $content = '<form name="tx_bib_pi1-author_select_form" ';
        //$content .= 'action="'.$this->pi1->get_link_url(['author' => ''], false).'"';
        $content .= ' method="post"';
        $content .= strlen($this->conf['form_class']) ? ' class="'.$this->conf['form_class'].'"' : '';
        $content .= '>';

        // The raw data
        $names = $this->pi1->stat['authors']['sel_surnames'];
        $sel_name = '';
        $sel_idx = $this->extConf['sel_name_idx'];
        if ($sel_idx >= 0) {
            $sel_name = $names[$sel_idx];
            $sel_name = htmlspecialchars($sel_name, ENT_QUOTES);
        }

        // The 'All with %l' select option
        $all = LocalizationUtility::translate('authorNav_select_all', 'bib');
        $rep = '?';
        if (strlen($this->extConf['sel_letter']) > 0) {
            $rep = htmlspecialchars($this->extConf['sel_letter'], ENT_QUOTES);
        }
        $all = str_replace('%l', $rep, $all);

        // The processed data pairs
        $pairs = ['' => $all];
        foreach ($names as $name) {
            $name = htmlspecialchars($name, ENT_QUOTES);
            $pairs[$name] = $name;
        }
        $attributes = [
            'name' => $this->pi1->prefix_pi1.'[author]',
            'onchange' => 'this.form.submit()',
        ];
        if (strlen($this->conf['select_class']) > 0) {
            $attributes['class'] = $this->conf['select_class'];
        }
        $button = Utility::html_select_input($pairs, $sel_name, $attributes);

        $button = $contentObjectRenderer->stdWrap($button, $this->conf['select.']);
        $content .= $button;

        // Go button
        $attributes = [];
        if (strlen($this->conf['go_btn_class']) > 0) {
            $attributes['class'] = $this->conf['go_btn_class'];
        }
        $button = Utility::html_submit_input(
            $this->pi1->prefix_pi1.'[action][select_author]',
            LocalizationUtility::translate('button_go', 'bib'),
            $attributes
        );
        $button = $contentObjectRenderer->stdWrap($button, $this->conf['go_btn.']);
        $content .= $button;

        // End of form
        $content .= '</form>';

        // Finalize
        if (1 === count($pairs)) {
            $content = '&nbsp;';
        }

        $content = $contentObjectRenderer->stdWrap($content, $this->conf['form.']);

        return $content;
    }
}
