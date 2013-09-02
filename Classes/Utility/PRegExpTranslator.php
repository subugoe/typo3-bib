<?php
namespace Ipf\Bib\Utility;

class PRegExpTranslator {

	protected  $pattern;
	protected $replacement;

	public function __construct() {
		$this->clear();
	}


	protected function clear() {
		$this->pattern = array();
		$this->replacement = array();
	}

	/**
	 * @param string $pattern
	 * @param string $replacement
	 * @return $this
	 */
	public function push($pattern, $replacement) {
		$this->pattern[] = $pattern;
		$this->replacement[] = $replacement;
		return $this;
	}


	protected function translate($str) {
		return preg_replace($this->pattern, $this->replacement, $str);
	}
}

?>