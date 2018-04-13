<?php

namespace Ipf\Bib\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Bib Link ViewHelper.
 */
class LinkViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('content', 'string', 'The content to be passed to the link', true);
        $this->registerArgument('linkVariables', 'array', 'LinkVariables', false, []);
        $this->registerArgument('autoCache', 'bool', 'Auto Cache', false, true);
        $this->registerArgument('attributes', 'array', 'Link attributes', false, []);
    }

    public function render()
    {
        $url = $this->get_link_url($this->arguments['linkVariables'], $this->arguments['autoCache']);

        return $this->composeLink($url, $this->arguments['content'], $this->arguments['attributes']);
    }

    /**
     * Same as get_link but returns just the URL.
     *
     * @param array $linkVariables
     * @param bool  $autoCache
     * @param bool  $currentRecord
     *
     * @return string The url
     */
    public function get_link_url($linkVariables = [], $autoCache = true, $currentRecord = true)
    {
        if ($this->extConf['edit_mode']) {
            $autoCache = false;
        }

        $linkVariables = array_merge($this->extConf['link_vars'], $linkVariables);
        $linkVariables = [$this->prefix_pi1 => $linkVariables];

        $record = '';
        if ($this->extConf['ce_links'] && $currentRecord) {
            $record = '#c'.strval($this->cObj->data['uid']);
        }

        $this->pi_linkTP('x', $linkVariables, $autoCache);
        $url = $this->cObj->lastTypoLinkUrl.$record;

        $url = preg_replace('/&([^;]{8})/', '&amp;\\1', $url);

        return $url;
    }

    /*
    * Composes a link of an url an some attributes.
    *
    * @param string $url
    * @param string $content
    * @param array  $attributes
    *
    * @return string The link (HTML <a> element)
    */
    protected function composeLink($url, $content, $attributes = null)
    {
        $linkString = '<a href="'.$url.'"';
        if (is_array($attributes)) {
            foreach ($attributes as $k => $v) {
                $linkString .= ' '.$k.'="'.$v.'"';
            }
        }
        $linkString .= '>'.$content.'</a>';

        return $linkString;
    }
}
