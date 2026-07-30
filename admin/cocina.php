<?php
$tituloPagina = 'Panel Cocina';
$paginaActual = 'cocina';
require __DIR__ . '/_layout_top.php';
?>

<div class="kitchen-shell">
    <section class="kitchen-hero card">
        <div>
            <p class="kitchen-kicker">Operacion en vivo</p>
            <h2>Panel de Cocina</h2>
            <p>Visualiza pedidos al instante y actualiza estados con un toque.</p>
        </div>
        <div class="kitchen-hero-actions">
            <label for="filtroEstadoCocina">Filtrar estado</label>
            <select id="filtroEstadoCocina">
                <option value="">Todos</option>
                <option value="pendiente">pendiente</option>
                <option value="pagado">pagado</option>
                <option value="en_preparacion">en_preparacion</option>
                <option value="en_camino">en_camino</option>
                <option value="entregado">entregado</option>
                <option value="cancelado">cancelado</option>
            </select>
            <button class="btn btn-primario" id="btnRefrescarCocina" type="button">Refrescar</button>
        </div>
    </section>

    <section class="kitchen-stats" id="kitchenStats"></section>

    <section class="kitchen-grid" id="kitchenGrid">
        <div class="card">Cargando pedidos...</div>
    </section>
</div>

<style>
.kitchen-shell { display: grid; gap: 16px; }
.kitchen-hero {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 18px;
    align-items: end;
    background: radial-gradient(circle at 20% 20%, #fff6eb, #ffffff 55%), linear-gradient(140deg, #ffffff, #f8fbff);
    border: 1px solid #f0f0f0;
}
.kitchen-kicker { color: #e8590c; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.kitchen-hero h2 { font-size: 26px; margin-top: 4px; }
.kitchen-hero p { color: #64748b; margin-top: 6px; }
.kitchen-hero-actions { display: grid; gap: 8px; }
.kitchen-hero-actions label { font-size: 12px; font-weight: 700; color: #475569; }
.kitchen-hero-actions select {
    width: 100%; padding: 11px 12px; border-radius: 10px; border: 1px solid #d6d6d6; background: #fff;
}
.kitchen-stats { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
.kitchen-stat {
    background: linear-gradient(145deg, #ffffff, #f7f9fc);
    border: 1px solid #eceff4;
    border-radius: 14px;
    padding: 12px;
}
.kitchen-stat .t { color: #6b7280; font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: .04em; }
.kitchen-stat .n { font-size: 24px; font-weight: 800; margin-top: 6px; }
.kitchen-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 14px;
}
.k-card {
    background: linear-gradient(170deg, #ffffff, #f8fafc);
    border: 1px solid #e8edf5;
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 10px 24px rgba(8, 15, 30, .07);
    display: grid;
    gap: 10px;
    animation: kIn .25s ease;
}
@keyframes kIn { from { transform: translateY(6px); opacity: .4; } to { transform: translateY(0); opacity: 1; } }
.k-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.k-code { font-weight: 800; font-size: 14px; }
.k-meta { color: #64748b; font-size: 12px; }
.k-client { font-weight: 700; font-size: 14px; }
.k-items { list-style: none; padding: 0; margin: 0; display: grid; gap: 4px; }
.k-items li { font-size: 12px; color: #475569; background: #f8fbff; border: 1px solid #eef3f9; border-radius: 8px; padding: 6px 8px; }
.k-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.k-actions button {
    border: none; border-radius: 10px; padding: 9px 10px; font-weight: 700; cursor: pointer; font-size: 12px;
}
.k-actions .next { background: #e8590c; color: #fff; }
.k-actions .danger { background: #fdecec; color: #9f2330; }
.k-status { display: inline-flex; align-items: center; gap: 6px; }
.k-badge-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; display: inline-block; }
@media (max-width: 980px) {
    .kitchen-hero { grid-template-columns: 1fr; }
    .kitchen-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .kitchen-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

<script>
(function() {
    const API = '../api/kitchen_orders.php';
    const estados = ['pendiente', 'pagado', 'en_preparacion', 'en_camino', 'entregado', 'cancelado'];
    const transicion = {
        pendiente: 'pagado',
        pagado: 'en_preparacion',
        en_preparacion: 'en_camino',
        en_camino: 'entregado',
        entregado: 'entregado',
        cancelado: 'cancelado'
    };

    const filtroEl = document.getElementById('filtroEstadoCocina');
    const gridEl = document.getElementById('kitchenGrid');
    const statsEl = document.getElementById('kitchenStats');
    const btnRefrescar = document.getElementById('btnRefrescarCocina');

    let pollingId = null;

    function estadoTexto(e) {
        return e.replace(/_/g, ' ');
    }

    function badgeClass(estado) {
        return 'badge badge-' + estado;
    }

    function renderStats(pedidos) {
        const conteo = { pendiente:0, pagado:0, en_preparacion:0, en_camino:0, entregado:0, cancelado:0 };
        pedidos.forEach(p => {
            if (conteo[p.estado] !== undefined) conteo[p.estado]++;
        });

        const cards = estados.map(e => `<div class="kitchen-stat"><div class="t">${estadoTexto(e)}</div><div class="n">${conteo[e]}</div></div>`);
        statsEl.innerHTML = cards.join('');
    }

    function minutosDesde(fechaIso) {
        const ts = new Date(fechaIso).getTime();
        if (!ts) return '-';
        const diff = Math.max(0, Math.floor((Date.now() - ts) / 60000));
        return diff + ' min';
    }

    function renderPedidos(pedidos) {
        if (!pedidos.length) {
            gridEl.innerHTML = '<div class="card">No hay pedidos para mostrar.</div>';
            return;
        }

        gridEl.innerHTML = pedidos.map(p => {
            const next = transicion[p.estado] || p.estado;
            const canAdvance = next !== p.estado;
            const itemsHtml = (p.items || []).slice(0, 8).map(it => `<li>${it}</li>`).join('');
            const entrega = p.tipo_entrega === 'delivery' ? 'Delivery' : 'Recojo';
            return `
                <article class="k-card" data-id="${p.id}">
                    <div class="k-head">
                        <div>
                            <div class="k-code">${p.codigo}</div>
                            <div class="k-meta">Hace ${minutosDesde(p.creado_en)} · ${entrega}</div>
                        </div>
                        <span class="badge ${badgeClass(p.estado)}">${estadoTexto(p.estado)}</span>
                    </div>
                    <div>
                        <div class="k-client">${p.cliente_nombre}</div>
                        <div class="k-meta">${p.cliente_telefono || '-'} · ${p.total_items} items · S/ ${Number(p.total).toFixed(2)}</div>
                    </div>
                    <ul class="k-items">${itemsHtml || '<li>Sin detalle</li>'}</ul>
                    <div class="k-actions">
                        <button class="next" ${canAdvance ? '' : 'disabled'} data-action="next" data-next="${next}">${canAdvance ? ('Pasar a ' + estadoTexto(next)) : 'Estado final'}</button>
                        <button class="danger" data-action="cancel">Cancelar</button>
                    </div>
                </article>
            `;
        }).join('');
    }

    async function cargarPedidos() {
        const estado = filtroEl.value;
        const url = estado ? `${API}?estado=${encodeURIComponent(estado)}` : API;

        try {
            const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje || 'Error al cargar');
            renderStats(data.pedidos || []);
            renderPedidos(data.pedidos || []);
        } catch (err) {
            gridEl.innerHTML = `<div class="card">No se pudo cargar pedidos: ${String(err.message || err)}</div>`;
        }
    }

    async function cambiarEstado(id, estado) {
        try {
            const r = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ pedido_id: id, estado })
            });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo actualizar');
            await cargarPedidos();
        } catch (err) {
            alert('Error actualizando estado: ' + (err.message || err));
        }
    }

    gridEl.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const card = e.target.closest('.k-card');
        if (!card) return;

        const id = parseInt(card.getAttribute('data-id'), 10);
        if (!id) return;

        if (btn.dataset.action === 'next') {
            const next = btn.dataset.next;
            if (next) cambiarEstado(id, next);
            return;
        }

        if (btn.dataset.action === 'cancel') {
            if (confirm('Seguro que deseas cancelar este pedido?')) {
                cambiarEstado(id, 'cancelado');
            }
        }
    });

    filtroEl.addEventListener('change', cargarPedidos);
    btnRefrescar.addEventListener('click', cargarPedidos);

    cargarPedidos();
    pollingId = setInterval(cargarPedidos, 8000);

    window.addEventListener('beforeunload', function() {
        if (pollingId) clearInterval(pollingId);
    });
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
