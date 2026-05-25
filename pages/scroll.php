<?php
$pageTitle = 'Scroll & Responsivita';
require_once __DIR__ . '/../components/header.php';
?>

<h2><i class="bi bi-arrows-expand"></i> Scroll & Responsivita</h2>
<p class="text-muted">Otestuj chování stránky při scrollování a na různých šířkách okna (320px, 768px, 1200px).</p>

<div id="alert-container"></div>

<!-- ── Ovládací prvky ────────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <button class="btn btn-sm btn-outline-primary" onclick="loadAll()">
            <i class="bi bi-arrow-repeat"></i> Načíst všech 100 řádků najednou (bez paginace)
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="loadPaged()">
            <i class="bi bi-journal-text"></i> Načíst po stránkách (limit=5)
        </button>
        <button class="btn btn-sm btn-outline-warning" onclick="toggleStickyHeader()">
            <i class="bi bi-pin-angle"></i> Toggle sticky hlavička
        </button>
        <span class="text-muted small ms-2">Šířka okna: <strong id="win-width"></strong>px</span>
    </div>
</div>

<!-- ── BUG: Tabulka bez .table-responsive – na mobilu přeteče ───────────────── -->
<h5>Tabulka A – <span class="text-danger">bez</span> <code>.table-responsive</code> (záměrný bug)</h5>
<p class="text-muted small">Na úzkém okně (&lt;576px) tato tabulka přeteče mimo viewport bez horizontálního scrollbaru.</p>

<table class="table table-sm table-bordered table-hover" id="table-no-responsive">
    <thead class="table-dark" id="sticky-header">
        <tr>
            <th>#</th>
            <th>Název produktu – velmi dlouhý nadpis sloupce</th>
            <th>SKU – unikátní identifikátor</th>
            <th>Cena (Kč)</th>
            <th>Kategorie</th>
            <th>Status</th>
            <th>Sklad</th>
            <th>Hmotnost</th>
            <th>Vytvořeno</th>
            <th>Popis produktu – tento sloupec má záměrně dlouhý obsah bez zkrácení</th>
        </tr>
    </thead>
    <tbody id="tbody-all">
        <tr><td colspan="10" class="text-center">Klikni na tlačítko „Načíst všech 100 řádků"</td></tr>
    </tbody>
</table>

<hr>

<!-- ── Tabulka B – správně responsive ───────────────────────────────────────── -->
<h5>Tabulka B – <span class="text-success">s</span> <code>.table-responsive</code> (referenční)</h5>
<p class="text-muted small">Stejná data, ale správně zabalená. Na mobilu se dá scrollovat horizontálně.</p>

<div class="table-responsive">
<table class="table table-sm table-bordered table-hover" id="table-responsive">
    <thead class="table-secondary">
        <tr>
            <th>#</th>
            <th>Název</th>
            <th>SKU</th>
            <th>Cena (Kč)</th>
            <th>Kategorie</th>
            <th>Status</th>
            <th>Sklad</th>
            <th>Hmotnost</th>
            <th>Vytvořeno</th>
            <th>Popis</th>
        </tr>
    </thead>
    <tbody id="tbody-paged">
        <tr><td colspan="10" class="text-center">Klikni na tlačítko „Načíst po stránkách"</td></tr>
    </tbody>
</table>
</div>

<!-- ── Paginace pro tabulku B ────────────────────────────────────────────────── -->
<nav id="paged-nav" class="mt-2" style="display:none">
    <ul class="pagination pagination-sm justify-content-center" id="paged-pagination"></ul>
</nav>

<!-- ── Back to top tlačítko ─────────────────────────────────────────────────── -->
<button id="back-to-top"
        class="btn btn-dark btn-sm position-fixed"
        style="bottom:20px;right:20px;display:none;z-index:999"
        onclick="window.scrollTo({top:0,behavior:'smooth'})">
    ↑ Nahoru
</button>

<!-- ── Responzivita card grid ────────────────────────────────────────────────── -->
<hr class="mt-5">
<h5>Card grid – responzivní layout</h5>
<p class="text-muted small">Bootstrap breakpointy: 1 sloupec na XS, 2 na SM, 3 na MD, 4 na LG.</p>
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-5" id="card-grid">
    <!-- BUG: karty nemají min-height nastavený, takže při různých délkách textů jsou nerovnoměrné -->
</div>

<script>
let allData  = [];
let pagedPage = 1;
const pagedLimit = 5;

window.addEventListener('resize', updateWidth);
window.addEventListener('scroll', () => {
    document.getElementById('back-to-top').style.display = window.scrollY > 300 ? '' : 'none';
});
updateWidth();

function updateWidth() {
    document.getElementById('win-width').textContent = window.innerWidth;
}

function rowHtml(p, i) {
    return `<tr>
        <td>${i + 1}</td>
        <td style="min-width:180px">${p.name}</td>
        <td><code>${p.sku}</code></td>
        <td class="text-end">${p.price} Kč</td>
        <td>${p.category}</td>
        <td>${p.status}</td>
        <td class="text-center">${p.stock_quantity}</td>
        <td class="text-end">${p.weight} kg</td>
        <td>${p.created_at}</td>
        <td style="min-width:300px">${p.description ?? '–'} Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</td>
    </tr>`;
}

// ── Načtení všech bez paginace ───────────────────────────────────────────────
async function loadAll() {
    // BUG: limit=100 načte vše najednou – bez virtuálního scrollu nebo paginace
    const r = await fetch(API_BASE + '/products?limit=100');
    const d = await r.json();
    allData  = d.data || [];
    // Generujeme 5× duplikáty pro simulaci dlouhého seznamu
    const expanded = [];
    for (let rep = 0; rep < 5; rep++) allData.forEach(p => expanded.push({...p, _rep: rep}));
    allData = expanded;

    const tbody = document.getElementById('tbody-all');
    tbody.innerHTML = allData.map((p, i) => rowHtml(p, i)).join('');
    showAlert('Načteno ' + allData.length + ' řádků najednou bez paginace – zkus scrollovat a měř výkon.', 'warning');

    // Karty
    renderCards(allData.slice(0, 12));
}

// ── Načtení po stránkách ─────────────────────────────────────────────────────
async function loadPaged(page = 1) {
    pagedPage = page;
    const r = await fetch(API_BASE + '/products?page=' + page + '&limit=' + pagedLimit);
    const d = await r.json();
    const items = d.data || [];
    const meta  = d.meta || {};

    const tbody = document.getElementById('tbody-paged');
    tbody.innerHTML = items.map((p, i) => rowHtml(p, (page - 1) * pagedLimit + i)).join('');

    // Paginace
    document.getElementById('paged-nav').style.display = '';
    let html = '';
    for (let i = 1; i <= (meta.pages || 1); i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadPaged(${i});return false">${i}</a></li>`;
    }
    document.getElementById('paged-pagination').innerHTML = html;

    if (!allData.length) renderCards(items);
}

function renderCards(items) {
    const grid = document.getElementById('card-grid');
    grid.innerHTML = items.map(p => `
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">${p.name}</h6>
                    <p class="card-text small text-muted">${p.description ?? 'Popis není k dispozici.'}</p>
                </div>
                <div class="card-footer d-flex justify-content-between small">
                    <span>${p.price} Kč</span>
                    <span class="badge bg-secondary">${p.category}</span>
                </div>
            </div>
        </div>`).join('');
}

let stickyEnabled = false;
function toggleStickyHeader() {
    stickyEnabled = !stickyEnabled;
    const th = document.getElementById('sticky-header');
    if (stickyEnabled) {
        th.style.position = 'sticky';
        th.style.top      = '56px';
        th.style.zIndex   = '10';
        showAlert('Sticky hlavička zapnuta. Zkus scrollovat.', 'info');
    } else {
        th.style.position = '';
        th.style.top      = '';
        showAlert('Sticky hlavička vypnuta.', 'secondary');
    }
}

loadPaged(1);
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
