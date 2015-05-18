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
 * Class ext_update
 */
class ext_update {

	/**
	 * @return string
	 */
	public function main() {
		$num = $this->num_wrong_aship_pid();
		$res = '';

		if ($num > 0) {
			$res .= '<h3>Missing authorship pids</h3>';
			$res .= '<p>';
			$res .= 'Found ' . $num . ' authorships with missing pid';
			$res .= '<p>';
			$res .= '<ul>';
			if ($this->fix_wrong_aship_pid()) {
				$res .= '<li>Fix failed</li>';
			} else {
				$res .= '<li>Fixed</li>';
			}
			$res .= '</ul>';
		}

		return $res;
	}

	/**
	 * @return bool
	 */
	function access() {
		$update = FALSE;
		if ($this->num_wrong_aship_pid() != 0) {
			$update = TRUE;
		}

		return $update;
	}


	/**
	 * Fix missing authorship pids
	 *
	 */
	function num_wrong_aship_pid() {
		$num = -1;
		$query = '
			SELECT count(*)
			FROM tx_bib_domain_model_authorships
			LEFT JOIN tx_bib_domain_model_reference ON
			tx_bib_domain_model_authorships.pub_id = tx_bib_domain_model_reference.uid
			WHERE
			tx_bib_domain_model_authorships.pid != tx_bib_domain_model_reference.pid
			;
		';
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$error = $GLOBALS['TYPO3_DB']->sql_error();
		if ((strlen($error) == 0) && $res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$num = intval($row['count(*)']);
		}
		return $num;
	}

	/**
	 * @return bool
	 */
	function fix_wrong_aship_pid() {
		$error = FALSE;
		$query = '
			UPDATE tx_bib_domain_model_authorships
			LEFT JOIN tx_bib_domain_model_reference ON
			tx_bib_domain_model_authorships.pub_id = tx_bib_domain_model_reference.uid
			SET
			tx_bib_domain_model_authorships.pid = tx_bib_domain_model_reference.pid
			WHERE
			tx_bib_domain_model_authorships.pid != tx_bib_domain_model_reference.pid
			;
		';
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$sql_error = $GLOBALS['TYPO3_DB']->sql_error();
		if (strlen($sql_error) == 0) {

		} else {
			$error = TRUE;
		}
		return $error;
	}


}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/class.ext_update.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/class.ext_update.php"]);
}
