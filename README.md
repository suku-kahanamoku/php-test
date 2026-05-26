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

| Metoda | Endpoint         | Auth       | Popis                                     |
|--------|------------------|------------|-------------------------------------------|
| GET    | `/products`      | –          | Seznam, q={}, sort, page, limit           |
| GET    | `/products/{id}` | –          | Jeden produkt                             |
| POST   | `/products`      | user/admin | Vytvořit (plná validace polí)             |
| PUT    | `/products/{id}` | user/admin | Upravit (plná validace polí)              |
| DELETE | `/products/{id}` | **admin**  | Smazat                                    |

#### Product fields & validation rules

| Field            | Type    | Required | Rules                                                              |
|------------------|---------|----------|--------------------------------------------------------------------|
| `name`           | string  | POST ✓   | min 2, max 200 characters                                         |
| `sku`            | string  | –        | max 50 chars, only `A-Za-z0-9-_`, must be unique                  |
| `price`          | float   | POST ✓   | numeric, dot as decimal separator, ≥ 0                            |
| `category`       | enum    | –        | `electronics`, `gadgets`, `home`, `office`, `garden`, `sports`, `other` |
| `status`         | enum    | –        | `active`, `inactive`, `discontinued`                              |
| `stock_quantity` | integer | –        | whole number (no float), ≥ 0                                      |
| `color`          | enum    | –        | `red`, `blue`, `green`, `black`, `white`, `gray`, `brown`, `gold`, `silver` |
| `unit`           | enum    | –        | `ks`, `m`, `kg`, `l`                                              |
| `weight`         | float   | –        | numeric, dot as decimal separator, ≥ 0                            |
| `description`    | string  | –        | max 1000 characters                                               |
| `created_at`     | date    | –        | format `YYYY-MM-DD`                                               |

### Enumerace

| Metoda | Endpoint                  | Auth      | Popis                              |
|--------|---------------------------|-----------|------------------------------------|
| GET    | `/enums`                  | –         | Všechny číselníky                  |
| GET    | `/enums/{type}`           | –         | Konkrétní číselník                 |
| POST   | `/enums/{type}`           | **admin** | Přidat novou hodnotu do číselníku  |
| PUT    | `/enums/{type}/{value}`   | **admin** | Upravit label existující hodnoty   |

Available enum types: `categories`, `statuses`, `colors`, `units`, `roles`

#### Enum POST/PUT fields & validation rules

| Field   | Type   | Required | Rules                                                              |
|---------|--------|----------|--------------------------------------------------------------------|
| `value` | string | POST ✓   | lowercase, only `a-z0-9_-`, max 50 chars, must be unique in type  |
| `label` | string | ✓        | non-empty, max 100 characters                                      |

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

### Scénář 4 – Nesoulad s desetinnými čísly (10 → 10.2)

**Cíl:** Odhalit, že produkt ID=1 má cenu uloženou jako integer `10` místo float `10.2`.

**Postup:**
1. `curl https://diamondfish.cz/php-test/api/products/1`
2. V odpovědi zkontroluj: `"price": 10` vs. specifikace `"price": 10.2`
3. Na stránce `/pages/product-detail.php?id=1` vidíš badge `typeof: number` + `isInt: true`
4. Na stránce `/pages/errors.php` klikni **"Načíst a zobrazit typy cen"** a sleduj console.table
5. Zavolej `console.table` z DevTools – ověř, které produkty mají integer cenu

**Co nahlásit:**
- Produkt ID=1: `price = 10` (integer) – dle specifikace má být `10.2` (float)
- Produkty ID=4 a ID=16: `price = "19,90"` resp. `"8,50"` (string s desetinnou čárkou) – viz Scénář 5
- Produkt ID=10: `price = 0` – edge case, ověřit, zda se zobrazuje správně (ne jako „–")

---

### Scénář 5 – Desetinná čárka místo tečky

**Cíl:** Odhalit a nahlásit produkty s cenou jako string s čárkou (`"19,90"`) a chybu ve formuláři.

**Postup:**
1. `curl https://diamondfish.cz/php-test/api/products/4` – `"price": "19,90"` (string!)
2. V JS: `parseFloat("19,90")` vrátí `19`, ne `19.9` – otevři DevTools Console a vyzkoušej
3. Na stránce `/pages/products.php` – cena produktu ID=4 se zobrazí jako `"19,90" Kč` (řetězec)
4. Na stránce `/pages/product-detail.php?id=4` – badge zobrazí `typeof: string`
5. Zkus POST nového produktu s `"price": "25,50"` (čárka)
   - Přes cURL: `curl -X POST https://diamondfish.cz/php-test/api/products ...
6. API vrátí `422` s chybou: **"price obsahuje desetinnou čárku místo tečky"**

**Co nahlásit:**
- data/products.json: ID=4 `price="19,90"`, ID=16 `price="8,50"` – chybný datový typ
- JS: `parseFloat("19,90") === 19` – způsobuje chybný výpočet na frontendu
- Formulář: pole pro cenu je `type="text"` – nebrání zadání čárky, mělo by být `type="number"` nebo mít validaci

---

### Scénář 6 – Validace formuláře

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
3. Ověř přes cURL: `curl -X POST https://diamondfish.cz/php-test/api/users -H "Content-Type: application/json" -d '{"role":"admin","username":"x","email":"x@test.cz","password":"heslo123"}'` → response `"role":"user"`

#### Bug E: Shoda hesel jen na JS, ne na backendu
1. Zadej různá hesla – JS zobrazí chybu, formulář nejde odeslat
2. Přes cURL odešli POST `/api/users` bez pole `password2` – backend hesla neporovnává
3. Nahlásit: chybí server-side porovnání hesel

---

### Scénář 7 – Responsivita

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

### Scénář 8 – Scrollování

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

### Scénář 9 – Backend chyby (500, 404, 401, 403)

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
curl -v "$BASE/status/500"
curl -v "$BASE/users"
curl -v -H "Authorization: Bearer test-token-abc123" "$BASE/users"
curl -v -X DELETE -H "Authorization: Bearer test-token-abc123" "$BASE/products/1"
curl -v -X DELETE -H "Authorization: Bearer admin-token-xyz456" "$BASE/products/1"
```

**Co nahlásit:**
- Všechny error responses musí mít `"success": false` a neprázdný `"message"`
- HTTP status kód musí sedět (500 → 500, ne 200)
- Frontend nesmí zobrazit raw PHP error – pouze JSON

---

## E2E testy – vinozezajeci.cz

Testovaná aplikace: **https://vinozezajeci.cz** (Nuxt SPA)

Před testováním:
- Otevři aplikaci v **anonymním okně** (čistý košík, žádné cookies)
- Otevři DevTools → Network (filtr: Fetch/XHR) a Console
- Při každém kroku sleduj síťové požadavky a případné JS chyby v konzoli

---

### E2E-1 – Přidání vína z homepage

**URL:** `https://vinozezajeci.cz/`

**Postup:**
1. Přejdi na homepage
2. Najdi sekci **Nabídka vín** (viditelná po scrollu dolů)
3. Klikni **Do košíku** u prvního vína (Müller Thurgau 2025, 190 Kč)
4. Ověř vizuální feedback – změna tlačítka, toast notifikace nebo ikona košíku
5. Klikni **Do košíku** u druhého vína (Sylvánské zelené 2025, 190 Kč)

**Co ověřit:**
- [ ] Tlačítko „Do košíku" reaguje na klik (spinner, změna textu nebo stavu)
- [ ] Ikona košíku v navigaci zobrazuje počet položek nebo zvýrazní se
- [ ] Druhý klik přidá druhé víno (košík má 2 položky), ne 2× to samé
- [ ] V Network tabu: API volání pro přidání do košíku (POST nebo PATCH)
- [ ] Žádné JS chyby v konzoli

---

### E2E-2 – Přidání vína z detailu produktu

**URL:** `https://vinozezajeci.cz/wine`

**Postup:**
1. Přejdi na stránku `/wine` (seznam všech vín)
2. Klikni na třetí víno (**Neuburské 2025**) – přejdi na detail
3. Zkontroluj detail: název, odrůda, ročník, přívlastek, objem, alkohol, cena
4. Nastav množství na **2** (pomocí `+` nebo číselného inputu)
5. Klikni **Do košíku**
6. Přejdi zpět na `/wine` a přidej ještě jedno jiné víno (klikni „Do košíku" přímo ze seznamu)

**Co ověřit:**
- [ ] Detail zobrazí všechna pole: Druh, Přívlastek, Barva, Odrůda, Objem, Ročník, Alkohol
- [ ] Množství lze změnit před přidáním do košíku (input nebo +/-)
- [ ] Po přidání 2 ks se košík správně aktualizuje (celková cena = 2 × cena vína)
- [ ] Tlačítko „Nabídka vín" (zpět) funguje

---

### E2E-3 – Správa košíku (krok 1 pokladny)

**URL:** `https://vinozezajeci.cz/cashdesk`

**Předpoklad:** V košíku jsou alespoň 3 různé položky (z E2E-1 a E2E-2).

**Postup:**
1. Přejdi na `/cashdesk` (nebo klikni na ikonu košíku)
2. Ověř, že košík zobrazuje všechny přidané položky se správnými cenami a množstvím
3. **Navýšení množství:** U prvního vína zvyš množství na 3
   - Ověř, že celková cena řádku se přepočítá (3 × cena)
   - Ověř, že celková suma objednávky se aktualizuje
4. **Snížení množství:** U téhož vína sniž množství zpět na 1
   - Ověř přepočet ceny
5. **Odstranění položky:** Klikni na tlačítko smazání (× nebo koš) u druhého vína
   - Ověř, že položka zmizí ze seznamu
   - Ověř, že celková cena je přepočítána bez odstraněné položky
6. Zkontroluj info o dopravě zdarma: „Při objednávce nad 2500 Kč je doprava zdarma"
   - Přidej víno tak, aby celková suma přesáhla 2500 Kč, a ověř, zda se stav změní

**Co ověřit:**
- [ ] Každá položka zobrazuje: název, cenu za kus, množství, cenu celkem za řádek
- [ ] Přepočet celkové sumy probíhá okamžitě (bez nutnosti refreshe)
- [ ] Odstranění položky je nenávratné (nebo nabídne undo)
- [ ] Prázdný košík zobrazí stav „košík je prázdný" (ověř po odstranění všech položek)
- [ ] Tlačítko „Zpět do obchodu" funguje

---

### E2E-4 – Vyplnění formuláře dopravy a platby (krok 2)

**Předpoklad:** Košík obsahuje alespoň 1 položku.

**Postup:**
1. V košíku klikni na **Doprava a platba** (nebo „Pokračovat")
2. Vyplň formulář kontaktních/doručovacích údajů:
   - Jméno a příjmení
   - E-mail
   - Telefon
   - Ulice a číslo popisné
   - Město
   - PSČ
3. Vyber způsob dopravy (pokud je na výběr)
4. Vyber způsob platby (pokud je na výběr)
5. Pokračuj na krok 3 (Shrnutí)

**Povinná pole – ověř chování při odeslání prázdného formuláře:**
- [ ] Jméno – povinné? Zobrazí se chybová hláška?
- [ ] E-mail – povinný? Validuje formát? (zkus `test@`, `testbezavinace`)
- [ ] Telefon – povinný? Validuje formát?
- [ ] Ulice – povinná?
- [ ] Město – povinné?
- [ ] PSČ – povinné? Validuje formát (5 číslic)?

**Typy vstupů:**
- [ ] PSČ – je pole `type="number"` nebo `type="text"`? Lze zadat písmena?
- [ ] Telefon – je pole `type="tel"`? Lze zadat nečíselné znaky?
- [ ] E-mail – je pole `type="email"`? Brání zadání neplatného formátu na úrovni HTML?

**Co nahlásit:**
- Pole bez validace (přijme libovolný text)
- Chybová hláška, která nespecifikuje, které pole je špatně
- Formulář, který při chybě ztratí již vyplněná data

---

### E2E-5 – Shrnutí objednávky (krok 3)

**Postup:**
1. Po úspěšném vyplnění formuláře přejdi na krok 3 (Shrnutí)
2. Ověř zobrazené informace:

**Kontrolní seznam:**
- [ ] Seznam objednaných vín (název, množství, cena za ks, cena celkem za řádek)
- [ ] Celková cena za zboží
- [ ] Způsob dopravy + cena dopravy
- [ ] Způsob platby
- [ ] Doručovací adresa (jméno, ulice, město, PSČ)
- [ ] Kontaktní e-mail a telefon
- [ ] Celková cena objednávky (zboží + doprava)
- [ ] Pokud suma > 2500 Kč: doprava zdarma (cena dopravy = 0 Kč)

**Regresní ověření:**
- [ ] Údaje na shrnutí přesně odpovídají tomu, co bylo zadáno ve formuláři
- [ ] Množství a ceny odpovídají košíku z kroku 1
- [ ] Po obnovení stránky (F5) shrnutí zůstane (nebo přesměruje na krok 1)

**Co nahlásit:**
- Nesoulad ceny v košíku vs. v shrnutí
- Chybějící položka na shrnutí
- Možnost přejít na shrnutí bez vyplnění formuláře (přímý URL `/cashdesk` + step param)

