<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bib - bibliography manager',
    'description' => 'A customizable bibliography and publication reference manager with a convenient frontend editor and import/export functionality.',
    'category' => 'plugin',
    'version' => '2.0.0',
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
    'constraints' => [
            'depends' => [
                    'typo3' => '8.7.0-8.7.99',
                    'php' => '7.0.0-7.2.99',
                ],
            'conflicts' => [
                ],
            'suggests' => [
                ],
        ],
];
