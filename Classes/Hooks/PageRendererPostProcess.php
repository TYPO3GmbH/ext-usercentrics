<?php
declare(strict_types = 1);

namespace T3G\AgencyPack\Usercentrics\Hooks;


use T3G\AgencyPack\Usercentrics\Page\AssetRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRendererPostProcess
{
    public function render($params)
    {
        // Use AssetRenderer to inject all JavaScripts and CSS files
        $assetRenderer = GeneralUtility::makeInstance(AssetRenderer::class);
        $jsFiles = &$params['jsFiles'];
        $jsFiles .= $assetRenderer->renderJavaScript(true);
        $jsFooterFiles = &$params['jsFooterFiles'];
        $jsFooterFiles .= $assetRenderer->renderJavaScript();
    }
}
