<?php
$pageTitle = 'HTTP chyby & konzole';
require_once __DIR__ . '/../components/header.php';
?>

<h2><i class="bi bi-exclamation-triangle"></i> Testování HTTP chyb & nástrojů</h2>
<p class="text-muted">Každé tlačítko zavolá API endpoint a zobrazí odpověď. Sleduj také záložku <strong>Network</strong> a <strong>Console</strong> v DevTools.</p>

<div id="alert-container"></div>

<!-- ── Simulace HTTP statusů ─────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-danger">500</h1>
                <p class="text-muted small">Internal Server Error</p>
                <button class="btn btn-outline-danger w-100" onclick="callStatus(500)">
                    Spustit 500
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-warning">404</h1>
                <p class="text-muted small">Not Found</p>
                <button class="btn btn-outline-warning w-100" onclick="callStatus(404)">
                    Spustit 404
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-secondary h-100">
            <div class="card-body text-center">
                <h1 class="display-4 text-secondary">401</h1>
                <p class="text-muted small">Unauthorized</p>
                <button class="btn btn-outline-secondary w-100" onclick="callStatus(401)">
                    Spustit 401
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-dark h-100">
            <div class="card-body text-center">
                <h1 class="display-4">403</h1>
                <p class="text-muted small">Forbidden</p>
                <button class="btn btn-outline-dark w-100" onclick="callStatus(403)">
                    Spustit 403
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Pokročilé auth testy ──────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-shield-lock"></i> Autentizace a autorizace</div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-5">
                <label class="form-label">Bearer token</label>
                <input type="text" class="form-control form-control-sm" id="auth-token"
                       value="test-token-abc123" placeholder="Bearer token">
            </div>
            <div class="col-md-3">
                <label class="form-label">Endpoint</label>
                <select class="form-select form-select-sm" id="auth-endpoint">
                    <option value="/users">GET /users (vyžaduje auth)</option>
                    <option value="/users/1">GET /users/1 (vlastní profil)</option>
                    <option value="/users/2">GET /users/2 (cizí profil)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary btn-sm w-100" onclick="callWithAuth()">Odeslat</button>
            </div>
        </div>
        <p class="small text-muted mb-1">Platné tokeny:</p>
        <ul class="small text-muted mb-0">
            <li><code>test-token-abc123</code> → role: user (ID 2)</li>
            <li><code>admin-token-xyz456</code> → role: admin (ID 1)</li>
            <li><em>prázdný / neplatný</em> → 401</li>
        </ul>
    </div>
</div>

<!-- ── Curl příklady ─────────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-terminal"></i> curl příklady – zkus z terminálu</div>
    <div class="card-body">
        <p class="small text-muted">Uprav <code>BASE_URL</code> podle svého prostředí.</p>
        <pre class="response-box" style="max-height:none">
# Proměnná prostředí
BASE="http://localhost:8765/api"

# 1. Veřejný seznam produktů (bez autentizace)
curl "$BASE/products"

# 2. Filtrování q={} – produkty kategorie electronics
curl "$BASE/products&q=$(python3 -c "import urllib.parse; print(urllib.parse.quote('{\"category\":\"electronics\"}'))")"

# 3. GET /users – bez tokenu → 401
curl "$BASE/users"

# 4. GET /users – s platným tokenem
curl -H "Authorization: Bearer test-token-abc123" "$BASE/users"

# 5. POST /products – vytvoření produktu
curl -X POST "$BASE/products" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer test-token-abc123" \
     -d '{"name":"Bug produkt","price":"25,50"}'

# 7. DELETE /products/1 – s user tokenem → 403 (vyžaduje admin)
curl -X DELETE "$BASE/products/1" \
     -H "Authorization: Bearer admin-token-xyz456"

# 9. Neexistující produkt → 404
curl "$BASE/products/9999"

# 10. Status endpoint → 500
curl "$BASE/status/500"</pre>
    </div>
</div>

<!-- ── Console.log sekce ─────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-code-square"></i> Console & debugging</div>
    <div class="card-body">
        <p class="text-muted small">Otevři DevTools → Console a klikni na tlačítka níže. Sleduj výstupy.</p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button class="btn btn-sm btn-outline-secondary" onclick="console.log('Tester byl tady:', new Date())">console.log</button>
            <button class="btn btn-sm btn-outline-warning"   onclick="console.warn('Warning: mock data obsahují záměrné chyby!')">console.warn</button>
            <button class="btn btn-sm btn-outline-danger"    onclick="console.error('Chyba: price je integer místo float!')">console.error</button>
            <button class="btn btn-sm btn-outline-info"      onclick="console.table([{id:1,price:10,type:'integer'},{id:4,price:'19,90',type:'string'},{id:20,price:10.2,type:'float'}])">console.table (price typy)</button>
            <button class="btn btn-sm btn-outline-primary"   onclick="debugPriceTypes()">Načíst a zobrazit typy cen</button>
        </div>
        <pre class="response-box" id="console-output">// Výstup se zobrazí zde i v Console…</pre>
    </div>
</div>

<!-- ── SSH & Git sekce ──────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-git"></i> SSH & Git – orientační otázky</div>
    <div class="card-body">
        <p class="text-muted small">Tato sekce slouží jako podklady pro ústní část testu. Tester by měl znát tyto příkazy.</p>
        <div class="accordion" id="gitAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#g1">
                        Git – základní workflow
                    </button>
                </h2>
                <div id="g1" class="accordion-collapse collapse" data-bs-parent="#gitAccordion">
                    <div class="accordion-body">
                        <pre class="response-box" style="max-height:none">
git clone &lt;repo&gt;
git status
git add .
git commit -m "fix: popis opravy"
git push origin feature/moje-vetev
git log --oneline -10
git diff HEAD~1
git stash / git stash pop</pre>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#g2">
                        SSH – základní příkazy
                    </button>
                </h2>
                <div id="g2" class="accordion-collapse collapse" data-bs-parent="#gitAccordion">
                    <div class="accordion-body">
                        <pre class="response-box" style="max-height:none">
ssh user@server.cz
ssh -i ~/.ssh/id_rsa user@server.cz -p 2222
scp local.txt user@server.cz:/var/www/
ssh-keygen -t ed25519 -C "tester@firma.cz"
cat ~/.ssh/id_ed25519.pub   # zkopírovat do authorized_keys</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Raw odpověď ──────────────────────────────────────────────────────────── -->
<h5><i class="bi bi-braces"></i> Raw API odpověď</h5>
<pre class="response-box" id="response-box">// Klikni na tlačítko výše…</pre>

<script>

async function callStatus(code) {
    const r = await fetch(API_BASE + '/status/' + code);
    const d = await r.json();
    showResponse(d);
    console.log('[GET /status/' + code + '] HTTP', r.status, d);
    showAlert(
        `HTTP <strong>${r.status}</strong>: ${d.message}`,
        r.ok ? 'success' : (r.status >= 500 ? 'danger' : r.status === 401 ? 'secondary' : 'warning')
    );
}

async function callWithAuth() {
    const token    = document.getElementById('auth-token').value.trim();
    const endpoint = document.getElementById('auth-endpoint').value;

    const headers = {};
    if (token) headers['Authorization'] = 'Bearer ' + token;

    const r = await fetch(API_BASE + endpoint, { headers });
    const d = await r.json();
    showResponse(d);
    console.log('[GET ' + endpoint + '] HTTP', r.status, d);

    if (d.success) {
        showAlert('OK (HTTP ' + r.status + '): ' + JSON.stringify(d.data).substring(0, 120) + '…', 'success');
    } else {
        showAlert('HTTP ' + r.status + ': ' + d.message);
    }
}

async function debugPriceTypes() {
    const r = await fetch(API_BASE + '/products?limit=20');
    const d = await r.json();
    const items = (d.data || []).map(p => ({
        id:    p.id,
        name:  p.name,
        price: p.price,
        type:  typeof p.price,
        isInt: Number.isInteger(p.price),
    }));
    document.getElementById('console-output').textContent = JSON.stringify(items, null, 2);
    console.table(items);
    console.warn('Produkty s cenou jako INTEGER:', items.filter(i => i.isInt).map(i => i.id));
    console.warn('Produkty s cenou jako STRING:', items.filter(i => i.type === 'string').map(i => ({id: i.id, price: i.price})));
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
