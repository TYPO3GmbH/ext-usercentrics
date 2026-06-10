<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\ViewHelpers;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\StringUtility;
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

    protected AssetCollector $assetCollector;

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

    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initialize(): void
    {
        // Add a tag builder, that does not html encode values, because rendering with encoding happens in AssetRenderer
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
            if ($this->hasArgument($attributeName) && $this->arguments[$attributeName] !== null) {
                $this->tag->addAttribute($attributeName, $this->arguments[$attributeName]);
            }
        }
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('async', 'bool', 'Define that the script will be fetched in parallel to parsing and evaluation.', false);
        $this->registerArgument('crossorigin', 'string', 'Define how to handle crossorigin requests.', false);
        $this->registerArgument('defer', 'bool', 'Define that the script is meant to be executed after the document has been parsed.', false);
        $this->registerArgument('integrity', 'string', 'Define base64-encoded cryptographic hash of the resource that allows browsers to verify what they fetch.', false);
        $this->registerArgument('nomodule', 'bool', 'Define that the script should not be executed in browsers that support ES2015 modules.', false);
        $this->registerArgument('nonce', 'string', 'Define a cryptographic nonce (number used once) used to whitelist inline styles in a style-src Content-Security-Policy.', false);
        $this->registerArgument('referrerpolicy', 'string', 'Define which referrer is sent when fetching the resource.', false);
        $this->registerArgument('src', 'string', 'Define the URI of the external resource.', false);
        $this->registerArgument('type', 'string', 'Define the MIME type (usually \'text/javascript\').', false);
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
        $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
        $attributes = $this->tag->getAttributes();
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $dataProcessingService;
        $src = $this->tag->getAttribute('src');
        unset($attributes['src']);
        $options = [
            'priority' => $this->arguments['priority']
        ];
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
}
