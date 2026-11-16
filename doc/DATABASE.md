# Databáze BetArena

Dokumentace podle `betarena.sql` v archivu `api(1).zip`. Obsahuje **13 tabulek a 2 pohledy**. Databáze je `betarena`, tabulky používají InnoDB, znakovou sadu `utf8mb4` a porovnávání `utf8mb4_unicode_ci`.

Technické názvy sloupců, typů a uložených hodnot jsou zachovány. SQL `NULL` znamená nepřítomnou hodnotu, `NOT NULL` povinnou hodnotu; nejde o prázdný text. `PK` je primární klíč, `UQ` jedinečný klíč a `FK` cizí klíč. Přesné složené klíče jsou u jednotlivých tabulek uvedeny samostatně.

## Formát a význam hodnot

| Oblast | Význam |
| :--- | :--- |
| ID | Většinou `INT UNSIGNED`; `transaction_id` je `BIGINT UNSIGNED`. ID transakce v JavaScriptu předávat jako řetězec. |
| Peníze a skóre | `DECIMAL(18,2)`, nejvýše 16 číslic před tečkou a 2 za ní. Používat desetinnou tečku. |
| Měny | `CZK` české koruny, `EUR` eura, `BSC` bonusové peníze podle zadání projektu. Význam `BAB` není v archivu vysvětlen. |
| Datum a čas | `DATE`: `YYYY-MM-DD`; `DATETIME`: `YYYY-MM-DD HH:MM:SS`. Jádro API nastavuje Europe/Prague; samostatné platební metody nastavení času nemají. |
| Příznaky | Význam 0 = ne, 1 = ano. `TINYINT(1)` sám neomezuje hodnoty pouze na 0/1. |
| Transakce | `deposit` vklad, `withdraw` výběr, `bet` sázka, `win` výhra, `bonus` připsání bonusu. `amount` je v ukázkách kladná hodnota; směr určuje typ, `balance_after` je historický zůstatek po operaci. |
| Role | `user` uživatel, `admin` správce. |
| Stav uživatele KYC | `none` bez ověření, `pending` čeká, `verified` ověřeno, `rejected` zamítnuto. |
| Stav dokladu | `pending` čeká na posouzení, `approved` schváleno, `rejected` zamítnuto. |
| Stav bonusu uživatele | `active` aktivní, `completed` dokončený, `expired` po platnosti. |
| Typ tokenu | `verify_email` ověření e-mailu, `reset_password` obnovení hesla. `token_hash` je SHA-256 otisk; `password_hash` je otisk hesla vytvořený PHP. |

## Tabulky a sloupce

### `auth_token`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `token_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID tokenu |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `type` | `enum('verify_email','reset_password')` | povinné | `'verify_email'`, `'reset_password'` |
| `token_hash` | `char(64)` | povinné | SHA-256 hash tokenu |
| `expires_at` | `datetime` | povinné | Expirace tokenu |
| `used_at` | `datetime` | výchozí NULL | Čas použití |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas vytvoření |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`token_id`)``
- ``ADD UNIQUE KEY `uq_auth_token_hash` (`token_hash`)``
- ``ADD KEY `idx_auth_token_user` (`user_id`,`type`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE``

### `bonus`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `bonus_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID bonusu |
| `name` | `varchar(150)` | povinné | Název bonusu |
| `description` | `text` | výchozí NULL | Popis bonusu |
| `conditions` | `text` | výchozí NULL | Podmínky pro získání/protočení |
| `amount` | `decimal(18,2)` | výchozí NULL | Hodnota bonusu |
| `currency` | `enum('CZK','EUR','BSC')` | výchozí NULL | `'CZK'`, `'EUR'`, `'BSC'` |
| `valid_from` | `datetime` | výchozí NULL | Platnost od |
| `valid_to` | `datetime` | výchozí NULL | Platnost do |
| `is_active` | `tinyint(1)` | povinné výchozí 1 | Aktivní bonus (`0` / `1`) |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas vytvoření |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`bonus_id`)``

**Vazby:**

Bez odchozího cizího klíče.

### `favorite_game`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `game_id` | `int(10) UNSIGNED` | povinné | ID hry |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas přidání |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`user_id`,`game_id`)``
- ``ADD KEY `fk_favorite_game` (`game_id`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_favorite_game` FOREIGN KEY (`game_id`) REFERENCES `game` (`game_id`) ON DELETE CASCADE``
- ``ADD CONSTRAINT `fk_favorite_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE``

### `game`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `game_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID hry |
| `provider_id` | `int(10) UNSIGNED` | povinné | ID poskytovatele |
| `name` | `varchar(150)` | povinné | Název hry |
| `description` | `text` | výchozí NULL | Popis hry |
| `image` | `varchar(255)` | výchozí NULL | Náhledový obrázek |
| `popularity` | `int(10) UNSIGNED` | povinné výchozí 0 | Skóre popularity |
| `is_active` | `tinyint(1)` | povinné výchozí 1 | Je hra dostupná (`0` / `1`) |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas přidání |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`game_id`)``
- ``ADD KEY `idx_game_provider` (`provider_id`)``
- ``ADD KEY `idx_game_popularity` (`popularity`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_game_provider` FOREIGN KEY (`provider_id`) REFERENCES `provider` (`provider_id`)``

### `kyc_document`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `kyc_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID dokladu |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `document_type` | `varchar(50)` | povinné | Typ dokladu (např. pas, OP) |
| `file_path` | `varchar(255)` | povinné | Cesta k souboru |
| `status` | `enum('pending','approved','rejected')` | povinné výchozí 'pending' | `'pending'`, `'approved'`, `'rejected'` |
| `uploaded_at` | `datetime` | povinné výchozí current_timestamp() | Čas nahrání |
| `reviewed_at` | `datetime` | výchozí NULL | Čas schválení/zamítnutí |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`kyc_id`)``
- ``ADD KEY `idx_kyc_user` (`user_id`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE``

### `payment_method`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `method_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID metody |
| `user_id` | `int(10) UNSIGNED` | povinné | ID vlastníka platební metody |
| `code` | `varchar(30)` | povinné | Kód metody (např. `'card'`, `'crypto'`) |
| `name` | `varchar(100)` | povinné | Název platební metody |
| `can_deposit` | `tinyint(1)` | povinné výchozí 1 | Povoleno pro vklad (`0` / `1`) |
| `can_withdraw` | `tinyint(1)` | povinné výchozí 1 | Povoleno pro výběr (`0` / `1`) |
| `is_active` | `tinyint(1)` | povinné výchozí 1 | Aktivní metoda (`0` / `1`) |
| `card_number` | `varchar(19)` | výchozí NULL | Číslo platební karty jako text |
| `card_expiry` | `char(5)` | výchozí NULL | Expirace karty; očekávaný význam MM/YY, API kontroluje pouze délku |
| `card_cvv` | `varchar(4)` | výchozí NULL | Ověřovací kód karty jako text |
| `card_holder_name` | `varchar(100)` | výchozí NULL | Jméno držitele karty |
| `recipient_account_number` | `varchar(34)` | výchozí NULL | Číslo účtu příjemce |
| `recipient_bank_code` | `varchar(10)` | výchozí NULL | Kód banky příjemce |
| `recipient_iban` | `varchar(34)` | výchozí NULL | IBAN příjemce |
| `recipient_bic` | `varchar(11)` | výchozí NULL | BIC/SWIFT banky příjemce |
| `recipient_bank_name` | `varchar(150)` | výchozí NULL | Název banky příjemce |
| `recipient_bank_address` | `varchar(255)` | výchozí NULL | Adresa banky příjemce |
| `recipient_crypto_address` | `varchar(100)` | výchozí NULL | Kryptoměnová adresa příjemce |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`method_id`)``
- ``ADD UNIQUE KEY `uq_payment_method_code` (`code`)``
- ``ADD KEY `fk_payment_method_user` (`user_id`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_payment_method_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE``

### `provider`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `provider_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID poskytovatele |
| `slug` | `varchar(50)` | povinné | URL slug |
| `name` | `varchar(100)` | povinné | Název poskytovatele |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`provider_id`)``
- ``ADD UNIQUE KEY `uq_provider_slug` (`slug`)``

**Vazby:**

Bez odchozího cizího klíče.

### `tournament`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `tournament_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID turnaje |
| `name` | `varchar(150)` | povinné | Název turnaje |
| `description` | `text` | výchozí NULL | Popis turnaje |
| `prize_pool` | `decimal(18,2)` | výchozí NULL | Celková výhra |
| `currency` | `enum('CZK','EUR','BSC')` | výchozí NULL | `'CZK'`, `'EUR'`, `'BSC'` |
| `starts_at` | `datetime` | povinné | Začátek turnaje |
| `ends_at` | `datetime` | povinné | Konec turnaje |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas vytvoření |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`tournament_id`)``
- ``ADD KEY `idx_tournament_dates` (`starts_at`,`ends_at`)``

**Vazby:**

Bez odchozího cizího klíče.

### `tournament_player`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `tournament_id` | `int(10) UNSIGNED` | povinné | ID turnaje |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `score` | `decimal(18,2)` | povinné výchozí 0.00 | Nahrané skóre |
| `joined_at` | `datetime` | povinné výchozí current_timestamp() | Čas vstupu do turnaje |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`tournament_id`,`user_id`)``
- ``ADD KEY `idx_tp_leaderboard` (`tournament_id`,`score`)``
- ``ADD KEY `fk_tp_user` (`user_id`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_tp_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`tournament_id`) ON DELETE CASCADE``
- ``ADD CONSTRAINT `fk_tp_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE``

### `transaction`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `transaction_id` | `bigint(20) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID transakce |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `wallet_id` | `int(10) UNSIGNED` | povinné | ID peněženky |
| `type` | `enum('deposit','withdraw','bet','win','bonus')` | povinné | `'deposit'`, `'withdraw'`, `'bet'`, `'win'`, `'bonus'` |
| `currency` | `enum('CZK','EUR','BSC')` | povinné | `'CZK'`, `'EUR'`, `'BSC'` |
| `amount` | `decimal(18,2)` | povinné | Částka transakce |
| `status` | `enum('pending','completed','cancelled')` | po migraci; výchozí 'completed' | Stav transakce |
| `balance_after` | `decimal(18,2)` | povinné | Zůstatek po transakci |
| `method_id` | `int(10) UNSIGNED` | výchozí NULL | ID platební metody |
| `game_id` | `int(10) UNSIGNED` | výchozí NULL | ID hry (při sázce/výhře) |
| `bonus_id` | `int(10) UNSIGNED` | výchozí NULL | ID bonusu |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas transakce |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`transaction_id`)``
- ``ADD KEY `idx_tx_user_date` (`user_id`,`created_at`)``
- ``ADD KEY `idx_tx_wallet` (`wallet_id`)``
- ``ADD KEY `idx_tx_type` (`type`)``
- ``ADD KEY `fk_tx_method` (`method_id`)``
- ``ADD KEY `fk_tx_game` (`game_id`)``
- ``ADD KEY `fk_tx_bonus` (`bonus_id`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_tx_bonus` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`bonus_id`)``
- ``ADD CONSTRAINT `fk_tx_game` FOREIGN KEY (`game_id`) REFERENCES `game` (`game_id`)``
- ``ADD CONSTRAINT `fk_tx_method` FOREIGN KEY (`method_id`) REFERENCES `payment_method` (`method_id`)``
- ``ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)``
- ``ADD CONSTRAINT `fk_tx_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallet` (`wallet_id`)``

### `user`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `user_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID uživatele |
| `email` | `varchar(190)` | povinné | E-mailová adresa |
| `password_hash` | `varchar(255)` | povinné | Hash hesla |
| `role` | `enum('user','admin')` | povinné výchozí 'user' | `'user'`, `'admin'` |
| `first_name` | `varchar(100)` | výchozí NULL | Křestní jméno |
| `last_name` | `varchar(100)` | výchozí NULL | Příjmení |
| `birth_date` | `date` | výchozí NULL | Datum narození |
| `phone` | `varchar(30)` | výchozí NULL | Telefonní číslo |
| `address` | `varchar(255)` | výchozí NULL | Bydliště |
| `language` | `varchar(5)` | povinné výchozí 'cs' | Jazyk rozhraní |
| `notifications` | `tinyint(1)` | povinné výchozí 1 | Povolení notifikací (`0` / `1`) |
| `kyc_status` | `enum('none','pending','verified','rejected')` | povinné výchozí 'none' | `'none'`, `'pending'`, `'verified'`, `'rejected'` |
| `email_verified_at` | `datetime` | výchozí NULL | Čas ověření e-mailu |
| `created_at` | `datetime` | povinné výchozí current_timestamp() | Čas vytvoření |
| `updated_at` | `datetime` | povinné výchozí current_timestamp() při změně current_timestamp() | Čas poslední úpravy |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`user_id`)``
- ``ADD UNIQUE KEY `uq_user_email` (`email`)``

**Vazby:**

Bez odchozího cizího klíče.

### `user_bonus`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `user_bonus_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID přiřazení |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `bonus_id` | `int(10) UNSIGNED` | povinné | ID bonusu |
| `status` | `enum('active','completed','expired')` | povinné výchozí 'active' | `'active'`, `'completed'`, `'expired'` |
| `claimed_at` | `datetime` | povinné výchozí current_timestamp() | Čas aktivace |
| `expires_at` | `datetime` | výchozí NULL | Expirace bonusu uživateli |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`user_bonus_id`)``
- ``ADD UNIQUE KEY `uq_user_bonus` (`user_id`,`bonus_id`)``
- ``ADD KEY `idx_user_bonus_status` (`user_id`,`status`)``
- ``ADD KEY `fk_user_bonus_bonus` (`bonus_id`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_user_bonus_bonus` FOREIGN KEY (`bonus_id`) REFERENCES `bonus` (`bonus_id`)``
- ``ADD CONSTRAINT `fk_user_bonus_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE``

### `wallet`

| Sloupec | SQL typ | Povinnost a výchozí hodnota | Význam |
| :--- | :--- | :--- | :--- |
| `wallet_id` | `int(10) UNSIGNED` | povinné; automaticky rostoucí ID | Unikátní ID peněženky |
| `user_id` | `int(10) UNSIGNED` | povinné | ID uživatele |
| `currency` | `enum('CZK','EUR','BSC','BAB')` | povinné | CZK, EUR, BSC nebo BAB; API přijímá jen CZK, EUR a BSC |
| `balance` | `decimal(18,2)` | povinné výchozí 0.00 | Aktuální zůstatek |

**Klíče a indexy:**

- ``ADD PRIMARY KEY (`wallet_id`)``
- ``ADD UNIQUE KEY `uq_wallet_user_currency` (`user_id`,`currency`)``

**Vazby:**

- ``ADD CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE``

## 👁️ Databázové pohledy

### `v_tournament`
Vypočítává aktuální stav turnaje a počet zúčastněných hráčů.
* **Vypočítané sloupce:**
  * `status`: `'upcoming'` (před začátkem), `'active'` (probíhá), `'finished'` (po konci).
  * `players`: Počet přihlášených hráčů v `tournament_player`.

### `v_admin_dashboard`
Agreguje přehledové statistiky pro administraci.
* **Vypočítané sloupce:**
  * `users_total`: Celkový počet registrovaných uživatelů.
  * `users_last_7d`: Počet nových uživatelů za posledních 7 dní.
  * `deposits_total`: Součet všech vkladů.
  * `withdrawals_total`: Součet všech výběrů.
  * `tournaments_active`: Počet právě probíhajících turnajů.

Pohled `v_tournament` vrací všechny sloupce turnaje a navíc `status`, `players`. Přesně při `ends_at` ještě vrací `active`, protože test ukončení používá `NOW() > ends_at`; endpoint pro vstup již při `ends_at <= NOW()` vstup odmítne. `v_admin_dashboard` sčítá částky všech měn dohromady bez převodu nebo rozdělení podle měny. Oba pohledy mají v exportu `DEFINER=root@localhost` a `SQL SECURITY DEFINER`.

## Vazby a práce API s daty

Registrace vytváří uživatele i nulové peněženky CZK a BSC v jedné transakci. Jedinečný klíč `(user_id, currency)` dovolí jednomu uživateli jen jednu peněženku dané měny. Samotný export tento počet peněženek pro historické účty nevynucuje.

Pohyby peněženek se počítají v MySQL jako DECIMAL pod zámkem `FOR UPDATE`. Uživatel a peněženka jsou do historie vloženi společně s měnou, částkou a zůstatkem po operaci. Cizí klíče historie ověřují existenci jednotlivých záznamů, nikoli shodu vlastníka peněženky, její měny a uživatele uvedeného v transakci. Tuto shodu při zápisu zajišťuje obsluha API.

Smazání uživatele kaskádově odstraňuje jeho tokeny, doklady, peněženky, metody, oblíbené hry, účasti a přiřazené bonusy. Existující transakce však mají vazby bez kaskádového mazání a mohou smazání blokovat. Mazání metody použité v historii je také blokováno. Při vynechání pravidla ON DELETE/ON UPDATE platí standardní omezení InnoDB; úplné deklarace jsou uvedeny u tabulek.

Platební metody patří konkrétnímu `user_id`, `code` má globální jedinečnost. Karta, bankovní údaje a kryptoadresa jsou nepovinné texty; SQL ani API neurčuje výběrem druhu metody povinnou kombinaci těchto polí. Číslo karty i CVV jsou ve stávajícím schématu uloženy přímo jako text. Podrobnosti jejich načítání jsou v API.md.

## Zjištěné rozdíly mezi schématem, daty a API

| Oblast | Skutečný stav |
| :--- | :--- |
| Měna BAB | Povolena jen v `wallet.currency`; ostatní měnové výčty a validace API ji neobsahují. |
| BSC | V projektu bonusové peníze. Ukázková metoda `demo_bsc` však obsahuje kryptoadresu a ukázkové transakce BSC i vklad/výběr; jde o nesoulad ukázkových dat s významem projektu. |
| Bonusy | Ukázkové bonusy jsou v CZK. Obsluha připíše měnu bonusu, nikoli automaticky BSC. |
| Peněženky při registraci | Aktuální kód zakládá CZK a BSC. Historická data některé peněženky postrádají; například uživatel 6 nemá v exportu žádnou. |
| Historie a zůstatek | Peněženka 3 má zůstatek 5000.00 CZK, ale poslední uvedená transakce této peněženky má `balance_after` 3000.00. Export proto nelze považovat za úplné odsouhlasení zůstatků s historií. |
| KYC | Uživatel 3 má `kyc_status=none`, přesto obsahuje schválený, čekající i zamítnutý doklad. Stav uživatele se z dokladů automaticky nepočítá. |

## Kontrola archivu proti dokumentaci

Struktura byla porovnána přímo s CREATE TABLE, ALTER TABLE a definicemi pohledů v dodaném SQL: všech 13 tabulek, všechny sloupce, typy, výchozí hodnoty, primární a jedinečné klíče, indexy a cizí klíče. Pomocné CREATE TABLE pro dva pohledy nejsou započítány jako skutečné tabulky. Byla porovnána také obsluha endpointů a ukázková data. Databáze nebyla importována ani spuštěna; dokumentace popisuje dodaný stav a výše výslovně uvádí jeho nesoulady.
