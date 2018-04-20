<?php

namespace Ipf\Bib\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

class NavigationSelectionViewHelper extends AbstractViewHelper implements ViewHelperInterface
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('configurationSelection', 'array', 'Selection configuration');
        $this->registerArgument('indices', 'array', 'List of indices');
        $this->registerArgument('numberOfItemsToBeDisplayed', 'int', 'Number of items to be displayed');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $numberOfItemsToBeDisplayed = $arguments['numberOfItemsToBeDisplayed'];
        $configurationSelection = $arguments['configurationSelection'];

        $sel = [
                    'prev' => [],
                    'cur' => [],
                    'next' => [],
                ];

        // Determine ranges of year navigation bar
        $minimumRange = (int) $arguments['indices'][0];
        $currentRange = (int) $arguments['indices'][1];
        $maximumRange = (int) $arguments['indices'][2];

        $no_cur = false;
        if ($currentRange < 0) {
            $currentRange = floor(($maximumRange - $minimumRange) / 2);
            $no_cur = true;
        }

        // Number of items to display in the selection - must be odd
        $numberOfItemsToBeDisplayed = ($numberOfItemsToBeDisplayed % 2) ? $numberOfItemsToBeDisplayed : ($numberOfItemsToBeDisplayed + 1);
        $numLR = (int) ($numberOfItemsToBeDisplayed - 1) / 2;

        $minimumRange = $minimumRange + 1;

        $idx1 = $currentRange - $numLR;
        if ($idx1 < $minimumRange) {
            $idx1 = $minimumRange;
            $numLR = $numLR + ($numLR - $currentRange) + 1;
        }
        $idx2 = ($currentRange + $numLR);
        if ($idx2 > ($maximumRange - 1)) {
            $idx2 = $maximumRange - 1;
            $numLR += $numLR - ($maximumRange - $currentRange) + 1;
            $idx1 = max($minimumRange, $currentRange - $numLR);
        }

        $sel = self::generateYearNavigationBar($configurationSelection, $maximumRange, $currentRange, $no_cur, $idx1, $idx2, $sel);

        // Item separator
        $itemSeparator = '&nbsp;';
        if (array_key_exists('separator', $configurationSelection)) {
            $itemSeparator = (string) $configurationSelection['separator'];
        }
        if (is_array($configurationSelection['separator.'])) {
            $itemSeparator = $cObj->stdWrap($itemSeparator, $configurationSelection['separator.']);
        }

        // Setup the translator
        $res = implode($itemSeparator, $sel['prev']);
        $res .= (count($sel['prev']) ? $itemSeparator : '');
        $res .= implode($itemSeparator, $sel['cur']);
        $res .= (count($sel['next']) ? $itemSeparator : '');
        $res .= implode($itemSeparator, $sel['next']);

        return $res;
    }

    /**
     * @param array $cfgSel
     * @param $maximumRange
     * @param $currentRange
     * @param $no_cur
     * @param $idx1
     * @param $idx2
     * @param $cObj
     * @param $sel
     *
     * @return mixed
     */
    private static function generateYearNavigationBar(
        array $cfgSel,
        int $maximumRange,
        int $currentRange,
        bool $no_cur,
        $idx1,
        $idx2,
        $sel
    ) {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $ii = 0;
        while ($ii <= $maximumRange) {
            $text = $this->sel_get_text($ii);
            $cr_link = true;

            if ($ii == $currentRange) { // Current
                $key = 'cur';
                if (!$no_cur) {
                    $wrap = $cfgSel['current.'];
                    $cr_link = false;
                } else {
                    $wrap = $cfgSel['below.'];
                }
            } else {
                if (0 === $ii) { // First
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
                        if ($ii < $currentRange) { // Previous
                            $key = 'prev';
                            $wrap = $cfgSel['below.'];
                        } else {
                            if ($ii <= $idx2) { // Following
                                $key = 'next';
                                $wrap = $cfgSel['above.'];
                            } else {
                                if ($ii < $maximumRange) { // More after
                                    $key = 'next';
                                    $text = '...';
                                    if (array_key_exists('more_above', $cfgSel)) {
                                        $text = strval($cfgSel['more_above']);
                                    }
                                    $wrap = $cfgSel['more_above.'];
                                    $cr_link = false;
                                    $ii = $maximumRange - 1;
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
            ++$ii;
        }

        return $sel;
    }
}
