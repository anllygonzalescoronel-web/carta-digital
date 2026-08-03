<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
requerirRol(['admin']);

header('Content-Type: application/json; charset=utf-8');

$db  = getDB();
$tipo    = $_GET['tipo']    ?? 'productos'; // productos | categorias
$periodo = $_GET['periodo'] ?? 'mes';       // hoy | semana | mes | anio

// Construir cláusula de fecha
switch ($periodo) {
    case 'hoy':
        $whereDate = "AND DATE(pe.creado_en) = CURDATE()";
        break;
    case 'semana':
        $whereDate = "AND pe.creado_en >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
        break;
    case 'anio':
        $whereDate = "AND YEAR(pe.creado_en) = YEAR(CURDATE())";
        break;
    default: // mes
        $whereDate = "AND YEAR(pe.creado_en) = YEAR(CURDATE()) AND MONTH(pe.creado_en) = MONTH(CURDATE())";
        break;
}

$items = [];

try {
    if ($tipo === 'categorias') {
        $stmt = $db->query("
            SELECT c.id, c.nombre, c.imagen,
                   SUM(pd.cantidad) AS total_vendido
            FROM pedido_detalle pd
            JOIN productos p  ON p.id  = pd.producto_id
            JOIN categorias c ON c.id  = p.categoria_id
            JOIN pedidos pe   ON pe.id = pd.pedido_id
            WHERE pe.estado != 'cancelado'
              AND pd.producto_id IS NOT NULL
              $whereDate
            GROUP BY c.id, c.nombre, c.imagen
            ORDER BY total_vendido DESC
            LIMIT 10
        ");
    } else {
        $stmt = $db->query("
            SELECT p.id, p.nombre, p.imagen,
                   SUM(pd.cantidad) AS total_vendido
            FROM pedido_detalle pd
            JOIN productos p ON p.id  = pd.producto_id
            JOIN pedidos pe  ON pe.id = pd.pedido_id
            WHERE pe.estado != 'cancelado'
              AND pd.producto_id IS NOT NULL
              $whereDate
            GROUP BY p.id, p.nombre, p.imagen
            ORDER BY total_vendido DESC
            LIMIT 12
        ");
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $carpeta = $tipo === 'categorias' ? 'categorias' : 'productos';
    $max = !empty($rows) ? (int)$rows[0]['total_vendido'] : 1;

    foreach ($rows as $i => $row) {
        $img = '';
        if (!empty($row['imagen'])) {
            if (str_starts_with($row['imagen'], 'http')) {
                $img = $row['imagen'];
            } elseif (str_contains($row['imagen'], 'uploads/')) {
                $img = '../' . $row['imagen'];
            } else {
                $img = '../uploads/' . $carpeta . '/' . $row['imagen'];
            }
        }
        $items[] = [
            'rank'          => $i + 1,
            'nombre'        => $row['nombre'],
            'imagen'        => $img,
            'total_vendido' => (int)$row['total_vendido'],
            'pct'           => $max > 0 ? round($row['total_vendido'] / $max * 100) : 0,
        ];
    }
} catch (Exception $e) {
    $items = [];
}

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
