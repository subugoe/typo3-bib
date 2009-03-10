<?php

class ext_update {


	function main ( ) {
		$num = $this->num_wrong_aship_pid ( );
		if ( $num > 0 ) {
			$res = '';
			$res .= '<h3>Missing authorship pids</h3>';
			$res .= '<p>';
			$res .= 'Found ' . $num . ' authorships with missing pid';
			$res .= '<p>';
			$res .= '<ul>';
			if ( $this->fix_wrong_aship_pid ( ) ) {
				$res .= '<li>Fix failed</li>';
			} else {
				$res .= '<li>Fixed</li>';
			}
			$res .= '</ul>';
		}

		return $res;
	}


	function access ( ) {
		$update = FALSE;
		if ( $this->num_wrong_aship_pid ( ) != 0 ) {
			$update = TRUE;
		}

		return $update;
	}


	/*
	 * Fix missing authorship pids
	 *
	 */
	function num_wrong_aship_pid ( ) {
		$num = -1;
		$query = '
			SELECT count(*)
			FROM tx_sevenpack_authorships 
			LEFT JOIN tx_sevenpack_references ON
			tx_sevenpack_authorships.pub_id = tx_sevenpack_references.uid
			WHERE
			tx_sevenpack_authorships.pid != tx_sevenpack_references.pid
			;
		';
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $query );
		$error = $GLOBALS['TYPO3_DB']->sql_error ( );
		if ( ( strlen ( $error ) == 0 ) && $res ) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc ( $res );
			$num = intval ( $row['count(*)'] );
		}
		return $num;
	}


	function fix_wrong_aship_pid ( ) {
		$error = FALSE;
		$query = '
			UPDATE tx_sevenpack_authorships
			LEFT JOIN tx_sevenpack_references ON
			tx_sevenpack_authorships.pub_id = tx_sevenpack_references.uid
			SET
			tx_sevenpack_authorships.pid = tx_sevenpack_references.pid
			WHERE
			tx_sevenpack_authorships.pid != tx_sevenpack_references.pid
			;
		';
		$res = $GLOBALS['TYPO3_DB']->sql_query ( $query );
		$sql_error = $GLOBALS['TYPO3_DB']->sql_error ( );
		if ( strlen ( $error ) == 0 ) {

		} else {
			$error = TRUE;
		}
		return $error;
	}


}

?>
