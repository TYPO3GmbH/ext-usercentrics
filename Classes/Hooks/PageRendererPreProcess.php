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
        $this->assetCollector->addJavaScript('usercentrics', 'https://app.usercentrics.eu/latest/main.js', [
            'type' => 'application/javascript',
            'id' => 'getMeFromTypoScript'
        ]);
        // do something with the JS Files stuff from TypoScript
    }
}
