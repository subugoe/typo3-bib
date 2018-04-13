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
 * Reference Model.
 */
class Reference extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var int
     */
    protected $bibtype;

    /**
     * @var string
     */
    protected $citeid;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $journal;

    /**
     * @var int
     */
    protected $year;

    /**
     * @var int
     */
    protected $month;

    /**
     * @var int
     */
    protected $day;

    /**
     * @var string
     */
    protected $volume;

    /**
     * @var string
     */
    protected $number;

    /**
     * @var string
     */
    protected $number2;

    /**
     * @var string
     */
    protected $pages;

    /**
     * @var string
     */
    protected $abstract;

    /**
     * @var string
     */
    protected $fullText;

    /**
     * @var int
     */
    protected $fullTextTstamp;

    /**
     * @var string
     */
    protected $fullTextFileUrl;

    /**
     * @var string
     */
    protected $affiliation;

    /**
     * @var string
     */
    protected $note;

    /**
     * @var string
     */
    protected $annotation;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var string
     */
    protected $tags;

    /**
     * @var string
     */
    protected $fileUrl;

    /**
     * @var string
     */
    protected $webUrl;

    /**
     * @var string
     */
    protected $webUrlDate;

    /**
     * @var string
     */
    protected $webUrl2;

    /**
     * @var string
     */
    protected $webUrl2Date;

    /**
     * @var string
     */
    protected $misc;

    /**
     * @var string
     */
    protected $misc2;

    /**
     * @var string
     */
    protected $editor;

    /**
     * @var string
     */
    protected $publisher;

    /**
     * @var string
     */
    protected $howpublished;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $series;

    /**
     * @var string
     */
    protected $edition;

    /**
     * @var string
     */
    protected $chapter;

    /**
     * @var string
     */
    protected $booktitle;

    /**
     * @var string
     */
    protected $school;

    /**
     * @var string
     */
    protected $institute;

    /**
     * @var string
     */
    protected $organization;

    /**
     * @var string
     */
    protected $institution;

    /**
     * @var string
     */
    protected $eventName;

    /**
     * @var string
     */
    protected $eventPlace;

    /**
     * @var string
     */
    protected $eventDate;

    /**
     * @var int
     */
    protected $state;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $ISBN;

    /**
     * @var string
     */
    protected $ISSN;

    /**
     * @var string
     */
    protected $DOI;

    /**
     * @var string
     */
    private $DOIUrl;

    /**
     * @var bool
     */
    protected $extern;

    /**
     * @var bool
     */
    protected $reviewed;

    /**
     * @var bool
     */
    protected $inLibrary;

    /**
     * @var string
     */
    protected $borrowedBy;

    /**
     * @var array
     */
    private $authors = [];

    /**
     * @var bool
     */
    private $hidden = false;

    /**
     * @var string
     */
    private $autoUrl;

    /**
     * @return int
     */
    public function getBibtype(): int
    {
        return $this->bibtype;
    }

    /**
     * @param int $bibtype
     *
     * @return Reference
     */
    public function setBibtype(int $bibtype): Reference
    {
        $this->bibtype = $bibtype;

        return $this;
    }

    /**
     * @return string
     */
    public function getCiteid(): string
    {
        return $this->citeid;
    }

    /**
     * @param string $citeid
     *
     * @return Reference
     */
    public function setCiteid(string $citeid): Reference
    {
        $this->citeid = $citeid;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Reference
     */
    public function setTitle(string $title): Reference
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getJournal(): string
    {
        return $this->journal;
    }

    /**
     * @param string $journal
     *
     * @return Reference
     */
    public function setJournal(string $journal): Reference
    {
        $this->journal = $journal;

        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param int $year
     *
     * @return Reference
     */
    public function setYear(int $year): Reference
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @param int $month
     *
     * @return Reference
     */
    public function setMonth(int $month): Reference
    {
        $this->month = $month;

        return $this;
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * @param int $day
     *
     * @return Reference
     */
    public function setDay(int $day): Reference
    {
        $this->day = $day;

        return $this;
    }

    /**
     * @return string
     */
    public function getVolume(): string
    {
        return $this->volume;
    }

    /**
     * @param string $volume
     *
     * @return Reference
     */
    public function setVolume(string $volume): Reference
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     *
     * @return Reference
     */
    public function setNumber(string $number): Reference
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber2(): string
    {
        return $this->number2;
    }

    /**
     * @param string $number2
     *
     * @return Reference
     */
    public function setNumber2(string $number2): Reference
    {
        $this->number2 = $number2;

        return $this;
    }

    /**
     * @return string
     */
    public function getPages(): string
    {
        return $this->pages;
    }

    /**
     * @param string $pages
     *
     * @return Reference
     */
    public function setPages(string $pages): Reference
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbstract(): string
    {
        return $this->abstract;
    }

    /**
     * @param string $abstract
     *
     * @return Reference
     */
    public function setAbstract(string $abstract): Reference
    {
        $this->abstract = $abstract;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullText(): string
    {
        return $this->fullText;
    }

    /**
     * @param string $fullText
     *
     * @return Reference
     */
    public function setFullText(string $fullText): Reference
    {
        $this->fullText = $fullText;

        return $this;
    }

    /**
     * @return int
     */
    public function getFullTextTstamp(): int
    {
        return $this->fullTextTstamp;
    }

    /**
     * @param int $fullTextTstamp
     *
     * @return Reference
     */
    public function setFullTextTstamp(int $fullTextTstamp): Reference
    {
        $this->fullTextTstamp = $fullTextTstamp;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullTextFileUrl(): string
    {
        return $this->fullTextFileUrl;
    }

    /**
     * @param string $fullTextFileUrl
     *
     * @return Reference
     */
    public function setFullTextFileUrl(string $fullTextFileUrl): Reference
    {
        $this->fullTextFileUrl = $fullTextFileUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getAffiliation(): string
    {
        return $this->affiliation;
    }

    /**
     * @param string $affiliation
     *
     * @return Reference
     */
    public function setAffiliation(string $affiliation): Reference
    {
        $this->affiliation = $affiliation;

        return $this;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * @param string $note
     *
     * @return Reference
     */
    public function setNote(string $note): Reference
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return string
     */
    public function getAnnotation(): string
    {
        return $this->annotation;
    }

    /**
     * @param string $annotation
     *
     * @return Reference
     */
    public function setAnnotation(string $annotation): Reference
    {
        $this->annotation = $annotation;

        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     *
     * @return Reference
     */
    public function setKeywords(string $keywords): Reference
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * @return string
     */
    public function getTags(): string
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     *
     * @return Reference
     */
    public function setTags(string $tags): Reference
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }

    /**
     * @param string $fileUrl
     *
     * @return Reference
     */
    public function setFileUrl(string $fileUrl): Reference
    {
        $this->fileUrl = $fileUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebUrl(): string
    {
        return $this->webUrl;
    }

    /**
     * @param string $webUrl
     *
     * @return Reference
     */
    public function setWebUrl(string $webUrl): Reference
    {
        $this->webUrl = $webUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebUrlDate(): string
    {
        return $this->webUrlDate;
    }

    /**
     * @param string $webUrlDate
     *
     * @return Reference
     */
    public function setWebUrlDate(string $webUrlDate): Reference
    {
        $this->webUrlDate = $webUrlDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebUrl2(): string
    {
        return $this->webUrl2;
    }

    /**
     * @param string $webUrl2
     *
     * @return Reference
     */
    public function setWebUrl2(string $webUrl2): Reference
    {
        $this->webUrl2 = $webUrl2;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebUrl2Date(): string
    {
        return $this->webUrl2Date;
    }

    /**
     * @param string $webUrl2Date
     *
     * @return Reference
     */
    public function setWebUrl2Date(string $webUrl2Date): Reference
    {
        $this->webUrl2Date = $webUrl2Date;

        return $this;
    }

    /**
     * @return string
     */
    public function getMisc(): string
    {
        return $this->misc;
    }

    /**
     * @param string $misc
     *
     * @return Reference
     */
    public function setMisc(string $misc): Reference
    {
        $this->misc = $misc;

        return $this;
    }

    /**
     * @return string
     */
    public function getMisc2(): string
    {
        return $this->misc2;
    }

    /**
     * @param string $misc2
     *
     * @return Reference
     */
    public function setMisc2(string $misc2): Reference
    {
        $this->misc2 = $misc2;

        return $this;
    }

    /**
     * @return string
     */
    public function getEditor(): string
    {
        return $this->editor;
    }

    /**
     * @param string $editor
     *
     * @return Reference
     */
    public function setEditor(string $editor): Reference
    {
        $this->editor = $editor;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublisher(): string
    {
        return $this->publisher;
    }

    /**
     * @param string $publisher
     *
     * @return Reference
     */
    public function setPublisher(string $publisher): Reference
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * @return string
     */
    public function getHowpublished(): string
    {
        return $this->howpublished;
    }

    /**
     * @param string $howpublished
     *
     * @return Reference
     */
    public function setHowpublished(string $howpublished): Reference
    {
        $this->howpublished = $howpublished;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     *
     * @return Reference
     */
    public function setAddress(string $address): Reference
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getSeries(): string
    {
        return $this->series;
    }

    /**
     * @param string $series
     *
     * @return Reference
     */
    public function setSeries(string $series): Reference
    {
        $this->series = $series;

        return $this;
    }

    /**
     * @return string
     */
    public function getEdition(): string
    {
        return $this->edition;
    }

    /**
     * @param string $edition
     *
     * @return Reference
     */
    public function setEdition(string $edition): Reference
    {
        $this->edition = $edition;

        return $this;
    }

    /**
     * @return string
     */
    public function getChapter(): string
    {
        return $this->chapter;
    }

    /**
     * @param string $chapter
     *
     * @return Reference
     */
    public function setChapter(string $chapter): Reference
    {
        $this->chapter = $chapter;

        return $this;
    }

    /**
     * @return string
     */
    public function getBooktitle(): string
    {
        return $this->booktitle;
    }

    /**
     * @param string $booktitle
     *
     * @return Reference
     */
    public function setBooktitle(string $booktitle): Reference
    {
        $this->booktitle = $booktitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getSchool(): string
    {
        return $this->school;
    }

    /**
     * @param string $school
     *
     * @return Reference
     */
    public function setSchool(string $school): Reference
    {
        $this->school = $school;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstitute(): string
    {
        return $this->institute;
    }

    /**
     * @param string $institute
     *
     * @return Reference
     */
    public function setInstitute(string $institute): Reference
    {
        $this->institute = $institute;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    /**
     * @param string $organization
     *
     * @return Reference
     */
    public function setOrganization(string $organization): Reference
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * @param string $institution
     *
     * @return Reference
     */
    public function setInstitution(string $institution): Reference
    {
        $this->institution = $institution;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @param string $eventName
     *
     * @return Reference
     */
    public function setEventName(string $eventName): Reference
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventPlace(): string
    {
        return $this->eventPlace;
    }

    /**
     * @param string $eventPlace
     *
     * @return Reference
     */
    public function setEventPlace(string $eventPlace): Reference
    {
        $this->eventPlace = $eventPlace;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventDate(): string
    {
        return $this->eventDate;
    }

    /**
     * @param string $eventDate
     *
     * @return Reference
     */
    public function setEventDate(string $eventDate): Reference
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     *
     * @return Reference
     */
    public function setState(int $state): Reference
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Reference
     */
    public function setType(string $type): Reference
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return Reference
     */
    public function setLanguage(string $language): Reference
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return string
     */
    public function getISBN(): string
    {
        return $this->ISBN;
    }

    /**
     * @param string $ISBN
     *
     * @return Reference
     */
    public function setISBN(string $ISBN): Reference
    {
        $this->ISBN = $ISBN;

        return $this;
    }

    /**
     * @return string
     */
    public function getISSN(): string
    {
        return $this->ISSN;
    }

    /**
     * @param string $ISSN
     *
     * @return Reference
     */
    public function setISSN(string $ISSN): Reference
    {
        $this->ISSN = $ISSN;

        return $this;
    }

    /**
     * @return string
     */
    public function getDOI(): string
    {
        return $this->DOI;
    }

    /**
     * @param string $DOI
     *
     * @return Reference
     */
    public function setDOI(string $DOI): Reference
    {
        $this->DOI = $DOI;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExtern(): bool
    {
        return $this->extern;
    }

    /**
     * @param bool $extern
     *
     * @return Reference
     */
    public function setExtern(bool $extern): Reference
    {
        $this->extern = $extern;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReviewed(): bool
    {
        return $this->reviewed;
    }

    /**
     * @param bool $reviewed
     *
     * @return Reference
     */
    public function setReviewed(bool $reviewed): Reference
    {
        $this->reviewed = $reviewed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInLibrary(): bool
    {
        return $this->inLibrary;
    }

    /**
     * @param bool $inLibrary
     *
     * @return Reference
     */
    public function setInLibrary(bool $inLibrary): Reference
    {
        $this->inLibrary = $inLibrary;

        return $this;
    }

    /**
     * @return string
     */
    public function getBorrowedBy(): string
    {
        return $this->borrowedBy;
    }

    /**
     * @param string $borrowedBy
     *
     * @return Reference
     */
    public function setBorrowedBy(string $borrowedBy): Reference
    {
        $this->borrowedBy = $borrowedBy;

        return $this;
    }

    /**
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param array $authors
     *
     * @return Reference
     */
    public function setAuthors(array $authors): Reference
    {
        $this->authors = $authors;

        return $this;
    }

    /**
     * @return string
     */
    public function getDOIUrl(): string
    {
        return $this->DOIUrl;
    }

    /**
     * @param string $DOIUrl
     *
     * @return Reference
     */
    public function setDOIUrl(string $DOIUrl): Reference
    {
        $this->DOIUrl = $DOIUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getAutoUrl(): string
    {
        return $this->autoUrl;
    }

    /**
     * @param string $autoUrl
     *
     * @return Reference
     */
    public function setAutoUrl(string $autoUrl): Reference
    {
        $this->autoUrl = $autoUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     *
     * @return Reference
     */
    public function setHidden(bool $hidden): Reference
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     *
     * @return Reference
     */
    public function setUid(int $uid): Reference
    {
        $this->uid = $uid;

        return $this;
    }
}
