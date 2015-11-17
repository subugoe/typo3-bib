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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Navigation.
 */
abstract class Navigation
{
    /**
     * @var \tx_bib_pi1
     */
    public $pi1;

    /**
     * The template string.
     *
     * @var string
     */
    public $template;

    /**
     * A prefix string.
     *
     * @var string
     */
    public $prefix;

    /**
     * @var array
     */
    protected $conf = [];

    /**
     * @var string
     */
    protected $sel_link_title;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * @param \tx_bib_pi1 $pi1
     */
    public function initialize($pi1)
    {
        $this->pi1 = &$pi1;

        /* @var \TYPO3\CMS\Fluid\View\StandaloneView $template */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($this->getTemplateFileFromCallingClass());
        $this->view = $view;
    }

    /**
     * @return string
     */
    protected function getTemplateFileFromCallingClass()
    {
        $classParts = explode('\\', get_called_class());
        $templateName = str_replace('Navigation', '', $classParts[3]);
        $templateFile = ExtensionManagementUtility::extPath($this->pi1->extKey) . '/Resources/Private/Templates/Navigation/' . $templateName . '.html';

        return $templateFile;
    }

    /**
     * Returns the translator for the main template.
     *
     * @return array
     */
    public function translator()
    {
        $pref = '###' . $this->prefix;
        $content = $this->get();

        $res = [];
        $res[$pref . '###'] = $content;

        $val = '';
        if ($this->conf['top_disable'] == 0) {
            $val = $this->pi1->cObj->stdWrap($content, $this->conf['top.']);
        }
        $res[$pref . '_TOP###'] = $val;

        $val = '';
        if ($this->conf['bottom_disable'] == 0) {
            $val = $this->pi1->cObj->stdWrap($content, $this->conf['bottom.']);
        }
        $res[$pref . '_BOTTOM###'] = $val;

        return $res;
    }

    /**
     * @return string
     */
    abstract protected function get();

    /**
     * @param $index
     *
     * @return mixed
     */
    abstract protected function sel_get_text($index);

    /**
     * @param $text
     * @param $index
     *
     * @return mixed
     */
    abstract protected function sel_get_link($text, $index);

    /**
     * Returns a selection translator.
     *
     * @param array $cfgSel
     * @param array $indices
     * @param int   $numSel
     *
     * @return string
     */
    protected function selection($cfgSel, $indices, $numSel)
    {
        $cObj = &$this->pi1->cObj;

        $sel = [
            'prev' => [],
            'cur' => [],
            'next' => [],
        ];

        // Determine ranges of year navigation bar
        $idxMin = $indices[0];
        $idxCur = $indices[1];
        $idxMax = $indices[2];

        $no_cur = false;
        if ($idxCur < 0) {
            $idxCur = floor(($idxMax - $idxMin) / 2);
            $no_cur = true;
        }

        // Number of items to display in the selection - must be odd
        $numSel = intval($numSel);
        $numSel = ($numSel % 2) ? $numSel : ($numSel + 1);
        $numLR = intval(($numSel - 1) / 2);

        $idxMin = $idxMin + 1;

        $idx1 = $idxCur - $numLR;
        if ($idx1 < $idxMin) {
            $idx1 = $idxMin;
            $numLR = $numLR + ($numLR - $idxCur) + 1;
        }
        $idx2 = ($idxCur + $numLR);
        if ($idx2 > ($idxMax - 1)) {
            $idx2 = $idxMax - 1;
            $numLR += $numLR - ($idxMax - $idxCur) + 1;
            $idx1 = max($idxMin, $idxCur - $numLR);
        }

        // Generate year navigation bar
        $ii = 0;
        while ($ii <= $idxMax) {
            $text = $this->sel_get_text($ii);
            $cr_link = true;

            if ($ii == $idxCur) { // Current
                $key = 'cur';
                if (!$no_cur) {
                    $wrap = $cfgSel['current.'];
                    $cr_link = false;
                } else {
                    $wrap = $cfgSel['below.'];
                }
            } else {
                if ($ii == 0) { // First
                    $key = 'prev';
                    $wrap = $cfgSel['first.'];
                } else {
                    if ($ii < $idx1) { // More before
                        $key = 'prev';
                        $text = '...';
                        if (array_key_exists('more_below', $cfgSel)) {
                            $text = strval($cfgSel['more_below']);
                        }
                        $wrap = $cfgSel['more_below.'];
                        $cr_link = false;
                        $ii = $idx1 - 1;
                    } else {
                        if ($ii < $idxCur) { // Previous
                            $key = 'prev';
                            $wrap = $cfgSel['below.'];
                        } else {
                            if ($ii <= $idx2) { // Following
                                $key = 'next';
                                $wrap = $cfgSel['above.'];
                            } else {
                                if ($ii < $idxMax) { // More after
                                    $key = 'next';
                                    $text = '...';
                                    if (array_key_exists('more_above', $cfgSel)) {
                                        $text = strval($cfgSel['more_above']);
                                    }
                                    $wrap = $cfgSel['more_above.'];
                                    $cr_link = false;
                                    $ii = $idxMax - 1;
                                } else { // Last
                                    $key = 'next';
                                    $wrap = $cfgSel['last.'];
                                }
                            }
                        }
                    }
                }
            }

            // Create link
            if ($cr_link) {
                $text = $this->sel_get_link($text, $ii);
            }
            if (is_array($wrap)) {
                $text = $cObj->stdWrap($text, $wrap);
            }

            $sel[$key][] = $text;
            $ii += 1;
        }

        // Item separator
        $sep = '&nbsp;';
        if (array_key_exists('separator', $cfgSel)) {
            $sep = strval($cfgSel['separator']);
        }
        if (is_array($cfgSel['separator.'])) {
            $sep = $cObj->stdWrap($sep, $cfgSel['separator.']);
        }

        // Setup the translator
        $res = implode($sep, $sel['prev']);
        $res .= (sizeof($sel['prev']) ? $sep : '');
        $res .= implode($sep, $sel['cur']);
        $res .= (sizeof($sel['next']) ? $sep : '');
        $res .= implode($sep, $sel['next']);

        return $res;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/Navigation.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/bib/Classes/Navigation/Navigation.php'];
}
