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
     * @param int $uid
     *
     * @return Author
     */
    public function setUid(int $uid): Author
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return string
     */
    public function getForeName(): string
    {
        return $this->foreName;
    }

    /**
     * @param string $foreName
     *
     * @return Author
     */
    public function setForeName(string $foreName): Author
    {
        $this->foreName = $foreName;

        return $this;
    }

    /**
     * @return string
     */
    public function getSurName(): string
    {
        return $this->surName;
    }

    /**
     * @param string $surName
     *
     * @return Author
     */
    public function setSurName(string $surName): Author
    {
        $this->surName = $surName;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Author
     */
    public function setUrl(string $url): Author
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return int
     */
    public function getFrontEndUserId(): int
    {
        return $this->frontEndUserId;
    }

    /**
     * @param int $frontEndUserId
     *
     * @return Author
     */
    public function setFrontEndUserId(int $frontEndUserId): Author
    {
        $this->frontEndUserId = $frontEndUserId;

        return $this;
    }
}
