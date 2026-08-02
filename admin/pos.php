<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$db = getDB();
$turnoAbierto = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
if (!$turnoAbierto) {
    header('Location: cajas.php');
    exit;
}

$tituloPagina = 'POS Restaurante';
$paginaActual = 'pos';
require __DIR__ . '/_layout_top.php';
?>

<style>
:root {
    --pos-bg: #f8fafc;
    --pos-border: #dbe3ef;
    --pos-primary: #0f172a;
    --pos-muted: #64748b;
    --pos-ok: #166534;
    --pos-ok-bg: #dcfce7;
    --pos-busy: #92400e;
    --pos-busy-bg: #fef3c7;
}

.pos-shell {
    display: grid;
    gap: 12px;
}

.pos-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    background: #fff;
    border: 1px solid var(--pos-border);
    border-radius: 14px;
    padding: 10px 12px;
}

.pos-top h2 {
    margin: 0;
    color: var(--pos-primary);
    font-size: 18px;
}

.pos-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 6px 10px;
    background: #e2e8f0;
    color: #1e293b;
    font-size: 12px;
    font-weight: 700;
}

.pos-grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 12px;
}

@media (max-width: 1180px) {
    .pos-grid {
        grid-template-columns: 1fr;
    }
}

.pos-card {
    background: #fff;
    border: 1px solid var(--pos-border);
    border-radius: 14px;
    padding: 12px;
}

.pos-card h3 {
    margin: 0 0 10px;
    color: var(--pos-primary);
    font-size: 16px;
}

.pos-left-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.pos-left-head h3 {
    margin: 0;
}

.pos-mesa-activa {
    font-size: 12px;
    color: #334155;
    font-weight: 700;
}

.pos-categorias {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px 0;
}

.pos-categoria-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.pos-categoria-btn.activa {
    background: var(--pos-primary);
    border-color: var(--pos-primary);
    color: #fff;
}

.pos-categoria-btn img {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    object-fit: cover;
    margin-right: 6px;
    vertical-align: middle;
    border: 1px solid rgba(15, 23, 42, 0.12);
}

.pos-cat-fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    margin-right: 6px;
    background: #dbe3ef;
    color: #334155;
    font-size: 10px;
    font-weight: 800;
}

.pos-zonas {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.pos-zona-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    border-radius: 10px;
    padding: 7px 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.pos-zona-btn.activa {
    background: var(--pos-primary);
    border-color: var(--pos-primary);
    color: #fff;
}

.pos-board-wrap {
    overflow: auto;
    border: 1px solid var(--pos-border);
    border-radius: 12px;
    background:
      radial-gradient(circle at 1px 1px, rgba(148, 163, 184, 0.35) 1px, transparent 0) 0 0 / 28px 28px,
      linear-gradient(135deg, #f8fbff 0%, #f1f5f9 100%);
}

.pos-board {
    position: relative;
    min-width: 900px;
    min-height: 540px;
}

.pos-mesa {
    position: absolute;
    width: 130px;
    min-height: 76px;
    border-radius: 12px;
    border: 2px solid #cbd5e1;
    background: #fff;
    padding: 8px;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 8px 14px rgba(15, 23, 42, 0.08);
}

.pos-mesa.redonda {
    border-radius: 999px;
}

.pos-mesa.libre {
    border-color: #86efac;
    background: #f0fdf4;
}

.pos-mesa.ocupada {
    border-color: #fcd34d;
    background: #fffbeb;
}

.pos-mesa.seleccionada {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.18), 0 8px 14px rgba(15, 23, 42, 0.08);
}

.pos-mesa .name {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
}

.pos-mesa .meta {
    display: block;
    font-size: 11px;
    color: #475569;
    margin-top: 2px;
}

.pos-order-disabled {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    color: #64748b;
    text-align: center;
    padding: 18px;
    font-size: 13px;
    font-weight: 700;
}

.pos-resumen {
    border: 1px solid var(--pos-border);
    border-radius: 12px;
    background: #f8fafc;
    padding: 10px;
    margin-bottom: 10px;
}

.pos-resumen strong {
    color: #0f172a;
}

.pos-resumen-list {
    max-height: 140px;
    overflow: auto;
    margin-top: 8px;
    border-top: 1px dashed #cbd5e1;
    padding-top: 8px;
}

.pos-resumen-item {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #334155;
    margin-bottom: 4px;
}

.pos-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 10px;
}

@media (max-width: 680px) {
    .pos-fields {
        grid-template-columns: 1fr;
    }
}

.pos-field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 4px;
    color: #334155;
}

.pos-field input,
.pos-field select,
.pos-field textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 8px;
}

.pos-products {
    border: 1px solid var(--pos-border);
    border-radius: 12px;
    max-height: 430px;
    overflow: auto;
    padding: 8px;
    margin-bottom: 10px;
}

.pos-prod-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

@media (max-width: 1200px) {
    .pos-prod-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .pos-prod-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .pos-prod-grid {
        grid-template-columns: 1fr;
    }
}

.pos-prod {
    border: 1px solid #dbe3ef;
    border-radius: 10px;
    padding: 8px;
    position: relative;
}

.pos-prod-badge {
    position: absolute;
    top: 14px;
    right: 14px;
    background: rgba(15, 23, 42, 0.86);
    color: #fff;
    border-radius: 999px;
    padding: 5px 8px;
    font-size: 10px;
    font-weight: 800;
}

.pos-prod-img {
    width: 100%;
    height: 120px;
    border-radius: 10px;
    object-fit: contain;
    object-position: center;
    border: 1px solid #e2e8f0;
    margin-bottom: 8px;
    background: #ffffff;
    padding: 4px;
}

.pos-prod-fallback {
    width: 100%;
    height: 120px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.pos-prod strong {
    display: block;
    color: #0f172a;
    font-size: 13px;
}

.pos-prod small {
    display: block;
    color: #64748b;
    margin: 2px 0 6px;
    font-size: 11px;
}

.pos-prod-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
}

.pos-stepper {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #dbe3ef;
    border-radius: 999px;
    padding: 3px;
}

.pos-stepper-btn {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
    cursor: pointer;
}

.pos-stepper-val {
    min-width: 18px;
    text-align: center;
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
}

.pos-btn {
    border: none;
    border-radius: 10px;
    padding: 8px 10px;
    font-weight: 700;
    cursor: pointer;
}

.pos-btn.dark { background: #0f172a; color: #fff; }
.pos-btn.soft { background: #e2e8f0; color: #0f172a; }
.pos-btn.warn { background: #fef3c7; color: #92400e; }

.pos-cart {
    border: 1px solid var(--pos-border);
    border-radius: 12px;
    max-height: 180px;
    overflow: auto;
    margin-bottom: 8px;
    background: #fff;
}

.pos-cart.rebote {
    animation: posCartBounce .55s ease;
}

@keyframes posCartBounce {
    0% { transform: scale(1); }
    30% { transform: scale(1.03); }
    55% { transform: scale(0.985); }
    100% { transform: scale(1); }
}

.pos-cart table {
    width: 100%;
    border-collapse: collapse;
}

.pos-cart th,
.pos-cart td {
    border-bottom: 1px solid #e2e8f0;
    padding: 7px;
    font-size: 12px;
    text-align: left;
}

.pos-cart-main {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.pos-cart-thumb {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    object-fit: cover;
    flex: 0 0 auto;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.pos-cart-thumb-fallback {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 800;
}

.pos-cart-info {
    flex: 1;
    min-width: 0;
}

.pos-cart-name {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 3px;
}

.pos-cart-unit {
    font-size: 11px;
    color: #64748b;
}

.pos-cart-options {
    list-style: none;
    margin: 6px 0 0;
    padding: 0 0 0 10px;
}

.pos-cart-options li {
    font-size: 11px;
    color: #475569;
    line-height: 1.45;
}

.pos-fly {
    position: fixed;
    z-index: 9999;
    pointer-events: none;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
    transition: left .7s cubic-bezier(.4,-0.2,.6,1), top .7s cubic-bezier(.4,-0.2,.6,1), width .7s ease, height .7s ease, opacity .7s ease, transform .7s ease;
}

.pos-total {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 10px;
}

.pos-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.pos-actions.stack {
    flex-direction: column;
}

.pos-btn.full {
    width: 100%;
    justify-content: center;
}

.pos-msg {
    min-height: 20px;
    margin-top: 8px;
    font-size: 13px;
    font-weight: 700;
}

.pos-msg.ok { color: #166534; }
.pos-msg.err { color: #b91c1c; }

.pos-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 14px;
}

.pos-modal.show { display: flex; }

.pos-modal-box {
    width: 100%;
    max-width: 560px;
    background: #fff;
    border-radius: 14px;
    border: 1px solid var(--pos-border);
    padding: 12px;
    max-height: 88vh;
    overflow: auto;
}

.pos-modal-box h4 { margin: 0 0 8px; color: #0f172a; }

.pos-modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

@media (max-width: 680px) {
    .pos-modal-grid {
        grid-template-columns: 1fr;
    }
}

.pos-opciones-producto {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
}

.pos-opciones-producto img {
    width: 72px;
    height: 72px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.pos-opciones-nombre {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.pos-opciones-precio {
    font-size: 13px;
    font-weight: 700;
    color: #166534;
    margin-top: 4px;
}

.pos-opciones-grupo {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 10px;
    margin-bottom: 10px;
}

.pos-opciones-titulo {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.pos-opciones-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 4px 8px;
    margin-left: 6px;
    background: #eef2ff;
    color: #334155;
    font-size: 10px;
    font-weight: 800;
}

.pos-opciones-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 10px;
    margin-bottom: 6px;
    cursor: pointer;
}

.pos-opciones-label.seleccionada {
    border-color: #0f172a;
    background: #f8fafc;
}

.pos-opciones-label input {
    margin-right: 8px;
}

.pos-opciones-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #e2e8f0;
    margin-top: 12px;
    padding-top: 10px;
    font-weight: 800;
    color: #0f172a;
}
</style>

<div class="pos-shell">
    <div class="pos-top">
        <h2><i class="ti ti-device-desktop"></i> POS Restaurante</h2>
        <div>
            <span class="pos-chip" id="chipCajaTurno">Caja activa</span>
            <span class="pos-chip" id="chipMesaActual">Mesa: sin seleccionar</span>
        </div>
    </div>

    <div class="pos-grid">
        <section class="pos-card">
            <div id="mesaSelectorView">
                <h3><i class="ti ti-layout-grid"></i> Mapa de mesas</h3>
                <div class="pos-zonas" id="posZonas"></div>
                <div class="pos-board-wrap">
                    <div class="pos-board" id="posBoard"></div>
                </div>
            </div>

            <div id="platosView" style="display:none;">
                <div class="pos-left-head">
                    <h3><i class="ti ti-tools-kitchen-2"></i> Platos de la mesa</h3>
                    <button type="button" class="pos-btn soft" id="btnVolverMesas">Volver a mesas</button>
                </div>
                <div class="pos-mesa-activa" id="platosMesaLabel">Mesa: -</div>

                <div class="pos-categorias" id="posCategorias"></div>

                <div class="pos-field" style="margin:10px 0 8px;">
                    <label>Buscar producto</label>
                    <input type="text" id="filtroProducto" placeholder="Nombre o descripción">
                </div>
                <div class="pos-products" id="catalogoPos"></div>
            </div>
        </section>

        <section class="pos-card">
            <h3><i class="ti ti-receipt"></i> Orden y facturación</h3>

            <div id="orderDisabled" class="pos-order-disabled">
                Selecciona una mesa en el mapa para iniciar la orden, enviar comanda y luego facturar.
            </div>

            <div id="orderPanel" style="display:none;">
                <div class="pos-resumen" id="posResumenMesa"></div>

                <div class="pos-actions" style="margin-bottom:10px;">
                    <button type="button" class="pos-btn warn" id="btnActualizarPrecuenta">Actualizar precuenta</button>
                    <button type="button" class="pos-btn soft" id="btnImprimirPrecuenta">Imprimir precuenta</button>
                </div>

                <div class="pos-cart">
                    <table>
                        <thead><tr><th>Producto</th><th>Cant.</th><th>Sub</th><th></th></tr></thead>
                        <tbody id="carritoBody"></tbody>
                    </table>
                </div>

                <div class="pos-total" id="posTotal">Total: S/ 0.00</div>

                <div class="pos-actions stack">
                    <button type="button" class="pos-btn dark full" id="btnEnviarComanda">Enviar comanda a cocina</button>
                    <button type="button" class="pos-btn warn full" id="btnAbrirFacturacion">Facturar mesa</button>
                    <button type="button" class="pos-btn soft full" id="btnLimpiarCarrito">Limpiar carrito local</button>
                </div>

                <div id="posMsg" class="pos-msg"></div>
            </div>
        </section>
    </div>
</div>

<div class="pos-modal" id="modalPrecuenta">
    <div class="pos-modal-box">
        <h4><i class="ti ti-file-invoice"></i> Precuenta de mesa</h4>
        <div id="precuentaContenido"></div>
        <div class="pos-actions" style="margin-top:10px;">
            <button type="button" class="pos-btn soft" id="btnCerrarPrecuenta">Cerrar</button>
        </div>
    </div>
</div>

<div class="pos-modal" id="modalOpcionesPos">
    <div class="pos-modal-box">
        <h4><i class="ti ti-adjustments-horizontal"></i> Personalizar producto</h4>
        <div id="posOpcionesProductoInfo" class="pos-opciones-producto"></div>
        <div id="posOpcionesGrupos"></div>
        <div class="pos-opciones-total">
            <span>Total producto</span>
            <strong id="posOpcionesTotalTexto">S/ 0.00</strong>
        </div>
        <div class="pos-actions" style="margin-top:12px;">
            <button type="button" class="pos-btn dark" id="btnConfirmarOpcionesPos">Agregar al carrito</button>
            <button type="button" class="pos-btn soft" id="btnCerrarOpcionesPos">Cancelar</button>
        </div>
    </div>
</div>

<div class="pos-modal" id="modalFacturacionPos">
    <div class="pos-modal-box">
        <h4><i class="ti ti-file-invoice"></i> Facturar mesa</h4>
        <div class="pos-modal-grid">
            <div class="pos-field"><label>Tipo de emisión</label><select id="posFactTipoEmision"><option value="ticket">Ticket de venta</option><option value="boleta">Boleta</option><option value="factura">Factura</option></select></div>
            <div class="pos-field"><label>Método de pago</label><select id="posFactPago"><option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option><option value="yape_plin">Yape/Plin</option></select></div>
            <div class="pos-field"><label>Cliente</label><input type="text" id="posFactNombre" value="Cliente Mesa"></div>
            <div class="pos-field"><label>Teléfono</label><input type="text" id="posFactTelefono" value="999999999"></div>
            <div class="pos-field"><label>Tipo doc</label><select id="posFactTipoDoc"><option value="dni">DNI</option><option value="ruc">RUC</option></select></div>
            <div class="pos-field"><label>Número doc</label><input type="text" id="posFactNumDoc" value="00000000"></div>
            <div class="pos-field" style="grid-column:1/-1;"><button type="button" class="pos-btn soft" id="btnBuscarDocumentoPos">Buscar cliente por DNI/RUC</button></div>
            <div class="pos-field" style="grid-column:1/-1;"><label>Notas</label><textarea id="posFactNotas" rows="2" placeholder="Observaciones del cobro"></textarea></div>
        </div>
        <div id="posFactInfo" style="font-size:12px;color:#64748b;margin-top:8px;">Si SUNAT no está disponible, el sistema emitirá ticket de venta local y permitirá imprimirlo.</div>
        <div class="pos-actions" style="margin-top:12px;">
            <button type="button" class="pos-btn dark" id="btnConfirmarFacturacion">Completar y facturar</button>
            <button type="button" class="pos-btn soft" id="btnCerrarFacturacion">Cancelar</button>
        </div>
    </div>
</div>

<script>
(() => {
    const API_MESAS = '../api/pos_mesas_estado.php';
    const API_PRECUENTA = '../api/pos_precuenta.php';
    const API_CATALOGO = '../api/pos_catalogo.php';
    const API_COMANDA = '../api/pos_comanda.php';
    const API_FACTURAR = '../api/pos_facturar_mesa.php';
    const API_CONSULTAR_DOCUMENTO = '../api/consultar_documento.php';

    let zonas = [];
    let zonaActivaId = 0;
    let mesaSeleccionada = null;
    let catalogo = [];
    let categoriaActiva = 'all';
    let carrito = [];
    let timerRefresh = null;
    let productoOpcionesActual = null;
    let lastPrecuenta = null;

    const ui = {
        zonas: document.getElementById('posZonas'),
        board: document.getElementById('posBoard'),
        mesaSelectorView: document.getElementById('mesaSelectorView'),
        platosView: document.getElementById('platosView'),
        platosMesaLabel: document.getElementById('platosMesaLabel'),
        categorias: document.getElementById('posCategorias'),
        chipCaja: document.getElementById('chipCajaTurno'),
        chipMesa: document.getElementById('chipMesaActual'),
        orderDisabled: document.getElementById('orderDisabled'),
        orderPanel: document.getElementById('orderPanel'),
        resumenMesa: document.getElementById('posResumenMesa'),
        filtro: document.getElementById('filtroProducto'),
        catalogo: document.getElementById('catalogoPos'),
        cartBody: document.getElementById('carritoBody'),
        total: document.getElementById('posTotal'),
        msg: document.getElementById('posMsg'),
        modal: document.getElementById('modalPrecuenta'),
        precuentaCont: document.getElementById('precuentaContenido'),
        cart: document.querySelector('.pos-cart'),
        modalOpciones: document.getElementById('modalOpcionesPos'),
        opcionesProductoInfo: document.getElementById('posOpcionesProductoInfo'),
        opcionesGrupos: document.getElementById('posOpcionesGrupos'),
        opcionesTotal: document.getElementById('posOpcionesTotalTexto'),
        modalFacturacion: document.getElementById('modalFacturacionPos'),
    };

    function mostrarVistaMesas() {
        ui.mesaSelectorView.style.display = '';
        ui.platosView.style.display = 'none';
    }

    function mostrarVistaPlatos() {
        ui.mesaSelectorView.style.display = 'none';
        ui.platosView.style.display = '';
        ui.platosMesaLabel.textContent = mesaSeleccionada
            ? `Mesa activa: ${mesaSeleccionada.nombre}`
            : 'Mesa: -';
    }

    function esc(t) {
        return String(t || '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch]));
    }

    function fmt(n) {
        return 'S/ ' + Number(n || 0).toFixed(2);
    }

    function fmtFecha(fecha) {
        const d = fecha ? new Date(fecha.replace(' ', 'T')) : new Date();
        return Number.isNaN(d.getTime()) ? String(fecha || '') : d.toLocaleString('es-PE');
    }

    function showMsg(text, isErr = false) {
        ui.msg.textContent = text;
        ui.msg.className = 'pos-msg ' + (isErr ? 'err' : 'ok');
        if (!isErr) {
            setTimeout(() => {
                ui.msg.textContent = '';
                ui.msg.className = 'pos-msg';
            }, 2800);
        }
    }

    async function getJson(url, opts = {}) {
        const r = await fetch(url, { headers: { Accept: 'application/json' }, ...opts });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje || 'Error de servidor');
        return d;
    }

    function abrirModalFacturacion() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }
        if (carrito.length > 0) {
            showMsg('Primero envía la comanda o limpia el carrito antes de facturar la mesa.', true);
            return;
        }
        document.getElementById('posFactNombre').value = `Cliente ${mesaSeleccionada.nombre}`;
        document.getElementById('posFactTelefono').value = '999999999';
        document.getElementById('posFactTipoEmision').value = 'ticket';
        document.getElementById('posFactTipoDoc').value = 'dni';
        document.getElementById('posFactNumDoc').value = '00000000';
        document.getElementById('posFactPago').value = 'efectivo';
        document.getElementById('posFactNotas').value = '';
        actualizarFormularioFacturacion();
        ui.modalFacturacion.classList.add('show');
    }

    function cerrarModalFacturacion() {
        ui.modalFacturacion.classList.remove('show');
    }

    function actualizarFormularioFacturacion() {
        const tipoEmision = document.getElementById('posFactTipoEmision').value;
        const selectTipoDoc = document.getElementById('posFactTipoDoc');
        const numDoc = document.getElementById('posFactNumDoc');
        const info = document.getElementById('posFactInfo');
        const btnBuscar = document.getElementById('btnBuscarDocumentoPos');

        if (tipoEmision === 'factura') {
            selectTipoDoc.value = 'ruc';
        }
        selectTipoDoc.disabled = tipoEmision === 'ticket' || tipoEmision === 'factura';
        numDoc.disabled = tipoEmision === 'ticket';
        btnBuscar.disabled = tipoEmision === 'ticket';

        if (tipoEmision === 'ticket') {
            numDoc.value = '00000000';
        }

        btnBuscar.textContent = selectTipoDoc.value === 'ruc'
            ? 'Buscar cliente por RUC'
            : 'Buscar cliente por DNI';

        if (tipoEmision === 'ticket') {
            info.textContent = 'Se imprimirá ticket de venta local al completar el cobro.';
        } else {
            info.textContent = 'Si SUNAT no está disponible, el sistema emitirá ticket de venta local y permitirá imprimirlo.';
        }
    }

    async function consultarDocumentoFacturacionPos() {
        const tipoEmision = document.getElementById('posFactTipoEmision').value;
        if (tipoEmision === 'ticket') {
            showMsg('La búsqueda de documento aplica para boleta o factura.', true);
            return;
        }

        const tipo = document.getElementById('posFactTipoDoc').value;
        const numero = (document.getElementById('posFactNumDoc').value || '').trim();
        const esValido = tipo === 'dni' ? /^\d{8}$/.test(numero) : /^\d{11}$/.test(numero);
        if (!esValido) {
            showMsg(tipo === 'dni' ? 'El DNI debe tener 8 dígitos.' : 'El RUC debe tener 11 dígitos.', true);
            return;
        }

        const btn = document.getElementById('btnBuscarDocumentoPos');
        btn.disabled = true;
        const textoOriginal = btn.textContent;
        btn.textContent = 'Consultando...';

        try {
            const d = await getJson(`${API_CONSULTAR_DOCUMENTO}?tipo=${encodeURIComponent(tipo)}&numero=${encodeURIComponent(numero)}`);
            const datos = d.datos || {};
            if (tipo === 'dni') {
                document.getElementById('posFactNombre').value = datos.nombreCompleto || `${datos.apellidoPaterno || ''} ${datos.apellidoMaterno || ''} ${datos.nombres || ''}`.replace(/\s+/g, ' ').trim();
            } else {
                document.getElementById('posFactNombre').value = datos.razonSocial || datos.nombreComercial || '';
            }
            showMsg('Cliente consultado correctamente.');
        } catch (e) {
            showMsg(e.message, true);
        } finally {
            btn.disabled = tipoEmision === 'ticket';
            btn.textContent = textoOriginal;
        }
    }

    function imprimirDocumentoPos(data, titulo) {
        const ventana = window.open('', '_blank', 'width=420,height=720');
        if (!ventana) {
            showMsg('No se pudo abrir la ventana de impresión.', true);
            return;
        }

        const html = `
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <title>${esc(titulo)}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 16px; color: #111; }
                    h1 { font-size: 18px; margin: 0 0 8px; }
                    .muted { color: #555; font-size: 12px; }
                    .row { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; margin: 4px 0; }
                    .blk { margin: 12px 0; }
                    .item { border-bottom: 1px dashed #ccc; padding: 8px 0; }
                    .item-name { font-weight: 700; font-size: 13px; }
                    .item-sub { font-size: 11px; color: #555; }
                    .total { font-size: 16px; font-weight: 700; margin-top: 14px; display: flex; justify-content: space-between; }
                    ul { margin: 4px 0 0 16px; padding: 0; }
                    li { font-size: 11px; color: #444; }
                </style>
            </head>
            <body>
                <h1>${esc(titulo)}</h1>
                <div class="muted">${esc(fmtFecha(data.fecha || new Date().toISOString()))}</div>
                <div class="blk">
                    <div class="row"><span>Mesa</span><strong>${esc(data.mesa?.nombre || '-')}</strong></div>
                    <div class="row"><span>Zona</span><strong>${esc(data.mesa?.zona || '-')}</strong></div>
                    ${data.numero_comprobante ? `<div class="row"><span>Comprobante</span><strong>${esc(data.numero_comprobante)}</strong></div>` : ''}
                    ${data.cliente_nombre ? `<div class="row"><span>Cliente</span><strong>${esc(data.cliente_nombre)}</strong></div>` : ''}
                    ${data.metodo_pago ? `<div class="row"><span>Pago</span><strong>${esc(data.metodo_pago)}</strong></div>` : ''}
                </div>
                <div class="blk">
                    ${(data.items || []).map((item) => `
                        <div class="item">
                            <div class="row"><span class="item-name">${esc(item.nombre_producto)}</span><strong>${fmt(item.subtotal)}</strong></div>
                            <div class="item-sub">${item.cantidad} x ${fmt(item.precio_unitario)}</div>
                            ${item.opciones && item.opciones.length ? `<ul>${item.opciones.map((op) => `<li>${esc(op.grupo_nombre)}: ${esc(op.opcion_nombre)}${Number(op.precio_extra) > 0 ? ' +' + fmt(op.precio_extra) : ''}</li>`).join('')}</ul>` : ''}
                        </div>
                    `).join('')}
                </div>
                <div class="total"><span>Total</span><span>${fmt(data.total || 0)}</span></div>
                ${data.estado_sunat ? `<div class="muted" style="margin-top:10px;">Estado SUNAT: ${esc(data.estado_sunat)}</div>` : ''}
                <script>window.onload = function(){ window.print(); };<\/script>
            </body>
            </html>`;

        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    }

    function mesaActualTexto() {
        if (!mesaSeleccionada) return 'Mesa: sin seleccionar';
        return `Mesa: ${mesaSeleccionada.nombre} (${mesaSeleccionada.estado})`;
    }

    function renderZonas() {
        ui.zonas.innerHTML = zonas.map((z) =>
            `<button type="button" class="pos-zona-btn${Number(z.id) === Number(zonaActivaId) ? ' activa' : ''}" data-zona="${z.id}">${esc(z.nombre)}</button>`
        ).join('');

        ui.zonas.querySelectorAll('[data-zona]').forEach((btn) => {
            btn.addEventListener('click', () => {
                zonaActivaId = Number(btn.dataset.zona);
                renderZonas();
                renderBoard();
            });
        });
    }

    function renderBoard() {
        const zona = zonas.find((z) => Number(z.id) === Number(zonaActivaId));
        if (!zona) {
            ui.board.innerHTML = '<div style="padding:12px;color:#64748b;">Sin zonas disponibles.</div>';
            return;
        }

        ui.board.style.width = `${Math.max(900, Number(zona.ancho || 900))}px`;
        ui.board.style.height = `${Math.max(540, Number(zona.alto || 540))}px`;

        ui.board.innerHTML = (zona.mesas || []).map((m) => {
            const cls = `pos-mesa ${m.forma === 'redonda' ? 'redonda' : ''} ${m.estado}${mesaSeleccionada && Number(mesaSeleccionada.id) === Number(m.id) ? ' seleccionada' : ''}`;
            return `
                <button type="button" class="${cls}" data-mesa="${m.id}" style="left:${Number(m.pos_x)}px; top:${Number(m.pos_y)}px;">
                    <span class="name">${esc(m.nombre)}</span>
                    <span class="meta">${m.sillas || m.capacidad} sillas</span>
                    <span class="meta">${m.estado === 'ocupada' ? `Ocupada · ${fmt(m.total_activo)}` : 'Libre'}</span>
                </button>
            `;
        }).join('');

        ui.board.querySelectorAll('[data-mesa]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.dataset.mesa);
                const mesa = (zona.mesas || []).find((m) => Number(m.id) === id) || null;
                mesaSeleccionada = mesa;
                ui.chipMesa.textContent = mesaActualTexto();
                ui.orderDisabled.style.display = 'none';
                ui.orderPanel.style.display = '';
                mostrarVistaPlatos();
                renderBoard();
                actualizarPrecuenta();
            });
        });
    }

    function calcTotalCarrito() {
        return carrito.reduce((acc, i) => acc + Number(i.precio) * Number(i.cantidad), 0);
    }

    function obtenerProductoPorId(prodId) {
        for (const categoria of catalogo) {
            const encontrado = (categoria.productos || []).find((producto) => Number(producto.id) === Number(prodId));
            if (encontrado) {
                return encontrado;
            }
        }
        return null;
    }

    function claveOpciones(opciones) {
        if (!Array.isArray(opciones) || !opciones.length) {
            return null;
        }
        return opciones.map((opcion) => Number(opcion.opcion_id)).sort((a, b) => a - b).join('_');
    }

    function renderCarrito() {
        ui.cartBody.innerHTML = carrito.length
            ? carrito.map((i, idx) => `
                <tr>
                    <td>
                        <div class="pos-cart-main">
                            ${i.imagen ? `<img class="pos-cart-thumb" src="${esc(i.imagen)}" alt="${esc(i.nombre)}">` : `<div class="pos-cart-thumb-fallback">IMG</div>`}
                            <div class="pos-cart-info">
                                <div class="pos-cart-name">${esc(i.nombre)}</div>
                                <div class="pos-cart-unit">Unitario: ${fmt(i.precio)}</div>
                                ${i.opciones && i.opciones.length ? `<ul class="pos-cart-options">${i.opciones.map((opcion) => `<li>${esc(opcion.grupo_nombre)}: <strong>${esc(opcion.opcion_nombre)}</strong>${Number(opcion.precio_extra) > 0 ? ` +${fmt(opcion.precio_extra)}` : ''}</li>`).join('')}</ul>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${i.cantidad}</td>
                    <td>${fmt(i.precio * i.cantidad)}</td>
                    <td><button type="button" class="pos-btn soft" data-del="${idx}">X</button></td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" style="text-align:center;color:#64748b;">Sin productos</td></tr>';

        ui.total.textContent = `Total: ${fmt(calcTotalCarrito())}`;

        ui.cartBody.querySelectorAll('[data-del]').forEach((btn) => {
            btn.addEventListener('click', () => {
                carrito.splice(Number(btn.dataset.del), 1);
                renderCarrito();
                renderCatalogo();
            });
        });
    }

    function rebotarCesta() {
        if (!ui.cart) {
            return;
        }
        ui.cart.classList.remove('rebote');
        void ui.cart.offsetWidth;
        ui.cart.classList.add('rebote');
    }

    function volarAlCarritoPos(imgEl) {
        if (!imgEl || !ui.cart) {
            rebotarCesta();
            return;
        }

        const origenRect = imgEl.getBoundingClientRect();
        const destinoRect = ui.cart.getBoundingClientRect();
        if (!origenRect.width || !destinoRect.width) {
            rebotarCesta();
            return;
        }

        const clone = document.createElement('img');
        clone.className = 'pos-fly';
        clone.src = imgEl.currentSrc || imgEl.src;
        clone.style.left = origenRect.left + 'px';
        clone.style.top = origenRect.top + 'px';
        clone.style.width = origenRect.width + 'px';
        clone.style.height = origenRect.height + 'px';
        document.body.appendChild(clone);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                clone.style.left = (destinoRect.left + destinoRect.width / 2 - 18) + 'px';
                clone.style.top = (destinoRect.top + 14) + 'px';
                clone.style.width = '36px';
                clone.style.height = '36px';
                clone.style.opacity = '0.22';
                clone.style.transform = 'scale(.72)';
            });
        });

        clone.addEventListener('transitionend', () => {
            clone.remove();
            rebotarCesta();
        }, { once: true });
    }

    function cerrarModalOpcionesPos() {
        productoOpcionesActual = null;
        ui.modalOpciones.classList.remove('show');
    }

    function actualizarTotalOpcionesPos() {
        if (!productoOpcionesActual) {
            ui.opcionesTotal.textContent = 'S/ 0.00';
            return;
        }

        let extra = 0;
        ui.opcionesGrupos.querySelectorAll('input:checked').forEach((input) => {
            extra += Number(input.dataset.precioExtra || 0);
        });
        ui.opcionesTotal.textContent = fmt(Number(productoOpcionesActual.precio) + extra);
    }

    function abrirModalOpcionesPos(prod, imgEl) {
        productoOpcionesActual = { producto: prod, imgEl };
        ui.opcionesProductoInfo.innerHTML = `
            ${prod.imagen ? `<img src="${esc(prod.imagen)}" alt="${esc(prod.nombre)}">` : '<div class="pos-cart-thumb-fallback">IMG</div>'}
            <div>
                <div class="pos-opciones-nombre">${esc(prod.nombre)}</div>
                <div class="pos-opciones-precio">${fmt(prod.precio)}</div>
            </div>
        `;

        ui.opcionesGrupos.innerHTML = (prod.grupos_opciones || []).map((grupo) => `
            <div class="pos-opciones-grupo" data-grupo-id="${grupo.id}" data-tipo="${esc(grupo.tipo)}" data-requerido="${grupo.requerido ? '1' : '0'}" data-max="${Number(grupo.max || 1)}">
                <div class="pos-opciones-titulo">
                    ${esc(grupo.nombre)}
                    <span class="pos-opciones-badge">${grupo.tipo === 'checkbox' ? 'Varios' : 'Uno'}</span>
                    ${grupo.requerido ? '<span class="pos-opciones-badge">Obligatorio</span>' : ''}
                </div>
                ${(grupo.opciones || []).map((opcion) => `
                    <label class="pos-opciones-label">
                        <span>
                            <input type="${grupo.tipo === 'checkbox' ? 'checkbox' : 'radio'}" name="grupo_${grupo.id}" value="${opcion.id}" data-precio-extra="${Number(opcion.precio_extra)}" data-opcion-nombre="${esc(opcion.nombre)}" data-grupo-nombre="${esc(grupo.nombre)}" data-grupo-id="${grupo.id}">
                            ${esc(opcion.nombre)}
                        </span>
                        <strong>${Number(opcion.precio_extra) > 0 ? '+' + fmt(opcion.precio_extra) : 'Gratis'}</strong>
                    </label>
                `).join('')}
            </div>
        `).join('');

        ui.opcionesGrupos.querySelectorAll('.pos-opciones-label').forEach((label) => {
            const input = label.querySelector('input');
            input.addEventListener('change', () => {
                const wrapper = label.closest('.pos-opciones-grupo');
                if (wrapper && wrapper.dataset.tipo === 'radio') {
                    wrapper.querySelectorAll('.pos-opciones-label').forEach((item) => item.classList.remove('seleccionada'));
                }
                label.classList.toggle('seleccionada', input.checked);
                actualizarTotalOpcionesPos();
            });
        });

        actualizarTotalOpcionesPos();
        ui.modalOpciones.classList.add('show');
    }

    function confirmarOpcionesPos() {
        if (!productoOpcionesActual) {
            return;
        }

        const grupos = ui.opcionesGrupos.querySelectorAll('.pos-opciones-grupo');
        let valido = true;
        const opcionesSeleccionadas = [];

        grupos.forEach((grupo) => {
            const requerido = grupo.dataset.requerido === '1';
            const tipo = grupo.dataset.tipo || 'radio';
            const max = Math.max(1, Number(grupo.dataset.max || 1));
            const marcados = grupo.querySelectorAll('input:checked');
            grupo.style.outline = '';

            if (requerido && marcados.length === 0) {
                valido = false;
                grupo.style.outline = '2px solid #e11d48';
                grupo.style.borderRadius = '14px';
            }
            if (tipo === 'checkbox' && marcados.length > max) {
                valido = false;
                grupo.style.outline = '2px solid #e11d48';
                grupo.style.borderRadius = '14px';
            }

            marcados.forEach((input) => {
                opcionesSeleccionadas.push({
                    grupo_id: Number(input.dataset.grupoId || 0),
                    grupo_nombre: input.dataset.grupoNombre || '',
                    opcion_id: Number(input.value || 0),
                    opcion_nombre: input.dataset.opcionNombre || '',
                    precio_extra: Number(input.dataset.precioExtra || 0),
                });
            });
        });

        if (!valido) {
            return;
        }

        addProducto(productoOpcionesActual.producto, opcionesSeleccionadas, productoOpcionesActual.imgEl);
        cerrarModalOpcionesPos();
    }

    function addProducto(prod, opcionesSeleccionadas = [], imgEl = null) {
        const precioBase = Number(prod.precio);
        const extraTotal = (opcionesSeleccionadas || []).reduce((acc, opcion) => acc + Number(opcion.precio_extra || 0), 0);
        const precioFinal = precioBase + extraTotal;
        const keyOpciones = claveOpciones(opcionesSeleccionadas);
        let item = null;

        if (keyOpciones) {
            item = carrito.find((carritoItem) => Number(carritoItem.id) === Number(prod.id) && carritoItem.key === keyOpciones);
        } else {
            item = carrito.find((carritoItem) => Number(carritoItem.id) === Number(prod.id) && !carritoItem.key);
        }

        if (item) {
            item.cantidad += 1;
        } else {
            carrito.push({
                id: prod.id,
                key: keyOpciones,
                nombre: prod.nombre,
                precio: precioFinal,
                precioBase,
                imagen: prod.imagen || null,
                opciones: opcionesSeleccionadas,
                cantidad: 1,
            });
        }
        renderCarrito();
        renderCatalogo();
        volarAlCarritoPos(imgEl);
    }

    function quitarProducto(prodId) {
        const idx = carrito.findIndex((i) => Number(i.id) === Number(prodId));
        if (idx < 0) {
            return;
        }
        carrito[idx].cantidad -= 1;
        if (carrito[idx].cantidad <= 0) {
            carrito.splice(idx, 1);
        }
        renderCarrito();
        renderCatalogo();
    }

    function cantidadEnCarrito(prodId) {
        return carrito
            .filter((i) => Number(i.id) === Number(prodId))
            .reduce((acc, i) => acc + Number(i.cantidad), 0);
    }

    function renderCategorias() {
        const items = [{ id: 'all', nombre: 'Todas', imagen: null }, ...catalogo.map((c) => ({ id: String(c.id), nombre: c.nombre, imagen: c.imagen || null }))];

        if (!items.some((i) => i.id === categoriaActiva)) {
            categoriaActiva = 'all';
        }

        ui.categorias.innerHTML = items.map((i) => `
            <button type="button" class="pos-categoria-btn${i.id === categoriaActiva ? ' activa' : ''}" data-categoria="${esc(i.id)}">
                ${i.imagen ? `<img src="${esc(i.imagen)}" alt="${esc(i.nombre)}">` : `<span class="pos-cat-fallback">${esc((i.nombre || 'C').slice(0, 1).toUpperCase())}</span>`}
                ${esc(i.nombre)}
            </button>
        `).join('');

        ui.categorias.querySelectorAll('[data-categoria]').forEach((btn) => {
            btn.addEventListener('click', () => {
                categoriaActiva = String(btn.dataset.categoria || 'all');
                renderCategorias();
                renderCatalogo();
            });
        });
    }

    function renderCatalogo() {
        const term = (ui.filtro.value || '').toLowerCase().trim();

        const productos = [];
        catalogo.forEach((cat) => {
            if (categoriaActiva !== 'all' && String(cat.id) !== categoriaActiva) {
                return;
            }
            (cat.productos || []).forEach((p) => {
                if (term && !p.nombre.toLowerCase().includes(term) && !(p.descripcion || '').toLowerCase().includes(term)) {
                    return;
                }
                productos.push(p);
            });
        });

        ui.catalogo.innerHTML = productos.length
            ? `<div class="pos-prod-grid">${productos.map((p) => `
                <div class="pos-prod">
                    ${p.tiene_opciones ? '<span class="pos-prod-badge">Extras</span>' : ''}
                    ${p.imagen ? `<img class="pos-prod-img" src="${esc(p.imagen)}" alt="${esc(p.nombre)}">` : `<div class="pos-prod-fallback">Sin imagen</div>`}
                    <strong>${esc(p.nombre)}</strong>
                    <small>${esc(p.descripcion || '')}</small>
                    <div class="pos-prod-footer">
                        <span>${fmt(p.precio)}</span>
                        <div class="pos-stepper">
                            <button type="button" class="pos-stepper-btn" data-minus="${p.id}">-</button>
                            <span class="pos-stepper-val">${cantidadEnCarrito(p.id)}</span>
                            <button type="button" class="pos-stepper-btn" data-plus="${p.id}">+</button>
                        </div>
                    </div>
                </div>
            `).join('')}</div>`
            : '<div style="font-size:12px;color:#64748b;">No hay productos para mostrar.</div>';

        ui.catalogo.querySelectorAll('[data-plus]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.dataset.plus);
                const producto = obtenerProductoPorId(id);
                if (!producto) {
                    return;
                }

                const card = btn.closest('.pos-prod');
                const imgEl = card ? card.querySelector('.pos-prod-img') : null;
                if (producto.tiene_opciones && Array.isArray(producto.grupos_opciones) && producto.grupos_opciones.length) {
                    abrirModalOpcionesPos(producto, imgEl);
                    return;
                }

                addProducto(producto, [], imgEl);
            });
        });

        ui.catalogo.querySelectorAll('[data-minus]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.dataset.minus);
                quitarProducto(id);
            });
        });
    }

    async function cargarCatalogo() {
        const d = await getJson(API_CATALOGO);
        catalogo = Array.isArray(d.categorias) ? d.categorias : [];
        renderCategorias();
        renderCatalogo();
    }

    async function cargarMesasEstado() {
        const d = await getJson(API_MESAS);
        zonas = Array.isArray(d.zonas) ? d.zonas : [];
        ui.chipCaja.textContent = `Caja activa · Turno #${d.caja.turno_id}`;

        if (!zonas.length) {
            ui.zonas.innerHTML = '';
            ui.board.innerHTML = '<div style="padding:12px;color:#64748b;">No hay zonas/mesas configuradas.</div>';
            mostrarVistaMesas();
            return;
        }

        if (!zonas.some((z) => Number(z.id) === Number(zonaActivaId))) {
            zonaActivaId = Number(zonas[0].id);
        }

        if (mesaSeleccionada) {
            let found = null;
            for (const z of zonas) {
                const m = (z.mesas || []).find((x) => Number(x.id) === Number(mesaSeleccionada.id));
                if (m) {
                    found = m;
                    zonaActivaId = Number(z.id);
                    break;
                }
            }
            mesaSeleccionada = found;
            ui.chipMesa.textContent = mesaActualTexto();
            if (!mesaSeleccionada) {
                ui.orderDisabled.style.display = '';
                ui.orderPanel.style.display = 'none';
                mostrarVistaMesas();
            }
        }

        renderZonas();
        renderBoard();
    }

    async function actualizarPrecuenta() {
        if (!mesaSeleccionada) {
            ui.resumenMesa.innerHTML = '<strong>Selecciona una mesa.</strong>';
            lastPrecuenta = null;
            return;
        }

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${Number(mesaSeleccionada.id)}`);
            lastPrecuenta = d;
            const pedidosActivos = d.pedidos_activos || [];
            const lineas = d.resumen_lineas || [];

            ui.resumenMesa.innerHTML = `
                <div><strong>${esc(d.mesa.nombre)}</strong> · Pedidos activos: <strong>${pedidosActivos.length}</strong></div>
                <div>Total consumido (precuenta): <strong>${fmt(d.total_precuenta)}</strong></div>
                <div class="pos-resumen-list">
                    ${lineas.length ? lineas.map((l) => `<div class="pos-resumen-item"><span>${esc(l.nombre_producto)} x${l.cantidad}</span><span>${fmt(l.subtotal)}</span></div>`).join('') : '<div style="font-size:12px;color:#64748b;">Sin consumos activos en la mesa.</div>'}
                </div>
            `;
        } catch (e) {
            lastPrecuenta = null;
            ui.resumenMesa.innerHTML = `<strong style="color:#b91c1c;">${esc(e.message)}</strong>`;
        }
    }

    async function mostrarModalPrecuenta() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${Number(mesaSeleccionada.id)}`);
            const pedidosActivos = d.pedidos_activos || [];
            const lineas = d.resumen_lineas || [];

            ui.precuentaCont.innerHTML = `
                <div style="font-size:13px;color:#334155;margin-bottom:8px;">Mesa: <strong>${esc(d.mesa.nombre)}</strong></div>
                <div style="font-size:13px;color:#334155;margin-bottom:8px;">Pedidos activos: <strong>${pedidosActivos.length}</strong></div>
                <div style="font-size:14px;color:#0f172a;margin-bottom:8px;">Total: <strong>${fmt(d.total_precuenta)}</strong></div>
                <div style="border-top:1px dashed #cbd5e1;padding-top:8px;">
                    ${lineas.length ? lineas.map((l) => `<div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>${esc(l.nombre_producto)} x${l.cantidad}</span><span>${fmt(l.subtotal)}</span></div>`).join('') : '<div style="font-size:12px;color:#64748b;">Sin consumos activos.</div>'}
                </div>
            `;
            ui.modal.classList.add('show');
        } catch (e) {
            showMsg(e.message, true);
        }
    }

    async function enviarComanda() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa.', true);
            return;
        }
        if (!carrito.length) {
            showMsg('Agrega productos al carrito antes de enviar a cocina.', true);
            return;
        }

        const btn = document.getElementById('btnEnviarComanda');
        btn.disabled = true;
        btn.textContent = 'Enviando comanda...';

        try {
            const d = await getJson(API_COMANDA, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    mesa_id: Number(mesaSeleccionada.id),
                    notas: 'Comanda POS',
                    items: carrito.map((i) => ({
                        id: i.id,
                        cantidad: i.cantidad,
                        opciones: Array.isArray(i.opciones) ? i.opciones : [],
                    })),
                }),
            });

            carrito = [];
            renderCarrito();
            renderCatalogo();
            showMsg(d.mensaje || 'Comanda enviada a cocina.');
            await cargarMesasEstado();
            await actualizarPrecuenta();
        } catch (e) {
            showMsg(e.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Enviar comanda a cocina';
        }
    }

    async function imprimirPrecuenta() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${Number(mesaSeleccionada.id)}`);
            lastPrecuenta = d;
            imprimirDocumentoPos({
                mesa: d.mesa,
                items: d.resumen_lineas || [],
                total: d.total_precuenta || 0,
                fecha: new Date().toISOString(),
            }, 'Precuenta de mesa');
        } catch (e) {
            showMsg(e.message, true);
        }
    }

    async function facturarMesaFinal() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa.', true);
            return;
        }
        if (carrito.length > 0) {
            showMsg('Primero envía la comanda actual o limpia el carrito.', true);
            return;
        }

        const tipoEmision = document.getElementById('posFactTipoEmision').value;
        const tipoDocumento = document.getElementById('posFactTipoDoc').value;
        const numeroDocumento = (document.getElementById('posFactNumDoc').value || '').replace(/\D/g, '');

        if (tipoEmision === 'boleta') {
            const validoBoleta = tipoDocumento === 'dni' ? /^\d{8}$/.test(numeroDocumento) : /^\d{11}$/.test(numeroDocumento);
            if (!validoBoleta) {
                showMsg('Documento inválido para boleta.', true);
                return;
            }
        }
        if (tipoEmision === 'factura' && !/^\d{11}$/.test(numeroDocumento)) {
            showMsg('Para factura debes ingresar un RUC válido de 11 dígitos.', true);
            return;
        }

        const btn = document.getElementById('btnConfirmarFacturacion');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        try {
            const d = await getJson(API_FACTURAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    mesa_id: Number(mesaSeleccionada.id),
                    tipo_emision: tipoEmision,
                    cliente_nombre: document.getElementById('posFactNombre').value.trim() || 'Cliente POS',
                    cliente_telefono: document.getElementById('posFactTelefono').value.trim() || '999999999',
                    tipo_documento: tipoDocumento,
                    numero_documento: numeroDocumento,
                    metodo_pago: document.getElementById('posFactPago').value,
                    notas: document.getElementById('posFactNotas').value.trim(),
                }),
            });

            cerrarModalFacturacion();
            await cargarMesasEstado();
            await actualizarPrecuenta();
            const tituloImpresion = d.tipo_emitido === 'factura'
                ? `Factura ${d.comprobante?.numero_comprobante || ''}`.trim()
                : d.tipo_emitido === 'boleta'
                    ? `Boleta ${d.comprobante?.numero_comprobante || ''}`.trim()
                    : 'Ticket de venta';

            if (d.nubefact_error) {
                // Mostrar el error exacto de Nubefact para que el admin pueda corregir la config
                alert('⚠️ ERROR NUBEFACT — No se pudo emitir el comprobante:\n\n' + d.nubefact_error + '\n\nSe imprimió el ticket de venta local. Revisa la configuración de NubeFacT en el panel de administración.');
                imprimirDocumentoPos(d.print || {}, 'Ticket de venta');
            } else if (d.pdf_url && d.tipo_emitido !== 'ticket') {
                showMsg(d.mensaje || 'Mesa facturada correctamente.');
                const win = window.open(d.pdf_url, '_blank');
                if (win) {
                    try { win.focus(); } catch (_) {}
                }
            } else {
                showMsg(d.mensaje || 'Mesa facturada correctamente.');
                imprimirDocumentoPos(d.print || {}, tituloImpresion);
            }
        } catch (e) {
            showMsg(e.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Completar y facturar';
        }
    }

    function iniciarRefresh() {
        if (timerRefresh) {
            clearInterval(timerRefresh);
        }
        timerRefresh = setInterval(async () => {
            try {
                await cargarMesasEstado();
                if (mesaSeleccionada) {
                    await actualizarPrecuenta();
                }
            } catch (_) {
                // Silencioso para no interrumpir la operación.
            }
        }, 12000);
    }

    document.getElementById('btnActualizarPrecuenta').addEventListener('click', () => {
        actualizarPrecuenta();
    });

    document.getElementById('btnImprimirPrecuenta').addEventListener('click', () => {
        imprimirPrecuenta();
    });

    document.getElementById('btnCerrarPrecuenta').addEventListener('click', () => {
        ui.modal.classList.remove('show');
    });

    ui.modal.addEventListener('click', (e) => {
        if (e.target === ui.modal) {
            ui.modal.classList.remove('show');
        }
    });

    ui.modalOpciones.addEventListener('click', (e) => {
        if (e.target === ui.modalOpciones) {
            cerrarModalOpcionesPos();
        }
    });

    document.getElementById('btnLimpiarCarrito').addEventListener('click', () => {
        carrito = [];
        renderCarrito();
        renderCatalogo();
    });

    document.getElementById('btnEnviarComanda').addEventListener('click', () => {
        enviarComanda();
    });

    document.getElementById('btnAbrirFacturacion').addEventListener('click', () => {
        abrirModalFacturacion();
    });

    document.getElementById('btnCerrarFacturacion').addEventListener('click', () => {
        cerrarModalFacturacion();
    });

    document.getElementById('btnConfirmarFacturacion').addEventListener('click', () => {
        facturarMesaFinal();
    });

    document.getElementById('posFactTipoEmision').addEventListener('change', () => {
        actualizarFormularioFacturacion();
    });

    document.getElementById('posFactTipoDoc').addEventListener('change', () => {
        actualizarFormularioFacturacion();
    });

    document.getElementById('btnBuscarDocumentoPos').addEventListener('click', () => {
        consultarDocumentoFacturacionPos();
    });

    document.getElementById('posFactNumDoc').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            consultarDocumentoFacturacionPos();
        }
    });

    document.getElementById('btnConfirmarOpcionesPos').addEventListener('click', () => {
        confirmarOpcionesPos();
    });

    document.getElementById('btnCerrarOpcionesPos').addEventListener('click', () => {
        cerrarModalOpcionesPos();
    });

    document.getElementById('btnVolverMesas').addEventListener('click', () => {
        mesaSeleccionada = null;
        ui.chipMesa.textContent = mesaActualTexto();
        ui.orderDisabled.style.display = '';
        ui.orderPanel.style.display = 'none';
        mostrarVistaMesas();
        renderBoard();
    });

    ui.modalFacturacion.addEventListener('click', (e) => {
        if (e.target === ui.modalFacturacion) {
            cerrarModalFacturacion();
        }
    });

    ui.filtro.addEventListener('input', () => {
        renderCatalogo();
    });

    (async () => {
        try {
            await Promise.all([cargarMesasEstado(), cargarCatalogo()]);
            renderCarrito();
            iniciarRefresh();
        } catch (e) {
            showMsg(e.message, true);
        }
    })();
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
