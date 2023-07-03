<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\EventListener\AssetRenderer;

use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class UsercentricsLibrary
{
    public function __invoke(BeforeJavaScriptsRenderingEvent $event): void
    {
        if ($event->isInline()) {
            return;
        }

        $config = $this->getTypoScriptConfiguration();
        if ($config === null) {
            return;
        }
        if (!$this->isValidId($config)) {
            throw new \InvalidArgumentException('Usercentrics ID not configured, please set plugin.tx_usercentrics.settingsId in your TypoScript configuration', 1583774571);
        }
        $this->addUsercentricsScript($event, $config);
        $this->addConfiguredJsFiles($event, $config['jsFiles.'] ?? []);
        $this->addConfiguredInlineJavaScript($event, $config['jsInline.'] ?? []);
    }

    protected function addConfiguredInlineJavaScript(BeforeJavaScriptsRenderingEvent $event, array $jsInline): void
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
            $event->getAssetCollector()->addInlineJavaScript($identifier, $code, $attributes, $options);
        }
    }

    protected function addConfiguredJsFiles(BeforeJavaScriptsRenderingEvent $event, array $jsFiles): void
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
            $event->getAssetCollector()->addJavaScript($identifier, $jsFile['file'], $attributes, $options);
        }
    }

    protected function addUsercentricsScript(BeforeJavaScriptsRenderingEvent $event, array $config): void
    {
        $event->getAssetCollector()->addJavaScript('usercentrics', 'https://app.usercentrics.eu/latest/main.js', [
            'type' => 'application/javascript',
            'id' => $config['settingsId'],
            'language' => $config['language'],
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
        return isset($jsFile['dataProcessingService']) && is_string($jsFile['dataProcessingService']);
    }

    protected function getDataProcessingService(array $configuration): string
    {
        return $configuration['dataProcessingService'];
    }
}
