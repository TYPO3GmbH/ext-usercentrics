<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Usercentrics\EventListener\ImportMapConfigurator;
use TYPO3\CMS\Core\Page\Event\ResolveJavaScriptImportEvent;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * ImportMap is mocked, which keeps this suite independent of its constructor
 * signature and therefore valid on both TYPO3 v13 and v14.
 */
final class ImportMapConfiguratorTest extends UnitTestCase
{
    #[Test]
    public function settingsTypeModuleIsRegisteredAlongsideTheSettingsEditor(): void
    {
        $importMap = $this->createMock(ImportMap::class);
        $importMap->expects(self::once())
            ->method('includeImportsFor')
            ->with('@t3g/usercentrics/settings/type/uc-file.js');

        $event = new ResolveJavaScriptImportEvent('@typo3/backend/settings/editor.js', true, $importMap);

        (new ImportMapConfigurator())($event);
    }

    #[Test]
    public function unrelatedSpecifiersAreLeftAlone(): void
    {
        $importMap = $this->createMock(ImportMap::class);
        $importMap->expects(self::never())->method('includeImportsFor');

        $event = new ResolveJavaScriptImportEvent('@typo3/backend/modal.js', true, $importMap);

        (new ImportMapConfigurator())($event);
    }
}
