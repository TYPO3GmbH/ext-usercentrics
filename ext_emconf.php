<?php

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Usercentrics Integration for TYPO3',
    'description' => 'Integrates Usercentrics (Compliance and Consent Management) into TYPO3.',
    'category' => 'fe',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author' => 'TYPO3 GmbH',
    'author_email' => 'info@typo3.com',
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.15-14.4.99',
            'backend' => '13.4.15-14.4.99',
            'fluid' => '13.4.15-14.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
