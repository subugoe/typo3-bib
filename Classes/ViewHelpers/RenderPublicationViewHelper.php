<?php
/*******************************************************************************
 * Copyright notice
 *
 * Copyright 2013 Sven-S. Porst, Göttingen State and University Library
 *                <porst@sub.uni-goettingen.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 ******************************************************************************/

namespace Ipf\Bib\ViewHelpers;

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
 */
class RenderPublicationViewHelper extends  \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	static $variables = array(
		'prefixIfFirst' => array('description' => 'default prefix for the first field that is displayed', 'default' => ''),
		'prefix' => array('description' => 'default prefix for fields', 'default' => ''),
		'suffix' => array('description' => 'default suffix for fields', 'default' => ','),
		'suffixIfLast' => array('description' => 'default suffix for the last field that is displayed', 'default' => '.')
	);

	static $prefixString = 'tx_bib_';
	static $containerVariableName = 'tx_bib_containerVariable';
	static $bibitemVariableName = 'tx_bib_bibitemVariable';

	/**
	 * Register arguments.
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('bibitem', 'array', 'the bibitem to create output for', TRUE);
		foreach ($this::$variables as $variableName => $variableConfig) {
			$this->registerArgument($variableName, 'string', $variableConfig['description'], FALSE, $variableConfig['default']);
		}
	}



	/**
	 * @throws \Exception
	 * @return array
	 */
	public function render() {
		// Get data.
		$bibitem = $this->arguments['bibitem'];

		// Set up template variables for RenderPublicationField View Helper.
		$this->templateVariableContainer->add($this::$bibitemVariableName, $bibitem);
		$this->templateVariableContainer->add($this::$containerVariableName, array());
		foreach ($this::$variables as $variableName => $variableConfig) {
			$this->templateVariableContainer->add($this::$prefixString . $variableName, $this->arguments[$variableName]);
		}

		// Render contained RenderPublicationField View Helpers and retrieve the data.
		$this->renderChildren();
		$fieldArray = $this->templateVariableContainer->get($this::$containerVariableName);

		// Unset template variables.
		$this->templateVariableContainer->remove($this::$containerVariableName);
		$this->templateVariableContainer->remove($this::$bibitemVariableName);
		foreach ($this::$variables as $variableName => $variableConfig) {
			$this->templateVariableContainer->remove($this::$prefixString . $variableName);
		}

		// Create the output.
		$doc = new \DomDocument();
		$recordSpan = $doc->createElement('span');
		$recordSpan->setAttribute('class', $this::$prefixString . 'record recordType-' . $bibitem['bibtype']);
		$recordSpan->setAttribute('id', 'citekey-' . $this->arguments['citeId']);
		$doc->appendChild($recordSpan);

		foreach ($fieldArray as $fieldIndex => $fieldInfo) {
			$content = $fieldInfo['children'];
			if ($content !== NULL) {
				if ($fieldInfo['xml']) {
					$childXML = new \DOMDocument();
					$childXML->loadXML($content);
					if ($childXML) {
						$contentXML = $doc->importNode($childXML->firstChild, TRUE);
					}
				}
				if (!$contentXML) {
					$contentXML = $doc->createTextNode($content);
				}
			}
			else {
				$fieldContent = $bibitem[$fieldInfo['field']];
				if ($fieldContent) {
					$contentXML = $doc->createTextNode($fieldContent);
				}
			}

			if ($contentXML) {
				$fieldSpan = $doc->createElement('span');
				$fieldClass = $this::$prefixString . 'field';
				if ($fieldInfo['field']) {
					$fieldClass .= ' ' . $this::$prefixString . 'field-' . $fieldInfo['field'];
				}
				$fieldSpan->setAttribute('class', $fieldClass);

				$prefixKey = 'prefix' . (($fieldIndex === 0) ? 'IfFirst' : '');
				$fieldSpan->appendChild($doc->createTextNode($fieldInfo[$prefixKey]));

				$fieldSpan->appendChild($contentXML);

				$suffixKey = 'suffix' . ($fieldIndex < count($fieldArray) - 1 ? '' : 'IfLast');
				$fieldSpan->appendChild($doc->createTextNode($fieldInfo[$suffixKey]));

				$recordSpan->appendChild($fieldSpan);
				$recordSpan->appendChild($doc->createTextNode(' '));
			}
			unset($contentXML);
		}

		$result = $doc->saveHTML();

		return $result;
	}

}

?>