# Usercentrics Integration for TYPO3

This extension integrates Usercentrics (Compliance and Consent Management) into TYPO3.

[![CI](https://github.com/TYPO3GmbH/ext-usercentrics/actions/workflows/ci.yml/badge.svg)](https://github.com/TYPO3GmbH/ext-usercentrics/actions/workflows/ci.yml)

## Compatibility

| Extension | TYPO3        | PHP    |
|-----------|--------------|--------|
| 13.x      | 13.4 LTS, 14 | >= 8.2 |
| 12.x      | 11.5, 12.4   | >= 7.4 |
| 10.x      | 10.4         | >= 7.2 |

Starting with version 13.0.0 the extension is configured through
[site sets and site settings](https://docs.typo3.org/permalink/t3coreapi:site-sets)
instead of TypoScript. See the migration notes below.

## Installation and Configuration

1. Install the extension from the TER or via composer:

    * TER: https://extensions.typo3.org/extension/usercentrics
    * Composer: `composer require t3g/usercentrics`

2. On every site where you want to use the extension, include the site set
   `t3g/usercentrics`, either in the backend under
   *Site Management > Sites* or in the site's `config.yaml`:

   ```yaml
   dependencies:
     - t3g/usercentrics
   ```

3. Configure your Usercentrics ID and the scripts to be handled by Usercentrics
   in the site settings, either in the backend under
   *Site Management > Sites > [your site] > Settings* or in `settings.yaml`:

   ```yaml
   plugin:
     tx_usercentrics:
       # Your Usercentrics Settings ID (required once the set is included)
       settingsId: XXXXX

       # ISO 639-1 code, or "current" to follow the site language (default)
       language: current

       jsFiles:
         -
           # Path to the JS file (required)
           file: 'EXT:site/Resources/Public/JavaScript/MyScriptFile.js'
           # Data processing service name as configured in Usercentrics (required)
           dataProcessingService: My Data Processing Service
         -
           file: secondFile.js
           dataProcessingService: My other Data Processing Service
           # Attributes for the script tag (optional)
           attributes:
             async: async
           # Options for the TYPO3 AssetCollector (optional).
           # priority renders the script in the head instead of the footer.
           options:
             priority: 1

       jsInline:
         -
           value: alert(123);
           dataProcessingService: My Data Processing Service
           attributes:
             custom: attribute
   ```

Note that the configured data processing service names need to match your Usercentrics
configuration.

You do not need to set the `type` or `data-usercentrics` attributes for the script tags,
the extension will handle that for you.

## Usage in Fluid

The extension comes with a custom ViewHelper which can be used to add scripts via Fluid.
The namespace `usercentrics` is registered globally:

```html
<usercentrics:script identifier="foo" dataProcessingService="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
<usercentrics:script identifier="bar" dataProcessingService="identifier123">
   alert('hello world');
</usercentrics:script>
```

The `identifier` is the AssetCollector identifier. Using the same identifier twice
injects the script only once.

## Integrate Usercentrics with PHP

To add scripts managed by Usercentrics via PHP, replace your previous calls to the
`PageRenderer` with `AssetCollector` calls and make sure to set the attributes
`type=text/plain` and `data-usercentrics=<data processing service>`.

Example:

```php
$dataProcessingService = 'My Data Processing Service';
$attributes = [
    'type' => 'text/plain',
    'data-usercentrics' => $dataProcessingService,
];
$assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
$assetCollector->addJavaScript(
    'my-ext-scripts',
    'EXT:site/Resources/Public/JavaScript/Scripts.js',
    $attributes
);
```

## Migrating from 12.x to 13.x

The TypoScript static template has been replaced by a site set. TypoScript
configuration under `plugin.tx_usercentrics` is no longer read.

1. Remove the static template *Usercentrics Integration* from your TypoScript templates.
2. Include the site set `t3g/usercentrics` for every site that uses the extension.
3. Move your `plugin.tx_usercentrics` TypoScript into the site settings. The structure
   is the same, except that the numbered TypoScript keys (`10`, `20`, …) become
   YAML list entries.

Before (TypoScript):

```
plugin.tx_usercentrics {
    settingsId = XXXXX
    jsFiles {
        10.file = EXT:site/Resources/Public/JavaScript/MyScriptFile.js
        10.dataProcessingService = My Data Processing Service
    }
}
```

After (`settings.yaml`):

```yaml
plugin:
  tx_usercentrics:
    settingsId: XXXXX
    jsFiles:
      -
        file: 'EXT:site/Resources/Public/JavaScript/MyScriptFile.js'
        dataProcessingService: My Data Processing Service
```

## Development

A DDEV environment is included. It declares the container only, the installation
itself is not part of this repository:

```bash
ddev start
ddev composer install
ddev restart   # DDEV writes config/system/additional.php once TYPO3 is installed
ddev exec bash -c 'TYPO3_DB_DRIVER=mysqli TYPO3_DB_HOST=db TYPO3_DB_DBNAME=db TYPO3_DB_USERNAME=db TYPO3_DB_PASSWORD=db TYPO3_SETUP_ADMIN_USERNAME=admin TYPO3_SETUP_ADMIN_EMAIL=admin@example.com TYPO3_SETUP_ADMIN_PASSWORD=Usercentrics.Dev.1 TYPO3_SETUP_CREATE_SITE=https://ext-usercentrics.ddev.site/ typo3 setup --no-interaction --server-type=apache'
ddev exec typo3 extension:setup
```

Two things about that command: the admin password has to satisfy TYPO3's policy,
so it needs an upper case character, a digit and a special character, and the
whole thing has to stay on one line, because `ddev exec` joins its arguments
before handing them to the shell in the container.

The `ddev restart` is needed once: DDEV decides whether to write
`config/system/additional.php` by looking for an installed TYPO3, and that check
runs before the dependencies exist on a fresh clone. That file is generated and
stays out of the repository, like everything else below `config/`.

To look at the extension rendering, point the site at the demo set shipped in
`Build/usercentrics_demo`. It is a development-only extension providing the page
TypoScript and a Fluid template that uses the ViewHelper, and it is never
released, because `Build/` is export-ignored.

1.  In `config/sites/main/config.yaml`, replace the `dependencies: {  }` that
    `--create-site` wrote with:

    ```yaml
    dependencies:
      - t3g/usercentrics-demo
    ```

2.  Delete `config/sites/main/setup.typoscript`. `--create-site` writes a welcome
    page into it, and site TypoScript is applied after the site sets, so it would
    override the demo rendering.

3.  Configure the extension in `config/sites/main/settings.yaml`, as documented
    further up, and run `ddev exec typo3 cache:flush`.

The page then registers scripts both ways the extension offers: two through the
site settings and three through the ViewHelper. View the page source and check
that each one carries `type="text/plain"` and a `data-usercentrics` attribute.

| URL | What it shows |
|---|---|
| https://ext-usercentrics.ddev.site/ | Demo page, English |
| https://ext-usercentrics.ddev.site/de/ | Same page in German, once a second language is configured, to check the `current` language |
| https://ext-usercentrics.ddev.site/typo3 | Backend |

### Tests and checks

```bash
ddev exec composer t3g:test            # lint, unit and functional tests
ddev exec composer t3g:test:php:unit
ddev exec composer t3g:test:php:functional
ddev exec composer t3g:cgl             # coding guidelines check
ddev exec composer t3g:cgl:fix         # coding guidelines fix
```

The functional suite does not use the demo environment above. It builds its own
instance, writes its own site configuration and renders through it.

The functional suite needs a database. Inside DDEV it uses the MariaDB container
via the credentials in `.ddev/config.yaml`; CI runs it on SQLite by setting
`typo3DatabaseDriver=pdo_sqlite`.

### Switching the TYPO3 version

```bash
ddev composer update --with "typo3/cms-core:^13.4"
ddev composer dump-autoload
ddev exec composer t3g:test
```

The `dump-autoload` is required: switching majors in place leaves a class alias
loader generated for the other one, and PHP then fails before the autoloader runs.
