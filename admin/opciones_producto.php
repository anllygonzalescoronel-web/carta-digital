<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$db = getDB();

$productoId = (int)($_GET['producto'] ?? 0);
$grupoId    = (int)($_GET['grupo'] ?? 0);

// ─────────────── Procesar acciones POST ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $backProducto = (int)($_POST['producto_id'] ?? $productoId);
    $backGrupo    = (int)($_POST['grupo_id']    ?? 0);
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
                $ord  = (int)($_POST['orden'] ?? 0);
                if ($nom === '') throw new RuntimeException('El nombre de la opción es obligatorio.');
                if ($oid > 0) {
                    $db->prepare('UPDATE producto_opciones SET nombre=:n, precio_extra=:p, disponible=:d, orden=:o WHERE id=:id')
                       ->execute(['n'=>$nom,'p'=>$prEx,'d'=>$disp,'o'=>$ord,'id'=>$oid]);
                } else {
                    $db->prepare('INSERT INTO producto_opciones (grupo_id,nombre,precio_extra,disponible,orden) VALUES (:g,:n,:p,:d,:o)')
                       ->execute(['g'=>$gid,'n'=>$nom,'p'=>$prEx,'d'=>$disp,'o'=>$ord]);
                }
                $productoId = $backProducto;
                $grupoId    = $gid;
                break;

            case 'eliminar_opcion':
                $oid = (int)$_POST['id'];
                $db->prepare('DELETE FROM producto_opciones WHERE id=:id')->execute(['id'=>$oid]);
                $productoId = $backProducto;
                $grupoId    = $backGrupo;
                break;
        }
    } catch (Throwable $e) {
        // Guardar error en sesión para mostrarlo después del redirect
        $_SESSION['opciones_error'] = 'Error: ' . $e->getMessage();
    }
    $qs = 'producto=' . $productoId . ($grupoId ? '&grupo=' . $grupoId : '');
    header('Location: opciones_producto.php?' . $qs);
    exit;
}

$tituloPagina = 'Opciones / Toppings';
$paginaActual = 'opciones';
require __DIR__ . '/_layout_top.php';

$mensaje = '';
$error   = '';

// Recuperar error de sesión si existe
if (!empty($_SESSION['opciones_error'])) {
    $error = $_SESSION['opciones_error'];
    unset($_SESSION['opciones_error']);
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
        $stmt = $db->prepare('SELECT * FROM producto_opciones WHERE grupo_id=:id ORDER BY orden');
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
.tp-toast-ok { background: #f0fdf4; border-color: #86efac; color: #166534; }
.tp-toast-err { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

.tp-header {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 14px;
    align-items: center;
    border: 1px solid #dbeafe;
    border-radius: 18px;
    padding: 18px 20px;
    background: linear-gradient(135deg, #eff6ff, #ffffff 62%);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.08);
}
.tp-header h2 { margin: 0; font-size: 22px; color: #0f172a; }
.tp-header p { margin: 5px 0 0; font-size: 12px; color: #64748b; }

.tp-kpis { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.tp-kpi {
    border-radius: 12px;
    border: 1px solid #dbeafe;
    background: #fff;
    min-width: 76px;
    text-align: center;
    padding: 9px 10px;
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
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #fff;
    padding: 16px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
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
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: linear-gradient(145deg, #ffffff, #f8fbff);
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.05);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    scroll-snap-align: start;
}
.tp-product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.12);
    border-color: #93c5fd;
}
.tp-product-card.is-selected {
    border-color: #2563eb;
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.16);
}
.tp-product-thumb {
    height: 84px;
    background: linear-gradient(135deg, #eef2ff, #f8fafc);
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
    background: #ecfeff;
    color: #0f766e;
}
.tp-product-badge.is-off { background: #fef2f2; color: #b91c1c; }
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
.tp-btn-main { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; box-shadow: 0 6px 14px rgba(37, 99, 235, 0.24); }
.tp-btn-soft { background: #eef2ff; color: #3730a3; }
.tp-btn-warn { background: #fff7ed; color: #c2410c; }
.tp-btn-danger { background: #fef2f2; color: #b91c1c; }
.tp-btn-gray { background: #f1f5f9; color: #334155; }

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
    border: 1.5px solid #dbe3ef;
    border-radius: 10px;
    background: #fff;
    padding: 9px 10px;
    font-size: 13px;
}
.tp-field input:focus,
.tp-field select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
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
.tp-group-card { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; overflow: hidden; }

.tp-group-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
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
.tp-pill-type { background: #eef2ff; color: #3730a3; border-color: #c7d2fe; }
.tp-pill-required { background: #fffbeb; color: #92400e; border-color: #fcd34d; }
.tp-pill-count { background: #ecfeff; color: #0f766e; border-color: #99f6e4; }

.tp-group-body { padding: 12px 14px 14px; display: grid; gap: 12px; }
.tp-edit-panel,
.tp-add-option-panel {
    display: none;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    padding: 12px;
}

.tp-options-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 10px;
}
.tp-option-item {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: linear-gradient(165deg, #ffffff, #f8fbff);
    padding: 10px;
    min-height: 170px;
    display: grid;
    grid-template-rows: auto auto 1fr auto;
    gap: 8px;
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
}
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
    .tp-options-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tp-option-item { min-height: 160px; }
}
@media (max-width: 420px) {
    .tp-options-list { grid-template-columns: 1fr; }
}
</style>

<div class="tp-shell">
    <section class="tp-header">
        <div>
            <h2>Toppings y Extras</h2>
            <p>Configura grupos de opciones y extras por producto con una vista limpia y ordenada.</p>
        </div>
        <div class="tp-kpis">
            <div class="tp-kpi"><strong><?= (int)$productoId > 0 ? 1 : 0 ?></strong><span>Producto</span></div>
            <div class="tp-kpi"><strong><?= $totalGrupos ?></strong><span>Grupos</span></div>
            <div class="tp-kpi"><strong><?= $totalOpciones ?></strong><span>Opciones</span></div>
        </div>
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

                <?php if ($producto): ?>
                <div class="tp-product-meta" style="margin-top:12px;padding-top:12px;border-top:1px solid #edf2f7;">
                    <p>Producto seleccionado</p>
                    <h4><?= limpiar((string)$producto['nombre']) ?></h4>
                    <div class="tp-product-main-price">S/ <?= number_format((float)$producto['precio'], 2) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <main>
            <?php if (!$producto): ?>
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
                            <form method="POST" onsubmit="return confirm('¿Eliminar este grupo y todas sus opciones?')">
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
                            <?php foreach ($g['opciones'] as $op): ?>
                            <article class="tp-option-item">
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
                                        <form method="POST" onsubmit="return confirm('¿Eliminar esta opción?')">
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
                                                <label>Orden</label>
                                                <input type="number" name="orden" value="<?= (int)$op['orden'] ?>" min="0">
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
                                            <label>Orden</label>
                                            <input type="number" name="orden" value="0" min="0">
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

            <?php endif; ?>
        </main>
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

setTimeout(function () {
    const toast = document.getElementById('tpToast');
    if (!toast) return;
    toast.style.transition = 'opacity .35s';
    toast.style.opacity = '0';
    setTimeout(function () { toast.remove(); }, 360);
}, 3800);
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
