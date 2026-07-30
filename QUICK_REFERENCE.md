# 🚀 REFERENCIA RÁPIDA - Facturación Electrónica Híbrida

## ✅ VERIFICACIÓN RÁPIDA DEL SISTEMA

### 1️⃣ Ejecutar Test Completo
```bash
php admin/test_facturacion_hibrida.php
```
**Resultado esperado:** ✅ 43/43 pruebas exitosas

---

## 🎯 CASOS DE USO

### Caso 1: Boleta Electrónica con DNI
**Datos del cliente en checkout:**
- Tipo comprobante: `Boleta`
- Tipo documento: `DNI`
- Número: `12345678` (8 dígitos)

**Resultado:** Sistema automáticamente:
- ✅ Valida formato
- ✅ Genera número: `B001-00000001`
- ✅ Registra en `facturacion_comprobantes`
- ✅ Envía a SUNAT (si está configurado) o NubeFacT (si está seleccionado)

---

### Caso 2: Factura Electrónica con RUC
**Datos del cliente en checkout:**
- Tipo comprobante: `Factura`
- Tipo documento: `RUC`
- Número: `20123456789` (11 dígitos)

**Resultado:** Sistema automáticamente:
- ✅ Valida formato (debe ser RUC)
- ✅ Genera número: `F001-00000001`
- ✅ Registra en `facturacion_comprobantes`
- ✅ Emite factura

---

### Caso 3: Error - Tipo Documento Inválido
**Intento:** Factura con DNI (incorrecto)
```
Tipo comprobante: Factura
Tipo documento: DNI
Número: 12345678
```
**Error:** ❌ "La factura solo permite RUC"
**Solución:** Cambiar tipo documento a RUC con 11 dígitos

---

## 🔧 CONFIGURACIÓN RÁPIDA

### Acceder Panel Admin
```
http://localhost/carta-digital/admin/configuracion.php
```

### Seleccionar Driver de Facturación
```
Sección "Facturación Electrónica Híbrida"
├── Driver Activo: [native] ← Seleccionar aquí
│   ├── SUNAT Nativo (recomendado para Perú)
│   └── NubeFacT (más rápido)
│
├── Modo Híbrido: ☐ (opcional, solo desarrollo)
```

### Configurar SUNAT Nativo
```
Sección "SUNAT Nativo"
├── RUC Emisor: 20123456789
├── Usuario SOL: usuario_sol
├── Contraseña SOL: ••••••••
├── Certificado: [Cargar .pfx]
├── Contraseña Cert: ••••••••
├── Modo: [demo] → [beta] → [prod]
├── Serie Boleta: B001
└── Serie Factura: F001
```

### Configurar NubeFacT (opcional)
```
Sección "NubeFacT API"
├── RUTA: https://api.nubefact.com/api/v1/xxxxx
├── TOKEN: (copiar de panel NubeFacT)
├── Serie Boleta: BBB1
└── Serie Factura: FFF1
```

---

## 📊 CONSULTAS SQL ÚTILES

### Ver Comprobantes del Día
```sql
SELECT 
    fc.numero_comprobante,
    p.codigo,
    p.cliente_nombre,
    fc.estado,
    fc.tipo_comprobante,
    fc.creado_en
FROM facturacion_comprobantes fc
JOIN pedidos p ON fc.pedido_id = p.id
WHERE DATE(fc.creado_en) = CURDATE()
ORDER BY fc.creado_en DESC;
```

### Ver Pendientes por Procesar
```sql
SELECT * FROM v_comprobantes_pendientes;
```

### Ver Aceptados por SUNAT
```sql
SELECT * FROM v_comprobantes_aceptados;
```

### Ver Errores no Resueltos
```sql
SELECT * FROM facturacion_error_log 
WHERE resuelto = 0 
ORDER BY creado_en DESC;
```

### Obtener Siguiente Correlativo
```sql
CALL sp_obtener_siguiente_correlativo('native', 'B001', 2, @numero);
SELECT @numero;
```

---

## 🔍 VALIDACIONES AUTOMÁTICAS

### Validación de DNI
```php
validarDocumentoCliente('boleta', 'dni', '12345678');
// ✅ Valido si:
// - Son 8 dígitos
// - Es boleta (no factura)
```

### Validación de RUC
```php
validarDocumentoCliente('factura', 'ruc', '20123456789');
// ✅ Valido si:
// - Son 11 dígitos
// - Es para factura (o boleta en empresa)
```

### Validación de Comprobante
```php
validarDocumentoCliente('boleta', 'tipo_invalido', '12345678');
// ❌ Error si:
// - El tipo no es 'boleta' o 'factura'
// - El documento no es 'dni', 'ruc' o válido
```

---

## 📋 ESTADOS DE FACTURACIÓN

```
┌─ facturacion_estado (en tabla pedidos)
├─ pendiente          ← Esperando procesar
├─ procesando         ← En envío a SUNAT/NubeFacT
├─ aceptado           ← ✅ Facturación exitosa
├─ rechazado          ← ❌ Rechazado por SUNAT
├─ error              ← ⚠️ Error técnico
└─ observado          ← Aceptado con observaciones

┌─ estado (en tabla facturacion_comprobantes)
├─ pendiente_configuracion   ← Falta config SUNAT
├─ pendiente_envio          ← Listo para SUNAT
├─ aceptado                 ← ✅ Aceptado
├─ observado                ← Aceptado con obs
├─ rechazado                ← Rechazado
└─ error                    ← Error técnico
```

---

## 🛠️ FUNCIONES PRINCIPALES

### Registrar Comprobante
```php
$comprobante = registrarComprobanteElectronicoDesdePedido($db, $pedidoId);
// Retorna:
// {
//   'id' => 1,
//   'numero_comprobante' => 'B001-00000006',
//   'estado_sunat' => 'pendiente_envio'
// }
```

### Enviar a SUNAT
```php
$resultado = enviarComprobanteSunatNativo($db, $comprobanteId);
// Retorna:
// {
//   'ok' => true,
//   'estado' => 'aceptado',
//   'mensaje' => 'Aceptado por SUNAT'
// }
```

### Emitir por NubeFacT
```php
$respuesta = emitirComprobanteNubefactUnificado($db, $pedidoId);
// Retorna:
// {
//   'ok' => true,
//   'error' => null,
//   'pdf' => 'https://enlace-pdf'
// }
```

---

## 🔐 CREDENCIALES SEGURAS

**Donde se guardan:**
- ✅ Tabla `configuracion` (encriptadas en aplicación)
- ❌ NUNCA en código fuente
- ❌ NUNCA en URLs

**Campos sensibles:**
- `sunat_clave_sol` → Guardado seguro
- `sunat_certificado_clave` → Guardado seguro
- `nubefact_token` → No se muestra en checkbox

---

## 🚨 TROUBLESHOOTING RÁPIDO

| Problema | Solución |
|----------|----------|
| "Falta configurar" | Llenar todos campos de SUNAT |
| "RUC requerido para factura" | Usar RUC (11 dígitos) |
| "DNI no válido" | Debe ser 8 dígitos, solo números |
| "NubeFacT: Ya existe" | Sistema reintentar automáticamente |
| "PDF vacío" | Regenerar desde `admin/comprobantes.php` |
| "SUNAT rechaza" | Verificar modo (`demo`/`beta`/`prod`) |

---

## 📁 ARCHIVOS CLAVE

| Ruta | Propósito |
|------|----------|
| `admin/configuracion.php` | Panel de configuración |
| `admin/comprobantes.php` | Ver y gestionar comprobantes |
| `admin/test_facturacion_hibrida.php` | Suite de pruebas |
| `api/pedido.php` | Flujo de checkout |
| `includes/facturacion.php` | Lógica SUNAT Nativo |
| `includes/nubefact.php` | Lógica NubeFacT |
| `sql/hybrid_migration.sql` | Migración SQL |

---

## ✅ CHECKLIST PRE-PRODUCCIÓN

- [ ] Migración SQL ejecutada: `php migrate.php`
- [ ] Suite de pruebas 100%: `php admin/test_facturacion_hibrida.php`
- [ ] SUNAT Nativo configurado completamente
- [ ] Certificado .pfx cargado y validado
- [ ] Modo SUNAT cambiadoa `beta` (pruebas) o `prod` (real)
- [ ] Probado pedido con boleta + DNI
- [ ] Probado pedido con factura + RUC
- [ ] PDF generado correctamente
- [ ] Comprobante visible en `admin/comprobantes.php`
- [ ] Estado actualizado en tabla pedidos
- [ ] Monitoreo de errores configurado

---

**¡Sistema listo para producción!** 🎉

Para más detalles: Ver `FACTURACION_HIBRIDA_GUIA.md`
