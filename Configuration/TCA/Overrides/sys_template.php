<?php

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function () {
    // Add static template
    ExtensionManagementUtility::addStaticFile('usercentrics', 'Configuration/TypoScript/Static/', 'Usercentrics Integration');
});
