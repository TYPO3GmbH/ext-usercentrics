<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\Event\ResolveJavaScriptImportEvent;

class ImportMapConfigurator
{
    #[AsEventListener]
    public function __invoke(ResolveJavaScriptImportEvent $event): void
    {
        // TYPO3 does not provide a way to add custom settings types yet.
        // Therefore, this event listener is used for now.
        if ($event->specifier === '@typo3/backend/settings/editor.js') {
            $event->importMap->includeImportsFor('@t3g/usercentrics/settings/type/uc-file.js');
        }
    }
}
