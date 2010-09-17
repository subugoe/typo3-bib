<?php

require_once(PATH_t3lib.'class.t3lib_befunc.php');

class tx_sevenpack_labels {

	/**
	 * Returns label of an authorship
	 *
	 * @return Name of author and title of reference
	 */
	function get_authorship_label(&$params, &$pObj) {
		$pub_id    = $params['row']['pub_id'];
		$author_id = $params['row']['uid'];

		if ($pub_id && $author_id) {
			$publication = t3lib_BEfunc::getRecord('tx_sevenpack_references', $pub_id);
			$author      = t3lib_BEfunc::getRecord('tx_sevenpack_authors', $author_id);
			$params['title'] = $author['surname'] .', '. $author['forename'] .' ['. $publication['title'] .']';
		} else {
			$params['title'] = '[Error!]';
		}
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_labels.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/res/class.tx_sevenpack_labels.php"]);
}

?>