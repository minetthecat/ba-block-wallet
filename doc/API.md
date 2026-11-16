# API
Dokumentace pro vývojáře projektu **BetArena**

*PHP API nad MySQL databází a JavaScriptová vrstva pro volání API z frontendu.*

## Základní informace
Archiv obsahuje **9 skupin API**, **37 adres endpointů** (37 vstupních PHP souborů) a **44 operací podle kombinace HTTP metody a adresy**. Automatické požadavky `OPTIONS` nejsou do počtu operací zahrnuty.

Skupiny: `account`, `admin`, `auth`, `bonus`, `casino`, `payment-method`, `tournament`, `transaction`, `wallet`. Adresář `_internal` obsahuje implementaci a není další skupinou veřejných endpointů.

Počet operací podle skupin:
- Ověření uživatele: 6
- Peněženka: 7
- Účet: 10
- Kasino: 6
- Transakce: 2
- Bonusy: 3
- Turnaj: 4
- Administrace: 1
- Platební metody: 5

### Základní URL a formát požadavků
- Základní URL: `/api/<group>/<endpoint>`, např. `/api/auth/register`.
- Endpointy se volají **bez** přípony `.php`. `/api/.htaccess` přepisuje `/api/auth/register` na `/api/auth/register.php` (interní přepis; HTTP metoda, tělo požadavku a řetězec dotazu zůstávají zachovány). Přímá volání s příponou `.php` také fungují.
- Parametry požadavků `GET` se posílají v řetězci dotazu, parametry `POST`/`PUT`/`DELETE` v těle JSON (`Content-Type: application/json`). Soubor se nahrává přes `multipart/form-data` (`account/kyc`).
- Ověření uživatele používá PHP relaci (cookie `PHPSESSID`); požadavky se musí odesílat s `credentials: 'same-origin'`.
- Formát odpovědi: `{ "success": true|false, "message": "...", "data": ... }`. Neúspěšný požadavek vrátí HTTP stav mimo rozsah 2xx nebo hodnotu `success: false`.

### Soubory dodaného archivu

- `/api/.htaccess`
- `/api/_internal/.htaccess`
- `/api/_internal/account.php`
- `/api/_internal/admin.php`
- `/api/_internal/auth.php`
- `/api/_internal/bonus.php`
- `/api/_internal/casino.php`
- `/api/_internal/core.php`
- `/api/_internal/tournament.php`
- `/api/_internal/transaction.php`
- `/api/_internal/wallet.php`
- `/api/account/account.php`
- `/api/account/bonuses.php`
- `/api/account/kyc.php`
- `/api/account/password.php`
- `/api/account/profile.php`
- `/api/account/settings.php`
- `/api/account/tournaments.php`
- `/api/admin/dashboard.php`
- `/api/auth/forgot-password.php`
- `/api/auth/login.php`
- `/api/auth/logout.php`
- `/api/auth/register.php`
- `/api/auth/reset-password.php`
- `/api/auth/verify.php`
- `/api/bonus/bonus.php`
- `/api/bonus/bonuses.php`
- `/api/bonus/claim.php`
- `/api/bootstrap.php`
- `/api/casino/favorite.php`
- `/api/casino/favorites.php`
- `/api/casino/game.php`
- `/api/casino/games.php`
- `/api/casino/providers.php`
- `/api/config.php`
- `/api/payment-method/delete.php`
- `/api/payment-method/get.php`
- `/api/payment-method/list.php`
- `/api/payment-method/post.php`
- `/api/payment-method/put.php`
- `/api/tournament/join.php`
- `/api/tournament/leaderboard.php`
- `/api/tournament/tournament.php`
- `/api/tournament/tournaments.php`
- `/api/transaction/transaction.php`
- `/api/transaction/transactions.php`
- `/api/wallet/deposit.php`
- `/api/wallet/wallet.php`
- `/api/wallet/wallets.php`
- `/api/wallet/withdraw.php`
- `/betarena.sql`
- `/services/api.js`
- `/services/database.php`

---

### Úrovně přístupu

* **Veřejný**: dostupný bez ověření uživatele.
* **Host**: dostupný pouze nepřihlášeným uživatelům.
* **Uživatel**: vyžaduje relaci přihlášeného uživatele.
* **Administrátor**: vyžaduje oprávnění správce.

---

### Endpointy API

V tabulkách je každý řádek jedna operace. Parametry označené jako povinné musí být dodány; ostatní lze vynechat. ID lze posílat jako kladný celočíselný řetězec. Podrobnosti formátů jsou uvedeny níže.

#### Peněženka (`/api/wallet/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `wallets` | Uživatel | Seznam peněženek přihlášeného uživatele | *Žádné* |
| `GET` | `wallet` | Uživatel | Detail peněženky | `wallet_id` (číslo, povinné) |
| `POST` | `wallet` | Uživatel | Vytvoření peněženky (kontrola duplicitní měny) | `currency` (enum: `CZK`, `EUR`, `BSC`, povinné) |
| `PUT` | `wallet` | Uživatel | Úprava peněženky | `wallet_id` (číslo, povinné), `currency` (enum: `CZK`, `EUR`, `BSC`, povinné) |
| `DELETE` | `wallet` | Uživatel | Smazání peněženky | `wallet_id` (číslo, povinné) |
| `POST` | `deposit` | Uživatel | Vklad podle měny | `currency` (enum: `CZK`, `EUR`, `BSC`, povinné), `amount` (číslo, povinné) |
| `POST` | `withdraw` | Uživatel | Výběr podle měny (kontrola zůstatku) | `currency` (enum: `CZK`, `EUR`, `BSC`, povinné), `amount` (číslo, povinné) |

#### Ověření uživatele (`/api/auth/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `login` | Host | Přihlášení | `email` (text, povinné), `password` (heslo, povinné) |
| `POST` | `register` | Host | Registrace | `email` (text, povinné), `password` (heslo, povinné), `password_confirm` (heslo, povinné) |
| `POST` | `logout` | Uživatel | Odhlášení | *Žádné* |
| `POST` | `forgot-password` | Veřejný | Žádost o e-mail pro obnovení hesla | `email` (text, povinné) |
| `POST` | `reset-password` | Veřejný | Nastavení nového hesla pomocí tokenu | `token` (text, povinné), `password` (heslo, povinné), `password_confirm` (heslo, povinné) |
| `GET` | `verify` | Veřejný | Ověření e-mailu pomocí tokenu | `token` (text, povinné) |

#### Účet (`/api/account/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `account` | Uživatel | Přehled účtu | *Žádné* |
| `GET` | `profile` | Uživatel | Načtení osobních údajů | *Žádné* |
| `PUT` | `profile` | Uživatel | Úprava osobních údajů | `first_name` (text), `last_name` (text), `birth_date` (datum), `phone` (text), `address` (text) |
| `GET` | `kyc` | Uživatel | Stav KYC a doklady | *Žádné* |
| `POST` | `kyc` | Uživatel | Nahrání dokladu pro KYC | `document_type` (text, povinné), `file` (soubor, povinné) |
| `GET` | `settings` | Uživatel | Načtení nastavení účtu | *Žádné* |
| `PUT` | `settings` | Uživatel | Úprava nastavení účtu | `language` (text), `notifications` (logická hodnota) |
| `PUT` | `password` | Uživatel | Změna hesla | `current_password` (heslo, povinné), `password` (heslo, povinné), `password_confirm` (heslo, povinné) |
| `GET` | `tournaments` | Uživatel | Turnaje uživatele | `page` (číslo), `limit` (číslo) |
| `GET` | `bonuses` | Uživatel | Bonusy uživatele | `status` (text), `page` (číslo), `limit` (číslo) |

#### Kasino (`/api/casino/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `games` | Veřejný | Seznam her (oblíbené hry pouze pro přihlášeného uživatele) | `filter` (enum: `popular`, `favorites`, `all`, `provider`), `provider` (text), `page` (číslo), `limit` (číslo) |
| `GET` | `game` | Veřejný | Detail hry | `game_id` (číslo, povinné) |
| `GET` | `providers` | Veřejný | Seznam poskytovatelů her | *Žádné* |
| `GET` | `favorites` | Uživatel | Seznam oblíbených her | `page` (číslo), `limit` (číslo) |
| `POST` | `favorite` | Uživatel | Přidání hry mezi oblíbené | `game_id` (číslo, povinné) |
| `DELETE` | `favorite` | Uživatel | Odebrání hry z oblíbených | `game_id` (číslo, povinné) |

#### Transakce (`/api/transaction/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `transactions` | Uživatel | Historie transakcí s filtrem | `type` (enum: `deposit`, `withdraw`, `bet`, `win`, `bonus`), `currency` (enum: `CZK`, `EUR`, `BSC`), `wallet_id` (číslo), `from` (datum), `to` (datum), `page` (číslo), `limit` (číslo) |
| `GET` | `transaction` | Uživatel | Detail transakce | `transaction_id` (číslo, povinné) |

#### Bonusy (`/api/bonus/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `bonuses` | Veřejný | Seznam dostupných bonusů | `page` (číslo), `limit` (číslo) |
| `GET` | `bonus` | Veřejný | Detail bonusu a podmínky | `bonus_id` (číslo, povinné) |
| `POST` | `claim` | Uživatel | Aktivace bonusu | `bonus_id` (číslo, povinné) |

#### Turnaje (`/api/tournament/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `tournaments` | Veřejný | Seznam turnajů podle stavu | `status` (enum: `active`, `upcoming`, `finished`), `page` (číslo), `limit` (číslo) |
| `GET` | `tournament` | Veřejný | Detail turnaje | `tournament_id` (číslo, povinné) |
| `GET` | `leaderboard` | Veřejný | Žebříček turnaje | `tournament_id` (číslo, povinné), `limit` (číslo) |
| `POST` | `join` | Uživatel | Vstup do turnaje | `tournament_id` (číslo, povinné) |

#### Administrace (`/api/admin/`)

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `dashboard` | Administrátor | Data nástěnky | *Žádné* |



---



#### Platební metody (`/api/payment-method/`)

Tyto soubory nepoužívají společné jádro `_internal/core.php` ani relaci uživatele. Identitu určují výhradně parametry požadavku; přihlášení ani vlastnictví metody zde nejsou ověřovány.

| Metoda | Endpoint | Přístup | Popis | Parametry |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `list` | Bez kontroly relace | Metody uživatele, od nejnovějších | `user_id` (povinné) |
| `GET` | `get` | Bez kontroly relace | Detail metody | `method_id` (povinné) |
| `POST` | `post` | Bez kontroly relace | Vytvoření metody | `user_id`, `code`, `name` (povinné); ostatní pole viz níže |
| `PUT` | `put` | Bez kontroly relace | Částečná změna metody | `method_id` (povinné) a alespoň jedno měnitelné pole |
| `DELETE` | `delete` | Bez kontroly relace | Smazání metody | `method_id` (povinné) |

Měnitelná pole jsou `user_id`, `code`, `name`, `can_deposit`, `can_withdraw`, `is_active`, `card_number`, `card_expiry`, `card_cvv`, `card_holder_name`, `recipient_account_number`, `recipient_bank_code`, `recipient_iban`, `recipient_bic`, `recipient_bank_name`, `recipient_bank_address`, `recipient_crypto_address`. Typy a délky odpovídají tabulce `payment_method` v DATABASE.md. Čísla karet a účtů se předávají jako text, aby zůstaly zachovány počáteční nuly.

`list` vrací pole záznamů s `card_last4` (poslední čtyři znaky čísla karty); `card_number` a `card_cvv` nevrací. `get` vrací jeden záznam včetně celého `card_number` a `card_cvv`. Obě ID vrací jako řetězce. `post` odpovídá HTTP 201 s `data.method_id`; úspěšné `put` a `delete` vracejí pouze `success` a `message`. Chybové odpovědi této skupiny také nemusejí obsahovat `data`.

Neznámé parametry se odmítají. Nepovinná textová pole karty a příjemce lze nastavit na `null`; prázdný nebo pouze mezerový text se u nich převede na `null`. Ostatní texty se ukládají bez ořezání. Kontrolují se délky a UTF-8, nikoli věcná platnost čísla karty, IBAN, expirace nebo kryptoadresy. Příznaky přijímají celé číslo nebo číselný řetězec v rozsahu −128 až 127, nikoli JSON `true`/`false`; významově se používá 0/1. `get`, `list`, `put`, `delete` vyžadují kladné ID do 4294967295; zápis `user_id` v `post`/`put` syntakticky přijímá i nulu, jejíž existenci pak posuzuje cizí klíč. `code` je jedinečný v celé tabulce, nikoli jen pro uživatele. Výchozí příznaky při vytvoření jsou 1.

# Frontendová vrstva (`/services/api.js`)

`/services/api.js` obsahuje univerzální funkci `sendApiRequest` a 39 obalových funkcí pro operace osmi skupin se společným jádrem. Sekce platebních metod je v souboru pouze nadpis; obalové funkce pro jejich pět operací chybějí. Lze je volat přes `sendApiRequest`, například `sendApiRequest('/api/payment-method/list', { user_id: "3" }, 'GET')`. Archiv neobsahuje stránky ani komponenty frontendu, proto nelze ověřit jejich skutečné zapojení.

```
/
└── services/
    └── api.js
```

Vložte jej před skripty stránky: `<script src="/services/api.js"></script>`

### `sendApiRequest(endpoint, data = {}, method = 'POST')`
- `GET` odesílá `data` jako řetězec dotazu.
- `POST`, `PUT` a `DELETE` odesílají `data` v těle JSON.
- Pokud je `data` typu `FormData`, odešle se jako `multipart/form-data` (hlavičku `Content-Type` nastaví prohlížeč).
- Odesílá relační cookie (`credentials: 'same-origin'`) a hlavičku `X-Requested-With: XMLHttpRequest`.
- Vrací zpracovanou odpověď JSON (`{ success, message, data }`).
- Vyvolá chybu `Error` se zprávou, pokud server není dostupný, odpověď není platný JSON, HTTP stav označuje chybu nebo `success === false`.

### Konvence pojmenování
- `get<Entity>s` vrací seznam,  `get<Entity>` vrací jeden záznam (`getTransactions`, `getTransaction`).
- `create<Entity>`, `update<Entity>`, `delete<Entity>` pro operace CRUD.
- Doménové operace se pojmenovávají podle významu (`depositToWallet`, `withdrawFromWallet`, `claimBonus`, `joinTournament`).
- Funkce se stejným názvem ve dvou skupinách dostanou předponu (`getAccountTournaments`, `getAccountBonuses`).
- Funkce s parametry přijímají objekt `data`; bezparametrové funkce, například `getWallets()` a `logout()`, jej nevyžadují.

### Použití
```js
try {
    await login({ email: 'test@email.com', password: 'Ukazka123!' });
    const response = await getWallets();
    await depositToWallet({ currency: 'CZK', amount: 100 });
} catch (error) {
    console.error(error.message);
}
```

Nahrání souboru:
```js
const data = new FormData();
data.append('document_type', 'passport');
data.append('file', fileInput.files[0]);
await uploadKycDocument(data);
```

### Funkce podle endpointu

**Ověření uživatele**
| Endpoint | Funkce |
| :--- | :--- |
| `POST login` | `login` |
| `POST register` | `register` |
| `POST logout` | `logout` |
| `POST forgot-password` | `requestPasswordReset` |
| `POST reset-password` | `resetPassword` |
| `GET verify` | `verifyEmail` |

**Účet**
| Endpoint | Funkce |
| :--- | :--- |
| `GET account` | `getAccount` |
| `GET profile` | `getProfile` |
| `PUT profile` | `updateProfile` |
| `GET kyc` | `getKyc` |
| `POST kyc` | `uploadKycDocument` |
| `GET settings` | `getSettings` |
| `PUT settings` | `updateSettings` |
| `PUT password` | `changePassword` |
| `GET tournaments` | `getAccountTournaments` |
| `GET bonuses` | `getAccountBonuses` |

**Peněženka**
| Endpoint | Funkce |
| :--- | :--- |
| `GET wallets` | `getWallets` |
| `GET wallet` | `getWallet` |
| `POST wallet` | `createWallet` |
| `PUT wallet` | `updateWallet` |
| `DELETE wallet` | `deleteWallet` |
| `POST deposit` | `depositToWallet` |
| `POST withdraw` | `withdrawFromWallet` |

**Transakce**
| Endpoint | Funkce |
| :--- | :--- |
| `GET transactions` | `getTransactions` |
| `GET transaction` | `getTransaction` |

**Kasino**
| Endpoint | Funkce |
| :--- | :--- |
| `GET games` | `getGames` |
| `GET game` | `getGame` |
| `GET providers` | `getProviders` |
| `GET favorites` | `getFavoriteGames` |
| `POST favorite` | `addFavoriteGame` |
| `DELETE favorite` | `removeFavoriteGame` |

**Turnaje**
| Endpoint | Funkce |
| :--- | :--- |
| `GET tournaments` | `getTournaments` |
| `GET tournament` | `getTournament` |
| `GET leaderboard` | `getTournamentLeaderboard` |
| `POST join` | `joinTournament` |

**Bonusy**
| Endpoint | Funkce |
| :--- | :--- |
| `GET bonuses` | `getBonuses` |
| `GET bonus` | `getBonus` |
| `POST claim` | `claimBonus` |

**Administrace**
| Endpoint | Funkce |
| :--- | :--- |
| `GET dashboard` | `getAdminDashboard` |

## Formáty, pravidla a tok dat

Frontend předá objekt do `sendApiRequest` → `fetch()` odešle HTTP požadavek → Apache přepíše adresu bez přípony → vstupní PHP soubor zavolá obsluhu → PDO provede parametrizovaný SQL dotaz → JSON odpověď se vrátí do JavaScriptu. Frontend se k MySQL nepřipojuje přímo.

Osm skupin načítá `_internal/core.php`, který čte `api/config.php`, kontroluje metodu a vstup, spouští relaci a předává požadavek odpovídajícímu souboru `_internal/<skupina>.php`. Platební metody používají samostatné `services/database.php`. Obě konfigurace míří na `betarena`; jejich nastavení se však spravuje zvlášť. Soubor `api/bootstrap.php` v archivu existuje, ale uvedené vstupní endpointy jej nenačítají.

| Hodnota | Skutečný formát a význam |
| :--- | :--- |
| ID | Kladné celé číslo nebo řetězec číslic bez počáteční nuly; u BIGINT používat řetězec, aby JavaScript neztratil přesnost. Typy ID v odpovědích společného jádra závisejí na PDO, nejsou jednotně převedeny na řetězce. |
| Částka | Kladné číslo nebo desetinný řetězec, nejvýše 16 číslic před tečkou a 2 za ní, bez čárky. Pro přesnost je vhodné `"100.10"`. SQL pracuje s `DECIMAL(18,2)`; částky v odpovědích mohou být řetězce. |
| Datum | `YYYY-MM-DD`; databázový čas má formát `YYYY-MM-DD HH:MM:SS`. Jádro používá `Europe/Prague`. |
| Stránkování | `page` 1–1000000 (výchozí 1), `limit` 1–100 (výchozí 20). Seznam se vrací v `data` bez celkového počtu a metadat stránek. `payment-method/list` stránkování nemá. |
| Nastavení oznámení | `notifications` v požadavku PUT musí být JSON `true`/`false`; v DB se ukládá 1/0 a odpověď se výslovně nepřevádí na logickou hodnotu. |
| Měna | API přijímá `CZK`, `EUR`, `BSC`; `CZK` jsou české koruny, `EUR` eura, `BSC` podle zadání projektu bonusové peníze. `BAB` je pouze ve schématu `wallet`, API ji nepřijímá a význam není doložen. |

Registrace vloží uživatele a dvě nulové peněženky `CZK` a `BSC` se stejným `user_id` v jedné databázové transakci. Vytvoří ověřovací token a následně připraví e-mailový odkaz. Registrace uživatele automaticky nepřihlásí. Přihlášení uloží `$_SESSION['user_id']` a obnoví ID relace. U účtu, peněženek a historie se `user_id` bere z relace, nikoli z klientského filtru. Heslo se nevrací jako `password_hash`; veřejná reprezentace uživatele tento sloupec odstraňuje.

Nové heslo musí mít 8–72 bajtů a shodovat se s `password_confirm`. Token je náhodný text, v DB se ukládá jeho SHA-256 otisk. Ověřovací token platí podle konfigurace 24 hodin, obnovovací 1 hodinu; použití a expirace se kontrolují. Aktuální konfigurace nevyžaduje ověření e-mailu pro přihlášení a e-maily zapisuje do PHP chybového logu (`mail.driver = log`).

Vklad a výběr vyhledají peněženku podle relace a měny, zamknou ji pomocí `FOR UPDATE`, změní zůstatek a zapíší transakci s `balance_after`. Výběr kontroluje dostatečný zůstatek. Endpointy nepoužívají `method_id`, nevolají platební bránu a neověřují příznaky platební metody. Změna měny a smazání peněženky jsou možné jen u nulového zůstatku bez historie; PUT se stejnou měnou vrátí záznam beze změny.

Aktivace dostupného bonusu vytvoří `user_bonus`; kladná pevná částka se připíše do existující peněženky měny nastavené u bonusu a zapíše se transakce typu `bonus`. Kód automaticky nepřevádí bonusy do BSC. Opakovanou aktivaci stejného bonusu odmítá. Seznam i detail bonusu vyžadují aktivní a časově platný bonus. Přihlásit se lze do probíhajícího i budoucího turnaje, pokud ještě neskončil. Žebříček řadí podle skóre sestupně, času přihlášení a ID uživatele vzestupně.

KYC upload vyžaduje `FormData` s `document_type` a `file`; podporuje PDF, JPEG a PNG podle zjištěného MIME typu. Soubor dostane náhodný název v `/uploads/`, vloží se `kyc_document` a stav uživatele přejde na `pending`. Adresář musí existovat a být zapisovatelný. Samostatný aplikační limit velikosti zde není; platí limity serveru a PHP.

## Stavové kódy a závislosti

Společné jádro vrací úspěch HTTP 200 s `success`, `message`, `data`; bez výsledku je `data: null`. Používá zejména 400 pro neplatný vstup, 401 pro chybějící přihlášení, 403 pro zakázaný přístup nebo chybějící `X-Requested-With`, 404 pro chybějící záznam, 405 pro nesprávnou metodu, 409 pro konflikt, 415 pro nepodporovaný obsah a 500 pro interní chybu. `OPTIONS` vrací 204 ještě před spuštěním relace. Tyto vlastnosti nelze automaticky vztahovat na samostatnou skupinu platebních metod.

`run_endpoint()` vyžaduje `/services/session.php` a funkci `betarena_start_session()`. Tento soubor v dodaném archivu **chybí**; k samostatnému spuštění osmi skupin je potřeba jej doplnit z projektu. Archiv také neobsahuje `/uploads/` ani frontendové stránky. Kontrola dokumentace je statické porovnání obsahu archivu, nikoli ověření běhu na serveru.

## Kontrola archivu proti dokumentaci

Zkontrolováno podle `api(1).zip`: 9 skupin, 37 adres, 44 operací a všech 39 obalových funkcí v `services/api.js`. Doplněno všech 5 samostatných operací platebních metod a jejich odlišný přístup, odpovědi i databázové připojení. Seznam souborů odpovídá archivu. Podrobnosti 13 tabulek a 2 pohledů jsou v DATABASE.md.
