<?php

if ( !isset($GLOBALS['TSFE']) )
	die ('This file is no meant to be executed');


class tx_sevenpack_single_view {

	public $pi1; // Plugin 1
	public $conf; // configuration array
	public $ra;  // Reference accessor
	public $db_utility;  // Reference accessor
	public $LLPrefix = 'editor_';
	public $idGenerator = FALSE;

	public $is_new = FALSE;
	public $is_new_first = FALSE;


	/** 
	 * Initializes this class
	 *
	 * @return Not defined
	 */
	function initialize ( $pi1 ) {
		$this->pi1  =& $pi1;
		$this->conf =& $pi1->conf['editor.'];
		$this->ra   =& $pi1->ra;
		// Load editor language data
		$this->pi1->extend_ll ( 'EXT:'.$this->pi1->extKey.'/pi1/locallang_editor.xml' );
	}


	/** 
	 * Returns the single view
	 *
	 * @return Not defined
	 */
	function single_view ( ) {
		$pi1 =& $this->pi1;
		$con = "To be implemented";

		$con .= '<p>';
		$con .= $pi1->get_link ( $pi1->get_ll ( 'link_back_to_list' ) );
		$con .= '</p>'."\n";

		return $con;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_single_view.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sevenpack/pi1/class.tx_sevenpack_single_view.php"]);
}

?>
