<?php
$pageTitle = 'Produkty';
require_once __DIR__ . '/../components/header.php';
?>

<div id="alert-container"></div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-box-seam"></i> Seznam produktů</h2>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bi bi-plus-lg"></i> Nový produkt
    </button>
</div>

<!-- ── Filtrační formulář ──────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-funnel"></i> Filtry (q={} syntax)</span>
        <small class="text-muted">Odeslaný q parametr: <code id="q-preview">–</code></small>
    </div>
    <div class="card-body">
        <form id="filter-form" class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Název (contains)</label>
                <input type="text" class="form-control" id="f-name" placeholder="např. widget">
            </div>
            <div class="col-md-2">
                <label class="form-label">SKU (start)</label>
                <input type="text" class="form-control" id="f-sku" placeholder="např. WDG">
            </div>
            <div class="col-md-2">
                <label class="form-label">Kategorie</label>
                <select class="form-select" id="f-category">
                    <option value="">– vše –</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="f-status">
                    <option value="">– vše –</option>
                    <option value="active">Aktivní</option>
                    <option value="inactive">Neaktivní</option>
                    <option value="discontinued">Ukončeno</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Cena ≥</label>
                <input type="number" class="form-control" id="f-price-min" placeholder="0">
            </div>
            <div class="col-md-1">
                <label class="form-label">Cena ≤</label>
                <input type="number" class="form-control" id="f-price-max" placeholder="9999">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Hledat</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Tabulka produktů – BUG: není zabalena v .table-responsive → přeteče na mobilu ── -->
<table class="table table-hover table-bordered table-sm align-middle" id="products-table">
    <thead class="table-dark">
        <tr>
            <th><a href="#" class="text-white text-decoration-none" onclick="setSortAndLoad('id')">ID ↕</a></th>
            <th><a href="#" class="text-white text-decoration-none" onclick="setSortAndLoad('name')">Název ↕</a></th>
            <th>SKU</th>
            <th><a href="#" class="text-white text-decoration-none" onclick="setSortAndLoad('price')">Cena ↕</a></th>
            <th>Kategorie</th>
            <th>Status</th>
            <th>Sklad</th>
            <th>Popis</th>
            <th>Akce</th>
        </tr>
    </thead>
    <tbody id="products-tbody">
        <tr><td colspan="9" class="text-center">Načítám…</td></tr>
    </tbody>
</table>

<!-- ── Stránkování ──────────────────────────────────────────────────────────── -->
<nav>
    <ul class="pagination pagination-sm justify-content-center" id="pagination"></ul>
</nav>

<!-- ── Modal: Nový produkt ──────────────────────────────────────────────────── -->
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nový produkt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modal-alert"></div>
                <form id="create-form">
                    <div class="mb-2">
                        <label class="form-label">Název *</label>
                        <input type="text" class="form-control" id="c-name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" id="c-sku">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cena *</label>
                        <!-- BUG: type="text" místo type="number" – uživatel může zadat "10,5" -->
                        <input type="text" class="form-control" id="c-price" placeholder="např. 99.90">
                        <div class="form-text text-muted">Použijte desetinnou tečku (např. 99.90)</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kategorie</label>
                        <select class="form-select" id="c-category"></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sklad (ks)</label>
                        <input type="number" class="form-control" id="c-stock" value="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Popis</label>
                        <textarea class="form-control" id="c-desc" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Token: <input class="form-control-plaintext d-inline w-auto small" id="create-token" value="test-token-abc123" size="22"></small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                <button type="button" class="btn btn-success" onclick="createProduct()">Vytvořit</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentSort = '';
const limit = 10;

// ── Načtení enumerací ────────────────────────────────────────────────────────
async function loadEnums() {
    const r = await fetch(API_BASE + '/enums/categories');
    const d = await r.json();
    const cats = d.data || [];
    ['f-category', 'c-category'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        // Zachovat první prázdnou option u filtru
        const base = el.id === 'f-category' ? '<option value="">– vše –</option>' : '<option value="other">Ostatní</option>';
        el.innerHTML = base + cats.map(c => `<option value="${c.value}">${c.label}</option>`).join('');
    });
}

// ── Sestavení q={} ───────────────────────────────────────────────────────────
function buildQ() {
    const q = {};
    const name     = document.getElementById('f-name').value.trim();
    const sku      = document.getElementById('f-sku').value.trim();
    const category = document.getElementById('f-category').value;
    const status   = document.getElementById('f-status').value;
    const priceMin = document.getElementById('f-price-min').value;
    const priceMax = document.getElementById('f-price-max').value;

    if (name)     q.name     = { value: name,   operator: 'contains' };
    if (sku)      q.sku      = { value: sku,     operator: 'start' };
    if (category) q.category = category;          // implicit eq
    if (status)   q.status   = status;            // implicit eq
    if (priceMin) q.price    = { value: parseFloat(priceMin), operator: 'gte' };
    if (priceMax) {
        if (q.price) {
            // Kombinace gte + lte není přímo podporována jako jedno pole;
            // druhý filtr přepíše první – toto je záměrný bug pro testera
            q.price = { value: parseFloat(priceMax), operator: 'lte' };
        } else {
            q.price = { value: parseFloat(priceMax), operator: 'lte' };
        }
    }
    return q;
}

// ── Načtení produktů ─────────────────────────────────────────────────────────
async function loadProducts(page = 1) {
    currentPage = page;
    const q   = buildQ();
    const qStr = JSON.stringify(q);
    document.getElementById('q-preview').textContent = Object.keys(q).length ? qStr : '{}';

    const params = new URLSearchParams({
        page:  page,
        limit: limit,
    });
    if (Object.keys(q).length) params.set('q', qStr);
    if (currentSort)           params.set('sort', currentSort);

    const r   = await fetch(API_BASE + '/products?' + params);
    const d   = await r.json();

    renderProducts(d.data || []);
    renderPagination(d.meta || {});
}

function renderProducts(items) {
    const tbody = document.getElementById('products-tbody');
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Žádné produkty nenalezeny.</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(p => {
        // BUG: price může být integer (10) nebo string ("19,90") – zobrazí se bez normalizace
        const priceDisplay = p.price;
        const statusClass  = p.status === 'active' ? 'success' : p.status === 'discontinued' ? 'danger' : 'secondary';
        const desc = p.description ?? '<em class="text-muted">–</em>';
        return `<tr>
            <td>${p.id}</td>
            <td><a href="product-detail.php?id=${p.id}">${p.name}</a></td>
            <td><code>${p.sku}</code></td>
            <td class="text-end">${priceDisplay} Kč</td>
            <td>${p.category}</td>
            <td><span class="badge bg-${statusClass}">${p.status}</span></td>
            <td class="text-center">${p.stock_quantity}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${desc}</td>
            <td>
                <a href="product-detail.php?id=${p.id}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i>
                </a>
            </td>
        </tr>`;
    }).join('');
}

function renderPagination(meta) {
    const pages = meta.pages || 1;
    const page  = meta.page  || 1;
    const ul    = document.getElementById('pagination');

    let html = `<li class="page-item ${page <= 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadProducts(${page - 1})">«</a></li>`;
    for (let i = 1; i <= pages; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadProducts(${i})">${i}</a></li>`;
    }
    html += `<li class="page-item ${page >= pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadProducts(${page + 1})">»</a></li>`;
    html += `<li class="page-item disabled"><span class="page-link text-muted">Celkem: ${meta.total ?? '?'}</span></li>`;
    ul.innerHTML = html;
}

function setSortAndLoad(field) {
    currentSort = currentSort === field ? '-' + field : field;
    loadProducts(1);
}

// ── Vytvoření produktu ───────────────────────────────────────────────────────
async function createProduct() {
    const token = document.getElementById('create-token').value.trim();
    const body  = {
        name:           document.getElementById('c-name').value,
        sku:            document.getElementById('c-sku').value,
        price:          document.getElementById('c-price').value,   // posíláme raw string!
        category:       document.getElementById('c-category').value,
        stock_quantity: document.getElementById('c-stock').value,
        description:    document.getElementById('c-desc').value,
    };

    const r = await fetch(API_BASE + '/products', {
        method:  'POST',
        headers: authHeaders(token),
        body:    JSON.stringify(body),
    });
    const d = await r.json();
    console.log('[POST /products]', d);

    if (d.success) {
        showAlert('Produkt vytvořen: ' + d.data.name, 'success');
        bootstrap.Modal.getInstance(document.getElementById('modalCreate')).hide();
        loadProducts(1);
    } else {
        document.getElementById('modal-alert').innerHTML =
            `<div class="alert alert-danger">${d.message}<br><pre>${JSON.stringify(d.errors, null, 2)}</pre></div>`;
    }
}

document.getElementById('filter-form').addEventListener('submit', e => {
    e.preventDefault();
    loadProducts(1);
});

loadEnums();
loadProducts(1);
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
