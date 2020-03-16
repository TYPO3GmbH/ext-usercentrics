<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\ViewHelpers;

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

/**
 * Usercentrics Viewhelper
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <usercentrics:script identifier="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
 *    <usercentrics:script identifier="identifier123">
 *       alert('hello world');
 *    </usercentrics:script>
 */
class ScriptViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Asset\ScriptViewHelper
{
    public function render(): string
    {
        $identifier = $this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();
        $attributes['type'] = 'text/plain';
        $attributes['data-usercentrics'] = $identifier;
        $src = $this->tag->getAttribute('src');
        unset($attributes['src']);
        $options = [
            'priority' => $this->arguments['priority']
        ];
        if ($src !== null) {
            $this->assetCollector->addJavaScript($identifier, $src, $attributes, $options);
        } else {
            $content = (string)$this->renderChildren();
            if ($content !== '') {
                $this->assetCollector->addInlineJavaScript($identifier, $content, $attributes, $options);
            }
        }
        return '';
    }
}
