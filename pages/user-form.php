<?php
$pageTitle = 'Registrace / Editace uživatele';
require_once __DIR__ . '/../components/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-6 col-md-8">

<h2><i class="bi bi-person-plus"></i> Registrace uživatele</h2>
<p class="text-muted">Formulář pro vytvoření nového uživatele. Otestuj validaci a chování jednotlivých polí.</p>

<div id="alert-container"></div>

<form id="reg-form" novalidate>

    <div class="mb-3">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <!--
            BUG #1: pole je readonly – vizuálně se od ostatních neliší, ale nelze do něj psát.
            Tester by měl najít a nahlásit, že pole vypadá editovatelně, ale má atribut readonly.
        -->
        <input type="text" class="form-control" id="f-username"
               value="auto-generated-user" readonly
               autocomplete="username">
        <div class="form-text">Username je generováno automaticky.</div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Jméno</label>
            <input type="text" class="form-control" id="f-first-name" placeholder="Jana">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Příjmení</label>
            <input type="text" class="form-control" id="f-last-name" placeholder="Nováková">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">E-mail <span class="text-danger">*</span></label>
        <!--
            BUG #2: chybí type="email" → prohlížeč neprovede nativní validaci.
            Vlastní JS validace pouze kontroluje přítomnost "@" – přijme "a@b" jako platný email.
        -->
        <input type="text" class="form-control" id="f-email"
               placeholder="jana.novakova@firma.cz"
               autocomplete="email">
        <div id="email-feedback" class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label class="form-label">Telefon</label>
        <!--
            BUG #3: žádná validace formátu telefonu – přijme libovolný řetězec.
            Tester by měl zkusit zadat "abc", "00420123" nebo prázdný řetězec.
        -->
        <input type="text" class="form-control" id="f-phone"
               placeholder="+420 777 123 456">
        <div class="form-text">Formát: +420 XXX XXX XXX</div>
    </div>

    <div class="mb-3">
        <label class="form-label">Heslo <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="f-password"
               placeholder="Minimálně 8 znaků" autocomplete="new-password">
        <div id="pw-strength" class="form-text"></div>
    </div>

    <div class="mb-3">
        <label class="form-label">Potvrdit heslo <span class="text-danger">*</span></label>
        <!--
            BUG #4: shoda hesel se kontroluje pouze na straně JS, ale chybí server-side kontrola.
            Navíc pokud uživatel odešle form přes curl/API přímo, žádná kontrola neproběhne.
        -->
        <input type="password" class="form-control" id="f-password2"
               placeholder="Zopakujte heslo" autocomplete="new-password">
        <div id="pw-match" class="form-text"></div>
    </div>

    <div class="mb-3">
        <label class="form-label">Role</label>
        <!--
            BUG #5: select je zobrazený, ale backend vždy nastaví role="user" bez ohledu na hodnotu.
            Tester by měl ověřit, že odeslaná role se nezapíše (a nahlásit to jako nedokumentované chování).
        -->
        <select class="form-select" id="f-role">
            <option value="user">Uživatel</option>
            <option value="editor">Editor</option>
            <option value="admin">Administrátor</option>
        </select>
        <div class="form-text text-warning"><i class="bi bi-exclamation-triangle"></i> Role se nastavuje automaticky.</div>
    </div>

    <div class="d-grid gap-2 d-md-flex">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-check"></i> Registrovat
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Reset</button>
    </div>

</form>

<!-- ── Sekce: Načíst existujícího uživatele ─────────────────────────────────── -->
<hr class="my-4">
<h4><i class="bi bi-search"></i> Načíst uživatele (GET)</h4>
<p class="text-muted small">Potřebuješ platný Bearer token. Zkus různé varianty q={} filtru.</p>
<div class="input-group mb-2">
    <span class="input-group-text">Token</span>
    <input type="text" class="form-control" id="get-token" value="test-token-abc123">
</div>
<div class="input-group mb-2">
    <span class="input-group-text">q={}</span>
    <input type="text" class="form-control" id="get-q"
           placeholder='{"email":{"value":"@test.cz","operator":"end"}}'>
    <button class="btn btn-outline-primary" onclick="loadUsers()">Načíst</button>
</div>
<div id="users-result"></div>

<!-- ── Raw odpověď ──────────────────────────────────────────────────────────── -->
<h5 class="mt-4"><i class="bi bi-braces"></i> Raw API odpověď</h5>
<pre class="response-box" id="response-box"></pre>

</div><!-- /col -->
</div><!-- /row -->

<script>

// ── Jednoduchá validace emailu (BUG: jen kontroluje "@") ────────────────────
function validateEmail(email) {
    return email.includes('@');   // BUG: nevaliduje TLD, mezery, atd.
}

// ── Indikátor síly hesla ─────────────────────────────────────────────────────
document.getElementById('f-password').addEventListener('input', function () {
    const pw  = this.value;
    const el  = document.getElementById('pw-strength');
    if (!pw)           { el.textContent = ''; return; }
    if (pw.length < 8) { el.className = 'form-text text-danger'; el.textContent = 'Příliš krátké (min. 8 znaků)'; }
    else if (pw.length < 12) { el.className = 'form-text text-warning'; el.textContent = 'Střední síla'; }
    else               { el.className = 'form-text text-success'; el.textContent = 'Silné heslo ✓'; }
});

// ── Shoda hesel ──────────────────────────────────────────────────────────────
document.getElementById('f-password2').addEventListener('input', function () {
    const pw1 = document.getElementById('f-password').value;
    const el  = document.getElementById('pw-match');
    if (!this.value) { el.textContent = ''; return; }
    if (this.value === pw1) { el.className = 'form-text text-success'; el.textContent = 'Hesla se shodují ✓'; }
    else                    { el.className = 'form-text text-danger';  el.textContent = 'Hesla se neshodují!'; }
});

// ── Odeslání formuláře ───────────────────────────────────────────────────────
document.getElementById('reg-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const email = document.getElementById('f-email').value.trim();
    const pw1   = document.getElementById('f-password').value;
    const pw2   = document.getElementById('f-password2').value;

    // Validace emailu (BUG: příliš benevolentní)
    if (!validateEmail(email)) {
        document.getElementById('f-email').classList.add('is-invalid');
        document.getElementById('email-feedback').textContent = 'Neplatný email.';
        return;
    }
    document.getElementById('f-email').classList.remove('is-invalid');

    // BUG: JS kontrola shody hesel, ale server ji neověří
    if (pw1 !== pw2) {
        showAlert('Hesla se neshodují!');
        return;
    }

    const body = {
        username:   document.getElementById('f-username').value,
        first_name: document.getElementById('f-first-name').value,
        last_name:  document.getElementById('f-last-name').value,
        email:      email,
        phone:      document.getElementById('f-phone').value,
        password:   pw1,
        role:       document.getElementById('f-role').value,
    };

    const r = await fetch(API_BASE + '/users', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body),
    });
    const d = await r.json();
    showResponse(d);
    console.log('[POST /users]', d);

    if (d.success) {
        showAlert('Uživatel vytvořen! ID: ' + d.data.id + ', role: ' + d.data.role, 'success');
    } else {
        showAlert(d.message + '<br><pre class="mb-0 mt-1">' + JSON.stringify(d.errors, null, 2) + '</pre>');
    }
});

// ── Reset formuláře ──────────────────────────────────────────────────────────
function resetForm() {
    document.getElementById('reg-form').reset();
    ['pw-strength','pw-match','alert-container'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
}

// ── GET uživatelé s q={} ─────────────────────────────────────────────────────
async function loadUsers() {
    const token = document.getElementById('get-token').value.trim();
    const rawQ  = document.getElementById('get-q').value.trim();

    const params = new URLSearchParams({ limit: 50 });
    if (rawQ) params.set('q', rawQ);

    const r = await fetch(API_BASE + '/users?' + params, {
        headers: { 'Authorization': 'Bearer ' + token }
    });
    const d = await r.json();
    showResponse(d);
    console.log('[GET /users]', d);

    const container = document.getElementById('users-result');
    if (!d.success) {
        container.innerHTML = `<div class="alert alert-danger">${d.message}</div>`;
        return;
    }
    const users = d.data || [];
    if (!users.length) {
        container.innerHTML = '<p class="text-muted">Žádní uživatelé.</p>';
        return;
    }
    container.innerHTML = '<ul class="list-group">' + users.map(u =>
        `<li class="list-group-item d-flex justify-content-between align-items-center">
            <div><strong>${u.username}</strong> <span class="text-muted">${u.email}</span></div>
            <span class="badge bg-primary">${u.role}</span>
        </li>`
    ).join('') + '</ul>';
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
