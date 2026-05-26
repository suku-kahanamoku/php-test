# Tester Exam – Testovací prostředí

PHP projekt určený k ověření znalostí nového QA testera.
Obsahuje záměrně vložené bugy a testovací scénáře pokrývající frontend, API a vývojářské nástroje.

---

## Spuštění projektu

```bash
cd /cesta/k/test/

# PHP built-in server (doporučeno pro lokální testování)
php -S localhost:8000 router.php
```

Otevři `https://diamondfish.cz/php-test/index.php`

Pro Apache/Nginx – projekt využívá `.htaccess` s `mod_rewrite`.

---

## Struktura projektu

```
test/
├── bootstrap.php          ← autoload, konstanty (tokeny)
├── router.php             ← router pro PHP built-in server (/api/* → api/index.php)
├── composer.json
├── .gitignore
├── data/
│   ├── products.json      ← mock produkty (záměrné datové chyby!)
│   ├── enums.json         ← číselníky (categories, statuses, colors…)
│   └── users.json         ← mock uživatelé (záměrné chyby v emailech!)
├── src/
│   ├── Request.php        ← parsování HTTP requestu + Bearer token
│   ├── Response.php       ← JSON odpovědi (success/error/successList)
│   ├── Router.php         ← jednoduchý HTTP router s regex matching
│   └── Filter.php         ← implementace q={} filtru nad poli
├── api/
│   ├── .htaccess          ← Apache: routování na index.php
│   └── index.php          ← API front-controller (products, enums, users, auth, status)
├── components/
│   ├── header.php         ← HTML hlavička + navigace + JS konstanty (API_BASE, AUTH_TOKEN)
│   └── footer.php         ← JS helpery (showResponse, authHeaders, showAlert)
├── pages/
│   ├── login.php          ← přihlašovací stránka (Bearer token → localStorage)
│   ├── products.php       ← seznam produktů, filtry, stránkování
│   ├── product-detail.php ← detail + editace produktu
│   ├── user-form.php      ← registrace uživatele, validace
│   ├── errors.php         ← HTTP chyby, curl příklady, konzole
│   └── scroll.php         ← scroll, responsivita
├── index.php              ← dashboard s přehledem API
└── tests/                 ← (v .gitignore – spuštění viz níže)
    ├── bootstrap.php
    ├── test_api_q.php
    ├── test_products.php
    ├── test_users.php
    ├── test_enums.php
    ├── test_errors.php
    └── run_all.php
```

---

## API přehled

**Základní URL:** `https://diamondfish.cz/php-test/api`

Frontend komunikuje výhradně přes `fetch()` na REST endpointy – žádné `?path=` parametry.

### Produkty

| Metoda | Endpoint         | Auth       | Popis                           |
|--------|------------------|------------|---------------------------------|
| GET    | `/products`      | –          | Seznam, q={}, sort, page, limit |
| GET    | `/products/{id}` | –          | Jeden produkt                   |
| POST   | `/products`      | user/admin | Vytvořit (validuje cenu)        |
| PUT    | `/products/{id}` | user/admin | Upravit                         |
| DELETE | `/products/{id}` | **admin**  | Smazat                          |

### Enumerace

| Metoda | Endpoint         | Auth | Popis                  |
|--------|------------------|------|------------------------|
| GET    | `/enums`         | –    | Všechny číselníky      |
| GET    | `/enums/{type}`  | –    | Konkrétní číselník     |

### Uživatelé

| Metoda | Endpoint        | Auth        | Popis                             |
|--------|-----------------|-------------|-----------------------------------|
| GET    | `/users`        | user/admin  | Seznam uživatelů                  |
| GET    | `/users/{id}`   | user (own)  | Profil (vlastní nebo admin)       |
| POST   | `/users`        | –           | Registrace                        |
| PUT    | `/users/{id}`   | user (own)  | Editace profilu                   |

### Autentizace

| Metoda | Endpoint       | Auth | Popis                                        |
|--------|----------------|------|----------------------------------------------|
| POST   | `/auth/login`  | –    | Přihlášení (email + password → Bearer token) |
| POST   | `/auth/logout` | –    | Odhlášení (token spravuje klient)            |
| GET    | `/auth/me`     | ✓    | Profil přihlášeného uživatele                |

### Simulované HTTP chyby

| Metoda | Endpoint       | Vrátí |
|--------|----------------|-------|
| GET    | `/status/500`  | 500   |
| GET    | `/status/404`  | 404   |
| GET    | `/status/401`  | 401   |
| GET    | `/status/403`  | 403   |

---

## Autentizace

### Přihlášení (Bearer token)

```bash
curl -X POST https://diamondfish.cz/php-test/api/auth/login ...
```

Odpověď:
```json
{
  "success": true,
  "data": {
    "token": "test-token-abc123",
    "user": { "id": 2, "username": "jan.novak", "role": "user", ... }
  }
}
```

Token pak posílej v hlavičce: `Authorization: Bearer test-token-abc123`

### Testovací účty

| Email                   | Heslo        | Role  | Token                  |
|-------------------------|--------------|-------|------------------------|
| `admin@test.cz`         | `Admin1234!` | admin | `admin-token-xyz456`   |
| `jan.novak@test.cz`     | `heslo123`   | user  | `test-token-abc123`    |
| `petra.dvorak@test.cz`  | `heslo123`   | editor| (přihlásí, token auto) |
| `martin.horak@firma.com`| `heslo123`   | user  | (přihlásí, token auto) |

> Uživatelé bez tokenu (`null`) ho automaticky dostanou po prvním přihlášení.

### Frontend – localStorage

Token je uložen v `localStorage` pod klíčem `auth_token`.
V JS je dostupný přes `AUTH_TOKEN()` (funkce definovaná v `components/header.php`).

```javascript
// Přihlásit se (volání fetch)
const res = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'jan.novak@test.cz', password: 'heslo123' }),
});
const { data } = await res.json();
localStorage.setItem('auth_token', data.token);
localStorage.setItem('auth_user',  JSON.stringify(data.user));

// Autentizovaný request
const r = await fetch('/api/users', {
  headers: { 'Authorization': 'Bearer ' + AUTH_TOKEN() }
});
```

---

## Syntaxe q={}

Parametr `q` přijímá JSON objekt pro filtrování dat.

```
GET /api/products?q=ENCODED_JSON
```

### Zkrácený zápis (implicit eq)

```json
{"category": "electronics"}
{"status": "active"}
```

### Explicitní operátor

```json
{"field": {"value": "hledana_hodnota", "operator": "OPERATOR"}}
```

### Podporované operátory

| Operátor   | Popis                       | Příklad                                                          |
|------------|-----------------------------|------------------------------------------------------------------|
| `eq`       | rovnost (výchozí)           | `{"status":"active"}`                                            |
| `neq`      | nerovnost                   | `{"status":{"value":"active","operator":"neq"}}`                 |
| `contains` | obsahuje (case-insensitive) | `{"name":{"value":"widget","operator":"contains"}}`              |
| `start`    | začíná na                   | `{"sku":{"value":"WDG","operator":"start"}}`                     |
| `end`      | končí na                    | `{"email":{"value":"@firma.cz","operator":"end"}}`               |
| `gte`      | větší nebo rovno            | `{"price":{"value":100,"operator":"gte"}}`                       |
| `lte`      | menší nebo rovno            | `{"price":{"value":500,"operator":"lte"}}`                       |
| `gt`       | ostře větší                 | `{"stock_quantity":{"value":0,"operator":"gt"}}`                 |
| `lt`       | ostře menší                 | `{"price":{"value":20,"operator":"lt"}}`                         |
| `in`       | hodnota je v poli           | `{"category":{"value":["electronics","gadgets"],"operator":"in"}}` |
| `isnull`   | hodnota je null             | `{"description":{"operator":"isnull"}}`                          |
| `notnull`  | hodnota není null           | `{"description":{"operator":"notnull"}}`                         |

### Stránkování a řazení

```
GET /api/products?page=2&limit=10
GET /api/products?sort=price          # vzestupně
GET /api/products?sort=-price         # sestupně
```

### Příklady pro curl

```bash
BASE="https://diamondfish.cz/php-test/api"

# Produkty kategorie electronics
curl "$BASE/products" ...

# Produkty s cenou >= 100
curl "$BASE/products" ...

# Uživatelé s emailem končícím @test.cz (vyžaduje auth)
curl "$BASE/users" ...
```

---

## Testovací scénáře

### Scénář 1 – Přihlášení a Bearer token

**Cíl:** Ověřit auth flow – login, token, localStorage, logout.

**Postup:**
1. Otevři `/pages/login.php`
2. Přihlaš se jako `admin@test.cz` / `Admin1234!`
3. Ověř v DevTools → Application → localStorage: klíče `auth_token` a `auth_user`
4. Ověř, že navbar zobrazí jméno přihlášeného uživatele
5. Odhlásit → token zmizí z localStorage, navbar zobrazí „Přihlásit"
6. Zkus přihlásit se špatným heslem → 401, chybová hláška
7. Zkus přihlásit neaktivního uživatele `tomas.kral@` → 403

**cURL ověření:**
```bash
BASE="https://diamondfish.cz/php-test/api"

# Správné přihlášení
curl -X POST "$BASE/auth/login" ...

# Špatné heslo → 401
curl -X POST "$BASE/auth/login" ...

# Profil přihlášeného uživatele
curl "$BASE/auth/me" ...
```

---

### Scénář 2 – Správné pochopení q={} syntaxe

**Cíl:** Tester musí umět sestavit a odeslat q={} dotaz v URL, přes formulář i cURL.

**Postup:**
1. Otevři `/pages/products.php`
2. Filtruj produkty podle kategorie `electronics` – nastav select a odešli formulář
3. Zkontroluj, že URL/API volání obsahuje `q={"category":"electronics"}`
4. Filtruj podle názvu obsahujícího `widget` – ověř operator `contains`
5. Filtruj SKU začínající na `WDG` – ověř operator `start`
6. Zkombinuj filtr názvu + kategorie, ověř, že oba podmínky platí najednou
7. Z terminálu zavolej přímo API s q={} přes cURL

**Očekávané výsledky:**
- Filtr se správně promítne do q={} JSON v parametru URL
- Pouze výsledky splňující všechna kritéria jsou vráceny
- Chybný JSON v q= se ignoruje (vrátí se všechna data)

---

### Scénář 3 – Gettery a settery produktů

**Cíl:** Ověřit čtení (GET) a zápis (POST/PUT) dat přes API.

**Postup:**
1. GET `/api/products` – ověř strukturu odpovědi (success, data, meta)
2. GET `/api/products/1` – ověř detail produktu
3. POST nový produkt s platnými daty (přihlásit nebo použít token v hlavičce)
4. GET nově vytvořeného produktu – ověř, že data sedí
5. PUT (update) produktu – změň název a cenu
6. GET upraveného produktu – ověř změny

**Očekávané výsledky:**
- GET vrací `{"success": true, "data": {...}, "meta": {...}}`
- POST vrací `201 Created` s novým produktem
- PUT vrací `200 OK` s aktualizovaným produktem
- Všechna čísla jsou vrácena jako float (ne int)

---

### Scénář 4 – Desetinná čárka místo tečky

**Cíl:** Odhalit a nahlásit produkty s cenou jako string s čárkou (`"19,90"`) a chybu ve formuláři.

**Postup:**
1. `curl https://diamondfish.cz/php-test/api/products/4 ...` – `"price": "19,90"` (string!)
2. V JS: `parseFloat("19,90")` vrátí `19`, ne `19.9` – otevři DevTools Console a vyzkoušej
3. Na stránce `/pages/products.php` – cena produktu ID=4 se zobrazí jako `"19,90" Kč` (řetězec)
4. API vrátí `422` s chybou: **"price obsahuje desetinnou čárku místo tečky"**

**Co nahlásit:**
- data/products.json: ID=4 `price="19,90"`, ID=16 `price="8,50"` – chybný datový typ
- JS: `parseFloat("19,90") === 19` – způsobuje chybný výpočet na frontendu
- Formulář: pole pro cenu je `type="text"` – nebrání zadání čárky, mělo by být `type="number"` nebo mít validaci

---

### Scénář 5 – Validace formuláře

**Cíl:** Otestovat chování formuláře při neplatných vstupech.

**Postup – stránka `/pages/user-form.php`:**

#### Bug A: Readonly pole (username)
1. Klikni na pole **Username** – nelze do něj psát (readonly)
2. Vizuálně vypadá stejně jako ostatní editovatelná pole – žádné šedé pozadí, žádný kurzor
3. Zkus odeslat formulář – username se odešle s výchozí hodnotou `auto-generated-user`

#### Bug B: Slabá validace emailu
1. Zadej email `test@` – formulář to **přijme** (obsahuje `@`)
2. Zadej email `a@b` – formulář to **přijme** (ale `filter_var` na backendu to odmítne)
3. Zadej email `testbezavinace.cz` – formulář to odmítne (OK)
4. Ověř: API POST `/api/users` odmítne `test@missing` s HTTP 422

#### Bug C: Telefon bez validace
1. Do pole Telefon zadej `abc123xyz` – formulář to přijme bez chyby
2. Zadej prázdný řetězec – také přijme
3. Nahlásit: chybí validace formátu telefonu

#### Bug D: Role select ignorovaná backendem
1. Nastav Role na **Administrátor** a odešli formulář
2. V odpovědi uvidíš `"role": "user"` – backend vždy nastaví `user`
3. Ověř přes cURL: `curl -X POST https://diamondfish.cz/php-test/api/users ...` → response `"role":"user"`

#### Bug E: Shoda hesel jen na JS, ne na backendu
1. Zadej různá hesla – JS zobrazí chybu, formulář nejde odeslat
2. Přes cURL odešli POST `/api/users` bez pole `password2` – backend hesla neporovnává
3. Nahlásit: chybí server-side porovnání hesel

---

### Scénář 6 – Responsivita

**Cíl:** Ověřit chování stránek na různých šířkách obrazovky.

**Postup:**
1. Otevři DevTools → Device toolbar (Ctrl+Shift+M)
2. Nastav šířku na **375px** (iPhone SE)
3. Otevři `/pages/products.php`
   - **Bug:** Tabulka produktů přetéká přes viewport – chybí `.table-responsive` wrapper
   - Není možné horizontálně scrollovat v tabulce
4. Otevři `/pages/scroll.php`
   - Porovnej **Tabulka A** (bez `table-responsive`) vs **Tabulka B** (s `table-responsive`)
   - Na mobilu Tabulka A přetéká, Tabulka B lze scrollovat
5. Na `/pages/product-detail.php` – formulář se správně přizpůsobuje (Bootstrap grid)
6. Otestuj breakpointy: 375px (XS), 576px (SM), 768px (MD), 992px (LG), 1200px (XL)

**Co nahlásit:**
- `/pages/products.php`: Tabulka nemá `.table-responsive` – přetéká na mobilech
- Navigační menu: hamburger menu funguje správně? Klikni a ověř

---

### Scénář 7 – Scrollování

**Cíl:** Ověřit UX při scrollování a velké množství dat.

**Postup:**
1. Otevři `/pages/scroll.php`
2. Klikni **"Načíst všech 100 řádků najednou"**
   - API vrátí 20 produktů × 5 duplikátů = 100 řádků bez paginace
   - Sleduj výkon: zasekne se stránka? (Performance tab v DevTools)
   - Scroll na konec stránky – objeví se **Back to top** tlačítko?
3. Klikni **"Toggle sticky hlavička"** a scrolluj – header tabulky zůstane fixní
4. Klikni **"Načíst po stránkách"** – data se načítají postupně po 5 položkách
5. Ověř, že paginace správně přepíná stránky

**Co nahlásit:**
- Načtení 100 řádků najednou bez virtuálního scrollu – potenciální performance issue
- Card grid: karty nemají fixní `min-height` – různě vysoké podle délky textu (vizuální nekonzistence)
- Back-to-top tlačítko: funguje smooth scroll?

---

### Scénář 8 – Backend chyby (500, 404, 401, 403)

**Cíl:** Ověřit správné zpracování a zobrazení HTTP chybových stavů.

**Postup – stránka `/pages/errors.php`:**

1. Klikni na tlačítko **"Spustit 500"**
   - Ověř: HTTP status je 500 v DevTools → Network
   - Ověř: odpověď má `{"success": false, "message": "..."}`
   - Ověř: UI zobrazí chybový alert (ne prázdnou stránku)

2. Klikni **"Spustit 404"**, **"Spustit 401"**, **"Spustit 403"** – ověř HTTP status + JSON strukturu

3. Testuj autorizaci – nastav různé tokeny:
   - Bez tokenu → GET `/api/users` vrátí 401
   - Neplatný token → 401
   - User token + DELETE → 403 (nedostatečná oprávnění)
   - Admin token + DELETE neexistujícího → 404

**cURL ověření:**
```bash
BASE="https://diamondfish.cz/php-test/api"
curl "$BASE/status/500" ...
curl "$BASE/users" ...
curl "$BASE/users" ...
curl -X DELETE "$BASE/products/1" ...
curl -X DELETE "$BASE/products/1" ...
```

**Co nahlásit:**
- Všechny error responses musí mít `"success": false` a neprázdný `"message"`
- HTTP status kód musí sedět (500 → 500, ne 200)
- Frontend nesmí zobrazit raw PHP error – pouze JSON

---

### Scénář 9 – Práce s konzolí, cURL, SSH, Gitem

**Cíl:** Prověřit technické dovednosti testera.

#### Konzole (DevTools)
```javascript
// Otevři stránku /pages/errors.php a v konzoli:
fetch('/api/products/1').then(r => r.json()).then(console.log)

// Autentizovaný request přes konzoli
const token = localStorage.getItem('auth_token');
fetch('/api/users', {
  headers: { 'Authorization': 'Bearer ' + token }
}).then(r => r.json()).then(console.table)

console.table([{id:1, price:10, type:'int'}, {id:4, price:'19,90', type:'string'}])
```

**Úkol:** Načti seznam produktů přes konzoli, najdi všechny s `typeof price !== 'number'`

#### cURL
```bash
BASE="https://diamondfish.cz/php-test/api"

# Přihlášení a získání tokenu
curl -X POST "$BASE/auth/login" ...

# Autentizovaný GET
curl "$BASE/users" ...

# POST produkt
curl -X POST "$BASE/products" ...

# Verbose výstup (hlavičky, status)
curl "$BASE/status/401" ...
```

#### SSH
```bash
ssh user@server.cz
ssh -i ~/.ssh/id_rsa user@server.cz -p 2222
scp soubor.json user@server.cz:/var/www/test/data/
```

#### Git
```bash
git clone <repo-url>
git checkout -b feature/oprava-ceny
git add data/products.json
git commit -m "fix: oprava datoveho typu price u produktu ID=1 (integer → float)"
git push origin feature/oprava-ceny

git log --oneline -5
git diff HEAD~1 data/products.json
git show HEAD:data/products.json | python3 -m json.tool | grep '"price"'
```

**Úkol:** Vytvoř branch `bugfix/price-types`, oprav data v `products.json` (změň `10` na `10.2`, `"19,90"` na `19.90`, `"8,50"` na `8.50`) a commitni s popisnou commit message.

---

## Spuštění automatizovaných testů

> `tests/` je v `.gitignore` – soubory jsou přítomny lokálně, ale nejdou do repozitáře.

```bash
# Nastav URL svého prostředí (výchozí: https://diamondfish.cz/php-test)
export BASE_URL=https://diamondfish.cz/php-test

# Spusť všechny testy
php tests/run_all.php

# Nebo jednotlivé sady
php tests/test_api_q.php      # q={} syntaxe
php tests/test_products.php   # produkty, datové typy
php tests/test_users.php      # uživatelé, auth, login endpoint
php tests/test_enums.php      # číselníky
php tests/test_errors.php     # HTTP chyby, auth
```

### Interpretace výsledků

- `✓` zelený = test prošel – chování je správné
- `✗` červený = test selhal – **záměrný bug byl detekován** nebo skutečná chyba

Testy označené `DETEKCE BUGU:` jsou záměrně nastaveny tak, aby selhaly na vestavěných chybách.
Tester by měl bugy **opravit** (v JSON datech nebo kódu) a pak testy spustit znovu – **všechna `✗` se změní na `✓`**.

---

## Přehled záměrných bugů

| ID  | Soubor                | Typ chyby          | Popis                                                       |
|-----|-----------------------|--------------------|-------------------------------------------------------------|
| B1  | `data/products.json`  | Datový typ         | `ID=1: price=10` (integer) – spec říká `10.2` (float)       |
| B2  | `data/products.json`  | Desetinná čárka    | `ID=4: price="19,90"` – string s čárkou místo tečky         |
| B3  | `data/products.json`  | Desetinná čárka    | `ID=16: price="8,50"` – string s čárkou místo tečky         |
| B4  | `data/products.json`  | Null handling      | `ID=7: description=null` – frontend musí ošetřit null       |
| B5  | `data/products.json`  | Edge case          | `ID=10: price=0` – musí se zobrazit jako "0 Kč", ne prázdně |
| B6  | `data/users.json`     | Neplatné emaily    | `ID=4: "tomas.kral@"`, `ID=5: "lucie@free"`, `ID=8: "test"` |
| B7  | `pages/products.php`  | Responsivita       | Tabulka bez `.table-responsive` – přetéká na mobilu         |
| B8  | `pages/user-form.php` | UX / accessibility | Pole `username` je readonly bez vizuálního rozlišení        |
| B9  | `pages/user-form.php` | Validace           | Email validace jen dle `@` – přijme `test@` nebo `a@b`      |
| B10 | `pages/user-form.php` | Security / UX      | Role select je zobrazen, ale backend ho vždy ignoruje       |
| B11 | `pages/user-form.php` | Bezpečnost         | Shoda hesel se ověřuje jen na JS, ne na backendu            |
| B12 | `pages/scroll.php`    | Performance        | 100 řádků bez virtuálního scrollu, načtení najednou         |
| B13 | `api/index.php`       | q={} limit filtr   | Kombinace gte+lte přes jedno pole přepíše první podmínku    |
| B14 | `pages/products.php`  | Cena jako string   | `parseFloat("19,90") === 19` v JS – chybné zobrazení ceny   |

---

## Hodnotící kritéria

| Oblast                          | Body | Popis                                                              |
|---------------------------------|------|--------------------------------------------------------------------|
| Login & Bearer token            |  10  | Přihlášení, token v localStorage, autentizovaný request            |
| q={} syntaxe – základní         |  10  | Správné sestavení a odeslání dotazu přes formulář                  |
| q={} syntaxe – pokročilé        |  10  | Operátory (contains, start, end, gte, lte, in, isnull)             |
| GET / SET – produkty            |  10  | Čtení a zápis přes API, správná struktura odpovědi                 |
| Decimal integer bug             |  10  | Detekce, popis a oprava B1 (price=10 → 10.2)                       |
| Decimal comma bug               |  10  | Detekce, popis a oprava B2+B3 (čárka → tečka)                      |
| Validace formuláře              |  10  | Nalezení B8–B11 a navržení správného řešení                        |
| Responsivita                    |  10  | Detekce B7, porovnání Tabulka A vs B, breakpointy                  |
| Scroll / Performance            |   5  | Popis B12, návrh řešení (paginace, virtual scroll)                 |
| HTTP chyby – frontend           |  10  | Správné zobrazení 500/404/401/403 v UI                             |
| cURL & konzole                  |  10  | Praktická demonstrace GET/POST/DELETE s tokeny                     |
| SSH                             |   5  | Znalost základních příkazů                                         |
| Git                             |  10  | Branch, commit, push s popisnou message                            |
| **Celkem**                      | **120** |                                                                 |
