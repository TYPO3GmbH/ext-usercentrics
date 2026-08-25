# 13.0.0

Supports TYPO3 13.4 LTS and TYPO3 14, requires PHP 8.2 or higher.

## BREAKING
- Configuration moved from TypoScript to site sets and site settings. The static
  template "Usercentrics Integration" has been removed, include the site set
  `t3g/usercentrics` instead and move `plugin.tx_usercentrics` into your site
  settings. See the migration notes in the README.
- Support for TYPO3 11 and 12 as well as PHP 7.4, 8.0 and 8.1 has been dropped.
- The `identifier` argument of the `usercentrics:script` ViewHelper is now used as
  the AssetCollector identifier. It was a required argument before but had no
  effect. Calling the ViewHelper twice with the same identifier now injects the
  script only once, which is what the argument always documented.

## FEATURE
- Site set `t3g/usercentrics` with settings definitions for `settingsId`,
  `language`, `jsFiles` and `jsInline`.
- Custom site settings type `uc_file` including a backend editor component, so
  scripts can be maintained in the site settings module instead of YAML only.
- The `language` setting accepts the keyword `current`, which resolves to the
  language of the current site language.
- The `useNonce` argument of the ViewHelper is now applied. It maps to the
  AssetCollector option `useNonce` on TYPO3 v13 and to `csp` on TYPO3 v14, where
  `useNonce` is deprecated.

## BUGFIX
- Usercentrics is no longer rendered in backend requests.
- Sites that do not include the site set are left untouched instead of raising an
  exception.
- An empty `settingsId` is reported as a configuration error again, instead of
  rendering a script tag with an empty ID.
- Incomplete `jsFiles` and `jsInline` entries raise the documented
  `InvalidArgumentException` again instead of a PHP error.

## TASK
- Test suite covering the event listener, the ViewHelper, the settings type and the
  import map configurator, running against TYPO3 13.4 and 14.3.
- CI tests TYPO3 13.4 and 14.3 on PHP 8.2, 8.3 and 8.4, plus a lowest dependency run.
- Documentation migrated to `guides.xml`, configuration and usage chapters corrected.

# 10.0.0

## FEATURE
- [FEATURE] Add Viewhelper [TASK] Clean-up [TASK] Extend README [FEATURE] Allow usage of InlineJS bdffe04

## TASK
- [TASK] Adjust constraints 539d84c
- [TASK] Adjust changelog 32c7cc6
- [TASK] Add documentation (#8) 999c8bb
- [TASK] Rename options (#7) 0284538
- [TASK] Add icon f986278
- [TASK] Fix CGL 4d836bd
- [TASK] Remove 9.5 from matrix config 20159a9
- [TASK] Cover all the things f89a854
- [FEATURE] Add Viewhelper [TASK] Clean-up [TASK] Extend README [FEATURE] Allow usage of InlineJS bdffe04
- [TASK] Fix stuff 8b7fefd
- [TASK] Add Readme 7f8ee16
- [TASK] Add functionality 6708b4a
- [TASK] Initial commit 1e29146

## BUGFIX
- [BUGFIX] Update Github action workflow a90a2a6
- [BUGFIX] Rename view helper 01cd0c1
- [BUGFIX] Check existence of TSFE c43c720
- [BUGFIX] Make use of constant ae712c0
- [BUGFIX] Apply php-cs-fixer f912576
- [BUGFIX] Fix URL 9f20262

