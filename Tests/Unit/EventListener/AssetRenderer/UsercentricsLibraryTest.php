<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Unit\EventListener\AssetRenderer;

use T3G\AgencyPack\Usercentrics\EventListener\AssetRenderer\UsercentricsLibrary;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UsercentricsLibraryTest extends UnitTestCase
{
    public function testAddLibraryThrowsExceptionIfUsercentricsIdIsNotSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774571);

        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [

                ],
            ],
        ]);

        $assetCollector = new AssetCollector();
        $event = new BeforeJavaScriptsRenderingEvent($assetCollector, true, true);
        (new UsercentricsLibrary())($event);
    }

    public function testAddLibraryAddsMainUsercentricsScript(): void
    {
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                ],
            ],
        ]);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getJavaScripts();
        self::assertArrayHasKey('usercentrics', $javaScripts);
        self::assertSame([
            'source' => 'https://app.usercentrics.eu/latest/main.js',
            'attributes' => [
                'type' => 'application/javascript',
                'id' => 'myUsercentricsId',
                'language' => 'en',
            ],
            'options' => [],
        ], $javaScripts['usercentrics']);
    }

    public function testAddLibraryIsSkippedIfNoIdentifierGivenForFile(): void
    {
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles' => [
                        0 => [
                            'file' => 'EXT:site/Resources/Public/JavaScript/test.js',
                        ],
                    ],
                ],
            ],
        ], false);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getJavaScripts();
        self::assertArrayHasKey('usercentrics', $javaScripts);
        self::assertSame([
            'source' => 'https://app.usercentrics.eu/latest/main.js',
            'attributes' => [
                'type' => 'application/javascript',
                'id' => 'myUsercentricsId',
                'language' => 'en',
            ],
            'options' => [],
        ], $javaScripts['usercentrics']);
    }

    public function testAddLibraryIsSkippedIfNoFileGiven(): void
    {
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles' => [
                        '10' => [
                        ],
                    ],
                ],
            ],
        ], false);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getJavaScripts();
        self::assertArrayHasKey('usercentrics', $javaScripts);
        self::assertSame([
            'source' => 'https://app.usercentrics.eu/latest/main.js',
            'attributes' => [
                'type' => 'application/javascript',
                'id' => 'myUsercentricsId',
                'language' => 'en',
            ],
            'options' => [],
        ], $javaScripts['usercentrics']);
    }

    public function testAddLibraryAddsConfiguredFileWithAttributes(): void
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles' => [
                        '10' => [
                            'file' => $file,
                            'dataProcessingService' => $identifier,
                            'attributes' => [
                                'custom' => 'attribute'
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $expectedAttributes = [
            'custom' => 'attribute',
            'type' => 'text/plain',
            'data-usercentrics' => $identifier
        ];

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getJavaScripts();
        $addedScript = current(array_filter($javaScripts, static function (string $usedIdentifier) use ($identifier) {
            return str_starts_with($usedIdentifier, $identifier);
        }, ARRAY_FILTER_USE_KEY));
        self::assertSame([
            'source' => 'EXT:site/Resources/Public/JavaScript/test.js',
            'attributes' => $expectedAttributes,
            'options' => [],
        ], $addedScript);
    }

    public function testAddLibraryAddsConfiguredFileWithAttributesAndOptions(): void
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles' => [
                        '10' => [
                            'file' => $file,
                            'dataProcessingService' => $identifier,
                            'attributes' => [
                                'custom' => 'attribute'
                            ],
                            'options' => [
                                'priority' => '1'
                            ]
                        ],
                    ],
                ],
            ],
        ]);

        $expectedAttributes = [
            'custom' => 'attribute',
            'type' => 'text/plain',
            'data-usercentrics' => $identifier
        ];
        $expectedOptions = [
            'priority' => true
        ];

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getJavaScripts();
        $addedScript = current(array_filter($javaScripts, static function (string $usedIdentifier) use ($identifier) {
            return str_starts_with($usedIdentifier, $identifier);
        }, ARRAY_FILTER_USE_KEY));
        self::assertSame([
            'source' => $file,
            'attributes' => $expectedAttributes,
            'options' => $expectedOptions,
        ], $addedScript);
    }

    public function testAddLibraryIsSkippedIfNoIdentifierGivenForInlineJs(): void
    {
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsInline' => [
                        '10' => [
                            'value' => 'alert(123);',
                        ],
                    ],
                ],
            ],
        ], false);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getJavaScripts();
        self::assertArrayHasKey('usercentrics', $javaScripts);
        self::assertSame([
            'source' => 'https://app.usercentrics.eu/latest/main.js',
            'attributes' => [
                'type' => 'application/javascript',
                'id' => 'myUsercentricsId',
                'language' => 'en',
            ],
            'options' => [],
        ], $javaScripts['usercentrics']);
    }

    public function testAddLibraryAddsInlineJsWithAttributesAndOptions(): void
    {
        $value = 'alert(123);';
        $identifier = 'myIdentifier';
        $this->mockSettings([
            'plugin' => [
                'tx_usercentrics' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsInline' => [
                        '10' => [
                            'value' => $value,
                            'dataProcessingService' => $identifier,
                            'attributes' => [
                                'custom' => 'attribute'
                            ],
                            'options' => [
                                'priority' => '1'
                            ]
                        ],
                    ],
                ],
            ],
        ]);

        $expectedAttributes = [
            'custom' => 'attribute',
            'type' => 'text/plain',
            'data-usercentrics' => $identifier
        ];
        $expectedOptions = [
            'priority' => true
        ];

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
        (new UsercentricsLibrary())($event);

        $javaScripts = $event->getAssetCollector()->getInlineJavaScripts();
        $addedScript = current(array_filter($javaScripts, static function (string $usedIdentifier) use ($identifier) {
            return str_starts_with($usedIdentifier, $identifier);
        }, ARRAY_FILTER_USE_KEY));
        self::assertSame([
            'source' => $value,
            'attributes' => $expectedAttributes,
            'options' => $expectedOptions,
        ], $addedScript);
    }

    /**
     * @param bool $createSettingsMap As the settings validation is skipped in this mock, this parameter should be set to false when passing an invalid settings tree.
     */
    private function mockSettings(array $settingsTree = [], bool $createSettingsMap = true): void
    {
        if ($createSettingsMap) {
            $settingsMap = [];
            foreach (($settingsTree['plugin']['tx_usercentrics'] ?? []) as $key => $value) {
                $settingsMap['plugin.tx_usercentrics.' . $key] = $value;
            }

            $settings = SiteSettings::create(new Settings($settingsMap));
        } else {
            $settings = SiteSettings::createFromSettingsTree($settingsTree);
        }

        $site = new Site('test', 1, [], $settings);

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', 1)
            ->withAttribute('site', $site);
    }
}
