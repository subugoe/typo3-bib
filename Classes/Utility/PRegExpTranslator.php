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

/**
 * Class PRegExpTranslator
 * @package Ipf\Bib\Utility
 */
class PRegExpTranslator
{

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
    public function __construct()
    {
        $this->clear();
    }

    /**
     * @return void
     */
    protected function clear()
    {
        $this->pattern = [];
        $this->replacement = [];
    }

    /**
     * @param string $pattern
     * @param string $replacement
     * @return $this
     */
    public function push($pattern, $replacement)
    {
        $this->pattern[] = $pattern;
        $this->replacement[] = $replacement;
        return $this;
    }

    /**
     * @param string $source
     * @return mixed
     */
    public function translate($source)
    {
        return preg_replace($this->pattern, $this->replacement, $source);
    }
}
