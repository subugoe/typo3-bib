<?php

require_once(PATH_t3lib . 'class.t3lib_befunc.php');

class Tx_Bib_Utility_Labels {

	/**
	 * Returns label of an authorship
	 *
	 * @return Name of author and title of reference
	 */
	function get_authorship_label(&$params, &$pObj) {
		$item_id = $params['row']['uid'];
		$title = '';

		if ($item_id) {
			$item = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('tx_bib_domain_model_authorships', $item_id);

			$author_id = $item['author_id'];
			if ($author_id) {
				$author = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('tx_bib_domain_model_author', $author_id);
				$title .= $author['surname'] . ', ' . $author['forename'];
			}

			$pub_id = $item['pub_id'];
			if ($pub_id) {
				$pub = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('tx_bib_domain_model_reference', $pub_id);
				$title .= ' [' . $pub['title'] . ']';
			}
		}

		if (strlen($title) == 0) {
			$title = '[Error!]';
		}

		$params['title'] = $title;
	}

}

?>