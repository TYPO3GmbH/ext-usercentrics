<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Hooks;

use T3G\AgencyPack\Usercentrics\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageRendererPreProcess
{

    /**
     * @var AssetCollector
     */
    private $assetCollector;

    public function __construct(AssetCollector $assetCollector = null)
    {
        // hooks: no DI yet :(
        $this->assetCollector = $assetCollector ?? GeneralUtility::makeInstance(AssetCollector::class);
    }

    public function addLibrary()
    {
        $config = $this->getTypoScriptConfiguration();
        if ($config === null) {
            return;
        }
        if (!$this->isValidId($config)) {
            throw new \InvalidArgumentException('Usercentrics ID not configured, please set plugin.tx_usercentrics.id in your TypoScript configuration', 1583774571);
        }
        $this->addUsercentricsScript($config['id']);
        $this->addConfiguredJsFiles($config['jsFiles.'] ?? []);
        $this->addConfiguredInlineJavaScript($config['jsInline.'] ?? []);
    }

    protected function addConfiguredInlineJavaScript(array $jsInline)
    {
        foreach ($jsInline as $inline) {
            $code = $inline['value'] ?? '';
            if (!$this->isValidIdentifier($inline)) {
                throw new \InvalidArgumentException('No valid identifier given for inline JS, please check TypoScript configuration.', 1583774685);
            }
            $identifier = $inline['identifier'];
            $attributes = $this->getAttributesForUsercentrics($inline['attributes.'] ?? [], $identifier);
            $options = $this->convertPriorityToBoolean($inline['options.'] ?? []);
            $this->assetCollector->addInlineJavaScript($identifier, $code, $attributes, $options);
        }
    }

    protected function addConfiguredJsFiles(array $jsFiles)
    {
        foreach ($jsFiles as $jsFile) {
            if (!$this->isValidFile($jsFile)) {
                throw new \InvalidArgumentException('No valid file given, please check TypoScript configuration.', 1583774682);
            }
            if (!$this->isValidIdentifier($jsFile)) {
                throw new \InvalidArgumentException('No valid identifier given for file, please check TypoScript configuration.', 1583774683);
            }
            $identifier = $jsFile['identifier'];
            $attributes = $this->getAttributesForUsercentrics($jsFile['attributes.'] ?? [], $identifier);
            $options = $this->convertPriorityToBoolean($jsFile['options.'] ?? []);
            $this->assetCollector->addJavaScript($identifier, $jsFile['file'], $attributes, $options);
        }
    }

    protected function addUsercentricsScript(string $id)
    {
        $this->assetCollector->addJavaScript('usercentrics', 'https://app.usercentrics.eu/latest/main.js', [
            'type' => 'application/javascript',
            'id' => $id
        ]);
    }

    protected function convertPriorityToBoolean(array $options): array
    {
        if (!empty($options['priority'])) {
            // make it a real boolean
            $options['priority'] = true;
        }
        return $options;
    }

    protected function getAttributesForUsercentrics(array $attributes, string $identifier): array
    {
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $identifier;
        return $attributes;
    }

    protected function getTypoScriptConfiguration()
    {
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            return null;
        }
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        $ts = $tsfe->tmpl->setup;
        return $ts['plugin.']['tx_usercentrics.'] ?? null;
    }

    protected function isValidFile(array $jsFile): bool
    {
        return isset($jsFile['file']) && is_string($jsFile['file']);
    }

    protected function isValidId(array $config): bool
    {
        return isset($config['id']) && is_string($config['id']);
    }

    protected function isValidIdentifier(array $jsFile): bool
    {
        return isset($jsFile['identifier']) && is_string($jsFile['identifier']);
    }
}
