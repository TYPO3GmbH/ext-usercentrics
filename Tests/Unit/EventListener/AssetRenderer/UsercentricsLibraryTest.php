<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Unit\EventListener\AssetRenderer;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Usercentrics\EventListener\AssetRenderer\UsercentricsLibrary;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Covers the listener on both TYPO3 v13 and v14. Both majors ship the very same
 * settings API, so a single set of expectations holds for either.
 */
final class UsercentricsLibraryTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function listenerIsInertForNonInlineEvents(): void
    {
        $this->setUpRequest(['settingsId' => 'myUsercentricsId']);
        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), false, true);

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function listenerIsInertForNonPriorityEvents(): void
    {
        $this->setUpRequest(['settingsId' => 'myUsercentricsId']);
        $event = new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, false);

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function listenerIsInertWithoutRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function listenerIsInertInBackendContext(): void
    {
        $this->setUpRequest(['settingsId' => 'myUsercentricsId'], SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function listenerIsInertWithoutApplicationType(): void
    {
        $this->setUpRequest(['settingsId' => 'myUsercentricsId'], null);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function listenerIsInertWithoutSite(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function listenerIsInertForNullSite(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', new NullSite());
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    /**
     * A site that does not include the site set must stay untouched instead of
     * being aborted with an exception.
     */
    #[Test]
    public function listenerIsInertWhenSiteSetIsNotIncluded(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', new Site('test', 1, [], SiteSettings::create(new Settings([
                'plugin.tx_someother.foo' => 'bar',
            ]))));
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([], $event->getAssetCollector()->getJavaScripts());
    }

    #[Test]
    public function throwsExceptionIfUsercentricsIdIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774571);

        $this->setUpRequest(['settingsId' => '']);

        (new UsercentricsLibrary())($this->createEvent());
    }

    #[Test]
    public function addsMainUsercentricsScript(): void
    {
        $this->setUpRequest(['settingsId' => 'myUsercentricsId', 'language' => 'en']);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame([
            'source' => 'https://app.usercentrics.eu/latest/main.js',
            'attributes' => [
                'type' => 'application/javascript',
                'id' => 'myUsercentricsId',
                'language' => 'en',
            ],
            'options' => [],
        ], $event->getAssetCollector()->getJavaScripts()['usercentrics']);
    }

    #[Test]
    public function resolvesLanguageFromSiteLanguageWhenSetToCurrent(): void
    {
        $this->setUpRequest(
            ['settingsId' => 'myUsercentricsId', 'language' => 'current'],
            SystemEnvironmentBuilder::REQUESTTYPE_FE,
            new SiteLanguage(0, 'de-DE', new \TYPO3\CMS\Core\Http\Uri('https://example.com/'), [])
        );
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame(
            'de',
            $event->getAssetCollector()->getJavaScripts()['usercentrics']['attributes']['language']
        );
    }

    #[Test]
    public function fallsBackToEmptyLanguageWhenCurrentCannotBeResolved(): void
    {
        $this->setUpRequest(['settingsId' => 'myUsercentricsId', 'language' => 'current']);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame(
            '',
            $event->getAssetCollector()->getJavaScripts()['usercentrics']['attributes']['language']
        );
    }

    #[Test]
    public function addsConfiguredJsFileWithAttributesAndOptions(): void
    {
        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'language' => 'en',
            'jsFiles' => [
                [
                    'file' => 'EXT:site/Resources/Public/JavaScript/test.js',
                    'dataProcessingService' => 'My Service',
                    'attributes' => ['async' => 'async'],
                    'options' => ['priority' => '1'],
                ],
            ],
        ]);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        $added = $this->findAssetByIdentifierPrefix($event->getAssetCollector()->getJavaScripts(), 'My Service-');
        self::assertSame([
            'source' => 'EXT:site/Resources/Public/JavaScript/test.js',
            'attributes' => [
                'async' => 'async',
                'type' => 'text/plain',
                'data-usercentrics' => 'My Service',
            ],
            'options' => ['priority' => true],
        ], $added);
    }

    #[Test]
    public function throwsExceptionForJsFileWithoutFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774682);

        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsFiles' => [['dataProcessingService' => 'My Service']],
        ]);

        (new UsercentricsLibrary())($this->createEvent());
    }

    #[Test]
    public function throwsExceptionForJsFileWithoutDataProcessingService(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774683);

        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsFiles' => [['file' => 'EXT:site/Resources/Public/JavaScript/test.js']],
        ]);

        (new UsercentricsLibrary())($this->createEvent());
    }

    #[Test]
    public function addsConfiguredInlineJavaScript(): void
    {
        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'language' => 'en',
            'jsInline' => [
                [
                    'value' => 'alert(123);',
                    'dataProcessingService' => 'My Service',
                    'attributes' => ['custom' => 'attribute'],
                ],
            ],
        ]);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        $added = $this->findAssetByIdentifierPrefix($event->getAssetCollector()->getInlineJavaScripts(), 'My Service-');
        self::assertSame([
            'source' => 'alert(123);',
            'attributes' => [
                'custom' => 'attribute',
                'type' => 'text/plain',
                'data-usercentrics' => 'My Service',
            ],
            'options' => [],
        ], $added);
    }

    #[Test]
    public function throwsExceptionForInlineJavaScriptThatIsNotAnArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774684);

        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsInline' => ['alert(123);'],
        ]);

        (new UsercentricsLibrary())($this->createEvent());
    }

    #[Test]
    public function throwsExceptionForInlineJavaScriptWithoutDataProcessingService(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1583774685);

        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsInline' => [['value' => 'alert(123);']],
        ]);

        (new UsercentricsLibrary())($this->createEvent());
    }

    #[Test]
    public function priorityOptionIsConvertedToBooleanForInlineJavaScript(): void
    {
        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsInline' => [
                [
                    'value' => 'alert(1);',
                    'dataProcessingService' => 'My Service',
                    'options' => ['priority' => '1'],
                ],
            ],
        ]);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        $added = $this->findAssetByIdentifierPrefix($event->getAssetCollector()->getInlineJavaScripts(), 'My Service-');
        self::assertSame(['priority' => true], $added['options']);
    }

    #[Test]
    public function falsyPriorityOptionIsLeftUntouched(): void
    {
        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsFiles' => [
                [
                    'file' => 'my.js',
                    'dataProcessingService' => 'My Service',
                    'options' => ['priority' => '0'],
                ],
            ],
        ]);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        $added = $this->findAssetByIdentifierPrefix($event->getAssetCollector()->getJavaScripts(), 'My Service-');
        self::assertSame(['priority' => '0'], $added['options']);
    }

    #[Test]
    public function everyConfiguredEntryIsCollected(): void
    {
        $this->setUpRequest([
            'settingsId' => 'myUsercentricsId',
            'jsFiles' => [
                ['file' => 'a.js', 'dataProcessingService' => 'Service A'],
                ['file' => 'b.js', 'dataProcessingService' => 'Service B'],
            ],
            'jsInline' => [
                ['value' => 'alert(1);', 'dataProcessingService' => 'Service C'],
                ['value' => 'alert(2);', 'dataProcessingService' => 'Service D'],
            ],
        ]);
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        // The main library plus both configured files.
        self::assertCount(3, $event->getAssetCollector()->getJavaScripts());
        self::assertCount(2, $event->getAssetCollector()->getInlineJavaScripts());
        self::assertSame('b.js', $this->findAssetByIdentifierPrefix($event->getAssetCollector()->getJavaScripts(), 'Service B-')['source']);
        self::assertSame('alert(2);', $this->findAssetByIdentifierPrefix($event->getAssetCollector()->getInlineJavaScripts(), 'Service D-')['source']);
    }

    #[Test]
    public function explicitlyConfiguredLanguageIsUsedInsteadOfTheSiteLanguage(): void
    {
        $this->setUpRequest(
            ['settingsId' => 'myUsercentricsId', 'language' => 'fr'],
            SystemEnvironmentBuilder::REQUESTTYPE_FE,
            new SiteLanguage(0, 'de-DE', new \TYPO3\CMS\Core\Http\Uri('https://example.com/'), [])
        );
        $event = $this->createEvent();

        (new UsercentricsLibrary())($event);

        self::assertSame(
            'fr',
            $event->getAssetCollector()->getJavaScripts()['usercentrics']['attributes']['language']
        );
    }

    private function createEvent(): BeforeJavaScriptsRenderingEvent
    {
        return new BeforeJavaScriptsRenderingEvent(new AssetCollector(), true, true);
    }

    /**
     * The asset identifier carries a random suffix, so it can only be matched by prefix.
     */
    private function findAssetByIdentifierPrefix(array $assets, string $prefix): array
    {
        foreach ($assets as $identifier => $asset) {
            if (str_starts_with((string)$identifier, $prefix)) {
                return $asset;
            }
        }
        self::fail(sprintf('No asset with identifier prefix "%s" was collected.', $prefix));
    }

    /**
     * @param array<string, mixed> $usercentricsSettings Keys below plugin.tx_usercentrics
     */
    private function setUpRequest(
        array $usercentricsSettings,
        ?int $applicationType = SystemEnvironmentBuilder::REQUESTTYPE_FE,
        ?SiteLanguage $siteLanguage = null
    ): void {
        $settingsMap = [];
        foreach ($usercentricsSettings as $key => $value) {
            $settingsMap['plugin.tx_usercentrics.' . $key] = $value;
        }
        $site = new Site('test', 1, [], SiteSettings::create(new Settings($settingsMap)));

        $request = (new ServerRequest())->withAttribute('site', $site);
        if ($applicationType !== null) {
            $request = $request->withAttribute('applicationType', $applicationType);
        }
        if ($siteLanguage !== null) {
            $request = $request->withAttribute('language', $siteLanguage);
        }

        $GLOBALS['TYPO3_REQUEST'] = $request;
    }
}
