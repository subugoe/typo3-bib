<?php

namespace Ipf\Bib\ViewHelpers;

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
use Ipf\Bib\Exception\DataException;
use Ipf\Bib\Utility\ReferenceReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Retrieve an array of the publication by providing a CiteId.
 *
 * Usage:
 * First: Declare the namespace for this extension:
 *
 * {namespace bib=Ipf\Bib\ViewHelpers}
 *
 * Than create an alias block for the result and call the properties inside this block:
 *
 * <f:alias map="{bib:\"{bib:publicationByCiteId(citeId:'2r')}\"}" >
 *     {bib.publisher}
 * </f:alias>
 */
class PublicationByCiteIdViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('citeId', 'string', 'Citation id');
        $this->registerArgument('storagePid', 'int', 'Storage PID where the bibliography records are stored');
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function render()
    {
        if ($this->hasArgument('citeId')) {
            $citationId = $this->arguments['citeId'];
        } else {
            $citationId = $this->renderChildren();
        }

        $this->hasArgument('storagePid') ? $storagePid = intval($this->arguments['storagePid']) : $storagePid = null;

        if (empty($citationId)) {
            throw new \Exception('A citation Id has to be Provided for ' . __CLASS__, 1378194424);
        } else {
            try {
                return $this->getBibliographicDataFromCitationId($citationId, $storagePid);
            } catch (\Exception $e) {
                return ['exception' => $e->getMessage()];
            }
        }
    }

    /**
     * @throws \Exception
     *
     * @param string $citationId
     * @param int    $storagePid
     *
     * @return array
     */
    protected function getBibliographicDataFromCitationId($citationId, $storagePid)
    {

        /** @var \Ipf\Bib\Utility\ReferenceReader $referenceReader */
        $referenceReader = GeneralUtility::makeInstance(ReferenceReader::class);

        $referenceReader->setPidList([$storagePid]);

        if ($referenceReader->citeIdExists($citationId)) {
            $referenceReader->append_filter(
                [
                    'citeid' => [
                        'ids' => $citationId,
                    ],
                ]
            );
        } else {
            throw new DataException('Citation Id ' . $citationId . ' does not exist', 1378195258);
        }

        $citationUid = $referenceReader->getUidFromCitationId($citationId);

        return $referenceReader->getPublicationDetails($citationUid);
    }
}
