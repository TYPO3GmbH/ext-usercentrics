<?php
declare(strict_types = 1);

namespace T3G\AgencyPack\Usercentrics\Hooks;


use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    public function addLibrary(&$params, &$pagerenderer): void
    {
        $config = $this->getTypoScriptConfiguration();
        if ($config === null) {
            return;
        }
        if (!$this->isValidId($config)) {
            throw new \InvalidArgumentException('Usercentrics ID not configured, please set plugin.tx_usercentrics.id in your TypoScript configuration', 1583774571);
        }
        $this->addUserCentricsScript($config['id']);
        $this->addConfiguredJsFiles($config['jsFiles.']);
    }

    /**
     * @param $jsFiles
     */
    protected function addConfiguredJsFiles($jsFiles): void
    {
        foreach ($jsFiles ?? [] as $jsFile) {
            if(!$this->isValidFile($jsFile)) {
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

    /**
     * @param $id
     */
    protected function addUserCentricsScript($id): void
    {
        $this->assetCollector->addJavaScript('usercentrics', 'https://app.usercentrics.eu/latest/main.js', [
            'type' => 'application/javascript',
            'id' => $id
        ]);
    }

    /**
     * @param array $options
     * @return array
     */
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

    protected function getTypoScriptConfiguration(): ?array
    {
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        $ts = $tsfe->tmpl->setup;
        return $ts['plugin.']['tx_usercentrics.'] ?? null;
    }

    protected function isValidFile($jsFile): bool
    {
        return isset($jsFile['file']) && is_string($jsFile['file']);
    }

    /**
     * @param array $config
     * @return bool
     */
    protected function isValidId(array $config): bool
    {
        return isset($config['id']) && is_string($config['id']);
    }

    protected function isValidIdentifier($jsFile): bool
    {
        return isset($jsFile['identifier']) && is_string($jsFile['identifier']);
    }
}
