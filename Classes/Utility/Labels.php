<?php
namespace Ipf\Bib\Utility;

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

class Labels {

	/**
	 * Returns label of an authorship
	 * @param array $params
	 * @param mixed $pObj
	 * @return void Name of author and title of reference
	 */
	public function get_authorship_label(&$params, &$pObj) {
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