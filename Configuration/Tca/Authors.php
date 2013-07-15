<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_bib_authors'] = array (
	'ctrl' => $TCA['tx_bib_authors']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => $TCA['tx_bib_authors']['feInterface']['fe_admin_fieldList']
	),
	'feInterface' => $TCA['tx_bib_authors']['feInterface'],
	'columns' => array (
		'surname' => array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authors_surname',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'max' => '255',
				'eval' => 'trim,required',
			)
		),
		'forename' => array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authors_forename',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'url' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authors_url',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'max' => '255',
				'checkbox' => '0',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'fe_user_id' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_authors_fe_user_id',
			'config' => Array (
				'type' => 'group',
				'size' => 1,
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => array (
		'0' => array ( 'showitem' => 'surname,forename,url,fe_user_id' )
	),
);