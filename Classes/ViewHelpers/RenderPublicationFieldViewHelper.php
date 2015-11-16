<?php

namespace Ipf\Bib\ViewHelpers;

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

/**
 * Prepares the data to use for displaying a bibliography item.
 * May only be used inside a renderPublication View Helper.
 *
 * with the namespace configuration:
 * {namespace bib=Ipf\Bib\ViewHelpers}
 *
 * Simple usage with default setup:
 * Append the edition field to the list of fields, using the default suffix »,«
 * in the middle of the record and the suffix ».« at the end.
 * <bib:renderPublicationField field="edition"/>
 *
 * Usage with custom setup:
 * Append the editor field to the list of fields, changing the suffix to » (Ed.),«
 * in the middle of the record and to » (Ed.).« at the end.
 * <bib:renderPublicationField field="editor" suffix=" (Ed.)," suffixIfLast=" (Ed.)."/>
 *
 * Advanced usage with custom XML as the tag’s content:
 * Create an anchor tag linking to a DOI. The tag content will be validated
 * as XML before it is used.
 * <bib:renderPublicationField field="doi" xml="1" prefix="DOI: " prefixIfFirst="DOI: ">
 *    <f:link.external uri="http://dx.doi.org/{bibitem.DOI}">{bibitem.DOI}</f:link.external>
 * </bib:renderPublicationField>
 *
 */
class RenderPublicationFieldViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Register arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('field', 'string', 'name of the BibTeX field to render', true);
        $this->registerArgument('xml', 'boolean', 'whether to treat the tag’s content as XML or as text', false, false);

        foreach (RenderPublicationViewHelper::$variables as $variableName => $variableConfig) {
            $this->registerArgument($variableName, 'string', $variableConfig['description'], false, null);
        }
    }

    /**
     * @return void
     */
    public function render()
    {
        $fieldConfiguration = $this->arguments;
        foreach (array_keys(RenderPublicationViewHelper::$variables) as $variableName) {
            if ($fieldConfiguration[$variableName] === null) {
                $fieldConfiguration[$variableName] = $this->templateVariableContainer->get('tx_bib_' . $variableName);
            }
        }

        $fieldConfiguration['children'] = $this->renderChildren();
        if ($fieldConfiguration['children'] !== null) {
            $fieldConfiguration['children'] = trim($fieldConfiguration['children']);
        }

        $bibliographyItem = $this->templateVariableContainer->get(RenderPublicationViewHelper::$bibliographyItemVariableName);
        if ($bibliographyItem[$fieldConfiguration['field']] || $fieldConfiguration['children'] !== null) {
            $container = $this->templateVariableContainer->get(RenderPublicationViewHelper::$containerVariableName);
            $container[] = $fieldConfiguration;
            $this->templateVariableContainer->remove(RenderPublicationViewHelper::$containerVariableName);
            $this->templateVariableContainer->add(RenderPublicationViewHelper::$containerVariableName, $container);
        }
    }

}
