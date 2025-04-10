# Caution: Development State

This version does currently NOT provide a JS module for the settings editor.
Therefore, scripts can only be managed via Site settings in YAML format.
As this is due to time constraints, it will be added in the future.

# Usercentrics Integration for TYPO3

This extension integrates Usercentrics (Compliance and Consent Management) into TYPO3.

## Installation and Configuration

1. Download and install the extension from the TER or via composer:

    * TER: https://extensions.typo3.org/extension/usercentrics
    * Composer: composer require t3g/usercentrics

2. Activate the extension in the extension manager

3. On every site where you want to use the extension, include the Site set

4. Configure your Usercentrics ID by setting `plugin.tx_usercentrics.settingsId = <your-id>` in your Site settings

5. Configure the JS Files to be handled by Usercentrics:

```
plugin:
  tx_usercentrics:
    settingsId: XXXXX
    jsFiles:
      -
        # Path to JS File (required)
        file: 'EXT:site/Resources/Public/JavaScriyt/MyScriptFile.js'

        # Identifier to use in Usercentrics (required)
        dataProcessingService: My Data Processing Service
      -
        file: secondFile.js
        dataProcessingService: My other Data Processing Service

        # attributes for the script tag (optional)
        attributes:
          async: async

        # options for the TYPO3 AssetCollector
        # setting priority will render the script in the head instead of the footer section
        options:
          priority: 1

    jsInline:
      -
        value: alert(123);
        dataProcessingService: My Data Processing Service
        attributes:
          custom: attribute
```

Note that the configured identifiers need to match your Usercentrics configuration.

You do not need to set the `type` or `data-usercentrics` attributes for the script tags, the extension will handle that for you.

## Usage in Fluid

The extension comes with a custom view helper which can be used to add scripts via Fluid:

```html
<usercentrics:script identifier="foo" dataProcessingService="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
<usercentrics:script identifier="bar" dataProcessingService="identifier123">
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
    $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
    $assetCollector->addJavaScript($identifier, $file, $attributes);
```
