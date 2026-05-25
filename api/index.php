<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// ─── Pomocné funkce ───────────────────────────────────────────────────────────

function loadJson(string $file): array
{
    $path = DATA_DIR . '/' . $file;
    if (!file_exists($path)) {
        Response::serverError("Data file '{$file}' not found.");
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveJson(string $file, array $data): void
{
    file_put_contents(
        DATA_DIR . '/' . $file,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );
}

function requireAuth(Request $request): array
{
    $token = $request->bearerToken();
    if ($token === null) {
        Response::unauthorized('Chybí Authorization: Bearer <token>');
    }
    $users = loadJson('users.json');
    foreach ($users as $user) {
        if (($user['token'] ?? null) === $token) {
            return $user;
        }
    }
    Response::unauthorized('Neplatný nebo vypršelý token.');
}

function requireAdmin(Request $request): array
{
    $user = requireAuth($request);
    if ($user['role'] !== 'admin') {
        Response::forbidden('Tato akce vyžaduje roli admin.');
    }
    return $user;
}

function nextId(array $data): int
{
    return empty($data) ? 1 : (int) max(array_column($data, 'id')) + 1;
}

// ─── Validators ───────────────────────────────────────────────────────────────

function validateProduct(Request $req, bool $isCreate = true): array
{
    $errors = [];

    // name
    $name = trim((string) $req->get('name', ''));
    if ($isCreate && $name === '') {
        $errors['name'] = 'Field "name" is required.';
    } elseif ($name !== '' && strlen($name) < 2) {
        $errors['name'] = 'Field "name" must be at least 2 characters long.';
    } elseif (strlen($name) > 200) {
        $errors['name'] = 'Field "name" must not exceed 200 characters.';
    }

    // sku
    $sku = $req->get('sku');
    if ($sku !== null && $sku !== '') {
        if (strlen((string) $sku) > 50) {
            $errors['sku'] = 'Field "sku" must not exceed 50 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9\-_]+$/', (string) $sku)) {
            $errors['sku'] = 'Field "sku" may only contain letters, digits, hyphens and underscores (e.g. "WDG-001").';
        }
    }

    // price
    $price = $req->get('price');
    if ($isCreate && ($price === null || $price === '')) {
        $errors['price'] = 'Field "price" is required.';
    } elseif ($price !== null && $price !== '') {
        if (is_string($price) && str_contains($price, ',')) {
            $errors['price'] = 'Field "price" uses a decimal comma instead of a dot. Use format "10.5".';
        } elseif (!is_numeric($price)) {
            $errors['price'] = 'Field "price" must be a valid numeric value (integer or float), got: "' . $price . '".';
        } elseif ((float) $price < 0) {
            $errors['price'] = 'Field "price" must be 0 or greater.';
        }
    }

    // category
    $validCategories = ['electronics', 'gadgets', 'home', 'office', 'garden', 'sports', 'other'];
    $category        = $req->get('category');
    if ($category !== null && $category !== '' && !in_array($category, $validCategories, true)) {
        $errors['category'] = 'Field "category" must be one of: ' . implode(', ', $validCategories) . '. Got: "' . $category . '".';
    }

    // status
    $validStatuses = ['active', 'inactive', 'discontinued'];
    $status        = $req->get('status');
    if ($status !== null && $status !== '' && !in_array($status, $validStatuses, true)) {
        $errors['status'] = 'Field "status" must be one of: ' . implode(', ', $validStatuses) . '. Got: "' . $status . '".';
    }

    // stock_quantity
    $stock = $req->get('stock_quantity');
    if ($stock !== null && $stock !== '') {
        if (!is_numeric($stock)) {
            $errors['stock_quantity'] = 'Field "stock_quantity" must be a whole integer number, got: "' . $stock . '".';
        } elseif (floor((float) $stock) !== (float) $stock) {
            $errors['stock_quantity'] = 'Field "stock_quantity" must be an integer, not a float (e.g. 5, not 5.3). Got: ' . $stock . '.';
        } elseif ((int) $stock < 0) {
            $errors['stock_quantity'] = 'Field "stock_quantity" must be 0 or greater.';
        }
    }

    // color
    $validColors = ['red', 'blue', 'green', 'black', 'white', 'gray', 'brown', 'gold', 'silver'];
    $color       = $req->get('color');
    if ($color !== null && $color !== '' && !in_array($color, $validColors, true)) {
        $errors['color'] = 'Field "color" must be one of: ' . implode(', ', $validColors) . '. Got: "' . $color . '".';
    }

    // unit
    $validUnits = ['ks', 'm', 'kg', 'l'];
    $unit       = $req->get('unit');
    if ($unit !== null && $unit !== '' && !in_array($unit, $validUnits, true)) {
        $errors['unit'] = 'Field "unit" must be one of: ' . implode(', ', $validUnits) . '. Got: "' . $unit . '".';
    }

    // weight
    $weight = $req->get('weight');
    if ($weight !== null && $weight !== '') {
        if (is_string($weight) && str_contains($weight, ',')) {
            $errors['weight'] = 'Field "weight" uses a decimal comma instead of a dot. Use format "1.5".';
        } elseif (!is_numeric($weight)) {
            $errors['weight'] = 'Field "weight" must be a valid numeric value (float), got: "' . $weight . '".';
        } elseif ((float) $weight < 0) {
            $errors['weight'] = 'Field "weight" must be 0 or greater.';
        }
    }

    // description
    $description = $req->get('description');
    if ($description !== null && strlen((string) $description) > 1000) {
        $errors['description'] = 'Field "description" must not exceed 1000 characters (got ' . strlen((string) $description) . ').';
    }

    // created_at
    $createdAt = $req->get('created_at');
    if ($createdAt !== null && $createdAt !== '') {
        $dt = \DateTime::createFromFormat('Y-m-d', (string) $createdAt);
        if (!$dt || $dt->format('Y-m-d') !== (string) $createdAt) {
            $errors['created_at'] = 'Field "created_at" must be a valid date in format YYYY-MM-DD, got: "' . $createdAt . '".';
        }
    }

    return $errors;
}

function validateEnumItem(Request $req, bool $requireValue = true): array
{
    $errors = [];

    // value (required on create only)
    $value = trim((string) $req->get('value', ''));
    if ($requireValue) {
        if ($value === '') {
            $errors['value'] = 'Field "value" is required.';
        } elseif (!preg_match('/^[a-z0-9_\-]+$/', $value)) {
            $errors['value'] = 'Field "value" must be lowercase and may only contain letters (a-z), digits, underscores and hyphens.';
        } elseif (strlen($value) > 50) {
            $errors['value'] = 'Field "value" must not exceed 50 characters.';
        }
    }

    // label
    $label = trim((string) $req->get('label', ''));
    if ($label === '') {
        $errors['label'] = 'Field "label" is required.';
    } elseif (strlen($label) > 100) {
        $errors['label'] = 'Field "label" must not exceed 100 characters.';
    }

    return $errors;
}

// ─── Router ───────────────────────────────────────────────────────────────────

$request = new Request();
$router  = new Router();

// ── PRODUCTS ─────────────────────────────────────────────────────────────────

$router->get('/products', function (Request $req) {
    $all      = loadJson('products.json');
    $rawQ     = (string) $req->get('q', '');
    $filtered = Filter::apply($all, $rawQ);

    // sort
    $sort = (string) $req->get('sort', '');
    if ($sort !== '') {
        $dir   = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        usort($filtered, fn($a, $b) => $dir === 'asc'
            ? ($a[$field] ?? '') <=> ($b[$field] ?? '')
            : ($b[$field] ?? '') <=> ($a[$field] ?? ''));
    }

    $page  = max(1, (int) $req->get('page', 1));
    $limit = min(100, max(1, (int) $req->get('limit', 20)));
    $paged = Filter::paginate($filtered, $page, $limit);

    Response::successList($paged['items'], $paged['total'], $page, $limit);
});

$router->get('/products/{id}', function (Request $req, array $params) {
    $id   = (int) $params['id'];
    $all  = loadJson('products.json');
    foreach ($all as $p) {
        if ((int) $p['id'] === $id) {
            Response::success($p);
        }
    }
    Response::notFound("Produkt s ID {$id} nebyl nalezen.");
});

$router->post('/products', function (Request $req) {
    requireAuth($req);

    $errors = validateProduct($req, true);
    if (!empty($errors)) {
        Response::error('Validation failed.', 422, $errors);
    }

    // sku uniqueness check
    $all = loadJson('products.json');
    $sku = trim((string) $req->get('sku', ''));
    if ($sku !== '') {
        foreach ($all as $p) {
            if (isset($p['sku']) && $p['sku'] === $sku) {
                Response::error('Validation failed.', 422, ['sku' => 'Field "sku" must be unique. Value "' . $sku . '" is already taken.']);
            }
        }
    }

    $product = [
        'id'             => nextId($all),
        'name'           => trim((string) $req->get('name', '')),
        'sku'            => $sku,
        'price'          => (float) $req->get('price'),
        'category'       => (string) $req->get('category', 'other'),
        'status'         => (string) $req->get('status', 'active'),
        'stock_quantity' => (int)    $req->get('stock_quantity', 0),
        'description'    => $req->get('description'),
        'color'          => (string) $req->get('color', ''),
        'unit'           => (string) $req->get('unit', 'ks'),
        'weight'         => (float)  $req->get('weight', 0),
        'created_at'     => $req->get('created_at') ?? date('Y-m-d'),
    ];

    $all[] = $product;
    saveJson('products.json', $all);
    Response::created($product);
});

$router->put('/products/{id}', function (Request $req, array $params) {
    requireAuth($req);

    $id    = (int) $params['id'];
    $all   = loadJson('products.json');
    $index = null;

    foreach ($all as $i => $p) {
        if ((int) $p['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        Response::notFound("Product with ID {$id} not found.");
    }

    $errors = validateProduct($req, false);
    if (!empty($errors)) {
        Response::error('Validation failed.', 422, $errors);
    }

    // sku uniqueness check (skip current product)
    $sku = $req->get('sku');
    if ($sku !== null && $sku !== '') {
        foreach ($all as $i => $p) {
            if ($i !== $index && isset($p['sku']) && $p['sku'] === (string) $sku) {
                Response::error('Validation failed.', 422, ['sku' => 'Field "sku" must be unique. Value "' . $sku . '" is already taken.']);
            }
        }
    }

    $fields = ['name', 'sku', 'category', 'status', 'description', 'color', 'unit', 'created_at'];
    foreach ($fields as $field) {
        $val = $req->get($field);
        if ($val !== null) {
            $all[$index][$field] = $val;
        }
    }

    $price = $req->get('price');
    if ($price !== null) {
        $all[$index]['price'] = (float) $price;
    }

    $stock = $req->get('stock_quantity');
    if ($stock !== null) {
        $all[$index]['stock_quantity'] = (int) $stock;
    }

    $weight = $req->get('weight');
    if ($weight !== null) {
        $all[$index]['weight'] = (float) $weight;
    }

    saveJson('products.json', $all);
    Response::success($all[$index], 'Product updated successfully.');
});

$router->delete('/products/{id}', function (Request $req, array $params) {
    requireAdmin($req);

    $id  = (int) $params['id'];
    $all = loadJson('products.json');
    $new = array_values(array_filter($all, fn($p) => (int) $p['id'] !== $id));

    if (count($new) === count($all)) {
        Response::notFound("Produkt s ID {$id} nebyl nalezen.");
    }

    saveJson('products.json', $new);
    Response::success(null, 'Produkt smazán.');
});

// ── ENUMS ─────────────────────────────────────────────────────────────────────

$router->get('/enums', function (Request $req) {
    $all  = loadJson('enums.json');
    $rawQ = (string) $req->get('q', '');

    // Volitelné filtrování konkrétního typu (type=categories atd.)
    $type = (string) $req->get('type', '');
    if ($type !== '') {
        if (!isset($all[$type])) {
            Response::notFound("Enum typ '{$type}' neexistuje.");
        }
        Response::success($all[$type]);
    }

    Response::success($all);
});

$router->get('/enums/{type}', function (Request $req, array $params) {
    $type = $params['type'];
    $all  = loadJson('enums.json');

    if (!isset($all[$type])) {
        Response::notFound("Enum typ '{$type}' neexistuje. Dostupné: " . implode(', ', array_keys($all)));
    }

    $rawQ     = (string) $req->get('q', '');
    $items    = Filter::apply($all[$type], $rawQ);
    $page     = max(1, (int) $req->get('page', 1));
    $limit    = min(100, max(1, (int) $req->get('limit', 100)));
    $paged    = Filter::paginate($items, $page, $limit);

    Response::successList($paged['items'], $paged['total'], $page, $limit);
});

$router->post('/enums/{type}', function (Request $req, array $params) {
    requireAdmin($req);

    $validTypes = ['categories', 'statuses', 'colors', 'units', 'roles'];
    $type       = $params['type'];
    if (!in_array($type, $validTypes, true)) {
        Response::notFound('Enum type "' . $type . '" does not exist. Available: ' . implode(', ', $validTypes) . '.');
    }

    $errors = validateEnumItem($req, true);
    if (!empty($errors)) {
        Response::error('Validation failed.', 422, $errors);
    }

    $value = trim((string) $req->get('value', ''));
    $label = trim((string) $req->get('label', ''));

    $all = loadJson('enums.json');
    foreach ($all[$type] as $item) {
        if ($item['value'] === $value) {
            Response::error('Validation failed.', 422, ['value' => 'Field "value" must be unique within type "' . $type . '". Value "' . $value . '" already exists.']);
        }
    }

    $newItem    = ['value' => $value, 'label' => $label];
    $all[$type][] = $newItem;
    saveJson('enums.json', $all);
    Response::created($newItem);
});

$router->put('/enums/{type}/{value}', function (Request $req, array $params) {
    requireAdmin($req);

    $validTypes = ['categories', 'statuses', 'colors', 'units', 'roles'];
    $type       = $params['type'];
    if (!in_array($type, $validTypes, true)) {
        Response::notFound('Enum type "' . $type . '" does not exist. Available: ' . implode(', ', $validTypes) . '.');
    }

    $value = $params['value'];
    $all   = loadJson('enums.json');
    $index = null;

    foreach ($all[$type] as $i => $item) {
        if ($item['value'] === $value) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        Response::notFound('Enum value "' . $value . '" not found in type "' . $type . '".');
    }

    $errors = validateEnumItem($req, false);
    if (!empty($errors)) {
        Response::error('Validation failed.', 422, $errors);
    }

    $label                     = trim((string) $req->get('label', ''));
    $all[$type][$index]['label'] = $label;
    saveJson('enums.json', $all);
    Response::success($all[$type][$index], 'Enum item updated successfully.');
});

// ── USERS ─────────────────────────────────────────────────────────────────────

$router->get('/users', function (Request $req) {
    requireAuth($req);

    $all      = loadJson('users.json');
    $rawQ     = (string) $req->get('q', '');
    $filtered = Filter::apply($all, $rawQ);

    // Nikdy nevracet tokeny ani hesla v listu
    $filtered = array_map(fn($u) => array_diff_key($u, ['token' => '', 'password' => '']), $filtered);

    $page  = max(1, (int) $req->get('page', 1));
    $limit = min(100, max(1, (int) $req->get('limit', 20)));
    $paged = Filter::paginate($filtered, $page, $limit);

    Response::successList($paged['items'], $paged['total'], $page, $limit);
});

$router->get('/users/{id}', function (Request $req, array $params) {
    $authUser = requireAuth($req);

    $id  = (int) $params['id'];
    $all = loadJson('users.json');

    foreach ($all as $u) {
        if ((int) $u['id'] === $id) {
            // Bežný uživatel vidí jen sebe
            if ($authUser['role'] !== 'admin' && (int) $authUser['id'] !== $id) {
                Response::forbidden('Nemáte oprávnění zobrazit jiného uživatele.');
            }
            unset($u['token'], $u['password']);
            Response::success($u);
        }
    }

    Response::notFound("Uživatel s ID {$id} nebyl nalezen.");
});

$router->post('/users', function (Request $req) {
    $email    = trim((string) $req->get('email', ''));
    $username = trim((string) $req->get('username', ''));
    $password = (string) $req->get('password', '');

    $errors = [];
    if ($username === '') {
        $errors['username'] = 'Pole username je povinné.';
    }
    if ($email === '') {
        $errors['email'] = 'Pole email je povinné.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Neplatný formát emailu: '{$email}'.";
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Heslo musí mít alespoň 8 znaků.';
    }

    if (!empty($errors)) {
        Response::error('Validační chyba.', 422, $errors);
    }

    $all = loadJson('users.json');
    foreach ($all as $u) {
        if ($u['email'] === $email) {
            Response::error('Email je již zaregistrován.', 409);
        }
        if ($u['username'] === $username) {
            Response::error('Username je již zaregistrován.', 409);
        }
    }

    $user = [
        'id'         => nextId($all),
        'username'   => $username,
        'email'      => $email,
        'first_name' => (string) $req->get('first_name', ''),
        'last_name'  => (string) $req->get('last_name', ''),
        'role'       => 'user',
        'phone'      => (string) $req->get('phone', ''),
        'active'     => true,
        'password'   => $password,
        'created_at' => date('Y-m-d'),
        'token'      => null,
    ];

    $all[] = $user;
    saveJson('users.json', $all);

    unset($user['token'], $user['password']);
    Response::created($user);
});

$router->put('/users/{id}', function (Request $req, array $params) {
    $authUser = requireAuth($req);
    $id       = (int) $params['id'];

    if ($authUser['role'] !== 'admin' && (int) $authUser['id'] !== $id) {
        Response::forbidden('Nemáte oprávnění upravit jiného uživatele.');
    }

    $all   = loadJson('users.json');
    $index = null;
    foreach ($all as $i => $u) {
        if ((int) $u['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        Response::notFound("Uživatel s ID {$id} nebyl nalezen.");
    }

    $email = $req->get('email');
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::error("Neplatný formát emailu: '{$email}'.", 422, ['field' => 'email']);
    }

    foreach (['first_name', 'last_name', 'phone', 'email'] as $field) {
        $val = $req->get($field);
        if ($val !== null) {
            $all[$index][$field] = $val;
        }
    }

    // Roli smí měnit jen admin
    $role = $req->get('role');
    if ($role !== null) {
        if ($authUser['role'] !== 'admin') {
            Response::forbidden('Změnu role může provést pouze admin.');
        }
        $all[$index]['role'] = $role;
    }

    saveJson('users.json', $all);
    $result = $all[$index];
    unset($result['token'], $result['password']);
    Response::success($result, 'Uživatel aktualizován.');
});

// ── SIMULOVANÉ HTTP CHYBY ────────────────────────────────────────────────────

$router->get('/status/500', function (Request $req) {
    Response::serverError('Simulovaná interní chyba serveru. Zkontroluj logy.');
});

$router->get('/status/404', function (Request $req) {
    Response::notFound('Požadovaný zdroj nebyl nalezen.');
});

$router->get('/status/401', function (Request $req) {
    Response::unauthorized('Přístup odepřen – chybí nebo je neplatný Bearer token.');
});

$router->get('/status/403', function (Request $req) {
    Response::forbidden('Přístup odepřen – nedostatečná oprávnění.');
});

// ── AUTH ──────────────────────────────────────────────────────────────────────

$router->post('/auth/login', function (Request $req) {
    $email    = trim((string) $req->get('email', ''));
    $password = (string) $req->get('password', '');

    if ($email === '' || $password === '') {
        Response::error('Pole email a password jsou povinná.', 422);
    }

    $users = loadJson('users.json');
    $found = null;
    foreach ($users as $u) {
        if ($u['email'] === $email) {
            $found = $u;
            break;
        }
    }

    if ($found === null || ($found['password'] ?? '') !== $password) {
        Response::unauthorized('Nesprávný email nebo heslo.');
    }

    if (!($found['active'] ?? false)) {
        Response::forbidden('Účet je deaktivován.');
    }

    // Pokud uživatel nemá token, vygenerujeme ho a uložíme
    if (empty($found['token'])) {
        $found['token'] = 'tok-' . bin2hex(random_bytes(16));
        foreach ($users as &$u) {
            if ((int) $u['id'] === (int) $found['id']) {
                $u['token'] = $found['token'];
                break;
            }
        }
        unset($u);
        saveJson('users.json', $users);
    }

    $token = $found['token'];
    $user  = array_diff_key($found, array_flip(['password', 'token']));

    Response::success(['token' => $token, 'user' => $user], 'Přihlášení úspěšné.');
});

$router->post('/auth/logout', function (Request $req) {
    // Bearer token je spravován na klientovi (localStorage) – server jen potvrdí
    Response::success(null, 'Odhlášení úspěšné.');
});

$router->get('/auth/me', function (Request $req) {
    $authUser = requireAuth($req);
    $user     = array_diff_key($authUser, array_flip(['password', 'token']));
    Response::success($user);
});

// ── DISPATCH ──────────────────────────────────────────────────────────────────

$router->dispatch($request);
