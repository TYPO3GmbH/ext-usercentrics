.. include:: /Includes.rst.txt


.. _usage:

=====
Usage
=====

The foundation of the Usercentrics for TYPO3 Integration is TYPO3's `Asset Collector`_.
This extension offers multiple entry points to integrate external scripts and inline
scripts guarded by Usercentrics.

.. important::
   Each include requires a data processing service name which must match the name as
   configured in Usercentrics.


Site Settings
=============

In your settings, all scripts are configured within the :yaml:`plugin.tx_usercentrics`
namespace, which is divided into two sections.

External script files are configured within :yaml:`plugin.tx_usercentrics.jsFiles`,
whereas inline scripts are configured in :yaml:`plugin.tx_usercentrics.jsInline`.

The following keys are accepted per entry:

* :yaml:`dataProcessingService` (string) **mandatory** - the data processing service name as configured in Usercentrics
* :yaml:`file` (string) **mandatory for external files** - the path to the script file
* :yaml:`value` (string) **mandatory for inline scripts** - the JavaScript being rendered inline
* :yaml:`attributes` (array) - a key / value dictionary with attributes to be rendered in the :html:`<script>` tag
* :yaml:`options` (array) - options passed to the Asset Collector, see below

The following :yaml:`options` are accepted:

* :yaml:`priority` (bool) - defines whether an include is rendered in :html:`<head>` or at the bottom of :html:`<body>`

Example:

.. code-block:: yaml

   plugin:
     tx_usercentrics:
       jsFiles:
         -
           dataProcessingService: Google Analytics
           file: https://www.google-analytics.com/analytics.js
           attributes:
             async: async
           options:
             priority: 1

       jsInline:
         -
           dataProcessingService: Google Analytics
           value: "
             window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
             ga('create', 'UA-XXXXX-Y', 'auto');
             ga('send', 'pageview');
           "

.. note::
   Incomplete entries are rejected with an exception rather than being silently skipped.
   An entry without :yaml:`dataProcessingService`, and a :yaml:`jsFiles` entry without
   :yaml:`file`, are configuration errors.

.. warning::
   In case Usercentrics is included into an existing project, all usages of
   :typoscript:`page.includeJS` and alike must be checked and migrated.


Fluid
=====

Scripts that need consent may be loaded via Fluid if necessary, e.g. a content element
needs another third party library. The extension ships the ViewHelper
:php:`T3G\AgencyPack\Usercentrics\ViewHelpers\ScriptViewHelper`, which is registered
under the global namespace :html:`usercentrics`.

The following arguments are accepted:

* :html:`identifier` (string) **mandatory** - the Asset Collector identifier; reusing the same identifier injects the script only once
* :html:`dataProcessingService` (string) **mandatory** - the data processing service name as configured in Usercentrics
* :html:`src` (string) **mandatory for external files** - the path to the script file
* :html:`priority` (bool) - defines whether an include is rendered in :html:`<head>` or at the bottom of :html:`<body>`
* :html:`async`, :html:`crossorigin`, :html:`defer`, :html:`integrity`, :html:`nomodule`, :html:`nonce`, :html:`referrerpolicy` - rendered as attributes of the :html:`<script>` tag

If inline scripts are used, the JavaScript must be written as content of the ViewHelper.

Example:

.. code-block:: html

   <html xmlns:usercentrics="http://typo3.org/ns/T3G/AgencyPack/Usercentrics/ViewHelpers">
     <usercentrics:script identifier="foo" dataProcessingService="Google Analytics" src="https://www.google-analytics.com/analytics.js" />
     <usercentrics:script identifier="bar" dataProcessingService="Google Analytics">
        window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
        ga('create', 'UA-XXXXX-Y', 'auto');
        ga('send', 'pageview');
     </usercentrics:script>
   </html>

.. note::
   The :html:`type` attribute is always forced to :html:`text/plain` and the attribute
   :html:`data-usercentrics` is added automatically. Both are required for Usercentrics
   to be able to block the script until consent is given.


PHP
===

Since this extension uses TYPO3's `Asset Collector`_ it's fairly easy to use Usercentrics
within PHP code.

Example:

.. code-block:: php

   $dataProcessingService = 'Google Analytics';
   $assetCollector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\AssetCollector::class);
   $attributes = [
       'type' => 'text/plain',
       'data-usercentrics' => $dataProcessingService,
   ];

   $file = 'https://www.google-analytics.com/analytics.js';
   $assetCollector->addJavaScript('my-ext-analytics-file', $file, $attributes);

   $source = 'window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;'
       . 'ga(\'create\', \'UA-XXXXX-Y\', \'auto\');'
       . 'ga(\'send\', \'pageview\');';
   $assetCollector->addInlineJavaScript('my-ext-analytics-inline', $source, $attributes);

.. important::
   A different :php:`$identifier` must be used **per include** in the same script group.
   Reusing an identifier overwrites the previously registered asset.

.. warning::
   In case Usercentrics is included into an existing project, all usages of
   :php:`PageRenderer->addJsLibrary()` and alike must be checked and migrated.

.. _`Asset Collector`: https://docs.typo3.org/permalink/t3coreapi:assets
