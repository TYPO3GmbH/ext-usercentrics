<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Usercentrics\ViewHelpers\ScriptViewHelper;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Drives the ViewHelper through its real Fluid lifecycle (initialize() + render()).
 *
 * This is the place where TYPO3 v13 and v14 genuinely differ: v13 ships Fluid 4,
 * v14 ships Fluid 5, which removed registerTagAttribute()/registerUniversalTagAttributes().
 * Calling initialize() exercises AbstractTagBasedViewHelper and TagBuilder of whichever
 * Fluid version is installed, so this suite proves the ViewHelper on both majors.
 *
 * initialize() and render() are called directly rather than via
 * initializeArgumentsAndRender(), because the latter additionally validates arguments
 * against a rendering context in Fluid 4, but not in Fluid 5.
 */
final class ScriptViewHelperTest extends UnitTestCase
{
    #[Test]
    public function externalFileIsPassedToAssetCollector(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'EXT:my_ext/Resources/Public/JavaScript/foo.js',
        ]);

        self::assertSame([
            'source' => 'EXT:my_ext/Resources/Public/JavaScript/foo.js',
            'attributes' => [
                'type' => 'text/plain',
                'data-usercentrics' => 'My Service',
            ],
            'options' => ['priority' => false, $this->getCspKey() => false],
        ], $assetCollector->getJavaScripts()['my-script']);
        self::assertSame([], $assetCollector->getInlineJavaScripts());
    }

    #[Test]
    public function inlineJavaScriptIsPassedToAssetCollector(): void
    {
        $assetCollector = $this->renderViewHelper(
            [
                'identifier' => 'my-inline',
                'dataProcessingService' => 'My Service',
            ],
            'alert(\'hello world\');'
        );

        self::assertSame([
            'source' => 'alert(\'hello world\');',
            'attributes' => [
                'type' => 'text/plain',
                'data-usercentrics' => 'My Service',
            ],
            'options' => ['priority' => false, $this->getCspKey() => false],
        ], $assetCollector->getInlineJavaScripts()['my-inline']);
        self::assertSame([], $assetCollector->getJavaScripts());
    }

    #[Test]
    public function emptyInlineJavaScriptIsNotCollected(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-inline',
            'dataProcessingService' => 'My Service',
        ], '');

        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
    }

    /**
     * TagBuilder turns boolean true into the attribute name itself. This behaviour is
     * identical in Fluid 4 and Fluid 5 and is what the removed registerTagAttribute()
     * used to provide.
     */
    #[Test]
    public function booleanAttributesAreConvertedToTheirAttributeName(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'async' => true,
            'defer' => true,
            'nomodule' => true,
        ]);

        self::assertSame([
            'async' => 'async',
            'defer' => 'defer',
            'nomodule' => 'nomodule',
            'type' => 'text/plain',
            'data-usercentrics' => 'My Service',
        ], $assetCollector->getJavaScripts()['my-script']['attributes']);
    }

    #[Test]
    public function booleanAttributesSetToFalseAreOmitted(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'async' => false,
            'defer' => false,
        ]);

        self::assertSame([
            'type' => 'text/plain',
            'data-usercentrics' => 'My Service',
        ], $assetCollector->getJavaScripts()['my-script']['attributes']);
    }

    #[Test]
    public function stringAttributesArePassedThrough(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'crossorigin' => 'anonymous',
            'integrity' => 'sha384-abc',
            'referrerpolicy' => 'no-referrer',
        ]);

        self::assertSame([
            'crossorigin' => 'anonymous',
            'integrity' => 'sha384-abc',
            'referrerpolicy' => 'no-referrer',
            'type' => 'text/plain',
            'data-usercentrics' => 'My Service',
        ], $assetCollector->getJavaScripts()['my-script']['attributes']);
    }

    /**
     * The type attribute has to be forced to text/plain, otherwise Usercentrics
     * would not be able to block the script until consent is given.
     */
    #[Test]
    public function typeAttributeCannotBeOverridden(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'type' => 'application/javascript',
        ]);

        self::assertSame(
            'text/plain',
            $assetCollector->getJavaScripts()['my-script']['attributes']['type']
        );
    }

    /**
     * Encoding happens in the AssetRenderer, so the ViewHelper must not encode twice.
     */
    #[Test]
    public function sourceIsNotHtmlEncoded(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'https://example.com/foo.js?foo=bar&bar=baz',
        ]);

        self::assertSame(
            'https://example.com/foo.js?foo=bar&bar=baz',
            $assetCollector->getJavaScripts()['my-script']['source']
        );
    }

    #[Test]
    public function priorityIsPassedAsOption(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'priority' => true,
        ]);

        self::assertSame(['priority' => true, $this->getCspKey() => false], $assetCollector->getJavaScripts()['my-script']['options']);
    }

    #[Test]
    public function nonceOptionMatchesTheRunningTypo3Major(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            $this->getCspKey() => true,
        ]);

        $expectedKey = $this->getCspKey();
        self::assertSame(
            ['priority' => false, $expectedKey => true],
            $assetCollector->getJavaScripts()['my-script']['options']
        );
    }

    #[Test]
    public function sameIdentifierIsCollectedOnlyOnce(): void
    {
        $assetCollector = new AssetCollector();
        $arguments = [
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
        ];
        $this->renderViewHelper($arguments, null, $assetCollector);
        $this->renderViewHelper($arguments, null, $assetCollector);

        self::assertCount(1, $assetCollector->getJavaScripts());
    }

    /**
     * Fluid 5 removed registerUniversalTagAttributes(), so class, id and friends are
     * no longer registered arguments. They arrive through handleAdditionalArguments()
     * instead, which is the path this asserts on both Fluid 4 and Fluid 5.
     */
    #[Test]
    public function undeclaredAttributesArePassedThroughAsAdditionalArguments(): void
    {
        $assetCollector = $this->renderViewHelper(
            [
                'identifier' => 'my-script',
                'dataProcessingService' => 'My Service',
                'src' => 'my.js',
            ],
            null,
            null,
            ['class' => 'consent-script', 'id' => 'analytics', 'data-foo' => 'bar']
        );

        self::assertSame([
            'class' => 'consent-script',
            'id' => 'analytics',
            'data-foo' => 'bar',
            'type' => 'text/plain',
            'data-usercentrics' => 'My Service',
        ], $assetCollector->getJavaScripts()['my-script']['attributes']);
    }

    #[Test]
    public function additionalAttributesArgumentIsPassedThrough(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'additionalAttributes' => ['class' => 'consent-script'],
        ]);

        self::assertSame([
            'class' => 'consent-script',
            'type' => 'text/plain',
            'data-usercentrics' => 'My Service',
        ], $assetCollector->getJavaScripts()['my-script']['attributes']);
    }

    #[Test]
    public function dataAndAriaArgumentsArePrefixed(): void
    {
        $assetCollector = $this->renderViewHelper([
            'identifier' => 'my-script',
            'dataProcessingService' => 'My Service',
            'src' => 'my.js',
            'data' => ['tracking' => 'analytics'],
            'aria' => ['hidden' => 'true'],
        ]);

        $attributes = $assetCollector->getJavaScripts()['my-script']['attributes'];
        self::assertSame('analytics', $attributes['data-tracking'] ?? null);
        self::assertSame('true', $attributes['aria-hidden'] ?? null);
    }

    /**
     * @param array<string, mixed> $arguments
     * @param string|null $children Inline JavaScript, null for a self-closing tag
     * @param array<string, mixed> $additionalArguments Attributes the ViewHelper does not declare
     */
    private function renderViewHelper(
        array $arguments,
        ?string $children = null,
        ?AssetCollector $assetCollector = null,
        array $additionalArguments = []
    ): AssetCollector {
        $assetCollector ??= new AssetCollector();

        $viewHelper = new ScriptViewHelper($assetCollector);
        $viewHelper->setRenderChildrenClosure(static fn (): string => (string)$children);
        if ($additionalArguments !== []) {
            $viewHelper->handleAdditionalArguments($additionalArguments);
        }
        // setArguments() bypasses Fluid's default handling, so every argument the
        // ViewHelper reads unconditionally has to be provided here.
        $viewHelper->setArguments($arguments + ['priority' => false, $this->getCspKey() => false]);
        $viewHelper->initialize();
        $viewHelper->render();

        return $assetCollector;
    }

    public function getCspKey(): string
    {
        return (new Typo3Version())->getMajorVersion() >= 14 ? 'csp' : 'useNonce';
    }
}
