<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_bib_domain_model_author'] = [
	'ctrl' => $TCA['tx_bib_domain_model_author']['ctrl'],
	'interface' => [
		'showRecordFieldList' => $TCA['tx_bib_domain_model_author']['feInterface']['fe_admin_fieldList']
	],
	'feInterface' => $TCA['tx_bib_domain_model_author']['feInterface'],
	'columns' => [
		'surname' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_surname',
			'config' => [
				'type' => 'input',
				'size' => '48',
				'max' => '255',
				'eval' => 'trim,required',
			]
		],
		'forename' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_forename',
			'config' => [
				'type' => 'input',
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			]
		],
		'url' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_url',
			'config' => [
				'type' => 'input',
				'size' => '48',
				'max' => '255',
				'checkbox' => '0',
				'wizards' => [
					'_PADDING' => 2,
					'link' => [
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					]
				]
			]
		],
		'fe_user_id' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:bib/Resources/Private/Language/locallang_db.xml:tx_bib_domain_model_author_fe_user_id',
			'config' => [
				'type' => 'group',
				'size' => 1,
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'minitems' => 0,
				'maxitems' => 1,
			]
		],
	],
	'types' => [
		'0' => ['showitem' => 'surname,forename,url,fe_user_id']
	],
];
