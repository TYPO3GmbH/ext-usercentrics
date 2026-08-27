<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders a real frontend page through the site set and asserts what actually
 * reaches the HTML. This is the layer the unit suite cannot reach: the site set
 * has to be resolved, the settings read, the event dispatched and the assets
 * rendered by the AssetRenderer.
 *
 * The same expectations hold on TYPO3 v13 and v14, which is what makes this
 * suite the proof that one version serves both.
 */
final class UsercentricsRenderingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['frontend', 'fluid'];

    protected array $testExtensionsToLoad = ['t3g/usercentrics'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $this->writeSiteConfiguration('usercentrics', [
            'rootPageId' => 1,
            'base' => 'http://localhost/',
            'dependencies' => ['t3g/usercentrics'],
            'settings' => [
                'plugin' => [
                    'tx_usercentrics' => [
                        'settingsId' => 'TEST-SETTINGS-ID',
                        'language' => 'current',
                        'jsFiles' => [
                            [
                                'file' => 'EXT:usercentrics/Resources/Public/Icons/Extension.svg',
                                'dataProcessingService' => 'Google Analytics',
                                'attributes' => ['async' => 'async'],
                                'options' => ['priority' => 1],
                            ],
                        ],
                        'jsInline' => [
                            [
                                'value' => "alert('consent required');",
                                'dataProcessingService' => 'Custom Inline Service',
                                'attributes' => ['custom' => 'attribute'],
                            ],
                        ],
                    ],
                ],
            ],
            'languages' => [
                $this->buildLanguage(0, 'en_US.UTF-8', '/'),
                $this->buildLanguage(1, 'de_DE.UTF-8', '/de/'),
            ],
        ]);

        // A site that does not include the set at all. The extension must leave it alone.
        $this->writeSiteConfiguration('plain', [
            'rootPageId' => 2,
            'base' => 'http://localhost/plain/',
            'languages' => [$this->buildLanguage(0, 'en_US.UTF-8', '/')],
        ]);

        $this->setUpFrontendRootPage(1, ['EXT:usercentrics/Tests/Functional/Fixtures/TypoScript/page.typoscript']);
        $this->setUpFrontendRootPage(2, ['EXT:usercentrics/Tests/Functional/Fixtures/TypoScript/plain.typoscript']);
    }

    #[Test]
    public function usercentricsLibraryIsRenderedWithTheConfiguredSettingsId(): void
    {
        $html = $this->renderPage('http://localhost/');

        self::assertStringContainsString('src="https://app.usercentrics.eu/latest/main.js"', $html);
        self::assertStringContainsString('id="TEST-SETTINGS-ID"', $html);
    }

    /**
     * The "current" keyword has to become the language of the site language that
     * answered the request.
     */
    #[Test]
    public function currentLanguageIsResolvedFromTheSiteLanguage(): void
    {
        self::assertStringContainsString('language="en"', $this->renderPage('http://localhost/'));
        self::assertStringContainsString('language="de"', $this->renderPage('http://localhost/de/'));
    }

    /**
     * Without type="text/plain" and data-usercentrics the browser would execute the
     * script before consent was given, which is the whole point of the extension.
     */
    #[Test]
    public function configuredJavaScriptFileIsRenderedBlockedForConsent(): void
    {
        $tag = $this->grabScriptTag($this->renderPage('http://localhost/'), 'Google Analytics');

        self::assertStringContainsString('type="text/plain"', $tag);
        self::assertStringContainsString('data-usercentrics="Google Analytics"', $tag);
        self::assertStringContainsString('async="async"', $tag);
    }

    #[Test]
    public function configuredInlineJavaScriptIsRenderedBlockedForConsent(): void
    {
        $html = $this->renderPage('http://localhost/');
        $tag = $this->grabScriptTag($html, 'Custom Inline Service');

        self::assertStringContainsString('type="text/plain"', $tag);
        self::assertStringContainsString('custom="attribute"', $tag);
        self::assertStringContainsString("alert('consent required');", $html);
    }

    #[Test]
    public function priorityOptionMovesAnAssetIntoTheHead(): void
    {
        $html = $this->renderPage('http://localhost/');
        $headEnd = (int)strpos($html, '</head>');

        self::assertLessThan(
            $headEnd,
            strpos($html, 'data-usercentrics="Google Analytics"'),
            'The jsFile configured with options.priority has to be rendered in the head.'
        );
        self::assertGreaterThan(
            $headEnd,
            strpos($html, 'data-usercentrics="Custom Inline Service"'),
            'The jsInline configured without priority has to be rendered in the body.'
        );
    }

    /**
     * Renders through the real Fluid template path, so it exercises Fluid 4 on
     * TYPO3 v13 and Fluid 5 on TYPO3 v14.
     */
    #[Test]
    public function viewHelperRegistersAssetsThroughTheGlobalFluidNamespace(): void
    {
        $html = $this->renderPage('http://localhost/');

        $fileTag = $this->grabScriptTag($html, 'ViewHelper File Service');
        self::assertStringContainsString('type="text/plain"', $fileTag);
        self::assertStringContainsString('async="async"', $fileTag);
        self::assertStringNotContainsString('src="EXT:', $fileTag);

        $inlineTag = $this->grabScriptTag($html, 'ViewHelper Inline Service');
        self::assertStringContainsString('type="text/plain"', $inlineTag);
        self::assertStringContainsString("alert('viewhelper inline');", $html);
    }

    /**
     * Fluid 5 removed registerUniversalTagAttributes(), so an attribute the
     * ViewHelper does not declare reaches the tag through additional arguments.
     * This asserts that path through a real template on both majors.
     */
    #[Test]
    public function viewHelperForwardsUndeclaredAttributesToTheScriptTag(): void
    {
        $tag = $this->grabScriptTag($this->renderPage('http://localhost/'), 'ViewHelper File Service');

        self::assertStringContainsString('class="consent-script"', $tag);
        self::assertStringContainsString('data-foo="bar"', $tag);
    }

    #[Test]
    public function viewHelperPriorityArgumentMovesTheAssetIntoTheHead(): void
    {
        $html = $this->renderPage('http://localhost/');
        $headEnd = (int)strpos($html, '</head>');

        self::assertLessThan(
            $headEnd,
            strpos($html, 'data-usercentrics="ViewHelper Priority Service"'),
            'The ViewHelper called with priority has to be rendered in the head.'
        );
        self::assertGreaterThan(
            $headEnd,
            strpos($html, 'data-usercentrics="ViewHelper File Service"'),
            'The ViewHelper called without priority has to be rendered in the body.'
        );
    }

    /**
     * Regression: reading an absent settingsId used to abort the request with
     * InvalidArgumentException 1583774571, so every page of every site that did
     * not include the set answered HTTP 500.
     */
    #[Test]
    public function siteWithoutTheSiteSetIsNotTouched(): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('http://localhost/plain/'));
        $html = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Page without usercentrics', $html);
        self::assertStringNotContainsString('app.usercentrics.eu', $html);
        self::assertStringNotContainsString('data-usercentrics', $html);
    }

    private function renderPage(string $uri): string
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame(200, $response->getStatusCode(), 'Frontend request to ' . $uri . ' failed.');

        return (string)$response->getBody();
    }

    /**
     * The asset identifier carries no stable position in the markup, so the tag is
     * located by the data processing service it was rendered for.
     */
    private function grabScriptTag(string $html, string $dataProcessingService): string
    {
        $pattern = '#<script[^>]*data-usercentrics="' . preg_quote($dataProcessingService, '#') . '"[^>]*>#';
        self::assertMatchesRegularExpression($pattern, $html, 'No script tag for "' . $dataProcessingService . '".');
        preg_match($pattern, $html, $matches);

        return $matches[0];
    }

    private function buildLanguage(int $languageId, string $locale, string $base): array
    {
        return [
            'title' => $locale,
            'enabled' => true,
            'languageId' => $languageId,
            'base' => $base,
            'locale' => $locale,
            'navigationTitle' => $locale,
            'flag' => 'global',
            'fallbackType' => 'fallback',
            'fallbacks' => [0],
        ];
    }

    private function writeSiteConfiguration(string $identifier, array $configuration): void
    {
        $this->get(SiteWriter::class)->write($identifier, $configuration);
        // File-backed caches survive the per-test truncation, so TypoScript cached
        // for a previous site configuration would otherwise answer here.
        $this->get(CacheManager::class)->flushCaches();
    }
}
