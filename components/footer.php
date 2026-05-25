</div><!-- /container-fluid -->

<footer class="bg-dark text-secondary text-center py-3 mt-5 small">
    Tester Exam &copy; <?= date('Y') ?> &nbsp;|&nbsp;
    PHP <?= PHP_VERSION ?> &nbsp;|&nbsp;
    <a href="#top" class="text-secondary">↑ Nahoru</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Globální pomocné funkce dostupné na každé stránce

/** Vypíše JSON objekt do #response-box nebo jiného elementu */
function showResponse(data, elementId = 'response-box') {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = JSON.stringify(data, null, 2);
    }
    console.log('[API response]', data);
}

/** Vrátí záhlaví s Bearer tokenem (čte z localStorage, nebo přijme explicitní token) */
function authHeaders(token) {
    const t = token || AUTH_TOKEN();
    const h = { 'Content-Type': 'application/json' };
    if (t) h['Authorization'] = 'Bearer ' + t;
    return h;
}

/** Zobrazí alert nad tabulkou / formulářem */
function showAlert(msg, type = 'danger', containerId = 'alert-container') {
    const c = document.getElementById(containerId);
    if (!c) return;
    c.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
}
</script>
</body>
</html>
