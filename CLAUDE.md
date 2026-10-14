# wamesk/laravel-nova-action-toolbar — akcie ako tlačidlá, ikony alebo ⋯

Samostatný repozitár v `~/WAME/PACKAGES/laravel-nova-action-toolbar`. Vznikol v aplikácii
`wame-reality` (`wamesk/laravel-nova-action-toolbar`, commit `70553eb`) a odtiaľ sa presunul.

Balík (namespace `Wame\LaravelNovaActionToolbar\`) dovolí pri každej Nova akcii zvoliť, či je **tlačidlo**, **ikona** alebo položka
v **⋯** — nad tabuľkou (`index`), v riadku (`inline`) a na detaile (`detail`). To isté platí pre
vstavané ovládacie prvky Novy (náhľad, replikovať, zobraziť, upraviť, zmazať, obnoviť,
natrvalo zmazať, prihlásiť sa ako). Vetva **`4.x` je Nova 4, `main` je Nova 5** — PHP API
je na oboch rovnaké, líši sa len frontend a `dist/`. Teamwork M2.6.8 (45504781).
Používateľská dokumentácia je v `README.md` (EN).

## Čo sa nesmie pokaziť

- **Akcia bez nastavenia nemá kľúč `toolbar`.** Makrá zapisujú len do `meta['toolbar']`;
  frontend bez neho kreslí presne to, čo Nova (všetko v ⋯, vstavané prvky na svojom mieste).
- **Nič dvakrát.** Akcia ako tlačidlo zmizne z ⋯ aj z Novinho výberu pre označené riadky
  (`ActionSelector`). Vstavaný prvok presunutý inam Nova už nekreslí (skrytý cez CSS).
- **Umiestnenie nie je autorizácia.** Tlačidlo je iná kresba tej istej akcie: Nova posiela na
  stránku len akcie, ktoré používateľ vidí (`canSee`, `runAction`), a tlačidlo volá ten istý
  endpoint. `canRun` / `canSee` sú v Nove **settery** — nič v API balíka ich nesmie
  napodobniť tak, aby vyzeralo ako kontrola.

## Ako to funguje (Nova 4.35)

V `Nova.booting` registruje päť komponentov **pod Novinými menami**: `ActionDropdown`,
`InlineActionDropdown`, `DetailActionDropdown`, `ActionSelector`, `CreateResourceButton`.
Nova registruje svoje komponenty až potom a obsadené meno preskočí (`Nova.component()`
kontroluje `isNil`), takže sa žiaden súbor Novy nemení.

- **Spúšťanie akcie je Novin kód.** `@` v `nova.mix.js` ukazuje na
  `vendor/laravel/nova/resources/js`, takže `useActions`, `Deletable` a pôvodné
  `ActionSelector` / `CreateResourceButton` sa **kompilujú z nainštalovanej Novy**. Po
  upgrade Novy treba **prebuildovať** a porovnať `DetailActionDropdown.vue` s Novinou verziou
  (metódy mazania sú skopírované bez zmeny, sú označené komentárom).
- **`@/mixins$` je shim** (`support/novaMixins.js`): `useActions` z indexu potrebuje len
  `Errors`; celý index + `form-backend-validation` by pribalil axios a všetky mixiny.
  Žiadny globálny `_` (lodash) — Nova ho nevystavuje a ProvidePlugin by pribalil celý lodash.
- **Hlavička zoznamu žije v `CreateResourceButton`.** Nova 4 kreslí `ActionDropdown` v hlavičke
  len pre standalone akcie, hromadné tlačidlo však musí byť viditeľné (vypnuté) aj bez výberu.
  Obal tlačidla „Vytvoriť“ si stav (všetky akcie, výber, počet) prečíta z Index view
  (`support/owner.js`, `$parent` až po komponent s `allActions` + `selectedResources`) a Novin
  `ActionDropdown` v hlavičke sa vtedy nevykreslí. **Keď Nova hlavičku nekreslí** (používateľ
  nesmie vytvárať a nie je žiadna standalone akcia), hromadná akcia ostane v `ActionSelector`
  — `ownerHasHeader()` musí zodpovedať podmienke v Novinom `Index.vue`, inak akcia zmizne úplne.
- **Vstavané prvky v riadku a na detaile** (zobraziť / upraviť / zmazať / obnoviť) kreslí
  Nova sama vedľa nášho komponentu. Presunutý prvok sa skryje cez `data-nat-hide` na
  spoločnom rodičovi + CSS podľa `dusk` atribútu a nové tlačidlo či položka **klikne na
  skrytý Novin prvok** (`support/useNativeControls.js`). Nekopírujeme `ResourceTableRow` —
  v `wame-reality` ho aj tak nahrádza `outl1ne/nova-sortable` (triedy `o1-…`), a `dusk`
  atribúty sú v oboch rovnaké.
- **Ikony:** Nova 4 má Heroicons v1 ako globálne komponenty (`HeroiconsOutlineDocumentAdd`).
  Názvy v2 (`document-plus`, `arrow-down-tray`) preloží `support/icons.js` na v1 dvojča, aby
  rovnaký PHP kód fungoval aj v Nove 5. Neznámy názov = žiadna ikona (+ `console.warn` v debug).
  Vlastné `<svg>` čistí `Icon::sanitize()` (whitelist elementov a atribútov).
- **Úzka obrazovka:** `usePriorityPlus` meria `scrollWidth` panelu a skrýva tlačidlá
  (`hidden`) od najnižšej `priority`, pri zhode od posledného. Aby sa panel vôbec mohol
  zúžiť, CSS cez `:has()` dá Novinmu `.ml-auto` kontajneru `flex: 1; min-width: 0`.
- **Rozloženie** `grouped` (predvolené, používateľ si vybral 2026-10-14) / `separate` je v
  `config/nova-action-toolbar.php`. ⋯ v riadku ostáva **na začiatku** ako v Nove, v hlavičke a
  na detaile je na konci.

## CSS

Novin `app.css` je purgnutý Tailwind — triedy, ktoré Nova sama nepoužíva, **neexistujú**.
Všetko je v `resources/css/toolbar.css` s prefixom `nat-` a číta Novine premenné
(`--colors-primary-500`, `--colors-gray-*`). Značková farba **len `primary-500`** (400/600 nesú
alfu). Svetlá/tmavá téma = trieda `.dark` na `<html>`. Vzhľad vychádza z prototypu
`docs/wireframes/action-toolbar/prototype.html` (gitignored, schválený 2026-10-14).

## Build a testy

```bash
composer install          # Nova z nova.laravel.com (auth.json), Testbench, Pest
npm ci && npm run production   # dist/ sa commituje — build proti Nove z vendor/
./vendor/bin/pest
```

`nova.mix.js` hľadá Novu v `NOVA_PATH`, potom vo vlastnom `vendor/`, potom vo `vendor/`
aplikácie, ktorá má balík v podadresári. `dist/` na vetve `4.x` musí byť z Novy 4, na `main`
z Novy 5 — iná major verzia tam nesmie skončiť. `webpack` je pripnutý na **5.89.0**
(laravel-mix 6).

V `wame-reality` sú navyše `tests/Feature/Contract/ContractActionToolbarTest.php` a Cypress
`cypress/e2e/contract.cy.js`.
