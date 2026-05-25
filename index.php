<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/components/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="display-5"><i class="bi bi-bug-fill text-warning"></i> Tester Exam</h1>
        <p class="lead text-muted">
            Testovací prostředí pro ověření znalostí nového QA testera.<br>
            Projdi níže uvedené scénáře, zdokumentuj nalezené bugy a dokaž práci s konzolí, cURL, SSH a Gitem.
        </p>
    </div>
</div>

<!-- ── Přehled stránek ───────────────────────────────────────────────────────── -->
<div class="row g-4 mb-5">
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-box-seam text-primary"></i> Produkty</h5>
                <p class="card-text text-muted small">
                    Filtrování přes <code>q={}</code> syntaxi, řazení, stránkování.
                    Obsahuje záměrné chyby v datových typech cen.
                </p>
            </div>
            <div class="card-footer">
                <a href="pages/products.php" class="btn btn-primary btn-sm w-100">Otevřít</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-success">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-fill text-success"></i> Uživatelský formulář</h5>
                <p class="card-text text-muted small">
                    Registrace, validace emailu, readonly pole, chyby serveru při neplatných datech.
                </p>
            </div>
            <div class="card-footer">
                <a href="pages/user-form.php" class="btn btn-success btn-sm w-100">Otevřít</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-danger">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-exclamation-octagon text-danger"></i> HTTP chyby</h5>
                <p class="card-text text-muted small">
                    Testování 500, 404, 401, 403. cURL příklady, konzole, SSH &amp; Git reference.
                </p>
            </div>
            <div class="card-footer">
                <a href="pages/errors.php" class="btn btn-danger btn-sm w-100">Otevřít</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-arrows-expand text-warning"></i> Scroll &amp; Responsive</h5>
                <p class="card-text text-muted small">
                    Tabulka bez <code>table-responsive</code>, long-list bez paginace, sticky header, back-to-top.
                </p>
            </div>
            <div class="card-footer">
                <a href="pages/scroll.php" class="btn btn-warning btn-sm w-100">Otevřít</a>
            </div>
        </div>
    </div>
</div>

<!-- ── Rychlý přehled API endpointů ─────────────────────────────────────────── -->
<h4><i class="bi bi-plug"></i> API přehled</h4>
<div class="table-responsive mb-5">
<table class="table table-sm table-bordered">
    <thead class="table-dark">
        <tr><th>Metoda</th><th>Endpoint</th><th>Auth</th><th>Popis</th></tr>
    </thead>
    <tbody>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/products</code></td>               <td>–</td>          <td>Seznam produktů, podpora q={}, sort, page, limit</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/products/{id}</code></td>           <td>–</td>          <td>Jeden produkt</td></tr>
        <tr><td><span class="badge bg-primary">POST</span></td> <td><code>/products</code></td>               <td>user</td>       <td>Vytvořit produkt (validuje cenu)</td></tr>
        <tr><td><span class="badge bg-warning text-dark">PUT</span></td>  <td><code>/products/{id}</code></td><td>user</td>       <td>Upravit produkt</td></tr>
        <tr><td><span class="badge bg-danger">DELETE</span></td><td><code>/products/{id}</code></td>          <td>admin</td>      <td>Smazat produkt</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/enums</code></td>                  <td>–</td>          <td>Všechny číselníky</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/enums/{type}</code></td>           <td>–</td>          <td>Konkrétní číselník (categories, statuses, colors, …)</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/users</code></td>                  <td>user</td>       <td>Seznam uživatelů</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/users/{id}</code></td>             <td>user</td>       <td>Uživatel (vlastní) / admin (libovolný)</td></tr>
        <tr><td><span class="badge bg-primary">POST</span></td> <td><code>/users</code></td>                  <td>–</td>          <td>Registrace (validuje email, username)</td></tr>
        <tr><td><span class="badge bg-warning text-dark">PUT</span></td>  <td><code>/users/{id}</code></td>   <td>user/admin</td> <td>Upravit uživatele</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/status/500</code></td>             <td>–</td>          <td>Simulace 500</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/status/404</code></td>             <td>–</td>          <td>Simulace 404</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/status/401</code></td>             <td>–</td>          <td>Simulace 401</td></tr>
        <tr><td><span class="badge bg-success">GET</span></td>  <td><code>/status/403</code></td>             <td>–</td>          <td>Simulace 403</td></tr>
    </tbody>
</table>
</div>

<!-- ── Live stav API ─────────────────────────────────────────────────────────── -->
<h4><i class="bi bi-activity"></i> Live stav API</h4>
<div id="api-status" class="row g-2 mb-4">
    <div class="col-auto"><span class="badge bg-secondary">Načítám…</span></div>
</div>

<script>

async function checkApi() {
    const checks = [
        { label: 'GET /products',       url: API_BASE + '/products?limit=1' },
        { label: 'GET /enums',          url: API_BASE + '/enums' },
        { label: 'GET /users (no auth)', url: API_BASE + '/users' },
        { label: 'GET /status/500',     url: API_BASE + '/status/500' },
    ];

    const container = document.getElementById('api-status');
    container.innerHTML = '';

    for (const c of checks) {
        const r    = await fetch(c.url).catch(() => ({ status: 0 }));
        const ok   = r.status >= 200 && r.status < 500;
        const cls  = r.status === 200 ? 'success' : r.status === 0 ? 'dark' : 'warning text-dark';
        container.innerHTML += `<div class="col-auto">
            <span class="badge bg-${cls}">${c.label} → ${r.status || 'ERR'}</span>
        </div>`;
    }
}

checkApi();
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
