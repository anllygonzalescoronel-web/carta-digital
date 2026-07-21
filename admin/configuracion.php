<?php
$tituloPagina = 'Configuración';
$paginaActual = 'configuracion';
require __DIR__ . '/_layout_top.php';

$mensaje = ''; $error = '';
$carpetaUploads = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $campos = [
            'nombre_negocio', 'direccion_local', 'whatsapp_numero', 'costo_delivery',
            'color_primario', 'color_secundario', 'color_texto', 'color_fondo',
            'yape_plin_numero', 'culqi_public_key', 'culqi_secret_key', 'mensaje_bienvenida',
        ];
        foreach ($campos as $c) {
            if (isset($_POST[$c])) guardarConfig($c, trim($_POST[$c]));
        }

        $checkboxes = ['delivery_activo', 'recojo_activo', 'efectivo_activo', 'yape_plin_activo', 'tarjeta_activo'];
        foreach ($checkboxes as $c) {
            guardarConfig($c, isset($_POST[$c]) ? '1' : '0');
        }

        $logo = subirImagen('logo', $carpetaUploads);
        if ($logo) guardarConfig('logo', $logo);

        $qr = subirImagen('yape_plin_qr', $carpetaUploads);
        if ($qr) guardarConfig('yape_plin_qr', $qr);

        $mensaje = 'Configuración guardada correctamente.';
        // Forzar recarga de config en esta petición
        $stmt = getDB()->query('SELECT clave, valor FROM configuracion');
        $GLOBALS['config_override'] = [];
    } catch (Throwable $e) {
        $error = 'Error al guardar: ' . $e->getMessage();
    }
}

// Releer configuración fresca desde BD (por si se acaba de guardar)
$configFresca = [];
foreach (getDB()->query('SELECT clave, valor FROM configuracion') as $row) {
    $configFresca[$row['clave']] = $row['valor'];
}
function c($k, $d = '') { global $configFresca; return $configFresca[$k] ?? $d; }
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <div class="card">
        <h3>🏪 Datos del negocio</h3>
        <div class="form-group">
            <label>Nombre del negocio</label>
            <input type="text" name="nombre_negocio" value="<?= limpiar(c('nombre_negocio')) ?>" required>
        </div>
        <div class="form-group">
            <label>Dirección</label>
            <input type="text" name="direccion_local" value="<?= limpiar(c('direccion_local')) ?>">
        </div>
        <div class="form-group">
            <label>Logo (opcional)</label>
            <input type="file" name="logo" accept="image/*">
            <?php if (c('logo')): ?><img src="../uploads/<?= limpiar(c('logo')) ?>" class="thumb" style="margin-top:8px;"><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Mensaje de bienvenida (uso futuro)</label>
            <input type="text" name="mensaje_bienvenida" value="<?= limpiar(c('mensaje_bienvenida')) ?>">
        </div>
    </div>

    <div class="card">
        <h3>🎨 Colores de la carta</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Color primario</label>
                <input type="color" name="color_primario" value="<?= limpiar(c('color_primario')) ?>">
            </div>
            <div class="form-group">
                <label>Color secundario</label>
                <input type="color" name="color_secundario" value="<?= limpiar(c('color_secundario')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Color de texto</label>
                <input type="color" name="color_texto" value="<?= limpiar(c('color_texto')) ?>">
            </div>
            <div class="form-group">
                <label>Color de fondo</label>
                <input type="color" name="color_fondo" value="<?= limpiar(c('color_fondo')) ?>">
            </div>
        </div>
    </div>

    <div class="card">
        <h3>📲 WhatsApp para recibir pedidos</h3>
        <div class="form-group">
            <label>Número de WhatsApp (con código de país, sin +)</label>
            <input type="text" name="whatsapp_numero" value="<?= limpiar(c('whatsapp_numero')) ?>" placeholder="51987654321" required>
        </div>
    </div>

    <div class="card">
        <h3>🛵 Entrega</h3>
        <div class="form-check">
            <input type="checkbox" name="recojo_activo" id="recojo_activo" <?= c('recojo_activo')==='1'?'checked':'' ?>>
            <label for="recojo_activo" style="margin:0;">Permitir recojo en local</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="delivery_activo" id="delivery_activo" <?= c('delivery_activo')==='1'?'checked':'' ?>>
            <label for="delivery_activo" style="margin:0;">Permitir delivery</label>
        </div>
        <div class="form-group">
            <label>Costo de delivery (S/)</label>
            <input type="number" step="0.01" name="costo_delivery" value="<?= limpiar(c('costo_delivery')) ?>">
        </div>
    </div>

    <div class="card">
        <h3>💰 Métodos de pago</h3>

        <div class="form-check">
            <input type="checkbox" name="efectivo_activo" id="efectivo_activo" <?= c('efectivo_activo')==='1'?'checked':'' ?>>
            <label for="efectivo_activo" style="margin:0;">Efectivo al recibir</label>
        </div>

        <div class="form-check">
            <input type="checkbox" name="yape_plin_activo" id="yape_plin_activo" <?= c('yape_plin_activo')==='1'?'checked':'' ?>>
            <label for="yape_plin_activo" style="margin:0;">Yape / Plin</label>
        </div>
        <div class="form-group">
            <label>Número Yape/Plin</label>
            <input type="text" name="yape_plin_numero" value="<?= limpiar(c('yape_plin_numero')) ?>">
        </div>
        <div class="form-group">
            <label>Imagen QR de Yape/Plin</label>
            <input type="file" name="yape_plin_qr" accept="image/*">
            <?php if (c('yape_plin_qr')): ?><img src="../uploads/<?= limpiar(c('yape_plin_qr')) ?>" class="thumb" style="margin-top:8px;width:70px;height:70px;"><?php endif; ?>
        </div>

        <div class="form-check">
            <input type="checkbox" name="tarjeta_activo" id="tarjeta_activo" <?= c('tarjeta_activo')==='1'?'checked':'' ?>>
            <label for="tarjeta_activo" style="margin:0;">Tarjeta (Culqi)</label>
        </div>
        <div class="form-group">
            <label>Llave pública de Culqi (pk_...)</label>
            <input type="text" name="culqi_public_key" value="<?= limpiar(c('culqi_public_key')) ?>">
        </div>
        <div class="form-group">
            <label>Llave secreta de Culqi (sk_...)</label>
            <input type="text" name="culqi_secret_key" value="<?= limpiar(c('culqi_secret_key')) ?>">
            <small style="color:#888;">La encuentras en tu panel de Culqi &rarr; Llaves API. Usa las de <b>prueba</b> (pk_test/sk_test) hasta que actives tu cuenta.</small>
        </div>
    </div>

    <button class="btn-principal" type="submit">Guardar configuración</button>
</form>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
