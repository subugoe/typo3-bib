<?php

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
 * References Model
 */
class Tx_Bib_Domain_Model_References extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var int
	 */
	protected $bibtype;

	/**
	 * @var String
	 */
	protected $citeid;

	/**
	 * @var String
	 */
	protected $title;

	/**
	 * @var String
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
	 * @var String
	 */
	protected $volume;

	/**
	 * @var String
	 */
	protected $number;

	/**
	 * @var String
	 */
	protected $number2;

	/**
	 * @var String
	 */
	protected $pages;

	/**
	 * @var String
	 */
	protected $abstract;

	/**
	 * @var String
	 */
	protected $fullText;

	/**
	 * @var int
	 */
	protected $fullTextTstamp;

	/**
	 * @var String
	 */
	protected $fullTextFileUrl;

	/**
	 * @var String
	 */
	protected $affiliation;

	/**
	 * @var String
	 */
	protected $note;

	/**
	 * @var String
	 */
	protected $annotation;

	/**
	 * @var String
	 */
	protected $keywords;

	/**
	 * @var String
	 */
	protected $tags;

	/**
	 * @var String
	 */
	protected $fileUrl;

	/**
	 * @var String
	 */
	protected $webUrl;

	/**
	 * @var String
	 */
	protected $webUrl2;

	/**
	 * @var String
	 */
	protected $misc;

	/**
	 * @var
	 */
	protected $misc2;

	/**
	 * @var String
	 */
	protected $editor;

	/**
	 * @var String
	 */
	protected $publisher;

	/**
	 * @var String
	 */
	protected $howpublished;

	/**
	 * @var String
	 */
	protected $address;

	/**
	 * @var String
	 */
	protected $series;

	/**
	 * @var String
	 */
	protected $edition;

	/**
	 * @var String
	 */
	protected $chapter;

	/**
	 * @var String
	 */
	protected $booktitle;

	/**
	 * @var String
	 */
	protected $school;

	/**
	 * @var String
	 */
	protected $institute;

	/**
	 * @var String
	 */
	protected $organization;

	/**
	 * @var String
	 */
	protected $institution;

	/**
	 * @var String
	 */
	protected $eventName;

	/**
	 * @var String
	 */
	protected $eventPlace;

	/**
	 * @var String
	 */
	protected $eventDate;

	/**
	 * @var int
	 */
	protected $state;

	/**
	 * @var String
	 */
	protected $type;

	/**
	 * @var String
	 */
	protected $language;

	/**
	 * @var String
	 */
	protected $ISBN;

	/**
	 * @var String
	 */
	protected $ISSN;

	/**
	 * @var String
	 */
	protected $DOI;

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
	 * @var String
	 */
	protected $borrowedBy;

	/**
	 * @param String $DOI
	 */
	public function setDOI($DOI) {
		$this->DOI = $DOI;
	}

	/**
	 * @return String
	 */
	public function getDOI() {
		return $this->DOI;
	}

	/**
	 * @param String $ISBN
	 */
	public function setISBN($ISBN) {
		$this->ISBN = $ISBN;
	}

	/**
	 * @return String
	 */
	public function getISBN() {
		return $this->ISBN;
	}

	/**
	 * @param String $ISSN
	 */
	public function setISSN($ISSN) {
		$this->ISSN = $ISSN;
	}

	/**
	 * @return String
	 */
	public function getISSN() {
		return $this->ISSN;
	}

	/**
	 * @param int $languageUid
	 */
	public function setLanguageUid($languageUid) {
		$this->_languageUid = $languageUid;
	}

	/**
	 * @return int
	 */
	public function getLanguageUid() {
		return $this->_languageUid;
	}

	/**
	 * @param int $localizedUid
	 */
	public function setLocalizedUid($localizedUid) {
		$this->_localizedUid = $localizedUid;
	}

	/**
	 * @return int
	 */
	public function getLocalizedUid() {
		return $this->_localizedUid;
	}

	/**
	 * @param String $abstract
	 */
	public function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	/**
	 * @return String
	 */
	public function getAbstract() {
		return $this->abstract;
	}

	/**
	 * @param String $address
	 */
	public function setAddress($address) {
		$this->address = $address;
	}

	/**
	 * @return String
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 * @param String $affiliation
	 */
	public function setAffiliation($affiliation) {
		$this->affiliation = $affiliation;
	}

	/**
	 * @return String
	 */
	public function getAffiliation() {
		return $this->affiliation;
	}

	/**
	 * @param String $annotation
	 */
	public function setAnnotation($annotation) {
		$this->annotation = $annotation;
	}

	/**
	 * @return String
	 */
	public function getAnnotation() {
		return $this->annotation;
	}

	/**
	 * @param int $bibtype
	 */
	public function setBibtype($bibtype) {
		$this->bibtype = $bibtype;
	}

	/**
	 * @return int
	 */
	public function getBibtype() {
		return $this->bibtype;
	}

	/**
	 * @param String $booktitle
	 */
	public function setBooktitle($booktitle) {
		$this->booktitle = $booktitle;
	}

	/**
	 * @return String
	 */
	public function getBooktitle() {
		return $this->booktitle;
	}

	/**
	 * @param String $borrowedBy
	 */
	public function setBorrowedBy($borrowedBy) {
		$this->borrowedBy = $borrowedBy;
	}

	/**
	 * @return String
	 */
	public function getBorrowedBy() {
		return $this->borrowedBy;
	}

	/**
	 * @param String $chapter
	 */
	public function setChapter($chapter) {
		$this->chapter = $chapter;
	}

	/**
	 * @return String
	 */
	public function getChapter() {
		return $this->chapter;
	}

	/**
	 * @param String $citeid
	 */
	public function setCiteid($citeid) {
		$this->citeid = $citeid;
	}

	/**
	 * @return String
	 */
	public function getCiteid() {
		return $this->citeid;
	}

	/**
	 * @param int $day
	 */
	public function setDay($day) {
		$this->day = $day;
	}

	/**
	 * @return int
	 */
	public function getDay() {
		return $this->day;
	}

	/**
	 * @param String $edition
	 */
	public function setEdition($edition) {
		$this->edition = $edition;
	}

	/**
	 * @return String
	 */
	public function getEdition() {
		return $this->edition;
	}

	/**
	 * @param String $editor
	 */
	public function setEditor($editor) {
		$this->editor = $editor;
	}

	/**
	 * @return String
	 */
	public function getEditor() {
		return $this->editor;
	}

	/**
	 * @param String $eventDate
	 */
	public function setEventDate($eventDate) {
		$this->eventDate = $eventDate;
	}

	/**
	 * @return String
	 */
	public function getEventDate() {
		return $this->eventDate;
	}

	/**
	 * @param String $eventName
	 */
	public function setEventName($eventName) {
		$this->eventName = $eventName;
	}

	/**
	 * @return String
	 */
	public function getEventName() {
		return $this->eventName;
	}

	/**
	 * @param String $eventPlace
	 */
	public function setEventPlace($eventPlace) {
		$this->eventPlace = $eventPlace;
	}

	/**
	 * @return String
	 */
	public function getEventPlace() {
		return $this->eventPlace;
	}

	/**
	 * @param boolean $extern
	 */
	public function setExtern($extern) {
		$this->extern = $extern;
	}

	/**
	 * @return boolean
	 */
	public function getExtern() {
		return $this->extern;
	}

	/**
	 * @param String $fileUrl
	 */
	public function setFileUrl($fileUrl) {
		$this->fileUrl = $fileUrl;
	}

	/**
	 * @return String
	 */
	public function getFileUrl() {
		return $this->fileUrl;
	}

	/**
	 * @param String $fullText
	 */
	public function setFullText($fullText) {
		$this->fullText = $fullText;
	}

	/**
	 * @return String
	 */
	public function getFullText() {
		return $this->fullText;
	}

	/**
	 * @param String $fullTextFileUrl
	 */
	public function setFullTextFileUrl($fullTextFileUrl) {
		$this->fullTextFileUrl = $fullTextFileUrl;
	}

	/**
	 * @return String
	 */
	public function getFullTextFileUrl() {
		return $this->fullTextFileUrl;
	}

	/**
	 * @param int $fullTextTstamp
	 */
	public function setFullTextTstamp($fullTextTstamp) {
		$this->fullTextTstamp = $fullTextTstamp;
	}

	/**
	 * @return int
	 */
	public function getFullTextTstamp() {
		return $this->fullTextTstamp;
	}

	/**
	 * @param String $howpublished
	 */
	public function setHowpublished($howpublished) {
		$this->howpublished = $howpublished;
	}

	/**
	 * @return String
	 */
	public function getHowpublished() {
		return $this->howpublished;
	}

	/**
	 * @param boolean $inLibrary
	 */
	public function setInLibrary($inLibrary) {
		$this->inLibrary = $inLibrary;
	}

	/**
	 * @return boolean
	 */
	public function getInLibrary() {
		return $this->inLibrary;
	}

	/**
	 * @param String $institute
	 */
	public function setInstitute($institute) {
		$this->institute = $institute;
	}

	/**
	 * @return String
	 */
	public function getInstitute() {
		return $this->institute;
	}

	/**
	 * @param String $institution
	 */
	public function setInstitution($institution) {
		$this->institution = $institution;
	}

	/**
	 * @return String
	 */
	public function getInstitution() {
		return $this->institution;
	}

	/**
	 * @param String $journal
	 */
	public function setJournal($journal) {
		$this->journal = $journal;
	}

	/**
	 * @return String
	 */
	public function getJournal() {
		return $this->journal;
	}

	/**
	 * @param String $keywords
	 */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
	 * @return String
	 */
	public function getKeywords() {
		return $this->keywords;
	}

	/**
	 * @param String $language
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}

	/**
	 * @return String
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param String $misc
	 */
	public function setMisc($misc) {
		$this->misc = $misc;
	}

	/**
	 * @return String
	 */
	public function getMisc() {
		return $this->misc;
	}

	/**
	 * @param mixed $misc2
	 */
	public function setMisc2($misc2) {
		$this->misc2 = $misc2;
	}

	/**
	 * @return mixed
	 */
	public function getMisc2() {
		return $this->misc2;
	}

	/**
	 * @param int $month
	 */
	public function setMonth($month) {
		$this->month = $month;
	}

	/**
	 * @return int
	 */
	public function getMonth() {
		return $this->month;
	}

	/**
	 * @param String $note
	 */
	public function setNote($note) {
		$this->note = $note;
	}

	/**
	 * @return String
	 */
	public function getNote() {
		return $this->note;
	}

	/**
	 * @param String $number
	 */
	public function setNumber($number) {
		$this->number = $number;
	}

	/**
	 * @return String
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * @param String $number2
	 */
	public function setNumber2($number2) {
		$this->number2 = $number2;
	}

	/**
	 * @return String
	 */
	public function getNumber2() {
		return $this->number2;
	}

	/**
	 * @param String $organization
	 */
	public function setOrganization($organization) {
		$this->organization = $organization;
	}

	/**
	 * @return String
	 */
	public function getOrganization() {
		return $this->organization;
	}

	/**
	 * @param String $pages
	 */
	public function setPages($pages) {
		$this->pages = $pages;
	}

	/**
	 * @return String
	 */
	public function getPages() {
		return $this->pages;
	}

	/**
	 * @param String $publisher
	 */
	public function setPublisher($publisher) {
		$this->publisher = $publisher;
	}

	/**
	 * @return String
	 */
	public function getPublisher() {
		return $this->publisher;
	}

	/**
	 * @param boolean $reviewed
	 */
	public function setReviewed($reviewed) {
		$this->reviewed = $reviewed;
	}

	/**
	 * @return boolean
	 */
	public function getReviewed() {
		return $this->reviewed;
	}

	/**
	 * @param String $school
	 */
	public function setSchool($school) {
		$this->school = $school;
	}

	/**
	 * @return String
	 */
	public function getSchool() {
		return $this->school;
	}

	/**
	 * @param String $series
	 */
	public function setSeries($series) {
		$this->series = $series;
	}

	/**
	 * @return String
	 */
	public function getSeries() {
		return $this->series;
	}

	/**
	 * @param int $state
	 */
	public function setState($state) {
		$this->state = $state;
	}

	/**
	 * @return int
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param String $tags
	 */
	public function setTags($tags) {
		$this->tags = $tags;
	}

	/**
	 * @return String
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * @param String $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return String
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param String $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return String
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param String $volume
	 */
	public function setVolume($volume) {
		$this->volume = $volume;
	}

	/**
	 * @return String
	 */
	public function getVolume() {
		return $this->volume;
	}

	/**
	 * @param String $webUrl
	 */
	public function setWebUrl($webUrl) {
		$this->webUrl = $webUrl;
	}

	/**
	 * @return String
	 */
	public function getWebUrl() {
		return $this->webUrl;
	}

	/**
	 * @param String $webUrl2
	 */
	public function setWebUrl2($webUrl2) {
		$this->webUrl2 = $webUrl2;
	}

	/**
	 * @return String
	 */
	public function getWebUrl2() {
		return $this->webUrl2;
	}

	/**
	 * @param int $year
	 */
	public function setYear($year) {
		$this->year = $year;
	}

	/**
	 * @return int
	 */
	public function getYear() {
		return $this->year;
	}



}