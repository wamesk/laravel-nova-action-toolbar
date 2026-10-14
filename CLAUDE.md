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

## Ako to funguje (Nova 5)

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
- **Hlavička zoznamu žije v `CreateResourceButton`.** Nova kreslí `ActionDropdown` v hlavičke
  len pre standalone akcie, hromadné tlačidlo však musí byť viditeľné (vypnuté) aj bez výberu.
  Obal tlačidla „Vytvoriť“ si stav (všetky akcie, výber, počet) prečíta z Index view
  (`support/owner.js`, `$parent` až po komponent s `allActions` + `selectedResources`) a Novin
  `ActionDropdown` v hlavičke sa vtedy nevykreslí. **Keď Nova hlavičku nekreslí** (používateľ
  nesmie vytvárať a nie je žiadna standalone akcia), hromadná akcia ostane v `ActionSelector`
  — `ownerHasHeader()` musí zodpovedať podmienke v Novinom `Index.vue`, inak akcia zmizne úplne.
  Novšie Novy 5 na to majú vlastný `hasResourceActionControls` (vytvoriť len na čistom zozname,
  pripojiť len na relácii); Nova 5.6.3 ho ešte nemá a používa podmienku z Novy 4 — preto
  `ownerHasHeader()` vezme `hasResourceActionControls`, ak existuje, inak starý vzorec.
- **Vstavané prvky v riadku a na detaile** (zobraziť / upraviť / zmazať / obnoviť) kreslí
  Nova sama vedľa nášho komponentu. Presunutý prvok sa skryje cez `data-nat-hide` na
  spoločnom rodičovi + CSS podľa `dusk` atribútu a nové tlačidlo či položka **klikne na
  skrytý Novin prvok** (`support/useNativeControls.js`). Nekopírujeme `ResourceTableRow` —
  v `wame-reality` ho aj tak nahrádza `outl1ne/nova-sortable` (triedy `o1-…`), a `dusk`
  atribúty sú v oboch rovnaké.
- **Ikony:** Nova 5 kreslí Heroicons v2 cez `Icon` z `laravel-nova-ui` — to je globálny
  `window.LaravelNovaUi`, v `nova.mix.js` vedený ako `externals`, takže sa nepribalí. Berie
  názvy v2 (`document-plus`, `arrow-down-tray`) aj v1 aliasy. Neznámy názov = žiadna ikona
  (+ `console.warn` v debug).
  Vlastné `<svg>` čistí `Icon::sanitize()` (whitelist elementov a atribútov).
- **Úzka obrazovka:** `usePriorityPlus` meria `scrollWidth` panelu a skrýva tlačidlá
  (`hidden`) od najnižšej `priority`, pri zhode od posledného. Aby sa panel vôbec mohol
  zúžiť, CSS cez `:has()` dá Novinmu `.ml-auto` kontajneru `flex: 0 1 auto; min-width: 0`.
  Keď sú už všetky tlačidlá v ⋯ a zvyšok (⋯, „Vytvoriť“, Novine ikony) sa stále nezmestí,
  dostane `.ml-auto` inline `min-width` presne na ten zvyšok — inak by „Vytvoriť“ na
  zozname širokom ako mobil vyšlo mimo obrazovky (opravené 2026-10-14).
- **Akcia s poľami v riadku si formulár načíta pre ten riadok** (`support/useRowActionForm.js`,
  4.0.1 / 5.0.1). Nova pečie akcie riadku aj s poľami do odpovede indexu, ktorá nemenuje žiadny
  záznam — pole závislé od záznamu (výber faktúr, predvyplnená suma) by bolo prázdne a menu
  riadku sa servera znova nespýta. Pred otvorením sa preto pýta `GET …/actions` s
  `resourceId`, `editing=true`, `editMode=create` ako detail (+ `via*`), **bez `display`**, aby
  sa našla aj akcia len pre riadok (`onlyInline()`). Novin `useActions` dostane props s akciou
  z odpovede (`reactive` + `toRefs`). Len `context === 'inline'` a len akcia s `fields` —
  inak žiaden request. Chyba = pečená akcia + `Nova.error`; 401/403/419 rieši Novin axios a
  formulár sa neotvorí. Bez `async`/`await` a spreadu — Babel by pribalil generator runtime
  a helpery do každého modulu.
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

Port na Novu 5 bol overený v prehliadači na Nove 5.6.3 (`laravel-skeleton`), `dist/` je
zostavený zo zdrojov 5.11.1 — súbory, ktoré sa z Novy kompilujú (`useActions`, `Deletable`,
`ActionSelector`, `CreateResourceButton`), sú v 5.6.3 a 5.11.1 zhodné.

## Rozdiely oproti Nove 4 (vetva `4.x`)

| Čo | Nova 4 (`4.x`) | Nova 5 (`main`) |
|---|---|---|
| Odpoveď akcie s modálom | `actionResponseData.modal` | `actionModalReponseData.component` + `.payload` (preklep je Novin) |
| Ikony | globálne `HeroiconsOutline*` (v1), mapa v2→v1 v `support/icons.js` | `Icon` z `laravel-nova-ui` (v2) |
| Popisky na detaile | `Delete Resource` | `Delete :resource` so `singularLabel` |
| Modály mazania | bez `resource-name` | s `resource-name` |
| Hlavička zoznamu | standalone \|\| create \|\| relate | `hasResourceActionControls` (5.11 áno, 5.6.3 nie — tam starý vzorec) |
| `ServingNova` v testoch | `new ServingNova($request)` | `new ServingNova($app, $request)` |

Oprava, ktorá platí pre obe Novy, ide najprv do `4.x` a do `main` (Nova 5) sa zlúči.

V `wame-reality` sú navyše `tests/Feature/Contract/ContractActionToolbarTest.php` a Cypress
`cypress/e2e/contract.cy.js`.
