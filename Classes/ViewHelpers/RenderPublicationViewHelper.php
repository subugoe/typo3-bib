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



/**
 * Renders HTML output for a bibliography entry.
 *
 * Set up the renderPublication View Helper with a bibliography item array.
 * Express the citation format to use, by adding renderPublicationField View Helpers
 * inside the renderPublication View Helper.
 *
 * The order, fields, prefixes and suffixes of the renderPublicationField View Helpers
 * will be recorded and turned into HTML markup.
 *
 * Usage:
 * with {bibitem} being a bibliographic item array as returned by the
 * publicationByCiteId View Helper.
 *
 * {namespace bib=Ipf\Bib\ViewHelpers}
 * <bib:renderPublication bibliographyItem="{bibitem}">
 * 	<f:if condition="{f:count(subject:bibitem.authors)} > 0">
 * 		<bib:renderPublicationField field="authors" xml="1" suffix=":">
 * 			<f:render partial="Bib/Authors" arguments="{_all}"/>
 * 		</bib:renderPublicationField>
 * 	</f:if>
 *
 * 	<bib:renderPublicationField field="title" suffix="."/>
 * 	<bib:renderPublicationField field="year"/>
 * 	<bib:renderPublicationField field="pages" prefix="p. "/>
 *
 * 	<f:if condition="{bibitem.DOI}">
 * 		<bib:renderPublicationField field="doi" xml="1" prefix="DOI: " prefixIfFirst="DOI: ">
 * 			<f:link.external uri="http://dx.doi.org/{bibitem.DOI}">{bibitem.DOI}</f:link.external>
 * 		</bib:renderPublicationField>
 * 	</f:if>
 * </bib:renderPublication>
 *
 */
class RenderPublicationViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	public static $variables = array(
		'prefixIfFirst' => array('description' => 'default prefix for the first field that is displayed', 'default' => ''),
		'prefix' => array('description' => 'default prefix for fields', 'default' => ''),
		'suffix' => array('description' => 'default suffix for fields', 'default' => ','),
		'suffixIfLast' => array('description' => 'default suffix for the last field that is displayed', 'default' => '.')
	);

	protected static $prefixString = 'tx_bib_';
	public static $containerVariableName = 'tx_bib_containerVariable';
	public static $bibliographyItemVariableName = 'tx_bib_bibliographyItemVariable';



	/**
	 * Register arguments.
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('bibliographyItem', 'array', 'the bibliography item to create output for', TRUE);

		foreach (self::$variables as $variableName => $variableConfig) {
			$this->registerArgument($variableName, 'string', $variableConfig['description'], FALSE, $variableConfig['default']);
		}
	}



	/**
	 * @return array
	 */
	public function render() {
		$bibliographyItem = $this->arguments['bibliographyItem'];

		// Set up template variables for RenderPublicationField View Helper.
		$this->templateVariableContainer->add(self::$bibliographyItemVariableName, $bibliographyItem);
		$this->templateVariableContainer->add(self::$containerVariableName, array());
		foreach (array_keys(self::$variables) as $variableName) {
			$this->templateVariableContainer->add(self::$prefixString . $variableName, $this->arguments[$variableName]);
		}

		// Render contained RenderPublicationField View Helpers and retrieve the data.
		$this->renderChildren();
		$fieldArray = $this->templateVariableContainer->get(self::$containerVariableName);

		// Unset template variables.
		$this->templateVariableContainer->remove(self::$containerVariableName);
		$this->templateVariableContainer->remove(self::$bibliographyItemVariableName);
		foreach (array_keys(self::$variables) as $variableName) {
			$this->templateVariableContainer->remove(self::$prefixString . $variableName);
		}

		return $this->createMarkup($bibliographyItem, $fieldArray);
	}



	/**
	 * Returns a string with HTML markup for the passed $bibliographyItem with
	 * the fields configured in $fieldArray.
	 *
	 * @param array $bibliographyItem the bibliography item to display
	 * @param array $fieldArray field configuration to use for the display
	 * @return string
	 */
	private function createMarkup ($bibliographyItem, $fieldArray) {
		$document = new \DomDocument();
		$recordSpan = $document->createElement('span');
		$recordSpan->setAttribute('class', self::$prefixString . 'record recordType-' . $bibliographyItem['bibtype']);
		$recordSpan->setAttribute('id', 'citekey-' . $this->arguments['citeId']);
		$document->appendChild($recordSpan);

		foreach ($fieldArray as $fieldIndex => $fieldInfo) {
			$content = $fieldInfo['children'];
			if ($content !== NULL) {
				if ($fieldInfo['xml']) {
					$childXML = new \DOMDocument();
					$childXML->loadXML($content);
					if ($childXML && $childXML->firstChild) {
						$contentXML = $document->importNode($childXML->firstChild, TRUE);
					}
				}
				if (!$contentXML) {
					$contentXML = $document->createTextNode($content);
				}
			}
			else {
				$fieldContent = $bibliographyItem[$fieldInfo['field']];
				if ($fieldContent) {
					$contentXML = $document->createTextNode($fieldContent);
				}
			}

			if ($contentXML) {
				$fieldSpan = $document->createElement('span');
				$fieldClass = self::$prefixString . 'field';
				if ($fieldInfo['field']) {
					$fieldClass .= ' ' . self::$prefixString . 'field-' . $fieldInfo['field'];
				}
				$fieldSpan->setAttribute('class', $fieldClass);

				$prefixKey = 'prefix' . (($fieldIndex === 0) ? 'IfFirst' : '');
				$fieldSpan->appendChild($document->createTextNode($fieldInfo[$prefixKey]));

				$fieldSpan->appendChild($contentXML);

				$suffixKey = 'suffix' . ($fieldIndex < count($fieldArray) - 1 ? '' : 'IfLast');
				$fieldSpan->appendChild($document->createTextNode($fieldInfo[$suffixKey]));

				$recordSpan->appendChild($fieldSpan);
				$recordSpan->appendChild($document->createTextNode(' '));
			}
			unset($contentXML);
		}

		return $document->saveHTML();
	}

}

?>
