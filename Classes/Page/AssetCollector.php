<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Page;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * The Asset Collector is responsible for keeping track of
 * - everything within <script> tags: javascript files and inline javascript code
 *
 * The goal of the asset collector is to:
 * - utilize a single "runtime-based" store for adding assets of certain kinds that are added to the output
 * - allow to deal with assets from non-cacheable plugins and cacheable content in the Frontend
 * - reduce the "power" and flexibility (I'd say it's a burden) of the "god class" PageRenderer.
 * - reduce the burden of storing everything in PageRenderer
 *
 * Note on the implementation:
 * - We use a Singleton to make use of the AssetCollector throughout Frontend process (similar to PageRenderer).
 * - Although this is not optimal, I don't see any other way to do so in the current code.
 *
 * https://developer.wordpress.org/reference/functions/wp_enqueue_style/
 */
class AssetCollector implements SingletonInterface
{
    /**
     * @var array
     */
    protected $javaScripts = [];

    /**
     * @var array
     */
    protected $inlineJavaScripts = [];

    public function addJavaScript(string $identifier, string $source, array $attributes, array $options = []): self
    {
        $existingAttributes = $this->javaScripts[$identifier]['attributes'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAttributes, $attributes);
        $existingOptions = $this->javaScripts[$identifier]['options'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingOptions, $options);
        $this->javaScripts[$identifier] = [
            'source' => $source,
            'attributes' => $existingAttributes,
            'options' => $existingOptions
        ];
        return $this;
    }

    public function addInlineJavaScript(string $identifier, string $source, array $attributes, array $options = []): self
    {
        $existingAttributes = $this->inlineJavaScripts[$identifier]['attributes'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAttributes, $attributes);
        $existingOptions = $this->inlineJavaScripts[$identifier]['options'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingOptions, $options);
        $this->inlineJavaScripts[$identifier] = [
            'source' => $source,
            'attributes' => $existingAttributes,
            'options' => $existingOptions
        ];
        return $this;
    }

    public function removeJavaScript(string $identifier): self
    {
        unset($this->javaScripts[$identifier]);
        return $this;
    }

    public function removeInlineJavaScript(string $identifier): self
    {
        unset($this->inlineJavaScripts[$identifier]);
        return $this;
    }

    public function getJavaScripts(): array
    {
        return $this->javaScripts;
    }

    public function getInlineJavaScripts(): array
    {
        return $this->inlineJavaScripts;
    }
}
