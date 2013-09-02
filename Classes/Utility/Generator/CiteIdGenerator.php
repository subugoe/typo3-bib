<?php

class Tx_Bib_Utility_Generator_CiteIdGenerator {

	/**
	 * @var tx_bib_pi1
	 */
	public $pi1;

	/**
	 * @var Tx_Bib_Utility_ReferenceReader
	 */
	public $referenceReader;

	/**
	 * @var String
	 */
	public $charset;


	/**
	 * @param tx_bib_pi1 $pi1
	 */
	function initialize($pi1) {
		$this->referenceReader =& $pi1->referenceReader;
		$this->charset = $pi1->extConf['charset']['upper'];
	}

	/**
	 * Generates a cite id for the publication in piVars['DATA']
	 *
	 * @param array $row
	 * @return The generated id
	 */
	function generateId($row) {
		$id = $this->generateBasicId($row);
		$tmpId = $id;

		$uid = -1;
		if (array_key_exists('uid', $row) && ($row['uid'] >= 0))
			$uid = intval($row['uid']);

		$num = 1;
		while ($this->referenceReader->citeid_exists($tmpId, $uid)) {
			$num++;
			$tmpId = $id . '_' . $num;
		}

		return $tmpId;
	}


	/**
	 * @param array $row
	 * @return String
	 */
	function generateBasicId($row) {
		$authors = $row['authors'];
		$editors = Tx_Bib_Utility_Utility::explode_author_str($row['editor']);

		$persons = array($authors, $editors);

		$id = '';
		foreach ($persons as $list) {
			if (strlen($id) == 0) {
				if (sizeof($list) > 0) {
					$pp =& $list[0];
					$a_str = '';
					if (strlen($pp['surname']) > 0)
						$a_str = $pp['surname'];
					else if (strlen($pp['forename']))
						$a_str = $pp['forename'];
					if (strlen($a_str) > 0)
						$id = $this->simplified_string($a_str);
				}
			}
			for ($i = 1; $i < sizeof($list); $i++) {
				$pp =& $list[$i];
				$a_str = '';
				if (strlen($pp['surname']) > 0)
					$a_str = $pp['surname'];
				else if (strlen($pp['forename']))
					$a_str = $pp['forename'];
				if (strlen($a_str) > 0) {
					$id .= mb_substr(
						$this->simplified_string($a_str), 0, 1, $this->charset);
				}
			}
		}

		if (strlen($id) == 0) {
			$id = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(serialize($row));
		}
		if ($row['year'] > 0)
			$id .= $row['year'];

		return $this->simplified_string($id);
	}


	/**
	 * Replaces all special characters and HTML sequences in a string to
	 * characters that are allowed in a citation id
	 *
	 * @return The simplified string
	 */
	function simplified_string($id) {
		// Replace some special characters with ASCII characters
		$id = htmlentities($id, ENT_QUOTES, $this->charset);
		$id = str_replace('&amp;', '&', $id);
		$id = preg_replace('/&(\w)\w{1,7};/', '$1', $id);

		// Replace remaining special characters with ASCII characters
		$tmpId = '';
		for ($i = 0; $i < mb_strlen($id, $this->charset); $i++) {
			$c = mb_substr($id, $i, 1, $this->charset);
			if (ctype_alnum($c) || ($c == '_')) {
				$tmpId .= $c;
			}
		}
		return $tmpId;
	}

}

?>