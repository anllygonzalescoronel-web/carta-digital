<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['admin']);

$db = getDB();

try {
    $turno = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
    if (!$turno) {
        jsonResponse(['ok' => false, 'mensaje' => 'No hay caja abierta para operar POS.'], 409);
    }

    $categorias = $db->query('SELECT id, nombre, imagen FROM categorias WHERE activo = 1 ORDER BY orden ASC, id ASC')->fetchAll();
    $stmtProd = $db->prepare(
        'SELECT id, nombre, descripcion, precio, precio_oferta, imagen, disponible
         FROM productos
         WHERE categoria_id = :cat
         ORDER BY orden ASC, id ASC'
    );
    $gruposPorProducto = [];

    $resolverImagen = static function (?string $imagen, string $carpeta): ?string {
        $valor = trim((string)$imagen);
        if ($valor === '') {
            return null;
        }

        $valor = str_replace('\\', '/', $valor);

        if (preg_match('#^(https?://|/)#i', $valor)) {
            return $valor;
        }

        // Si ya viene con carpeta uploads, no volver a prefijar.
        if (stripos($valor, 'uploads/') === 0) {
            return '../' . $valor;
        }

        // Si ya viene con carpeta específica, usarla directo desde admin.
        if (stripos($valor, $carpeta . '/') === 0) {
            return '../uploads/' . $valor;
        }

        return '../uploads/' . $carpeta . '/' . $valor;
    };

    try {
        $allGrupos = $db->query(
            'SELECT g.*, GROUP_CONCAT(o.id,"|",o.nombre,"|",o.precio_extra,"|",o.disponible ORDER BY o.orden SEPARATOR ";;") AS opciones_raw
             FROM producto_grupos g
             LEFT JOIN producto_opciones o ON o.grupo_id = g.id
             GROUP BY g.id
             ORDER BY g.orden ASC, g.id ASC'
        )->fetchAll();

        foreach ($allGrupos as $g) {
            $opciones = [];
            if (!empty($g['opciones_raw'])) {
                foreach (explode(';;', (string)$g['opciones_raw']) as $row) {
                    $parts = explode('|', $row);
                    if (count($parts) === 4 && (int)$parts[3] === 1) {
                        $opciones[] = [
                            'id' => (int)$parts[0],
                            'nombre' => (string)$parts[1],
                            'precio_extra' => (float)$parts[2],
                        ];
                    }
                }
            }

            if (empty($opciones)) {
                continue;
            }

            $productoId = (int)$g['producto_id'];
            if (!isset($gruposPorProducto[$productoId])) {
                $gruposPorProducto[$productoId] = [];
            }

            $gruposPorProducto[$productoId][] = [
                'id' => (int)$g['id'],
                'nombre' => (string)$g['nombre'],
                'tipo' => (string)$g['tipo'],
                'requerido' => (bool)$g['requerido'],
                'max' => (int)$g['max_opciones'],
                'opciones' => $opciones,
            ];
        }
    } catch (Throwable $e) {
        $gruposPorProducto = [];
    }

    $data = [];
    foreach ($categorias as $cat) {
        $stmtProd->execute(['cat' => $cat['id']]);
        $productos = [];
        foreach ($stmtProd->fetchAll() as $p) {
            if ((int)$p['disponible'] !== 1) {
                continue;
            }
            $precio = ((float)$p['precio_oferta'] > 0) ? (float)$p['precio_oferta'] : (float)$p['precio'];
            $productos[] = [
                'id' => (int)$p['id'],
                'nombre' => (string)$p['nombre'],
                'descripcion' => (string)($p['descripcion'] ?? ''),
                'precio' => $precio,
                'imagen' => $resolverImagen($p['imagen'] ?? null, 'productos'),
                'tiene_opciones' => !empty($gruposPorProducto[(int)$p['id']]),
                'grupos_opciones' => $gruposPorProducto[(int)$p['id']] ?? [],
            ];
        }

        $data[] = [
            'id' => (int)$cat['id'],
            'nombre' => (string)$cat['nombre'],
            'imagen' => $resolverImagen($cat['imagen'] ?? null, 'categorias'),
            'productos' => $productos,
        ];
    }

    jsonResponse(['ok' => true, 'categorias' => $data]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'mensaje' => 'No se pudo cargar el catalogo POS.'], 500);
}
