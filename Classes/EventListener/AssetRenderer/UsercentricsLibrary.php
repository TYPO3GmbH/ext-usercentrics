<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\EventListener\AssetRenderer;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\StringUtility;

final readonly class UsercentricsLibrary
{
    public function __invoke(BeforeJavaScriptsRenderingEvent $event): void
    {
        if (!$event->isInline() || !$event->isPriority() || 1 !== $this->getRequest()->getAttribute('applicationType')) {
            return;
        }

        $config = $this->getSettings();
        if ($config === null) {
            return;
        }
        if (!$this->isValidId($config)) {
            throw new \InvalidArgumentException('Usercentrics ID not configured, please set plugin.tx_usercentrics.settingsId in your settings', 1583774571);
        }

        $this->addUsercentricsScript($event, $config);
        $this->addConfiguredJsFiles($event, $config['jsFiles'] ?? []);
        $this->addConfiguredInlineJavaScript($event, $config['jsInline'] ?? []);
    }

    protected function addConfiguredInlineJavaScript(BeforeJavaScriptsRenderingEvent $event, array $jsInline): void
    {
        foreach ($jsInline as $inline) {
            $code = $inline['value'] ?? '';
            if (!$this->isValidIdentifier($inline)) {
                throw new \InvalidArgumentException('No valid identifier given for inline JS, please check your settings.', 1583774685);
            }
            $dataProcessingService = $this->getDataProcessingService($inline);
            $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
            $attributes = $this->getAttributesForUsercentrics($inline['attributes'] ?? [], $dataProcessingService);
            $options = $this->convertPriorityToBoolean($inline['options'] ?? []);
            $event->getAssetCollector()->addInlineJavaScript($identifier, $code, $attributes, $options);
        }
    }

    protected function addConfiguredJsFiles(BeforeJavaScriptsRenderingEvent $event, array $jsFiles): void
    {
        foreach ($jsFiles as $jsFile) {
            $dataProcessingService = $this->getDataProcessingService($jsFile);
            $identifier = StringUtility::getUniqueId($dataProcessingService . '-');
            $attributes = $this->getAttributesForUsercentrics($jsFile['attributes'] ?? [], $dataProcessingService);
            $options = $this->convertPriorityToBoolean($jsFile['options'] ?? []);
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

    protected function getSettings(): ?array
    {
        /** @var SiteInterface|null $site */
        $site = $this->getRequest()->getAttribute('site');

        if (null === $site || $site instanceof NullSite) {
            return null;
        }

        $config = [
            'settingsId' => $site->getSettings()->get('plugin.tx_usercentrics.settingsId'),
            'language' => $site->getSettings()->get('plugin.tx_usercentrics.language'),
            'jsFiles' => $site->getSettings()->get('plugin.tx_usercentrics.jsFiles'),
            'jsInline' => $site->getSettings()->get('plugin.tx_usercentrics.jsInline'),
        ];

        if ('current' === $config['language']) {
            /** @var SiteLanguage|null $siteLanguage */
            $siteLanguage = $this->getRequest()->getAttribute('language');
            $config['language'] = $siteLanguage?->getLocale()->getLanguageCode();
        }

        return $config;
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

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
