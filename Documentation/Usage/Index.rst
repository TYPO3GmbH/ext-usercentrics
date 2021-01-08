.. include:: ../Includes.txt


.. _usage:

=====
Usage
=====

The foundation of the Usercentrics for TYPO3 Integration is the TYPO3's `Asset Collector`_.
This extensions offers multiple entry points to integrate external scripts and inline scripts guarded by Usercentrics.

.. important::
   Each include requires an identifier which must match the data processing service name as configured in Usercentrics.


TypoScript
==========

In TypoScript, all scripts are configured within the :typoscript:`plugin.tx_usercentrics` namespace, which is divided
into two sections.

External script files are configured within :typoscript:`plugin.tx_usercentrics.jsFiles`, whereas inline scripts are
configured in :typoscript:`plugin.tx_usercentrics.jsInline`.

The following arguments are accepted:

* :typoscript:`dataProcessingService` (string) **mandatory** - the data processing service name as configured in Usercentrics
* :typoscript:`file` (string) **mandatory for external files** - the path to the script file
* :typoscript:`value` (string) **mandatory for inline scripts** - the JavaScript being rendered inline
* :typoscript:`attributes` (array) - a key / value dictionary with attributes to be rendered in the :html:`<script>` tag
* :typoscript:`priority` (bool) - defines whether an include is rendered in :html:`<head>` or at the bottom of :html:`<body>`

.. warning::
   In case the Usercentrics Release v10.0.1 or older is in use, there is no :typoscript:`dataProcessingService` argument. Use :typoscript:`dataServiceProcessor` instead.
   
Example:

.. code-block:: typoscript

   plugin.tx_usercentrics {
       jsFiles {
           10 {
               dataProcessingService = Google Analytics
               file = https://www.google-analytics.com/analytics.js
               attributes {
                   async = async
               }
           }
       }

       jsInline {
           10 {
               dataProcessingService = Google Analytics
               value (
                   window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
                   ga('create', 'UA-XXXXX-Y', 'auto');
                   ga('send', 'pageview');
               )
           }
       }
   }

.. warning::
   In case Usercentrics is included into an existing project, all usages of :typoscript:`page.includeJS` and alike must
   be checked and migrated.


Fluid
=====

Scripts that need consent may be loaded via Fluid if necessary, e.g a content element needs another third party
library. The extension ships the ViewHelper :php:`T3G\AgencyPack\Usercentrics\ViewHelpers\ScriptViewHelper` whose
namespace may need to be imported.

The following arguments are accepted:

* :typoscript:`src` (string) **mandatory for external files** - the path to the script file
* :typoscript:`dataProcessingService` (string) **mandatory** - the data processing service name as configured in Usercentrics
* :typoscript:`attributes` (array) - a key / value dictionary with attributes to be rendered in the :html:`<script>` tag
* :typoscript:`priority` (bool) - defines whether an include is rendered in :html:`<head>` or at the bottom of :html:`<body>`

If inline scripts are used, the JavaScript must be written as content of the ViewHelper.

Example:

.. code-block:: html

   <html xmlns:usercentrics="http://typo3.org/ns/T3G/AgencyPack/Usercentrics/ViewHelpers">
     <usercentrics:script dataProcessingService="Google Analytics" src="https://www.google-analytics.com/analytics.js" />
     <usercentrics:script dataProcessingService="Google Analytics">
        window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
        ga('create', 'UA-XXXXX-Y', 'auto');
        ga('send', 'pageview');
     </usercentrics:script>
   </html>


PHP
===

Since this extension uses TYPO3's `Asset Collector`_ it's fairly easy to use Usercentrics within PHP code.

Example:

.. code-block:: php

   $dataProcessingService = 'Google Analytics';
   $assetCollector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\AssetCollector::class);

   $identifier = \TYPO3\CMS\Core\Utility\StringUtility::getUniqueId($dataProcessingService . '-');
   $file = 'https://www.google-analytics.com/analytics.js';
   $attributes = [
       'type' => 'text/plain',
       'data-usercentrics' => $dataProcessingService
   ];
   $assetCollector->addJavaScript($identifier, $file, $attributes);

   $identifier = \TYPO3\CMS\Core\Utility\StringUtility::getUniqueId($dataProcessingService . '-');
   $source = 'window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;'
       . 'ga(\'create\', \'UA-XXXXX-Y\', \'auto\');'
       . 'ga(\'send\', \'pageview\');';
   $attributes = [
       'type' => 'text/plain',
       'data-usercentrics' => $consentName
   ];
   $assetCollector->addInlineJavaScript($identifier, $file, $attributes);

.. important::
   A different internal :php:`$identifier` must be used **per include** in the same script group.

.. warning::
   In case Usercentrics is included into an existing project, all usages of :php:`PageRenderer->addJsLibrary()` and
   alike must be checked and migrated.

.. _`Asset Collector`: https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.3/Feature-90522-IntroduceAssetCollector.html
