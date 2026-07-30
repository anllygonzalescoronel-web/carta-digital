<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirRol(['admin']);

$tituloPagina = 'Usuarios';
$paginaActual = 'usuarios';
$db = getDB();

$mensaje = '';
$tipoMensaje = 'ok';

function contarAdminsActivos(PDO $db, ?int $excluirId = null): int {
    $sql = "SELECT COUNT(*) c FROM admin_usuarios WHERE rol = 'admin' AND activo = 1";
    $params = [];
    if ($excluirId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $excluirId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)($stmt->fetch()['c'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string)($_POST['accion'] ?? '');

    try {
        if ($accion === 'crear_usuario') {
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $usuario = trim((string)($_POST['usuario'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $rol = normalizarRolUsuario((string)($_POST['rol'] ?? 'cocinero'));
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '' || $usuario === '') {
                throw new RuntimeException('Nombre y usuario son obligatorios.');
            }
            if (strlen($password) < 6) {
                throw new RuntimeException('La contraseÃ±a debe tener al menos 6 caracteres.');
            }

            $stmt = $db->prepare('INSERT INTO admin_usuarios (nombre, usuario, password_hash, rol, activo) VALUES (:nombre, :usuario, :pass, :rol, :activo)');
            $stmt->execute([
                'nombre' => $nombre,
                'usuario' => $usuario,
                'pass' => password_hash($password, PASSWORD_BCRYPT),
                'rol' => $rol,
                'activo' => $activo,
            ]);
            $mensaje = 'Usuario creado correctamente.';
            $tipoMensaje = 'ok';
        }

        if ($accion === 'actualizar_usuario') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $usuario = trim((string)($_POST['usuario'] ?? ''));
            $rol = normalizarRolUsuario((string)($_POST['rol'] ?? 'cocinero'));
            $activo = (int)(($_POST['activo'] ?? '1') === '1');

            if ($id <= 0) {
                throw new RuntimeException('Usuario invÃ¡lido.');
            }
            if ($nombre === '' || $usuario === '') {
                throw new RuntimeException('Nombre y usuario son obligatorios.');
            }

            $stmtUser = $db->prepare('SELECT * FROM admin_usuarios WHERE id = :id LIMIT 1');
            $stmtUser->execute(['id' => $id]);
            $user = $stmtUser->fetch();
            if (!$user) {
                throw new RuntimeException('Usuario no encontrado.');
            }

            $esMismoUsuario = ((int)$_SESSION['admin_id']) === $id;
            if ($esMismoUsuario && $activo !== 1) {
                throw new RuntimeException('No puedes desactivar tu propio usuario.');
            }
            if ($esMismoUsuario && $rol !== 'admin') {
                throw new RuntimeException('No puedes quitarte el rol admin.');
            }

            $eraAdminActivo = (($user['rol'] ?? 'admin') === 'admin' && (int)($user['activo'] ?? 1) === 1);
            $dejaDeSerAdminActivo = $eraAdminActivo && !($rol === 'admin' && $activo === 1);
            if ($dejaDeSerAdminActivo && contarAdminsActivos($db, $id) <= 0) {
                throw new RuntimeException('Debe existir al menos un administrador activo.');
            }

            $stmt = $db->prepare('UPDATE admin_usuarios SET nombre = :nombre, usuario = :usuario, rol = :rol, activo = :activo WHERE id = :id');
            $stmt->execute([
                'nombre' => $nombre,
                'usuario' => $usuario,
                'rol' => $rol,
                'activo' => $activo,
                'id' => $id,
            ]);
            $mensaje = 'Usuario actualizado correctamente.';
            $tipoMensaje = 'ok';
        }

        if ($accion === 'cambiar_password') {
            $id = (int)($_POST['id'] ?? 0);
            $password = (string)($_POST['password'] ?? '');
            if ($id <= 0) {
                throw new RuntimeException('Usuario invÃ¡lido.');
            }
            if (strlen($password) < 6) {
                throw new RuntimeException('La nueva contraseÃ±a debe tener al menos 6 caracteres.');
            }

            $stmt = $db->prepare('UPDATE admin_usuarios SET password_hash = :pass WHERE id = :id');
            $stmt->execute([
                'pass' => password_hash($password, PASSWORD_BCRYPT),
                'id' => $id,
            ]);
            $mensaje = 'ContraseÃ±a actualizada correctamente.';
            $tipoMensaje = 'ok';
        }
    } catch (PDOException $e) {
        if ((string)$e->getCode() === '23000') {
            $mensaje = 'El usuario ya existe. Elige otro nombre de usuario.';
        } else {
            $mensaje = 'Error guardando datos: ' . $e->getMessage();
        }
        $tipoMensaje = 'error';
    } catch (Throwable $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = 'error';
    }
}

$usuarios = $db->query('SELECT id, nombre, usuario, rol, activo, creado_en FROM admin_usuarios ORDER BY creado_en DESC, id DESC')->fetchAll();

$totalUsuarios = count($usuarios);
$totalAdmins = 0;
$totalCocineros = 0;
$totalActivos = 0;
foreach ($usuarios as $u) {
    if (($u['rol'] ?? 'admin') === 'admin') {
        $totalAdmins++;
    } else {
        $totalCocineros++;
    }
    if ((int)($u['activo'] ?? 0) === 1) {
        $totalActivos++;
    }
}

require __DIR__ . '/_layout_top.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<?php if ($mensaje): ?>
<div class="ug-toast <?= $tipoMensaje === 'ok' ? 'ug-toast-ok' : 'ug-toast-err' ?>" id="ugToast">
    <i class="fa-solid <?= $tipoMensaje === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
    <span><?= limpiar($mensaje) ?></span>
</div>
<?php endif; ?>

<div class="ug-shell">

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• HEADER â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <header class="ug-header">
        <div class="ug-header-left">
            <div class="ug-header-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <h1>GestiÃ³n de Usuarios</h1>
                <p>Administra cuentas, roles y accesos del equipo</p>
            </div>
        </div>
        <div class="ug-counters">
            <div class="ug-counter ug-counter-total">
                <strong><?= $totalUsuarios ?></strong>
                <span>Total</span>
            </div>
            <div class="ug-counter ug-counter-admin">
                <strong><?= $totalAdmins ?></strong>
                <span>Admins</span>
            </div>
            <div class="ug-counter ug-counter-cook">
                <strong><?= $totalCocineros ?></strong>
                <span>Cocineros</span>
            </div>
            <div class="ug-counter ug-counter-active">
                <strong><?= $totalActivos ?></strong>
                <span>Activos</span>
            </div>
        </div>
    </header>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• LAYOUT 2 COL â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="ug-layout">

        <!-- SIDEBAR: nuevo usuario -->
        <aside class="ug-sidebar">
            <div class="ug-new-card">
                <div class="ug-new-card-head">
                    <div class="ug-new-card-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <h3>Nuevo usuario</h3>
                        <p>AÃ±ade un miembro al equipo</p>
                    </div>
                </div>

                <form method="POST" class="ug-form" autocomplete="off">
                    <input type="hidden" name="accion" value="crear_usuario">

                    <div class="ug-field">
                        <label><i class="fa-solid fa-id-card"></i> Nombre completo</label>
                        <input type="text" name="nombre" placeholder="Ej. Carlos RamÃ­rez" required>
                    </div>

                    <div class="ug-field">
                        <label><i class="fa-solid fa-at"></i> Nombre de usuario</label>
                        <div class="ug-input-prefix">
                            <span>@</span>
                            <input type="text" name="usuario" placeholder="cocina1" required>
                        </div>
                    </div>

                    <div class="ug-field">
                        <label><i class="fa-solid fa-lock"></i> ContraseÃ±a</label>
                        <div class="ug-input-eye">
                            <input type="password" name="password" id="newPassInput" minlength="6" placeholder="MÃ­nimo 6 caracteres" required>
                            <button type="button" class="ug-eye-btn" onclick="togglePass('newPassInput',this)"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="ug-field">
                        <label><i class="fa-solid fa-user-shield"></i> Rol</label>
                        <div class="ug-role-selector">
                            <label class="ug-role-opt">
                                <input type="radio" name="rol" value="cocinero" checked>
                                <span><i class="fa-solid fa-fire"></i> Cocinero</span>
                            </label>
                            <label class="ug-role-opt">
                                <input type="radio" name="rol" value="admin">
                                <span><i class="fa-solid fa-user-shield"></i> Admin</span>
                            </label>
                        </div>
                    </div>

                    <div class="ug-field">
                        <label class="ug-toggle-label">
                            <span><i class="fa-solid fa-power-off"></i> Activar cuenta</span>
                            <label class="ug-toggle">
                                <input type="checkbox" name="activo" checked>
                                <span class="ug-toggle-track"><span class="ug-toggle-thumb"></span></span>
                            </label>
                        </label>
                    </div>

                    <button type="submit" class="ug-btn-primary"><i class="fa-solid fa-user-plus"></i> Crear usuario</button>
                </form>
            </div>
        </aside>

        <!-- MAIN: lista de usuarios -->
        <main class="ug-main">
            <div class="ug-list-head">
                <h3><i class="fa-solid fa-users-gear"></i> Equipo <span class="ug-count-badge"><?= $totalUsuarios ?></span></h3>
                <p>Haz clic en <strong>Editar</strong> para expandir los datos de cada cuenta.</p>
            </div>

            <div class="ug-user-list">
                <?php foreach ($usuarios as $u):
                    $uid       = (int)$u['id'];
                    $nombre    = trim((string)$u['nombre']);
                    $partes    = preg_split('/\s+/', $nombre);
                    $iniciales = strtoupper(substr($partes[0] ?? 'U', 0, 1)) . strtoupper(substr($partes[1] ?? '', 0, 1));
                    $esYo      = ((int)($_SESSION['admin_id'] ?? 0)) === $uid;
                    $esAdmin   = ($u['rol'] ?? 'admin') === 'admin';
                    $esActivo  = (int)$u['activo'] === 1;
                ?>
                <div class="ug-user-item" id="ug-item-<?= $uid ?>">

                    <!-- FILA RESUMEN -->
                    <div class="ug-user-row">
                        <div class="ug-avatar ug-avatar-<?= $esAdmin ? 'admin' : 'cook' ?>"><?= limpiar($iniciales) ?></div>
                        <div class="ug-user-info">
                            <div class="ug-user-name">
                                <?= limpiar($nombre) ?>
                                <?php if ($esYo): ?><span class="ug-you-badge"><i class="fa-solid fa-star"></i> TÃº</span><?php endif; ?>
                            </div>
                            <div class="ug-user-sub">@<?= limpiar((string)$u['usuario']) ?></div>
                        </div>
                        <div class="ug-user-chips">
                            <span class="ug-chip <?= $esAdmin ? 'chip-admin' : 'chip-cook' ?>">
                                <i class="fa-solid <?= $esAdmin ? 'fa-user-shield' : 'fa-fire' ?>"></i>
                                <?= $esAdmin ? 'Admin' : 'Cocinero' ?>
                            </span>
                            <span class="ug-chip <?= $esActivo ? 'chip-on' : 'chip-off' ?>">
                                <i class="fa-solid <?= $esActivo ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <button class="ug-btn-edit" type="button" onclick="ugToggle(<?= $uid ?>)">
                            <i class="fa-solid fa-pen-to-square"></i> Editar
                        </button>
                    </div>

                    <!-- PANEL EDICIÃ“N (oculto por defecto) -->
                    <div class="ug-edit-panel" id="ug-panel-<?= $uid ?>">
                        <div class="ug-edit-grid">

                            <!-- Form actualizar datos -->
                            <form method="POST" class="ug-form ug-form-edit">
                                <input type="hidden" name="accion" value="actualizar_usuario">
                                <input type="hidden" name="id" value="<?= $uid ?>">

                                <div class="ug-edit-title"><i class="fa-solid fa-sliders"></i> Datos de la cuenta</div>

                                <div class="ug-field">
                                    <label><i class="fa-solid fa-id-card"></i> Nombre</label>
                                    <input type="text" name="nombre" value="<?= limpiar($nombre) ?>" required>
                                </div>

                                <div class="ug-field">
                                    <label><i class="fa-solid fa-at"></i> Usuario</label>
                                    <div class="ug-input-prefix">
                                        <span>@</span>
                                        <input type="text" name="usuario" value="<?= limpiar((string)$u['usuario']) ?>" required>
                                    </div>
                                </div>

                                <div class="ug-two-col">
                                    <div class="ug-field">
                                        <label><i class="fa-solid fa-user-shield"></i> Rol</label>
                                        <select name="rol">
                                            <option value="admin"    <?= $esAdmin ? 'selected' : '' ?>>Administrador</option>
                                            <option value="cocinero" <?= !$esAdmin ? 'selected' : '' ?>>Cocinero</option>
                                        </select>
                                    </div>
                                    <div class="ug-field">
                                        <label><i class="fa-solid fa-signal"></i> Estado</label>
                                        <select name="activo">
                                            <option value="1" <?= $esActivo ? 'selected' : '' ?>>Activo</option>
                                            <option value="0" <?= !$esActivo ? 'selected' : '' ?>>Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="ug-btn-primary ug-btn-sm"><i class="fa-solid fa-check"></i> Guardar cambios</button>
                            </form>

                            <!-- Form cambiar contraseÃ±a -->
                            <form method="POST" class="ug-form ug-form-edit">
                                <input type="hidden" name="accion" value="cambiar_password">
                                <input type="hidden" name="id" value="<?= $uid ?>">

                                <div class="ug-edit-title"><i class="fa-solid fa-key"></i> Nueva contraseÃ±a</div>

                                <div class="ug-field">
                                    <label><i class="fa-solid fa-lock"></i> ContraseÃ±a</label>
                                    <div class="ug-input-eye">
                                        <input type="password" name="password" id="pass-<?= $uid ?>" minlength="6" placeholder="MÃ­nimo 6 caracteres" required>
                                        <button type="button" class="ug-eye-btn" onclick="togglePass('pass-<?= $uid ?>',this)"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>

                                <p class="ug-pass-hint"><i class="fa-solid fa-circle-info"></i> Solo se aplica si completas este campo y guardas.</p>

                                <button type="submit" class="ug-btn-secondary ug-btn-sm"><i class="fa-solid fa-key"></i> Actualizar clave</button>
                            </form>

                        </div>

                        <div class="ug-edit-meta">
                            <span><i class="fa-solid fa-hashtag"></i> ID <?= $uid ?></span>
                            <span><i class="fa-regular fa-calendar"></i> <?= limpiar((string)$u['creado_en']) ?></span>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        </main>

    </div><!-- /.ug-layout -->

</div><!-- /.ug-shell -->

<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   GESTIÃ“N DE USUARIOS â€” UI/UX REFACTORED
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

.ug-shell { display: grid; gap: 20px; }

/* â”€â”€ Toast â”€â”€ */
.ug-toast {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 18px; border-radius: 12px; margin-bottom: 4px;
    font-size: 13px; font-weight: 600;
    animation: ug-fadein .3s ease;
}
.ug-toast-ok  { background: #f0fdf4; border: 1px solid #86efac; color: #15803d; }
.ug-toast-err { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
@keyframes ug-fadein { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

/* â”€â”€ Header â”€â”€ */
.ug-header {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 16px;
    background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 60%);
    border: 1px solid #dde5ff; border-radius: 18px; padding: 20px 24px;
    box-shadow: 0 4px 16px rgba(37,99,235,.07);
}
.ug-header-left { display: flex; align-items: center; gap: 14px; }
.ug-header-icon {
    width: 52px; height: 52px; border-radius: 14px; flex: 0 0 auto;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    box-shadow: 0 8px 20px rgba(37,99,235,.30);
}
.ug-header h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; }
.ug-header p  { font-size: 12px; color: #64748b; margin: 4px 0 0; }

/* â”€â”€ Contadores â”€â”€ */
.ug-counters { display: flex; gap: 10px; flex-wrap: wrap; }
.ug-counter {
    text-align: center; border-radius: 12px; padding: 10px 18px;
    min-width: 72px; border: 1.5px solid transparent;
}
.ug-counter strong { display: block; font-size: 26px; font-weight: 800; line-height: 1; }
.ug-counter span   { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; display: block; }
.ug-counter-total  { background: #f8fafc; border-color: #e2e8f0; color: #0f172a; }
.ug-counter-total strong { color: #0f172a; }
.ug-counter-admin  { background: #eff6ff; border-color: #bfdbfe; }
.ug-counter-admin strong { color: #1d4ed8; } .ug-counter-admin span { color: #3b82f6; }
.ug-counter-cook   { background: #fff7ed; border-color: #fed7aa; }
.ug-counter-cook strong { color: #c2410c; } .ug-counter-cook span { color: #f97316; }
.ug-counter-active { background: #f0fdf4; border-color: #86efac; }
.ug-counter-active strong { color: #15803d; } .ug-counter-active span { color: #22c55e; }

/* â”€â”€ Layout â”€â”€ */
.ug-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 18px;
    align-items: start;
}

/* â”€â”€ Sidebar card nuevo usuario â”€â”€ */
.ug-new-card {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 18px;
    padding: 20px; box-shadow: 0 8px 24px rgba(0,0,0,.06);
    position: sticky; top: 80px;
}
.ug-new-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
.ug-new-card-icon {
    width: 40px; height: 40px; border-radius: 12px; flex: 0 0 auto;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 17px;
    box-shadow: 0 6px 16px rgba(34,197,94,.28);
}
.ug-new-card h3 { font-size: 15px; font-weight: 700; margin: 0; }
.ug-new-card p  { font-size: 12px; color: #64748b; margin: 3px 0 0; }

/* â”€â”€ Formularios â”€â”€ */
.ug-form { display: grid; gap: 14px; }
.ug-field { display: grid; gap: 6px; }
.ug-field label {
    font-size: 12px; font-weight: 700; color: #374151;
    display: flex; align-items: center; gap: 6px;
}
.ug-field input,
.ug-field select {
    border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;
    font-size: 13px; background: #fff; width: 100%;
    transition: border-color .15s, box-shadow .15s;
}
.ug-field input:focus,
.ug-field select:focus {
    outline: none; border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.15);
}

/* â”€â”€ Prefix @ â”€â”€ */
.ug-input-prefix { display: flex; align-items: stretch; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; }
.ug-input-prefix span { background: #f8fafc; border-right: 1.5px solid #e2e8f0; padding: 0 12px; font-size: 13px; font-weight: 700; color: #64748b; display: flex; align-items: center; }
.ug-input-prefix input { border: none; border-radius: 0; flex: 1; padding: 10px 12px; min-width: 0; }
.ug-input-prefix input:focus { outline: none; box-shadow: none; }
.ug-input-prefix:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }

/* â”€â”€ Password eye â”€â”€ */
.ug-input-eye { display: flex; align-items: stretch; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; }
.ug-input-eye input { border: none; border-radius: 0; flex: 1; padding: 10px 12px; min-width: 0; }
.ug-input-eye input:focus { outline: none; box-shadow: none; }
.ug-input-eye:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.ug-eye-btn { background: #f8fafc; border: none; border-left: 1.5px solid #e2e8f0; padding: 0 12px; color: #64748b; cursor: pointer; font-size: 13px; }
.ug-eye-btn:hover { color: #374151; }

/* â”€â”€ Role selector â”€â”€ */
.ug-role-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ug-role-opt { cursor: pointer; }
.ug-role-opt input { display: none; }
.ug-role-opt span {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    padding: 9px 12px; border-radius: 10px; border: 1.5px solid #e2e8f0;
    font-size: 12px; font-weight: 700; color: #64748b; background: #fff;
    transition: all .15s;
}
.ug-role-opt input:checked + span { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }

/* â”€â”€ Toggle activo â”€â”€ */
.ug-toggle-label { display: flex; justify-content: space-between; align-items: center; }
.ug-toggle-label > span { font-size: 12px; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 6px; }
.ug-toggle { position: relative; display: inline-block; width: 42px; height: 24px; }
.ug-toggle input { display: none; }
.ug-toggle-track { position: absolute; inset: 0; border-radius: 999px; background: #d1d5db; transition: background .2s; cursor: pointer; }
.ug-toggle input:checked ~ .ug-toggle-track { background: #22c55e; }
.ug-toggle-thumb { position: absolute; left: 3px; top: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.2); transition: transform .2s; }
.ug-toggle input:checked ~ .ug-toggle-track .ug-toggle-thumb { transform: translateX(18px); }

/* â”€â”€ Botones â”€â”€ */
.ug-btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none;
    border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(37,99,235,.28); transition: opacity .15s, transform .1s;
    width: 100%;
}
.ug-btn-primary:hover { opacity: .92; }
.ug-btn-primary:active { transform: scale(.98); }
.ug-btn-primary.ug-btn-sm { padding: 9px 14px; font-size: 12px; }

.ug-btn-secondary {
    background: #fff; color: #374151; border: 1.5px solid #e2e8f0;
    border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    transition: background .15s, border-color .15s; width: 100%;
}
.ug-btn-secondary:hover { background: #f8fafc; border-color: #94a3b8; }
.ug-btn-secondary.ug-btn-sm { padding: 9px 14px; font-size: 12px; }

/* â”€â”€ Main lista â”€â”€ */
.ug-list-head { margin-bottom: 12px; }
.ug-list-head h3 { font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.ug-list-head p  { font-size: 12px; color: #64748b; margin-top: 4px; }
.ug-count-badge {
    background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
    border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 700;
}

/* â”€â”€ User list â”€â”€ */
.ug-user-list { display: grid; gap: 10px; }

.ug-user-item {
    background: #fff; border: 1.5px solid #e5e7eb; border-radius: 16px;
    overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.04);
    transition: box-shadow .2s;
}
.ug-user-item:hover { box-shadow: 0 6px 18px rgba(0,0,0,.08); }

.ug-user-row {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px;
}

/* Avatar */
.ug-avatar {
    width: 44px; height: 44px; border-radius: 12px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 800;
}
.ug-avatar-admin { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
.ug-avatar-cook  { background: linear-gradient(135deg, #fed7aa, #fde68a); color: #c2410c; }

.ug-user-info { flex: 1; min-width: 0; }
.ug-user-name {
    font-size: 14px; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.ug-user-sub { font-size: 12px; color: #64748b; margin-top: 3px; }

.ug-you-badge {
    background: #fef9c3; border: 1px solid #fde047; color: #854d0e;
    border-radius: 999px; font-size: 10px; font-weight: 700; padding: 2px 8px;
    display: inline-flex; align-items: center; gap: 4px;
}

.ug-user-chips { display: flex; gap: 6px; flex-wrap: wrap; }

/* Chips */
.ug-chip {
    border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px;
}
.chip-admin { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
.chip-cook  { background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; }
.chip-on    { background: #f0fdf4; border: 1px solid #86efac; color: #15803d; }
.chip-off   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

/* BotÃ³n editar */
.ug-btn-edit {
    background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px;
    padding: 8px 14px; font-size: 12px; font-weight: 700; color: #374151;
    cursor: pointer; white-space: nowrap; flex: 0 0 auto;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .15s, border-color .15s;
}
.ug-btn-edit:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }

/* Panel ediciÃ³n */
.ug-edit-panel {
    display: none;
    border-top: 1.5px solid #f1f5f9;
    background: #fafcff;
    padding: 18px 16px 14px;
    animation: ug-slidein .2s ease;
}
.ug-edit-panel.open { display: block; }
@keyframes ug-slidein { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

.ug-edit-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.ug-form-edit { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; }

.ug-edit-title {
    font-size: 13px; font-weight: 800; color: #374151;
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px; padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}

.ug-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.ug-pass-hint { font-size: 11px; color: #64748b; margin: 0; display: flex; align-items: flex-start; gap: 5px; }

.ug-edit-meta {
    display: flex; gap: 14px; flex-wrap: wrap;
    font-size: 11px; color: #94a3b8; margin-top: 12px;
    padding-top: 10px; border-top: 1px solid #f1f5f9;
}
.ug-edit-meta span { display: inline-flex; align-items: center; gap: 5px; }

/* â”€â”€ Responsive â”€â”€ */
@media (max-width: 1100px) {
    .ug-layout { grid-template-columns: 280px 1fr; }
}
@media (max-width: 820px) {
    .ug-layout { grid-template-columns: 1fr; }
    .ug-new-card { position: static; }
    .ug-edit-grid { grid-template-columns: 1fr; }
    .ug-two-col   { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .ug-header  { flex-direction: column; align-items: flex-start; }
    .ug-counters { width: 100%; }
    .ug-user-chips { display: none; }
    .ug-user-row { flex-wrap: wrap; }
}
</style>

<script>
function ugToggle(id) {
    const panel = document.getElementById('ug-panel-' + id);
    if (!panel) return;
    const isOpen = panel.classList.toggle('open');
    const btn = document.querySelector('#ug-item-' + id + ' .ug-btn-edit');
    if (btn) btn.innerHTML = isOpen
        ? '<i class="fa-solid fa-chevron-up"></i> Cerrar'
        : '<i class="fa-solid fa-pen-to-square"></i> Editar';
}

function togglePass(inputId, btn) {
    const inp = document.getElementById(inputId);
    if (!inp) return;
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.querySelector('i').className = 'fa-solid ' + (show ? 'fa-eye-slash' : 'fa-eye');
}

<?php if ($mensaje && $tipoMensaje === 'ok'): ?>
// Auto-hide toast after 4s
setTimeout(function() {
    const t = document.getElementById('ugToast');
    if (t) { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
}, 4000);
<?php endif; ?>
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
