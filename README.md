# Usercentrics Integration for TYPO3

This extension integrates Usercentrics (Compliance and Consent Management) into TYPO3.

## Installation and Configuration

1. Download and install the extension from the TER or via composer:

    TER: https://extensions.typo3.org/...
    Composer: composer require t3g/usercentrics

2. Activate the extension in the extension manager

3. On every site where you want to use the extension, include the static TypoScript setup

4. Configure your Usercentrics ID by setting `plugin.tx_usercentrics.id = <your-id>` in your TypoScript setup

5. Configure the JS Files to be handled by Usercentrics:

```
plugin.tx_usercentrics {
    id = <your-id>
    jsFiles {

        # Path to JS File (required)
        10.file = EXT:site/Resources/Public/JavaScriyt/MyScriptFile.js

        # Identifier to use in Usercentrics (required)
        10.identifier = myscript

        20.file = secondFile.js
        20.identifier = anotherFile

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
}
```

Note that the configured identifiers need to match your Usercentrics configuration.

You do not need to set the `type` or `data-usercentrics` attributes for the script tags, the extension will handle that for you.
