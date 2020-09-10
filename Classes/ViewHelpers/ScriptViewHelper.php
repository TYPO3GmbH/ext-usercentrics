<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\ViewHelpers;

use T3G\AgencyPack\Usercentrics\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Usercentrics Viewhelper
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <usercentrics:script dataServiceProcessor="Data Service" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
 *    <usercentrics:script dataServiceProcessor="Data Service">
 *       alert('hello world');
 *    </usercentrics:script>
 */
class ScriptViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var AssetCollector
     */
    protected $assetCollector;

    /**
     * @param AssetCollector $assetCollector
     */
    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        parent::registerUniversalTagAttributes();
        $this->registerTagAttribute('async', 'bool', '', false);
        $this->registerTagAttribute('crossorigin', 'string', '', false);
        $this->registerTagAttribute('defer', 'bool', '', false);
        $this->registerTagAttribute('integrity', 'string', '', false);
        $this->registerTagAttribute('nomodule', 'bool', '', false);
        $this->registerTagAttribute('nonce', 'string', '', false);
        $this->registerTagAttribute('referrerpolicy', 'string', '', false);
        $this->registerTagAttribute('src', 'string', '', false);
        $this->registerTagAttribute('type', 'string', '', false);
        $this->registerArgument(
            'identifier',
            'string',
            'Use this identifier within templates to only inject your JS once, even though it is added multiple times',
            true
        );
        $this->registerArgument(
            'priority',
            'boolean',
            'Define whether the JavaScript should be put in the <head> tag above-the-fold or somewhere in the body part.',
            false,
            false
        );
        $this->registerArgument('dataServiceProcessor', 'string', 'The data processing service name as configured in Usercentrics', true);
    }

    public function render(): string
    {
        $dataServiceProcessor = $this->arguments['dataServiceProcessor'];
        $identifier = StringUtility::getUniqueId($dataServiceProcessor . '-');
        $attributes = $this->tag->getAttributes();
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $dataServiceProcessor;
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
}
