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
    public function hook_init()
    {
        $this->configuration['link_vars']['author_letter'] = '';
        $pvar = GeneralUtility::_GP('tx_bib_pi1')['author_letter'];
        if (strlen($pvar) > 0) {
            $this->configuration['author_navi']['sel_letter'] = $pvar;
            $this->configuration['link_vars']['author_letter'] = $pvar;
        }

        $this->configuration['link_vars']['author'] = '';
        $pvar = GeneralUtility::_GP('tx_bib_pi1')['author'];
        $this->configuration['author_navi']['sel_author'] = '0';
        if (strlen($pvar) > 0) {
            $this->configuration['author_navi']['sel_author'] = $pvar;
            $this->configuration['link_vars']['author'] = $pvar;
        }

        $this->stat = [];
    }

    /**
     * Hook in to pi1 at filter stage.
     */
    public function hook_filter()
    {
        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class, $this->configuration);

        // Init statistics
        $this->stat['authors'] = [];

        $filter = [];

        // Fetch all surnames and initialize letters
        $this->stat['authors']['surnames'] = $referenceReader->getSurnamesOfAllAuthors();
        $this->stat['authors']['sel_surnames'] = [];
        $this->initializeLetters($this->stat['authors']['surnames']);

        // Filter for selected author letter
        // with a temporary filter
        if (strlen($this->configuration['author_navi']['sel_letter']) > 0) {
            $filters = $this->configuration['author_navi']['filters'];

            $txt = $this->configuration['author_navi']['sel_letter'];
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
            $this->stat['authors']['sel_surnames'] = $referenceReader->getSurnamesOfAllAuthors();

            // Remove ampersand strings from surname list
            $lst = [];
            $spec = false;
            $sel_up = mb_strtoupper($this->configuration['author_navi']['sel_letter']);
            $sel_low = mb_strtolower($this->configuration['author_navi']['sel_letter']);
            /** @var Author $author */
            foreach ($this->stat['authors']['sel_surnames'] as $author) {
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
            $this->stat['authors']['sel_surnames'] = $lst;

            // Restore filter
            $referenceReader->set_filters($this->configuration['author_navi']['filters'] ?? []);
        }

        // Setup filter for selected author
        if ('0' != $this->configuration['author_navi']['sel_author']) {
            $spec = htmlentities($this->configuration['author_navi']['sel_author'], ENT_QUOTES);

            // Check if the selected author is available
            if (in_array($this->configuration['author_navi']['sel_author'], $this->stat['authors']['sel_surnames'])
                || in_array($spec, $this->stat['authors']['sel_surnames'])
            ) {
                $pats = [$this->configuration['author_navi']['sel_author']];
                if ($this->configuration['author_navi']['sel_author'] != $spec) {
                    $pats[] = $spec;
                }

                // Reset filter with the surname only
                $filter = [];
                foreach ($pats as $pat) {
                    $filter[] = ['surname' => $pat];
                }
            } else {
                $this->configuration['author_navi']['sel_author'] = '0';
            }
        }

        // Append filter
        if (count($filter) > 0) {
            $this->configuration['filters']['author'] = [];
            $this->configuration['filters']['author']['author'] = [];
            $this->configuration['filters']['author']['author']['authors'] = $filter;

            $referenceReader->set_filters($this->configuration['filters']);
        }
    }

    /**
     * Initialize letters.
     *
     * @param array $names
     */
    private function initializeLetters(array $names)
    {
        // Acquire letter
        $letters = $this->first_letters($names);

        // Acquire selected letter
        $selectedLetter = (string) $this->configuration['author_navi']['sel_letter'];
        $idx = $this->string_index($selectedLetter, $letters, '');
        if ($idx < 0) {
            $selectedLetter = '';
        } else {
            $selectedLetter = $letters[$idx];
        }

        $this->configuration['author_navi']['letters'] = $letters;
        $this->configuration['author_navi']['sel_letter'] = $selectedLetter;
    }

    /**
     * Returns the first letters of all strings in a list.
     *
     * @param array  $names
     * @param string $charset
     *
     * @return array
     */
    protected function first_letters(array $names)
    {
        // Acquire letters
        $letters = [];
        /** @var Author $author */
        foreach ($names as $author) {
            $ll = mb_substr($author->getSurName(), 0, 1);
            if ('&' == $ll) {
                $match = preg_match('/^(&[^;]{1,7};)/', $author->getSurName(), $grp);
                if ($match) {
                    $ll = html_entity_decode($grp[1], ENT_QUOTES);
                } else {
                    $ll = false;
                }
            }
            $up = mb_strtoupper($ll);
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
     */
    protected function string_index(string $string, array $list, string $null): int
    {
        $sel1 = $string;
        $sel2 = htmlentities($sel1, ENT_QUOTES);
        $sel3 = html_entity_decode($sel1, ENT_QUOTES);

        $index = -1;
        if ($sel1 !== $null) {
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
        $txt = strval($this->stat['authors']['sel_surnames'][$index]);
        $txt = htmlspecialchars($txt, ENT_QUOTES);

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
        $arg = strval($this->stat['authors']['sel_surnames'][$ii]);
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
        $selectedSurnames = $this->stat['authors']['sel_surnames'];

        $this->view->setTemplatePathAndFilename('EXT:bib/Resources/Private/Templates/Navigation/Author.html');
        // find the index of the selected name
        $this->configuration['author_navi']['sel_name_idx'] = $this->string_index(
            (string) $this->configuration['author_navi']['sel_author'],
            $selectedSurnames,
            '0'
        );

        $configurationSelection = is_array($this->conf['selection.']) ? $this->conf['selection.'] : [];

        $numSel = 3;
        if (array_key_exists('authors', $configurationSelection)) {
            $numSel = abs(intval($configurationSelection['authors']));
        }

        $this->view
            ->assign('letterSelection', $this->getLetterSelection())
            ->assign('names', $selectedSurnames)
            ->assign('currentLetter', $this->configuration['author_navi']['sel_letter'])
            ->assign('configurationSelection', $configurationSelection)
            ->assign('max', count($this->stat['authors']['sel_surnames'] ?? []) - 1)
            ->assign('current', $this->configuration['author_navi']['sel_name_idx'])
            ->assign('numberOfItemsToBeDisplayed', $numSel)

            ->assign('surnameSelection', $this->getHtmlSelectFormField())
        ;

        return $this->view->render();
    }

    /**
     * Returns the author surname letter selection.
     */
    protected function getLetterSelection(): array
    {
        if (0 === count($this->configuration['author_navi']['letters'])) {
            return [];
        }

        return $this->configuration['author_navi']['letters'];
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
        $names = $this->stat['authors']['sel_surnames'];
        $sel_name = '';
        $sel_idx = $this->configuration['author_navi']['sel_name_idx'];
        if ($sel_idx >= 0) {
            $sel_name = $names[$sel_idx];
            $sel_name = htmlspecialchars($sel_name, ENT_QUOTES);
        }

        // The 'All with %l' select option
        $all = LocalizationUtility::translate('authorNav_select_all', 'bib');
        $rep = '?';
        if (strlen($this->configuration['author_navi']['sel_letter']) > 0) {
            $rep = htmlspecialchars($this->configuration['author_navi']['sel_letter'], ENT_QUOTES);
        }
        $all = str_replace('%l', $rep, $all);

        // The processed data pairs
        $pairs = ['' => $all];
        foreach ($names as $name) {
            $name = htmlspecialchars($name, ENT_QUOTES);
            $pairs[$name] = $name;
        }
        $attributes = [
            'name' => 'tx_bib_pi1[author]',
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
            'tx_bib_pi1[action][select_author]',
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
