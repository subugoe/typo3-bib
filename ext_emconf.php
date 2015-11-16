<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bib - bibliography manager',
    'description' => 'A customizable bibliography and publication reference manager with a convenient frontend editor and import/export functionality.',
    'category' => 'plugin',
    'shy' => 0,
    'version' => '1.6.0',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'beta',
    'uploadfolder' => 1,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 1,
    'lockType' => '',
    'author' => 'Ingo Pfennigstorf',
    'author_email' => 'i.pfennigstorf@gmail.com',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '6.2.0-7.99.99',
                    'php' => '5.5.0-5.6.99'
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];
