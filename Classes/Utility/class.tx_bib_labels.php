<?php

require_once(PATH_t3lib.'class.t3lib_befunc.php');

class tx_bib_labels {

	/**
	 * Returns label of an authorship
	 *
	 * @return Name of author and title of reference
	 */
	function get_authorship_label(&$params, &$pObj) {
		$item_id = $params['row']['uid'];
		$title = '';

		if ( $item_id ) {
			$item = t3lib_BEfunc::getRecord('tx_bib_authorships', $item_id);
			
			$author_id = $item['author_id'];
			if ( $author_id ) {
				$author = t3lib_BEfunc::getRecord('tx_bib_authors', $author_id);
				$title .= $author['surname'] . ', ' . $author['forename'];
			}

			$pub_id = $item['pub_id'];
			if ( $pub_id ) {
				$pub = t3lib_BEfunc::getRecord('tx_bib_references', $pub_id);
				$title .= ' [' . $pub['title'] . ']';
			}
		}
		
		if ( strlen ( $title ) == 0 ) {
			$title = '[Error!]';
		}
		
		$params['title'] = $title;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/res/class.tx_bib_labels.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/res/class.tx_bib_labels.php"]);
}

?>