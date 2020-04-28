<?php

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Usercentrics Extension',
    'description' => '',
    'category' => 'fe',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 GmbH',
    'author_email' => 'info@typo3.com',
    'version' => '10.0.0-dev',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.5-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
