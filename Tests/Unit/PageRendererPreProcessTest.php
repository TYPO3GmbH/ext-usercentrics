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
use T3G\AgencyPack\Usercentrics\Page\AssetCollector;
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
    public function addLibraryThrowsExceptionIfUsercentricsIdIsNotSet()
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
    public function addLibraryAddsMainUsercentricsScript()
    {
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
                ],
            ],
        ];

        $pageRendererPreProcess = new PageRendererPreProcess($this->assetCollector->reveal());
        $pageRendererPreProcess->addLibrary();

        $this->assetCollector->addJavaScript('usercentrics', 'https://app.usercentrics.eu/latest/main.js', [
            'type' => 'application/javascript',
            'id' => 'myUsercentricsId',
        ])->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function addLibraryThrowsExceptionIfNoIdentifierGivenForFile()
    {
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
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
    public function addLibraryThrowsExceptionIfNoFileGiven()
    {
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
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
    public function addLibraryAddsConfiguredFileWithAttributes()
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
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
    public function addLibraryAddsConfiguredFileWithAttributesAndOptions()
    {
        $file = 'EXT:site/Resources/Public/JavaScript/test.js';
        $identifier = 'myIdentifier';
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
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
    public function addLibraryThrowsExceptionIfNoIdentifierGivenForInlineJs()
    {
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
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
    public function addLibraryAddsInlineJsWithAttributesAndOptions()
    {
        $value = 'alert(123);';
        $identifier = 'myIdentifier';
        $this->templateService->setup = [
            'plugin.' => [
                'tx_usercentrics.' => [
                    'settingsId' => 'myUsercentricsId',
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
