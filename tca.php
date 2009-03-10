<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_sevenpack_references'] = array (
	'ctrl' => $TCA['tx_sevenpack_references']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,bibtype,citeid,title,journal,year,volume,number,pages,day,month,abstract,affiliation,note,annotation,keywords,file_url,misc,editor,publisher,series,address,edition,chapter,howpublished,booktitle,organization,school,institution,state,extern,ISBN'
	),
	'feInterface' => $TCA['tx_sevenpack_references']['feInterface'],
	'columns' => array (
		'hidden' => array (		
#			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'bibtype' => Array (		
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_0', '0'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_1', '1'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_2', '2'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_3', '3'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_4', '4'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_5', '5'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_6', '6'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_7', '7'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_8', '8'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_9', '9'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_10', '10'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_11', '11'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_12', '12'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_13', '13'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_14', '14'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_15', '15'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_bibtype_I_16', '16'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'citeid' => Array (
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_citeid',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'eval' => 'nospace,uniqueInPid,required',
			)
		),
		'title' => Array (
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_title',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '5',
			)
		),
		'journal' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_journal',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max'  => '255',
				'eval' => 'trim',
			)
		),
		'year' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_year',
			'config' => Array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'range'    => Array (
					'upper' => '10000',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'volume' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_volume',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'number' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_number',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'pages' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_pages',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'day' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_day',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('-',   '0'),
					Array('1',   '1'),
					Array('2',   '2'),
					Array('3',   '3'),
					Array('4',   '4'),
					Array('5',   '5'),
					Array('6',   '6'),
					Array('7',   '7'),
					Array('8',   '8'),
					Array('9',   '9'),
					Array('10', '10'),
					Array('11', '11'),
					Array('13', '13'),
					Array('13', '13'),
					Array('14', '14'),
					Array('15', '15'),
					Array('16', '16'),
					Array('17', '17'),
					Array('18', '18'),
					Array('19', '19'),
					Array('20', '20'),
					Array('21', '21'),
					Array('22', '22'),
					Array('23', '23'),
					Array('24', '24'),
					Array('25', '25'),
					Array('26', '26'),
					Array('27', '27'),
					Array('28', '28'),
					Array('29', '29'),
					Array('30', '30'),
					Array('31', '31')
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'month' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_month',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('-',   '0'),
					Array('1',   '1'),
					Array('2',   '2'),
					Array('3',   '3'),
					Array('4',   '4'),
					Array('5',   '5'),
					Array('6',   '6'),
					Array('7',   '7'),
					Array('8',   '8'),
					Array('9',   '9'),
					Array('10', '10'),
					Array('11', '11'),
					Array('12', '12'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'abstract' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_abstract',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '5',
			)
		),
		'affiliation' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_affiliation',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'note' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_note',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '5',
			)
		),
		'annotation' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_annotation',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '5',
			)
		),
		'keywords' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_keywords',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',	
				'rows' => '5',
			)
		),
		'file_url' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_file_url',
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
					),
				),
			)
		),
		'misc' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_misc',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'editor' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_editor',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'publisher' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_publisher',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'series' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_series',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'address' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_address',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'edition' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_edition',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'chapter' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_chapter',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'howpublished' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_howpublished',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'booktitle' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_booktitle',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'organization' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_organization',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'school' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_school',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'institution' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_institution',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'state' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_state',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_state_I_0', '0'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_state_I_1', '1'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_state_I_2', '2'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_state_I_3', '3'),
					Array('LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_state_I_4', '4'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'type' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_type',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'ISBN' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_ISBN',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'extern' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_extern',
			'config' => Array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'reviewed' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_reviewed',
			'config' => Array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'in_library' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_in_library',
			'config' => Array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'borrowed_by' => Array (
#			'exclude' => 1,
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_references_borrowed_by',
			'config' => Array (
				'type' => 'input',	
				'size' => '48',
				'max' => '255',
				'eval' => 'trim',
			)
		),
	),
	'types' => array (
		'0' => array ( 'showitem' => 'hidden;;1, bibtype, citeid, title;;;;2-2-2, journal;;;;3-3-3, year, volume, number, pages, day, month, abstract, affiliation, note, annotation, keywords, file_url, misc, editor, publisher, series, address, edition, chapter, howpublished, booktitle, organization, school, institution, state, type, ISBN, extern, reviewed, in_library, borrowed_by' )
	),
);


$TCA['tx_sevenpack_authors'] = array (
	'ctrl' => $TCA['tx_sevenpack_authors']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'surname,forename'
	),
	'feInterface' => $TCA['tx_sevenpack_authors']['feInterface'],
	'columns' => array (
		'surname' => array (
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authors_surname',		
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'eval' => 'trim,required',
			)
		),
		'forename' => array (		
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authors_forename',		
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'eval' => 'trim',
			)
		),
	),
	'types' => array (
		'0' => array ( 'showitem' => 'surname,forename' )
	),
);


$TCA['tx_sevenpack_authorships'] = array (
	'ctrl' => $TCA['tx_sevenpack_authorships']['ctrl'],
	'columns' => array (
		'pub_id' => array (
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authorships_pub_id',		
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'eval' => 'int',
				'foreign_table'       => 'tx_sevenpack_references',
				'foreign_table_where' => ' AND tx_sevenpack_references.uid=tx_sevenpack_authorships.pub_id ',
			)
		),
		'author_id' => array (
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authorships_author_id',		
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'eval' => 'int',
				'foreign_table'       => 'tx_sevenpack_authors',
				'foreign_table_where' => ' AND tx_sevenpack_authors.uid=tx_sevenpack_authorships.author_id ',
			)
		),
		'sorting' => array (
#			'exclude' => 1,		
			'label' => 'LLL:EXT:sevenpack/locallang_db.xml:tx_sevenpack_authorships_sorting',		
			'config' => Array (
				'type' => 'input',	
				'size' => '48',	
				'max' => '255',	
				'eval' => 'int',
			)
		),
	),
	'types' => array (
		'0' => array ( 'showitem' => 'pub_id,author_id,sorting' )
	),
);

?>
