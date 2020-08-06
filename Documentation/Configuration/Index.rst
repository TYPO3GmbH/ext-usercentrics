.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

Include The Static Template
===========================

After the extension has been installed, a new static TypoScript template called "Usercentrics Integration" is made
available. After inclusion of the static template, basic configuration of the extension is propagated to the website and
takes effect on all pages within the tree where the TypoScript template record is stored.

.. figure:: ../Images/Configuration/TypoScriptTemplate.png
   :class: with-shadow
   :alt: Static Usercentrics template
   :width: 100%


Configure The Usercentrics Settings ID
======================================

The extension ships a TypoScript constant that allows to set the Usercentrics Settings ID that is used to load the
Usercentrics library associated with the account that holds further configuration.

In TypoScript, set the constant as follows:

.. code-block:: typoscript

   plugin.tx_usercentrics.settingsId = XXXXXXXX


Configure The Usercentrics Default language
===========================================

The extension ships a TypoScript constant that allows to set the default language to be used.
The language must be enabled in the Usercentrics account settings and provided as ISO 639-1 code.

In TypoScript, set the constant as follows:

.. code-block:: typoscript

   plugin.tx_usercentrics.language = en

