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
     * Attributes that are forwarded to the script tag.
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

    public function __construct(protected AssetCollector $assetCollector)
    {
        parent::__construct();
    }

    public function initialize(): void
    {
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
        $this->registerArgument('useNonce', 'mixed', 'Whether to use the global nonce value');
        $this->registerArgument('csp', 'bool', 'Whether to collect a CSP hash value for this asset', false, false);
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
        $identifier = $this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $dataProcessingService;
        $src = $this->tag->getAttribute('src');
        unset($attributes['src']);
        $options = [
            'priority' => $this->arguments['priority'],
        ];
        // backwards compatibility for useNonce argument in v14
        if (isset($this->arguments['useNonce']) && $this->arguments['useNonce'] !== null) {
            $useNonce = filter_var($this->arguments['useNonce'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($this->isTypo3Version14OrHigher()) {
                trigger_error(
                    'Using the \'useNonce\' attribute on <usercentrics:script> is deprecated in TYPO3 v14. Please use the \'csp\' attribute instead.',
                    E_USER_DEPRECATED
                );
                $options['csp'] = $useNonce;
            } else {
                $options['useNonce'] = $useNonce;
            }
        } else {
            $options['csp'] = $this->arguments['csp'];
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
