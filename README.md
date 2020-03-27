# Usercentrics Integration for TYPO3

This extension integrates Usercentrics (Compliance and Consent Management) into TYPO3.

## Installation and Configuration

1. Download and install the extension from the TER or via composer:

    * TER: https://extensions.typo3.org/extension/usercentrics
    * Composer: composer require t3g/usercentrics

2. Activate the extension in the extension manager

3. On every site where you want to use the extension, include the static TypoScript setup

4. Configure your Usercentrics ID by setting `plugin.tx_usercentrics.settingsId = <your-id>` in your TypoScript setup

5. Configure the JS Files to be handled by Usercentrics:

```
plugin.tx_usercentrics {
    settingsId = {$plugin.tx_usercentrics.settingsId}
    jsFiles {

        # Path to JS File (required)
        10.file = EXT:site/Resources/Public/JavaScriyt/MyScriptFile.js

        # Identifier to use in Usercentrics (required)
        10.dataProcessingService = My Data Processing Service

        20.file = secondFile.js
        20.dataProcessingService = My other Data Processing Service

        # attributes for the script tag (optional)
        20.attributes {
            async = async
        }

        # options for the TYPO3 AssetCollector
        # setting priority will render the script in the head instead of the footer section
        20.options {
            priority = 1
        }
    }

    jsInline {
      10.value (
        alert(123);
      )
      10.dataProcessingService = My Data Processing Service
      10.attributes {
        custom = attribute
      }
    }
}
```

Note that the configured identifiers need to match your Usercentrics configuration.

You do not need to set the `type` or `data-usercentrics` attributes for the script tags, the extension will handle that for you.

## Usage in Fluid

The extension comes with a custom view helper which can be used to add scripts via Fluid:

```html
<usercentrics:script dataProcessingService="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
<usercentrics:script dataProcessingService="identifier123">
   alert('hello world');
</usercentrics:script>
```

## Integrate Usercentrics with PHP

Since TYPO3 v10 the AssetCollector is part of the TYPO3 Core API. To add scripts managed by Usercentrics via PHP, replace your previous calls to the `PageRenderer` with `AssetCollector` calls and make sure to
set the attributes `type=text/plain` and `data-usercentrics=identifer`.

Example:

```
    $dataProcessingService = 'My Data Processing Service';
    $identifier = \TYPO3\CMS\Core\Utility\StringUtility::getUniqueId($dataProcessingService . '-');
    $file = 'EXT:site/Resources/Public/JavaScript/Scripts.js';
    $attributes = [
        'type' => 'text/plain',
        'data-usercentrics' => $dataProcessingService
    ];
    $assetCollector = GeneralUtility::makeInstance(\T3G\AgencyPack\Usercentrics\Page\AssetCollector::class);
    $assetCollector->addJavaScript($identifier, $file, $attributes);
```
