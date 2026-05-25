<?php
$pageTitle = 'Přihlášení';
require_once __DIR__ . '/../components/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-sm-8 col-md-5 col-lg-4">
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-person-lock"></i> Přihlášení</h5>
            </div>
            <div class="card-body">
                <div id="alert-container"></div>
                <form id="login-form" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" placeholder="admin@test.cz" required>
                        <div class="form-text text-muted">
                            Hint: <code>admin@test.cz</code> / <code>Admin1234!</code><br>
                            nebo: <code>jan.novak@test.cz</code> / <code>heslo123</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Heslo</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle-pw">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="btn-login">
                            <i class="bi bi-box-arrow-in-right"></i> Přihlásit se
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header small fw-bold">Testovací scénáře – přihlášení</div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li>Přihlásit bez vyplnění polí → validační chyba</li>
                    <li>Špatné heslo → HTTP 401</li>
                    <li>Neaktivní účet (tomas.kral@) → HTTP 403</li>
                    <li>Neexistující email → HTTP 401</li>
                    <li>Úspěšné přihlášení → token v localStorage, přesměrování</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header small fw-bold">Poslední odpověď API</div>
            <div class="card-body p-0">
                <pre class="response-box m-0" id="response-box">–</pre>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('toggle-pw').addEventListener('click', function() {
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pw.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const btn      = document.getElementById('btn-login');

    if (!email || !password) {
        showAlert('Vyplňte e-mail a heslo.', 'warning');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Přihlašování…';

    try {
        const res = await fetch(API_BASE + '/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        showResponse(data);

        if (res.ok && data.success) {
            localStorage.setItem('auth_token', data.data.token);
            localStorage.setItem('auth_user',  JSON.stringify(data.data.user));
            showAlert('Přihlášení úspěšné! Přesměrování…', 'success');
            setTimeout(() => { window.location.href = API_BASE.replace('/api', '') + '/index.php'; }, 800);
        } else {
            showAlert('Chyba: ' + (data.message || res.status), 'danger');
        }
    } catch (err) {
        showAlert('Síťová chyba: ' + err.message, 'danger');
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Přihlásit se';
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
