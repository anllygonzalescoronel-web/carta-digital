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
        })
        .catch(function (error) {
            if (error.message !== 'redirigido') {
                // Si algo falla, navegamos normal como respaldo
                window.location.href = url;
            }
        });
}