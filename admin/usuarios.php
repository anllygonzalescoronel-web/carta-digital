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
                throw new RuntimeException('La contraseña debe tener al menos 6 caracteres.');
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
                throw new RuntimeException('La nueva contraseña debe tener al menos 6 caracteres.');
            }

            $stmt = $db->prepare('UPDATE admin_usuarios SET password_hash = :pass WHERE id = :id');
            $stmt->execute([
                'pass' => password_hash($password, PASSWORD_BCRYPT),
                'id' => $id,
            ]);
            $mensaje = 'Contraseña actualizada correctamente.';
            $tipoMensaje = 'ok';
        }

        if ($accion === 'eliminar_usuario') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Usuario invÃ¡lido.');
            }

            $stmtUser = $db->prepare('SELECT * FROM admin_usuarios WHERE id = :id LIMIT 1');
            $stmtUser->execute(['id' => $id]);
            $user = $stmtUser->fetch();
            if (!$user) {
                throw new RuntimeException('Usuario no encontrado.');
            }

            $esMismoUsuario = ((int)($_SESSION['admin_id']) === $id);
            if ($esMismoUsuario) {
                throw new RuntimeException('No puedes eliminar tu propio usuario.');
            }

            $eraAdminActivo = (($user['rol'] ?? 'admin') === 'admin' && (int)($user['activo'] ?? 1) === 1);
            if ($eraAdminActivo && contarAdminsActivos($db, $id) <= 0) {
                throw new RuntimeException('Debe existir al menos un administrador activo.');
            }

            $stmt = $db->prepare('DELETE FROM admin_usuarios WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() <= 0) {
                throw new RuntimeException('No se pudo eliminar el usuario.');
            }

            $mensaje = 'Usuario eliminado correctamente.';
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
$totalMeseros = 0;
$totalActivos = 0;
foreach ($usuarios as $u) {
    $rolUsuario = (string)($u['rol'] ?? 'admin');
    if ($rolUsuario === 'admin') {
        $totalAdmins++;
    } elseif ($rolUsuario === 'mesero') {
        $totalMeseros++;
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
                <h1>Gestión de Usuarios</h1>
                <p>Administra cuentas, roles y accesos del equipo</p>
            </div>
        </div>
<div class="ug-counters">
            <div class="ug-counter ug-counter-total" data-filtro="total" onclick="ugFiltrar('total', this)">
                <strong><?= $totalUsuarios ?></strong>
                <span>Total</span>
            </div>
            <div class="ug-counter ug-counter-admin" data-filtro="admin" onclick="ugFiltrar('admin', this)">
                <strong><?= $totalAdmins ?></strong>
                <span>Admins</span>
            </div>
            <div class="ug-counter ug-counter-cook" data-filtro="cocinero" onclick="ugFiltrar('cocinero', this)">
                <strong><?= $totalCocineros ?></strong>
                <span>Cocineros</span>
            </div>
<<<<<<< HEAD
                    <div class="ug-counter ug-counter-waiter">
                        <strong><?= $totalMeseros ?></strong>
                        <span>Meseros</span>
                    </div>
            <div class="ug-counter ug-counter-active">
=======
            <div class="ug-counter ug-counter-active" data-filtro="activo" onclick="ugFiltrar('activo', this)">
>>>>>>> 88a6277931d4b7ca7ba80581acee71a1c47c51dc
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
                        <p>Añade un miembro al equipo</p>
                    </div>
                </div>

                <form method="POST" class="ug-form" autocomplete="off">
                    <input type="hidden" name="accion" value="crear_usuario">

                    <div class="ug-field">
                        <label><i class="fa-solid fa-id-card"></i> Nombre completo</label>
                        <input type="text" name="nombre" placeholder="Ej. Carlos Ramirez" required>
                    </div>

                    <div class="ug-field">
                        <label><i class="fa-solid fa-at"></i> Nombre de usuario</label>
                        <div class="ug-input-prefix">
                            <span>@</span>
                            <input type="text" name="usuario" placeholder="cocina1" required>
                        </div>
                    </div>

                    <div class="ug-field">
                        <label><i class="fa-solid fa-lock"></i> Contraseña</label>
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
                            <label class="ug-role-opt">
                                <input type="radio" name="rol" value="mesero">
                                <span><i class="fa-solid fa-user-tie"></i> Mesero</span>
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
                    $rolUsuario = (string)($u['rol'] ?? 'admin');
                    $esAdmin   = $rolUsuario === 'admin';
                    $esMesero  = $rolUsuario === 'mesero';
                    $esActivo  = (int)$u['activo'] === 1;
                ?>
                <div class="ug-user-item" id="ug-item-<?= $uid ?>" data-rol="<?= $esAdmin ? 'admin' : 'cocinero' ?>" data-activo="<?= $esActivo ? '1' : '0' ?>">

                    <!-- FILA RESUMEN -->
                    <div class="ug-user-row">
                        <div class="ug-avatar ug-avatar-<?= $esAdmin ? 'admin' : ($esMesero ? 'waiter' : 'cook') ?>"><?= limpiar($iniciales) ?></div>
                        <div class="ug-user-info">
                            <div class="ug-user-name">
                                <?= limpiar($nombre) ?>
                                <?php if ($esYo): ?><span class="ug-you-badge"><i class="fa-solid fa-star"></i> </span><?php endif; ?>
                            </div>
                            <div class="ug-user-sub">@<?= limpiar((string)$u['usuario']) ?></div>
                        </div>
                        <div class="ug-user-chips">
                            <span class="ug-chip <?= $esAdmin ? 'chip-admin' : ($esMesero ? 'chip-waiter' : 'chip-cook') ?>">
                                <i class="fa-solid <?= $esAdmin ? 'fa-user-shield' : ($esMesero ? 'fa-user-tie' : 'fa-fire') ?>"></i>
                                <?= $esAdmin ? 'Admin' : ($esMesero ? 'Mesero' : 'Cocinero') ?>
                            </span>
                            <span class="ug-chip <?= $esActivo ? 'chip-on' : 'chip-off' ?>">
                                <i class="fa-solid <?= $esActivo ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <div class="ug-row-actions">
                            <button class="ug-btn-edit" type="button" onclick="ugToggle(<?= $uid ?>)">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>
                            <form method="POST" class="ug-inline-form" onsubmit="return confirmarEliminarUsuario(this)">
                                <input type="hidden" name="accion" value="eliminar_usuario">
                                <input type="hidden" name="id" value="<?= $uid ?>">
                                <input type="hidden" name="nombre" value="<?= limpiar($nombre) ?>">
                                <button class="ug-btn-delete-row" type="submit" <?= $esYo ? 'disabled title="No puedes eliminar tu propio usuario"' : '' ?>>
                                    <i class="fa-solid fa-trash"></i> Eliminar
                                </button>
                            </form>
                        </div>
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
                                            <option value="cocinero" <?= (!$esAdmin && !$esMesero) ? 'selected' : '' ?>>Cocinero</option>
                                            <option value="mesero"   <?= $esMesero ? 'selected' : '' ?>>Mesero</option>
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

                            <!-- Form cambiar contraseña -->
                            <form method="POST" class="ug-form ug-form-edit">
                                <input type="hidden" name="accion" value="cambiar_password">
                                <input type="hidden" name="id" value="<?= $uid ?>">

                                <div class="ug-edit-title"><i class="fa-solid fa-key"></i> Nueva contraseña</div>

                                <div class="ug-field">
                                    <label><i class="fa-solid fa-lock"></i> Contraseña</label>
                                    <div class="ug-input-eye">
                                        <input type="password" name="password" id="pass-<?= $uid ?>" minlength="6" placeholder="MÃ­nimo 6 caracteres" required>
                                        <button type="button" class="ug-eye-btn" onclick="togglePass('pass-<?= $uid ?>',this)"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>

                                <p class="ug-pass-hint"><i class="fa-solid fa-circle-info"></i> Solo se aplica si completas este campo y guardas.</p>

                                <button type="submit" class="ug-btn-secondary ug-btn-sm"><i class="fa-solid fa-key"></i> Actualizar clave</button>
                            </form>

                            <!-- Form eliminar usuario -->
                            <form method="POST" class="ug-form ug-form-edit ug-form-danger" onsubmit="return confirmarEliminarUsuario(this)">
                                <input type="hidden" name="accion" value="eliminar_usuario">
                                <input type="hidden" name="id" value="<?= $uid ?>">
                                <input type="hidden" name="nombre" value="<?= limpiar($nombre) ?>">

                                <div class="ug-edit-title"><i class="fa-solid fa-trash"></i> Eliminar cuenta</div>

                                <p class="ug-pass-hint">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Esta accion elimina el usuario de forma permanente.
                                </p>

                                <button type="submit" class="ug-btn-danger ug-btn-sm" <?= $esYo ? 'disabled title="No puedes eliminar tu propio usuario"' : '' ?>>
                                    <i class="fa-solid fa-trash"></i> Eliminar usuario
                                </button>
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

<div class="ug-modal-backdrop" id="ugDeleteModal" aria-hidden="true">
    <div class="ug-modal" role="dialog" aria-modal="true" aria-labelledby="ugDeleteModalTitle" aria-describedby="ugDeleteModalText">
        <div class="ug-modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h4 id="ugDeleteModalTitle">Confirmar eliminacion</h4>
        <p id="ugDeleteModalText">Esta accion no se puede deshacer.</p>
        <div class="ug-modal-actions">
            <button type="button" class="ug-modal-btn ug-modal-cancel" id="ugDeleteCancelBtn">Cancelar</button>
            <button type="button" class="ug-modal-btn ug-modal-confirm" id="ugDeleteConfirmBtn">
                <i class="fa-solid fa-trash"></i> Eliminar usuario
            </button>
        </div>
    </div>
</div>

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
.ug-counter-waiter { background: #ecfdf5; border-color: #a7f3d0; }
.ug-counter-waiter strong { color: #0f766e; } .ug-counter-waiter span { color: #14b8a6; }
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
    background: var(--neu-base);
    border: none;
    border-radius: 20px;
    padding: 20px;
    box-shadow: inset 6px 6px 14px var(--neu-sombra-oscura), inset -6px -6px 14px var(--neu-sombra-clara);
    position: sticky; top: 80px;
}
.ug-new-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid rgba(0,0,0,.06); }
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
    border: none; border-radius: 10px; padding: 10px 12px;
    font-size: 13px; background: var(--neu-base); width: 100%;
    color: var(--pos-texto, #333);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
    transition: box-shadow .15s;
}
.ug-field input:focus,
.ug-field select:focus {
    outline: none;
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35);
}

/* â”€â”€ Prefix @ â”€â”€ */
.ug-input-prefix {
    display: flex; align-items: stretch; border: none; border-radius: 10px; overflow: hidden;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
}
.ug-input-prefix span { background: transparent; border-right: 1px solid rgba(0,0,0,.08); padding: 0 12px; font-size: 13px; font-weight: 700; color: var(--pos-muted, #64748b); display: flex; align-items: center; }
.ug-input-prefix input { border: none; border-radius: 0; flex: 1; padding: 10px 12px; min-width: 0; background: transparent; box-shadow: none; }
.ug-input-prefix input:focus { outline: none; box-shadow: none; }
.ug-input-prefix:focus-within { box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35); }

.ug-input-eye {
    display: flex; align-items: stretch; border: none; border-radius: 10px; overflow: hidden;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
}
.ug-input-eye input { border: none; border-radius: 0; flex: 1; padding: 10px 12px; min-width: 0; background: transparent; box-shadow: none; }
.ug-input-eye input:focus { outline: none; box-shadow: none; }
.ug-input-eye:focus-within { box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35); }
.ug-eye-btn { background: transparent; border: none; border-left: 1px solid rgba(0,0,0,.08); padding: 0 12px; color: var(--pos-muted, #64748b); cursor: pointer; font-size: 13px; }
.ug-eye-btn:hover { color: var(--pos-texto, #374151); }

/* â”€â”€ Role selector â”€â”€ */
.ug-role-selector { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
.ug-role-opt { cursor: pointer; }
.ug-role-opt input { display: none; }
.ug-role-opt span {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    padding: 9px 12px; border-radius: 10px; border: none;
    font-size: 12px; font-weight: 700; color: var(--pos-muted, #64748b);
    background: var(--neu-base);
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
    transition: box-shadow .15s, color .15s;
}
.ug-role-opt input:checked + span {
    color: #E8590C;
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35);
}

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
    background: linear-gradient(135deg, #ff8a3d, #E8590C); color: #fff; border: none;
    border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 4px 4px 10px rgba(232,89,12,.4); transition: transform .15s ease, box-shadow .15s ease;
    width: 100%;
}
.ug-btn-primary:hover { transform: translateY(-2px); }
.ug-btn-primary:hover { opacity: .92; }
.ug-btn-primary:active { transform: scale(.98); }
.ug-btn-primary.ug-btn-sm { padding: 9px 14px; font-size: 12px; }

.ug-btn-secondary {
    background: var(--neu-base); color: var(--pos-texto, #374151); border: none;
    border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara);
    transition: transform .15s ease; width: 100%;
}
.ug-btn-secondary:hover { transform: translateY(-2px); }
.ug-btn-secondary.ug-btn-sm { padding: 9px 14px; font-size: 12px; }

.ug-btn-danger {
    background: #fff1f2; color: #be123c; border: 1.5px solid #fecdd3;
    border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    transition: background .15s, border-color .15s; width: 100%;
}
.ug-btn-danger:hover { background: #ffe4e6; border-color: #fda4af; }
.ug-btn-danger.ug-btn-sm { padding: 9px 14px; font-size: 12px; }
.ug-btn-danger:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.ug-form-danger {
    background: #fff7f7;
    border-color: #fecaca;
}

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
    background: var(--neu-base); border: none; border-radius: 18px;
    overflow: hidden;
    box-shadow: inset 6px 6px 14px var(--neu-sombra-oscura), inset -6px -6px 14px var(--neu-sombra-clara);
    transition: box-shadow .2s;
}
.ug-user-item:hover { box-shadow: inset 8px 8px 18px var(--neu-sombra-oscura), inset -8px -8px 18px var(--neu-sombra-clara); }

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
.ug-avatar-waiter { background: linear-gradient(135deg, #ccfbf1, #99f6e4); color: #0f766e; }

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

.ug-row-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex: 0 0 auto;
}

.ug-inline-form { margin: 0; }

/* Chips */
.ug-chip {
    border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px;
}
.chip-admin { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
.chip-cook  { background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; }
.chip-waiter { background: #ecfeff; border: 1px solid #a5f3fc; color: #0f766e; }
.chip-on    { background: #f0fdf4; border: 1px solid #86efac; color: #15803d; }
.chip-off   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

/* BotÃ³n editar */
.ug-btn-edit {
    background: var(--neu-base); border: none; border-radius: 10px;
    padding: 8px 14px; font-size: 12px; font-weight: 700; color: #E8590C;
    cursor: pointer; white-space: nowrap; flex: 0 0 auto;
    display: inline-flex; align-items: center; gap: 6px;
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
    transition: transform .15s ease;
}
.ug-btn-edit:hover { transform: translateY(-2px); }

.ug-btn-delete-row {
    background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 10px;
    padding: 8px 14px; font-size: 12px; font-weight: 700; color: #be123c;
    cursor: pointer; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .15s, border-color .15s;
}
.ug-btn-delete-row:hover { background: #ffe4e6; border-color: #fda4af; }
.ug-btn-delete-row:disabled {
    opacity: .6;
    cursor: not-allowed;
}

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

.ug-form-edit {
    background: var(--neu-base); border: none; border-radius: 14px; padding: 14px;
    box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara);
}

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

/* Modal eliminar personalizado */
.ug-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 18px;
}

.ug-modal-backdrop.open {
    display: flex;
    animation: ug-modal-fade .18s ease;
}

.ug-modal {
    width: min(460px, 100%);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 18px 45px rgba(15, 23, 42, .18);
    padding: 20px;
    text-align: center;
    animation: ug-modal-pop .2s ease;
}

.ug-modal-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 10px;
    border-radius: 14px;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #be123c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.ug-modal h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
}

.ug-modal p {
    margin: 10px 0 0;
    color: #475569;
    font-size: 13px;
    line-height: 1.45;
}

.ug-modal-actions {
    margin-top: 18px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.ug-modal-btn {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}

.ug-modal-cancel {
    background: #f8fafc;
    color: #334155;
}

.ug-modal-cancel:hover {
    background: #f1f5f9;
}

.ug-modal-confirm {
    background: linear-gradient(135deg, #e11d48, #be123c);
    border-color: #be123c;
    color: #fff;
}

.ug-modal-confirm:hover {
    opacity: .92;
}

@keyframes ug-modal-fade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes ug-modal-pop {
    from { transform: translateY(8px) scale(.98); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

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
    .ug-modal-actions { grid-template-columns: 1fr; }
}




/* ── Modo oscuro: Gestión de Usuarios ── */
body.modo-oscuro .ug-header {
    background: linear-gradient(135deg, #1e2535 0%, #1a1f2e 60%);
    border-color: rgba(255,255,255,0.08);
    box-shadow: 0 4px 16px rgba(0,0,0,.3);
}
body.modo-oscuro .ug-header h1 { color: #f1f5f9; }
body.modo-oscuro .ug-header p  { color: #94a3b8; }

body.modo-oscuro .ug-counter-total  { background: #1e2535; border-color: rgba(255,255,255,0.1); }
body.modo-oscuro .ug-counter-total strong { color: #f1f5f9; }
body.modo-oscuro .ug-counter-admin  { background: rgba(30,64,175,0.2); border-color: rgba(96,165,250,0.25); }
body.modo-oscuro .ug-counter-admin strong { color: #93c5fd; } body.modo-oscuro .ug-counter-admin span { color: #93c5fd; }
body.modo-oscuro .ug-counter-cook   { background: rgba(194,65,12,0.2); border-color: rgba(253,186,116,0.25); }
body.modo-oscuro .ug-counter-cook strong { color: #fdba74; } body.modo-oscuro .ug-counter-cook span { color: #fdba74; }
body.modo-oscuro .ug-counter-active { background: rgba(22,101,52,0.2); border-color: rgba(134,239,172,0.25); }
body.modo-oscuro .ug-counter-active strong { color: #86efac; } body.modo-oscuro .ug-counter-active span { color: #86efac; }

body.modo-oscuro .ug-new-card-head { border-bottom-color: rgba(255,255,255,0.08); }
body.modo-oscuro .ug-new-card h3 { color: #f1f5f9; }
body.modo-oscuro .ug-new-card p  { color: #94a3b8; }

body.modo-oscuro .ug-field label { color: #cbd5e1; }
body.modo-oscuro .ug-field input,
body.modo-oscuro .ug-field select {
    color: #e2e8f0;
}

body.modo-oscuro .ug-input-prefix span { color: #94a3b8; border-right-color: rgba(255,255,255,0.1); }
body.modo-oscuro .ug-eye-btn { color: #94a3b8; border-left-color: rgba(255,255,255,0.1); }
body.modo-oscuro .ug-eye-btn:hover { color: #e2e8f0; }

body.modo-oscuro .ug-role-opt span { color: #cbd5e1; }
body.modo-oscuro .ug-role-opt input:checked + span { color: #ff8a3d; }

body.modo-oscuro .ug-toggle-label > span { color: #cbd5e1; }

body.modo-oscuro .ug-list-head h3 { color: #f1f5f9; }
body.modo-oscuro .ug-list-head p  { color: #94a3b8; }
body.modo-oscuro .ug-count-badge {
    background: rgba(30,64,175,0.2); border-color: rgba(96,165,250,0.25); color: #93c5fd;
}

body.modo-oscuro .ug-user-name { color: #f1f5f9; }
body.modo-oscuro .ug-user-sub  { color: #94a3b8; }

body.modo-oscuro .ug-avatar-admin { background: linear-gradient(135deg, rgba(30,64,175,.35), rgba(96,165,250,.25)); color: #93c5fd; }
body.modo-oscuro .ug-avatar-cook  { background: linear-gradient(135deg, rgba(194,65,12,.35), rgba(253,186,116,.25)); color: #fdba74; }

body.modo-oscuro .ug-you-badge {
    background: rgba(146,64,14,0.25); border-color: rgba(253,230,138,0.3); color: #fde68a;
}

body.modo-oscuro .chip-admin { background: rgba(30,64,175,0.2); border-color: rgba(96,165,250,0.25); color: #93c5fd; }
body.modo-oscuro .chip-cook  { background: rgba(194,65,12,0.2); border-color: rgba(253,186,116,0.25); color: #fdba74; }
body.modo-oscuro .chip-on    { background: rgba(22,101,52,0.2); border-color: rgba(134,239,172,0.25); color: #86efac; }
body.modo-oscuro .chip-off   { background: rgba(185,28,28,0.2); border-color: rgba(252,165,165,0.25); color: #fca5a5; }

body.modo-oscuro .ug-edit-panel { background: transparent; border-top-color: rgba(255,255,255,0.08); }
body.modo-oscuro .ug-edit-title { color: #cbd5e1; border-bottom-color: rgba(255,255,255,0.08); }
body.modo-oscuro .ug-pass-hint { color: #94a3b8; }

body.modo-oscuro .ug-edit-meta { color: #64748b; border-top-color: rgba(255,255,255,0.08); }

body.modo-oscuro .ug-toast-ok  { background: rgba(30,132,73,0.2);  color: #86efac; border-color: rgba(34,197,94,0.3); }
body.modo-oscuro .ug-toast-err { background: rgba(192,57,43,0.2);  color: #fca5a5; border-color: rgba(239,68,68,0.3); }

.ug-counter {
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}
.ug-counter:hover { transform: translateY(-2px); }

.ug-counter-total.ug-counter-activo-filtro {
    box-shadow: 0 0 0 2px #0f172a, 0 4px 12px rgba(15,23,42,.2);
}
.ug-counter-admin.ug-counter-activo-filtro {
    box-shadow: 0 0 0 2px #3b82f6, 0 4px 12px rgba(59,130,246,.25);
}
.ug-counter-cook.ug-counter-activo-filtro {
    box-shadow: 0 0 0 2px #f97316, 0 4px 12px rgba(249,115,22,.25);
}
.ug-counter-active.ug-counter-activo-filtro {
    box-shadow: 0 0 0 2px #22c55e, 0 4px 12px rgba(34,197,94,.25);
}
</style>

<script>
<<<<<<< HEAD
let ugDeleteFormPendiente = null;
=======

function ugFiltrar(tipo, el) {
    // Marcar visualmente cuál está activo
    document.querySelectorAll('.ug-counter').forEach(c => c.classList.remove('ug-counter-activo-filtro'));
    el.classList.add('ug-counter-activo-filtro');

    document.querySelectorAll('.ug-user-item').forEach(item => {
        const rol = item.dataset.rol;
        const activo = item.dataset.activo;
        let mostrar = true;

        if (tipo === 'admin') mostrar = rol === 'admin';
        else if (tipo === 'cocinero') mostrar = rol === 'cocinero';
        else if (tipo === 'activo') mostrar = activo === '1';
        // tipo === 'total' → mostrar todos

        item.style.display = mostrar ? '' : 'none';
    });
}
>>>>>>> 88a6277931d4b7ca7ba80581acee71a1c47c51dc

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

function confirmarEliminarUsuario(form) {
    const idInput = form.querySelector('input[name="id"]');
    const nombreInput = form.querySelector('input[name="nombre"]');
    const userId = idInput ? idInput.value : '';
    const nombre = nombreInput ? nombreInput.value : 'este usuario';

    const modal = document.getElementById('ugDeleteModal');
    const text = document.getElementById('ugDeleteModalText');
    if (!modal || !text) {
        return false;
    }

    text.textContent = 'Vas a eliminar a "' + nombre + '" (ID ' + userId + '). Esta accion no se puede deshacer.';
    ugDeleteFormPendiente = form;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');

    const confirmBtn = document.getElementById('ugDeleteConfirmBtn');
    if (confirmBtn) confirmBtn.focus();

    return false;
}

function cerrarModalEliminar() {
    const modal = document.getElementById('ugDeleteModal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('ugDeleteModal');
    const cancelBtn = document.getElementById('ugDeleteCancelBtn');
    const confirmBtn = document.getElementById('ugDeleteConfirmBtn');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            ugDeleteFormPendiente = null;
            cerrarModalEliminar();
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const form = ugDeleteFormPendiente;
            ugDeleteFormPendiente = null;
            cerrarModalEliminar();
            if (form) form.submit();
        });
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                ugDeleteFormPendiente = null;
                cerrarModalEliminar();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ugDeleteFormPendiente = null;
            cerrarModalEliminar();
        }
    });
});

<?php if ($mensaje && $tipoMensaje === 'ok'): ?>
// Auto-hide toast after 4s
setTimeout(function() {
    const t = document.getElementById('ugToast');
    if (t) { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
}, 4000);
<?php endif; ?>
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
