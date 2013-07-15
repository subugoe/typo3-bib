<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_bib_authorships'] = array (
	'ctrl' => $TCA['tx_bib_authorships']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => $TCA['tx_bib_authorships']['feInterface']['fe_admin_fieldList']
	),
	'feInterface' => $TCA['tx_pmeightpack_authorships']['feInterface'],
	'columns' => array (
		'pub_id' => array (
#      "exclude" => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authorships_pub_id',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_bib_references',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'selectedListStyle' => 'width:320px;',
				'wizards' => array (
					'_VALIGN' => 'top',
					'ajax_search' => array (
						'type' => 'userFunc',
						'userFunc' => 'tx_ajaxgroupsearch_client->renderAjaxSearch',
						'params' => array (
							'client' => array ( 'startLength' => 2 ),
							'wrapStyle' => 'z-index:80;',
							'inputStyle' => 'width:200px;',
							'itemListStyle' => 'width:320px;',
							'tables' => array (
								'tx_bib_references' => array (
									'searchBySQL' => array (
									'fields' => 'r.title, r.uid',
									'tables' => 'tx_bib_references AS r',
									'where' => 'r.title LIKE "%###SEARCHWORD###%" AND r.deleted=0 AND r.hidden=0',
									'group_by' => '',
									'order_by' => 'r.title DESC',
									'limit' => '10'
									)
								)
							)
						)
					)
				)
			)
		),
		'author_id' => array (
#      "exclude" => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authorships_author_id',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_bib_authors',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'selectedListStyle' => 'width:320px;',
				'wizards' => array(
					'_VALIGN' => 'top',
					'ajax_search' => array(
						'type' => 'userFunc',
						'userFunc' => 'tx_ajaxgroupsearch_client->renderAjaxSearch',
						'params' => array(
							'client' => array('startLength'=>2),
							'wrapStyle' => 'z-index:80;',
							'inputStyle' => 'width:200px;',
							'itemListStyle' => 'width:320px;',
							'tables' => array(
								'tx_bib_authors' => array(
									'searchBySQL' => array(
									'fields' => 'a.surname, a.uid',
									'tables' => 'tx_bib_authors AS a',
									'where' => 'a.surname LIKE "%###SEARCHWORD###%" AND a.deleted=0 AND a.hidden=0',
									'group_by' => '',
									'order_by' => 'a.surname DESC, a.forename DESC',
									'limit' => '10'
									)
								)
							)
						)
					)
				)
			)
		),
		'sorting' => array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authorships_sorting',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '255',
				'eval' => 'int',
			)
		),
	),
	'types' => array (
		'0' => array ( 'showitem' => 'pub_id,author_id,sorting' )
	),
);