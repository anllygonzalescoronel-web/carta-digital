<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$db = getDB();

$productoId = (int)($_GET['producto'] ?? 0);
$grupoId    = (int)($_GET['grupo'] ?? 0);
$esAjax     = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// ─────────────── Procesar acciones POST ───────────────
$errorPost = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $backProducto = (int)($_POST['producto_id'] ?? $productoId);
    $backGrupo    = (int)($_POST['grupo_id']    ?? 0);
    $esAjaxPost = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    try {
        switch ($accion) {
            case 'guardar_grupo':
                $pid   = (int)$_POST['producto_id'];
                $gid   = (int)($_POST['id'] ?? 0);
                $nom   = trim($_POST['nombre']);
                $tipo  = $_POST['tipo'] === 'checkbox' ? 'checkbox' : 'radio';
                $req   = isset($_POST['requerido']) ? 1 : 0;
                $minOp = max(0, (int)($_POST['min_opciones'] ?? 0));
                $maxOp = max(1, (int)($_POST['max_opciones'] ?? 1));
                $ord   = (int)($_POST['orden'] ?? 0);
                if ($nom === '') throw new RuntimeException('El nombre del grupo es obligatorio.');
                if ($gid > 0) {
                    $db->prepare('UPDATE producto_grupos SET nombre=:n, tipo=:t, requerido=:r, min_opciones=:mi, max_opciones=:ma, orden=:o WHERE id=:id')
                       ->execute(['n'=>$nom,'t'=>$tipo,'r'=>$req,'mi'=>$minOp,'ma'=>$maxOp,'o'=>$ord,'id'=>$gid]);
                } else {
                    $db->prepare('INSERT INTO producto_grupos (producto_id,nombre,tipo,requerido,min_opciones,max_opciones,orden) VALUES (:p,:n,:t,:r,:mi,:ma,:o)')
                       ->execute(['p'=>$pid,'n'=>$nom,'t'=>$tipo,'r'=>$req,'mi'=>$minOp,'ma'=>$maxOp,'o'=>$ord]);
                }
                $productoId = $pid;
                break;

            case 'eliminar_grupo':
                $gid = (int)$_POST['id'];
                $db->prepare('DELETE FROM producto_grupos WHERE id=:id')->execute(['id'=>$gid]);
                $productoId = $backProducto;
                break;

          case 'guardar_opcion':
                $gid  = (int)$_POST['grupo_id'];
                $oid  = (int)($_POST['id'] ?? 0);
                $nom  = trim($_POST['nombre']);
                $prEx = (float)($_POST['precio_extra'] ?? 0);
                $disp = isset($_POST['disponible']) ? 1 : 0;
                // El usuario escribe posición "humana" (1 = primero, 2 = segundo...); internamente usamos 0-based
                $ordDeseado1   = (int)($_POST['orden'] ?? 1);
                $ordDeseadoIdx = max(0, $ordDeseado1 - 1);
                if ($nom === '') throw new RuntimeException('El nombre de la opción es obligatorio.');

                if ($oid > 0) {
                    $stmtG = $db->prepare('SELECT grupo_id FROM producto_opciones WHERE id=:id');
                    $stmtG->execute(['id' => $oid]);
                    $filaG = $stmtG->fetch();
                    $gidReal = $filaG ? (int)$filaG['grupo_id'] : $gid;

                    $db->prepare('UPDATE producto_opciones SET nombre=:n, precio_extra=:p, disponible=:d WHERE id=:id')
                       ->execute(['n'=>$nom,'p'=>$prEx,'d'=>$disp,'id'=>$oid]);
                } else {
                    $gidReal = $gid;
                    // Se inserta temporalmente al final; la posición real se calcula abajo
                    $db->prepare('INSERT INTO producto_opciones (grupo_id,nombre,precio_extra,disponible,orden) VALUES (:g,:n,:p,:d,999999)')
                       ->execute(['g'=>$gidReal,'n'=>$nom,'p'=>$prEx,'d'=>$disp]);
                    $oid = (int)$db->lastInsertId();
                }

                // ── Normaliza el grupo según el orden actual real, y hace swap puro por posición ──
                $stmtLista = $db->prepare('SELECT id FROM producto_opciones WHERE grupo_id=:g ORDER BY orden, id');
                $stmtLista->execute(['g' => $gidReal]);
                $idsOrdenados = array_map('intval', array_column($stmtLista->fetchAll(), 'id'));

                $rankActual = array_search($oid, $idsOrdenados, true);
                if ($rankActual === false) {
                    $idsOrdenados[] = $oid;
                    $rankActual = count($idsOrdenados) - 1;
                }

                $rankDestino = max(0, min($ordDeseadoIdx, count($idsOrdenados) - 1));

                if ($rankDestino !== $rankActual) {
                    $idRival = $idsOrdenados[$rankDestino];
                    $idsOrdenados[$rankDestino] = $oid;
                    $idsOrdenados[$rankActual]  = $idRival;
                }

                // Renumerar todo el grupo de forma limpia y consecutiva (0,1,2,3...) — autocorrige datos viejos
                $stmtUpd = $db->prepare('UPDATE producto_opciones SET orden=:o WHERE id=:id');
                foreach ($idsOrdenados as $rank => $idOp) {
                    $stmtUpd->execute(['o' => $rank, 'id' => $idOp]);
                }

                $productoId = $backProducto;
                $grupoId    = $gidReal;
                break;

            case 'eliminar_opcion':
                $oid = (int)$_POST['id'];
                $db->prepare('DELETE FROM producto_opciones WHERE id=:id')->execute(['id'=>$oid]);
                $productoId = $backProducto;
                $grupoId    = $backGrupo;
                break;
        }
} catch (Throwable $e) {
        $errorPost = 'Error: ' . $e->getMessage();
        if (!$esAjaxPost) {
            // Guardar error en sesión para mostrarlo después del redirect
            $_SESSION['opciones_error'] = $errorPost;
        }
    }

    if ($esAjaxPost) {
        $esAjax = true;
    } else {
        $qs = 'producto=' . $productoId . ($grupoId ? '&grupo=' . $grupoId : '');
        header('Location: opciones_producto.php?' . $qs);
        exit;
    }
}

$tituloPagina = 'Opciones / Toppings';
$paginaActual = 'opciones';
if (!$esAjax) {
    require __DIR__ . '/_layout_top.php';
}

$mensaje = '';
$error   = '';

// Recuperar error de sesión si existe
if (!empty($_SESSION['opciones_error'])) {
    $error = $_SESSION['opciones_error'];
    unset($_SESSION['opciones_error']);
}
if ($errorPost !== '') {
    $error = $errorPost;
}
function rutaImagenProductoAdmin(?string $imagen): string {
    $nombre = trim((string)$imagen);
    if ($nombre === '') return '';
    if (str_starts_with($nombre, 'http://') || str_starts_with($nombre, 'https://')) return $nombre;
    if (str_contains($nombre, 'uploads/')) return $nombre;
    return '../uploads/productos/' . $nombre;
}

// ─────────────── Datos ───────────────
$productos = $db->query('SELECT p.id, p.nombre, p.imagen, p.precio, p.precio_oferta, p.disponible, c.nombre AS categoria FROM productos p JOIN categorias c ON c.id=p.categoria_id ORDER BY c.orden, p.orden')->fetchAll();

$producto = null;
$grupos   = [];
if ($productoId > 0) {
    $stmt = $db->prepare('SELECT * FROM productos WHERE id=:id');
    $stmt->execute(['id' => $productoId]);
    $producto = $stmt->fetch();

    $stmt = $db->prepare('SELECT * FROM producto_grupos WHERE producto_id=:id ORDER BY orden');
    $stmt->execute(['id' => $productoId]);
    $grupos = $stmt->fetchAll();

    foreach ($grupos as &$g) {
        $stmt = $db->prepare('SELECT * FROM producto_opciones WHERE grupo_id=:id ORDER BY orden, id');
        $stmt->execute(['id' => $g['id']]);
        $g['opciones'] = $stmt->fetchAll();
    }
    unset($g);
}

// Grupo activo para colapsar/expandir opciones
$grupoActivo = $grupoId ?: ($grupos[0]['id'] ?? 0);
?>

<?php
$okMensaje = isset($_GET['ok']) ? trim((string)$_GET['ok']) : '';
$totalGrupos = count($grupos);
$totalOpciones = 0;
foreach ($grupos as $grupoTmp) {
    $totalOpciones += count($grupoTmp['opciones'] ?? []);
}
?>


<?php
// ───── Fragmento: KPIs ─────
ob_start();
?>
<div class="tp-kpi"><strong><?= (int)$productoId > 0 ? 1 : 0 ?></strong><span>Producto</span></div>
<div class="tp-kpi"><strong><?= $totalGrupos ?></strong><span>Grupos</span></div>
<div class="tp-kpi"><strong><?= $totalOpciones ?></strong><span>Opciones</span></div>
<?php
$htmlKpis = ob_get_clean();

// ───── Fragmento: "Producto seleccionado" (sidebar) ─────
ob_start();
if ($producto): ?>
<div class="tp-product-meta" style="margin-top:12px;padding-top:12px;border-top:1px solid #edf2f7;">
    <p>Producto seleccionado</p>
    <h4><?= limpiar((string)$producto['nombre']) ?></h4>
    <div class="tp-product-main-price">S/ <?= number_format((float)$producto['precio'], 2) ?></div>
</div>
<?php endif;
$htmlSidebarMeta = ob_get_clean();
// ───── Fragmento: contenido principal (<main>) ─────
ob_start();
if (!$producto): ?>
<div class="tp-panel tp-empty">
    <i class="ti ti-list-check"></i>
    <p>Selecciona un producto para empezar a gestionar sus toppings y extras.</p>
</div>
<?php else: ?>

<div class="tp-actions-head">
    <h3>Grupos de <?= limpiar((string)$producto['nombre']) ?></h3>
    <button class="tp-btn tp-btn-main" type="button" id="btnNuevoGrupo" onclick="toggleFormNuevoGrupo(this)">
        <i class="ti ti-plus"></i> Nuevo grupo
    </button>
</div>

<section id="formNuevoGrupo" class="tp-panel" style="display:none;">
    <form method="POST">
        <input type="hidden" name="accion" value="guardar_grupo">
        <input type="hidden" name="producto_id" value="<?= $productoId ?>">

        <div class="tp-form-grid">
            <div class="tp-field">
                <label>Nombre del grupo</label>
                <input type="text" name="nombre" placeholder="Ej: Elige tu porcion" required>
            </div>
            <div class="tp-field">
                <label>Tipo</label>
                <select name="tipo">
                    <option value="radio">Radio (elige uno)</option>
                    <option value="checkbox">Checkbox (varios)</option>
                </select>
            </div>
            <div class="tp-field">
                <label>Min opciones</label>
                <input type="number" name="min_opciones" value="0" min="0">
            </div>
            <div class="tp-field">
                <label>Max opciones</label>
                <input type="number" name="max_opciones" value="1" min="1">
            </div>
            <div class="tp-field">
                <label>Orden</label>
                <input type="number" name="orden" value="0" min="0">
            </div>
            <div class="tp-field">
                <label>&nbsp;</label>
                <label class="tp-check"><input type="checkbox" name="requerido"> Obligatorio</label>
            </div>
        </div>

        <div class="tp-inline-actions">
            <button type="submit" class="tp-btn tp-btn-main"><i class="ti ti-check"></i> Guardar grupo</button>
            <button type="button" class="tp-btn tp-btn-gray" onclick="toggleFormNuevoGrupo(document.getElementById('btnNuevoGrupo'))">Cancelar</button>
        </div>
    </form>
</section>

<?php if (empty($grupos)): ?>
<div class="tp-panel tp-empty">
    <i class="ti ti-circle-off"></i>
    <p>Este producto aun no tiene grupos. Crea el primero para activar toppings.</p>
</div>
<?php else: ?>
<section class="tp-group-list">
    <?php foreach ($grupos as $g): ?>
    <article class="tp-group-card">
        <header class="tp-group-head">
            <div class="tp-group-title">
                <i class="ti ti-<?= $g['tipo'] === 'checkbox' ? 'checkbox' : 'circle-dot' ?>"></i>
                <?= limpiar((string)$g['nombre']) ?>
            </div>
            <div class="tp-group-meta">
                <span class="tp-pill tp-pill-type"><?= $g['tipo'] === 'checkbox' ? 'Varios' : 'Uno' ?></span>
                <?php if ((int)$g['requerido'] === 1): ?><span class="tp-pill tp-pill-required">Obligatorio</span><?php endif; ?>
                <span class="tp-pill tp-pill-count"><?= count($g['opciones'] ?? []) ?> opciones</span>
                <button class="tp-btn tp-btn-warn" type="button" onclick="toggleEditGrupo(<?= (int)$g['id'] ?>)"><i class="ti ti-pencil"></i></button>
               <form method="POST" class="tp-form-eliminar" data-mensaje="¿Eliminar este grupo y todas sus opciones?">
<input type="hidden" name="accion" value="eliminar_grupo">
                        <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                        <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                        <button type="submit" class="tp-btn tp-btn-danger"><i class="ti ti-trash"></i></button>
                    </form>
            </div>
        </header>

        <div class="tp-group-body">
            <div class="tp-edit-panel" id="editGrupo<?= (int)$g['id'] ?>" style="<?= (int)$grupoActivo === (int)$g['id'] ? 'display:block;' : '' ?>">
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_grupo">
                    <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                    <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                    <div class="tp-form-grid">
                        <div class="tp-field">
                            <label>Nombre</label>
                            <input type="text" name="nombre" value="<?= limpiar((string)$g['nombre']) ?>" required>
                        </div>
                        <div class="tp-field">
                            <label>Tipo</label>
                            <select name="tipo">
                                <option value="radio" <?= $g['tipo'] === 'radio' ? 'selected' : '' ?>>Radio</option>
                                <option value="checkbox" <?= $g['tipo'] === 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                            </select>
                        </div>
                        <div class="tp-field">
                            <label>Min opciones</label>
                            <input type="number" name="min_opciones" value="<?= (int)$g['min_opciones'] ?>" min="0">
                        </div>
                        <div class="tp-field">
                            <label>Max opciones</label>
                            <input type="number" name="max_opciones" value="<?= (int)$g['max_opciones'] ?>" min="1">
                        </div>
                        <div class="tp-field">
                            <label>Orden</label>
                            <input type="number" name="orden" value="<?= (int)$g['orden'] ?>" min="0">
                        </div>
                        <div class="tp-field">
                            <label>&nbsp;</label>
                            <label class="tp-check"><input type="checkbox" name="requerido" <?= (int)$g['requerido'] === 1 ? 'checked' : '' ?>> Obligatorio</label>
                        </div>
                    </div>
                    <div class="tp-inline-actions">
                        <button type="submit" class="tp-btn tp-btn-main">Actualizar grupo</button>
                        <button type="button" class="tp-btn tp-btn-gray" onclick="toggleEditGrupo(<?= (int)$g['id'] ?>)">Cancelar</button>
                    </div>
                </form>
            </div>

            <div class="tp-options-list">
              <?php foreach ($g['opciones'] as $opIndex => $op): ?>
                <?php
                    $tpPaleta = ['c0','c1','c2','c3','c4','c5','c6','c7'];
                    $tpColor = $tpPaleta[$opIndex % count($tpPaleta)];
                    $tpClaseColor = 'tp-opt-' . $tpColor . ((int)$op['disponible'] === 1 ? '' : ' tp-opt-oculta');
                ?>
                <article class="tp-option-item <?= $tpClaseColor ?>">
                    <div class="tp-option-row">
                        <div class="tp-option-name"><?= limpiar((string)$op['nombre']) ?></div>
                        <div class="tp-option-sub">
                            <?= (float)$op['precio_extra'] > 0
                                ? '<span class="tp-option-price-up">+ S/ ' . number_format((float)$op['precio_extra'], 2) . '</span>'
                                : '<span class="tp-option-price-free">Sin costo extra</span>' ?>
                        </div>
                        <div class="tp-option-sub">
                            <?= (int)$op['disponible'] === 1 ? 'Visible' : 'Oculta' ?>
                        </div>
                        <div class="tp-option-actions">
                            <button type="button" class="tp-btn tp-btn-soft" onclick="toggleEditOpcion(<?= (int)$op['id'] ?>)" title="Editar opcion"><i class="ti ti-pencil"></i></button>
                            <form method="POST" class="tp-form-eliminar" data-mensaje="¿Eliminar esta opción?">
<input type="hidden" name="accion" value="eliminar_opcion">
                                    <input type="hidden" name="id" value="<?= (int)$op['id'] ?>">
                                    <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
                                    <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                                    <button type="submit" class="tp-btn tp-btn-danger" title="Eliminar opcion"><i class="ti ti-trash"></i></button>
                                </form>
                        </div>
                    </div>

                    <div class="tp-option-edit-panel" id="editOpcion<?= (int)$op['id'] ?>">
                        <form method="POST">
                            <input type="hidden" name="accion" value="guardar_opcion">
                            <input type="hidden" name="id" value="<?= (int)$op['id'] ?>">
                            <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
                            <input type="hidden" name="producto_id" value="<?= $productoId ?>">

                            <div class="tp-form-inline">
                                <div class="tp-field">
                                    <label>Nombre</label>
                                    <input type="text" name="nombre" value="<?= limpiar((string)$op['nombre']) ?>" required>
                                </div>
                                <div class="tp-field">
                                    <label>Precio extra (S/)</label>
                                    <input type="number" name="precio_extra" value="<?= (float)$op['precio_extra'] ?>" min="0" step="0.10">
                                </div>
                               <div class="tp-field">
                                    <label>Posición (1 = primero)</label>
                                    <input type="number" name="orden" value="<?= $opIndex + 1 ?>" min="1">
                                </div>
                                <div class="tp-field">
                                    <label class="tp-check"><input type="checkbox" name="disponible" <?= (int)$op['disponible'] === 1 ? 'checked' : '' ?>> Visible</label>
                                </div>
                            </div>
                            <div class="tp-inline-actions">
                                <button type="submit" class="tp-btn tp-btn-main">Guardar</button>
                                <button type="button" class="tp-btn tp-btn-gray" onclick="toggleEditOpcion(<?= (int)$op['id'] ?>)">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>

                <?php if (empty($g['opciones'])): ?>
                <div class="tp-empty" style="padding:18px 10px;">
                    <i class="ti ti-mood-empty" style="font-size:30px;"></i>
                    <p>Sin opciones en este grupo.</p>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <button class="tp-btn tp-btn-gray" type="button" onclick="toggleNuevaOpcion(<?= (int)$g['id'] ?>)">
                    <i class="ti ti-plus"></i> Agregar opcion
                </button>
              <div class="tp-add-option-panel" id="nuevaOpcion<?= (int)$g['id'] ?>">
    <form method="POST">
        <input type="hidden" name="accion" value="guardar_opcion">
        <input type="hidden" name="grupo_id" value="<?= (int)$g['id'] ?>">
        <input type="hidden" name="producto_id" value="<?= $productoId ?>">

        <div class="tp-form-inline">
            <div class="tp-field">
                <label>Nombre</label>
                <input type="text" name="nombre" placeholder="Ej: Salsa picante" required>
            </div>
            <div class="tp-field">
                <label>Precio extra (S/)</label>
                <input type="number" name="precio_extra" value="0" min="0" step="0.10">
            </div>
            <div class="tp-field">
                <label>Posición (1 = primero)</label>
                <input type="number" name="orden" value="<?= count($g['opciones'] ?? []) + 1 ?>" min="1">
            </div>
            <div class="tp-field">
                <label class="tp-check"><input type="checkbox" name="disponible" checked> Visible</label>
            </div>
        </div>
                        <div class="tp-inline-actions">
                            <button type="submit" class="tp-btn tp-btn-main">Guardar opcion</button>
                            <button type="button" class="tp-btn tp-btn-gray" onclick="toggleNuevaOpcion(<?= (int)$g['id'] ?>)">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php endif;
$htmlMain = ob_get_clean();

// ───── Si es petición AJAX, responder JSON y terminar aquí ─────
if ($esAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'error' => $errorPost,
        'main' => $htmlMain,
        'kpis' => $htmlKpis,
        'sidebarMeta' => $htmlSidebarMeta,
        'productoId' => $productoId,
    ]);
    exit;
}
?>

<?php if ($okMensaje !== ''): ?>
<div class="tp-toast tp-toast-ok" id="tpToast"><i class="ti ti-circle-check"></i> <?= limpiar($okMensaje) ?></div>
<?php endif; ?>
<?php if ($mensaje): ?><div class="tp-toast tp-toast-ok"><i class="ti ti-circle-check"></i> <?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="tp-toast tp-toast-err"><i class="ti ti-alert-circle"></i> <?= limpiar($error) ?></div><?php endif; ?>

<style>
.tp-shell { display: grid; gap: 18px; }

.tp-toast {
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 12px;
    padding: 11px 14px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid transparent;
}
.tp-toast-ok { background: #f0fdf4; border: none; box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara); color: #166534; }
.tp-toast-err { background: #fef2f2; border: none; box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara); color: #b91c1c; }

.tp-header {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 14px;
    align-items: center;
    border: none;
    border-radius: 22px;
    padding: 18px 20px;
    background: var(--neu-base);
    box-shadow: 10px 10px 22px var(--neu-sombra-oscura), -10px -10px 22px var(--neu-sombra-clara);
}
.tp-header h2 { margin: 0; font-size: 22px; color: #0f172a; }
.tp-header p { margin: 5px 0 0; font-size: 12px; color: #64748b; }

.tp-kpis { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.tp-kpi {
    border-radius: 14px;
    border: none;
    background: var(--neu-base);
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara);
    min-width: 76px;
    text-align: center;
    padding: 10px 10px;
}
.tp-kpi strong { display: block; font-size: 20px; color: #1d4ed8; line-height: 1; }
.tp-kpi span { font-size: 11px; color: #475569; font-weight: 700; }

.tp-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 16px;
    align-items: start;
}

.tp-panel {
    border: none;
    border-radius: 20px;
    background: var(--neu-base);
    padding: 16px;
    box-shadow: 8px 8px 18px var(--neu-sombra-oscura), -8px -8px 18px var(--neu-sombra-clara);
}

.tp-sidebar { position: sticky; top: 78px; display: grid; gap: 12px; }
.tp-label { font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; display: block; }
.tp-product-picker { display: grid; gap: 10px; }
.tp-product-picker-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.tp-picker-title { font-size: 12px; font-weight: 800; color: #0f172a; }
.tp-picker-count { font-size: 11px; color: #64748b; background: #f1f5f9; border-radius: 999px; padding: 4px 8px; }
.tp-product-scroll {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 6px;
    scroll-snap-type: x proximity;
}
.tp-product-scroll::-webkit-scrollbar { height: 7px; }
.tp-product-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
.tp-product-card {
    min-width: 180px;
    max-width: 180px;
    border: none;
    border-radius: 16px;
    background: var(--neu-base);
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 5px 5px 12px var(--neu-sombra-oscura), -5px -5px 12px var(--neu-sombra-clara);
    transition: transform .2s ease, box-shadow .2s ease;
    scroll-snap-align: start;
}
.tp-product-card:hover {
    transform: translateY(-3px);
}
.tp-product-card.is-selected {
    box-shadow: 5px 5px 12px var(--neu-sombra-oscura), -5px -5px 12px var(--neu-sombra-clara), 0 0 0 2px #E8590C;
}
.tp-product-thumb {
    height: 84px;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 8px var(--neu-sombra-oscura);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 8px;
}
.tp-product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 10px;
}
.tp-product-body { padding: 10px; display: grid; gap: 5px; }
.tp-product-name { font-size: 13px; font-weight: 800; color: #0f172a; line-height: 1.25; }
.tp-product-meta { font-size: 11px; color: #64748b; }
.tp-product-price { font-size: 13px; font-weight: 800; color: #0f766e; }
.tp-product-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 3px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    background: var(--neu-base);
    box-shadow: inset 2px 2px 4px var(--neu-sombra-oscura);
    color: #0f766e;
}
.tp-product-badge.is-off { color: #b91c1c; }
.tp-product-meta-row { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.tp-product-meta-row .tp-product-badge { margin-top: 2px; }
.tp-product-meta { margin-top: 0; }
.tp-product-meta p { margin: 0; font-size: 12px; color: #64748b; }
.tp-product-main-price { margin-top: 6px; color: #0f766e; font-size: 13px; font-weight: 800; }

.tp-actions-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
}
.tp-actions-head h3 { margin: 0; font-size: 17px; color: #0f172a; }

.tp-btn {
    border: 0;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    padding: 8px 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.tp-btn-main { background: linear-gradient(135deg, #ff8a3d, #E8590C); color: #fff; box-shadow: 4px 4px 10px rgba(232,89,12,.35); transition: transform .15s; }
.tp-btn-main:hover { transform: translateY(-2px); }
.tp-btn-soft { background: var(--neu-base); color: #3730a3; box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara); }
.tp-btn-warn { background: var(--neu-base); color: #c2410c; box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara); }
.tp-btn-danger { background: var(--neu-base); color: #b91c1c; box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara); }
.tp-btn-gray { background: var(--neu-base); color: #4a5160; box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara); }

.tp-form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.tp-field { display: grid; gap: 5px; }
.tp-field label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}
.tp-field input,
.tp-field select {
    border: none;
    border-radius: 10px;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
    padding: 9px 10px;
    font-size: 13px;
    color: #333;
}
.tp-field input:focus,
.tp-field select:focus {
    outline: none;
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35);
}

.tp-inline-actions { display: flex; gap: 8px; margin-top: 10px; }
.tp-check { font-size: 12px; display: inline-flex; align-items: center; gap: 6px; color: #334155; }

.tp-empty {
    text-align: center;
    padding: 38px 14px;
    color: #94a3b8;
}
.tp-empty i { font-size: 40px; display: block; margin-bottom: 8px; }

.tp-group-list { display: grid; gap: 12px; }
.tp-group-card { border: none; border-radius: 18px; background: var(--neu-base); overflow: hidden; box-shadow: 6px 6px 14px var(--neu-sombra-oscura), -6px -6px 14px var(--neu-sombra-clara); }

.tp-group-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    background: transparent;
    border-bottom: 3px solid #E8590C;
}
.tp-group-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
    color: #0f172a;
    font-size: 14px;
}
.tp-group-meta { margin-left: auto; display: flex; gap: 6px; align-items: center; }
.tp-pill {
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid transparent;
}
.tp-pill-type { background: var(--neu-base); box-shadow: inset 2px 2px 4px var(--neu-sombra-oscura); color: #3730a3; border-color: transparent; }
.tp-pill-required { background: var(--neu-base); box-shadow: inset 2px 2px 4px var(--neu-sombra-oscura); color: #92400e; border-color: transparent; }
.tp-pill-count { background: var(--neu-base); box-shadow: inset 2px 2px 4px var(--neu-sombra-oscura); color: #0f766e; border-color: transparent; }

.tp-group-body { padding: 12px 14px 14px; display: grid; gap: 12px; }
.tp-edit-panel,
.tp-add-option-panel {
    display: none;
    border: none;
    border-radius: 14px;
    background: var(--neu-base);
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara);
    padding: 12px;
}

.tp-options-list {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.tp-masonry-col {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1 1 170px;
    min-width: 170px;
    max-width: 220px;
}
.tp-option-item {
    border: none;
    border-radius: 16px;
    background: var(--neu-base);
    padding: 10px;
    min-height: 170px;
    display: grid;
    grid-template-rows: auto auto 1fr auto;
    gap: 8px;
    box-shadow: 5px 5px 12px var(--neu-sombra-oscura), -5px -5px 12px var(--neu-sombra-clara);
    transition: transform .15s ease;
}
.tp-option-item:hover { transform: translateY(-2px); }

/* ===== Paleta rotativa de colores por opción ===== */
.tp-option-item[class*="tp-opt-c"] { position: relative; padding-top: 16px; }
.tp-option-item[class*="tp-opt-c"]::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 6px;
    border-radius: 16px 16px 0 0;
}

.tp-opt-c0 { background: #fdf2f8; }
.tp-opt-c0::before { background: #ec4899; }

.tp-opt-c1 { background: #eff6ff; }
.tp-opt-c1::before { background: #3b82f6; }

.tp-opt-c2 { background: #ecfdf5; }
.tp-opt-c2::before { background: #10b981; }

.tp-opt-c3 { background: #f5f3ff; }
.tp-opt-c3::before { background: #8b5cf6; }

.tp-opt-c4 { background: #fff7ed; }
.tp-opt-c4::before { background: #f97316; }

.tp-opt-c5 { background: #fefce8; }
.tp-opt-c5::before { background: #eab308; }

.tp-opt-c6 { background: #f0fdfa; }
.tp-opt-c6::before { background: #14b8a6; }

.tp-opt-c7 { background: #fff1f2; }
.tp-opt-c7::before { background: #f43f5e; }

/* Opción A: oculta = mismo color pero "apagado" */
.tp-opt-oculta { opacity: 0.55; }

/* ===== Modo oscuro: paleta un poco más tenue sobre fondo oscuro ===== */
body.modo-oscuro .tp-opt-c0 { background: rgba(236,72,153,.12); }
body.modo-oscuro .tp-opt-c1 { background: rgba(59,130,246,.12); }
body.modo-oscuro .tp-opt-c2 { background: rgba(16,185,129,.12); }
body.modo-oscuro .tp-opt-c3 { background: rgba(139,92,246,.12); }
body.modo-oscuro .tp-opt-c4 { background: rgba(249,115,22,.12); }
body.modo-oscuro .tp-opt-c5 { background: rgba(234,179,8,.12); }
body.modo-oscuro .tp-opt-c6 { background: rgba(20,184,166,.12); }
body.modo-oscuro .tp-opt-c7 { background: rgba(244,63,94,.12); }
.tp-option-row {
    display: contents;
}
.tp-option-name {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
    min-height: 34px;
}
.tp-option-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 0;
    line-height: 1.35;
}
.tp-option-price-up { color: #0f766e; font-weight: 700; }
.tp-option-price-free { color: #94a3b8; font-weight: 700; }
.tp-option-actions {
    margin-top: auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}
.tp-option-actions .tp-btn {
    justify-content: center;
    padding: 7px 8px;
    font-size: 11px;
}

.tp-option-edit-panel {
    display: none;
    margin-top: 10px;
    border-top: 1px solid #e2e8f0;
    padding-top: 10px;
}

.tp-form-inline {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 8px;
    align-items: end;
}

.tp-option-edit-panel .tp-form-inline {
    grid-template-columns: 1fr;
    align-items: stretch;
}

.tp-option-edit-panel .tp-field {
    width: 100%;
}

.tp-option-edit-panel .tp-field label.tp-check {
    justify-content: flex-start;
}

@media (max-width: 1080px) {
    .tp-layout { grid-template-columns: 1fr; }
    .tp-sidebar { position: static; }
}
@media (max-width: 780px) {
    .tp-header { grid-template-columns: 1fr; }
    .tp-kpis { justify-content: flex-start; }
    .tp-form-grid { grid-template-columns: 1fr 1fr; }
    .tp-form-inline { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 560px) {
    .tp-form-grid,
    .tp-form-inline { grid-template-columns: 1fr; }
    .tp-options-list { columns: 1; }
    .tp-option-item { min-height: 160px; }
}
@media (max-width: 420px) {
    .tp-options-list { columns: 1; }
}

/* ===== Modo oscuro: toppings ===== */
body.modo-oscuro .tp-header h2,
body.modo-oscuro .tp-picker-title,
body.modo-oscuro .tp-product-name,
body.modo-oscuro .tp-group-title,
body.modo-oscuro .tp-actions-head h3,
body.modo-oscuro .tp-option-name { color: #f2f2f4; }
body.modo-oscuro .tp-header p,
body.modo-oscuro .tp-picker-count,
body.modo-oscuro .tp-product-meta,
body.modo-oscuro .tp-option-sub,
body.modo-oscuro .tp-kpi span,
body.modo-oscuro .tp-empty { color: #9aa0ac; }
body.modo-oscuro .tp-field label,
body.modo-oscuro .tp-check { color: #cfd3dc; }
body.modo-oscuro .tp-field input,
body.modo-oscuro .tp-field select { color: #ececed; }
body.modo-oscuro .tp-kpi strong { color: #ff8a3d; }





/* ===== Modal de confirmación (reemplaza confirm() nativo) ===== */
.tp-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.5);
    backdrop-filter: blur(3px);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.tp-modal-box {
    background: var(--neu-base);
    border-radius: 22px;
    padding: 28px;
    max-width: 380px;
    width: 100%;
    text-align: center;
    box-shadow: 14px 14px 32px rgba(0,0,0,.28), -8px -8px 24px var(--neu-sombra-clara);
}
.tp-modal-icono {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: rgba(220,53,69,.12);
    color: #dc3545;
    font-size: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.tp-modal-box h3 { margin: 0 0 8px; font-size: 17px; color: #0f172a; }
.tp-modal-box p { margin: 0 0 20px; font-size: 13.5px; color: #64748b; }
.tp-modal-botones { display: flex; gap: 10px; }
.tp-modal-botones .tp-btn { flex: 1; justify-content: center; padding: 10px; }
.tp-btn-danger-solido {
    background: linear-gradient(135deg, #ff5c6c, #dc3545);
    color: #fff;
    box-shadow: 4px 4px 10px rgba(220,53,69,.35);
}
.tp-btn-danger-solido:hover { transform: translateY(-2px); }

body.modo-oscuro .tp-modal-box h3 { color: #f2f2f4; }
body.modo-oscuro .tp-modal-box p { color: #9aa0ac; }
</style>

<div class="tp-shell">
    <section class="tp-header">
        <div>
            <h2>Toppings y Extras</h2>
            <p>Configura grupos de opciones y extras por producto con una vista limpia y ordenada.</p>
        </div>
       <div class="tp-kpis" id="tpKpis"><?= $htmlKpis ?></div>
    </section>

    <div class="tp-layout">
        <aside class="tp-sidebar">
            <div class="tp-panel">
                <div class="tp-product-picker">
                    <div class="tp-product-picker-head">
                        <span class="tp-picker-title">Selecciona un producto</span>
                        <span class="tp-picker-count"><?= count($productos) ?> productos</span>
                    </div>

                    <div class="tp-product-scroll">
                        <?php foreach ($productos as $pr): ?>
                        <?php
                            $selected = (int)$pr['id'] === $productoId;
                            $imgPr = rutaImagenProductoAdmin((string)($pr['imagen'] ?? ''));
                            $precioPr = (float)($pr['precio_oferta'] > 0 ? $pr['precio_oferta'] : $pr['precio']);
                            $estadoPr = (int)$pr['disponible'] === 1 ? 'Disponible' : 'Oculto';
                        ?>
                        <a class="tp-product-card <?= $selected ? 'is-selected' : '' ?>" href="opciones_producto.php?producto=<?= (int)$pr['id'] ?>">
                            <div class="tp-product-thumb">
                                <?php if ($imgPr !== ''): ?>
                                <img src="<?= limpiar($imgPr) ?>" alt="<?= limpiar((string)$pr['nombre']) ?>" onerror="this.style.display='none';this.parentElement.innerHTML='<span style=\'font-size:28px;color:#94a3b8;\'>🍽️</span>'">
                                <?php else: ?>
                                <span style="font-size:28px;color:#94a3b8;">🍽️</span>
                                <?php endif; ?>
                            </div>
                            <div class="tp-product-body">
                                <div class="tp-product-name"><?= limpiar((string)$pr['nombre']) ?></div>
                                <div class="tp-product-meta-row">
                                    <span class="tp-product-meta"><?= limpiar((string)$pr['categoria']) ?></span>
                                    <span class="tp-product-badge <?= (int)$pr['disponible'] === 1 ? '' : 'is-off' ?>"><?= limpiar($estadoPr) ?></span>
                                </div>
                                <div class="tp-product-price">S/ <?= number_format($precioPr, 2) ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

          
     
<div id="tpSidebarMeta"><?= $htmlSidebarMeta ?></div>
            </div>
        </aside>
        <main id="tpMain"><?= $htmlMain ?></main>
    </div>                
    <div class="tp-modal-overlay" id="tpModalConfirmar" style="display:none;">
        <div class="tp-modal-box">
            <div class="tp-modal-icono"><i class="ti ti-alert-triangle"></i></div>
            <h3 id="tpModalTitulo">¿Eliminar?</h3>
            <p id="tpModalTexto">Esta acción no se puede deshacer.</p>
            <div class="tp-modal-botones">
                <button type="button" class="tp-btn tp-btn-gray" id="tpModalCancelar">Cancelar</button>
                <button type="button" class="tp-btn tp-btn-danger-solido" id="tpModalConfirmarBtn"><i class="ti ti-trash"></i> Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleById(id, displayType) {
    const el = document.getElementById(id);
    if (!el) return;
    const show = el.style.display === 'none' || el.style.display === '';
    el.style.display = show ? displayType : 'none';
}

function toggleFormNuevoGrupo(btn) {
    const form = document.getElementById('formNuevoGrupo');
    if (!form || !btn) return;
    const abrir = form.style.display === 'none' || form.style.display === '';
    form.style.display = abrir ? 'block' : 'none';
    btn.innerHTML = abrir
        ? '<i class="ti ti-x"></i> Cerrar'
        : '<i class="ti ti-plus"></i> Nuevo grupo';
}

function toggleEditGrupo(id) {
    toggleById('editGrupo' + id, 'block');
}

function toggleEditOpcion(id) {
    toggleById('editOpcion' + id, 'block');
}

function toggleNuevaOpcion(gid) {
    toggleById('nuevaOpcion' + gid, 'block');
}

// ── Utilidades AJAX compartidas (guardar/eliminar sin recargar la página) ──
const tpMainEl = document.getElementById('tpMain');
const tpKpisEl = document.getElementById('tpKpis');
const tpSidebarMetaEl = document.getElementById('tpSidebarMeta');

function tpMostrarToast(mensaje, esError) {
    const shell = document.querySelector('.tp-shell');
    if (!shell) return;
    const toast = document.createElement('div');
    toast.className = 'tp-toast ' + (esError ? 'tp-toast-err' : 'tp-toast-ok');
    toast.innerHTML = '<i class="ti ti-' + (esError ? 'alert-circle' : 'circle-check') + '"></i> ' + mensaje;
    shell.insertBefore(toast, shell.firstChild);
    setTimeout(function () {
        toast.style.transition = 'opacity .35s';
        toast.style.opacity = '0';
        setTimeout(function () { toast.remove(); }, 360);
    }, 3200);
}

function tpAplicarFragmentos(data) {
    if (tpMainEl && data.main !== undefined) tpMainEl.innerHTML = data.main;
    if (tpKpisEl && data.kpis !== undefined) tpKpisEl.innerHTML = data.kpis;
    if (tpSidebarMetaEl && data.sidebarMeta !== undefined) tpSidebarMetaEl.innerHTML = data.sidebarMeta;
}

function tpEnviarFormulario(form) {
    const formData = new FormData(form);
    formData.set('ajax', '1');

    const btnSubmit = form.querySelector('button[type="submit"]');
    let textoOriginal = null;
    if (btnSubmit) {
        textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
    }

    fetch('opciones_producto.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
        .then(function (r) { return r.json(); })
.then(function (data) {
            if (!data) throw new Error('Respuesta inválida');
            tpAplicarFragmentos(data);
            tpReflowTodasLasListas();
            if (data.error) {
                tpMostrarToast(data.error, true);
            } else {
                tpMostrarToast('Cambios guardados correctamente.', false);
            }
        })
        .catch(function () {
            // Si algo falla, hacemos fallback al envío normal (con recarga)
            form.submit();
        })
        .finally(function () {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                if (textoOriginal !== null) btnSubmit.innerHTML = textoOriginal;
            }
        });
}

// ── Interceptar todos los formularios POST del panel (guardar grupo/opción) ──
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.method.toLowerCase() !== 'post') return;
    if (!form.closest('.tp-shell')) return;
    if (form.classList.contains('tp-form-eliminar')) return; // esos pasan por el modal

    e.preventDefault();
    tpEnviarFormulario(form);
});

// ── Modal de confirmación para eliminar (ahora también vía AJAX) ──
(function () {
    const overlay = document.getElementById('tpModalConfirmar');
    const texto   = document.getElementById('tpModalTexto');
    const btnCancelar = document.getElementById('tpModalCancelar');
    const btnConfirmar = document.getElementById('tpModalConfirmarBtn');
    let formPendiente = null;

    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.tp-form-eliminar');
        if (!form) return;
        e.preventDefault();
        formPendiente = form;
        texto.textContent = form.dataset.mensaje || '¿Confirmas esta acción?';
        overlay.style.display = 'flex';
    });

    btnCancelar.addEventListener('click', function () {
        overlay.style.display = 'none';
        formPendiente = null;
    });

    btnConfirmar.addEventListener('click', function () {
        if (formPendiente) tpEnviarFormulario(formPendiente);
        overlay.style.display = 'none';
        formPendiente = null;
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            overlay.style.display = 'none';
            formPendiente = null;
        }
    });
})();

// ── Navegación AJAX entre productos (sin recargar la página) ──
(function () {
    const mainEl = document.getElementById('tpMain');
    const kpisEl = document.getElementById('tpKpis');
    const sidebarMetaEl = document.getElementById('tpSidebarMeta');

    function actualizarSeleccionCarrusel(productoId) {
        document.querySelectorAll('.tp-product-card').forEach(function (card) {
            try {
                const url = new URL(card.href, window.location.origin);
                const idCard = url.searchParams.get('producto');
                card.classList.toggle('is-selected', String(idCard) === String(productoId));
            } catch (_) {}
        });
    }

    function cargarProducto(productoId, pushUrl) {
        if (!productoId) return;
        if (mainEl) mainEl.style.opacity = '0.5';

        fetch('opciones_producto.php?producto=' + encodeURIComponent(productoId) + '&ajax=1', {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) throw new Error('Respuesta inválida');
                if (mainEl) mainEl.innerHTML = data.main;
                if (kpisEl) kpisEl.innerHTML = data.kpis;
                if (sidebarMetaEl) sidebarMetaEl.innerHTML = data.sidebarMeta;
                tpReflowTodasLasListas();
                actualizarSeleccionCarrusel(productoId);
                if (pushUrl) {
                    history.pushState({ productoId: productoId }, '', 'opciones_producto.php?producto=' + productoId);
                }
            })
            .catch(function () {
                window.location.href = 'opciones_producto.php?producto=' + productoId;
            })
            .finally(function () {
                if (mainEl) mainEl.style.opacity = '1';
            });
    }

    document.querySelectorAll('.tp-product-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            e.preventDefault();
            try {
                const url = new URL(card.href, window.location.origin);
                const productoId = url.searchParams.get('producto');
                cargarProducto(productoId, true);
            } catch (_) {}
        });
    });

    window.addEventListener('popstate', function () {
        const params = new URLSearchParams(window.location.search);
        const productoId = params.get('producto') || 0;
        cargarProducto(productoId, false);
    });
})();

// ── Desplazar el carrusel de productos hasta el que está seleccionado ──
(function () {
    const seleccionado = document.querySelector('.tp-product-card.is-selected');
    if (seleccionado) {
        seleccionado.scrollIntoView({ behavior: 'auto', inline: 'center', block: 'nearest' });
    }
})();

// ── Masonry real: acomoda las opciones en columnas sin reordenarlas al editar ──
function tpAplicarMasonry(container) {
    if (!container) return;
    const items = Array.from(container.querySelectorAll('.tp-option-item'));
    if (items.length === 0) return;

    const minColWidth = 180; // ancho mínimo por columna (170px + gap aprox)
    const containerWidth = container.offsetWidth || minColWidth;
    let numCols = Math.max(1, Math.floor(containerWidth / minColWidth));
    numCols = Math.min(numCols, items.length);

    const cols = [];
    for (let i = 0; i < numCols; i++) {
        const col = document.createElement('div');
        col.className = 'tp-masonry-col';
        cols.push(col);
    }

    const alturas = new Array(numCols).fill(0);
    items.forEach(function (item) {
        let idxMin = 0;
        for (let i = 1; i < numCols; i++) {
            if (alturas[i] < alturas[idxMin]) idxMin = i;
        }
        cols[idxMin].appendChild(item);
        alturas[idxMin] += item.offsetHeight + 10;
    });

    container.innerHTML = '';
    cols.forEach(function (col) { container.appendChild(col); });
}

function tpReflowTodasLasListas() {
    document.querySelectorAll('.tp-options-list').forEach(function (lista) {
        tpAplicarMasonry(lista);
    });
}

// Reacomodar al cargar la página
tpReflowTodasLasListas();

// Reacomodar si cambia el tamaño de ventana (con pequeño retraso)
let tpResizeTimeout;
window.addEventListener('resize', function () {
    clearTimeout(tpResizeTimeout);
    tpResizeTimeout = setTimeout(tpReflowTodasLasListas, 200);
});

// ── Ocultar el toast automáticamente ──
setTimeout(function () {
    const toast = document.getElementById('tpToast');
    if (!toast) return;
    toast.style.transition = 'opacity .35s';
    toast.style.opacity = '0';
    setTimeout(function () { toast.remove(); }, 360);
}, 3800);
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
