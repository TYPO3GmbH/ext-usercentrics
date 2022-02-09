<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Hooks;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageRendererPreProcess
{

    /**
     * @var \TYPO3\CMS\Core\Page\AssetCollector
     */
    private $assetCollector;

    public function __construct(AssetCollector $assetCollector = null)
    {
        // hooks: no DI yet :(
        $this->assetCollector = $assetCollector ?? GeneralUtility::makeInstance(AssetCollector::class);
    }

    public function addLibrary(): void
    {
        $config = $this->getTypoScriptConfiguration();
        if ($config === null) {
            return;
        }
        if (!$this->isValidId($config)) {
            throw new \InvalidArgumentException('Usercentrics ID not configured, please set plugin.tx_usercentrics.settingsId in your TypoScript configuration', 1583774571);
        }

        $this->addUsercentricsScript($config);
        if($config['smartDataProtector'])
            $this->addSmartDataProtectorScript($config);
        $this->addConfiguredJsFiles($config['jsFiles.'] ?? []);
        $this->addConfiguredInlineJavaScript($config['jsInline.'] ?? []);
    }

    protected function addConfiguredInlineJavaScript(array $jsInline): void
    {
        foreach ($jsInline as $inline) {
            $code = $inline['value'] ?? '';
            if (!$this->isValidIdentifier($inline)) {
                throw new \InvalidArgumentException('No valid identifier given for inline JS, please check TypoScript configuration.', 1583774685);
            }
            $dataProcessingService = $this->getDataProcessingService($inline);
            $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
            $attributes = $this->getAttributesForUsercentrics($inline['attributes.'] ?? [], $dataProcessingService);
            $options = $this->convertPriorityToBoolean($inline['options.'] ?? []);
            $this->assetCollector->addInlineJavaScript($identifier, $code, $attributes, $options);
        }
    }

    protected function addConfiguredJsFiles(array $jsFiles): void
    {
        foreach ($jsFiles as $jsFile) {
            if (!$this->isValidFile($jsFile)) {
                throw new \InvalidArgumentException('No valid file given, please check TypoScript configuration.', 1583774682);
            }
            if (!$this->isValidIdentifier($jsFile)) {
                throw new \InvalidArgumentException('No valid identifier given for file, please check TypoScript configuration.', 1583774683);
            }
            $dataProcessingService = $this->getDataProcessingService($jsFile);
            $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
            $attributes = $this->getAttributesForUsercentrics($jsFile['attributes.'] ?? [], $dataProcessingService);
            $options = $this->convertPriorityToBoolean($jsFile['options.'] ?? []);
            $this->assetCollector->addJavaScript($identifier, $jsFile['file'], $attributes, $options);
        }
    }

    protected function addUsercentricsScript(array $config): void
    {
        if($config['preconnectRessources']) {
            $this->assetCollector->addStyleSheet('usercentrics-preconnect-app', '//app.usercentrics.eu', [
                    'type' => '',
                    'rel' => 'preconnect'
                ],
                [
                    'priority' => true
                ]
            );
            $this->assetCollector->addStyleSheet('usercentrics-preconnect-api', '//api.usercentrics.eu', [
                    'type' => '',
                    'rel' => 'preconnect'
                ],
                [
                    'priority' => true
                ]
            );
            $this->assetCollector->addStyleSheet('usercentrics-preload-loader', '//app.usercentrics.eu/browser-ui/latest/loader.js', [
                    'type' => '',
                    'rel' => 'preload',
                    'as' => 'script'
                ],
                [
                    'priority' => true
                ]
            );
        }

        $this->assetCollector->addJavaScript('usercentrics-cmp', 'https://app.usercentrics.eu/browser-ui/latest/loader.js', [
                'id' => 'usercentrics-cmp',
                'data-settings-id' => $config['settingsId'],
                'async' => 'async'
            ],
            [
                'priority' => true
            ]
        );
    }

    protected function addSmartDataProtectorScript(array $config): void
    {
        if($config['preconnectRessources']) {
            $this->assetCollector->addStyleSheet('usercentrics-preconnect-proxy', '//privacy-proxy.usercentrics.eu', [
                    'type' => '',
                    'rel' => 'preconnect'
                ],
                [
                    'priority' => true
                ]
            );
            $this->assetCollector->addStyleSheet('usercentrics-preload-bundle', '//privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js', [
                    'type' => '',
                    'rel' => 'preload',
                    'as' => 'script'
                ],
                [
                    'priority' => true
                ]
            );
        }

        $this->assetCollector->addJavaScript('usercentrics-bundle', 'https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js', [
                'type' => 'application/javascript',
            ],
            [
                'priority' => true
            ]
        );
    }

    protected function convertPriorityToBoolean(array $options): array
    {
        if (!empty($options['priority'])) {
            // make it a real boolean
            $options['priority'] = true;
        }
        return $options;
    }

    protected function getAttributesForUsercentrics(array $attributes, string $dataProcessingService): array
    {
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $dataProcessingService;
        return $attributes;
    }

    protected function getTypoScriptConfiguration(): ?array
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
        return isset($config['settingsId']) && is_string($config['settingsId']);
    }

    protected function isValidIdentifier(array $jsFile): bool
    {
        if (isset($jsFile['dataServiceProcessor'])) {
            trigger_error(
                'The setting "dataServiceProcessor" has been marked as deprecated. Use dataProcessingService instead.',
                E_USER_DEPRECATED
            );
            return isset($jsFile['dataServiceProcessor']) && is_string($jsFile['dataServiceProcessor']);
        }

        return isset($jsFile['dataProcessingService']) && is_string($jsFile['dataProcessingService']);
    }

    protected function getDataProcessingService(array $configuration): string
    {
        if (isset($configuration['dataServiceProcessor'])) {
            trigger_error(
                'The setting "dataServiceProcessor" has been marked as deprecated. Use dataProcessingService instead.',
                E_USER_DEPRECATED
            );
            return $configuration['dataServiceProcessor'];
        }

        return $configuration['dataProcessingService'];
    }
}