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
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UsercentricsLibraryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfUsercentricsIdIsNotSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774571);

        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [

                ],
            ],
        ]);

        $assetCollector = new AssetCollector();
        $event = new BeforeJavaScriptsRenderingEvent($assetCollector, false, false);
        (new UsercentricsLibrary())($event);
    }

    /**
     * @test
     */
    public function addLibraryAddsMainUsercentricsScript(): void
    {
        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                ],
            ],
        ]);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
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

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoIdentifierGivenForFile(): void
    {
        $this->expectExceptionCode(1583774683);

        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles.' => [
                        '10.' => [
                            'file' => 'EXT:site/Resources/Public/JavaScript/test.js',
                        ],
                    ],
                ],
            ],
        ]);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
        (new UsercentricsLibrary())($event);
    }

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoFileGiven(): void
    {
        $this->expectExceptionCode(1583774682);

        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles.' => [
                        '10.' => [
                        ],
                    ],
                ],
            ],
        ]);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
        (new UsercentricsLibrary())($event);
    }

    /**
     * @test
     */
    public function addLibraryAddsConfiguredFileWithAttributes(): void
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles.' => [
                        '10.' => [
                            'file' => $file,
                            'dataProcessingService' => $identifier,
                            'attributes.' => [
                                'custom' => 'attribute'
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

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
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

    /**
     * @test
     */
    public function addLibraryAddsConfiguredFileWithAttributesAndOptions(): void
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles.' => [
                        '10.' => [
                            'file' => $file,
                            'dataProcessingService' => $identifier,
                            'attributes.' => [
                                'custom' => 'attribute'
                            ],
                            'options.' => [
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

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
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

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoIdentifierGivenForInlineJs(): void
    {
        $this->expectExceptionCode(1583774685);

        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsInline.' => [
                        '10.' => [
                            'value' => 'alert(123);',
                        ],
                    ],
                ],
            ],
        ]);

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
        (new UsercentricsLibrary())($event);
    }

    /**
     * @test
     */
    public function addLibraryAddsInlineJsWithAttributesAndOptions(): void
    {
        $value = 'alert(123);';
        $identifier = 'myIdentifier';
        $this->mockTypoScriptFrontend([
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsInline.' => [
                        '10.' => [
                            'value' => $value,
                            'dataProcessingService' => $identifier,
                            'attributes.' => [
                                'custom' => 'attribute'
                            ],
                            'options.' => [
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

        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, false);
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

    private function mockTypoScriptFrontend(array $setup = []): void
    {
        $templateService = $this->createMock(TemplateService::class);
        $templateService->setup = $setup;

        $tsfe = $this->createMock(TypoScriptFrontendController::class);
        $tsfe->tmpl = $templateService;
        $GLOBALS['TSFE'] = $tsfe;
    }
}
