<?php
$tituloPagina = 'Opciones / Toppings';
$paginaActual = 'opciones';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = '';
$error = '';

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
                $mensaje = 'Grupo guardado.';
                $productoId = $pid;
                break;

            case 'eliminar_grupo':
                $gid = (int)$_POST['id'];
                $db->prepare('DELETE FROM producto_grupos WHERE id=:id')->execute(['id'=>$gid]);
                $mensaje = 'Grupo eliminado.';
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
                $mensaje = 'Opción guardada.';
                $productoId = $backProducto;
                $grupoId    = $gid;
                break;

            case 'eliminar_opcion':
                $oid = (int)$_POST['id'];
                $db->prepare('DELETE FROM producto_opciones WHERE id=:id')->execute(['id'=>$oid]);
                $mensaje = 'Opción eliminada.';
                $productoId = $backProducto;
                $grupoId    = $backGrupo;
                break;
        }
    } catch (Throwable $e) {
        $error = 'Error: ' . $e->getMessage();
    }
    // Redirigir para evitar reenvío del formulario
    $qs = 'producto=' . $productoId . ($grupoId ? '&grupo=' . $grupoId : '');
    header('Location: opciones_producto.php?' . $qs);
    exit;
}

// ─────────────── Datos ───────────────
$productos = $db->query('SELECT p.id, p.nombre, c.nombre AS categoria FROM productos p JOIN categorias c ON c.id=p.categoria_id ORDER BY c.orden, p.orden')->fetchAll();

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

<?php if (isset($_GET['ok'])): ?>
<div class="alerta-ok"><?= limpiar($_GET['ok']) ?></div>
<?php endif; ?>
<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<style>
.op-layout{display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;}
.op-sidebar{width:240px;flex-shrink:0;}
.op-sidebar select{width:100%;padding:10px 12px;border-radius:12px;border:1.5px solid #dde3e0;font-size:14px;cursor:pointer;background:#fff;}
.op-main{flex:1;min-width:280px;}
.op-card{background:#fff;border-radius:18px;box-shadow:0 4px 16px rgba(0,0,0,.07);padding:20px;margin-bottom:18px;}
.op-card h4{margin:0 0 14px;font-size:15px;display:flex;align-items:center;gap:8px;}
.op-card h4 .badge-tipo{font-size:11px;padding:2px 8px;border-radius:20px;background:#eaf3ee;color:#2e7d55;font-weight:600;}
.op-tabla{width:100%;border-collapse:collapse;font-size:13px;}
.op-tabla th{text-align:left;padding:6px 10px;color:#888;font-weight:600;border-bottom:1px solid #eee;}
.op-tabla td{padding:7px 10px;border-bottom:1px solid #f3f3f3;vertical-align:middle;}
.op-tabla tr:last-child td{border:none;}
.btn-sm{padding:5px 12px;border-radius:8px;font-size:12px;cursor:pointer;border:none;font-weight:600;}
.btn-sm-prim{background:var(--primario,#3ea76a);color:#fff;}
.btn-sm-warn{background:#f59e42;color:#fff;}
.btn-sm-danger{background:#e53e3e;color:#fff;}
.btn-sm-ghost{background:#f0f4f2;color:#444;}
.op-form-inline{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:12px;}
.op-form-inline .fld{display:flex;flex-direction:column;gap:4px;font-size:12px;color:#666;font-weight:600;}
.op-form-inline input,.op-form-inline select{padding:7px 10px;border-radius:9px;border:1.5px solid #dde3e0;font-size:13px;background:#fff;}
.op-form-inline input[type=number]{width:80px;}
.op-form-inline input[type=text]{width:180px;}
.op-check{display:flex;align-items:center;gap:6px;font-size:13px;margin-top:4px;}
.precio-extra{color:#2e7d55;font-weight:700;}
.precio-gratis{color:#aaa;}
.sin-grupos{text-align:center;padding:40px;color:#aaa;}
.grupo-acciones{display:flex;gap:6px;}
</style>

<div class="op-layout">
    <!-- Selector de producto -->
    <div class="op-sidebar">
        <div class="op-card">
            <label style="font-size:13px;font-weight:700;color:#555;display:block;margin-bottom:8px;">Selecciona un producto</label>
            <select onchange="window.location='opciones_producto.php?producto='+this.value">
                <option value="0">— Elige un producto —</option>
                <?php foreach ($productos as $pr): ?>
                <option value="<?= $pr['id'] ?>" <?= $pr['id'] === $productoId ? 'selected' : '' ?>>
                    [<?= limpiar($pr['categoria']) ?>] <?= limpiar($pr['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <?php if ($producto): ?>
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #eee;">
                <p style="margin:0;font-size:12px;color:#888;">Producto seleccionado:</p>
                <p style="margin:4px 0 0;font-weight:700;font-size:14px;"><?= limpiar($producto['nombre']) ?></p>
                <p style="margin:2px 0 0;color:#2e7d55;font-size:13px;">S/ <?= number_format($producto['precio'], 2) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel principal -->
    <div class="op-main">
        <?php if (!$producto): ?>
        <div class="op-card sin-grupos">
            <i class="ti ti-list-check" style="font-size:48px;color:#ccc;display:block;margin-bottom:12px;"></i>
            <p>Selecciona un producto para gestionar sus grupos de opciones.</p>
        </div>
        <?php else: ?>

        <!-- Botón nuevo grupo -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="margin:0;font-size:16px;">Grupos de <em><?= limpiar($producto['nombre']) ?></em></h3>
            <button class="btn-sm btn-sm-prim" onclick="document.getElementById('formNuevoGrupo').style.display='block';this.style.display='none'">
                <i class="ti ti-plus"></i> Nuevo grupo
            </button>
        </div>

        <!-- Formulario nuevo grupo -->
        <div id="formNuevoGrupo" class="op-card" style="display:none;">
            <h4><i class="ti ti-layout-list"></i> Nuevo grupo de opciones</h4>
            <form method="POST">
                <input type="hidden" name="accion" value="guardar_grupo">
                <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                <div class="op-form-inline">
                    <div class="fld">Nombre del grupo
                        <input type="text" name="nombre" placeholder="Ej: Elige tu parte" required style="width:220px;">
                    </div>
                    <div class="fld">Tipo
                        <select name="tipo">
                            <option value="radio">Radio (elige uno)</option>
                            <option value="checkbox">Checkbox (varios)</option>
                        </select>
                    </div>
                    <div class="fld">Mín.
                        <input type="number" name="min_opciones" value="0" min="0">
                    </div>
                    <div class="fld">Máx.
                        <input type="number" name="max_opciones" value="1" min="1">
                    </div>
                    <div class="fld">Orden
                        <input type="number" name="orden" value="0" min="0">
                    </div>
                    <div class="fld">&nbsp;
                        <label class="op-check"><input type="checkbox" name="requerido"> Obligatorio</label>
                    </div>
                </div>
                <div style="margin-top:14px;display:flex;gap:8px;">
                    <button type="submit" class="btn-sm btn-sm-prim">Guardar grupo</button>
                    <button type="button" class="btn-sm btn-sm-ghost" onclick="document.getElementById('formNuevoGrupo').style.display='none';document.querySelector('.btn-sm-prim').style.display=''">Cancelar</button>
                </div>
            </form>
        </div>

        <?php if (empty($grupos)): ?>
        <div class="op-card sin-grupos">
            <i class="ti ti-circle-off" style="font-size:36px;color:#ddd;display:block;margin-bottom:8px;"></i>
            <p>Este producto no tiene grupos de opciones aún. Crea el primero arriba.</p>
        </div>
        <?php endif; ?>

        <?php foreach ($grupos as $g): ?>
        <div class="op-card">
            <h4>
                <i class="ti ti-<?= $g['tipo'] === 'checkbox' ? 'checkbox' : 'circle-dot' ?>"></i>
                <?= limpiar($g['nombre']) ?>
                <span class="badge-tipo"><?= $g['tipo'] === 'checkbox' ? 'Varios' : 'Uno' ?></span>
                <?php if ($g['requerido']): ?><span class="badge-tipo" style="background:#fff3cd;color:#856404;">Obligatorio</span><?php endif; ?>
                <span style="margin-left:auto;" class="grupo-acciones">
                    <button class="btn-sm btn-sm-warn" onclick="toggleEditGrupo(<?= $g['id'] ?>)"><i class="ti ti-pencil"></i></button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este grupo y todas sus opciones?')">
                        <input type="hidden" name="accion" value="eliminar_grupo">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                        <button type="submit" class="btn-sm btn-sm-danger"><i class="ti ti-trash"></i></button>
                    </form>
                </span>
            </h4>

            <!-- Edit grupo inline -->
            <div id="editGrupo<?= $g['id'] ?>" style="display:none;margin-bottom:14px;padding:12px;background:#f8faf9;border-radius:10px;">
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_grupo">
                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                    <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                    <div class="op-form-inline">
                        <div class="fld">Nombre
                            <input type="text" name="nombre" value="<?= limpiar($g['nombre']) ?>" required style="width:200px;">
                        </div>
                        <div class="fld">Tipo
                            <select name="tipo">
                                <option value="radio" <?= $g['tipo']==='radio'?'selected':'' ?>>Radio</option>
                                <option value="checkbox" <?= $g['tipo']==='checkbox'?'selected':'' ?>>Checkbox</option>
                            </select>
                        </div>
                        <div class="fld">Mín.
                            <input type="number" name="min_opciones" value="<?= $g['min_opciones'] ?>" min="0">
                        </div>
                        <div class="fld">Máx.
                            <input type="number" name="max_opciones" value="<?= $g['max_opciones'] ?>" min="1">
                        </div>
                        <div class="fld">Orden
                            <input type="number" name="orden" value="<?= $g['orden'] ?>" min="0">
                        </div>
                        <div class="fld">&nbsp;
                            <label class="op-check"><input type="checkbox" name="requerido" <?= $g['requerido']?'checked':'' ?>> Obligatorio</label>
                        </div>
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px;">
                        <button type="submit" class="btn-sm btn-sm-prim">Actualizar</button>
                        <button type="button" class="btn-sm btn-sm-ghost" onclick="toggleEditGrupo(<?= $g['id'] ?>)">Cancelar</button>
                    </div>
                </form>
            </div>

            <!-- Opciones del grupo -->
            <table class="op-tabla">
                <thead>
                    <tr><th>Opción</th><th>Precio extra</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($g['opciones'] as $op): ?>
                <tr>
                    <td><?= limpiar($op['nombre']) ?></td>
                    <td><?= $op['precio_extra'] > 0 ? '<span class="precio-extra">+S/ '.number_format($op['precio_extra'],2).'</span>' : '<span class="precio-gratis">Gratis</span>' ?></td>
                    <td><?= $op['disponible'] ? '<span class="badge badge-pagado">Activa</span>' : '<span class="badge badge-cancelado">Oculta</span>' ?></td>
                    <td style="display:flex;gap:6px;">
                        <button class="btn-sm btn-sm-warn" onclick="toggleEditOpcion(<?= $op['id'] ?>)"><i class="ti ti-pencil"></i></button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta opción?')">
                            <input type="hidden" name="accion" value="eliminar_opcion">
                            <input type="hidden" name="id" value="<?= $op['id'] ?>">
                            <input type="hidden" name="grupo_id" value="<?= $g['id'] ?>">
                            <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                            <button type="submit" class="btn-sm btn-sm-danger"><i class="ti ti-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <tr id="editOpcion<?= $op['id'] ?>" style="display:none;background:#f8faf9;">
                    <td colspan="4" style="padding:10px;">
                        <form method="POST">
                            <input type="hidden" name="accion" value="guardar_opcion">
                            <input type="hidden" name="id" value="<?= $op['id'] ?>">
                            <input type="hidden" name="grupo_id" value="<?= $g['id'] ?>">
                            <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                            <div class="op-form-inline">
                                <div class="fld">Nombre<input type="text" name="nombre" value="<?= limpiar($op['nombre']) ?>" required></div>
                                <div class="fld">Precio extra (S/)<input type="number" name="precio_extra" value="<?= $op['precio_extra'] ?>" min="0" step="0.10"></div>
                                <div class="fld">Orden<input type="number" name="orden" value="<?= $op['orden'] ?>" min="0"></div>
                                <div class="fld">&nbsp;<label class="op-check"><input type="checkbox" name="disponible" <?= $op['disponible']?'checked':'' ?>> Visible</label></div>
                            </div>
                            <div style="margin-top:8px;display:flex;gap:8px;">
                                <button type="submit" class="btn-sm btn-sm-prim">Actualizar</button>
                                <button type="button" class="btn-sm btn-sm-ghost" onclick="toggleEditOpcion(<?= $op['id'] ?>)">Cancelar</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($g['opciones'])): ?>
                <tr><td colspan="4" style="color:#aaa;text-align:center;padding:12px;">Sin opciones aún.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <!-- Agregar nueva opción -->
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f0f0f0;">
                <button class="btn-sm btn-sm-ghost" onclick="toggleNuevaOpcion(<?= $g['id'] ?>)"><i class="ti ti-plus"></i> Agregar opción</button>
                <div id="nuevaOpcion<?= $g['id'] ?>" style="display:none;margin-top:10px;">
                    <form method="POST">
                        <input type="hidden" name="accion" value="guardar_opcion">
                        <input type="hidden" name="grupo_id" value="<?= $g['id'] ?>">
                        <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                        <div class="op-form-inline">
                            <div class="fld">Nombre<input type="text" name="nombre" placeholder="Ej: Pechuga" required></div>
                            <div class="fld">Precio extra (S/)<input type="number" name="precio_extra" value="0" min="0" step="0.10"></div>
                            <div class="fld">Orden<input type="number" name="orden" value="0" min="0"></div>
                            <div class="fld">&nbsp;<label class="op-check"><input type="checkbox" name="disponible" checked> Visible</label></div>
                        </div>
                        <div style="margin-top:8px;display:flex;gap:8px;">
                            <button type="submit" class="btn-sm btn-sm-prim">Guardar opción</button>
                            <button type="button" class="btn-sm btn-sm-ghost" onclick="toggleNuevaOpcion(<?= $g['id'] ?>)">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<script>
function toggleEditGrupo(id) {
    const el = document.getElementById('editGrupo' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function toggleEditOpcion(id) {
    const el = document.getElementById('editOpcion' + id);
    el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}
function toggleNuevaOpcion(gid) {
    const el = document.getElementById('nuevaOpcion' + gid);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
