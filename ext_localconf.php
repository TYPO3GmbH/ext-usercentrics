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
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][\T3G\AgencyPack\Usercentrics\Hooks\PageRendererPreProcess::class]
        = \T3G\AgencyPack\Usercentrics\Hooks\PageRendererPreProcess::class . '->addLibrary';
});
