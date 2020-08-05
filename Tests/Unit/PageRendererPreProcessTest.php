<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Unit;

use Prophecy\Argument;
use T3G\AgencyPack\Usercentrics\Hooks\PageRendererPreProcess;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageRendererPreProcessTest extends UnitTestCase
{
    protected $templateService;
    protected $assetCollector;

    protected function setUp(): void
    {
        parent::setUp();
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $this->templateService = $this->prophesize(TemplateService::class);
        $tsfeProphecy->tmpl = $this->templateService->reveal();
        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();
        $this->assetCollector = $this->prophesize(AssetCollector::class);
        $this->assetCollector->addJavaScript(Argument::cetera())->willReturn($this->assetCollector->reveal());
        $this->assetCollector->addInlineJavaScript(Argument::cetera())->willReturn($this->assetCollector->reveal());
    }

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfUsercentricsIdIsNotSet(): void
    {
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [

                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774571);

        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();
    }

    /**
     * @test
     */
    public function addLibraryAddsMainUsercentricsScript(): void
    {
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                ],
            ],
        ];

        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();

        $this->assetCollector->addJavaScript('usercentrics', 'https://app.usercentrics.eu/latest/main.js', [
            'type' => 'application/javascript',
            'id' => 'myUsercentricsId',
            'language' => 'en',
        ])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoIdentifierGivenForFile(): void
    {
        $this->templateService->setup = [
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
        ];

        $this->expectExceptionCode(1583774683);

        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();
    }

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoFileGiven(): void
    {
        $this->templateService->setup = [
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
        ];

        $this->expectExceptionCode(1583774682);

        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();
    }

    /**
     * @test
     */
    public function addLibraryAddsConfiguredFileWithAttributes(): void
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles.' => [
                        '10.' => [
                            'file' => $file,
                            'dataServiceProcessor' => $identifier,
                            'attributes.' => [
                                'custom' => 'attribute'
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $expectedAttributes = [
            'custom' => 'attribute',
            'type' => 'text/plain',
            'data-usercentrics' => $identifier
        ];
        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();

        $this->assetCollector->addJavaScript($identifier, $file, $expectedAttributes);
    }

    /**
     * @test
     */
    public function addLibraryAddsConfiguredFileWithAttributesAndOptions(): void
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsFiles.' => [
                        '10.' => [
                            'file' => $file,
                            'dataServiceProcessor' => $identifier,
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
        ];

        $expectedAttributes = [
            'custom' => 'attribute',
            'type' => 'text/plain',
            'data-usercentrics' => $identifier
        ];
        $expectedOptions = [
            'priority' => true
        ];
        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();

        $this->assetCollector->addJavaScript($identifier, $file, $expectedAttributes, $expectedOptions);
    }

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoIdentifierGivenForInlineJs(): void
    {
        $this->templateService->setup = [
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
        ];

        $this->expectExceptionCode(1583774685);

        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();
    }

    /**
     * @test
     */
    public function addLibraryAddsInlineJsWithAttributesAndOptions(): void
    {
        $value = 'alert(123);';
        $identifier = 'myIdentifier';
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                    'language' => 'en',
                    'jsInline.' => [
                        '10.' => [
                            'value' => $value,
                            'dataServiceProcessor' => $identifier,
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
        ];

        $expectedAttributes = [
            'custom' => 'attribute',
            'type' => 'text/plain',
            'data-usercentrics' => $identifier
        ];
        $expectedOptions = [
            'priority' => true
        ];
        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();

        $this->assetCollector->addInlineJavaScript($identifier, $value, $expectedAttributes, $expectedOptions);
    }
}
