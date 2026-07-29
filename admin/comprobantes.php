<?php
$tituloPagina = 'Comprobantes';
$paginaActual = 'comprobantes';

require __DIR__ . '/_layout_top.php';
require_once __DIR__ . '/../includes/facturacion.php';

$db = getDB();
ensureFacturacionSchema($db);

$mensaje = '';
$error = '';
$whatsappRedirectUrl = '';

// Autogenera PDFs faltantes para comprobantes históricos o registros previos.
$faltantes = $db->query("SELECT id FROM comprobantes_electronicos WHERE (pdf_path IS NULL OR pdf_path = '') ORDER BY id DESC LIMIT 30")->fetchAll();
foreach ($faltantes as $f) {
    facturacionRegenerarPdfComprobante($db, (int)$f['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = trim((string)($_POST['accion'] ?? ''));
    try {
        $comprobanteId = (int)($_POST['comprobante_id'] ?? 0);
        if ($comprobanteId <= 0) {
            throw new RuntimeException('Comprobante inválido.');
        }

        if ($accion === 'reenviar') {
            $db->beginTransaction();
            $resultado = enviarComprobanteSunatNativo($db, $comprobanteId);
            $db->commit();

            if (!empty($resultado['ok'])) {
                $mensaje = 'Comprobante enviado correctamente: ' . ($resultado['mensaje'] ?? 'OK');
            } else {
                $error = 'No se pudo enviar: ' . ($resultado['mensaje'] ?? 'Sin detalle');
            }
        } elseif ($accion === 'generar_pdf') {
            $resultado = facturacionRegenerarPdfComprobante($db, $comprobanteId);
            if (!empty($resultado['ok'])) {
                $mensaje = $resultado['mensaje'] ?? 'PDF generado correctamente.';
            } else {
                $error = $resultado['mensaje'] ?? 'No se pudo generar el PDF.';
            }
        } elseif ($accion === 'enviar_whatsapp_pdf') {
            $st = $db->prepare('SELECT * FROM comprobantes_electronicos WHERE id = :id LIMIT 1');
            $st->execute(['id' => $comprobanteId]);
            $comp = $st->fetch();
            if (!$comp) {
                throw new RuntimeException('No existe el comprobante.');
            }

            if (empty($comp['pdf_path'])) {
                $gen = facturacionRegenerarPdfComprobante($db, $comprobanteId);
                if (empty($gen['ok'])) {
                    throw new RuntimeException($gen['mensaje'] ?? 'No se pudo generar PDF para WhatsApp.');
                }
                $st->execute(['id' => $comprobanteId]);
                $comp = $st->fetch();
            }

            $numeroWs = trim((string)($_POST['numero_ws'] ?? ''));
            $wa = facturacionConstruirLinkWhatsappPdf($comp ?: [], $numeroWs);
            if (!$wa) {
                throw new RuntimeException('Número de WhatsApp inválido o PDF no disponible.');
            }

            $mensaje = 'Link de WhatsApp generado. Se abrirá una pestaña para enviar el mensaje.';
            $whatsappRedirectUrl = $wa;
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'Error en operación: ' . $e->getMessage();
    }
}

$filtroEstado = trim((string)($_GET['estado'] ?? ''));
$filtroTipo = trim((string)($_GET['tipo'] ?? ''));
$verId = (int)($_GET['ver'] ?? 0);

$where = [];
$params = [];

if (in_array($filtroEstado, ['pendiente_configuracion', 'pendiente_envio', 'aceptado', 'observado', 'rechazado', 'error'], true)) {
    $where[] = 'c.estado_sunat = :estado';
    $params['estado'] = $filtroEstado;
}

if (in_array($filtroTipo, ['boleta', 'factura'], true)) {
    $where[] = 'c.tipo_comprobante = :tipo';
    $params['tipo'] = $filtroTipo;
}

$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT c.*, p.codigo AS pedido_codigo, p.cliente_nombre, p.total, p.creado_en
        FROM comprobantes_electronicos c
        INNER JOIN pedidos p ON p.id = c.pedido_id
        $sqlWhere
        ORDER BY c.creado_en DESC";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->execute();
$comprobantes = $stmt->fetchAll();

$detalle = null;
if ($verId > 0) {
    $st = $db->prepare("SELECT c.*, p.codigo AS pedido_codigo, p.cliente_nombre, p.cliente_telefono, p.total, p.creado_en
                        FROM comprobantes_electronicos c
                        INNER JOIN pedidos p ON p.id = c.pedido_id
                        WHERE c.id = :id LIMIT 1");
    $st->execute(['id' => $verId]);
    $detalle = $st->fetch();
}

function descripcionCodigoSunat(?string $codigo): string {
    $codigo = trim((string)$codigo);
    if ($codigo === '0') {
        return 'Aceptado por SUNAT';
    }
    if ($codigo === '') {
        return '-';
    }

    $num = (int)$codigo;
    if ($num >= 2000 && $num <= 3999) {
        return 'Rechazado por SUNAT';
    }
    if ($num >= 4000) {
        return 'Aceptado con observaciones';
    }

    return 'Código de excepción';
}

function claseCodigoSunat(?string $codigo): string {
    $codigo = trim((string)$codigo);
    if ($codigo === '0') return 'codigo-sunat-ok';
    if ($codigo === '') return 'codigo-sunat-none';

    $num = (int)$codigo;
    if ($num >= 2000 && $num <= 3999) return 'codigo-sunat-bad';
    if ($num >= 4000) return 'codigo-sunat-warn';
    return 'codigo-sunat-none';
}

function extraerIdCdr(?string $cdrJson): string {
    $raw = trim((string)$cdrJson);
    if ($raw === '') {
        return '-';
    }

    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        return '-';
    }

    $id = trim((string)($arr['id'] ?? ''));
    return $id !== '' ? $id : '-';
}
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>
<?php if ($whatsappRedirectUrl): ?>
    <script>
        window.open(<?= json_encode($whatsappRedirectUrl) ?>, '_blank');
    </script>
<?php endif; ?>

<div class="card">
    <h3><i class="ti ti-filter"></i> Filtros</h3>
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Estado SUNAT</label>
            <select name="estado">
                <option value="">Todos</option>
                <?php foreach (['pendiente_configuracion', 'pendiente_envio', 'aceptado', 'observado', 'rechazado', 'error'] as $estado): ?>
                    <option value="<?= $estado ?>" <?= $filtroEstado === $estado ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $estado)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Tipo comprobante</label>
            <select name="tipo">
                <option value="">Todos</option>
                <option value="boleta" <?= $filtroTipo === 'boleta' ? 'selected' : '' ?>>Boleta</option>
                <option value="factura" <?= $filtroTipo === 'factura' ? 'selected' : '' ?>>Factura</option>
            </select>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:8px;">
            <button class="btn btn-primario" type="submit"><i class="ti ti-search"></i> Filtrar</button>
            <a class="btn btn-secundario" href="comprobantes.php">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <h3><i class="ti ti-file-invoice"></i> Comprobantes electrónicos</h3>
    <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Doc.</th>
                    <th>Total</th>
                    <th>Estado SUNAT</th>
                    <th>Código</th>
                    <th>Mensaje</th>
                    <th>XML/CDR/PDF</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comprobantes as $c): ?>
                    <tr>
                        <td><?= limpiar(strtoupper($c['tipo_comprobante'])) ?> <?= limpiar($c['numero_comprobante']) ?></td>
                        <td><?= limpiar($c['pedido_codigo']) ?></td>
                        <td><?= limpiar($c['cliente_nombre']) ?></td>
                        <td><?= strtoupper(limpiar($c['tipo_documento'])) ?> <?= limpiar($c['numero_documento']) ?></td>
                        <td><?= formatoPrecio($c['total']) ?></td>
                        <td><span class="badge badge-sunat-<?= limpiar($c['estado_sunat']) ?>"><?= limpiar($c['estado_sunat']) ?></span></td>
                        <td>
                            <span class="codigo-sunat <?= claseCodigoSunat((string)($c['sunat_codigo'] ?? '')) ?>"><?= limpiar((string)($c['sunat_codigo'] ?? '-')) ?></span>
                            <?php if ((string)($c['sunat_codigo'] ?? '') !== ''): ?><br><small style="color:#666;"><?= limpiar(descripcionCodigoSunat((string)$c['sunat_codigo'])) ?></small><?php endif; ?>
                        </td>
                        <td style="max-width:260px;"><?= limpiar((string)($c['sunat_descripcion'] ?? '')) ?></td>
                        <td>
                            <?php if (!empty($c['xml_path'])): ?><a class="btn btn-secundario btn-sm" target="_blank" href="../<?= limpiar($c['xml_path']) ?>">XML</a><?php endif; ?>
                            <?php if (!empty($c['cdr_path'])): ?><a class="btn btn-secundario btn-sm" target="_blank" href="../<?= limpiar($c['cdr_path']) ?>">CDR</a><?php endif; ?>
                            <?php if (!empty($c['pdf_path'])): ?><a class="btn btn-secundario btn-sm" target="_blank" href="../<?= limpiar($c['pdf_path']) ?>">PDF</a><?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($c['creado_en'])) ?></td>
                        <td style="white-space:nowrap;">
                            <a href="?ver=<?= (int)$c['id'] ?>" class="btn btn-secundario btn-sm"><i class="ti ti-eye"></i> Ver</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Reenviar a SUNAT este comprobante?');">
                                <input type="hidden" name="accion" value="reenviar">
                                <input type="hidden" name="comprobante_id" value="<?= (int)$c['id'] ?>">
                                <button class="btn btn-primario btn-sm" type="submit"><i class="ti ti-send"></i> Reenviar</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Generar/actualizar PDF para este comprobante?');">
                                <input type="hidden" name="accion" value="generar_pdf">
                                <input type="hidden" name="comprobante_id" value="<?= (int)$c['id'] ?>">
                                <button class="btn btn-secundario btn-sm" type="submit"><i class="ti ti-file-download"></i> Generar PDF</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($comprobantes)): ?>
                    <tr><td colspan="11" style="text-align:center;color:#888;">No hay comprobantes registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($detalle): ?>
<div class="card">
    <h3><i class="ti ti-file-text"></i> Detalle de comprobante <?= limpiar($detalle['numero_comprobante']) ?></h3>
    <p><b>Pedido:</b> <?= limpiar($detalle['pedido_codigo']) ?> | <b>Cliente:</b> <?= limpiar($detalle['cliente_nombre']) ?> | <b>Teléfono:</b> <?= limpiar($detalle['cliente_telefono']) ?></p>
    <p><b>Estado SUNAT:</b> <span class="badge badge-sunat-<?= limpiar($detalle['estado_sunat']) ?>"><?= limpiar($detalle['estado_sunat']) ?></span></p>
    <p><b>Código SUNAT:</b> <span class="codigo-sunat <?= claseCodigoSunat((string)($detalle['sunat_codigo'] ?? '')) ?>"><?= limpiar((string)($detalle['sunat_codigo'] ?? '-')) ?></span> <?php if ((string)($detalle['sunat_codigo'] ?? '') !== ''): ?><small style="color:#666;">(<?= limpiar(descripcionCodigoSunat((string)$detalle['sunat_codigo'])) ?>)</small><?php endif; ?></p>
    <p><b>Número en CDR SUNAT:</b> <?= limpiar(extraerIdCdr((string)($detalle['cdr_response_json'] ?? ''))) ?></p>
    <p><b>Descripción:</b> <?= limpiar((string)($detalle['sunat_descripcion'] ?? '-')) ?></p>
    <p><b>Hash XML:</b> <?= limpiar((string)($detalle['xml_hash'] ?? '-')) ?></p>
    <p><b>Intentos:</b> <?= (int)$detalle['intentos_envio'] ?></p>

    <p>
        <?php if (!empty($detalle['xml_path'])): ?><a class="btn btn-secundario btn-sm" target="_blank" href="../<?= limpiar($detalle['xml_path']) ?>">Descargar XML</a><?php endif; ?>
        <?php if (!empty($detalle['cdr_path'])): ?><a class="btn btn-secundario btn-sm" target="_blank" href="../<?= limpiar($detalle['cdr_path']) ?>">Descargar CDR</a><?php endif; ?>
        <?php if (!empty($detalle['pdf_path'])): ?><a class="btn btn-secundario btn-sm" target="_blank" href="../<?= limpiar($detalle['pdf_path']) ?>">Ver PDF</a><?php endif; ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="accion" value="generar_pdf">
            <input type="hidden" name="comprobante_id" value="<?= (int)$detalle['id'] ?>">
            <button class="btn btn-secundario btn-sm" type="submit"><i class="ti ti-file-download"></i> Generar PDF</button>
        </form>
    </p>

    <form method="POST" class="form-row" style="margin-top:10px;">
        <input type="hidden" name="accion" value="enviar_whatsapp_pdf">
        <input type="hidden" name="comprobante_id" value="<?= (int)$detalle['id'] ?>">
        <div class="form-group">
            <label>Enviar PDF por WhatsApp</label>
            <input type="text" name="numero_ws" placeholder="Ej. 51987654321" required>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;">
            <button class="btn btn-exito" type="submit"><i class="ti ti-brand-whatsapp"></i> Enviar enlace PDF</button>
        </div>
    </form>

    <div class="form-group" style="margin-top:12px;">
        <label>Payload JSON</label>
        <textarea rows="12" readonly><?= limpiar((string)($detalle['payload_json'] ?? '')) ?></textarea>
    </div>

    <div class="form-group" style="margin-top:12px;">
        <label>Respuesta CDR (JSON)</label>
        <textarea rows="8" readonly><?= limpiar((string)($detalle['cdr_response_json'] ?? '')) ?></textarea>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
