<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\ViewHelpers;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Usercentrics Viewhelper
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <usercentrics:script identifier="foo" dataProcessingService="Data Service" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
 *    <usercentrics:script identifier="bar" dataProcessingService="Data Service">
 *       alert('hello world');
 *    </usercentrics:script>
 */
class ScriptViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * Rendered children string is passed as JavaScript code,
     * there is no point in HTML encoding anything from that.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var string
     */
    protected $tagName = 'script';

    /**
     * Attributes that are forwarded to the script tag. Registered as plain arguments
     * because registerTagAttribute() was removed with Fluid 5 (TYPO3 v14).
     */
    private const SCRIPT_ATTRIBUTES = [
        'async',
        'crossorigin',
        'defer',
        'integrity',
        'nomodule',
        'nonce',
        'referrerpolicy',
        'src',
        'type',
    ];

    protected AssetCollector $assetCollector;

    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initialize(): void
    {
        // Add a tag builder, that does not html encode values, because rendering with encoding happens in AssetRenderer.
        // The signature is intentionally untyped to stay compatible with both Fluid 4 (TYPO3 v13) and Fluid 5 (TYPO3 v14).
        $this->setTagBuilder(
            new class() extends TagBuilder {
                public function addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters = false): void
                {
                    parent::addAttribute($attributeName, $attributeValue, false);
                }
            }
        );
        parent::initialize();
        foreach (self::SCRIPT_ATTRIBUTES as $attributeName) {
            if ($this->hasArgument($attributeName)) {
                $this->tag->addAttribute($attributeName, $this->arguments[$attributeName]);
            }
        }
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('async', 'bool', 'Define that the script will be fetched in parallel to parsing and evaluation.');
        $this->registerArgument('crossorigin', 'string', 'Define how to handle crossorigin requests.');
        $this->registerArgument('defer', 'bool', 'Define that the script is meant to be executed after the document has been parsed.');
        $this->registerArgument('integrity', 'string', 'Define base64-encoded cryptographic hash of the resource that allows browsers to verify what they fetch.');
        $this->registerArgument('nomodule', 'bool', 'Define that the script should not be executed in browsers that support ES2015 modules.');
        $this->registerArgument('nonce', 'string', 'Define a cryptographic nonce (number used once) used to whitelist inline styles in a style-src Content-Security-Policy.');
        $this->registerArgument('referrerpolicy', 'string', 'Define which referrer is sent when fetching the resource.');
        $this->registerArgument('src', 'string', 'Define the URI of the external resource.');
        $this->registerArgument('type', 'string', 'Define the MIME type (usually \'text/javascript\').');
        $this->registerArgument('useNonce', 'bool', 'Whether to use the global nonce value', false, false);
        $this->registerArgument(
            'identifier',
            'string',
            'Use this identifier within templates to only inject your JS once, even though it is added multiple times.',
            true
        );
        $this->registerArgument(
            'priority',
            'boolean',
            'Define whether the JavaScript should be put in the <head> tag above-the-fold or somewhere in the body part.',
            false,
            false
        );
        $this->registerArgument('dataProcessingService', 'string', 'the data processing service name as configured in Usercentrics', true);
    }

    public function render(): string
    {
        $dataProcessingService = $this->getDataProcessingService();
        // The identifier is what makes the AssetCollector inject a script only once,
        // even if the ViewHelper is called multiple times with the same identifier.
        $identifier = (string)$this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $dataProcessingService;
        $src = $this->tag->getAttribute('src');
        unset($attributes['src']);
        $options = [
            'priority' => (bool)$this->arguments['priority'],
        ];
        if ($this->arguments['useNonce']) {
            // The "useNonce" option is deprecated in favour of "csp" as of TYPO3 v14.
            $options[$this->isTypo3Version14OrHigher() ? 'csp' : 'useNonce'] = true;
        }
        if ($src !== null) {
            $this->assetCollector->addJavaScript($identifier, html_entity_decode($src), $attributes, $options);
        } else {
            $content = (string)$this->renderChildren();
            if ($content !== '') {
                $this->assetCollector->addInlineJavaScript($identifier, $content, $attributes, $options);
            }
        }
        return '';
    }

    protected function getDataProcessingService(): string
    {
        return $this->arguments['dataProcessingService'];
    }

    protected function isTypo3Version14OrHigher(): bool
    {
        return (new Typo3Version())->getMajorVersion() >= 14;
    }
}
