<?php

namespace Ipf\Bib\Exporter;

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

use Ipf\Bib\Domain\Model\Reference;
use Ipf\Bib\Utility\ReferenceReader;

/**
 * Class XmlExporter.
 */
class XmlExporter extends Exporter
{
    /**
     * @var array
     */
    protected $pattern = [];

    /**
     * @var array
     */
    protected $replacement = [];

    public function initialize()
    {
        parent::initialize();

        $this->pattern[] = '/&/';
        $this->replacement[] = '&amp;';
        $this->pattern[] = '/</';
        $this->replacement[] = '&lt;';
        $this->pattern[] = '/>/';
        $this->replacement[] = '&gt;';

        $this->setFileName('bib_'.$this->filterKey.'.xml');
    }

    /**
     * @param Reference $publication
     * @param array     $infoArr
     *
     * @return string
     */
    protected function formatPublicationForExport(Reference $publication, $infoArr = [])
    {
        $content = '<reference>'.PHP_EOL;
        $reflectionObject = new \ReflectionObject($publication);
        foreach ($reflectionObject->getProperties() as $prop) {
            $prop->setAccessible(true);
            $append = true;

            switch ($prop->getName()) {
                case 'authors':
                    $value = $publication->getAuthors();
                    if (0 === count($value)) {
                        $append = false;
                    }
                    break;
                default:
                    $value = $prop->getValue($publication);
                    if ((0 === strlen($value)) || ('0' === $value)) {
                        $append = false;
                    }
            }

            if ($append) {
                $content .= $this->xmlFormatField($prop->getName(), $value);
            }
        }

        $content .= '</reference>'.PHP_EOL;

        return $content;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    protected function xmlFormatField($key, $value)
    {
        $content = '';
        switch ($key) {
            case 'authors':
                $authors = is_array($value) ? $value : explode(' and ', $value);
                $value = '';
                $aXML = [];
                foreach ($authors as $author) {
                    $a_str = '';
                    $foreName = $this->xmlFormatString($author->getForeName());
                    $surName = $this->xmlFormatString($author->getSurName());
                    if (strlen($foreName)) {
                        $a_str .= '<fn>'.$foreName.'</fn>';
                    }
                    if (strlen($surName)) {
                        $a_str .= '<sn>'.$surName.'</sn>';
                    }
                    if (strlen($a_str)) {
                        $aXML[] = $a_str;
                    }
                }
                if (count($aXML)) {
                    $value .= PHP_EOL;
                    foreach ($aXML as $author) {
                        $value .= '<person>'.$author.'</person>'.PHP_EOL;
                    }
                }
                break;
            case 'bibtype':
                $value = ReferenceReader::$allBibTypes[$value];
                $value = $this->xmlFormatString($value);
                break;
            case 'state':
                $value = ReferenceReader::$allStates[$value];
                $value = $this->xmlFormatString($value);
                break;
            default:
                $value = $this->xmlFormatString($value);
        }
        $content .= '<'.$key.'>'.$value.'</'.$key.'>'.PHP_EOL;

        return $content;
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function xmlFormatString($value)
    {
        $value = preg_replace($this->pattern, $this->replacement, $value);

        return $value;
    }

    /**
     * @param array $infoArr
     *
     * @return string
     */
    protected function fileIntro($infoArr = [])
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
        $content .= '<bib>'.PHP_EOL;
        $content .= '<comment>'.PHP_EOL;
        $content .= $this->xmlFormatString($this->getGeneralInformationText());
        $content .= '</comment>'.PHP_EOL;

        return $content;
    }

    /**
     * @param array $infoArr
     *
     * @return string
     */
    protected function fileOutro(array $infoArr = []): string
    {
        $content = '</bib>'.PHP_EOL;

        return $content;
    }
}