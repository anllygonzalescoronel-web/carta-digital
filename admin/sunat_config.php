<?php
$tituloPagina = 'SUNAT Nativo';
$paginaActual = 'sunat_config';

require __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../includes/facturacion.php';

$db = getDB();
ensureFacturacionSchema($db);

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = trim((string)($_POST['accion'] ?? 'guardar'));

        $campos = [
            'facturacion_driver',
            'sunat_modo',
            'sunat_ruc_emisor',
            'sunat_razon_social',
            'sunat_nombre_comercial',
            'sunat_usuario_sol',
            'sunat_clave_sol',
            'sunat_certificado_clave',
            'sunat_direccion',
            'sunat_ubigeo',
            'sunat_distrito',
            'sunat_provincia',
            'sunat_departamento',
            'sunat_serie_boleta',
            'sunat_serie_factura',
            'sunat_correlativo_boleta',
            'sunat_correlativo_factura',
            'sunat_igv_porcentaje',
            'url_publica',
        ];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                guardarConfig($campo, trim((string)$_POST[$campo]));
            }
        }

        $serieBoleta = strtoupper(trim((string)($_POST['sunat_serie_boleta'] ?? 'B001')));
        $serieFactura = strtoupper(trim((string)($_POST['sunat_serie_factura'] ?? 'F001')));

        if (!preg_match('/^B\d{3}$/', $serieBoleta)) {
            throw new RuntimeException('La serie de boleta debe tener formato B001.');
        }
        if (!preg_match('/^F\d{3}$/', $serieFactura)) {
            throw new RuntimeException('La serie de factura debe tener formato F001.');
        }

        $corBoleta = max((int)($_POST['sunat_correlativo_boleta'] ?? 1), 1);
        $corFactura = max((int)($_POST['sunat_correlativo_factura'] ?? 1), 1);
        guardarConfig('sunat_serie_boleta', $serieBoleta);
        guardarConfig('sunat_serie_factura', $serieFactura);
        guardarConfig('sunat_correlativo_boleta', (string)$corBoleta);
        guardarConfig('sunat_correlativo_factura', (string)$corFactura);

        $carpetaCert = __DIR__ . '/../uploads/sunat/certificados';
        if (!is_dir($carpetaCert)) {
            mkdir($carpetaCert, 0775, true);
        }

        $cert = subirArchivoSeguro('sunat_certificado', $carpetaCert, ['pfx', 'pem', 'cer', 'crt'], 8 * 1024 * 1024);
        if ($cert) {
            guardarConfig('sunat_certificado_path', 'uploads/sunat/certificados/' . $cert);
        }

        if ($accion === 'probar_conexion') {
            $resultado = facturacionProbarConexionSunatNativo();
            if (!empty($resultado['ok'])) {
                $mensaje = $resultado['mensaje'] ?? 'Conexión SUNAT verificada correctamente.';
            } else {
                $error = $resultado['mensaje'] ?? 'No se pudo validar conexión con SUNAT.';
            }
        } else {
            $mensaje = 'Configuración SUNAT guardada correctamente.';
        }
    } catch (Throwable $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

$config = [];
foreach ($db->query('SELECT clave, valor FROM configuracion') as $row) {
    $config[$row['clave']] = $row['valor'];
}

function cs(string $k, string $d = ''): string {
    global $config;
    return (string)($config[$k] ?? $d);
}
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="grid-dos-cards">
        <div class="card">
            <h3><i class="ti ti-building-factory-2"></i> Datos de emisor</h3>
            <div class="form-group">
                <label>Motor de facturación</label>
                <select name="facturacion_driver">
                    <option value="native" <?= cs('facturacion_driver', 'native') === 'native' ? 'selected' : '' ?>>SUNAT Nativo (Greenter)</option>
                    <option value="nubefact" <?= cs('facturacion_driver') === 'nubefact' ? 'selected' : '' ?>>Nubefact (próximamente)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Modo de operación</label>
                <select name="sunat_modo">
                    <option value="demo" <?= cs('sunat_modo', 'demo') === 'demo' ? 'selected' : '' ?>>Demo (simulación)</option>
                    <option value="beta" <?= cs('sunat_modo') === 'beta' ? 'selected' : '' ?>>Beta (SUNAT pruebas)</option>
                    <option value="produccion" <?= cs('sunat_modo') === 'produccion' ? 'selected' : '' ?>>Producción (SUNAT real)</option>
                </select>
            </div>
            <div class="form-group">
                <label>RUC emisor</label>
                <input type="text" name="sunat_ruc_emisor" maxlength="11" value="<?= limpiar(cs('sunat_ruc_emisor')) ?>" placeholder="20123456789" required>
            </div>
            <div class="form-group">
                <label>Razón social</label>
                <input type="text" name="sunat_razon_social" value="<?= limpiar(cs('sunat_razon_social', cs('nombre_negocio'))) ?>" required>
            </div>
            <div class="form-group">
                <label>Nombre comercial</label>
                <input type="text" name="sunat_nombre_comercial" value="<?= limpiar(cs('sunat_nombre_comercial', cs('nombre_negocio'))) ?>">
            </div>
            <div class="form-group">
                <label>Dirección fiscal</label>
                <input type="text" name="sunat_direccion" value="<?= limpiar(cs('sunat_direccion', cs('direccion_local'))) ?>">
            </div>
            <div class="form-group">
                <label>IGV (%) para operaciones gravadas</label>
                <input type="number" step="0.01" min="0" name="sunat_igv_porcentaje" value="<?= limpiar(cs('sunat_igv_porcentaje', '18')) ?>">
                <small style="color:#666;display:block;margin-top:6px;">Referencia general SUNAT: IGV vigente 18% (incluye IPM) para ventas gravadas.</small>
            </div>
            <div class="form-group">
                <label>URL pública del sistema (para links PDF por WhatsApp)</label>
                <input type="text" name="url_publica" value="<?= limpiar(cs('url_publica')) ?>" placeholder="https://tudominio.com/carta-digital">
                <small style="color:#666;display:block;margin-top:6px;">Usa una URL accesible desde internet para que tus clientes abran el PDF.</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Ubigeo</label>
                    <input type="text" name="sunat_ubigeo" maxlength="6" value="<?= limpiar(cs('sunat_ubigeo', '150101')) ?>">
                </div>
                <div class="form-group">
                    <label>Distrito</label>
                    <input type="text" name="sunat_distrito" value="<?= limpiar(cs('sunat_distrito', 'LIMA')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Provincia</label>
                    <input type="text" name="sunat_provincia" value="<?= limpiar(cs('sunat_provincia', 'LIMA')) ?>">
                </div>
                <div class="form-group">
                    <label>Departamento</label>
                    <input type="text" name="sunat_departamento" value="<?= limpiar(cs('sunat_departamento', 'LIMA')) ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="ti ti-key"></i> Credenciales y certificado</h3>
            <div class="form-group">
                <label>Usuario SOL</label>
                <input type="text" name="sunat_usuario_sol" value="<?= limpiar(cs('sunat_usuario_sol')) ?>" placeholder="MODDATOS o usuario SOL">
            </div>
            <div class="form-group">
                <label>Clave SOL</label>
                <input type="password" name="sunat_clave_sol" value="<?= limpiar(cs('sunat_clave_sol')) ?>">
            </div>
            <div class="form-group">
                <label>Certificado digital (.pfx/.pem/.cer/.crt)</label>
                <input type="file" name="sunat_certificado" accept=".pfx,.pem,.cer,.crt">
                <?php if (cs('sunat_certificado_path')): ?>
                    <small style="color:#666;display:block;margin-top:6px;">Actual: <?= limpiar(cs('sunat_certificado_path')) ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Clave del certificado</label>
                <input type="password" name="sunat_certificado_clave" value="<?= limpiar(cs('sunat_certificado_clave')) ?>">
            </div>

            <h3 style="margin-top:18px;"><i class="ti ti-numbers"></i> Series y correlativos</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Serie boleta</label>
                    <input type="text" name="sunat_serie_boleta" value="<?= limpiar(cs('sunat_serie_boleta', 'B001')) ?>" maxlength="4" required>
                </div>
                <div class="form-group">
                    <label>Correlativo boleta</label>
                    <input type="number" min="1" name="sunat_correlativo_boleta" value="<?= (int)cs('sunat_correlativo_boleta', '1') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Serie factura</label>
                    <input type="text" name="sunat_serie_factura" value="<?= limpiar(cs('sunat_serie_factura', 'F001')) ?>" maxlength="4" required>
                </div>
                <div class="form-group">
                    <label>Correlativo factura</label>
                    <input type="number" min="1" name="sunat_correlativo_factura" value="<?= (int)cs('sunat_correlativo_factura', '1') ?>" required>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn-principal" type="submit" name="accion" value="guardar" style="width:auto;min-width:260px;">Guardar configuración SUNAT</button>
        <button class="btn btn-secundario" type="submit" name="accion" value="probar_conexion" style="padding:12px 18px;font-size:14px;font-weight:700;"><i class="ti ti-plug-connected"></i> Probar conexión SUNAT</button>
    </div>
</form>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
