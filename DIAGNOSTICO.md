# 🔍 Guía de Diagnóstico - Mega Soft Gateway

## ¿Por qué necesitas el diagnóstico?

Si estás viendo el mensaje **"La plataforma bancaria no está disponible"** o el gateway no conecta correctamente, este script te ayudará a identificar exactamente qué está fallando.

## Acceso al Sistema de Diagnóstico

### Opción 1: Desde el Admin de WordPress (Recomendado)

1. Inicia sesión en tu WordPress como administrador
2. Ve al menú lateral: **Mega Soft > 🔍 Diagnóstico**
3. Haz clic en el botón **"Ejecutar Diagnóstico Completo"**
4. Espera 20-30 segundos mientras se ejecutan todas las pruebas
5. Revisa los resultados detallados

### Opción 2: Desde código (Solo desarrolladores)

```php
// En cualquier archivo PHP de WordPress:
require_once WP_PLUGIN_DIR . '/woocommerce-megasoft-gateway/includes/class-megasoft-diagnostics.php';

$diagnostics = new MegaSoft_Diagnostics();
$result = $diagnostics->run_full_diagnostic();

// Ver resultados
print_r( $result );
```

## ¿Qué verifica el diagnóstico?

El sistema ejecuta **9 verificaciones completas**:

### 1️⃣ Simulador de PG Inactivo
- ✅ **Qué verifica:** Si el simulador de prueba está activo
- ❌ **Error común:** Simulador activo causando fallos intencionalmente
- 🔧 **Solución:** Desactívalo desde WooCommerce > Ajustes > Pagos > Mega Soft

### 2️⃣ Configuración del Gateway
- ✅ **Qué verifica:**
  - Si el gateway está habilitado
  - Modo de operación (Prueba vs Producción)
  - Estado del modo debug
- 🚀 **Modo Producción:** `https://e-payment.megasoft.com.ve/action/`
- 🧪 **Modo Prueba:** `https://paytest.megasoft.com.ve/action/`

### 3️⃣ Credenciales de API
- ✅ **Qué verifica:**
  - Código de afiliación configurado
  - Usuario API configurado
  - Contraseña API configurada
- ❌ **Error común:** Credenciales vacías o incorrectas
- 🔧 **Solución:** Ingresa las credenciales proporcionadas por Mega Soft

### 4️⃣ Pruebas de Conectividad
- ✅ **Qué verifica:**
  - Resolución DNS del dominio de Mega Soft
  - Conexión HTTP al servidor
  - Acceso al puerto 443 (HTTPS)
- ❌ **Errores comunes:**
  - `could not resolve host` → Problema DNS
  - `connection timed out` → Firewall o servidor caído
  - `connection refused` → Puerto bloqueado
- 🔧 **Soluciones:**
  - Verifica tu conexión a internet
  - Contacta a tu hosting si hay firewalls
  - Verifica con Mega Soft si su servidor está operativo

### 5️⃣ Verificación SSL
- ✅ **Qué verifica:**
  - Si tu sitio usa HTTPS
  - Si OpenSSL está disponible
- ❌ **Error común:** Sitio sin SSL en producción
- 🔧 **Solución:** Instala certificado SSL (Let's Encrypt es gratuito)

### 6️⃣ Requisitos del Sistema
- ✅ **Qué verifica:**
  - PHP 7.4+
  - cURL habilitado
  - WordPress 5.8+
  - WooCommerce 6.0+
- ❌ **Error común:** Versiones desactualizadas
- 🔧 **Solución:** Actualiza PHP, WordPress o WooCommerce según corresponda

### 7️⃣ Base de Datos
- ✅ **Qué verifica:**
  - Si existe la tabla de transacciones
  - Cantidad de registros
  - Transacciones recientes
- ❌ **Error común:** Tabla no creada
- 🔧 **Solución:** Desactiva y reactiva el plugin

### 8️⃣ Prueba de Pre-Registro (La más importante)
- ✅ **Qué hace:** Intenta crear un pre-registro real con Mega Soft
- ✅ **Si funciona:** Recibirás un número de control válido
- ❌ **Si falla:** Te mostrará el error exacto
- 🔧 **Diagnósticos automáticos:**
  - Problema DNS
  - Timeout
  - Credenciales incorrectas
  - Respuesta inválida del servidor

### 9️⃣ Logs Recientes
- ✅ **Qué verifica:** Errores en las últimas 24 horas
- 📊 **Muestra:** Los 5 errores más recientes
- 🔍 **Útil para:** Ver el historial de problemas

## Interpretando los Resultados

### ✅ Símbolos de Estado

- ✅ **Verde (Éxito):** Todo funciona correctamente
- ⚠️ **Amarillo (Advertencia):** Funciona pero hay mejoras recomendadas
- ❌ **Rojo (Error):** Problema crítico que DEBE resolverse
- ℹ️ **Azul (Info):** Información adicional

### 📊 Resumen Final

El diagnóstico mostrará un resumen con:
- Cantidad de verificaciones exitosas
- Cantidad de advertencias
- Cantidad de errores críticos

**Estado ideal:**
```
✅ ¡Todo está en orden!
📊 Verificaciones exitosas: 25
⚠️ Advertencias: 0
❌ Errores críticos: 0
```

## Problemas Más Comunes y Sus Soluciones

### Problema 1: "SIMULADOR ACTIVO"
```
❌ SIMULADOR ACTIVO
⚠️ El simulador de PG inactivo está ACTIVO.
```

**Causa:** El simulador está activado para pruebas de certificación.

**Solución:**
1. Ve a: Mega Soft > 🔍 Diagnóstico
2. Haz clic en: **"Desactivar Simulador PG Inactivo"**
3. Confirma la acción
4. Ejecuta el diagnóstico nuevamente

O manualmente:
1. Ve a: WooCommerce > Ajustes > Pagos > Mega Soft
2. Busca: "Simulador de PG Inactivo"
3. Haz clic en: "Desactivar Simulación"

---

### Problema 2: "ERROR DE CONEXIÓN HTTP"
```
❌ ERROR DE CONEXIÓN HTTP
❌ could not resolve host: e-payment.megasoft.com.ve
```

**Causa:** El servidor no puede acceder a Mega Soft.

**Soluciones posibles:**
1. **Verifica tu conexión a internet**
2. **Prueba resolución DNS:**
   ```bash
   ping e-payment.megasoft.com.ve
   nslookup e-payment.megasoft.com.ve
   ```
3. **Contacta a tu hosting:** Puede haber un firewall bloqueando
4. **Verifica con Mega Soft:** Su servidor puede estar caído

---

### Problema 3: "CREDENCIALES INVÁLIDAS"
```
❌ ERROR EN PRE-REGISTRO
❌ credenciales inválidas o requiere enviar las credenciales
```

**Causa:** Usuario, contraseña o código de afiliación incorrectos.

**Solución:**
1. Ve a: WooCommerce > Ajustes > Pagos > Mega Soft
2. Verifica que tengas configurado:
   - Código de Afiliación
   - Usuario API
   - Contraseña API
3. Confirma con Mega Soft que las credenciales sean correctas
4. Verifica que estés usando las credenciales del ambiente correcto (Prueba vs Producción)

**Credenciales de prueba (ejemplo):**
```
Código de Afiliación: 20250508
Usuario API: multimuniv
Contraseña API: Caracas123.1
Modo: PRUEBA (activado)
```

---

### Problema 4: "SSL REQUERIDO"
```
❌ SSL REQUERIDO
❌ Tu sitio NO usa HTTPS. Esto es OBLIGATORIO en producción.
```

**Causa:** Tu sitio no tiene certificado SSL instalado.

**Solución:**
1. **Instala un certificado SSL:**
   - Let's Encrypt (gratuito) - disponible en la mayoría de hostings
   - Certificado comercial
2. **En cPanel/Plesk:**
   - Busca "Let's Encrypt" o "SSL/TLS"
   - Instala el certificado
3. **Fuerza HTTPS en WordPress:**
   ```php
   // wp-config.php
   define('FORCE_SSL_ADMIN', true);
   ```

---

### Problema 5: "PUERTO 443 BLOQUEADO"
```
❌ PUERTO 443 BLOQUEADO
❌ No se puede conectar al puerto 443
```

**Causa:** El firewall del servidor bloquea conexiones salientes por HTTPS.

**Solución:**
1. **Contacta a tu proveedor de hosting**
2. **Solicita:** Abrir puerto 443 saliente hacia:
   - `e-payment.megasoft.com.ve` (producción)
   - `paytest.megasoft.com.ve` (pruebas)

---

### Problema 6: "HTTP Error 503/502/504"
```
❌ ERROR HTTP
❌ Servidor responde con error (HTTP 503)
```

**Causa:** El servidor de Mega Soft no está disponible temporalmente.

**Solución:**
1. **Espera 5-10 minutos** y vuelve a intentar
2. **Contacta a Mega Soft** para verificar estado de su plataforma
3. **Verifica en redes sociales** si hay mantenimiento programado
4. **Mientras tanto:** Usa otro método de pago en tu tienda

---

## Acciones Después del Diagnóstico

### Si hay errores críticos:
1. ✅ Lee cada error detenidamente
2. ✅ Aplica las soluciones sugeridas
3. ✅ Ejecuta el diagnóstico nuevamente
4. ✅ Repite hasta resolver todos los errores

### Si todo está en orden:
1. ✅ Haz una compra de prueba
2. ✅ Verifica que la redirección funcione
3. ✅ Confirma que el pago se procese
4. ✅ Activa el gateway para tus clientes

### Si aún tienes problemas después de resolver errores:
1. ✅ Activa el modo Debug (WooCommerce > Ajustes > Pagos > Mega Soft)
2. ✅ Intenta una transacción
3. ✅ Revisa: Mega Soft > Logs
4. ✅ Busca mensajes de error específicos
5. ✅ Contacta al soporte técnico con:
   - Captura del diagnóstico
   - Logs recientes
   - Descripción del problema

## Exportar Resultados del Diagnóstico

### Para soporte técnico:
1. Ejecuta el diagnóstico completo
2. Haz clic en **"Imprimir Reporte"**
3. Guarda como PDF
4. Envía a soporte técnico de Mega Soft o tu desarrollador

### Para documentación:
1. Captura pantalla de los resultados
2. Incluye la sección de resumen
3. Incluye errores específicos si los hay

## Preguntas Frecuentes

### ¿Con qué frecuencia debo ejecutar el diagnóstico?
- Cuando el gateway deje de funcionar
- Después de cambiar credenciales
- Después de cambiar de modo Prueba a Producción
- Después de actualizar el plugin
- Después de migrar el sitio a otro servidor

### ¿El diagnóstico hace transacciones reales?
No. La prueba de pre-registro crea una transacción de $0.01 que no se procesa completamente. Solo verifica que la API responda correctamente.

### ¿Puedo ejecutar el diagnóstico en producción?
Sí, es seguro. No afecta transacciones reales de clientes.

### ¿Qué hago si el diagnóstico no se ejecuta?
1. Verifica que estés logueado como administrador
2. Verifica que el plugin esté activo
3. Revisa errores de PHP en los logs del servidor
4. Contacta a tu proveedor de hosting

## Información Técnica

### Archivos del Sistema de Diagnóstico

```
includes/
├── class-megasoft-diagnostics.php      # Motor de diagnóstico
└── class-megasoft-diagnostics-ui.php   # Interfaz de usuario
```

### Hooks y Filtros

```php
// Personalizar verificaciones
add_filter( 'megasoft_diagnostic_checks', function( $checks ) {
    // Agregar verificaciones personalizadas
    return $checks;
});
```

### Base de Datos

El diagnóstico NO modifica la base de datos. Solo lee datos existentes.

## Soporte

Si después de ejecutar el diagnóstico y seguir las soluciones aún tienes problemas:

1. **Soporte Mega Soft:**
   - Email: soporte@megasoft.com.ve
   - Teléfono: Ver documentación oficial

2. **Tu proveedor de hosting:**
   - Para problemas de conectividad, SSL, o PHP

3. **Desarrollador del plugin:**
   - Para problemas específicos del código
   - Incluye siempre el reporte de diagnóstico completo

---

**Última actualización:** <?php echo date('Y-m-d'); ?>
**Versión del plugin:** 3.0.5
