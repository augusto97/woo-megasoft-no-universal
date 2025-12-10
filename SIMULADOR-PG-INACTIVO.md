# Simulador de Payment Gateway Inactivo

Guía para usar el Simulador de PG Inactivo durante la certificación MegaSoft.

## 📋 ¿Qué es el Simulador PG Inactivo?

El **Simulador de Payment Gateway Inactivo** es una funcionalidad de certificación que simula un escenario donde el servidor de MegaSoft no responde (timeout).

Esto permite a MegaSoft validar que tu plugin:
- ✅ Maneja correctamente los errores de timeout
- ✅ Muestra mensajes claros al usuario
- ✅ No deja órdenes en estados inconsistentes
- ✅ Registra el error apropiadamente en logs

---

## 🎯 Instrucciones para Certificación MegaSoft

### **1. Activar el Simulador**

1. Ve a **WooCommerce → Ajustes → Pagos → Mega Soft v2**
2. Busca la opción: **"Simulador PG Inactivo (Certificación)"**
3. ✅ **Activa** el checkbox: "Activar Simulador de Payment Gateway Inactivo"
4. Guarda los cambios

⚠️ **IMPORTANTE:** Esta opción aparecerá en la sección principal del gateway, justo después del "Modo de Prueba".

---

### **2. Realizar Compra de Prueba**

1. Ve al frontend de tu tienda (como cliente)
2. Agrega un producto al carrito
3. Procede al checkout
4. Selecciona cualquier método de pago de MegaSoft:
   - Tarjeta de Crédito/Débito
   - Pago Móvil C2P
   - Pago Móvil P2C
   - Crédito Inmediato
5. Completa el formulario con datos de prueba
6. Haz clic en **"Realizar el pedido"**

**Resultado esperado:** Verás un mensaje de error en pantalla

---

### **3. Capturar Pantalla del Error**

El mensaje que debe aparecer es:

```
Error: El Payment Gateway no responde. La operación excedió el tiempo
de espera permitido (timeout). Por favor, intente nuevamente más tarde
o contacte al comercio.
```

**Lo que debes capturar:**

📸 **Screenshot completo** mostrando:
- ✅ La página de checkout
- ✅ El mensaje de error visible
- ✅ URL en la barra del navegador
- ✅ Campos del formulario (opcional)

**Ejemplo de captura:**
```
┌─────────────────────────────────────────┐
│ tutienda.com/checkout                   │
├─────────────────────────────────────────┤
│                                         │
│  [X] Error: El Payment Gateway no       │
│      responde. La operación excedió...  │
│                                         │
│  Detalles de facturación               │
│  Método de pago: [Tarjeta]             │
│                                         │
│  [ Realizar el pedido ]                │
│                                         │
└─────────────────────────────────────────┘
```

---

### **4. Desactivar el Simulador**

⚠️ **MUY IMPORTANTE:** Una vez capturada la evidencia, **DESACTIVA** inmediatamente el simulador:

1. Ve a **WooCommerce → Ajustes → Pagos → Mega Soft v2**
2. Busca: **"Simulador PG Inactivo (Certificación)"**
3. ❌ **Desactiva** el checkbox
4. Guarda los cambios

**¿Por qué desactivar?**
- Si lo dejas activado, TODAS las transacciones fallarán
- Tus clientes no podrán completar compras reales
- Es solo para certificación, no para producción

---

## 🔍 Verificación de Logs

Para una certificación completa, también puedes capturar los logs:

1. Ve a **WooCommerce → Mega Soft → Logs**
2. Filtra por nivel: **Warning** o **Error**
3. Busca entradas con texto: **"SIMULADOR PG INACTIVO ACTIVADO"**

**Ejemplo de log esperado:**
```
[2025-12-10 10:30:45] WARNING: ⚠️ SIMULADOR PG INACTIVO ACTIVADO - Forzando error de timeout
Order ID: 1234
Payment Method: Tarjeta de Crédito
Certification Mode: true
```

---

## 📧 Enviar Evidencia a MegaSoft

Una vez completados los pasos, envía a MegaSoft:

**Email:** merchant@megasoft.com.ve

**Asunto:** Certificación Plugin WooCommerce - Simulador PG Inactivo

**Adjuntos:**
1. 📸 Screenshot del mensaje de error en checkout
2. 📄 Log mostrando la entrada "SIMULADOR PG INACTIVO ACTIVADO" (opcional)
3. ✅ Confirmación de que el simulador ha sido desactivado

**Contenido del email:**
```
Estimados,

Adjunto evidencia del Simulador de Payment Gateway Inactivo:

- Plugin: WooCommerce MegaSoft Gateway v4.0.0
- Sitio: [tu-dominio.com]
- Método probado: [Tarjeta/P2C/C2P/Crédito Inmediato]
- Fecha de prueba: [fecha]

El simulador ha sido desactivado exitosamente.

Saludos,
[Tu nombre]
```

---

## ⚠️ Preguntas Frecuentes

### ¿El simulador afecta a todos los métodos de pago?

**Sí.** Cuando está activado, afecta a:
- ✅ Tarjetas de Crédito/Débito
- ✅ Pago Móvil C2P
- ✅ Pago Móvil P2C
- ✅ Crédito Inmediato

Puedes probar con cualquiera y todos mostrarán el error de timeout.

---

### ¿Qué pasa con las órdenes creadas durante la prueba?

Las órdenes quedarán en estado **"Pending Payment"** (Pago Pendiente) o **"Failed"** (Fallida).

Puedes eliminarlas manualmente desde:
- WooCommerce → Pedidos

---

### ¿Puedo usar el simulador en producción?

**NO.** El simulador es **SOLO para certificación**.

Si lo dejas activado en producción:
- ❌ Ninguna transacción funcionará
- ❌ Perderás ventas
- ❌ Los clientes verán siempre el error

**Usa el simulador solo cuando MegaSoft te lo solicite.**

---

### ¿Cómo sé si el simulador está activado?

**Opción 1:** Revisa la configuración del gateway
- WooCommerce → Ajustes → Pagos → Mega Soft v2
- Verifica que el checkbox "Simulador PG Inactivo" esté desmarcado

**Opción 2:** Haz una compra de prueba
- Si todas las transacciones fallan con timeout, el simulador está activo

---

### ¿El simulador funciona en modo de prueba y producción?

**Sí.** El simulador funciona independientemente de si tienes activado:
- Modo de Prueba (paytest.megasoft.com.ve)
- Modo de Producción (e-payment.megasoft.com.ve)

---

## 🛠️ Troubleshooting

### El checkbox no aparece

**Causa:** Versión antigua del plugin

**Solución:**
1. Actualiza a la versión más reciente (v4.0.0+)
2. Desactiva y reactiva el plugin
3. Limpia la caché del navegador

---

### El error no muestra el mensaje esperado

**Causa:** Hay un error real de conexión (no es el simulador)

**Solución:**
1. Verifica que el simulador esté ACTIVO en settings
2. Revisa los logs: debe decir "SIMULADOR PG INACTIVO ACTIVADO"
3. Si no dice eso, el error es real de tu servidor/conexión

---

### No puedo desactivar el simulador

**Solución rápida vía base de datos:**

```sql
UPDATE wp_options
SET option_value = REPLACE(option_value, '"simulate_inactive_pg";s:3:"yes"', '"simulate_inactive_pg";s:2:"no"')
WHERE option_name = 'woocommerce_megasoft_v2_settings';
```

⚠️ **Cuidado:** Solo usa esto si no puedes acceder al admin de WordPress.

---

## 📚 Referencias

- **Plugin:** MegaSoft Gateway v4.0.0
- **Documentación API:** MAET-PAYM-00_JUL_2025.md
- **Soporte MegaSoft:** merchant@megasoft.com.ve

---

## ✅ Checklist de Certificación

Antes de enviar a MegaSoft, verifica:

- [ ] Simulador activado en settings
- [ ] Compra de prueba realizada
- [ ] Screenshot del error capturado
- [ ] Screenshot muestra URL y mensaje completo
- [ ] Log del simulador capturado (opcional)
- [ ] **Simulador desactivado después de la prueba**
- [ ] Email enviado a MegaSoft con evidencia

---

**Última actualización:** 2025-12-10
**Versión del plugin:** 4.0.0
**Estado:** ✅ Simulador PG Inactivo implementado correctamente
