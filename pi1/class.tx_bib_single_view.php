<?php

class tx_bib_single_view {

	public $pi1; // Plugin 1
	public $conf; // configuration array
	public $ref_read; // Reference accessor
	public $db_utility; // Reference accessor
	public $LLPrefix = 'editor_';
	public $idGenerator = FALSE;

	public $is_new = FALSE;
	public $is_new_first = FALSE;


	/**
	 * Initializes this class
	 *
	 * @return void
	 */
	function initialize($pi1) {
		$this->pi1 =& $pi1;
		$this->conf =& $pi1->conf['single_view.'];
		$this->ref_read =& $pi1->ref_read;
		// Load editor language data
		$this->pi1->extend_ll('EXT:' . $this->pi1->extKey . '/Resources/Private/Language/locallang_editor.xml');
	}


	/**
	 * Returns the single view
	 *
	 * @return Not defined
	 */
	function single_view() {
		$pi1 =& $this->pi1;
		$con = '';

		$uid = intval($pi1->extConf['single_view']['uid']);
		$ref = $this->ref_read->fetch_db_pub($uid);
		if (is_array($ref)) {
			$con .= $this->type_reference($ref);
		} else {
			$con .= '<p>';
			$con .= 'No publication with uid ' . strval($uid);
			$con .= '</p>' . "\n";
		}

		$con .= '<p>';
		$con .= $pi1->get_link($pi1->get_ll('link_back_to_list'));
		$con .= '</p>' . "\n";

		// remove multiple line breaks
		$con = preg_replace("/\n+/", "\n", $con);

		return $con;
	}


	function type_reference($ref) {
		$pi1 =& $this->pi1;
		$conf =& $this->conf;
		$cObj =& $pi1->cObj;

		$warnings = array();

		$tmpl_file = $conf['template'];
		$templ = $cObj->fileResource($tmpl_file);
		if (strlen($templ) == 0) {
			$err = 'The HTML single view template file \'' . $tmpl_file . '\' is not readable or empty';
			return $err;
		}

		$templ = $cObj->getSubpart($templ, '##SINGLE_VIEW###');


		// Store the cObj Data for later recovery
		$stor_data = $cObj->data;

		// Prepare the publication data and environment
		$pi1->prepare_item_setup();
		$pdata = $pi1->prepare_pub_display($ref, $warnings, true);
		$pi1->prepare_pub_cObj_data($pdata);

		$bib_str = $pdata['bibtype_short'];

		// The translator array
		$trans = array();

		// The filed list
		$fields = $this->ref_read->pubAllFields;
		$dont_show = Tx_Bib_Utility_Utility::explode_trim(',', $conf['dont_show'], TRUE);

		// Remove condition fields and setup the translator
		foreach ($fields as $field) {
			$field_up = strtoupper($field);

			// "Has field" conditions
			$has_str = '';
			if ((strlen($pdata[$field]) > 0)) {
				if (!in_array($field, $dont_show)) {
					$has_str = array('', '');
					$label = $this->field_label($field, $bib_str);
					$label = $pi1->cObj->stdWrap($label, $this->conf['all_labels.']);

					$value = strval($pdata[$field]);
					$stdWrap = $pi1->conf['field.'][$field . '.'];
					if (isset ($pi1->conf['field.'][$bib_str . '.'][$field . '.']))
						$stdWrap = $pi1->conf['field.'][$bib_str . '.'][$field . '.'];
					if (isset ($this->conf['field_wrap.'][$field . '.']))
						$stdWrap = $this->conf['field_wrap.'][$field . '.'];
					//\TYPO3\CMS\Core\Utility\GeneralUtility::debug ( array ( $field => $stdWrap ));
					if (isset ($stdWrap['single_view_link'])) {
						$value = $pi1->get_link($value, array('show_uid' => strval($pdata['uid'])));
					}
					$value = $cObj->stdWrap($value, $stdWrap);


					$trans['###' . $field_up . '###'] = $value;
					$trans['###FL_' . $field_up . '###'] = $label;
				}
			}
			$templ = $cObj->substituteSubpart($templ, '###HAS_' . $field_up . '###', $has_str);
		}

		// Insert field data
		$templ = $cObj->substituteMarkerArrayCached($templ, $trans);

		// Single view title
		$title = $pi1->get_ll('single_view_title');
		$title = $pi1->cObj->stdWrap($title, $this->conf['title.']);
		$templ = $cObj->substituteMarker($templ, '###SINGLE_VIEW_TITLE###', $title);

		// Pre and post text
		$txt = strval($this->conf['pre_text']);
		$txt = $pi1->cObj->stdWrap($txt, $this->conf['pre_text.']);
		$templ = $cObj->substituteMarker($templ, '###PRE_TEXT###', $txt);

		$txt = strval($this->conf['post_text']);
		$txt = $pi1->cObj->stdWrap($txt, $this->conf['post_text.']);
		$templ = $cObj->substituteMarker($templ, '###POST_TEXT###', $txt);


		// Restore cObj data
		$pi1->cObj->data = $stor_data;

		return $templ;
	}


	/**
	 * Depending on the bibliography type this function returns
	 * The label for a field
	 * @param field The field
	 * @param bib_str The bibtype identifier string
	 */
	function field_label($field, $bib_str) {
		$pi1 =& $this->pi1;
		$label = $this->ref_read->refTable . '_' . $field;

		switch ($field) {
			case 'authors':
				$label = $this->ref_read->authorTable . '_' . $field;
				break;
		}

		$over = array(
			$pi1->conf['editor.']['olabel.']['all.'][$field],
			$pi1->conf['editor.']['olabel.'][$bib_str . '.'][$field]
		);

		foreach ($over as $lvar) {
			if (is_string($lvar)) {
				$label = $lvar;
			}
		}

		$label = trim($label);
		if (strlen($label) > 0) {
			$label = $pi1->get_ll($label, $label, TRUE);
		}

		return $label;
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/pi1/class.tx_bib_single_view.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/bib/pi1/class.tx_bib_single_view.php"]);
}

?>