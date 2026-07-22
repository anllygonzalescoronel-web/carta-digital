// ===== Modo oscuro: recordar preferencia =====
(function () {
    const temaGuardado = localStorage.getItem('tema-admin');
    if (temaGuardado === 'oscuro') {
        document.body.classList.add('modo-oscuro');
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    const boton = document.getElementById('theme-toggle');
    if (boton) {
        actualizarIconoTema(boton);
        boton.addEventListener('click', function () {
            document.body.classList.toggle('modo-oscuro');
            const esOscuro = document.body.classList.contains('modo-oscuro');
            localStorage.setItem('tema-admin', esOscuro ? 'oscuro' : 'claro');
            actualizarIconoTema(boton);
        });

        // ===== Menú móvil (abrir/cerrar sidebar) =====
    const btnMenu = document.getElementById('btn-menu-movil');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('overlay-menu');

    if (btnMenu && sidebar && overlay) {
        btnMenu.addEventListener('click', function () {
            sidebar.classList.add('abierto');
            overlay.classList.add('visible');
        });
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('abierto');
            overlay.classList.remove('visible');
        });
    }

// ===== Botones de desplazamiento de tablas en móvil =====
    document.querySelectorAll('.tabla-scroll').forEach(function (contenedor) {
        const controles = contenedor.previousElementSibling;
        if (!controles || !controles.classList.contains('tabla-controles')) return;

        const btnIzq = controles.querySelector('.btn-scroll-izq');
        const btnDer = controles.querySelector('.btn-scroll-der');
        const paso = 150;

        function actualizarBotones() {
            btnIzq.disabled = contenedor.scrollLeft <= 0;
            btnDer.disabled = contenedor.scrollLeft >= (contenedor.scrollWidth - contenedor.clientWidth - 2);
        }

        btnIzq.addEventListener('click', function () {
            contenedor.scrollBy({ left: -paso, behavior: 'smooth' });
        });
        btnDer.addEventListener('click', function () {
            contenedor.scrollBy({ left: paso, behavior: 'smooth' });
        });
        contenedor.addEventListener('scroll', actualizarBotones);
        actualizarBotones();
    });

    }

    // ===== Resaltado de columna al pasar el cursor sobre el encabezado =====
    document.querySelectorAll('table').forEach(function (tabla) {
        const encabezados = tabla.querySelectorAll('thead th');
        encabezados.forEach(function (th, indice) {
            th.addEventListener('mouseenter', function () {
                resaltarColumna(tabla, indice, true);
            });
            th.addEventListener('mouseleave', function () {
                resaltarColumna(tabla, indice, false);
            });
        });
    });

    function resaltarColumna(tabla, indice, activar) {
    const th = tabla.querySelectorAll('thead th')[indice];
    if (th) { th.classList.toggle('col-hover', activar); }

    tabla.querySelectorAll('tbody tr').forEach(function (fila) {
        const celda = fila.children[indice];
        if (celda) { celda.classList.toggle('col-hover', activar); }
    });
}
    // ===== Conteo animado de los números grandes =====
    document.querySelectorAll('.stat-num[data-target]').forEach(function (el) {
        const destino = parseFloat(el.getAttribute('data-target')) || 0;
        const prefijo = el.getAttribute('data-prefijo') || '';
        const decimales = parseInt(el.getAttribute('data-decimales') || '0', 10);
        const duracion = 900;
        const inicio = performance.now();

        function paso(ahora) {
            const progreso = Math.min((ahora - inicio) / duracion, 1);
            const valorActual = destino * progreso;
            el.textContent = prefijo + valorActual.toFixed(decimales);
            if (progreso < 1) {
                requestAnimationFrame(paso);
            } else {
                el.textContent = prefijo + destino.toFixed(decimales);
            }
        }
        requestAnimationFrame(paso);
    });
});

function actualizarIconoTema(boton) {
    boton.innerHTML = document.body.classList.contains('modo-oscuro')
        ? '<i class="ti ti-sun"></i>'
        : '<i class="ti ti-moon"></i>';
}