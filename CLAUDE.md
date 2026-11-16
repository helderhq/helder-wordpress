# helder-wordpress: werkafspraken

WordPress-plugin "Helder" (GPLv2 of later). Fase 1: website-chat. WooCommerce volgt later als module
in deze plugin, niet als aparte plugin.

- **Besluiten, OpenSpec en Linear staan in de monorepo `jackyraimond/helder`:** ADR-35-addendum en
  ADR-30-addendum van 13 november 2026, OpenSpec `wordpress-plugin`. Noem het HEL-issue in elke PR
  (HEL-537), niet in de PR-titel.
- **Helder-kant** (koppelroute, `/api/wordpress/*`, `tokenUrl` in de widget) wordt in de monorepo
  gebouwd. Deze repo praat alleen met die publieke API.
- **Geen geheimen in de code.** Elk geheim komt per site uit de koppeling en staat in de optie
  `helder_koppeling` (niet autoloaded). Nooit naar de browser, nooit loggen.
- **WordPress.org-richtlijnen:** externe dienst beschreven in `readme.txt`, niets naar Helder vóór
  het koppelen, geen gebundelde of externe JavaScript behalve het laadscript van Helder.
- **Code en strings in het Engels** (WordPress-conventie, vertalingen via translate.wordpress.org
  en `languages/`). Commentaar mag Engels. Klassen en methodes houden de Nederlandse namen uit de
  monorepo (koppeling, klanttoken, opslag).
- **Checks:** `composer install && composer lint` (PHPCS met WordPress Coding Standards) en
  `composer test`. CI draait ook Plugin Check.
- **Uitgave:** versie in `helder.php`, `readme.txt` (Stable tag) en `HELDER_VERSION` gelijk
  ophogen, tag `vX.Y.Z`; de workflow maakt de zip en zet hem in de SVN van WordPress.org zodra
  `SVN_USERNAME` en `SVN_PASSWORD` als secrets bestaan.
