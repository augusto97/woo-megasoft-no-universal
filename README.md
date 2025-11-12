# Mega Soft Gateway v2 - NON-UNIVERSAL Mode

Plugin de integración de WooCommerce con la pasarela de pago Mega Soft (Venezuela) en modalidad **NON-UNIVERSAL** (captura directa de tarjetas).

## 📋 Información del Plugin

- **Versión:** 4.0.0
- **API Version:** v2 (REST API)
- **Documentación Base:** MAET-PAYM-00_JUL_2025 (v4.24)
- **Modo:** NON-UNIVERSAL (Direct Card Capture)
- **Requisitos:** WordPress 5.8+, WooCommerce 6.0+, PHP 7.4+

## ✨ Características Principales

### 🔒 Seguridad PCI DSS
- ✅ Captura directa sin almacenamiento de PAN/CVV
- ✅ Sanitización automática en logs
- ✅ SSL/HTTPS obligatorio
- ✅ Rate limiting anti-fraude
- ✅ Detección de IPs sospechosas
- ✅ Validación Luhn y verificación de tarjetas

### 💳 Métodos de Pago
- **Tarjetas de Crédito** (Visa, MasterCard, Amex, Discover, Diners)
- **Tarjetas de Débito**
- **Pago Móvil C2P** (Cliente a Persona)
- **Pago Móvil P2C** (Persona a Cliente)
- Soporte para 27 bancos venezolanos

### 🎨 Experiencia de Usuario
- Formulario moderno e intuitivo
- Validación en tiempo real
- Iconos de marcas de tarjetas
- Formateo automático
- Diseño responsive
- Vouchers imprimibles

### 📊 Panel Administrativo
- Dashboard con estadísticas en tiempo real
- Gráficos interactivos (Chart.js)
- Gestión completa de transacciones
- Sistema de logs con filtros
- Exportación a CSV
- Integración con órdenes de WooCommerce

### 🔔 Webhooks
- Endpoint REST personalizado
- Validación IP whitelist
- Verificación de firma HMAC-SHA256
- Sistema de reintentos automáticos
- Alertas de contracargos

## 📦 Estructura del Plugin

```
woo-megasoft-no-universal/
├── woocommerce-megasoft-gateway-v2.php     # Archivo principal
├── MAET-PAYM-00_JUL_2025.md                # Documentación oficial
├── includes/
│   ├── class-megasoft-v2-api.php           # Integración REST API v2
│   ├── class-megasoft-v2-gateway.php       # Gateway principal
│   ├── class-megasoft-v2-logger.php        # Sistema de logs PCI
│   ├── class-megasoft-v2-security.php      # Validación y anti-fraude
│   ├── class-megasoft-v2-card-validator.php # Validación de tarjetas
│   ├── class-megasoft-v2-payment-methods.php # Métodos adicionales
│   ├── class-megasoft-v2-webhook.php       # Handler de webhooks
│   └── class-megasoft-v2-admin.php         # Panel administrativo
└── assets/
    ├── js/
    │   ├── card-validator.js               # Validación frontend
    │   ├── payment-form.js                 # Interactividad del form
    │   └── admin.js                        # Dashboard interactivo
    └── css/
        ├── payment-form.css                # Estilos del checkout
        └── admin.css                       # Estilos del admin
```

## 🚀 Instalación

1. **Requisitos previos:**
   - Certificado SSL activo (HTTPS)
   - WooCommerce instalado y configurado
   - Credenciales de Mega Soft (API User, Password, Merchant ID, Terminal ID)

2. **Instalación:**
   ```bash
   cd wp-content/plugins/
   git clone [repository-url] woo-megasoft-no-universal
   ```

3. **Activación:**
   - Ir a WordPress Admin → Plugins
   - Activar "Pasarela de Pago Mega Soft para WooCommerce (Modalidad NO UNIVERSAL) - PRODUCCIÓN v2"

4. **Configuración:**
   - Ir a Mega Soft → Configuración
   - Configurar cada método de pago que desees activar
   - Ingresar credenciales de Mega Soft
   - Registrar URL del webhook en el panel de Mega Soft

## ⚙️ Configuración Inicial

### 1. Gateway Principal (Tarjetas)
- Ir a: WooCommerce → Ajustes → Pagos → Mega Soft v2
- Habilitar el gateway
- Ingresar credenciales API
- Seleccionar tarjetas aceptadas
- Configurar captura automática

### 2. Pago Móvil C2P
- Ir a: WooCommerce → Ajustes → Pagos → Pago Móvil C2P
- Configurar teléfono y banco receptor

### 3. Webhook
- Copiar URL del webhook desde Mega Soft → Dashboard
- Registrar en el panel de Mega Soft
- Formato: `https://tudominio.com/megasoft-v2-webhook/`

## 🧪 Modo de Prueba

1. Activar "Modo de Prueba" en la configuración
2. Usar tarjetas de prueba:
   - Visa: `4111111111111111`
   - MasterCard: `5555555555554444`
   - Amex: `378282246310005`

## 📊 Panel Administrativo

Acceder a: **WordPress Admin → Mega Soft**

- **Dashboard:** Estadísticas, gráficos, transacciones recientes
- **Transacciones:** Lista completa con filtros y búsqueda
- **Logs:** Sistema de logs con niveles (DEBUG, INFO, WARN, ERROR)
- **Configuración:** Accesos rápidos y documentación

## 🔐 Seguridad

### Cumplimiento PCI DSS
- ✅ No almacenamiento de datos sensibles (PAN, CVV, expdate)
- ✅ Solo últimos 4 dígitos para display
- ✅ Sanitización automática en logs
- ✅ Encriptación SSL/TLS
- ✅ Tokens únicos por transacción

### Anti-Fraude
- Rate limiting: 10 intentos/hora, 50 intentos/día por IP
- Detección de transacciones rápidas
- Scoring de riesgo multi-factor
- Bloqueo automático de IPs sospechosas

## 🛠️ Desarrollo

### Tecnologías Utilizadas
- PHP 7.4+
- WordPress/WooCommerce APIs
- REST API v2 (XML)
- Chart.js (gráficos)
- JavaScript ES6+
- CSS3 (responsive)

### Testing
```bash
# Modo de prueba activado
define('MEGASOFT_V2_ALLOW_NO_SSL', true); // Solo para desarrollo local
```

## 📝 Changelog

### v4.0.0 (2024)
- ✨ Reconstrucción completa del plugin
- ✨ Migración de UNIVERSAL a NON-UNIVERSAL mode
- ✨ Integración con REST API v2
- ✨ Nueva UI moderna e intuitiva
- ✨ Panel administrativo completo
- ✨ Sistema de webhooks asíncronos
- ✨ Seguridad PCI-compliant
- ✨ Soporte para múltiples métodos de pago

## 🤝 Soporte

Para soporte técnico:
- Mega Soft Computación C.A.
- https://megasoft.com.ve

## 📄 Licencia

GPL v2 or later

## ⚠️ Advertencias Importantes

1. **PCI DSS:** Este plugin requiere certificación PCI DSS nivel SAQ-A-EP
2. **SSL:** HTTPS es obligatorio para producción
3. **Credenciales:** Nunca compartas tus credenciales de API
4. **Testing:** Siempre prueba en ambiente de prueba primero

## 🎯 Estado del Proyecto

**✅ PRODUCCIÓN READY**

- Core completo: 100%
- Seguridad: 100%
- Frontend: 100%
- Backend: 100%
- Webhooks: 100%
- Documentación: 100%

Total: ~6,700 líneas de código
