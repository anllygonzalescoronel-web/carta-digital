<?php
require_once __DIR__ . '/includes/cliente_auth.php';
require_once __DIR__ . '/includes/functions.php';

if (clienteEstaLogueado()) {
    header('Location: cliente-dashboard.php');
    exit;
}

$nombreNegocio = cfg('nombre_negocio', 'Mi Restaurante');
$googleClientId = trim((string)cfg('google_client_id', ''));
$googleLoginActivo = cfg('google_login_activo', '0') === '1' && $googleClientId !== '';
$clientesWebActivo = cfg('clientes_web_activo', '1') === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cuenta cliente - <?= limpiar($nombreNegocio) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--p:#1f6b3a;--p2:#0f172a;--bg:#f5f8f6;--card:#ffffff;--muted:#64748b;--line:#dbe3dd}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',sans-serif;background:radial-gradient(circle at 10% 0%, rgba(31,107,58,.10) 0%, transparent 28%),radial-gradient(circle at 90% 100%, rgba(15,23,42,.08) 0%, transparent 28%),var(--bg);color:#102018}
.auth-wrap{min-height:100vh;display:grid;place-items:center;padding:18px}
.auth-card{width:min(440px,100%);background:var(--card);border:1px solid #e6ece8;border-radius:22px;padding:20px;box-shadow:0 14px 36px rgba(15,23,42,.08)}
.auth-top{text-align:center;margin-bottom:14px}
.auth-chip{display:inline-flex;padding:6px 10px;border-radius:999px;background:#ecfdf5;color:var(--p);font-size:11px;font-weight:800;letter-spacing:.08em}
.auth-top h1{font-size:24px;margin:10px 0 6px;line-height:1.08}
.auth-top p{margin:0;color:#4b6357;font-size:13px}
.tabs{display:flex;gap:8px;margin:16px 0}
.tab-btn{flex:1;border:none;border-radius:12px;background:#edf3ee;color:#3a5143;padding:11px;font-weight:800;cursor:pointer}
.tab-btn.activo{background:var(--p);color:#fff}
.form-box{display:none}
.form-box.activo{display:block}
.field{margin-bottom:12px}
.field label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#334155}
.field input{width:100%;border:1px solid var(--line);border-radius:12px;padding:12px;font-size:14px;background:#fff}
.field input:focus{outline:none;border-color:var(--p)}
.btn{width:100%;border:none;border-radius:12px;padding:12px 14px;font-size:14px;font-weight:800;cursor:pointer}
.btn-primary{background:linear-gradient(135deg,var(--p2),var(--p));color:#fff}
.sep{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:12px;margin:12px 0}
.sep::before,.sep::after{content:'';height:1px;flex:1;background:var(--line)}
.msg{display:none;border-radius:12px;padding:10px 12px;font-size:13px;margin-bottom:10px}
.msg.ok{display:block;background:#e8f8ee;color:#166534}
.msg.err{display:block;background:#feecec;color:#b42318}
.helper{font-size:12px;color:var(--muted);line-height:1.45;margin:10px 0 0}
.link-back{display:inline-flex;align-items:center;gap:7px;margin-top:14px;color:#334155;text-decoration:none;font-weight:700;font-size:13px}
.off-box{max-width:560px;margin:60px auto;background:#fff;border-radius:16px;padding:22px;border:1px solid #e5e7eb;box-shadow:0 16px 32px rgba(15,23,42,.07)}
</style>
</head>
<body>
<?php if (!$clientesWebActivo): ?>
<div class="off-box">
    <h1 style="margin-top:0">Cuentas desactivadas</h1>
    <p>El acceso de clientes todavía no está habilitado en esta carta digital.</p>
    <a href="index.php">Volver a la carta</a>
</div>
</body>
</html>
<?php exit; endif; ?>
<div class="auth-wrap">
    <section class="auth-card">
        <div class="auth-top">
            <span class="auth-chip">CUENTA CLIENTE</span>
            <h1><?= limpiar($nombreNegocio) ?></h1>
            <p>Ingresa o crea tu cuenta para ver tus pedidos y tu dashboard.</p>
        </div>

        <div class="tabs">
            <button type="button" class="tab-btn activo" data-tab="login">Ingresar</button>
            <button type="button" class="tab-btn" data-tab="registro">Crear cuenta</button>
        </div>

        <div id="msg" class="msg"></div>

        <form class="form-box activo" id="formLogin">
            <div class="field">
                <label>Correo</label>
                <input type="email" id="loginEmail" placeholder="cliente@correo.com" required>
            </div>
            <div class="field">
                <label>Contraseña</label>
                <input type="password" id="loginPassword" placeholder="Tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
            <?php if ($googleLoginActivo): ?>
            <div class="sep">o entra con Google</div>
            <div id="googleBtnWrap"></div>
            <?php endif; ?>
            <p class="helper">Tu historial y tus pedidos estarán listos en tu dashboard.</p>
        </form>

        <form class="form-box" id="formRegistro">
            <div class="field">
                <label>Nombre completo</label>
                <input type="text" id="registroNombre" placeholder="Ej. Juan Pérez" required>
            </div>
            <div class="field">
                <label>Correo</label>
                <input type="email" id="registroEmail" placeholder="cliente@correo.com" required>
            </div>
            <div class="field">
                <label>Teléfono</label>
                <input type="tel" id="registroTelefono" placeholder="987654321" required>
            </div>
            <div class="field">
                <label>Contraseña</label>
                <input type="password" id="registroPassword" placeholder="Mínimo 6 caracteres" required>
            </div>
            <button type="submit" class="btn btn-primary">Crear cuenta</button>
            <p class="helper">Si ya compraste antes, vincularemos tu historial automáticamente.</p>
        </form>
        <a class="link-back" href="index.php"><i class="fa-solid fa-arrow-left"></i> Volver a la carta</a>
    </section>
</div>
<?php if ($googleLoginActivo): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>
<script>
const GOOGLE_CLIENT_ID = <?= json_encode($googleClientId) ?>;
const GOOGLE_LOGIN_ACTIVO = <?= $googleLoginActivo ? 'true' : 'false' ?>;

function showMsg(text, type) {
    const box = document.getElementById('msg');
    box.textContent = text;
    box.className = 'msg ' + (type === 'ok' ? 'ok' : 'err');
}

async function authRequest(payload) {
    const resp = await fetch('api/cliente_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });
    return resp.json();
}

document.querySelectorAll('.tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('activo'));
        document.querySelectorAll('.form-box').forEach((f) => f.classList.remove('activo'));
        btn.classList.add('activo');
        document.getElementById(btn.dataset.tab === 'login' ? 'formLogin' : 'formRegistro').classList.add('activo');
    });
});

document.getElementById('formLogin').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = await authRequest({
        accion: 'login',
        email: document.getElementById('loginEmail').value.trim(),
        password: document.getElementById('loginPassword').value,
    });
    if (!data.ok) {
        showMsg(data.mensaje || 'No se pudo iniciar sesión.', 'err');
        return;
    }
    window.location.href = 'cliente-dashboard.php';
});

document.getElementById('formRegistro').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = await authRequest({
        accion: 'registro',
        nombre: document.getElementById('registroNombre').value.trim(),
        email: document.getElementById('registroEmail').value.trim(),
        telefono: document.getElementById('registroTelefono').value.trim(),
        password: document.getElementById('registroPassword').value,
    });
    if (!data.ok) {
        showMsg(data.mensaje || 'No se pudo crear la cuenta.', 'err');
        return;
    }
    window.location.href = 'cliente-dashboard.php';
});

window.handleGoogleCredentialResponse = async function(response) {
    const data = await authRequest({ accion: 'google', credential: response.credential });
    if (!data.ok) {
        showMsg(data.mensaje || 'No se pudo ingresar con Google.', 'err');
        return;
    }
    window.location.href = 'cliente-dashboard.php';
};

window.addEventListener('load', function() {
    if (!GOOGLE_LOGIN_ACTIVO || !window.google || !GOOGLE_CLIENT_ID) return;
    google.accounts.id.initialize({
        client_id: GOOGLE_CLIENT_ID,
        callback: handleGoogleCredentialResponse,
    });
    google.accounts.id.renderButton(document.getElementById('googleBtnWrap'), {
        theme: 'outline',
        size: 'large',
        width: '100%',
        text: 'signin_with'
    });
});
</script>
</body>
</html>