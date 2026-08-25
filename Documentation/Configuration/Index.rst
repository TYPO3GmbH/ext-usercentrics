.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

Include The Static Template
===========================

After the extension has been installed, a new Site set called "Usercentrics" is made
available. After inclusion of the Site set, basic configuration of the extension is propagated to the website and
takes effect on all pages within the tree.

.. figure:: ../Images/Configuration/SiteSet.png
   :class: with-shadow
   :alt: Usercentrics Site set
   :width: 100%


Configure The Usercentrics Settings ID
======================================

The extension ships a Site set that allows to set the Usercentrics Settings ID that is used to load the
Usercentrics library associated with the account that holds further configuration.

In your settings.yaml, set the Usercentrics Settings ID as follows:

.. code-block:: yaml

   plugin.tx_usercentrics.settingsId = XXXXXXXX


Configure The Usercentrics Default language
===========================================

The extension ships a Site set that allows to set the default language to be used.
The language must be enabled in the Usercentrics account settings and provided as ISO 639-1 code.
The special keyword 'current' is replaced by the current site language automatically.

In your settings.yaml, set the default language as follows:

.. code-block:: yaml

   plugin.tx_usercentrics.language = en

