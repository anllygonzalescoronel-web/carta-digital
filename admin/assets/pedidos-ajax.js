// ==================================================================
// Filtros y paginación de "Pedidos" por AJAX (sin recargar la página)
// Requiere que pedidos.php tenga el <div id="listado-pedidos-ajax">
// con los links de filtro dentro de .filtros-ajax y la paginación
// dentro de .paginacion-pedidos (ya vienen así en el archivo).
// ==================================================================
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('listado-pedidos-ajax')) {
        return; // no estamos en la página de Pedidos, no hacemos nada
    }

    function enlaceEstaEnZonaAjax(enlace) {
        return enlace.closest('#listado-pedidos-ajax .filtros-ajax')
            || enlace.closest('#listado-pedidos-ajax .paginacion-pedidos');
    }

    // Delegamos el click en "document" (nunca se destruye), así seguimos
    // escuchando aunque el contenido de #listado-pedidos-ajax se reemplace.
    document.addEventListener('click', function (e) {
        const enlace = e.target.closest('a');
        if (!enlace || !enlaceEstaEnZonaAjax(enlace)) return;

        if (enlace.classList.contains('deshabilitado')) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        cargarPedidosAjax(enlace.getAttribute('href'), true);
    });

    // Soporte para el botón "atrás/adelante" del navegador
    window.addEventListener('popstate', function () {
        cargarPedidosAjax(window.location.href, false);
    });

    // Como este es el primer HTML que trajo el servidor (no vino por fetch),
    // los botones de scroll de la tabla YA los conectó admin.js normalmente.
    // No hace falta llamarlo aquí también.
});

function cargarPedidosAjax(url, guardarHistorial) {
    const contenedor = document.getElementById('listado-pedidos-ajax');
    if (!contenedor) {
        window.location.href = url;
        return;
    }

    contenedor.style.transition = 'opacity .15s ease';
    contenedor.style.opacity = '.45';
    contenedor.style.pointerEvents = 'none';

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (respuesta) {
            if (respuesta.redirected) {
                // Ej: la sesión expiró y nos manda a login.php
                window.location.href = respuesta.url;
                throw new Error('redirigido');
            }
            if (!respuesta.ok) {
                throw new Error('Error de red al cargar pedidos');
            }
            return respuesta.text();
        })
        .then(function (html) {
            const htmlLimpio = html.trim();
            if (!htmlLimpio) {
                throw new Error('Respuesta vacía');
            }
            contenedor.outerHTML = htmlLimpio;
            if (guardarHistorial) {
                window.history.pushState({}, '', url);
            }
            // El bloque que acabamos de insertar es HTML nuevo: sus botones
            // de scroll (‹ ›) y el resaltado de columnas todavía no tienen
            // ningún evento conectado (admin.js solo corrió una vez, al
            // cargar la página por primera vez, y esos elementos de ese
            // entonces ya no existen). Los volvemos a conectar acá, a mano,
            // cada vez que esto pasa.
            reconectarBotonesScrollTabla();
            reconectarResaltadoColumnas();
        })
        .catch(function (error) {
            if (error.message !== 'redirigido') {
                // Si algo falla, navegamos normal como respaldo
                window.location.href = url;
            }
        });
}

// Misma lógica que usa admin.js para las flechitas de scroll horizontal,
// pero la repetimos acá para poder llamarla de nuevo después de cada AJAX.
function reconectarBotonesScrollTabla() {
    const contenedor = document.getElementById('listado-pedidos-ajax');
    if (!contenedor) return;

    contenedor.querySelectorAll('.tabla-scroll').forEach(function (scrollEl) {
        const controles = scrollEl.previousElementSibling;
        if (!controles || !controles.classList.contains('tabla-controles')) return;

        const btnIzq = controles.querySelector('.btn-scroll-izq');
        const btnDer = controles.querySelector('.btn-scroll-der');
        if (!btnIzq || !btnDer) return;

        const paso = 150;

        function actualizarBotones() {
            btnIzq.disabled = scrollEl.scrollLeft <= 0;
            btnDer.disabled = scrollEl.scrollLeft >= (scrollEl.scrollWidth - scrollEl.clientWidth - 2);
        }

        btnIzq.addEventListener('click', function () {
            scrollEl.scrollBy({ left: -paso, behavior: 'smooth' });
        });
        btnDer.addEventListener('click', function () {
            scrollEl.scrollBy({ left: paso, behavior: 'smooth' });
        });
        scrollEl.addEventListener('scroll', actualizarBotones);
        actualizarBotones();
    });
}

// Misma lógica que usa admin.js para resaltar la columna al pasar el
// cursor sobre el encabezado, repetida acá para poder reconectarla
// después de cada actualización por AJAX.
function reconectarResaltadoColumnas() {
    const contenedor = document.getElementById('listado-pedidos-ajax');
    if (!contenedor) return;

    contenedor.querySelectorAll('table').forEach(function (tabla) {
        const encabezados = tabla.querySelectorAll('thead th');
        encabezados.forEach(function (th, indice) {
            th.addEventListener('mouseenter', function () {
                resaltarColumnaAjax(tabla, indice, true);
            });
            th.addEventListener('mouseleave', function () {
                resaltarColumnaAjax(tabla, indice, false);
            });
        });
    });
}

function resaltarColumnaAjax(tabla, indice, activar) {
    const th = tabla.querySelectorAll('thead th')[indice];
    if (th) { th.classList.toggle('col-hover', activar); }

    tabla.querySelectorAll('tbody tr').forEach(function (fila) {
        const celda = fila.children[indice];
        if (celda) { celda.classList.toggle('col-hover', activar); }
    });
}