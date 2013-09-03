<?php
namespace Ipf\Bib\Utility;

class PRegExpTranslator {

	/**
	 * @var string
	 */
	protected $pattern;

	/**
	 * @var string
	 */
	protected $replacement;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->clear();
	}

	/**
	 * @return void
	 */
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

	/**
	 * @param string $source
	 * @return mixed
	 */
	public function translate($source) {
		return preg_replace($this->pattern, $this->replacement, $source);
	}
}

?>