<?php

namespace Ipf\Bib\Domain\Model;

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

/**
 * Model for Author.
 */
class Author extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $foreName;

    /**
     * @var string
     */
    protected $surName;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var int
     */
    protected $frontEndUserId;

    /**
     * @return string
     */
    public function getForeName()
    {
        return $this->foreName;
    }

    /**
     * @param string $foreName
     */
    public function setForeName($foreName)
    {
        $this->foreName = $foreName;
    }

    /**
     * @return int
     */
    public function getFrontEndUserId()
    {
        return $this->frontEndUserId;
    }

    /**
     * @param int $frontEndUserId
     */
    public function setFrontEndUserId($frontEndUserId)
    {
        $this->frontEndUserId = $frontEndUserId;
    }

    /**
     * @return string
     */
    public function getSurName()
    {
        return $this->surName;
    }

    /**
     * @param string $surName
     */
    public function setSurName($surName)
    {
        $this->surName = $surName;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}
