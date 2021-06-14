<?php

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function () {
    // Add static template
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('usercentrics', 'Configuration/TypoScript/Static/', 'Usercentrics Integration');
});
