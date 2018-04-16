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

use Ipf\Bib\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class PreferenceNavigation.
 */
class PreferenceNavigation extends Navigation
{
    /**
     * @var array
     */
    public $extConf = [];

    /**
     * Intialize.
     *
     * @param \tx_bib_pi1 $pi1
     */
    public function initialize(array $configuration)
    {
        parent::initialize($configuration);
        if (is_array($pi1->conf['prefNav.'])) {
            $this->conf = &$pi1->conf['prefNav.'];
        }
    }

    /*
     * Hook in to pi1 at init stage
     */
    public function hook_init(array $configuration): array
    {
        $configuration = $this->getItemsPerPageConfiguration($configuration);
        $configuration = $this->getAbstractConfiguration($configuration);
        $configuration = $this->getKeywordConfiguration($configuration);

        return $configuration;
    }

    /**
     * Returns the preference navigation bar.
     */
    public function get(): string
    {
        $this->view
            ->assign('label', $this->getPreferenceNavigationLabel())
            ->assign('formStart', $this->getFormTagStart())
            ->assign('itemsPerPageSelection', $this->getItemsPerPageSelection())
            ->assign('showKeywords', $this->getKeywordSelection())
            ->assign('showAbstracts', $this->getAbstractSelection())
            ->assign('goButton', $this->getGoButton());

        return $this->view->render();
    }

    protected function getKeywordConfiguration(array $configuration): array
    {
        // Show keywords
        $show = false;
        if (0 !== (int) $this->pi1->piVars['show_keywords']) {
            $show = true;
        }

        $configuration['hide_fields']['keywords'] = $show ? false : true;
        $configuration['hide_fields']['tags'] = $configuration['hide_fields']['keywords'];
        $configuration['link_vars']['show_keywords'] = $show ? '1' : '0';

        return $configuration;
    }

    protected function getAbstractConfiguration(array $configuration): array
    {
        // Show abstracts
        $show = false;
        if (0 !== (int) $this->pi1->piVars['show_abstracts']) {
            $show = true;
        }
        $configuration['hide_fields']['abstract'] = $show ? false : true;
        $configuration['link_vars']['show_abstracts'] = $show ? '1' : '0';

        return $configuration;
    }

    protected function getItemsPerPageConfiguration(array $configuration): array
    {
        // Available ipp values
        $configuration['pref_ipps'] = GeneralUtility::intExplode(',', $this->conf['ipp_values']);

        // Default ipp value
        if (is_numeric($this->conf['ipp_default'])) {
            $configuration['sub_page']['ipp'] = intval($this->conf['ipp_default']);
            $configuration['pref_ipp'] = $configuration['sub_page']['ipp'];
        }

        // Selected ipp value
        $itemsPerPage = $this->pi1->piVars['items_per_page'];
        if (is_numeric($itemsPerPage)) {
            $itemsPerPage = max(intval($itemsPerPage), 0);
            if (in_array($itemsPerPage, $configuration['pref_ipps'])) {
                $configuration['sub_page']['ipp'] = $itemsPerPage;
                if ($configuration['sub_page']['ipp'] != $configuration['pref_ipp']) {
                    $configuration['link_vars']['items_per_page'] = $configuration['sub_page']['ipp'];
                }
            }
        }

        return $configuration;
    }

    /**
     * Field for determining the shown number of items per page.
     *
     * @return string
     */
    protected function getItemsPerPageSelection()
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $label = $this->languageService->getLL('prefNav_ipp_sel');
        $label = $contentObjectRenderer->stdWrap($label, $this->conf['ipp.']['label.']);
        $pairs = [];
        foreach ($this->configuration['pref_navi']['pref_ipps'] as $ii) {
            $pairs[$ii] = '&nbsp;'.strval($ii).'&nbsp;';
        }
        $attributes = [
            'name' => 'tx_bib_pi1[items_per_page]',
            'onchange' => 'this.form.submit()',
        ];
        if (strlen($this->conf['ipp.']['select_class']) > 0) {
            $attributes['class'] = $this->conf['ipp.']['select_class'];
        }
        $button = Utility::html_select_input($pairs, $this->configuration['sub_page']['ipp'], $attributes);
        $button = $contentObjectRenderer->stdWrap($button, $this->conf['ipp.']['select.']);

        return $$contentObjectRenderer->stdWrap($label.$button, $this->conf['ipp.']['widget.']);
    }

    /**
     * Checkbox for showing or hiding abstracts.
     *
     * @return string
     */
    protected function getAbstractSelection()
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $attributes = ['onchange' => 'this.form.submit()'];
        if (strlen($this->conf['abstract.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['abstract.']['btn_class'];
        }

        $label = $this->languageService->getLL('prefNav_show_abstracts');
        $label = $contentObjectRenderer->stdWrap($label, $this->conf['abstract.']['label.']);
        $check = $this->configuration['hide_fields']['abstract'] ? false : true;
        $button = Utility::html_check_input(
            'tx_bib_pi1[show_abstracts]',
            '1',
            $check,
            $attributes
        );
        $button = $contentObjectRenderer->stdWrap($button, $this->conf['abstract.']['btn.']);

        return $contentObjectRenderer->stdWrap($label.$button, $this->conf['abstract.']['widget.']);
    }

    /**
     * Checkbox for showing or hiding keywords.
     *
     * @return string
     */
    protected function getKeywordSelection()
    {
        $attributes = ['onchange' => 'this.form.submit()'];
        if (strlen($this->conf['keywords.']['btn_class']) > 0) {
            $attributes['class'] = $this->conf['keywords.']['btn_class'];
        }

        $label = $this->languageService->getLL('prefNav_show_keywords');
        $label = $this->pi1->cObj->stdWrap($label, $this->conf['keywords.']['label.']);
        $check = $this->pi1->extConf['hide_fields']['keywords'] ? false : true;
        $button = Utility::html_check_input(
            $this->pi1->prefix_pi1.'[show_keywords]',
            '1',
            $check,
            $attributes
        );
        $button = $this->pi1->cObj->stdWrap($button, $this->conf['keywords.']['btn.']);

        return $this->pi1->cObj->stdWrap($label.$button, $this->conf['keywords.']['widget.']);
    }

    /**
     * Generates the go button to apply the preference navigation.
     *
     * @return string
     */
    protected function getGoButton()
    {
        $attributes = [];
        if (strlen($this->conf['go_btn_class']) > 0) {
            $attributes['class'] = $this->conf['go_btn_class'];
        }
        $widget = Utility::html_submit_input(
            $this->pi1->prefix_pi1.'[action][eval_pref]',
            $this->languageService->getLL('button_go'),
            $attributes
        );

        return $this->pi1->cObj->stdWrap($widget, $this->conf['go_btn.']);
    }

    /**
     * Get the label or header for the preference navigation.
     *
     * @return string
     */
    protected function getPreferenceNavigationLabel()
    {
        $label = $this->languageService->getLL('prefNav_label');

        return $this->pi1->cObj->stdWrap($label, $this->conf['label.']);
    }

    /**
     * Starting tag of the form element.
     *
     * @return string
     */
    protected function getFormTagStart()
    {
        $emptySelection = [
            'items_per_page' => '',
            'show_abstracts' => '',
            'show_keywords' => '',
        ];
        $formStart = '';
        $formStart .= '<form name="'.$this->pi1->prefix_pi1.'-preferences_form" ';
        $formStart .= 'action="'.$this->pi1->get_link_url($emptySelection, false).'"';
        $formStart .= ' method="post"';
        $formStart .= strlen($this->conf['form_class']) ? ' class="'.$this->conf['form_class'].'"' : '';
        $formStart .= '>';

        return $formStart;
    }

    protected function sel_get_text(int $index): string
    {
        return '';
    }

    /**
     * @param $text
     * @param $index
     *
     * @return mixed
     */
    protected function sel_get_link($text, $index)
    {
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/PreferenceNavigation.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/PreferenceNavigation.php'];
}
