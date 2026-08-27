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
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\StringUtility;

final readonly class UsercentricsLibrary
{
    #[AsEventListener(identifier: 'usercentrics/UsercentricsLibrary')]
    public function __invoke(BeforeJavaScriptsRenderingEvent $event): void
    {
        if (!$event->isInline() || !$event->isPriority()) {
            return;
        }

        $request = $this->getRequest();
        if ($request === null || !$this->isFrontendRequest($request)) {
            return;
        }

        $config = $this->getSettings($request);
        if ($config === null) {
            return;
        }
        if ($config['settingsId'] === '') {
            throw new \InvalidArgumentException('Usercentrics ID not configured, please set plugin.tx_usercentrics.settingsId in your site settings', 1583774571);
        }

        $this->addUsercentricsScript($event, $config);
        $this->addConfiguredJsFiles($event, $config['jsFiles']);
        $this->addConfiguredInlineJavaScript($event, $config['jsInline']);
    }

    protected function addConfiguredInlineJavaScript(BeforeJavaScriptsRenderingEvent $event, array $jsInline): void
    {
        foreach ($jsInline as $inline) {
            if (!is_array($inline)) {
                throw new \InvalidArgumentException('No valid inline JS given, please check plugin.tx_usercentrics.jsInline in your site settings.', 1583774684);
            }
            if (!$this->isValidIdentifier($inline)) {
                throw new \InvalidArgumentException('No valid identifier given for inline JS, please check plugin.tx_usercentrics.jsInline in your site settings.', 1583774685);
            }
            $code = $inline['value'] ?? '';
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
            if (!is_array($jsFile) || !$this->isValidFile($jsFile)) {
                throw new \InvalidArgumentException('No valid file given, please check plugin.tx_usercentrics.jsFiles in your site settings.', 1787814745);
            }
            if (!$this->isValidIdentifier($jsFile)) {
                throw new \InvalidArgumentException('No valid identifier given for file, please check plugin.tx_usercentrics.jsFiles in your site settings.', 1787814751);
            }
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

    /**
     * @return array{settingsId: string, language: string, jsFiles: mixed[], jsInline: mixed[]}|null
     */
    protected function getSettings(ServerRequestInterface $request): ?array
    {
        /** @var SiteInterface|null $site */
        $site = $request->getAttribute('site');
        if ($site === null || $site instanceof NullSite) {
            return null;
        }

        $settings = $site->getSettings();
        $settingsId = $settings->get('plugin.tx_usercentrics.settingsId');
        if (!is_string($settingsId)) {
            return null;
        }

        $language = $settings->get('plugin.tx_usercentrics.language', 'current');
        if ($language === 'current') {
            /** @var SiteLanguage|null $siteLanguage */
            $siteLanguage = $request->getAttribute('language');
            $language = $siteLanguage?->getLocale()->getLanguageCode() ?? '';
        }

        return [
            'settingsId' => $settingsId,
            'language' => $language,
            'jsFiles' => (array)($settings->get('plugin.tx_usercentrics.jsFiles') ?? []),
            'jsInline' => (array)($settings->get('plugin.tx_usercentrics.jsInline') ?? []),
        ];
    }

    protected function isValidFile(array $jsFile): bool
    {
        return isset($jsFile['file']) && is_string($jsFile['file']) && $jsFile['file'] !== '';
    }

    protected function isValidIdentifier(array $configuration): bool
    {
        return isset($configuration['dataProcessingService'])
            && is_string($configuration['dataProcessingService'])
            && $configuration['dataProcessingService'] !== '';
    }

    protected function getDataProcessingService(array $configuration): string
    {
        return $configuration['dataProcessingService'];
    }

    /**
     * Deliberately not using ApplicationType::fromRequest(), which throws an exception for requests
     * that are neither FE nor BE, for example CLI requests.
     */
    private function isFrontendRequest(ServerRequestInterface $request): bool
    {
        $applicationType = $request->getAttribute('applicationType');
        return is_int($applicationType)
            && ($applicationType & SystemEnvironmentBuilder::REQUESTTYPE_FE) === SystemEnvironmentBuilder::REQUESTTYPE_FE;
    }

    private function getRequest(): ?ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        return $request instanceof ServerRequestInterface ? $request : null;
    }
}
