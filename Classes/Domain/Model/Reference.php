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
 * Reference Model
 */
class Reference extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

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
	 * @param string $DOI
	 */
	public function setDOI($DOI) {
		$this->DOI = $DOI;
	}

	/**
	 * @return string
	 */
	public function getDOI() {
		return $this->DOI;
	}

	/**
	 * @param string $ISBN
	 */
	public function setISBN($ISBN) {
		$this->ISBN = $ISBN;
	}

	/**
	 * @return string
	 */
	public function getISBN() {
		return $this->ISBN;
	}

	/**
	 * @param string $ISSN
	 */
	public function setISSN($ISSN) {
		$this->ISSN = $ISSN;
	}

	/**
	 * @return string
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
	 * @param string $abstract
	 */
	public function setAbstract($abstract) {
		$this->abstract = $abstract;
	}

	/**
	 * @return string
	 */
	public function getAbstract() {
		return $this->abstract;
	}

	/**
	 * @param string $address
	 */
	public function setAddress($address) {
		$this->address = $address;
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 * @param string $affiliation
	 */
	public function setAffiliation($affiliation) {
		$this->affiliation = $affiliation;
	}

	/**
	 * @return string
	 */
	public function getAffiliation() {
		return $this->affiliation;
	}

	/**
	 * @param string $annotation
	 */
	public function setAnnotation($annotation) {
		$this->annotation = $annotation;
	}

	/**
	 * @return string
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
	 * @param string $booktitle
	 */
	public function setBooktitle($booktitle) {
		$this->booktitle = $booktitle;
	}

	/**
	 * @return string
	 */
	public function getBooktitle() {
		return $this->booktitle;
	}

	/**
	 * @param string $borrowedBy
	 */
	public function setBorrowedBy($borrowedBy) {
		$this->borrowedBy = $borrowedBy;
	}

	/**
	 * @return string
	 */
	public function getBorrowedBy() {
		return $this->borrowedBy;
	}

	/**
	 * @param string $chapter
	 */
	public function setChapter($chapter) {
		$this->chapter = $chapter;
	}

	/**
	 * @return string
	 */
	public function getChapter() {
		return $this->chapter;
	}

	/**
	 * @param string $citeid
	 */
	public function setCiteid($citeid) {
		$this->citeid = $citeid;
	}

	/**
	 * @return string
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
	 * @param string $edition
	 */
	public function setEdition($edition) {
		$this->edition = $edition;
	}

	/**
	 * @return string
	 */
	public function getEdition() {
		return $this->edition;
	}

	/**
	 * @param string $editor
	 */
	public function setEditor($editor) {
		$this->editor = $editor;
	}

	/**
	 * @return string
	 */
	public function getEditor() {
		return $this->editor;
	}

	/**
	 * @param string $eventDate
	 */
	public function setEventDate($eventDate) {
		$this->eventDate = $eventDate;
	}

	/**
	 * @return string
	 */
	public function getEventDate() {
		return $this->eventDate;
	}

	/**
	 * @param string $eventName
	 */
	public function setEventName($eventName) {
		$this->eventName = $eventName;
	}

	/**
	 * @return string
	 */
	public function getEventName() {
		return $this->eventName;
	}

	/**
	 * @param string $eventPlace
	 */
	public function setEventPlace($eventPlace) {
		$this->eventPlace = $eventPlace;
	}

	/**
	 * @return string
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
	 * @param string $fileUrl
	 */
	public function setFileUrl($fileUrl) {
		$this->fileUrl = $fileUrl;
	}

	/**
	 * @return string
	 */
	public function getFileUrl() {
		return $this->fileUrl;
	}

	/**
	 * @param string $fullText
	 */
	public function setFullText($fullText) {
		$this->fullText = $fullText;
	}

	/**
	 * @return string
	 */
	public function getFullText() {
		return $this->fullText;
	}

	/**
	 * @param string $fullTextFileUrl
	 */
	public function setFullTextFileUrl($fullTextFileUrl) {
		$this->fullTextFileUrl = $fullTextFileUrl;
	}

	/**
	 * @return string
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
	 * @param string $howpublished
	 */
	public function setHowpublished($howpublished) {
		$this->howpublished = $howpublished;
	}

	/**
	 * @return string
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
	 * @param string $institute
	 */
	public function setInstitute($institute) {
		$this->institute = $institute;
	}

	/**
	 * @return string
	 */
	public function getInstitute() {
		return $this->institute;
	}

	/**
	 * @param string $institution
	 */
	public function setInstitution($institution) {
		$this->institution = $institution;
	}

	/**
	 * @return string
	 */
	public function getInstitution() {
		return $this->institution;
	}

	/**
	 * @param string $journal
	 */
	public function setJournal($journal) {
		$this->journal = $journal;
	}

	/**
	 * @return string
	 */
	public function getJournal() {
		return $this->journal;
	}

	/**
	 * @param string $keywords
	 */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
	 * @return string
	 */
	public function getKeywords() {
		return $this->keywords;
	}

	/**
	 * @param string $language
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * @param string $misc
	 */
	public function setMisc($misc) {
		$this->misc = $misc;
	}

	/**
	 * @return string
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
	 * @param string $note
	 */
	public function setNote($note) {
		$this->note = $note;
	}

	/**
	 * @return string
	 */
	public function getNote() {
		return $this->note;
	}

	/**
	 * @param string $number
	 */
	public function setNumber($number) {
		$this->number = $number;
	}

	/**
	 * @return string
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * @param string $number2
	 */
	public function setNumber2($number2) {
		$this->number2 = $number2;
	}

	/**
	 * @return string
	 */
	public function getNumber2() {
		return $this->number2;
	}

	/**
	 * @param string $organization
	 */
	public function setOrganization($organization) {
		$this->organization = $organization;
	}

	/**
	 * @return string
	 */
	public function getOrganization() {
		return $this->organization;
	}

	/**
	 * @param string $pages
	 */
	public function setPages($pages) {
		$this->pages = $pages;
	}

	/**
	 * @return string
	 */
	public function getPages() {
		return $this->pages;
	}

	/**
	 * @param string $publisher
	 */
	public function setPublisher($publisher) {
		$this->publisher = $publisher;
	}

	/**
	 * @return string
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
	 * @param string $school
	 */
	public function setSchool($school) {
		$this->school = $school;
	}

	/**
	 * @return string
	 */
	public function getSchool() {
		return $this->school;
	}

	/**
	 * @param string $series
	 */
	public function setSeries($series) {
		$this->series = $series;
	}

	/**
	 * @return string
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
	 * @param string $tags
	 */
	public function setTags($tags) {
		$this->tags = $tags;
	}

	/**
	 * @return string
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $volume
	 */
	public function setVolume($volume) {
		$this->volume = $volume;
	}

	/**
	 * @return string
	 */
	public function getVolume() {
		return $this->volume;
	}

	/**
	 * @param string $webUrl
	 */
	public function setWebUrl($webUrl) {
		$this->webUrl = $webUrl;
	}

	/**
	 * @return string
	 */
	public function getWebUrl() {
		return $this->webUrl;
	}

	/**
	 * @param string $webUrl2
	 */
	public function setWebUrl2($webUrl2) {
		$this->webUrl2 = $webUrl2;
	}

	/**
	 * @return string
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

	/**
	 * @return string
	 */
	public function getWebUrl2Date() {
		return $this->webUrl2Date;
	}

	/**
	 * @param string $webUrl2Date
	 */
	public function setWebUrl2Date($webUrl2Date) {
		$this->webUrl2Date = $webUrl2Date;
	}

	/**
	 * @return string
	 */
	public function getWebUrlDate() {
		return $this->webUrlDate;
	}

	/**
	 * @param string $webUrlDate
	 */
	public function setWebUrlDate($webUrlDate) {
		$this->webUrlDate = $webUrlDate;
	}


}