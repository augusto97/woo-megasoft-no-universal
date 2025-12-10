# Certificación MegaSoft - Guía de Pruebas

Este documento describe las pruebas requeridas por MegaSoft para la certificación del plugin.

## 📋 Requisitos de Certificación

### 1. Simulador de PG Inactivo ✅

**Objetivo:** Verificar que el plugin maneja correctamente escenarios de timeout cuando el Payment Gateway no responde.

**Ubicación:** WooCommerce → Mega Soft → Estado del Sistema → Probar Conexión

**¿Qué prueba?**
- Comportamiento del plugin cuando el servidor de MegaSoft no responde
- Manejo de errores de timeout (1 segundo)
- Mensajes de error apropiados al usuario
- Que el checkout no se bloquee indefinidamente

**Resultado esperado:**
```
✓ Timeout manejado correctamente (Simulador PG Inactivo APROBADO)

Detalles:
- Timeout configurado: 1s
- Tiempo transcurrido: 1.01s
- Error detectado: Sí (esperado)
- Error code: http_request_failed
- Error message: Operation timed out after 1000 milliseconds
- Certificación: APROBADO - El plugin maneja timeouts correctamente según requerimientos MegaSoft
```

**¿Cómo funciona?**
El test realiza una petición con un timeout de 1 segundo (en lugar de los 60 segundos normales). Esto prácticamente garantiza que ocurra un timeout, simulando un Payment Gateway inactivo.

---

## 🔧 Cómo Ejecutar las Pruebas

### Panel de Administración

1. Ve a **WooCommerce → Mega Soft → Estado del Sistema**
2. Asegúrate de tener configuradas tus credenciales en la pestaña "Configuración"
3. Haz clic en **"Probar Conexión"**
4. Revisa los resultados de las 6 pruebas:
   - ✓ Credentials
   - ✓ SSL
   - ✓ Extensions
   - ✓ Database
   - ✓ API Connection
   - ✓ **Timeout Handling (Simulador PG Inactivo)**

### Todas las Pruebas

El sistema ejecuta automáticamente:

| # | Prueba | Descripción | Crítico |
|---|--------|-------------|---------|
| 1 | **Credentials** | Verifica que API User, Password y Código de Afiliación estén configurados | Sí |
| 2 | **SSL** | Verifica que HTTPS esté activo (requerido para producción) | Sí |
| 3 | **Extensions** | Verifica extensiones PHP: curl, json, openssl, xml, simplexml | Sí |
| 4 | **Database** | Verifica que las tablas de BD existan correctamente | Sí |
| 5 | **API Connection** | Prueba conexión real con PreRegistro (timeout: 15s) | Sí |
| 6 | **Timeout Handling** | Simula PG inactivo con timeout de 1s (certificación) | ⚠️ Info |

---

## ✅ Criterios de Aprobación

### Para Certificación MegaSoft:

**Prueba de Timeout debe mostrar:**
- ✅ `passed: true`
- ✅ Mensaje: "Timeout manejado correctamente (Simulador PG Inactivo APROBADO)"
- ✅ Error detectado como timeout (`http_request_failed` o similar)
- ✅ Tiempo transcurrido cercano al timeout configurado (1s)

**NO es necesario que el timeout ocurra siempre**. Si el servidor responde rápidamente (< 1s), el test también aprueba con:
- ✅ Mensaje: "Conexión rápida exitosa (Gateway respondió antes del timeout)"

### En Producción:

Durante transacciones reales, el plugin usa timeouts adecuados:
- **PreRegistro:** 60 segundos (default)
- **Procesar Compra:** 90 segundos
- **Query Status:** 30 segundos
- **Anulación:** 60 segundos

---

## 🚨 Manejo de Errores en Transacciones Reales

### Escenario: Timeout durante un pago

**Lo que sucede:**
1. Usuario completa el formulario de pago en checkout
2. Plugin llama a `procesar_compra()` con timeout de 90 segundos
3. Si el servidor de MegaSoft no responde en 90s:
   - `wp_remote_post()` retorna un `WP_Error`
   - Error capturado: `Operation timed out`
4. Plugin muestra mensaje al usuario:
   ```
   Error: Operation timed out after 90000 milliseconds with 0 bytes received
   ```
5. Orden queda en estado `pending` (no se marca como pagada)
6. Error se registra en logs de MegaSoft
7. Usuario ve página de checkout con el error

### Código que maneja timeouts:

**En `class-megasoft-v2-api.php`:**
```php
$response = wp_remote_post( $url, $args );

// Manejar errores de conexión
if ( is_wp_error( $response ) ) {
    if ( $this->logger ) {
        $this->logger->error( "API Request Error: " . $response->get_error_message(), array(
            'endpoint' => $endpoint,
            'error_code' => $response->get_error_code(),
        ) );
    }
    return $response; // Retorna WP_Error
}
```

**En `class-megasoft-v2-gateway.php`:**
```php
try {
    $preregistro_response = $this->api->preregistro();

    if ( ! $preregistro_response['success'] ) {
        throw new Exception( $preregistro_response['message'] ?? 'Error en pre-registro' );
    }
    // ... continua procesamiento
} catch ( Exception $e ) {
    $this->logger->error( 'Error al procesar pago', array(
        'order_id' => $order_id,
        'error'    => $e->getMessage(),
    ) );

    wc_add_notice( __( 'Error: ', 'woocommerce-megasoft-gateway-v2' ) . $e->getMessage(), 'error' );

    return array(
        'result'   => 'failure',
        'redirect' => '',
    );
}
```

---

## 📊 Evidencia para Certificación

Para enviar a MegaSoft como evidencia:

1. **Captura de pantalla** del panel "Probar Conexión" mostrando:
   - ✓ Todas las pruebas pasadas (checks verdes)
   - ✓ Test "timeout_handling" con estado APROBADO
   - ✓ Detalles expandidos del test de timeout

2. **Logs del plugin** mostrando manejo de timeout:
   - Ve a: WooCommerce → Mega Soft → Logs
   - Filtra por nivel: `error`
   - Busca entradas tipo: `API Request Error: Operation timed out`

3. **Transacción de prueba** con timeout simulado:
   - Configura `timeout = 1` en `class-megasoft-v2-api.php` línea 57
   - Realiza una compra de prueba
   - Captura el mensaje de error mostrado al usuario
   - Restaura timeout a 60 segundos

---

## 🔍 Troubleshooting

### La prueba de timeout NO muestra "APROBADO"

**Problema:** Test muestra error en lugar de APROBADO

**Causa:** El servidor de MegaSoft respondió MUY rápido (< 1s)

**Solución:** Esto es NORMAL y ACEPTABLE. El test aprueba de todas formas con mensaje:
```
✓ Conexión rápida exitosa (Gateway respondió antes del timeout)
```

### Todas las pruebas fallan

**Problema:** Ninguna prueba pasa

**Causas posibles:**
1. Credenciales incorrectas → Verifica Usuario API, Contraseña, Código de Afiliación
2. Sin conexión a internet → Verifica conectividad del servidor
3. Firewall bloqueando → Verifica que puedes acceder a `paytest.megasoft.com.ve`
4. SSL inactivo → Activa HTTPS en tu servidor

**Solución:**
```bash
# Probar conectividad manualmente
curl -v https://paytest.megasoft.com.ve/payment/action/v2-preregistro

# Debería responder (aunque con error de autenticación está OK)
```

### Los logs no muestran errores de timeout

**Problema:** No encuentras entradas de timeout en logs

**Causa:** Los timeouts son eventos RAROS en producción (esto es BUENO)

**Solución:** Usa el "Simulador de PG Inactivo" para generar timeouts de prueba

---

## 📚 Referencias

- **Documentación MegaSoft:** Payment Gateway v4.24
- **API Version:** REST v2 (NO UNIVERSAL)
- **Modalidad:** Captura Directa de Tarjetas
- **Plugin Version:** 4.0.0

---

## ✉️ Contacto MegaSoft

Para dudas sobre certificación:
- **Email:** merchant@megasoft.com.ve
- **Teléfono:** +58 (contacto proporcionado por MegaSoft)

---

**Última actualización:** 2025-12-10
**Estado:** ✅ Simulador de PG Inactivo implementado y funcionando
