<?php
$pageTitle = 'Detail produktu';
require_once __DIR__ . '/../components/header.php';
?>

<div id="alert-container"></div>

<div class="row">
    <div class="col-lg-7">
        <h2><i class="bi bi-box-seam"></i> Detail produktu <small class="text-muted fs-5" id="product-title"></small></h2>

        <div id="product-card" class="card mb-4">
            <div class="card-body">
                <p class="text-muted">Načítám…</p>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <h4><i class="bi bi-pencil-square"></i> Upravit produkt</h4>
        <div id="edit-alert"></div>
        <form id="edit-form" style="display:none">
            <div class="mb-2">
                <label class="form-label">Název</label>
                <input type="text" class="form-control" id="e-name">
            </div>
            <div class="mb-2">
                <label class="form-label">SKU</label>
                <!-- BUG: readonly, ale vizuálně nevypadá jinak -->
                <input type="text" class="form-control" id="e-sku" readonly>
                <div class="form-text">SKU nelze měnit.</div>
            </div>
            <div class="mb-2">
                <label class="form-label">Cena (Kč)</label>
                <!-- BUG: type="text" – uživatel může zadat "10,5" -->
                <input type="text" class="form-control" id="e-price">
                <div class="form-text text-muted">Aktuální hodnota z API: <code id="e-price-raw"></code></div>
            </div>
            <div class="mb-2">
                <label class="form-label">Kategorie</label>
                <select class="form-select" id="e-category"></select>
            </div>
            <div class="mb-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="e-status">
                    <option value="active">Aktivní</option>
                    <option value="inactive">Neaktivní</option>
                    <option value="discontinued">Ukončeno</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Sklad (ks)</label>
                <input type="number" class="form-control" id="e-stock">
            </div>
            <div class="mb-2">
                <label class="form-label">Popis</label>
                <textarea class="form-control" id="e-desc" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Token (Bearer)</label>
                <input type="text" class="form-control form-control-sm" id="e-token" value="test-token-abc123">
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Uložit</button>
                <button type="button" class="btn btn-outline-danger" onclick="deleteProduct()">Smazat</button>
                <a href="products.php" class="btn btn-secondary ms-auto">Zpět</a>
            </div>
        </form>
    </div>
</div>

<!-- ── Raw API odpověď ──────────────────────────────────────────────────────── -->
<h5 class="mt-4"><i class="bi bi-braces"></i> Raw API odpověď</h5>
<pre class="response-box" id="response-box"></pre>

<script>
const urlId    = new URLSearchParams(location.search).get('id');
let   productId = null;

async function loadEnums() {
    const r = await fetch(API_BASE + '/enums/categories');
    const d = await r.json();
    const el = document.getElementById('e-category');
    el.innerHTML = (d.data || []).map(c => `<option value="${c.value}">${c.label}</option>`).join('');
}

async function loadProduct() {
    if (!urlId) {
        document.getElementById('product-card').innerHTML =
            '<div class="card-body text-danger">Chybí parametr ?id= v URL.</div>';
        return;
    }

    const r = await fetch(API_BASE + '/products/' + urlId);
    const d = await r.json();
    showResponse(d);

    if (!d.success) {
        document.getElementById('product-card').innerHTML =
            `<div class="card-body text-danger">${d.message}</div>`;
        return;
    }

    const p = d.data;
    productId = p.id;

    document.getElementById('product-title').textContent = '#' + p.id + ' – ' + p.name;

    // BUG: price může být integer (10) nebo string ("19,90") – zobrazuje se nekorigovaně
    const priceType = typeof p.price;
    document.getElementById('product-card').innerHTML = `
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">ID</dt>             <dd class="col-sm-8">${p.id}</dd>
                <dt class="col-sm-4">Název</dt>          <dd class="col-sm-8">${p.name}</dd>
                <dt class="col-sm-4">SKU</dt>            <dd class="col-sm-8"><code>${p.sku}</code></dd>
                <dt class="col-sm-4">Cena</dt>
                <dd class="col-sm-8">
                    <strong>${p.price} Kč</strong>
                    <span class="badge bg-secondary ms-1">typeof: ${priceType}</span>
                </dd>
                <dt class="col-sm-4">Kategorie</dt>      <dd class="col-sm-8">${p.category}</dd>
                <dt class="col-sm-4">Status</dt>         <dd class="col-sm-8">${p.status}</dd>
                <dt class="col-sm-4">Sklad</dt>          <dd class="col-sm-8">${p.stock_quantity} ks</dd>
                <dt class="col-sm-4">Popis</dt>          <dd class="col-sm-8">${p.description ?? '<em class="text-muted">null</em>'}</dd>
                <dt class="col-sm-4">Hmotnost</dt>       <dd class="col-sm-8">${p.weight} kg</dd>
                <dt class="col-sm-4">Vytvořeno</dt>      <dd class="col-sm-8">${p.created_at}</dd>
            </dl>
        </div>`;

    // Naplnit editační formulář
    document.getElementById('e-name').value     = p.name;
    document.getElementById('e-sku').value      = p.sku;
    document.getElementById('e-price').value    = p.price;   // raw – bez formátování
    document.getElementById('e-price-raw').textContent = JSON.stringify(p.price) + ' (' + priceType + ')';
    document.getElementById('e-stock').value    = p.stock_quantity;
    document.getElementById('e-desc').value     = p.description ?? '';
    document.getElementById('e-status').value   = p.status;

    const catEl = document.getElementById('e-category');
    [...catEl.options].forEach(o => { o.selected = o.value === p.category; });

    document.getElementById('edit-form').style.display = '';
}

async function saveProduct() {
    const token = document.getElementById('e-token').value.trim();
    const body  = {
        name:           document.getElementById('e-name').value,
        price:          document.getElementById('e-price').value,  // raw string
        category:       document.getElementById('e-category').value,
        status:         document.getElementById('e-status').value,
        stock_quantity: document.getElementById('e-stock').value,
        description:    document.getElementById('e-desc').value,
    };

    const r = await fetch(API_BASE + '/products/' + productId, {
        method:  'PUT',
        headers: authHeaders(token),
        body:    JSON.stringify(body),
    });
    const d = await r.json();
    showResponse(d);
    console.log('[PUT /products/' + productId + ']', d);

    if (d.success) {
        showAlert('Uloženo!', 'success');
        loadProduct();
    } else {
        document.getElementById('edit-alert').innerHTML =
            `<div class="alert alert-danger">${d.message}<br><pre class="mt-1 mb-0">${JSON.stringify(d.errors, null, 2)}</pre></div>`;
    }
}

async function deleteProduct() {
    if (!confirm('Opravdu smazat produkt #' + productId + '?')) return;
    const token = document.getElementById('e-token').value.trim();
    const r = await fetch(API_BASE + '/products/' + productId, {
        method:  'DELETE',
        headers: authHeaders(token),
    });
    const d = await r.json();
    showResponse(d);
    console.log('[DELETE /products/' + productId + ']', d);

    if (d.success) {
        showAlert('Produkt smazán. Přesměrování…', 'warning');
        setTimeout(() => location.href = 'products.php', 1500);
    } else {
        showAlert(d.message);
    }
}

loadEnums();
loadProduct();
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
