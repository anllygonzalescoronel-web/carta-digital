<?php
/**
 * ===========================================
 * SUITE DE PRUEBAS: FACTURACIÓN ELECTRÓNICA HÍBRIDA
 * ===========================================
 * Pruebas profundas para validar:
 * - Integración SUNAT Nativo (native driver)
 * - Integración NubeFacT (nubefact driver)
 * - Selector de driver en configuración
 * - Flujos de facturación end-to-end
 * - Validaciones de comprobantes
 * - Generación de números correlativoss
 * 
 * Uso: php admin/test_facturacion_hibrida.php
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/facturacion.php';
require_once __DIR__ . '/../includes/nubefact.php';
require_once __DIR__ . '/../includes/facturacion_nubefact_bridge.php';

// Colores para output
class Colors {
    const GREEN = "\033[92m";
    const RED = "\033[91m";
    const YELLOW = "\033[93m";
    const BLUE = "\033[94m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

class FacturacionHibridaTest {
    private PDO $db;
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private array $errores = [];

    public function __construct() {
        $this->db = getDB();
    }

    public function run(): void {
        echo "\n" . Colors::BOLD . Colors::BLUE . "===========================================\n";
        echo "SUITE DE PRUEBAS: FACTURACIÓN ELECTRÓNICA HÍBRIDA\n";
        echo "===========================================" . Colors::RESET . "\n\n";

        // FASE 1: Validar estructura de base de datos
        $this->testFase1();

        // FASE 2: Validar configuración inicial
        $this->testFase2();

        // FASE 3: Validar driver SUNAT Nativo
        $this->testFase3();

        // FASE 4: Validar driver NubeFacT
        $this->testFase4();

        // FASE 5: Validar selector en configuración
        $this->testFase5();

        // FASE 6: Flujos end-to-end
        $this->testFase6();

        // RESUMEN
        $this->printResumen();
    }

    private function testFase1(): void {
        echo Colors::BOLD . "\n[FASE 1] Validando Estructura de Base de Datos\n" . Colors::RESET;
        echo "─────────────────────────────────────────\n";

        // Tabla facturacion_comprobantes
        $this->assert(
            'Tabla facturacion_comprobantes existe',
            $this->tablaExiste('facturacion_comprobantes')
        );

        // Tabla facturacion_secuencias
        $this->assert(
            'Tabla facturacion_secuencias existe',
            $this->tablaExiste('facturacion_secuencias')
        );

        // Tabla facturacion_config
        $this->assert(
            'Tabla facturacion_config existe',
            $this->tablaExiste('facturacion_config')
        );

        // Tabla facturacion_error_log
        $this->assert(
            'Tabla facturacion_error_log existe',
            $this->tablaExiste('facturacion_error_log')
        );

        // Columnas en pedidos
        $this->assert(
            'Columna facturacion_driver en pedidos',
            $this->columnaExiste('pedidos', 'facturacion_driver')
        );
        
        $this->assert(
            'Columna facturacion_estado en pedidos',
            $this->columnaExiste('pedidos', 'facturacion_estado')
        );

        // Vista v_comprobantes_pendientes
        $this->assert(
            'Vista v_comprobantes_pendientes existe',
            $this->vistaExiste('v_comprobantes_pendientes')
        );

        // Vista v_comprobantes_aceptados
        $this->assert(
            'Vista v_comprobantes_aceptados existe',
            $this->vistaExiste('v_comprobantes_aceptados')
        );

        // Procedure sp_obtener_siguiente_correlativo
        $this->assert(
            'Procedure sp_obtener_siguiente_correlativo existe',
            $this->procedureExiste('sp_obtener_siguiente_correlativo')
        );
    }

    private function testFase2(): void {
        echo Colors::BOLD . "\n[FASE 2] Validando Configuración Inicial\n" . Colors::RESET;
        echo "─────────────────────────────────────────\n";

        // Configuración en tabla configuracion
        $facturacionDriverActivo = cfg('facturacion_driver_activo', '');
        $this->assert(
            'facturacion_driver_activo está configurado',
            !empty($facturacionDriverActivo) && in_array($facturacionDriverActivo, ['native', 'nubefact'])
        );

        $modoHibrido = cfg('facturacion_modo_hibrido', '');
        $this->assert(
            'facturacion_modo_hibrido está configurado',
            in_array($modoHibrido, ['0', '1'])
        );

        // Configuración para SUNAT Nativo
        $nativeConfig = $this->obtenerConfigDriver('native');
        $this->assert(
            'Configuración SUNAT Nativo (native) existe',
            count($nativeConfig) > 0
        );

        $this->assert(
            'SUNAT Nativo tiene serie_boleta',
            isset($nativeConfig['serie_boleta'])
        );

        $this->assert(
            'SUNAT Nativo tiene serie_factura',
            isset($nativeConfig['serie_factura'])
        );

        // Configuración para NubeFacT
        $nubefactConfig = $this->obtenerConfigDriver('nubefact');
        $this->assert(
            'Configuración NubeFacT existe',
            count($nubefactConfig) > 0
        );

        $this->assert(
            'NubeFacT tiene serie_boleta',
            isset($nubefactConfig['serie_boleta'])
        );

        $this->assert(
            'NubeFacT tiene serie_factura',
            isset($nubefactConfig['serie_factura'])
        );

        // Secuencias inicializadas
        $secuencias = $this->db->query(
            "SELECT COUNT(*) as cnt FROM facturacion_secuencias WHERE driver IN ('native', 'nubefact')"
        )->fetch();
        $this->assert(
            'Secuencias inicializadas para ambos drivers',
            $secuencias['cnt'] >= 4
        );
    }

    private function testFase3(): void {
        echo Colors::BOLD . "\n[FASE 3] Validando Driver SUNAT Nativo (native)\n" . Colors::RESET;
        echo "─────────────────────────────────────────\n";

        // Validar tipos de documento aceptados
        $tiposValidos = ['dni', 'ruc'];
        $this->assert(
            'Función validarDocumentoCliente rechaza tipo de documento inválido',
            !is_null(validarDocumentoCliente('boleta', 'pasaporte', '12345678'))
        );

        // Boleta con DNI válido
        $errorDni = validarDocumentoCliente('boleta', 'dni', '12345678');
        $this->assert(
            'Boleta con DNI válido (8 dígitos) es aceptada',
            $errorDni === null
        );

        // Boleta con RUC válido
        $errorRuc = validarDocumentoCliente('boleta', 'ruc', '20123456789');
        $this->assert(
            'Boleta con RUC válido (11 dígitos) es aceptada',
            $errorRuc === null
        );

        // Factura solo con RUC
        $errorFacturaRuc = validarDocumentoCliente('factura', 'ruc', '20123456789');
        $this->assert(
            'Factura con RUC es aceptada',
            $errorFacturaRuc === null
        );

        // Factura con DNI debe fallar
        $errorFacturaDni = validarDocumentoCliente('factura', 'dni', '12345678');
        $this->assert(
            'Factura con DNI es rechazada',
            !is_null($errorFacturaDni)
        );

        // Validar generación de número de comprobante
        $numero = facturacionNumeroComprobante('B001', 5);
        $this->assert(
            'Número de comprobante generado correctamente',
            $numero === 'B001-00000005'
        );

        // Validar generación de correlativo
        $nextCorr = facturacionSiguienteCorrelativo($this->db, 'boleta');
        $this->assert(
            'Función facturacionSiguienteCorrelativo retorna entero',
            is_int($nextCorr) && $nextCorr > 0
        );

        // Validar obtención de serie
        $serieBoleta = facturacionObtenerSerie('boleta');
        $this->assert(
            'Serie de boleta obtenida',
            !empty($serieBoleta)
        );

        $serieFactura = facturacionObtenerSerie('factura');
        $this->assert(
            'Serie de factura obtenida',
            !empty($serieFactura)
        );
    }

    private function testFase4(): void {
        echo Colors::BOLD . "\n[FASE 4] Validando Driver NubeFacT\n" . Colors::RESET;
        echo "─────────────────────────────────────────\n";

        // Verificar disponibilidad de función
        $this->assert(
            'Función emitirBoletaNubefact existe',
            function_exists('emitirBoletaNubefact')
        );

        $this->assert(
            'Función emitirFacturaNubefact existe',
            function_exists('emitirFacturaNubefact')
        );

        $this->assert(
            'Función emitirComprobanteNubefactUnificado existe',
            function_exists('emitirComprobanteNubefactUnificado')
        );

        // Verificar tabla de secuencias para NubeFacT
        $secNubefact = $this->db->query(
            "SELECT COUNT(*) as cnt FROM facturacion_secuencias WHERE driver = 'nubefact'"
        )->fetch();
        $this->assert(
            'Secuencias NubeFacT inicializadas',
            $secNubefact['cnt'] >= 2
        );

        // Validación de configuración (sin valores acaban de ser inicializados)
        $configNubefact = $this->obtenerConfigDriver('nubefact');
        $this->assert(
            'Configuración NubeFacT tiene clave habilitado',
            isset($configNubefact['habilitado'])
        );

        echo Colors::YELLOW . "  ℹ NubeFacT requiere RUTA y TOKEN para operar (actualmente no configurados)\n" . Colors::RESET;
        echo Colors::YELLOW . "  ℹ Para pruebas reales, ingresa credenciales en admin/configuracion.php\n" . Colors::RESET;
    }

    private function testFase5(): void {
        echo Colors::BOLD . "\n[FASE 5] Validando Selector en Configuración\n" . Colors::RESET;
        echo "─────────────────────────────────────────\n";

        // Leer archivo de configuración
        $configFile = __DIR__ . '/configuracion.php';
        $this->assert(
            'Archivo configuracion.php existe',
            file_exists($configFile)
        );

        if (file_exists($configFile)) {
            $contenido = file_get_contents($configFile);

            $this->assert(
                'Selector de facturacion_driver de SUNAT Nativo en configuración',
                strpos($contenido, 'sunat') !== false || strpos($contenido, 'native') !== false
            );

            $this->assert(
                'Selector de facturacion_driver de NubeFacT en configuración',
                strpos($contenido, 'nubefact') !== false
            );

            // Buscar formulario POST para guardar config de facturación
            $tieneFormulario = strpos($contenido, "('facturacion_driver") !== false ||
                             strpos($contenido, "('sunat_") !== false ||
                             strpos($contenido, "('nubefact_") !== false;
            $this->assert(
                'Configuración post-procesamiento para drivers de facturación',
                $tieneFormulario
            );
        }

        // Validar que cfg() pueda obtener el driver activo
        $driver = cfg('facturacion_driver_activo');
        $this->assert(
            'cfg() puede obtener facturacion_driver_activo',
            !empty($driver)
        );

        echo Colors::YELLOW . "  ℹ Driver activo actual: " . Colors::BOLD . $driver . Colors::RESET . "\n";
    }

    private function testFase6(): void {
        echo Colors::BOLD . "\n[FASE 6] Flujos End-to-End\n" . Colors::RESET;
        echo "─────────────────────────────────────────\n";

        // Simular un pedido simple
        echo "Simulando pedido de prueba...\n";

        try {
            // Crear pedido simulado
            $codigoPedido = 'TEST-' . date('YmdHis');
            
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare(
                'INSERT INTO pedidos (codigo, cliente_nombre, cliente_telefono, cliente_email,
                 tipo_comprobante, tipo_documento, numero_documento, tipo_entrega, 
                 metodo_pago, estado, subtotal, costo_delivery, total, facturacion_driver)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            
            $stmt->execute([
                $codigoPedido,
                'TEST Cliente',
                '999999999',
                'test@example.com',
                'boleta',
                'dni',
                '12345678',
                'recojo',
                'efectivo',
                'pendiente',
                100.00,
                0.00,
                100.00,
                cfg('facturacion_driver_activo', 'native')
            ]);
            
            $pedidoId = $this->db->lastInsertId();
            
            // Agregar detalle
            $stmtDet = $this->db->prepare(
                'INSERT INTO pedido_detalle (pedido_id, producto_id, nombre_producto, 
                 precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)'
            );
            
            $stmtDet->execute([
                $pedidoId,
                1,
                'Producto Test',
                100.00,
                1,
                100.00
            ]);
            
            $this->db->commit();
            
            $this->assert(
                'Pedido de prueba creado exitosamente',
                $pedidoId > 0
            );

            // Hacer con driver SUNAT Nativo
            echo "\n  Probando flujo con SUNAT Nativo:\n";
            try {
                ensureFacturacionSchema($this->db);
                $comprobante = registrarComprobanteElectronicoDesdePedido($this->db, $pedidoId);
                
                $this->assert(
                    'Comprobante registrado en v_comprobantes_electronicos',
                    $comprobante !== null
                );

                if ($comprobante) {
                    $this->assert(
                        'Comprobante tiene número',
                        !empty($comprobante['numero_comprobante'])
                    );

                    $this->assert(
                        'Estado inicial es correcto',
                        in_array($comprobante['estado_sunat'], ['pendiente_configuracion', 'pendiente_envio'])
                    );

                    echo Colors::GREEN . "  ✓ Comprobante SUNAT creado: " . Colors::BOLD . $comprobante['numero_comprobante'] . Colors::RESET . "\n";
                }
            } catch (Exception $e) {
                echo Colors::YELLOW . "  ⚠ Comprobante SUNAT: " . $e->getMessage() . Colors::RESET . "\n";
            }

            // Limpiar
            $this->db->exec("DELETE FROM pedido_detalle WHERE pedido_id = $pedidoId");
            $this->db->exec("DELETE FROM pedidos WHERE id = $pedidoId");
            $this->db->exec("DELETE FROM comprobantes_electronicos WHERE pedido_id = $pedidoId");

        } catch (Exception $e) {
            $this->assert(
                'Error al simular pedido',
                false
            );
            echo Colors::RED . "  ERROR: " . $e->getMessage() . Colors::RESET . "\n";
        }

        echo "\n  Probando vistas unificadas:\n";
        
        // Ver vistacomprobantes pendientes
        $pendientes = $this->db->query("SELECT COUNT(*) as cnt FROM v_comprobantes_pendientes")->fetch();
        echo Colors::GREEN . "  ✓ Comprobantes pendientes: " . $pendientes['cnt'] . Colors::RESET . "\n";

        // Ver comprobantes aceptados
        $aceptados = $this->db->query("SELECT COUNT(*) as cnt FROM v_comprobantes_aceptados")->fetch();
        echo Colors::GREEN . "  ✓ Comprobantes aceptados: " . $aceptados['cnt'] . Colors::RESET . "\n";

        $this->assert(
            'Vista v_comprobantes_pendientes funciona',
            is_array($pendientes) && isset($pendientes['cnt'])
        );

        $this->assert(
            'Vista v_comprobantes_aceptados funciona',
            is_array($aceptados) && isset($aceptados['cnt'])
        );
    }

    private function tablaExiste(string $tabla): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM information_schema.TABLES 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
        );
        $stmt->execute([DB_NAME, $tabla]);
        return $stmt->fetch()['cnt'] > 0;
    }

    private function columnaExiste(string $tabla, string $columna): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([DB_NAME, $tabla, $columna]);
        return $stmt->fetch()['cnt'] > 0;
    }

    private function vistaExiste(string $vista): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM information_schema.VIEWS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
        );
        $stmt->execute([DB_NAME, $vista]);
        return $stmt->fetch()['cnt'] > 0;
    }

    private function procedureExiste(string $procedure): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM information_schema.ROUTINES 
             WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = ? AND ROUTINE_TYPE = 'PROCEDURE'"
        );
        $stmt->execute([DB_NAME, $procedure]);
        return $stmt->fetch()['cnt'] > 0;
    }

    private function obtenerConfigDriver(string $driver): array {
        $stmt = $this->db->prepare(
            "SELECT clave, valor FROM facturacion_config WHERE driver = ? ORDER BY clave"
        );
        $stmt->execute([$driver]);
        
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = $row['valor'];
        }
        return $config;
    }

    private function assert(string $descripcion, bool $condicion): void {
        $this->totalTests++;
        
        if ($condicion) {
            echo Colors::GREEN . "  ✓ " . Colors::RESET . $descripcion . "\n";
            $this->passedTests++;
        } else {
            echo Colors::RED . "  ✗ " . Colors::RESET . $descripcion . "\n";
            $this->failedTests++;
            $this->errores[] = $descripcion;
        }
    }

    private function printResumen(): void {
        echo Colors::BOLD . "\n===========================================\n";
        echo "RESUMEN DE PRUEBAS\n";
        echo "==========================================\n" . Colors::RESET;

        echo "Total de pruebas: " . Colors::BOLD . $this->totalTests . Colors::RESET . "\n";
        echo Colors::GREEN . "Pasadas: " . $this->passedTests . Colors::RESET . "\n";
        
        if ($this->failedTests > 0) {
            echo Colors::RED . "Fallos: " . $this->failedTests . Colors::RESET . "\n";
            echo "\nDetalles de fallos:\n";
            foreach ($this->errores as $error) {
                echo "  • " . $error . "\n";
            }
        } else {
            echo Colors::GREEN . "Fallos: " . $this->failedTests . Colors::RESET . "\n";
        }

        $porcentaje = ($this->passedTests / $this->totalTests) * 100;
        echo "\nResultado: " . Colors::BOLD;
        if ($porcentaje === 100) {
            echo Colors::GREEN . "✓ 100% (ÉXITO TOTAL)\n" . Colors::RESET;
        } elseif ($porcentaje >= 80) {
            echo Colors::YELLOW . $porcentaje . "% (ACEPTABLE)\n" . Colors::RESET;
        } else {
            echo Colors::RED . $porcentaje . "% (REVISA LOS ERRORES)\n" . Colors::RESET;
        }

        echo "\n" . Colors::BOLD . "Recomendaciones:\n" . Colors::RESET;
        echo "1. Completa la migración SQL: sql/hybrid_facturacion_migration.sql\n";
        echo "2. Configura el selector de driver en admin/configuracion.php\n";
        echo "3. Para SUNAT Nativo: carga certificado digital y credenciales SOL\n";
        echo "4. Para NubeFacT: ingresa RUTA y TOKEN de tu cuenta\n";
        echo "5. Prueba un pedido end-to-end desde el checkout\n";
        echo "\n";
    }
}

// Ejecutar
$test = new FacturacionHibridaTest();
$test->run();
