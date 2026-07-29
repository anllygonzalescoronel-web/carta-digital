<?php
$tituloPagina = 'Config. NubeFacT';
$paginaActual = 'nubefact_config';

require __DIR__ . '/_layout_top.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ruta  = trim((string)($_POST['nubefact_ruta'] ?? ''));
        $token = trim((string)($_POST['nubefact_token'] ?? ''));
        $serie = strtoupper(trim((string)($_POST['nubefact_serie_boleta'] ?? 'BBB1')));
        $serieFactura = strtoupper(trim((string)($_POST['nubefact_serie_factura'] ?? '')));

        if ($ruta === '' || $token === '') {
            throw new RuntimeException('La RUTA y el TOKEN son obligatorios.');
        }
        if (!preg_match('/^[A-Z0-9]{1,4}$/', $serie)) {
            throw new RuntimeException('La serie de boleta debe tener entre 1 y 4 caracteres (letras/números), ej. BBB1.');
        }
        if ($serieFactura !== '' && !preg_match('/^[A-Z0-9]{1,4}$/', $serieFactura)) {
            throw new RuntimeException('La serie de factura debe tener entre 1 y 4 caracteres (letras/números), ej. FFF1.');
        }

        guardarConfig('nubefact_ruta', $ruta);
        guardarConfig('nubefact_token', $token);
        guardarConfig('nubefact_serie_boleta', $serie);
        if ($serieFactura !== '') {
            guardarConfig('nubefact_serie_factura', $serieFactura);
        }

        $mensaje = 'Configuración de NubeFacT guardada correctamente.';
    } catch (Throwable $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

$rutaActual  = cfg('nubefact_ruta', '');
$tokenActual = cfg('nubefact_token', '');
$serieActual = cfg('nubefact_serie_boleta', 'BBB1');
$serieFacturaActual = cfg('nubefact_serie_factura', '');
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<form method="POST">
    <div class="card">
        <h3><i class="ti ti-cloud"></i> Credenciales de NubeFacT</h3>
        <p style="color:#666;margin-top:-6px;">
            Consíguelas en tu panel de NubeFacT, sección <b>API - Integración</b> (botones "Copiar" de RUTA y TOKEN).
        </p>

        <div class="form-group">
            <label>RUTA</label>
            <input type="text" name="nubefact_ruta" value="<?= limpiar($rutaActual) ?>" placeholder="https://api.nubefact.com/api/v1/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
        </div>

        <div class="form-group">
            <label>TOKEN</label>
            <input type="text" name="nubefact_token" value="<?= limpiar($tokenActual) ?>" placeholder="token largo que te da NubeFacT" required>
            <small style="color:#666;display:block;margin-top:6px;">
                Se guarda en la tabla de configuración, visible para quien tenga acceso a este panel admin.
            </small>
        </div>

        <div class="form-group">
            <label>Serie de boleta</label>
            <input type="text" name="nubefact_serie_boleta" value="<?= limpiar($serieActual) ?>" maxlength="4" placeholder="BBB1" required>
            <small style="color:#666;display:block;margin-top:6px;">
                Debe coincidir con una serie ya configurada en tu cuenta de NubeFacT.
            </small>
        </div>

        <div class="form-group">
            <label>Serie de factura (opcional, solo si vas a emitir facturas con RUC)</label>
            <input type="text" name="nubefact_serie_factura" value="<?= limpiar($serieFacturaActual) ?>" maxlength="4" placeholder="FFF1">
            <small style="color:#666;display:block;margin-top:6px;">
                Déjalo vacío si por ahora solo emites boletas. Debe existir también en tu cuenta de NubeFacT.
            </small>
        </div>

        <?php if ($rutaActual !== '' && $tokenActual !== ''): ?>
            <div class="alerta-ok" style="margin-top:10px;">
                <i class="ti ti-check"></i> Ya tienes credenciales guardadas para esta serie (<?= limpiar($serieActual) ?>).
            </div>
        <?php else: ?>
            <div class="alerta-error" style="margin-top:10px;">
                <i class="ti ti-alert-triangle"></i> Aún no configuraste RUTA/TOKEN de NubeFacT.
            </div>
        <?php endif; ?>
    </div>

    <button class="btn-principal" type="submit" style="width:auto;min-width:260px;">Guardar configuración de NubeFacT</button>
</form>

<?php require __DIR__ . '/_layout_bottom.php'; ?>