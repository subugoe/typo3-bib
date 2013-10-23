<?php
namespace Ipf\Bib\Hooks;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Pfennigstorf <pfennigstorf@sub-goettingen.de>
 *      Göttingen State and University Library
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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * RealUrl Hook for automatic URL generation
 */
class RealUrl {

	/**
	 * Create automatic RealUrl Configuration
	 *
	 * @param array $params
	 * @param \tx_realurl $pObj
	 * @return array
	 */
	public function addRealUrlConfiguration($params, &$pObj) {
		return array_merge_recursive($params['config'], array(
				'postVarSets' => array(
					'_DEFAULT' => array(
						'publication' => array(
							array(
								'GETvar' => 'tx_bib_pi1[show_uid]',
								'lookUpTable' => array(
									'table' => 'tx_bib_domain_model_reference',
									'id_field' => 'uid',
									'alias_field' => 'citeid',
									'addWhereClause' => ' AND NOT deleted',
									'useUniqueCache' => 1,
									'useUniqueCache_conf' => array(
										'strtolower' => 1,
										'spaceCharacter' => '-',
										),
									),
							),
						),
						'edit' => array(
							array(
								'GETvar' => 'tx_bib_pi1[action][edit]',
								'valueMap' => array(
									'publication' => 1
								)
							),
						),
						'hide' => array(
							array(
								'GETvar' => 'tx_bib_pi1[action][hide]',
								'valueMap' => array(
									'publication' => 1
								)
							),
						),
						'add' => array(
							array(
								'GETvar' => 'tx_bib_pi1[action][new]',
								'valueMap' => array(
									'publication' => 1
								)
							),
						),
						'resultpage' => array(
							array(
								'GETvar' => 'tx_bib_pi1[page]',
								'userFunc' => 'EXT:bib/Classes/Utility/RealUrl.php:&Ipf\\Bib\Utility\\RealUrl->pageBrowser',
							),
						),
						'publicationid' => array(
							array(
								'GETVar' => 'tx_bib_pi1[uid]',
							),
						),
						'import' => array(
							array(
								'GETVar' => 'tx_bib_pi1[import]',
								'valueMap' => array(
									'bibtex' => 1,
									'xml' => 2
								),
							),
						),
						'export' => array(
							array(
								'GETVar' => 'tx_bib_pi1[export]',
							)
						)
					)
				)
			)
		);
	}

}