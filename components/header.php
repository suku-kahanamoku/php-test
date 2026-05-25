<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Tester Exam';
$base      = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
// Pokud jsme v pages/, vystoupime o uroven vyse
if (str_ends_with($base, '/pages')) {
    $base = dirname($base);
}
$apiBase = $base . '/api';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Tester Exam</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { padding-top: 56px; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        pre.response-box {
            background: #1e1e2e;
            color: #cdd6f4;
            border-radius: 8px;
            padding: 1rem;
            font-size: .8rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .badge-active       { background-color: #198754; }
        .badge-inactive     { background-color: #6c757d; }
        .badge-discontinued { background-color: #dc3545; }
        .q-badge { font-family: monospace; font-size: .75rem; background: #e9ecef; padding: 2px 6px; border-radius: 4px; }
    </style>
    <script>
        // Globální konstanty dostupné ve všech stránkách
        const API_BASE = <?= json_encode($apiBase) ?>;
        const AUTH_TOKEN = () => localStorage.getItem('auth_token');
        const AUTH_USER  = () => { try { return JSON.parse(localStorage.getItem('auth_user')); } catch(e) { return null; } };
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $base ?>/index.php">⚙ TesterExam</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/pages/products.php">Produkty</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/pages/user-form.php">Uživatel</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/pages/errors.php">HTTP chyby</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/pages/scroll.php">Scroll / Responsive</a></li>
            </ul>
            <ul class="navbar-nav ms-auto" id="nav-auth">
                <li class="nav-item" id="nav-login-item">
                    <a class="nav-link" href="<?= $base ?>/pages/login.php"><i class="bi bi-box-arrow-in-right"></i> Přihlásit</a>
                </li>
                <li class="nav-item dropdown d-none" id="nav-user-item">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="nav-username"></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small" id="nav-user-role"></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="doLogout();return false;"><i class="bi bi-box-arrow-right"></i> Odhlásit</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
    (function() {
        const token = AUTH_TOKEN();
        const user  = AUTH_USER();
        if (token && user) {
            document.getElementById('nav-login-item').classList.add('d-none');
            const userItem = document.getElementById('nav-user-item');
            userItem.classList.remove('d-none');
            document.getElementById('nav-username').textContent = (user.first_name || user.username || 'Uživatel');
            document.getElementById('nav-user-role').textContent = 'Role: ' + (user.role || '');
        }
    })();
    function doLogout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        window.location.href = <?= json_encode($base . '/pages/login.php') ?>;
    }
</script>
<div class="container-fluid py-4">
