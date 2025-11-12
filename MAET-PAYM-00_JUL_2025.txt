Manual de Especificaciones Técnicas del Payment Gateway
V4.24.
Wilfred Suárez
Tabla de Contenido
Manual de Especificaciones Técnicas del Payment Gateway
Propósito
Audiencia
Marco Legal
Historial de Cambios
Introducción
Especificación de mensajes: HTTP y XML
Notación
Representación General
Atributos de Longitud
Especificación de parámetros o tags
Parámetros en Request HTTP
Tags en Respuesta XML
Requerimientos REST Versión 2
PreRegistro
QueryStatus
Compra con Tarjeta de Crédito
Pago Móvil (C2P)
Pago Móvil (P2C)
Criptomonedas (Obtener monedas disponibles)
Criptomonedas (Solicitud)
Criptomonedas (Confirmación)
Banplus Pay (Solicitud)
Banplus Pay (Confirmación)
Zelle
Débito Inmediato (Solicitud)
Débito Inmediato (Confirmación)
Crédito Inmediato
Depósito
C@mbio Pago Móvil
C@mbio Privado
Preautorización
Completitud
Cierre
Anulación
Anulación – Banplus Pay
Anulación – Pago Móvil C2P
Test Merchant
Recepción de Llave para Desencriptación
Requerimientos SOAP Versión 2
PreRegistro de Transacción
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 1/176

QueryStatus de una Transacción
Transacción con Tarjeta de Crédito
Transacción con Tarjeta de Crédito
Transacción de Pago Móvil C2P
Transacción de Pago Móvil P2C
Transacción de Criptomonedas - Logon
Transacción de Criptomonedas - Solicitud
Transacción de Criptomonedas - Confirmación
Transacción de Banplus Pay - Solicitud
Transacción de Banplus Pay - Confirmación
Transacción de Zelle
Transacción de Crédito Inmediato
Transacción de Débito Inmediato (Solicitud)
Transacción de Débito Inmediato (Confirmación)
Transacción de Depósito
Transacción de C@mbio Pago Móvil
Transacción de C@mbio Privado
Cierre
Transacción de Anulación
Transacción de Anulación – Banplus Pay
Transacción de Anulación – Pago Móvil C2P
Recepción de Llave para Desencriptación
Recomendaciones para la Implementación del Payment Gateway Modo No Universal (URL)
Configuraciones como requisitos importantes para las Certificaciones de Botón de Pago y Vouchers
Métodos de Identificación del Usuario
Métodos de registro de Cuenta de Usuario en el Site
Enmascaramiento de Datos de Entrada como medida de seguridad
En el Código de Seguridad de la Tarjeta (CVV2)
Fecha de Vencimiento
Vouchers en el Site
Certificación y Manejo de Datos Sensibles
Manual de Especificaciones Técnicas del Payment Gateway
Esta publicación fue producida por Mega Soft Computación C.A. Caracas, Venezuela.
merchant@megasoft.com.ve
Copyright ©2025 by Mega Soft Computación - RIF. J-00343075-7. Todos los derechos reservados.
La información contenida en este manual es propiedad exclusiva de Mega Soft Computación C.A. Por ello el usuario
final de este documento es responsable de:
Mantener la confidencialidad de su contenido y de los datos indicados en el mismo.
Proteger la información contenida en el citado documento, a los fines de evitar su copia por cualquier medio
impreso y/o electrónico.
Este documento, es entregado al usuario final bajo licencias de uso. Esta información sólo puede ser usada y divulgada
de acuerdo a los términos y condiciones indicados en las referidas licencias.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 2/176

Mega Soft Computación C.A, se reserva el derecho de modificar el contenido de este Manual de Usuario, sin previo
aviso.
Propósito
Este documento provee las especificaciones técnicas de la aplicación Payment Gateway, que permiten su integración
con una aplicación Web, como un carrito de compras, para el procesamiento de transacciones financieras.
Este manual incluye la siguiente información:
Definición de requerimientos HTTP y respuestas XML empleadas para el intercambio de mensajes entre la
aplicación cliente y el Payment Gateway.
Definición de cada uno de los campos que componen los requerimientos y respuestas.
Ejemplos de diferentes casos.
Especificación de archivo plano de conciliación.
Audiencia
Esta guía está dirigida a desarrolladores de sistemas o personal técnico, con conocimientos en herramientas de
Internet, mensajería o terminología financiera básica y XML.
Marco Legal
Payment Gateway® es un nombre propiedad de Mega Soft Computación C.A.
Historial de Cambios
Información del Documento
Fecha: Responsable(s): Código: Versión PG: Versión VPos:
09/05/2025 Javier González / Wilfred Suárez. MAET-PAYM-00_JUL.2025. 4.24. 3.15.3.
Requerimientos/Incidencias:
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 3/176

Información del Documento
Cambios en esta versión de la aplicación:
1. Nuevo: Se implementa la verificación 3D Secure en la Modalidad Universal.
2. Nuevo: Se añade prefijo telefónico "0422" como código de operadora en todas las modalidades del servicio.
3. Nuevo: Se incorpora la letra "C" como nuevo tipo de identificación permitido en todas las modalidades del
servicio.
4. Nuevo: Se agrega el campo "MontoDivisa" en QueryStatus para transacciones con tarjetas internacionales.
5. Nuevo: Se añade el campo "cédula" en petición de la llamada "Verificación Tarjeta".
6. Nuevo: Se actualiza versión del Tomcat a la v9.0.104.
7. Nuevo: Se actualiza versión de Vpos a la v3.15.3.
8. Nuevo: Se agregan nuevos payments "P-VzlaDI", "P-BVCUbii", "P-MercantilDI", "P-BancaribeDI", "P-BancamigaDI" y
"P-BanplusDI".
9. Ajuste: Se corrige error en Modalidad Universal al utilizar Débito Inmediato o Crédito Inmediato y al obtener
timeout, no se actualizaba la transacción correctamente.
Cambios en esta versión de la documentación:
• No hubo cambios en esta versión del documento.
Introducción
La aplicación Payment Gateway proporciona una interfaz diseñada para clientes remotos que se comunican usando el
protocolo HTTP o HTTPS, que ejecutan Server Side en un Web Server, como páginas ASP, JSP, PHP, etc.
El Payment Gateway está basado en el concepto de REST, es decir, servicios Web que retornan XML en respuesta a una
solicitud HTTP GET o POST. El integrador debe codificar los parámetros del comando GET o POST, de acuerdo a la
especificación HTTP, con el fin de procesar transacciones financieras entre el comercio y el servidor Merchant Server
de Mega Soft Computación C.A.
El Payment Gateway también tiene incorporado servicios SOAP, el cual es un protocolo estándar que define cómo dos
objetos en diferentes procesos pueden comunicarse por medio de intercambio de datos XML, el integrador debe tomar
los WSDL de los servicios disponibles y crear el cliente correspondiente para procesar transacciones financieras entre
el comercio y el servicio Merchant Server de Mega Soft Computación C.A.
El siguiente diagrama muestra la arquitectura de la aplicación.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 4/176

Especificación de mensajes: HTTP y XML
En esta sección se describen los aspectos vinculados a los Requerimientos o Respuestas utilizados por la aplicación
Payment Gateway en modalidad HTTP y XML.
Notación
Explicación de la notación empleada para definir los atributos y formato de los campos.
Representación General
Notación Interpretación
n Cadena de caracteres numéricos.
am Cadena de caracteres que representa un monto de dos decimales y no contiene separadores de miles y
decimales.
d Fecha en formato AAAAMMDD.
t Hora en formato hhmmss.
an Cadena de caracteres alfanuméricos.
Atributos de Longitud
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 5/176

Notación Interpretación
-dígito(s) Longitud fija en el número de posiciones indicada.
Ejemplos:
n-3: indica un campo numérico de 3 posiciones.
an-5: indica un campo alfanumérico de 5 posiciones.
..dígito(s) Longitud variable con un número máximo de posiciones especificado.
Ejemplo:
n..3: indica un campo numérico de máximo 3 posiciones.
Especificación de parámetros o tags
Descripción detallada de cada campo que compone tanto el requerimiento HTTP como la respuesta XML
Parámetros en Request HTTP
Nombre Tipo Descripción Comentario
account n-20 Número de cuenta. N/A.
amount n..12 Monto de la transacción. Este campo solo puede estar formateado
de las siguientes formas:
Crudo: representación 10+2 del monto
donde los 10 primeros representan los
enteros y los 2 últimos los decimales,
Ej: 123456 representa 1.234,56.
Decimal: representación 10+2 del
monto donde los 10 primeros
representan los enteros,
posteriormente seguido por un
carácter separador decimal, el cual
puede ser “.” O “,” y por último los 2
dígitos decimales, Ej: 1234.56 o 1234,56
representan 1.234,56.
authid an..6 Código de aprobación de la
transacción a completar.
N/A.
checknum n..10 Número de cheque. N/A.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 6/176

Nombre Tipo Descripción Comentario
cid an..14 Identificación del cliente. El primer carácter, de izquierda a
derecha, corresponde al tipo de persona,
de acuerdo a los siguientes valores:
V: Venezolano.
J: Jurídico.
E: Extranjero.
G: Gubernamental.
P: Pasaporte.
Los caracteres restantes, corresponden al
número de identificación del cliente,
cédula o pasaporte.
Ejemplo: V6891254.
client an..20 Nombre del tarjetahabiente. N/A.
cod_afiliacion an..20 Código de afiliación del comercio. El uso de este parámetro aplica a partir de
la versión 2.0.0.
cvv2 n..4 Valor de verificación. (Card
Verification Value 2)
Está ubicado al reverso de las tarjetas de
crédito. Si la tarjeta no posee estos tres (3)
dígitos debe ser colocado el valor 000 en
el Request.
En transacciones de completitud este
valor es opcional. En caso de ser enviado
será validado con los datos de la
Preautorización correspondiente.
telefono n-11 Número de teléfono del cliente. Los primeros cuatro (4) dígitos
representan la operadora y los siguientes
7 el número del cliente. Ejemplo:
04241234567.
codigobanco n-4 Número de identificación del
banco.
Número de cuatro (4) dígitos que
identifica la entidad bancaria donde el
cliente posee la cuenta. Ejemplo: 0102.
codigoc2p n-8 Código o clave Pago Móvil. Código de ocho (8) dígitos que le
proporciona el banco al cliente.
telefonoCliente n-11 Número de teléfono del cliente. Número de once (11) dígitos que
proporciona el cliente.
telefonoComercio n-11 Número de teléfono del comercio. Número de once (11) dígitos que tiene
registrado el comercio en la afiliación.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 7/176

Nombre Tipo Descripción Comentario
codigobancoCliente n-4 Código del banco del cliente. Código de cuatro (4) dígitos que
representa el banco del cliente.
codigobancoComercio an-4 Código del banco del comercio. Código de cuatro (4) dígitos que tiene
registrado el comercio en la afiliación.
Bancos Internacionales (solo Zelle):
BOFA - Bank of America
NFBK - Capital One
CHAS - Chase Bank
CITI - Citibank
FTBC - Firts Third Bank
MRMD - HSBC
PNCC - PNC Bank
WFBI - Wells Fargo Bank
tipo_moneda a..4 /
n..4
Identificación del tipo de
criptomoneda / Moneda Fiat.
Código de la criptomoneda a utilizar:
BTC (BitCoin), DASH (Dash), ETH
(Ethereum).
Código moneda Fiat a utilizar. Ejemplo:
0 (Bs), 840 (Dólares), 978 (Euros).
Nota: Banplus Pay solo acepta código "0"
(Bs).
tipo_cuenta n..4 Identificación del tipo de cuenta
fiat./ tipo de bolsillos
Código cuenta Fiat a utilizar. Ejemplo:
Monedas Fiat:
10 (Bolívares).
40 (Dolares).
90 (Euros).
Banplus Pay:
900 (Banplus Pay Bolívares).
720 (Banplus Pay Dólar).
563 (Vuelto Dólar).
654 (GiftCard Dólar).
652 (Vale Dólar).
700 (Banplus Pay Euro).
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 8/176

Nombre Tipo Descripción Comentario
tipoPago n..4 Tipo de moneda con el que el
cliente realizó el pago
Tipos de pago a utilizar. Ejemplo:
Monedas Fiat:
10 (Bolívares).
40 (Dolares).
90 (Euros).
cod_otp n..12 Código OTP entregado por el banco
del cliente.
Ejemplo: 165432
expdate n..4 Fecha de expiración de la tarjeta en
formato MMAA.
Ejemplo: 1007, representa el valor
Octubre de 2007.
factura an..20 Número de factura. Este campo es opcional y en caso de no
ser especificado, será asignado de forma
automática un consecutivo por afiliación.
field<n> an..30 Corresponde a diez (10) campos
opcionales que puede utilizar cada
cliente para registrar datos
relativos a su tipo de negocio. Estos
campos están identificados desde el
campo field1 hasta el campo
field10, es decir, n entre 1 y 10.
Ejemplos:
Comercio 1
field1 -› planilla
field2 -› estado
field3 -› municipio
Comercio 2
field1 -› modelo
field2 -› talla
mode n-1 Campo opcional que especifica el
modo en que se captura la
transacción.
El uso de este parámetro aplica a partir de
la versión 2.0.3, en transacciones de
Compra Crédito, Preautorización y
Completitud.
Cuando en los Requerimientos HTTP no
se indica el mode, el Payment Gateway
coloca mode=4 por defecto.
Valores:
2 = Manual Online.
4 = Manual Online Internet (por
defecto).
pan n..19 Número de tarjeta. N/A.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 9/176

Nombre Tipo Descripción Comentario
plan n-2 Plan de crédito solicitado por el
cliente.
Valores: 00: Rotativo, 02: 3 meses, 04 al 35:
Cuotas fijas, 40: Meses de gracia.
signatures n-1 Número de firmas autorizadas
sobre la cuenta.
Valores: 1 = Una firma, 2 = Dos o más
firmas.
transcode n-4 Código que identifica el tipo de
transacción.
Campo válido sólo para versiones previas
a 2.0.0.
Valores:
0141: Compra tarjeta de crédito.
0131: Conformación de cheques.
0135: Preautorización.
cuentaOrigen n-20 Número de cuenta bancaria del
cliente.
Número cuenta de veinte (20) dígitos que
proporciona el cliente desde donde
realizará la transferencia.
cuentaDestino n-20 Número de cuenta del comercio. Número de cuenta de veinte (20) dígitos
que tiene registrado el comercio en la
afiliación.
vtid n..20 Identificador de terminal virtual. Campo válido sólo para versiones previas
a 2.0.0.
terminal an..20 Número de terminal utilizado para
procesar la transacción.
N/A.
referencia n..12 Número de referencia. N/A.
ult n..4 últimos 4 dígitos de la tarjeta de
crédito.
N/A.
security_key an..256 Llave secreta para la
desencriptación de datos.
Es la llave usada en el servicio de compra
para desencriptar la data que viene
encriptada. Solo se aplica si el cliente
tiene asociada la opción de
desencriptamiento de datos.
Tags en Respuesta XML
Nombre Tipo Descripción Comentario
afiliacion an..15 Código de afiliación del comercio. N/A.
authid an..6 Código de autorización o aprobación de
la transacción.
N/A.
authname an..40 Nombre del Payment adquiriente que
procesó la transacción.
Ejemplo: P-Banesco.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 10/176

Nombre Tipo Descripción Comentario
codigo n-2 Código de respuesta enviado por el
Sistema.
N/A.
descripcion an..100 Texto asociado al código de respuesta
enviado por el banco, el Merchant
Server o generado por el Payment
Gateway.
Ejemplos:
Aprobada.
Tarjeta inválida.
Saldo insuficiente.
Parámetros inválidos.
factura an..20 Número de factura. N/A.
linea an..50 Este valor está contenido en el tag
"voucher". Cada campo de este tipo
corresponde a una línea en el recibo de
la transacción, enviado por el Merchant
Server.
Todos los caracteres "_" que se encuentren
dentro de estos tags deben ser sustituidos por
espacios en blanco.
Aplica a partir de la versión 2.0.3. Estará
presente sólo si es habilitada la opción
“Enviar Voucher en Respuesta XML”, a través
del Módulo administrativo.
lote n..10 Número de lote. N/A.
rifbanco an..15 RIF del banco adquiriente que procesó
la transacción.
N/A.
seqnum n..9 Número de secuencia de la transacción
procesada.
N/A.
tarjeta n..19 Número de la tarjeta empleada para
realizar la compra.
El valor estará enmascarado con asteriscos,
mostrando sólo los primeros 6 y los 4 últimos
dígitos de la misma.
Ejemplo: 454520* * * * * * * *8992.
cuenta n20 Número de la cuenta empleada para
realizar la transferencia para la
compra.
El valor estará enmascarado con asteriscos,
mostrando sólo los primeros 6 y los 4 últimos
dígitos de la misma.
Ejemplo: 010220* * * * * * * *8992.
terminal an..20 Número de terminal utilizado para
procesar la transacción.
N/A.
tipo an..20 Tipo de producto. Ejemplo: MasterCard.
vtid an..20 Identificación de terminal virtual a
través del cual se procesó la
transacción.
N/A.
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 11/176

Nombre Tipo Descripción Comentario
voucher - Representa la información detallada
del voucher de la transacción.
Aplica a partir de la versión 2.0.3. Contendrá
información sólo si es habilitada la opción
“Enviar Voucher en Respuesta XML”, a través
del Módulo administrativo.
lista_codigos - Representa la lista de códigos de
Criptomonedas.
Ejemplo: BTC,DASH,ETH
lista_nombres - Representa la lista de nombres de
Criptomonedas.
Ejemplo: Bitcoin,Dash,Ethereum
monto_crypto n..12 Representa el monto al valor de la
criptomoneda.
Ejemplo: 0.012345
tipomoneda an..6 Representa el código de la
criptomoneda.
Ejemplo: BTC
qrurl an..100 Representa la URL del código QR. La URL recibida proporciona directamente la
imagen del código QR.
referencia an..50 Representa un código de guía de la
transacción.
N/A.
monto_divisa an..15 Representa el monto en divisas de la
transacción.
N/A.
moneda_pago an..20 Representa el nombre de la moneda
utilizada para realizar el pago
Ejemplo: Bolívares, Dolares, Euros.
Requerimientos REST Versión 2
Debido a la necesidad de saber que ocurrió con una transacción, si se interrumpió el proceso por alguna falla o pérdida
de conexión, se decidió realizar un flujo similar al del ‘Botón de Pago’. En donde, se debe ‘PreRegistrar’ la transacción
antes de poder ser ejecutada. Esto permite llevar un registro de ella desde el inicio y poder consultarla en cualquier
momento luego de realizarla.
Debe tener en cuenta que para realizar alguna llamada a uno de estos servicios debe tener en la cabecera de la petición
el siguiente parámetro:
Autenticación
Cabecera:
Authorization Basic
Comentarios:
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 12/176

Autenticación
Se debe enviar Authorization Basic seguido del usuario, dos puntos (:) y la contraseña proporcionada encriptado
todo en Base64.
Ejemplo: Al encriptar “Usuario:Contraseña” (puede utilizar la página ‘https://www.base64encode.org’), da como
resultado: “VXN1YXJpbzpDb250cmFzZcOxYQ==”.
Quedando el header de la siguiente manera:
“Authorization: Basic VXN1YXJpbzpDb250cmFzZcOxYQ==”
Para poder utilizar cualquier servicio de la versión 2 Https REST, primero debe generar un número de control:
PreRegistro
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
PreRegistro
URL:
https://<ip>/payment/action/v2-preregistro
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Exitosa:
Respuesta Fallida
QueryStatus
<request>
<cod_afiliacion>12345678</cod_afiliacion>
</request>
XML
<response>
<codigo>00</codigo>
<descripcion>PREREGISTRADO</descripcion>
<control>1583176057917189347</control>
</response>
XML
<response>
<codigo>99</codigo>
<descripcion>No se recibió el código de afiliación del comercio</descripcion>
<control></control>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 13/176

Esta llamada permite saber el estado de la transacción en cualquier momento luego de haber realizado un
‘PreRegistro’.
QueryStatus Version 2.3
Este Querystatus ofrece las mismas ventajas que la versión 2, sólo que se agregaron los campos: “monedaInicio”,
“monedaFin” y “montoDivisa” en la respuesta. Esto permite obtener la información de las transacciones que presentan
conversión monetaria.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
QueryStatus V2.3
Url:
https://<ip>/payment/action/v2-querystatus
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Parametros
Nombre Tipo Descripción
cod_afilicion n..10 Código de afiliación otorgado por MegaSoft al generar credenciales de autenticación.
control n..19 Número de control generado por la petición “PreRegistro”
version n-1 Número de versión del QueryStatus.
1: (DEPRECATED), se utiliza para implementaciones Antiguas.
2: Tiene la capacidad de devolver información específica dependiendo del tipo de
transacción que se solicite.
3: Tiene la capacidad de devolver información específica dependiendo del tipo de
transacción que se solicite y datos multimoneda.
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<version>3</version>
<tipotrx>CREDITO</tipotrx>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 14/176

QueryStatus V2.3
Tipotrx a..20 Tipo de transacción a consultar, posibles consultas:
CREDITO: Tarjetas de crédito
DEBITO: Tarjetas de débito
TARJETA: Tarjetas de crédito o débito
C2P: Pago Móvil Comercio a Persona.
P2C: Pago Móvil Persona a Comercio.
CRYPTO: Solicitud de pago con Criptomonedas.
CRYPTO_CONFIR: Confirmación de pago con Criptomonedas.
BANPLUSP: Solicitud de pago con Banplus Pay.
BANPLUSP_CONFIR: Confirmación de pago con Banplus Pay.
ZELLE: Verificación de pago Zelle.
C@MBIO_PAGOMOVIL: Vuelto realizado a través de Pago Móvil.
C@MBIO_PRIVADO: Vuelto realizado a través de los bancos.
CREDITO_INMEDIATO: Transferencias entre cuentas bancarias.
DEBITO_INM: Solicitud de pago con Débito Inmediato.
DEBITO_INM_CONFIR: Confirmación de pago con Débito Inmediato.
DEPOSITO: Depósito a una cuenta bancaria.
ANULACION: Anulaciones de transacciones de Crédito, C2P y Banplus Pay.
Respuesta Aprobada - Crédito
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 15/176

QueryStatus V2.3
Respuesta Aprobada - Débito
<response>
<control>1639415373749215202</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>3866</factura>
<monto>11,99</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>bancamiga</vtid>
<seqnum>1029</seqnum>
<authid>460765</authid>
<authname>P-EComBancamigaIntl</authname>
<tarjeta>530072 * * * * * * 7519</tarjeta>
<referencia>000011</referencia>
<terminal>18201001</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>0010000017</afiliacion>
<marca>MasterCard</marca>
<tipotrx>CREDITO</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>840</monedaFin>
<montoDivisa>3,00</montoDivisa>
<voucher>
BANCAMIGA
COMPRA: CREDITO
MERCHANT BANCAMIGA MONTALBAN 3
RIF:J123456789 F:13/12/2021 H:13:09:43
AFIL:0010000017 TERM:18201001 LOT:1
530072 * * * * * * 7519
TRC:11 REF:000011 APR:460765
MONTO BS.:11,99
MONTO USD: 3,00
FIRMA:___
CAJA:BANCAMIGA SECUENCIA:1029
ME OBLIGO A PAGAR AL BANCO EMISOR
DE ESTA TARJETA EL MONTO DE ESTA
NOTA DE CONSUMO
<UT> DUPLICADO </UT>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 16/176

QueryStatus V2.3
Respuesta Aprobada – Pago Móvil C2P
<response>
<control>1639415373749215202</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>3866</factura>
<monto>11,99</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>bancamiga</vtid>
<seqnum>1029</seqnum>
<authid>460765</authid>
<authname>P-EComBancamigaIntl</authname>
<tarjeta>530072 * * * * * * 7519</tarjeta>
<referencia>000011</referencia>
<terminal>18201001</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>0010000017</afiliacion>
<marca>MasterCard</marca>
<tipotrx>DEBITO</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>840</monedaFin>
<montoDivisa>3,00</montoDivisa>
<voucher>
BANCAMIGA
COMPRA: DEBITO
MERCHANT BANCAMIGA MONTALBAN 3
RIF:J123456789 F:13/12/2021 H:13:09:43
AFIL:0010000017 TERM:18201001 LOT:1
530072 * * * * * * 7519
TRC:11 REF:000011 APR:460765
MONTO BS.:11,99
MONTO USD: 3,00
FIRMA:___
CAJA:BANCAMIGA SECUENCIA:1029
ME OBLIGO A PAGAR AL BANCO EMISOR
DE ESTA TARJETA EL MONTO DE ESTA
NOTA DE CONSUMO
<UT> DUPLICADO </UT>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 17/176

QueryStatus V2.3
Respuesta Aprobada – Pago Móvil P2C
<response>
<control>1639410150218215132</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>3859</factura>
<monto>12.345.678,90</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>871</seqnum>
<authid>86567</authid>
<authname>P-BancoPlazaC2P</authname>
<tarjeta></tarjeta>
<referencia>4459749</referencia>
<terminal>32132112</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>123456711</afiliacion>
<telefonoEmisor>0424 * * * 6002</telefonoEmisor>
<bancoEmisor>0134</bancoEmisor>
<bancoAdquiriente>0138</bancoAdquiriente>
<tipotrx>C2P</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>928</monedaFin>
<montoDivisa>0,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
PAGO MOVIL
C2P
CARACAS
RIF:J138249197 AFIL:123456711
TER:32132112 LOTE:1 REF:4459749
NRO.TELEFONO:04242766002
FECHA:13/12/2021 11:42:43
SECUENCIA:871 CAJA:VPOS
MONTO BS. :12.345.678,90
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 18/176

QueryStatus V2.3
Respuesta Aprobada – Criptomonedas
<response>
<control>1639413977498215187</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>3864</factura>
<monto>4.000.000,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>c2phj</vtid>
<seqnum>129</seqnum>
<authid>00404963</authid>
<authname>P-BancoPlazaP2C</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>99911134</terminal>
<lote>34</lote>
<rifbanco></rifbanco>
<afiliacion>876543331</afiliacion>
<telefonoEmisor>0412 * * * 0188</telefonoEmisor>
<bancoEmisor>0138</bancoEmisor>
<telefonoAdquiriente>0412 * * * 3333</telefonoAdquiriente>
<bancoAdquiriente>0138</bancoAdquiriente>
<tipotrx>P2C</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>928</monedaFin>
<montoDivisa>0,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
PAGO MOVIL
C2P
CARACAS
RIF:J138249197 AFIL:876543331
TER:99911134 LOTE:34 REF:6450504
NRO.TELEFONO:04120300188
FECHA:13/12/2021 12:56:08
SECUENCIA:129 CAJA:C2PHJ
MONTO BS. :4.000.000,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 19/176

QueryStatus V2.3
Respuesta Aprobada – BanPlus Pay
<response>
<control>1643826006598226642</control>
<cod_afiliacion>20420202306</cod_afiliacion>
<factura>1234</factura>
<monto>80,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>multiban07</vtid>
<seqnum>2093</seqnum>
<authid></authid>
<authname>P-Cryptobuyer</authname>
<tarjeta></tarjeta>
<referencia>156459a0-d1c8-4502-a614-fc20dc9abe41</referencia>
<terminal>00000002</terminal>
<lote>37</lote>
<rifbanco>J09876543310</rifbanco>
<afiliacion>67512300</afiliacion>
<monto_crypto>21.00000000</monto_crypto>
<nombre_moneda>Tether USD (TRC-20)</nombre_moneda>
<tipomoneda>TRX_USDT</tipomoneda>
<qrurl>
<![CDATA[https://chart.googleapis.com/chart?
chs=250x250&cht=qr&chl=tron:TVGNFiSnbEnyUVyrAUFy67L28kuoReaavZ?amount=21.00]]>
</qrurl>
<tipotrx>CRYPTO_CONFIR</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>840</monedaFin>
<montoDivisa>20,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
CRYPTOBUYER
PAGO CRIPTOMONEDA
PRUEBAS MULTIBANCO
LA CANDELARIA
RIF:J-003430757
TER:00000002 AFIL:67512300
FECHA:02/02/2022 14:21:18
ID: FC20DC9ABE41
SECUENCIA:2093 CAJA:MULTIBAN07
MONTO BS. :80,00
MONTO FIAT: 20,00 USD
MONTO CRIPTO:21.0 USDT
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 20/176

QueryStatus V2.3
Respuesta Aprobada – Zelle
<response>
<control>1643825167654226587</control>
<cod_afiliacion>20420202306</cod_afiliacion>
<factura>337</factura>
<monto>40,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>multiban07</vtid>
<seqnum>2083</seqnum>
<authid>7938</authid>
<authname>P-BanplusPresidents</authname>
<tarjeta></tarjeta>
<referencia>7938</referencia>
<terminal>13400002</terminal>
<lote>29</lote>
<rifbanco></rifbanco>
<afiliacion>00000000000003</afiliacion>
<nombre_cuenta>Banplus Pay Dolar</nombre_cuenta>
<tipo_cuenta>720</tipo_cuenta>
<tipotrx>BANPLUSP_CONFIR</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>840</monedaFin>
<montoDivisa>10,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
BANPLUS
VENTA BANPLUS PAY
PRUEBAS MULTIBANCO
LA CANDELARIA
RIF:J-003430757
TER:13400002 AFIL:00000000000003
FECHA:02/02/2022 14:06:20
REFERENCIA: 7938
SECUENCIA:2083 CAJA:MULTIBAN07
MONTO BS: 40,00
MONTO USD: 10,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 21/176

QueryStatus V2.3
Respuesta Aprobada – C@mbio Pago Móvil
<response>
<control>1650604074837217491</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1184</factura>
<monto>4.000.000,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>1273</seqnum>
<authid></authid>
<authname>P-Zelle</authname>
<tarjeta></tarjeta>
<referencia>1s3t5g7b9o0q</referencia>
<terminal>99600014</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>888941</afiliacion>
<bancoAdquiriente>BOFA</bancoAdquiriente>
<tipotrx>ZELLE</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>840</monedaFin>
<montoDivisa>1.000.000,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
VERIFICACION PAGO ZELLE
PROCESA CREDICARD2
CARACAS
RIF:J138249197 AFIL:888941
TER:99600014 LOTE:1
REF:1S3T5G7B9O0Q
BANCO COMERCIO:BOFA
FECHA:22/04/2022 01:08:10
SECUENCIA:1273 CAJA:VPOS
MONTO BS.:4.000.000,00
MONTO USD: 1.000.000,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 22/176

QueryStatus V2.3
Respuesta Aprobada – C@mbio Privado
<response>
<control>1665088234708223599</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1234</factura>
<monto>5,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>JGDESA01</vtid>
<seqnum>1084</seqnum>
<authid></authid>
<authname>P-Sunacrip</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>MBPTRO01</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>77512311</afiliacion>
<telefonoEmisor>0414*2947</telefonoEmisor>
<tipotrx>PETROQPON_CONFIR</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>840</monedaFin>
<montoDivisa>1,25</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
SUNACRIP
PAGO PETRO CUPON
PRUEBAS MULTIBANCO
LA CANDELARIA
RIF:J-003430757
TER:MBPTRO01 AFIL:77512311
FECHA:06/10/2022 16:30:51
ID: 627BDD072862EB7641044FCF
SECUENCIA:1084 CAJA:JGDESA01
MONTO BS. :5,00
MONTO PETRO CUPON:1,25 USD
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 23/176

QueryStatus V2.3
Respuesta Aprobada – Crédito Inmediato
<response>
<control>1665088670298223617</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1458</factura>
<monto>20,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>JGDESA01</vtid>
<seqnum>1086</seqnum>
<authid>8881</authid>
<authname>P-BanplusPresidents</authname>
<tarjeta></tarjeta>
<referencia>419055</referencia>
<terminal>13400002</terminal>
<lote>137</lote>
<rifbanco></rifbanco>
<afiliacion>00000000000003</afiliacion>
<nombre_moneda>Bs</nombre_moneda>
<nombre_cuenta>Bolívares</nombre_cuenta>
<bancoEmisor>0174</bancoEmisor>
<telefonoAdquiriente>0424---6002</telefonoAdquiriente>
<bancoAdquiriente>0174</bancoAdquiriente>
<tipotrx>C@mbio_Privado</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>928</monedaFin>
<montoDivisa>0,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
BANPLUS
C@MBIO DIVISAS
PRUEBAS MULTIBANCO
LA CANDELARIA
RIF:J-003430757
TER:13400002 AFIL:00000000000003
FECHA:06/10/2022 16:37:59
REFERENCIA: 419055
SECUENCIA:1086 CAJA:JGDESA01
MONTO BS: 20,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 24/176

QueryStatus V2.3
Respuesta Aprobada – Depósito
<response>
<control>1669728941309232417</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1452</factura>
<monto>200,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>68</seqnum>
<authid></authid>
<authname>P-MercantilP2C</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>2874900</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>787789</afiliacion>
<cuentacliente>365412</cuentacliente>
<telefonocliente>0414---4567</telefonocliente>
<bancocliente>0138</bancocliente>
<cuentacomercio>010512--------4565</cuentacomercio>
<tipotrx>CREDITO_INMEDIATO</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>928</monedaFin>
<montoDivisa>0,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
MERCANTIL P2C
VERIFICACION TRANSFERENCIA
PROCESA CREDICARD2
CARACAS
RIF:J003430757 AFIL:787789
TER:2874900 LOTE:1
REF:273
CUENTA:
FECHA:29/11/2022 09:35:50
SECUENCIA:68 CAJA:VPOS
MONTO BS.:200,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 25/176

QueryStatus V2.3
Respuesta Aprobada – Débito Inmediato
<response>
<control>1669729628928232465</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1457</factura>
<monto>300,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>73</seqnum>
<authid></authid>
<authname>P-MercantilP2C</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>2874900</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>787789</afiliacion>
<cuentacomercio>0105124565</cuentacomercio>
<numdeposito>123474</numdeposito>
<tipotrx>DEPOSITO</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>928</monedaFin>
<montoDivisa>0,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
MERCANTIL P2C
VERIFICACION DEPOSITO
PROCESA CREDICARD2
CARACAS
RIF:J003430757 AFIL:787789
TER:2874900 LOTE:1
REF:107
CUENTA:0102152145
FECHA:15/11/2022 16:56:45
SECUENCIA:19 CAJA:VPOS
MONTO BS.:300,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 26/176

QueryStatus V2.3
Respuesta Aprobada – Anulación Pago Móvil C2P
<response>
<control>1735958566485513752</control>
<cod_afiliacion>202321090520</cod_afiliacion>
<factura>9057</factura>
<monto>400,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>30064</seqnum>
<authid></authid>
<authname>P-MiBancoDI</authname>
<tarjeta></tarjeta>
<referencia>123456</referencia>
<terminal>9366699</terminal>
<lote>33</lote>
<rifbanco></rifbanco>
<afiliacion>1919289111</afiliacion>
<telefonoEmisor>0412*8321</telefonoEmisor>
*<cuentaCliente>013812***4567</cuentaCliente>
<tipotrx>DEBITO_INM_CONFIR</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
MI BANCO
DEBITO INMEDIATO
UNICASA MONTALBAN
CARACAS
RIF:J-003430757 TER:9366699
LOTE:33 REF:123456
CELULAR: 4129568321
FECHA:03/01/2025 22:45:17
SECUENCIA:30064 CAJA:VPOS
CEDULA: 6457425
MONTO BS. :400,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 27/176

QueryStatus V2.3
QueryStatus Version 2
Esta versión de QueryStatus permite mostrar datos específicos dependiendo del tipo de transacción que sea consultada,
eso con el fin de permitir un mejor control a las integraciones a nivel contable y estadístico.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
QueryStatus
URL:
https://<ip>/payment/action/v2-querystatus
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
<response>
<control>1668715983435225655</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1693</factura>
<monto>50,00</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>20102</seqnum>
<authid>56801</authid>
<authname>P-BancoPlazaC2P</authname>
<tarjeta></tarjeta>
<referencia>9700942</referencia>
<terminal>32132112</terminal>
<lote>2</lote>
<rifbanco></rifbanco>
<afiliacion>123456711</afiliacion>
<tipotrx>ANULACION</tipotrx>
<monedaInicio>928</monedaInicio>
<monedaFin>928</monedaFin>
<montoDivisa>0,00</montoDivisa>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
ANULACION PAGO MOVIL
PROCESA CREDICARD2
CARACAS
RIF:J003430757 AFIL:123456711
TER:32132112 LOTE:2 REF:9700942
NRO.TELEFONO:04241234567
FECHA:17/11/2022 16:13:08
SECUENCIA:20102 CAJA:VPOS
MONTO BS. :-50,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 28/176

QueryStatus
Parametros
Nombre Tipo Descripción
cod_afilicion n..10 Código de afiliación otorgado por MegaSoft al generar credenciales de autenticación.
control n..19 Número de control generado por la petición “PreRegistro”
version n-1 Número de versión del QueryStatus.
1: (DEPRECATED), se utiliza para implementaciones Antiguas.
2: Tiene la capacidad de devolver información específica dependiendo del tipo de
transaccioón que se solicite.
Tipotrx a..20 Tipo de transacción a consultar, posibles consultas:
CREDITO: Tarjetas de crédito
DEBITO: Tarjetas de débito
TARJETA: Tarjetas de crédito o débito
C2P: Pago Móvil Comercio a Persona.
P2C: Pago Móvil Persona a Comercio.
CRYPTO: Solicitud de pago con Criptomonedas.
CRYPTO_CONFIR: Confirmación de pago con Criptomonedas.
BANPLUSP: Solicitud de pago con Banplus Pay.
BANPLUSP_CONFIR: Confirmación de pago con Banplus Pay.
ZELLE: Verificación de pago Zelle.
C@MBIO_PAGOMOVIL: Vuelto realizado a través de Pago Móvil.
C@MBIO_PRIVADO: Vuelto realizado a través de los bancos.
CREDITO_INMEDITO: Transferencia entre cuentas bancarias.
DEPOSITO: Depósito a una cuenta bancaria.
ANULACION: Anulaciones de transacciones de Crédito, C2P y Banplus Pay.
Respuesta Aprobada - Crédito
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<version>2</version>
<tipotrx>CREDITO</tipotrx>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 29/176

QueryStatus
Respuesta Aprobada - Débito
<response>
<control>1615913079514204303</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>2922</factura>
<monto>1199</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>145</seqnum>
<authid>3451</authid>
<authname>P-Banesco</authname>
<tarjeta>542007 * * * * * 9279</tarjeta>
<referencia>72</referencia>
<terminal>7775</terminal>
<lote>22</lote>
<rifbanco>J-07013380-5</rifbanco>
<afiliacion>9876543112</afiliacion>
<marca>MasterCard</marca>
<tipotrx>CREDITO</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
B A N E S C O J-07013380-5
COMPRA
C2P
CARACAS
RIF:J003430757 AFIL:9876543112
TER:7775 LOTE:22 REF:72
NRO.CTA:542007**9279 "M"
FECHA:16/03/2021 12:44:49 APROB:3451
SECUENCIA:145 CAJA:VPOS
MONTO BS. :11,99
FIRMA :
C.I. :
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 30/176

QueryStatus
Respuesta Aprobada – Pago Móvil C2P
<response>
<control>1615913079514204303</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>2922</factura>
<monto>1199</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>145</seqnum>
<authid>3451</authid>
<authname>P-Banesco</authname>
<tarjeta>542007 * * * * * 9279</tarjeta>
<referencia>72</referencia>
<terminal>7775</terminal>
<lote>22</lote>
<rifbanco>J-07013380-5</rifbanco>
<afiliacion>9876543112</afiliacion>
<marca>Master Debit</marca>
<tipotrx>DEBITO</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
B A N E S C O J-07013380-5
COMPRA
C2P
CARACAS
RIF:J003430757 AFIL:9876543112
TER:7775 LOTE:22 REF:72
NRO.CTA:542007**9279 "M"
FECHA:16/03/2021 12:44:49 APROB:3451
SECUENCIA:145 CAJA:VPOS
MONTO BS. :11,99
FIRMA :
C.I. :
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 31/176

QueryStatus
Respuesta Aprobada – Pago Móvil P2C
<response>
<control>1615913265877204312</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>2923</factura>
<monto>1234567890</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid></vtid>
<seqnum>146</seqnum>
<authid>0</authid>
<authname>P-BancoPlazaC2P</authname>
<tarjeta></tarjeta>
<referencia>0</referencia>
<terminal>32132112</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>123456711</afiliacion>
<telefonoEmisor>0424 * * * 6002</telefonoEmisor>
<bancoEmisor>0134</bancoEmisor>
<bancoAdquiriente>0138</bancoAdquiriente>
<tipotrx>C2P</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
PAGO MOVIL
C2P
CARACAS
RIF:J003430757 AFIL:123456711
TER:32132112 LOTE:1 REF:0
NRO.TELEFONO:04242766002
FECHA:16/03/2021 12:47:53
SECUENCIA:146 CAJA:VPOS
MONTO BS. :12.345.678,90
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 32/176

QueryStatus
Respuesta Aprobada – Criptomonedas
<response>
<control>1615913454764204326</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>2924</factura>
<monto>300</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>147</seqnum>
<authid>00404963</authid>
<authname>P-BancoPlazaP2C</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>7773</terminal>
<lote>23</lote>
<rifbanco></rifbanco>
<afiliacion>876543331</afiliacion>
<telefonoEmisor>0412---0188</telefonoEmisor>
<bancoEmisor>0138</bancoEmisor>
<telefonoAdquiriente>0412---3379</telefonoAdquiriente>
<bancoAdquiriente>0138</bancoAdquiriente>
<moneda_pago>Euros</moneda_pago>
<monto_divisa>0,00</monto_divisa>
<tipotrx>P2C</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
PAGO MOVIL
C2P
CARACAS
RIF:J003430757 AFIL:876543331
TER:7773 LOTE:23 REF:00404963
NRO.TELEFONO:04120300188
FECHA:16/03/2021 12:50:46
SECUENCIA:147 CAJA:VPOS
MONTO BS. :3,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 33/176

QueryStatus
Respuesta Aprobada – BanPlus Pay
<response>
<control>1615913692896204337</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1234</factura>
<monto>400000000</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>149</seqnum>
<authid></authid>
<authname>P-Cryptobuyer</authname>
<tarjeta></tarjeta>
<referencia>fbae2b5a-df98-47d8-b274-640075ef51bd</referencia>
<terminal>00000003</terminal>
<lote>14</lote>
<rifbanco></rifbanco>
<afiliacion>67512300</afiliacion>
<monto_crypto>0.00037633</monto_crypto>
<nombre_moneda>Bitcoin</nombre_moneda>
<tipomoneda>BTC</tipomoneda>
<qrurl>
<![CDATA[https://chart.googleapis.com/chart?
chs=250x250&cht=qr&chl=bitcoin:bc1qf2myyry9ycwdc9xwkwvfmqgptplhzmdv7paz9z?amount=0.00037633]]>
</qrurl>
<tipotrx>CRYPTO_CONFIR</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
CRYPTOBUYER
PAGO CRIPTOMONEDA
C2P
CARACAS
RIF:J003430757
TER:00000003 AFIL:67512300
FECHA:16/03/2021 12:57:16
ID: 640075EF51BD
SECUENCIA:149 CAJA:VPOS
MONTO BS. :4.000.000,00
MONTO FIAT: 20,00 USD
MONTO CRIPTO:3.76335427 BTC
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 34/176

QueryStatus
Respuesta Aprobada – Zelle
<response>
<control>1620309766241205818</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>BPPBs</factura>
<monto>4000000</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>106</seqnum>
<authid>7471</authid>
<authname>P-BanplusPresidents</authname>
<tarjeta></tarjeta>
<referencia>7471</referencia>
<terminal>88811143</terminal>
<lote>4</lote>
<rifbanco></rifbanco>
<afiliacion>99988809</afiliacion>
<nombre_moneda>Dolar</nombre_moneda>
<monto_divisa>20,00</monto_divisa>
<nombre_cuenta>BANPLUS Pay Dolar</nombre_cuenta>
<tipotrx>BANPLUSP_CONFIR</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
BANPLUS
VENTA BANPLUS PAY
C2P
CARACAS
RIF:J003430757
TER:88811143 AFIL:99988809
FECHA:06/05/2021 10:03:19
REFERENCIA: 7471
SECUENCIA:106 CAJA:VPOS
MONTO BS: 40.000,00
MONTO USD: 20,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 35/176

QueryStatus
Respuesta Aprobada – C@mbio Pago Móvil
<response>
<control>1650604074837217491</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1184</factura>
<monto>400000000</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>1273</seqnum>
<authid></authid>
<authname>P-Zelle</authname>
<tarjeta></tarjeta>
<referencia>1s3t5g7b9o0q</referencia>
<terminal>99600014</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>888941</afiliacion>
<bancoAdquiriente>BOFA</bancoAdquiriente>
<tipotrx>ZELLE</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
VERIFICACION PAGO ZELLE
PROCESA
CARACAS
RIF:J138249197 AFIL:888941
TER:99600014 LOTE:1
REF:1S3T5G7B9O0Q
BANCO COMERCIO:BOFA
FECHA:22/04/2022 01:08:10
SECUENCIA:1273 CAJA:VPOS
MONTO BS.:4.000.000,00
MONTO USD: 1.000.000,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 36/176

QueryStatus
Respuesta Aprobada – C@mbio Privado
<response>
<control>1620235634338205776</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>3094</factura>
<monto>1234567890</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>101</seqnum>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<tarjeta></tarjeta>
<referencia>6432318</referencia>
<terminal>7773</terminal>
<lote>23</lote>
<rifbanco></rifbanco>
<afiliacion>876543331</afiliacion>
<nombre_moneda>Bs</nombre_moneda>
<bancoEmisor>0138</bancoEmisor>
<telefonoAdquiriente>0424*6002</telefonoAdquiriente>
<bancoAdquiriente>0134</bancoAdquiriente>
<tipotrx>C@mbio_PagoMovil</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
RECARGA PAGO MOVIL
C2P
CARACAS
RIF:J003430757 AFIL:876543331
TER:7773 LOTE:23 REF:6432318
APROBACION:12065
NRO.TELEFONO:04241111102
FECHA:06/05/2021 09:10:28
SECUENCIA:101 CAJA:VPOS
MONTO BS. :12.345.678,90
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 37/176

QueryStatus
Respuesta Aprobada – Crédito Inmediato
<response>
<control>1625252363558213111</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>3493</factura>
<monto>1234567890</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>188</seqnum>
<authid>25779</authid>
<authname>P-BanplusPresidents</authname>
<tarjeta></tarjeta>
<referencia>3009697</referencia>
<terminal>88811143</terminal>
<lote>4</lote>
<rifbanco></rifbanco>
<afiliacion>99988809</afiliacion>
<nombre_moneda>Bs</nombre_moneda>
<nombre_cuenta>Bolívares</nombre_cuenta>
<bancoEmisor>0174</bancoEmisor>
<telefonoAdquiriente>0424*4567</telefonoAdquiriente>
<bancoAdquiriente>0174</bancoAdquiriente>
<tipotrx>C@mbio_Privado</tipotrx>
<voucher>
ULTIMA TRANS APROBADA
NO SE IMPRIMIO VOUCHER ORIG
C2P
DESCONOCIDO
FECHA : 02/07/2021 14:59:54
LOCALIDAD: CARACAS
RIF : J003430757
CAJA : VPOS
SECUENCIA: 188
TARJETA :
NRO. REF : 3009697
APROBACION: 25779
MONTO : 12.345.678,90
NO SE ENCONTRO VOUCHER ASIGNADO
PARA ESTA TRANSACCION,FAVOR
REPORTAR AL 0212.507.76.00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 38/176

QueryStatus
Respuesta Aprobada – Depósito
<response>
<control>1669727693405232368</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1448</factura>
<monto>20000</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>65</seqnum>
<authid></authid>
<authname>P-MercantilP2C</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>2874900</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>787789</afiliacion>
<cuentacliente>365412</cuentacliente>
<telefonocliente>0414---4567</telefonocliente>
<bancocliente>0138</bancocliente>
<cuentacomercio>010512----------4565</cuentacomercio>
<tipotrx>CREDITO_INMEDIATO</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
MERCANTIL P2C
VERIFICACION TRANSFERENCIA
PROCESA CREDICARD2
CARACAS
RIF:J003430757 AFIL:787789
TER:2874900 LOTE:1
REF:270
CUENTA:
FECHA:29/11/2022 09:15:01
SECUENCIA:65 CAJA:VPOS
MONTO BS.:200,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 39/176

QueryStatus
Respuesta Aprobada – Anulación C2P
<response>
<control>1669728264616232386</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1450</factura>
<monto>30000</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>66</seqnum>
<authid></authid>
<authname>P-MercantilP2C</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>2874900</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>787789</afiliacion>
<cuentacomercio>0105124565</cuentacomercio>
<numdeposito>123474</numdeposito>
<tipotrx>DEPOSITO</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
MERCANTIL P2C
VERIFICACION DEPOSITO
PROCESA CREDICARD2
CARACAS
RIF:J003430757 AFIL:787789
TER:2874900 LOTE:1
REF:107
CUENTA:0105124565
FECHA:29/11/2022 09:25:45
SECUENCIA:19 CAJA:VPOS
MONTO BS.:300,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 40/176

QueryStatus
QueryStatus Versión 1 (DEPRECATED)
Esta versión sólo se mantiene para los comercios que ya tienen realizada una implementación. Si desea tener todas las
mejoras le recomendamos utilizar “QueryStatus Versión 2”.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
QueryStatus
URL:
https://<ip>/payment/action/v2-querystatus
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
<response>
<control>1668715983435225655</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>1693</factura>
<monto>5000</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>20102</seqnum>
<authid>56801</authid>
<authname>P-BancoPlazaC2P</authname>
<tarjeta></tarjeta>
<referencia>9700942</referencia>
<terminal>32132112</terminal>
<lote>2</lote>
<rifbanco></rifbanco>
<afiliacion>123456711</afiliacion>
<tipotrx>ANULACION</tipotrx>
<voucher>
<UT> DUPLICADO</UT>
BANCO PLAZA
ANULACION PAGO MOVIL
PROCESA CREDICARD2
CARACAS
RIF:J003430757 AFIL:123456711
TER:32132112 LOTE:2 REF:9700942
NRO.TELEFONO:04241234567
FECHA:17/11/2022 16:13:08
SECUENCIA:20102 CAJA:VPOS
MONTO BS. :-50,00
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 41/176

QueryStatus
Respuesta Aprobada
Respuesta Pendiente
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
</request>
XML
<response>
<control>1234567890123456789</control>
<cod_afiliacion>1234567</cod_afiliacion>
<factura>883</factura>
<monto>1199</monto>
<estado>A</estado>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>evertec04</vtid>
<seqnum>33</seqnum>
<authid>901509</authid>
<authname>P-Mega</authname>
<tarjeta>123456 * * * * * * 1234</tarjeta>
<referencia>901509</referencia>
<terminal>UGEVE001</terminal>
<lote>24</lote>
<rifbanco></rifbanco>
<afiliacion>EV000002</afiliacion>
<voucher>
ORIGINAL-COMERCIO
MEGA SOFT COMPUTACION C.A
COMPRA
EVERTEC
CARACAS
EV000002
UGEVE001LOTE:24
R.I.F:J-00000002-0
FECHA :02/03/2020 15:23:32
NRO.CUENTA:123456**1234
NRO. REF :901509
APROBACION:901509
CAJA :MEGA04
SECUENCIA :33
MONTO BSF :11,99
FIRMA:................
C.I :................
<UT>ULTIMA TRANS. APROB.</UT>
ASUMO LA OBLIGACION DE PAGAR
AL BANCO EMISOR DE ESTA TARJETA
EL MONTO INDICADO EN ESTA NOTA
DE CONSUMO.
RIF: J-30984132-7
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 42/176

QueryStatus
Respuesta Fallida
Compra con Tarjeta de Crédito
Esta llamada realiza la transacción de pago a crédito.
Si se desea enviar los datos encriptados, recuerde realizar la petición “Recepción de Llave para Desencriptación” para
ingresar la llave con lo que se trabajará, ya que esta es la encargada de traducir los datos cuando llegan al Payment
Gateway.
<response>
<control>1234567890123456789</control>
<cod_afiliacion>1234567</cod_afiliacion>
<factura>0</factura>
<monto>0</monto>
<estado>P</estado>
<codigo>09</codigo>
<descripcion>La Transacción aun no fue realizada</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher></voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<cod_afiliacion>1234567</cod_afiliacion>
<factura>884</factura>
<monto>100</monto>
<estado>R</estado>
<codigo>XD</codigo>
<descripcion>Terminal o Payment no disponible</descripcion>
<vtid>MEGA04</vtid>
<seqnum>34</seqnum>
<authid></authid>
<authname>MEGA</authname>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal>0</terminal>
<lote>0</lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
RECIBO DE COMPRA
EVERTEC
CARACAS
RIF : J-00000002-0
FECHA : 02/03/2020 15:42:22
EVERTEC04
TRANSACCION FALLIDA:
XD
TERMINAL O PAYMENT NO
DISPONIBLE
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 43/176

GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Tarjeta de Crédito
URL:
https://<ip>/payment/action/v2-procesar-compra
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Cuerpo Encriptado:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<transcode>0141</transcode>
<pan>1234567890123456</pan>
<cvv2>123</cvv2>
<cid>V1234567</cid>
<expdate>0101</expdate>
<amount>1.00</amount>
<client>Pedro Perez</client>
<factura>2</factura>
<mode>4</mode>
</request>
XML
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<transcode>0141</transcode>
<pan>sOxnPn7fvUXdOdJFGu+OCZSXJwFDwVFNbTgVCXBGfFU=</pan>
<cvv2>crIUGIcsuqBkw/lC8VfYYQ==</cvv2>
<cid>e4j8i145mfkgkiCMJb2shA==</cid>
<expdate>1ZrRHmVW8wrYkIO1eFxNxA==</expdate>
<amount>1.00</amount>
<client>Pedro Perez</client>
<factura>2</factura>
<mode>4</mode>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 44/176

Tarjeta de Crédito
Respuesta Fallida – Número de control erróneo
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>MEGA04</vtid>
<seqnum>33</seqnum>
<authid>901509</authid>
<authname>P-Mega</authname>
<factura>883</factura>
<tarjeta>123456 * * * * * * 3456</tarjeta>
<referencia>901509</referencia>
<terminal>MEGA001</terminal>
<lote>24</lote>
<rifbanco></rifbanco>
<afiliacion>EV000002</afiliacion>
<voucher>
<linea>_</linea>
<linea>ORIGINAL-COMERCIO</linea>
<linea>_MEGA_SOFT_COMPUTACION</linea>
<linea></linea>
<linea>COMPRA</linea>
<linea>_</linea>
<linea>MEGA_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>MEGA001_LOTE:24</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:02/03/2020_15:23:32</linea>
<linea>NRO.CUENTA:123456* * * * * *3456_</linea>
<linea>NRO.REF:901509</linea>
<linea>APROBACION:901509_</linea>
<linea>_</linea>
<linea>CAJA:MEGA04_</linea>
<linea>SECUENCIA_:33_</linea>
<linea>_</linea>
<linea>MONTO_BSF_:1,00_</linea>
<linea>_</linea>
<linea>_</linea>
<linea>FIRMA:................</linea>
<linea></linea>
<linea></linea>
<linea>C.I:................</linea>
<linea></linea>
<linea>
<UT>ULTIMA_TRANS._APROB.</UT>
</linea>
<linea>_</linea>
<linea>_ASUMO_LA_OBLIGACION_DE_PAGAR_</linea>
<linea>AL_BANCO_EMISOR_DE_ESTA_TARJETA_</linea>
<linea>_EL_MONTO_INDICADO_EN_ESTA_NOTA_</linea>
<linea>DE_CONSUMO.</linea>
<linea>_</linea>
<linea>__RIF:_J-30984132-7</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 45/176

Tarjeta de Crédito
Respuesta Fallida – Número de tarjeta erróneo
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 46/176

Tarjeta de Crédito
Pago Móvil (C2P)
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Pago Móvil C2P
URL:
https://<ip>/payment/action/v2-procesar-compra-c2p
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
<response>
<control>1234567890123456789</control>
<codigo>39</codigo>
<descripcion>No es cuenta de credito</descripcion>
<vtid>evertec04</vtid>
<seqnum>36</seqnum>
<authid></authid>
<authname>P-MEGA</authname>
<factura>887</factura>
<tarjeta>123456 * * * * * * 3456</tarjeta>
<referencia>901510</referencia>
<terminal>MEGA001</terminal>
<lote>24</lote>
<rifbanco></rifbanco>
<afiliacion>EV000002</afiliacion>
<voucher>
<linea>_</linea>
<linea>_</linea>
<linea>_MEGA_SOFT_COMPUTACION</linea>
<linea>_</linea>
<linea>_COMPRA</linea>
<linea></linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>MEGA001LOTE:24_</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:02/03/2020_16:14:35</linea>
<linea>NRO.CUENTA:123456**3456_</linea>
<linea>NRO.REF:901510</linea>
<linea>_</linea>
<linea>CAJA:MEGA04_</linea>
<linea>SECUENCIA_:36_</linea>
<linea>_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>NO_ES_CUENTA_DE_CREDITO</linea>
<linea>_</linea>
<linea>_RIF:_J-30984132-7</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 47/176

Pago Móvil C2P
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
Respuesta Fallida – Número de control erróneo
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>V1234567</cid>
<telefono>04241234567</telefono>
<codigobanco>0102</codigobanco>
<codigoc2p>12345678</codigoc2p>
<amount>10.00</amount>
<factura>2</factura>
</request>
XML
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>mega04</vtid>
<seqnum>37</seqnum>
<authid>367740</authid>
<authname>P-MEGA</authname>
<factura>889</factura>
<referencia>800226</referencia>
<terminal>MEGA03</terminal>
<lote>7</lote>
<rifbanco>J-30984132-7</rifbanco>
<afiliacion>12345678</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>_MEGA_SOFT_COMPUTACION</linea>
<linea>PAGO_MOVIL</linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J-00000002-0_AFIL:12345678</linea>
<linea>TER:MEGA03_LOTE:7_REF:226_</linea>
<linea>NRO.TELEFONO:04241234567</linea>
<linea>FECHA:02/03/2020_16:23:25_APROB:367740_</linea>
<linea>SECUENCIA:37_CAJA:MEGA04_</linea>
<linea>MONTO_BS._:12.345.678,90</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 48/176

Pago Móvil C2P
Pago Móvil (P2C)
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Pago Móvil P2C
URL:
https://<ip>/payment/action/v2-procesar-compra-p2c
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<telefonoCliente>04241234567</telefonoCliente>
<codigobancoCliente>0102</codigobancoCliente>
<telefonoComercio>04121234567</telefonoComercio>
<codigobancoComercio>0138</codigobancoComercio>
<tipoPago>10</tipoPago>
<amount>100</amount>
<factura>2</factura>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 49/176

Pago Móvil P2C
Respuesta Fallida – Número de control erróneo
Respuesta Fallida – terminal no disponible
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>bancoplazap2c</vtid>
<seqnum>5033</seqnum>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<factura>1072</factura>
<referencia></referencia>
<terminal>32165412</terminal>
<lote>6</lote>
<rifbanco></rifbanco>
<afiliacion>1234567888</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>BANCO_PLAZA</linea>
<linea>PAGO_MOVIL</linea>
<linea>P2C_BANCO_PLAZA_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J0123456789_AFIL:1234567888</linea>
<linea>TER:32165412_LOTE:6_REF:246710650007_</linea>
<linea>NRO.TELEFONO:04249998877</linea>
<linea>FECHA:12/03/2020_08:59:04_</linea>
<linea>SECUENCIA:5033_CAJA:BANCOPLAZAP2C_</linea>
<linea>MONTO_BS.:2.500,00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 50/176

Pago Móvil P2C
Respuesta Fallida – Número de referencia utilizado en otra compra
<response>
<control>1234567890123456789</control>
<codigo>XD</codigo>
<descripcion>Terminal o Payment no disponible</descripcion>
<vtid>Mega04</vtid>
<seqnum>38</seqnum>
<authid></authid>
<authname>P-MEGA</authname>
<factura>890</factura>
<referencia></referencia>
<terminal>0</terminal>
<lote>0</lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea>_</linea>
<linea>_RECIBO_DE_COMPRA</linea>
<linea>_</linea>
<linea>MEGA_</linea>
<linea>CARACAS_</linea>
<linea>_</linea>
<linea>RIF:_J-00000002-0</linea>
<linea>FECHA:_02/03/2020_16:27:55</linea>
<linea>MEGA04_</linea>
<linea>_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>XD_</linea>
<linea>TERMINAL_O_PAYMENT_NO</linea>
<linea>DISPONIBLE</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 51/176

Pago Móvil P2C
Criptomonedas (Obtener monedas disponibles)
Este servicio ejecuta la petición para obtener los códigos y nombres de las criptomonedas disponibles.
 
No requiere de número de control para su llamado.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Pago Criptomonedas (Obtener monedas disponibles)
URL:
https://<ip>/payment/action/v2-procesar-crypto-get
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
<response>
<control>1234567890123456789</control>
<codigo>PC</codigo>
<descripcion>Referencia utilizada en otra compra</descripcion>
<vtid>bancoplazap2c</vtid>
<seqnum>5030</seqnum>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<factura>1068</factura>
<referencia></referencia>
<terminal>32165412</terminal>
<lote>6</lote>
<rifbanco></rifbanco>
<afiliacion>1234567888</afiliacion>
<voucher>
<linea>_</linea>
<linea>BANCO_PLAZA</linea>
<linea>_COMPRA</linea>
<linea>P2C_BANCO_PLAZA</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J0123456789_AFIL:1234567888</linea>
<linea>TER:32165412_LOTE:6_REF:00404963_</linea>
<linea>NRO.TELEFONO:04120300188_</linea>
<linea>FECHA:12/03/2020_08:50:19_</linea>
<linea>SECUENCIA:5030_CAJA:BANCOPLAZAP2C_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>REFERENCIA_UTILIZADA_EN_OTRA_COMPRA</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 52/176

Pago Criptomonedas (Obtener monedas disponibles)
Respuesta Aprobada
Criptomonedas (Solicitud)
Este servicio ejecuta la petición para realizar un pago con criptomendas. En donde por medio del monto en bolívares y
la selección del tipo de criptomoneda, tendrá como respuesta el valor en la moneda elegida, aparte de la referencia y
código QR, necesarios para el proceso de confirmación del pago.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Pago Criptomonedas (Solicitud)
URL:
https://<ip>/payment/action/v2-procesar-crypto-auth
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
</request>
XML
<response>
<codigo>00</codigo>
<descripcion>APROBADO</descripcion>
<lista_codigos>BTC,BCH,DASH,ETH</lista_codigos>
<lista_nombres>Bitcoin,Bitcoin Cash,Dash,Ethereum</lista_nombres>
</response>
XML
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<amount>12345678</amount>
<tipo_moneda>BTC</tipo_moneda>
<factura>1234</factura>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 53/176

Pago Criptomonedas (Solicitud)
Respuesta Fallida – Número de control erróneo
Criptomonedas (Confirmación)
Este servicio realiza la petición de confirmación de pago con criptomendas. Debe enviarse con el mismo número de
control que la solicitud, luego de que el usuario realizara el pago a través de su criptobilletera.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Pago Criptomonedas (Confirmación)
URL:
https://<ip>/payment/action/v2-procesar-crypto-confir
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADO</descripcion>
<monto>12345678</monto>
<monto_crypto>0.0123456</monto_crypto>
<nombre_moneda>Bitcoin</nombre_moneda>
<tipomoneda>BTC</tipomoneda>
<factura>1234</factura>
<seqnum>1234</seqnum>
<referencia>8dc34d4e-f09d-4749-b022-07b3cd488233</referencia>
<qrurl><![CDATA[https://chart.googleapis.com/chart?
chs=250x250&cht=qr&chl=bitcoin:1C8nLT1dhbQnAj5uTMwsQjt7DU4DwQKrNpamount=0.00228192]]>
</qrurl>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<monto></monto>
<monto_crypto></monto_crypto>
<nombre_moneda></nombre_moneda>
<tipomoneda></tipomoneda>
<factura></factura>
<seqnum></seqnum>
<referencia></referencia>
<qrurl></qrurl>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 54/176

Pago Criptomonedas (Confirmación)
Respuesta Aprobada
Respuesta Fallida – Número de control erróneo
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
</request>
XML
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADO</descripcion>
<monto>12345678</monto>
<monto_crypto>0.0123456</monto_crypto>
<nombre_moneda>Bitcoin</nombre_moneda>
<factura>1234</factura>
<vtid>mega04</vtid>
<seqnum>37</seqnum>
<lote>12</lote>
<authname>P-Cryptobuyer</authname>
<terminal>00900944</terminal>
<referencia>8dc34d4e-f09d-4749-b022-07b3cd488233</referencia>
<afiliacion>1234567888</afiliacion>
<rifbanco></rifbanco>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>CRYPTOBUYER</linea>
<linea>PAGO_CRIPTOMONEDA_</linea>
<linea>CRYPTO_HJ_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J123456789</linea>
<linea>TER:90908798_AFIL:6665443</linea>
<linea>FECHA:19/02/2020_15:52:54_</linea>
<linea>ID:_E22C88485AD8_</linea>
<linea>SECUENCIA:7131_CAJA:MEGA05_</linea>
<linea>MONTO_BS.:4.000.000,00_</linea>
<linea>MONTO_FIAT:_20,00_USD_</linea>
<linea>MONTO_CRIPTO:0.00217302_BTC</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 55/176

Pago Criptomonedas (Confirmación)
Respuesta Fallida – Timeout o plataforma no disponible
Respuesta Fallida – Pago por monto inferior
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<monto></monto>
<monto_crypto></monto_crypto>
<nombre_moneda></nombre_moneda>
<factura></factura>
<vtid></vtid>
<seqnum></seqnum>
<lote></lote>
<authname></authname>
<terminal></terminal>
<referencia></referencia>
<afiliacion></afiliacion>
<rifbanco></rifbanco>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1589926115529194971</control>
<codigo>AI</codigo>
<descripcion>AI: Plataforma no disponible</descripcion>
<monto></monto>
<monto_crypto></monto_crypto>
<nombre_moneda></nombre_moneda>
<tipomoneda></tipomoneda>
<factura></factura>
<seqnum></seqnum>
<referencia></referencia>
<qrurl></qrurl>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 56/176

Pago Criptomonedas (Confirmación)
Fallida – Transacción no pagada
<response>
<control>1589988945418195391</control>
<codigo>MF</codigo>
<descripcion>Pago por monto inferior</descripcion>
<monto>2000000.00</monto>
<monto_crypto>0.00109326</monto_crypto>
<nombre_moneda>Bitcoin</nombre_moneda>
<factura>1234</factura>
<vtid>MEGA05</vtid>
<seqnum>7186</seqnum>
<lote>1</lote>
<authname>P-Cryptobuyer</authname>
<terminal>90908798</terminal>
<referencia>625b7599-e36f-428d-86ba-5891fefac7ee</referencia>
<afiliacion>6665443</afiliacion>
<rifbanco></rifbanco>
<voucher>
<linea>_</linea>
<linea>CRYPTOBUYER</linea>
<linea>_PAGO_CRIPTOMONEDA</linea>
<linea>CRYPTO_HJ_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J123456789_AFIL:6665443</linea>
<linea>TER:90908798_</linea>
<linea>FECHA:20/01/2020_10:45:55_</linea>
<linea>ID:_5891FEFAC7EE_</linea>
<linea>SECUENCIA:7186_CAJA:MEGA05_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>PAGO_POR_MONTO_INFERIOR__</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 57/176

Pago Criptomonedas (Confirmación)
Banplus Pay (Solicitud)
Este servicio ejecuta la petición para realizar un pago con Banplus Pay. En donde por medio del monto en bolívares, la
selección del tipo de moneda Fiat a utilizar y el tipo de cuenta, tendrá como respuesta el valor en la moneda elegida.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Banplus Pay (Solicitud)
URL:
https://<ip>/payment/action/v2-procesar-banplusp-auth
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
<response>
<control>1589993457673195551</control>
<codigo>ME</codigo>
<descripcion>Transaccion crypto no ha sido pagada</descripcion>
<monto>4000000.00</monto>
<monto_crypto>0.00221421</monto_crypto>
<nombre_moneda>Bitcoin</nombre_moneda>
<factura>1234</factura>
<vtid>MEGA05</vtid>
<seqnum>7223</seqnum>
<lote>1</lote>
<authname>P-Cryptobuyer</authname>
<terminal>90908798</terminal>
<referencia>61316858-289a-45a1-85a6-c28daa6263f4</referencia>
<afiliacion>6665443</afiliacion>
<rifbanco></rifbanco>
<voucher>
<linea>_</linea>
<linea>CRYPTOBUYER</linea>
<linea>_PAGO_CRIPTOMONEDA</linea>
<linea>CRYPTO_HJ_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J123456789_AFIL:6665443</linea>
<linea>TER:90908798_</linea>
<linea>FECHA:20/01/2020_12:01:04_</linea>
<linea>ID:_C28DAA6263F4_</linea>
<linea>SECUENCIA:7223_CAJA:MEGA05_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>TRANSACCION_CRYPTO_NO_HA_SIDO_PAGADA__</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 58/176

Banplus Pay (Solicitud)
Respuesta Aprobada
Respuesta Fallida – Número de control erróneo
Banplus Pay (Confirmación)
Este servicio realiza la petición de confirmación de pago con Banplus Pay. Debe enviarse con el mismo número de
control que la solicitud.
 
Si el cliente ya realizó la confirmación del ‘código OTP’ desde su aplicación financiera el campo
‘cod_otp’ puede quedar vacio.
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>V12345678</cid>
<monto>123456.78</monto>
<tipo_moneda>840</tipo_moneda>
<tipo_cuenta>720</tipo_cuenta>
<factura>1234</factura>
</request>
XML
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<monto>40000.00</monto>
<cid>V18602635</cid>
<monto_divisa>0,00</monto_divisa>
<tipo_moneda>840</tipo_moneda>
<tipo_cuenta>720</tipo_cuenta>
<factura>293</factura>
<seqnum>333</seqnum>
<authid></authid>
<authname></authname>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
</response>
XML
<response>
<control>1603469052443201785</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<monto></monto>
<cid></cid>
<monto_divisa></monto_divisa>
<tipo_moneda></tipo_moneda>
<tipo_cuenta></tipo_cuenta>
<factura></factura>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 59/176

GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Banplus Pay (Confirmación)
URL:
https://<ip>/payment/action/v2-procesar-banplusp-confir
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cod_otp>123456</cod_otp>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 60/176

Banplus Pay (Confirmación)
Respuesta Fallida – Número de control erróneo
<response>
<control>1603469052443201785</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<monto>40000.00</monto>
<cid>V18602635</cid>
<monto_divisa>0,00</monto_divisa>
<tipo_moneda>840</tipo_moneda>
<tipo_cuenta>720</tipo_cuenta>
<factura>293</factura>
<seqnum>334</seqnum>
<authid>9139</authid>
<authname>P-BanplusPresidents</authname>
<referencia>9139</referencia>
<terminal>88811143</terminal>
<lote>4</lote>
<rifbanco></rifbanco>
<afiliacion>99988809</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>BANPLUS</linea>
<linea>_VENTA_PRESIDENTS</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757</linea>
<linea>TER:88811143_AFIL:99988809__</linea>
<linea>FECHA:28/07/2021_10:27:49_</linea>
<linea>REFERENCIA:_9139_</linea>
<linea>SECUENCIA:334_CAJA:VPOS_</linea>
<linea>MONTO_BS:_40.000,00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1603469052443201785</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<monto></monto>
<cid></cid>
<monto_divisa></monto_divisa>
<tipo_moneda></tipo_moneda>
<tipo_cuenta>720</tipo_cuenta>
<factura></factura>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 61/176

Zelle
Este servicio realiza la petición de verificación del pago realizado a través de Zelle.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Zelle
URL:
https://<ip>/payment/action/v2-procesar-compra-zelle
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>v6457425</cid>
<codigobancoComercio>BOFA</codigobancoComercio>
<referencia>1s3t5g7b9o0q</referencia>
<amount>4000000.00</amount>
<factura>123</factura>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 62/176

Zelle
Respuesta Fallida – Número de control erróneo
Débito Inmediato (Solicitud)
Este servicio realiza la petición de solicitud de la transacción Débito Inmediato.
<response>
<control>1650604074837217491</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>1273</seqnum>
<authid></authid>
<authname>P-Zelle</authname>
<factura>1184</factura>
<referencia>1s3t5g7b9o0q</referencia>
<terminal>99600014</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>888941</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>VERIFICACION_PAGO_ZELLE</linea>
<linea>PROCESA</linea>
<linea>CARACAS_</linea>
<linea>RIF:J138249197_AFIL:888941</linea>
<linea>TER:99600014_LOTE:1</linea>
<linea>REF:1S3T5G7B9O0Q_</linea>
<linea>BANCO_COMERCIO:BOFA__</linea>
<linea>FECHA:22/04/2022_01:08:10_</linea>
<linea>SECUENCIA:1273_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000.000,00_</linea>
<linea>MONTO_USD:_1.000.000,00_</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1603469052443201785</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<monto></monto>
<cid></cid>
<monto_divisa></monto_divisa>
<tipo_moneda></tipo_moneda>
<tipo_cuenta>720</tipo_cuenta>
<factura></factura>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 63/176

GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Débito Inmediato (Solicitud)
URL:
https://<ip>/payment/action/v2-procesar-debitoinmediato-auth
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
 
Para procesar la transacción se debe usar o la cuentaCuente o el telefonoCliente, de usar ambos,
se tomará siempre el teléfono como el dato para procesar la transacción.
Respuesta Aprobada
Respuesta Fallida – Número de control erróneo
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1669730023643232474</control>
<cid>v6457425</cid>
<codigobancoCliente>0138</codigobancoCliente>
<cuentaCliente>1234567891234567</cuentaCliente>
<telefonoCliente>04129568321</telefonoCliente>
<amount>400.00</amount>
<factura>9057</factura>
</request>
XML
<response>
<control>1735958566485513752</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<monto>400.00</monto>
<factura>9057</factura>
<seqnum>30063</seqnum>
<authid></authid>
<authname>P-MiBancoDI</authname>
<referencia></referencia>
<terminal>9366699</terminal>
<lote>33</lote>
<rifbanco></rifbanco>
<afiliacion>1919289111</afiliacion>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 64/176

Débito Inmediato (Solicitud)
Débito Inmediato (Confirmación)
Este servicio realiza la petición de solicitud de la transacción Débito Inmediato.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Débito Inmediato (Confirmación)
URL:
https://<ip>/payment/action/v2-procesar-debitoinmediato-confir
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1735958566485513752</control>
<cod_otp>123456</cod_otp>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 65/176

Débito Inmediato (Confirmación)
Respuesta Fallida – Número de control erróneo
Crédito Inmediato
Este servicio realiza la petición de verificación de la transferencia a través del Crédito Inmediato.
GET Request
<response>
<control>1735958566485513752</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>test1</vtid>
<monto>400.00</monto>
<factura>9057</factura>
<seqnum>30064</seqnum>
<authid>20</authid>
<authname>P-MiBancoDI</authname>
<referencia>123456</referencia>
<terminal>9366699</terminal>
<lote>33</lote>
<rifbanco></rifbanco>
<afiliacion>1919289111</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>MI_BANCO_</linea>
<linea></linea>
<linea>DEBITO_INMEDIATO</linea>
<linea>UNICASA_MONTALBAN_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J-003430757_TER:9366699_</linea>
<linea>LOTE:33_REF:123456_</linea>
<linea>CELULAR:_4129568321</linea>
<linea>FECHA:03/01/2025_22:45:17_</linea>
<linea>SECUENCIA:30064_CAJA:TEST1_</linea>
<linea>CEDULA:_6457425_</linea>
<linea>MONTO_BS._:400,00</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1735958566485513752</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 66/176

La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Crédito Inmediato
URL:
https://<ip>/payment/action/v2-procesar-compra-creditoinmediato
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1669730023643232474</control>
<cid>v6457425</cid>
<cuentaOrigen>365412</cuentaOrigen>
<telefonoOrigen>04141234567</telefonoOrigen>
<codigobancoOrigen>0138</codigobancoOrigen>
<cuentaDestino>01051234567895214565</cuentaDestino>
<amount>200.00</amount>
<factura></factura>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 67/176

Crédito Inmediato
Respuesta Fallida – Número de control erróneo
Depósito
Este servicio realiza la petición de verificación del depósito a través del Depósito.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
<response>
<control>1669730023643232474</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>74</seqnum>
<authid></authid>
<authname>P-MercantilP2C</authname>
<factura>1458</factura>
<referencia></referencia>
<terminal>2874900</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>787789</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>MERCANTIL_P2C_</linea>
<linea>VERIFICACION_TRANSFERENCIA</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:276_</linea>
<linea>CUENTA:__</linea>
<linea>FECHA:29/11/2022_09:53:50_</linea>
<linea>SECUENCIA:74_CAJA:VPOS_</linea>
<linea>MONTO_BS.:200,00_</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 68/176

POST Request
Depósito
URL:
https://<ip>/payment/action/v2-procesar-compra-deposito
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
Respuesta Fallida – Número de control erróneo
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1669729628928232465</control>
<cid>v6457425</cid>
<numDeposito>123474</numDeposito>
<cuentaDestino>01051234567895214565</cuentaDestino>
<amount>300.00</amount>
<factura>123</factura>
</request>
XML
<response>
<control>1669729628928232465</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>73</seqnum>
<authid></authid>
<authname>P-MercantilP2C</authname>
<factura>1457</factura>
<referencia></referencia>
<terminal>2874900</terminal>
<lote>1</lote>
<rifbanco></rifbanco>
<afiliacion>787789</afiliacion>
<voucher>
<linea>_</linea>
<linea><UT>DUPLICADO</UT>_</linea>
<linea>MERCANTIL_P2C</linea>
<linea>VERIFICACION_DEPOSITO</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789_</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:107_</linea>
<linea>CUENTA:0102152145__</linea>
<linea>FECHA:15/11/2022_16:56:45_</linea>
<linea>SECUENCIA:19_CAJA:VPOS_</linea>
<linea>MONTO_BS.:300,00_</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 69/176

Depósito
C@mbio Pago Móvil
Este servicio ejecuta la petición para realizar un vuelto con Pago Móvil. En donde por medio del monto en bolívares y
la selección del tipo de moneda Fiat a utilizar, se realizará la devolución a un cliente.
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Pago Criptomonedas (Solicitud)
URL:
https://<ip>/payment/action/v2-procesar-cambio-pagomovil
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<response>
<control>123456789012345678977</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>E1234567</cid>
<telefono>042412345678</telefono>
<codigobanco>0134</codigobanco>
<tipo_moneda>0</tipo_moneda>
<amount>1234567890</amount>
<factura>123456</factura>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 70/176

Pago Criptomonedas (Solicitud)
Respuesta Fallida – Número de control erróneo
C@mbio Privado
Este servicio ejecuta la petición para realizar un vuelto con bancos privados. En donde por medio del monto en
bolívares, dólares o euros, la selección del tipo de moneda Fiat y se la selección de tipo de cuenta, se realizará la
devolución a un cliente.
GET Request
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>92</seqnum>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<factura>3086</factura>
<referencia>24991</referencia>
<terminal>7773</terminal>
<lote>23</lote>
<rifbanco></rifbanco>
<afiliacion>876543331</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>BANCO_PLAZA</linea>
<linea>__RECARGA_PAGO_MOVIL</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:876543331</linea>
<linea>TER:7773_LOTE:23_REF:24991_</linea>
<linea>APROBACION:32850_</linea>
<linea>NRO.TELEFONO:04241234567</linea>
<linea>FECHA:03/05/2021_20:05:55_</linea>
<linea>SECUENCIA:92_CAJA:VPOS_</linea>
<linea>MONTO_BS.:12.345.678,90_</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 71/176

La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
C@mbio Privado
URL:
https://<ip>/payment/action/v2-procesar-cambio-privado
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>E1234567</cid>
<telefono>04241234567</telefono>
<codigobanco>0174</codigobanco>
<tipo_moneda>978</tipo_moneda>
<tipo_cuenta>10</tipo_cuenta>
<amount>1234567890</amount>
<factura>123456</factura>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 72/176

C@mbio Privado
Respuesta Fallida – Número de control erróneo
<response>
<control>1625252363558213111</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>188</seqnum>
<authid>25779</authid>
<authname>P-BanplusPresidents</authname>
<factura>3493</factura>
<referencia>3009697</referencia>
<terminal>88811143</terminal>
<lote>4</lote>
<rifbanco></rifbanco>
<afiliacion>99988809</afiliacion>
<nombre_cuenta>Bolívares</nombre_cuenta>
<nombre_moneda>Bs</nombre_moneda>
<voucher>
<linea>_</linea>
<linea>_</linea>
<linea>ULTIMA_TRANS_APROBADA</linea>
<linea>_</linea>
<linea>NO_SE_IMPRIMIO_VOUCHER_ORIG_</linea>
<linea>_</linea>
<linea>C2P_</linea>
<linea>DESCONOCIDO_</linea>
<linea>FECHA:_02/07/2021_14:59:54_</linea>
<linea>LOCALIDAD:_CARACAS_</linea>
<linea>RIF_:_J003430757</linea>
<linea>CAJA:_VPOS</linea>
<linea>SECUENCIA:_188_</linea>
<linea>TARJETA:</linea>
<linea>NRO.REF:_3009697_</linea>
<linea>APROBACION:_25779_</linea>
<linea>_</linea>
<linea>MONTO_:_12.345.678,90</linea>
<linea>_</linea>
<linea>_</linea>
<linea>NO_SE_ENCONTRO_VOUCHER_ASIGNADO_</linea>
<linea>PARA_ESTA_TRANSACCION,FAVOR_</linea>
<linea>REPORTAR_AL_0212.507.76.00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 73/176

C@mbio Privado
Preautorización
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Preautorización
URL:
https://<ip>/payment/action/v2-procesar-compra
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Cuerpo Encriptado:
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<transcode>0135</transcode>
<pan>1234567890123456</pan>
<cvv2>638</cvv2>
<cid>V1234567</cid>
<expdate>0510</expdate>
<amount>123456</amount>
<client>Pedro Perez</client>
<factura>7</factura>
<mode>4</mode>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 74/176

Preautorización
Respuesta Aprobada
<request>
<cod_afiliacion>12354678</cod_afiliacion>
<control>1234567890123456789</control>
<transcode>0135</transcode>
<pan>sOxnPn7fvUXdOdJFGu+OCZSXJwFDwVFNbTgVCXBGfFU=</pan>
<cvv2>crIUGIcsuqBkw/lC8VfYYQ==</cvv2>
<cid>e4j8i145mfkgkiCMJb2shA==</cid>
<expdate>1ZrRHmVW8wrYkIO1eFxNxA==</expdate>
<amount>123456</amount>
<client>Pedro Perez</client>
<factura>123456</factura>
<mode>4</mode>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 75/176

Preautorización
Repuesta Fallida – Número de control erróneo
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>mega04</vtid>
<seqnum>39</seqnum>
<authid>59252</authid>
<authname>P-MEGA</authname>
<factura>892</factura>
<tarjeta>123456------123456</tarjeta>
<referencia>901511</referencia>
<terminal>MEGA001</terminal>
<lote>24</lote>
<rifbanco></rifbanco>
<afiliacion>EV000002</afiliacion>
<voucher>
<linea>_</linea>
<linea>ORIGINAL-COMERCIO</linea>
<linea>_MEGA_SOFT_COMPUTACION</linea>
<linea>_</linea>
<linea>PREAUTORIZACION</linea>
<linea>_</linea>
<linea>MEGA_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>MEGA001_LOTE:24</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:02/03/2020_16:31:58</linea>
<linea>NRO.CUENTA:123456 * * * * * * 1234_</linea>
<linea>NRO.REF:0015110302203158</linea>
<linea>APROBACION:59252_</linea>
<linea>_</linea>
<linea>CAJA:MEGA04_</linea>
<linea>SECUENCIA_:39_</linea>
<linea>_</linea>
<linea>MONTO_BSF_:1.234,56_</linea>
<linea>_</linea>
<linea>_</linea>
<linea>FIRMA:................</linea>
<linea></linea>
<linea></linea>
<linea>C.I:................</linea>
<linea></linea>
<linea>
<UT>ULTIMA_TRANS._APROB.</UT>
</linea>
<linea>_</linea>
<linea>_ASUMO_LA_OBLIGACION_DE_PAGAR_</linea>
<linea>AL_BANCO_EMISOR_DE_ESTA_TARJETA_</linea>
<linea>_EL_MONTO_INDICADO_EN_ESTA_NOTA_</linea>
<linea>DE_CONSUMO.</linea>
<linea></linea>
<linea>RIF:_J-30984132-7_</linea>
<linea>___</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 76/176

Preautorización
Respuesta Fallida – no se ingresó cédula
Completitud
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Completitud
URL:
https://<ip>/payment/action/v2-procesar-completitud
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1583181656338189495</control>
<codigo>GA</codigo>
<descripcion>No se recibió la cédula o RIF</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 77/176

Completitud
Content-Type: text/xml
Cuerpo:
Cuerpo Encriptado:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<authid>654321</authid>
<cid>V1234567</cid>
<pan>1234567890123456</pan>
<amount>121</amount>
<expdate>0510</expdate>
<cvv2>638</cvv2>
</request>
XML
<request>
<cod_afiliacion>123456</cod_afiliacion>
<control>1234567890123456789</control>
<authid>987654</authid>
<cid>e4j8i145mfkgkiCMJb2shA==</cid>
<pan>sOxnPn7fvUXdOdJFGu+OCZSXJwFDwVFNbTgVCXBGfFU=</pan>
<amount>121</amount>
<expdate>1ZrRHmVW8wrYkIO1eFxNxA==</expdate>
<cvv2>crIUGIcsuqBkw/lC8VfYYQ==</cvv2>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 78/176

Completitud
Respuesta Fallida – Número de control erróneo
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>mega04</vtid>
<seqnum>43</seqnum>
<authid>628335</authid>
<authname>P-MEGA</authname>
<factura>903</factura>
<tarjeta>123456 * * * * * * 3456</tarjeta>
<referencia>901515</referencia>
<terminal>MEGA001</terminal>
<lote>24</lote>
<rifbanco></rifbanco>
<afiliacion>EV000002</afiliacion>
<voucher>
<linea>_</linea>
<linea>ORIGINAL-COMERCIO</linea>
<linea>_MEGA_SOFT_COMPUTACION</linea>
<linea>_</linea>
<linea>COMPLETITUD</linea>
<linea>_</linea>
<linea>MEGA_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>MEGA001LOTE:24_</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:02/03/2020_16:45:34</linea>
<linea>NRO.CUENTA:123456------3456_</linea>
<linea>NRO.REF:901515</linea>
<linea>APROBACION:628335_</linea>
<linea>_</linea>
<linea>CAJA:MEGA04_</linea>
<linea>SECUENCIA_:43_</linea>
<linea>_</linea>
<linea>MONTO_BSF_:1,21_</linea>
<linea>_</linea>
<linea>_</linea>
<linea>FIRMA:................</linea>
<linea></linea>
<linea></linea>
<linea>C.I:................</linea>
<linea></linea>
<linea></linea>
<linea>
<UT>ULTIMA_TRANS.APROB.</UT>
</linea>
<linea>_</linea>
<linea>_ASUMO_LA_OBLIGACION_DE_PAGAR_</linea>
<linea>AL_BANCO_EMISOR_DE_ESTA_TARJETA_</linea>
<linea>_EL_MONTO_INDICADO_EN_ESTA_NOTA_</linea>
<linea>DE_CONSUMO.</linea>
<linea></linea>
<linea>RIF:_J-30984132-7_</linea>
<linea>___</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 79/176

Completitud
Rpuesta Fallida – Número de Aprobación erróneo
Cierre
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Cierre
URL:
https://<ip>/payment/action/v2-procesar-cierre
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>MK</codigo>
<descripcion>MK: NO EXISTE LA PREUTORIZACION CORRESPONDIENTE A LOS DATOS
SUMINISTRADOS</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid>345345</authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 80/176

Cierre
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
Respuesta Fallida
Anulación
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Anulación
URL:
https://<ip>/payment/action/v2-procesar-anulacion
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
<request>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
</request>
XML
<response>
<vterminal>
<vtid>MEGA04</vtid>
<codigo>OO</codigo>
<descripcion>APROBADA</descripcion>
<seqnum></seqnum>
</vterminal>
</response>
XML
<response>
<vterminal>
<vtid>MEGA04</vtid>
<codigo>XD</codigo>
<descripcion>Terminal o Payment no disponible</descripcion>
<seqnum></seqnum>
</vterminal>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 81/176

Anulación
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<terminal>Mega001</terminal>
<seqnum>22</seqnum>
<monto>11,99</monto>
<factura>26</factura>
<referencia>123456</referencia>
<ult>3456</ult>
<authid>123456</authid>
</request>
XML
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>mega04</vtid>
<seqnum>80</seqnum>
<authid>901547</authid>
<authname>P-Mega</authname>
<factura></factura>
<tarjeta>123456 * * * * * * 3456</tarjeta>
<referencia>100000001547</referencia>
<terminal>MEGA001</terminal>
<lote>24</lote>
<rifbanco>J-07013380-5</rifbanco>
<afiliacion>EV000002</afiliacion>
<voucher>
<linea>_</linea>
<linea>ORIGINAL-COMERCIO</linea>
<linea>_MEGA_SOFT_COMPUTACION</linea>
<linea>_</linea>
<linea>ANULACION_COMPRA_</linea>
<linea>_</linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>MEGA001_LOTE:24</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:03/03/2020_11:01:44</linea>
<linea>NRO.CUENTA:123456------3456_</linea>
<linea>NRO.REF:100000001547</linea>
<linea>APROBACION:901547_</linea>
<linea>_</linea>
<linea>CAJA:MEGA04_</linea>
<linea>SECUENCIA_:80_</linea>
<linea>_</linea>
<linea>MONTO_BSF_:11,99_</linea>
<linea>_</linea>
<linea>
<UT>ULTIMA_TRANS.APROB.</UT>
</linea>
<linea>_</linea>
<linea>NO_REQUIERE_FIRMA_</linea>
<linea>_</linea>
<linea>RIF:_J-30984132-7_</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 82/176

Anulación
Fallida – Número de control erróneo
Fallida – datos erróneos
Anulación – Banplus Pay
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Anulación
URL:
https://<ip>/payment/action/v2-procesar-anulacion-banplusp
Cabecera:
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>GA</codigo>
<descripcion>GA: Parámetros de entrada errados</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<tarjeta></tarjeta>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 83/176

Anulación
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
Fallida – Número de control erróneo
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>V12345678</cid>
<referencia>123456</referencia>
<seqnum>22</seqnum>
<factura>26</factura>
</request>
XML
<response>
<control>1603470060166201829</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>1719</seqnum>
<authid></authid>
<authname>P-BanplusPresidents</authname>
<factura></factura>
<referencia></referencia>
<terminal>88811143</terminal>
<lote>2</lote>
<rifbanco></rifbanco>
<afiliacion>99988809</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>BANPLUS</linea>
<linea>ANULACION_VENTA_PRESIDENTS</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757</linea>
<linea>TER:88811143_AFIL:99988809__</linea>
<linea>FECHA:23/10/2020_11:47:32_</linea>
<linea>REFERENCIA:_4491_</linea>
<linea>SECUENCIA:1719_CAJA:VPOS_</linea>
<linea>MONTO_BS:_-4.000.000,00</linea>
<linea>MONTO_USD:_-20,00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 84/176

Anulación
Fallida – datos erróneos
Fallida – Transacción no encontrada o ya anulada
Anulación – Pago Móvil C2P
<response>
<control>1603470508465201845</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1603470141763201835</control>
<codigo>GA</codigo>
<descripcion>GA: Parámetros de entrada errados, No se recibió el número
de secuencia</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1603470141763201835</control>
<codigo>GA</codigo>
<descripcion>Transacción no encontrada</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 85/176

g
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Anulación
URL:
https://<ip>/payment/action/v2-procesar-anulacion-c2p
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
<request>
<cod_afiliacion>1234567</cod_afiliacion>
<control>1234567890123456789</control>
<cid>V12345678</cid>
<telefono>04141234567</telefono>
<seqnum>22</seqnum>
</request>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 86/176

Anulación
Fallida – Número de control erróneo
Fallida – datos erróneos
<response>
<control>1234567890123456789</control>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>vpos</vtid>
<seqnum>11</seqnum>
<authid></authid>
<authname>P-MiBancoC2P</authname>
<factura></factura>
<referencia></referencia>
<terminal>7776</terminal>
<lote>6</lote>
<rifbanco></rifbanco>
<afiliacion>1234567</afiliacion>
<voucher>
<linea>_</linea>
<linea>
<UT>__DUPLICADO</UT>
</linea>
<linea>MI_BANCO_</linea>
<linea>_ANULACION_PAGO_MOVIL</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:1234567</linea>
<linea>TER:7776_LOTE:6</linea>
<linea>REF:16189_</linea>
<linea>NRO.TELEFONO:04141234567</linea>
<linea>FECHA:05/02/2021_11:11:17_APROB:4029_</linea>
<linea>SECUENCIA:11_CAJA:VPOS_</linea>
<linea>MONTO_BS.:12.345.678,90_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 87/176

Anulación
Fallida – Transacción no encontrada o ya anulada
Test Merchant
GET Request
Llamada utilizada para obtener el estatus del servicio Merchant server.
Metodo GET
Cabecera:
Authorization Basic
URL:
https://<ip>/payment/action/procesar-test?cod_afiliacion=<cod_afiliacion>;
Respuesta
<response>
<control>1234567890123456789</control>
<codigo>GA</codigo>
<descripcion>GA: Parámetros de entrada errados, No se recibió el número
de secuencia</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
<response>
<control>1234567890123456789</control>
<codigo>GA</codigo>
<descripcion>Transacción no encontrada</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
<linea></linea>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 88/176

Metodo GET
Recepción de Llave para Desencriptación
GET Request
La versión 2 del servicio REST no implementará el método ‘GET’.
POST Request
Llave Desencriptamiento
URL:
https://<ip>/payment/action/v2-establecer-llave
Cabecera:
Authorization: Basic TWVnYVNvZnQ6UGF5bWVudA==
Content-Type: text/xml
Cuerpo:
Respuesta Aprobada
Requerimientos SOAP Versión 2
Debido a la necesidad de saber que ocurrió con una transacción, si se interrumpió el proceso por alguna falla o pérdida
de conexión, se decidió realizar un flujo similar al del ‘Botón de Pago’. En donde, se debe ‘PreRegistrar’ la transacción
antes de poder ser ejecutada. Esto permite llevar un registro de ella desde el inicio y poder consultarla en cualquier
momento luego de realizarla.
<response>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<vtid>JGDESA01</vtid>
</response>
XML
<request>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<securityKey>F5C0C804-3244-9510-884A-46DB1424C8972.180449<securityKey>
</request>
XML
<response>
<codigo>00</codigo>
<descripcion>Llave aplicada</descripcion>
<vtid></vtid>
<seqnum></seqnum>
<authid></authid>
<authname></authname>
<factura></factura>
<referencia></referencia>
<terminal></terminal>
<lote></lote>
<rifbanco></rifbanco>
<afiliacion></afiliacion>
<voucher>
</voucher>
</response>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 89/176

PreRegistro de Transacción
Este servicio permite obtener un número de control, el cual será necesaria para efectuar cualquier servicio
transaccional en esta versión.
Petición
PreRegistro de Transacción
URL:
https://<ip>/payment/ws/v2/preregistro
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa
Respuesta Fallida – Sin Header en la Petición:
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:preregistro>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
</end:preregistro>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preregistroResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<control>1583957080392191038</control>
<descripcion>PREREGISTRADO</descripcion>
</return>
</ns2:preregistroResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 90/176

PreRegistro de Transacción
QueryStatus de una Transacción
Este servicio permite consultar el estado de una transacción en cualquier momento luego de haber realizado un
PreRegistro.
QueryStatus V2.3
Este Querystatus ofrece las mismas ventajas que la versión 2, sólo que se agregaron los campos: “monedaInicio”,
“monedaFin” y “montoDivisa” en la respuesta. Esto permite obtener la información de las transacciones que presentan
conversión monetaria.
Petición
PreRegistro de Transacción
URL:
https://<ip>/payment/ws/v2/querystatus
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preregistroResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<control></control>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:preregistroResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 91/176

PreRegistro de Transacción
Parametros
Nombre Tipo Descripción
cod_afilicion n..10 Código de afiliación otorgado por MegaSoft al generar credenciales de autenticación.
control n..19 Número de control generado por la petición “PreRegistro”.
version n-1 Número de version del QueryStatus.
1: (DEPRECATED), se utiliza para implementaciones Antiguas.
2: Tiene la capacidad de devolver información específica dependiendo del tipo de
transaccioón que se solicite. 3: Tiene la capacidad de devolver información específica
dependiendo del tipo de transacción que se solicite y datos multimoneda.
Tipotrx a..20 Tipo de transacción a consultar, posibles consultas:
CREDITO: Tarjetas de crédito.
C2P: Pago Móvil Comercio a Persona.
P2C: Pago Móvil Persona a Comercio.
CRYPTO: Solicitud de pago con Criptomonedas.
CRYPTO_CONFIR: Confirmación de pago con Criptomonedas.
BANPLUSP: Solicitud de pago con Banplus Pay´s.
BANPLUSP_CONFIR: Confirmación de pago con Banplus Pay´s.
ZELLE: Verificación de pago Zelle.
CREDITO_INMEDIATO: Transferencia entre cuentas bancarias.
DEBITO_INM: Solicitud de pago con Débito Inmediato.
DEBITO_INM_CONFIR: Confirmación de pago con Débito Inmediato.
DEPOSITO: Depósito a una cuenta bancaria.
ANULACION: Anulaciones de transacciones de Crédito, C2P y Banplus Pay.
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>digitel</usuario>
<contrasena>d1git3l</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:querystatus>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<control>1234567890123456789</control>
<version>3</version>
<tipotrx>CREDITO</tipotrx>
</end:querystatus>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 92/176

PreRegistro de Transacción
Respuesta Exitosa - Crédito:
Respuesta Exitosa – Pago Móvil C2P:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>20420202306</cod_afiliacion>
<afiliacion>108040</afiliacion>
<authid>0</authid>
<authname>P-SigmaIntl</authname>
<codigo>00</codigo>
<control>1643826861495226668</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>215</factura>
<lote>19</lote>
<marca>MasterCard_Intl</marca>
<monedaFin>840</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>5,22</monto>
<monto_divisa>1,04</monto_divisa>
<referencia>103013443</referencia>
<rifbanco></rifbanco>
<seqnum>2094</seqnum>
<tarjeta>530072 * * * * * * 7519</tarjeta>
<terminal>10804000</terminal>
<tipotrx>CREDITO</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>_SIGMA</linea>
<linea>_VENTA</linea>
<linea>PRUEBAS_MULTIBANCO_</linea>
<linea>LA_CANDELARIA_</linea>
<linea>RIF:J-003430757</linea>
<linea>TER:108040002_AFIL:108040</linea>
<linea>FECHA:02/02/2022_14:34:46_</linea>
<linea>TARJETA:530072 * * * * * * 7519_</linea>
<linea>REFERENCIA:_103013443_</linea>
<linea>SECUENCIA:2094_CAJA:MULTIBAN07_</linea>
<linea>MONTO_BS.:5,22_</linea>
<linea>MONTO_USD:_1,04_</linea>
<linea>_</linea>
</voucher>
<vtid>multiban07</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 93/176

PreRegistro de Transacción
Respuesta Exitosa – Pago Móvil P2C:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>20420202306</cod_afiliacion>
<afiliacion>MB000014</afiliacion>
<authid>90986</authid>
<authname>P-Bnc</authname>
<bancoAdquiriente>0191</bancoAdquiriente>
<bancoEmisor>0134</bancoEmisor>
<codigo>00</codigo>
<control>1643827061504226675</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>101</lote>
<monedaFin>928</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>15,01</monto>
<monto_divisa>0,00</monto_divisa>
<referencia>800876</referencia>
<rifbanco>J-00000006-0</rifbanco>
<seqnum>2095</seqnum>
<telefonoEmisor>0412 * * * 4567</telefonoEmisor>
<terminal>GI0002</terminal>
<tipotrx>C2P</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>_BANCO_NACIONAL_DE_CREDITO</linea>
<linea>PAGO_MOVIL</linea>
<linea>PRUEBAS_MULTIBANCO_</linea>
<linea>LA_CANDELARIA_</linea>
<linea>RIF:J-003430757_AFIL:MB000014</linea>
<linea>TER:GI0002_LOTE:101_REF:876_</linea>
<linea>NRO.TELEFONO:584121234567</linea>
<linea>FECHA:02/02/2022_14:37:49_APROB:90986_</linea>
<linea>SECUENCIA:2095_CAJA:MULTIBAN07_</linea>
<linea>MONTO_BS._:15,01</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>multiban07</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 94/176

PreRegistro de Transacción
Respuesta Exitosa – Criptomonedas:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>20420202306</cod_afiliacion>
<afiliacion>44523452340</afiliacion>
<authid>00404963</authid>
<authname>P-MercantilP2C</authname>
<bancoAdquiriente>0105</bancoAdquiriente>
<bancoEmisor>0138</bancoEmisor>
<codigo>00</codigo>
<control>1643827180091226683</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>30</lote>
<monedaFin>928</monedaFin>
<monedaInicio>928</monedaInicio>
<moneda_pago>Bs</moneda_pago>
<monto>3.000.000,00</monto>
<monto_divisa>0,00</monto_divisa>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>2096</seqnum>
<telefonoAdquiriente>0412 * * * 4567</telefonoAdquiriente>
<telefonoEmisor>0412 * * * 0188</telefonoEmisor>
<terminal>P2CMERC0</terminal>
<tipotrx>P2C</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>MERCANTIL_P2C_</linea>
<linea>PAGO_MOVIL</linea>
<linea>PRUEBAS_MULTIBANCO_</linea>
<linea>LA_CANDELARIA_</linea>
<linea>RIF:J-003430757_AFIL:44523452340</linea>
<linea>TER:P2CMERC002B_LOTE:30</linea>
<linea>REF:114_</linea>
<linea>NRO.TELEFONO:04120300188</linea>
<linea>FECHA:02/02/2022_14:39:44_</linea>
<linea>SECUENCIA:2096_CAJA:MULTIBAN07_</linea>
<linea>MONTO_BS.:3.000.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>multiban07</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 95/176

PreRegistro de Transacción
Respuesta Exitosa – Banplus Pay:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>20420202306</cod_afiliacion>
<afiliacion>67512300</afiliacion>
<authname>P-Cryptobuyer</authname>
<codigo>00</codigo>
<control>1643827374457226706</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>37</lote>
<monedaFin>840</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>80,00</monto>
<monto_crypto>21.00000000</monto_crypto>
<monto_divisa>20,00</monto_divisa>
<nombre_moneda>Tether USD (TRC-20)</nombre_moneda>
<qrurl>&lt;![CDATA[https://chart.googleapis.com/chart?
chs=250x250&amp;cht=qr&amp;chl=tron:TQtXmLZQ9pX3Dc4yj1XB9v4w2xDosuXMy5?amount=21.00]]&gt;</qrurl>
<referencia>c0c4a025-a72a-4dcb-94e6-08bdcddb9910</referencia>
<rifbanco>J09876543310</rifbanco>
<seqnum>2099</seqnum>
<terminal>00000002</terminal>
<tipo_moneda>TRX_USDT</tipo_moneda>
<tipotrx>CRYPTO_CONFIR</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>CRYPTOBUYER</linea>
<linea>PAGO_CRIPTOMONEDA_</linea>
<linea>PRUEBAS_MULTIBANCO_</linea>
<linea>LA_CANDELARIA_</linea>
<linea>RIF:J-003430757</linea>
<linea>TER:00000002_AFIL:67512300</linea>
<linea>FECHA:02/02/2022_14:44:39_</linea>
<linea>ID:_08BDCDDB9910_</linea>
<linea>SECUENCIA:2099_CAJA:MULTIBAN07_</linea>
<linea>MONTO_BS.:80,00_</linea>
<linea>MONTO_FIAT:_20,00_USD_</linea>
<linea>MONTO_CRIPTO:21.0_USDT</linea>
<linea>_</linea>
</voucher>
<vtid>multiban07</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 96/176

PreRegistro de Transacción
Respuesta Exitosa – Zelle:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>20420202306</cod_afiliacion>
<afiliacion>00000000000003</afiliacion>
<authid>5728</authid>
<authname>P-BanplusPresidents</authname>
<codigo>00</codigo>
<control>1643827698827226726</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>BPbsS0AP</factura>
<lote>29</lote>
<monedaFin>840</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>4.000.000,00</monto>
<monto_divisa>1.000.000,00</monto_divisa>
<nombre_moneda>Bs</nombre_moneda>
<referencia>5728</referencia>
<seqnum>2102</seqnum>
<terminal>13400002</terminal>
<tipo_cuenta>720</tipo_cuenta>
<tipotrx>BANPLUSP_CONFIR</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANPLUS</linea>
<linea>_VENTA_BANPLUS_PAY</linea>
<linea>PRUEBAS_MULTIBANCO_</linea>
<linea>LA_CANDELARIA_</linea>
<linea>RIF:J-003430757</linea>
<linea>TER:13400002_AFIL:00000000000003__</linea>
<linea>FECHA:02/02/2022_14:48:26_</linea>
<linea>REFERENCIA:_5728_</linea>
<linea>SECUENCIA:2102_CAJA:MULTIBAN07_</linea>
<linea>MONTO_BS:_4.000.000,00_</linea>
<linea>MONTO_USD:_1.000.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>multiban07</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 97/176

PreRegistro de Transacción
Respuesta Exitosa – Crédito Inmediato:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>888941</afiliacion>
<authid></authid>
<authname>P-Zelle</authname>
<bancoAdquiriente>BOFA</bancoAdquiriente>
<codigo>00</codigo>
<control>1650605056171217505</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>1</lote>
<monedaFin>840</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>4.000,00</monto>
<monto_divisa>1.000,00</monto_divisa>
<nombre_moneda>Dolar</nombre_moneda>
<referencia>1s3d4f5g6h7j</referencia>
<rifbanco></rifbanco>
<seqnum>1274</seqnum>
<terminal>99600014</terminal>
<tipotrx>ZELLE</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>VERIFICACION_PAGO_ZELLE</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J138249197_AFIL:888941</linea>
<linea>TER:99600014_LOTE:1</linea>
<linea>REF:1S3D4F5G6H7J_</linea>
<linea>BANCO_COMERCIO:BOFA__</linea>
<linea>FECHA:22/04/2022_01:24:29_</linea>
<linea>SECUENCIA:1274_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000,00_</linea>
<linea>MONTO_USD:_1.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 98/176

PreRegistro de Transacción
Respuesta Fallida – No encontró el Número de Control:
Referencia Fallida – Transacción aun no realizada:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>787789</afiliacion>
<authid></authid>
<authname>P-MercantilP2C</authname>
<bancoCliente>0138</bancoCliente>
<codigo>00</codigo>
<control>1669739318111232508</control>
<cuenta_cliente>123456</cuenta_cliente>
<cuenta_comercio>010512--------4565</cuenta_comercio>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>1</lote>
<monedaFin>928</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>4.000,00</monto>
<monto_divisa>0,00</monto_divisa>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>75</seqnum>
<telefonoEmisor>0412---8321</telefonoEmisor>
<terminal>2874900</terminal>
<tipotrx>CREDITO_INMEDIATO</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>MERCANTIL_P2C_</linea>
<linea>VERIFICACION_TRANSFERENCIA</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:277_</linea>
<linea>CUENTA:__</linea>
<linea>FECHA:29/11/2022_12:28:45_</linea>
<linea>SECUENCIA:75_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No hay datos de control para el número indicado</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 99/176

PreRegistro de Transacción
Respuesta Exitosa – Depósito:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion></afiliacion>
<authid></authid>
<authname></authname>
<codigo>09</codigo>
<control>1583959858759191151</control>
<descripcion>La Transacción aun no fue realizada</descripcion>
<estado>P</estado>
<factura>0</factura>
<lote></lote>
<monto>0</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum></seqnum>
<tarjeta></tarjeta>
<terminal></terminal>
<vtid></vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 100/176

PreRegistro de Transacción
Respuesta Fallida – No encontró el Número de Control:
Referencia Fallida – Transacción aun no realizada:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>787789</afiliacion>
<authid></authid>
<authname>P-MercantilP2C</authname>
<codigo>00</codigo>
<control>1669739570920232526</control>
<cuenta_comercio>0105124565</cuenta_comercio>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>1</lote>
<monedaFin>928</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>4.000,00</monto>
<monto_divisa>0,00</monto_divisa>
<numDeposito>1234567</numDeposito>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>76</seqnum>
<tarjeta></tarjeta>
<terminal>2874900</terminal>
<tipotrx>DEPOSITO</tipotrx>
<voucher>
<linea>_</linea>
<linea><UT>DUPLICADO</UT>_</linea>
<linea>MERCANTIL_P2C</linea>
<linea>VERIFICACION_DEPOSITO</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789_</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:107_</linea>
<linea>CUENTA:0102152145__</linea>
<linea>FECHA:15/11/2022_16:56:45_</linea>
<linea>SECUENCIA:19_CAJA:VPOS_</linea>
<linea>MONTO_BS.:300,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No hay datos de control para el número indicado</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 101/176

PreRegistro de Transacción
Respuesta Exitosa – Débito Inmediato:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion></afiliacion>
<authid></authid>
<authname></authname>
<codigo>09</codigo>
<control>1583959858759191151</control>
<descripcion>La Transacción aun no fue realizada</descripcion>
<estado>P</estado>
<factura>0</factura>
<lote></lote>
<monto>0</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum></seqnum>
<tarjeta></tarjeta>
<terminal></terminal>
<vtid></vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 102/176

PreRegistro de Transacción
Respuesta Fallida – No encontró el Número de Control:
Referencia Fallida – Transacción aun no realizada:
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>202321090520</cod_afiliacion>
<afiliacion>1919289111</afiliacion>
<authname>P-MiBancoDI</authname>
<codigo>00</codigo>
<control>1735959482004513764</control>
<cuenta_cliente>0138124567</cuenta_cliente>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>9059</factura>
<lote>33</lote>
<monto>4.000.000,00</monto>
<referencia>123456</referencia>
<seqnum>30066</seqnum>
<telefonoEmisor>0424***1736</telefonoEmisor>
<terminal>9366699</terminal>
<tipotrx>DEBITO_INM_CONFIR</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>MI_BANCO_</linea>
<linea></linea>
<linea>DEBITO_INMEDIATO</linea>
<linea>UNICASA_MONTALBAN_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J-003430757_TER:9366699_</linea>
<linea>LOTE:33_REF:123456_</linea>
<linea>CELULAR:_4241431736</linea>
<linea>FECHA:03/01/2025_22:58:10_</linea>
<linea>SECUENCIA:30066_CAJA:VPOS_</linea>
<linea>CEDULA:_18602635_</linea>
<linea>MONTO_BS._:4.000.000,00</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No hay datos de control para el número indicado</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 103/176

PreRegistro de Transacción
Respuesta Exitosa – Anulación C2P:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion></afiliacion>
<authid></authid>
<authname></authname>
<codigo>09</codigo>
<control>1583959858759191151</control>
<descripcion>La Transacción aun no fue realizada</descripcion>
<estado>P</estado>
<factura>0</factura>
<lote></lote>
<monto>0</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum></seqnum>
<tarjeta></tarjeta>
<terminal></terminal>
<vtid></vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 104/176

PreRegistro de Transacción
QueryStatus V2
Esta versión de QueryStatus permite mostrar datos específicos dependiendo del tipo de transacción que sea consultada,
eso con el fin de permitir un mejor control a las integraciones a nivel contable y estadístico.
Petición
PreRegistro de Transacción
URL:
https://<ip>/payment/ws/v2/querystatus
Cabeceras:
Content-Type: text/xml
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>123456711</afiliacion>
<authid>28785</authid>
<authname>P-BancoPlazaC2P</authname>
<codigo>00</codigo>
<control>1668716422151225671</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>2</lote>
<monedaFin>928</monedaFin>
<monedaInicio>928</monedaInicio>
<monto>15,01</monto>
<monto_divisa>0,00</monto_divisa>
<referencia>233787</referencia>
<rifbanco></rifbanco>
<seqnum>20104</seqnum>
<terminal>32132112</terminal>
<tipotrx>ANULACION</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANCO_PLAZA</linea>
<linea>_ANULACION_PAGO_MOVIL</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:123456711</linea>
<linea>TER:32132112_LOTE:2_REF:233787_</linea>
<linea>NRO.TELEFONO:04121234567</linea>
<linea>FECHA:17/11/2022_16:20:28_</linea>
<linea>SECUENCIA:20104_CAJA:VPOS_</linea>
<linea>MONTO_BS.:-15,01_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 105/176

PreRegistro de Transacción
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Parametros
Nombre Tipo Descripción
cod_afilicion n..10 Código de afiliación otorgado por MegaSoft al generar credenciales de autenticación.
control n..19 Número de control generado por la petición “PreRegistro”
version n-1 Número de version del QueryStatus.
1: (DEPRECATED), se utiliza para implementaciones Antiguas.
2: Tiene la capacidad de devolver información específica dependiendo del tipo de
transaccioón que se solicite.
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>digitel</usuario>
<contrasena>d1git3l</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:querystatus>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<control>1234567890123456789</control>
<version>2</version>
<tipotrx>CREDITO</tipotrx>
</end:querystatus>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 106/176

PreRegistro de Transacción
Tipotrx a..20 Tipo de transacción a consultar, posibles consultas:
CREDITO: Tarjetas de crédito
C2P: Pago Móvil Comercio a Persona.
P2C: Pago Móvil Persona a Comercio.
CRYPTO: Solicitud de pago con Criptomonedas.
CRYPTO_CONFIR: Confirmación de pago con Criptomonedas.
BANPLUSP: Solicitud de pago con Banplus Pay´s.
BANPLUSP_CONFIR: Confirmación de pago con Banplus Pay´s.
ZELLE: Verificación de pago Zelle.
C@MBIO_PAGOMOVIL: Vuelto realizado a través de Pago Móvil.
C@MBIO_PRIVADO: Vuelto realizado a través de los bancos.
CREDITO_INMEDIATO: Transferencia entre cuentas bancarias.
DEPOSITO: Depósito a una cuenta bancaria.
ANULACION: Anulaciones de transacciones de Crédito, C2P y Banplus Pay.
Respuesta Exitosa - Crédito:
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 107/176

PreRegistro de Transacción
Respuesta Exitosa – Pago Móvil P2C:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>9876543112</afiliacion>
<authid>1183</authid>
<authname>P-Banesco</authname>
<codigo>00</codigo>
<control>1615916273294204376</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>2930</factura>
<lote>22</lote>
<marca>MasterCard</marca>
<monto>522</monto>
<referencia>73</referencia>
<rifbanco>J-07013380-5</rifbanco>
<seqnum>153</seqnum>
<tarjeta>542007 * * * * * * 9279</tarjeta>
<terminal>7775</terminal>
<tipotrx>CREDITO</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>B_A_N_E_S_C_O_J-07013380-5</linea>
<linea>COMPRA</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:9876543112</linea>
<linea>TER:7775_LOTE:22_REF:73_</linea>
<linea>NRO.CTA:542007 * * * * * * 9279_&quot;M&quot;_</linea>
<linea>FECHA:16/03/2021_13:37:50_APROB:1183_</linea>
<linea>SECUENCIA:153_CAJA:VPOS_</linea>
<linea>MONTO_BS.:5,22_</linea>
<linea>_</linea>
<linea>FIRMA:_</linea>
<linea>C.I.:__</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 108/176

PreRegistro de Transacción
Respuesta Exitosa – Banplus Pay:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>876543331</afiliacion>
<authid>00404963</authid>
<authname>P-BancoPlazaP2C</authname>
<bancoAdquiriente>0138</bancoAdquiriente>
<bancoEmisor>0138</bancoEmisor>
<codigo>00</codigo>
<control>1615916634936204392</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>23</lote>
<moneda_pago>Euros</moneda_pago>
<monto>300</monto>
<monto_divisa>0,00</monto_divisa>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>155</seqnum>
<telefonoAdquiriente>0412 * * * 3379</telefonoAdquiriente>
<telefonoEmisor>0412 * * * 0188</telefonoEmisor>
<terminal>7773</terminal>
<tipotrx>P2C</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANCO_PLAZA</linea>
<linea>PAGO_MOVIL</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:876543331</linea>
<linea>TER:7773_LOTE:23_REF:00404963_</linea>
<linea>NRO.TELEFONO:04120300188</linea>
<linea>FECHA:16/03/2021_13:44:00_</linea>
<linea>SECUENCIA:155_CAJA:VPOS_</linea>
<linea>MONTO_BS.:3,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 109/176

PreRegistro de Transacción
Respuesta Exitosa – Zelle:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionbanpluspResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>99988809</afiliacion>
<amount>4000000.00</amount>
<authid>695</authid>
<authname>P-BanplusPresidents</authname>
<cid>V18602635</cid>
<control>1615918177476204418</control>
<factura>BPbsS0AP</factura>
<lote>4</lote>
<montoDivisa>20,00</montoDivisa>
<tipo_cuenta>720</tipo_cuenta>
<referencia>695</referencia>
<rifbanco></rifbanco>
<seqnum>160</seqnum>
<terminal>88811143</terminal>
<tipoMonedaFiat>0</tipoMonedaFiat>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANPLUS</linea>
<linea>_VENTA_PRESIDENTS</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757</linea>
<linea>TER:88811143_AFIL:99988809__</linea>
<linea>FECHA:16/03/2021_14:10:04_</linea>
<linea>REFERENCIA:_695_</linea>
<linea>SECUENCIA:160_CAJA:VPOS_</linea>
<linea>MONTO_BS:_4.000.000,00_</linea>
<linea>MONTO_USD:_20,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:confirmacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 110/176

PreRegistro de Transacción
Respuesta Exitosa – C@mbio Pago Móvil:
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>888941</afiliacion>
<authid></authid>
<authname>P-Zelle</authname>
<bancoAdquiriente>BOFA</bancoAdquiriente>
<codigo>00</codigo>
<control>1650605056171217505</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>1</lote>
<monto>400000</monto>
<monto_divisa>1.000,00</monto_divisa>
<referencia>1s3d4f5g6h7j</referencia>
<rifbanco></rifbanco>
<seqnum>1274</seqnum>
<terminal>99600014</terminal>
<tipotrx>ZELLE</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>VERIFICACION_PAGO_ZELLE</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J138249197_AFIL:888941</linea>
<linea>TER:99600014_LOTE:1</linea>
<linea>REF:1S3D4F5G6H7J_</linea>
<linea>BANCO_COMERCIO:BOFA__</linea>
<linea>FECHA:22/04/2022_01:24:29_</linea>
<linea>SECUENCIA:1274_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000,00_</linea>
<linea>MONTO_USD:_1.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 111/176

PreRegistro de Transacción
Respuesta Exitosa – C@mbio Privado:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>876543331</afiliacion>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<bancoAdquiriente>0134</bancoAdquiriente>
<bancoEmisor>0138</bancoEmisor>
<codigo>00</codigo>
<control>1620307666069205801</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>23</lote>
<monto>1501</monto>
<nombre_moneda>Dolar</nombre_moneda>
<referencia>4977475</referencia>
<rifbanco></rifbanco>
<seqnum>104</seqnum>
<telefonoAdquiriente>0412*1111</telefonoAdquiriente>
<terminal>7773</terminal>
<tipotrx>C@mbio_PagoMovil</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANCO_PLAZA</linea>
<linea>__RECARGA_PAGO_MOVIL</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:876543331</linea>
<linea>TER:7773_LOTE:23_REF:4977475_</linea>
<linea>APROBACION:71764_</linea>
<linea>NRO.TELEFONO:04121111111</linea>
<linea>FECHA:06/05/2021_09:27:42_</linea>
<linea>SECUENCIA:104_CAJA:VPOS_</linea>
<linea>MONTO_BS.:15,01_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 112/176

PreRegistro de Transacción
Respuesta Exitosa - Crédito Inmediato:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>99988809</afiliacion>
<authid>60457</authid>
<authname>P-BanplusPresidents</authname>
<bancoAdquiriente>0174</bancoAdquiriente>
<bancoEmisor>0174</bancoEmisor>
<codigo>00</codigo>
<control>1625258122935213132</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>4</lote>
<monto>1501</monto>
<nombre_cuenta>Bolívares</nombre_cuenta>
<nombre_moneda>Bs</nombre_moneda>
<referencia>6776669</referencia>
<rifbanco></rifbanco>
<seqnum>190</seqnum>
<telefonoAdquiriente>0412 * * * 1111</telefonoAdquiriente>
<terminal>88811143</terminal>
<tipotrx>C@mbio_Privado</tipotrx>
<voucher>
<linea>_</linea>
<linea>_</linea>
<linea>ULTIMA_TRANS_APROBADA</linea>
<linea>_</linea>
<linea>NO_SE_IMPRIMIO_VOUCHER_ORIG_</linea>
<linea>_</linea>
<linea>C2P_</linea>
<linea>DESCONOCIDO_</linea>
<linea>FECHA:_02/07/2021_16:36:00_</linea>
<linea>LOCALIDAD:_CARACAS_</linea>
<linea>RIF_:_J003430757</linea>
<linea>CAJA:_VPOS</linea>
<linea>SECUENCIA:_190_</linea>
<linea>TARJETA:</linea>
<linea>NRO.REF:_6776669_</linea>
<linea>APROBACION:_60457_</linea>
<linea>_</linea>
<linea>MONTO_:_15,01</linea>
<linea>_</linea>
<linea>_</linea>
<linea>NO_SE_ENCONTRO_VOUCHER_ASIGNADO_</linea>
<linea>PARA_ESTA_TRANSACCION,FAVOR_</linea>
<linea>REPORTAR_AL_0212.507.76.00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 113/176

PreRegistro de Transacción
Respuesta Fallida – No encontró el Número de Control:
Referencia Fallida – Transacción aun no realizada:
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>787789</afiliacion>
<authid></authid>
<authname>P-MercantilP2C</authname>
<bancoCliente>0138</bancoCliente>
<codigo>00</codigo>
<control>1669739740488232535</control>
<cuenta_cliente>123456</cuenta_cliente>
<cuenta_comercio>010512--------4565</cuenta_comercio>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>1</lote>
<monto>400000</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>77</seqnum>
<telefonoEmisor>0412---8321</telefonoEmisor>
<terminal>2874900</terminal>
<tipotrx>CREDITO_INMEDIATO</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>MERCANTIL_P2C_</linea>
<linea>VERIFICACION_TRANSFERENCIA</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:279_</linea>
<linea>CUENTA:__</linea>
<linea>FECHA:29/11/2022_12:35:49_</linea>
<linea>SECUENCIA:77_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No hay datos de control para el número indicado</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 114/176

PreRegistro de Transacción
Respuesta Exitosa – Depósito:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion></afiliacion>
<authid></authid>
<authname></authname>
<codigo>09</codigo>
<control>1583959858759191151</control>
<descripcion>La Transacción aun no fue realizada</descripcion>
<estado>P</estado>
<factura>0</factura>
<lote></lote>
<monto>0</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum></seqnum>
<tarjeta></tarjeta>
<terminal></terminal>
<vtid></vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 115/176

PreRegistro de Transacción
Respuesta Fallida – No encontró el Número de Control:
Referencia Fallida – Transacción aun no realizada:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>787789</afiliacion>
<authid></authid>
<authname>P-MercantilP2C</authname>
<codigo>00</codigo>
<control>1669739862703232541</control>
<cuenta_comercio>0105124565</cuenta_comercio>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>1</lote>
<monto>400000</monto>
<numDeposito>1234567</numDeposito>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>78</seqnum>
<tarjeta></tarjeta>
<terminal>2874900</terminal>
<tipotrx>DEPOSITO</tipotrx>
<voucher>
<linea>_</linea>
<linea><UT>DUPLICADO</UT>_</linea>
<linea>MERCANTIL_P2C</linea>
<linea>VERIFICACION_DEPOSITO</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789_</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:107_</linea>
<linea>CUENTA:0102152145__</linea>
<linea>FECHA:15/11/2022_16:56:45_</linea>
<linea>SECUENCIA:19_CAJA:VPOS_</linea>
<linea>MONTO_BS.:300,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No hay datos de control para el número indicado</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 116/176

PreRegistro de Transacción
Respuesta Exitosa - Anulación C2P:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion></afiliacion>
<authid></authid>
<authname></authname>
<codigo>09</codigo>
<control>1583959858759191151</control>
<descripcion>La Transacción aun no fue realizada</descripcion>
<estado>P</estado>
<factura>0</factura>
<lote></lote>
<monto>0</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum></seqnum>
<tarjeta></tarjeta>
<terminal></terminal>
<vtid></vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 117/176

PreRegistro de Transacción
QueryStatus V1
Esta versión sólo se mantiene para los comercios que ya tienen realizada una implementación. Si desea tener todas las
mejoras le recomendamos utilizar “QueryStatus V2” o “QueryStatus V2.3”.
Petición
PreRegistro de Transacción
URL:
https://<ip>/payment/ws/v2/querystatus
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>201909260244</cod_afiliacion>
<afiliacion>123456711</afiliacion>
<authid>28785</authid>
<authname>P-BancoPlazaC2P</authname>
<codigo>00</codigo>
<control>1668716422151225671</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>66</factura>
<lote>2</lote>
<monto>1501</monto>
<referencia>233787</referencia>
<rifbanco></rifbanco>
<seqnum>20104</seqnum>
<terminal>32132112</terminal>
<tipotrx>ANULACION</tipotrx>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANCO_PLAZA</linea>
<linea>_ANULACION_PAGO_MOVIL</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:123456711</linea>
<linea>TER:32132112_LOTE:2_REF:233787_</linea>
<linea>NRO.TELEFONO:04121234567</linea>
<linea>FECHA:17/11/2022_16:20:28_</linea>
<linea>SECUENCIA:20104_CAJA:VPOS_</linea>
<linea>MONTO_BS.:-15,01_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 118/176

PreRegistro de Transacción
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>digitel</usuario>
<contrasena>d1git3l</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:querystatus>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<control>1234567890123456789</control>
</end:querystatus>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 119/176

PreRegistro de Transacción
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion>EV000002</afiliacion>
<authid>901589</authid>
<authname>P-Bnc</authname>
<codigo>00</codigo>
<control>1583959694663191138</control>
<descripcion>APROBADA</descripcion>
<estado>A</estado>
<factura>1051</factura>
<lote>29</lote>
<monto>522</monto>
<referencia>901589</referencia>
<rifbanco></rifbanco>
<seqnum>141</seqnum>
<tarjeta>542007 * * * * * * 9279</tarjeta>
<terminal>UGEVE001</terminal>
<voucher>
<linea>_</linea>
<linea>ORIGINAL-COMERCIO</linea>
<linea>_BANCO_NACIONAL_DE_CREDITO</linea>
<linea>_</linea>
<linea>COMPRA</linea>
<linea>_</linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>UGEVE001_LOTE:29</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:11/03/2020_16:45:59</linea>
<linea>NRO.CUENTA:542007 * * * * * * 9279_</linea>
<linea>NRO.REF:901589</linea>
<linea>APROBACION:901589_</linea>
<linea>_</linea>
<linea>CAJA:EVERTEC04_</linea>
<linea>SECUENCIA_:141_</linea>
<linea>_</linea>
<linea>MONTO_BSF_:5,22_</linea>
<linea>_</linea>
<linea>_</linea>
<linea>FIRMA:................</linea>
<linea></linea>
<linea></linea>
<linea>C.I:................</linea>
<linea></linea>
<linea>&lt;UT&gt;ULTIMA_TRANS._APROB.&lt;/UT&gt;</linea>
<linea>_</linea>
<linea>_ASUMO_LA_OBLIGACION_DE_PAGAR_</linea>
<linea>AL_BANCO_EMISOR_DE_ESTA_TARJETA_</linea>
<linea>_EL_MONTO_INDICADO_EN_ESTA_NOTA_</linea>
<linea>DE_CONSUMO.</linea>
<linea>_</linea>
<linea>__RIF:_J-30984132-7</linea>
<linea>_</linea>
</voucher>
<vtid>evertec04</vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 120/176

PreRegistro de Transacción
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Credeciales Inválidas:
Respuesta Fallida – No encontró el Número de Control:
Referencia Fallida – Transacción aun no realizada:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:querystatusResponse >
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No hay datos de control para el número indicado</descripcion>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 121/176

PreRegistro de Transacción
Transacción con Tarjeta de Crédito
Este servicio permite enviar transacciones de compra con tarjetas de crédito a los adquirientes asociados a la afiliación.
Petición
Transacción con Tarjeta de Crédito
URL:
https://<ip>/payment/ws/v2/procesar-compra
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:querystatusResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<afiliacion></afiliacion>
<authid></authid>
<authname></authname>
<codigo>09</codigo>
<control>1583959858759191151</control>
<descripcion>La Transacción aun no fue realizada</descripcion>
<estado>P</estado>
<factura>0</factura>
<lote></lote>
<monto>0</monto>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum></seqnum>
<tarjeta></tarjeta>
<terminal></terminal>
<vtid></vtid>
</return>
</ns2:querystatusResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 122/176

Transacción con Tarjeta de Crédito
Cuerpo con Encriptamiento:
Respuesta Exitosa:
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:compra>
<control>1234567890123456789<control>
<cod_afiliacion>202002170200</cod_afiliacion>
<pan>5420070123456789</pan>
<expdate>0100</expdate>
<cvv2>321</cvv2>
<cid>V12345678</cid>
<client>Pedro Perez</client>
<amount>123456</amount>
<factura>123</factura>
</end:compra>
</soapenv:Body>
XML
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:compra>
<control>1234567890123456789<control>
<cod_afiliacion>202002170200</cod_afiliacion>
<pan>sOxnPn7fvUXdOdJFGu+OCZSXJwFDwVFNbTgVCXBGfFU=</pan>
<expdate>1ZrRHmVW8wrYkIO1eFxNxA==</expdate>
<cvv2>crIUGIcsuqBkw/lC8VfYYQ==</cvv2>
<cid>e4j8i145mfkgkiCMJb2shA==</cid>
<client>Pedro Perez</client>
<amount>123456</amount>
<factura>123</factura>
</end:compra>
</soapenv:Body>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 123/176

Transacción con Tarjeta de Crédito
Respuesta Fallida – Credenciales Inválidas:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compraResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>EV000002</afiliacion>
<authid>901595</authid>
<authname>P-Bnc</authname>
<control>1583961328684191188</control>
<factura>1057</factura>
<lote>29</lote>
<marca>MasterCard</marca>
<referencia>901595</referencia>
<rifbanco></rifbanco>
<seqnum>147</seqnum>
<tarjeta>542007 * * * * * * 9279</tarjeta>
<terminal>UGEVE001</terminal>
<voucher>
<linea>_</linea>
<linea>ORIGINAL-COMERCIO</linea>
<linea>_BANCO_NACIONAL_DE_CREDITO</linea>
<linea>_</linea>
<linea>COMPRA</linea>
<linea>_</linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>UGEVE001_LOTE:29</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:11/03/2020_17:13:12</linea>
<linea>NRO.CUENTA:542007 * * * * * * 9279_</linea>
<linea>NRO.REF:901595</linea>
<linea>APROBACION:901595_</linea>
<linea>_</linea>
<linea>CAJA:EVERTEC04_</linea>
<linea>SECUENCIA_:147_</linea>
<linea>_</linea>
<linea>MONTO_BSF_:5,22_</linea>
<linea>_</linea>
<linea>_</linea>
<linea>FIRMA:................</linea>
<linea></linea>
<linea></linea>
<linea>C.I:................</linea>
<linea></linea>
<linea>&lt;UT&gt;ULTIMA_TRANS._APROB.&lt;/UT&gt;</linea>
<linea>_</linea>
<linea>_ASUMO_LA_OBLIGACION_DE_PAGAR_</linea>
<linea>AL_BANCO_EMISOR_DE_ESTA_TARJETA_</linea>
<linea>_EL_MONTO_INDICADO_EN_ESTA_NOTA_</linea>
<linea>DE_CONSUMO.</linea>
<linea>_</linea>
<linea>__RIF:_J-30984132-7</linea>
<linea>_</linea>
</voucher>
<vtid>evertec04</vtid>
</return>
</ns2:compraResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 124/176

Transacción con Tarjeta de Crédito
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compraResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:compraResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compraResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:compraResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 125/176

Transacción con Tarjeta de Crédito
Respuesta Fallida – Número de Control Inválido:
Transacción con Tarjeta de Crédito
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compraResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>39</codigo>
<descripcion>No es cuenta de credito</descripcion>
<afiliacion>EV000002</afiliacion>
<authid></authid>
<authname>P-Bnc</authname>
<factura>782</factura>
<lote>21</lote>
<marca>MasterCard</marca>
<referencia>901451</referencia>
<rifbanco></rifbanco>
<seqnum>187</seqnum>
<tarjeta>542007 * * * * * * 9279</tarjeta>
<terminal>UGEVE001</terminal>
<voucher>
<linea>_</linea>
<linea>_</linea>
<linea>_BANCO_NACIONAL_DE_CREDITO</linea>
<linea>_</linea>
<linea>_COMPRA</linea>
<linea></linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>EV000002_</linea>
<linea>UGEVE001LOTE:21_</linea>
<linea>_</linea>
<linea>R.I.F:J-00000002-0</linea>
<linea>FECHA:17/02/2020_14:13:09</linea>
<linea>NRO.CUENTA:542007 * * * * * *9279_</linea>
<linea>NRO.REF:901451</linea>
<linea>_</linea>
<linea>CAJA:EVERTEC04_</linea>
<linea>SECUENCIA_:187_</linea>
<linea>_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>NO_ES_CUENTA_DE_CREDITO</linea>
<linea>_</linea>
<linea>_RIF:_J-30984132-7</linea>
<linea>_</linea>
</voucher>
<vtid>evertec04</vtid>
</return>
</ns2:compraResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compraResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:compraResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 126/176

Este servicio permite enviar transacciones de pre autorización con tarjetas de crédito a los adquirientes asociados a la
afiliación.
Petición
Transacción de Pre - Autorización
Url:
https://<ip>/payment/ws/v2/preautorizacion
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Cuerpo con Encriptamiento:
<soapenv:Envelope
xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:preautorizacion>
<control>1234567890123456789</control>
<cod_afiliacion>2015100901</cod_afiliacion>
<pan>5406282515946732</pan>
<expdate>0519</expdate>
<cvv2>422</cvv2>
<cid>V123654</cid>
<client>Pedro Perez</client>
<amount>123456</amount>
<factura>010101</factura>
<field1>CA1</field1>
<field2>CA2</field2>
</end:preautorizacion>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 127/176

Transacción de Pre - Autorización
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:preautorizacion>
<cod_afiliacion>2015100901</cod_afiliacion>
<pan>sOxnPn7fvUXdOdJFGu+OCZSXJwFDwVFNbTgVCXBGfFU=</pan>
<expdate>1ZrRHmVW8wrYkIO1eFxNxA==</expdate>
<cvv2>crIUGIcsuqBkw/lC8VfYYQ==</cvv2>
<cid>e4j8i145mfkgkiCMJb2shA==</cid>
<client>Pedro Perez</client>
<amount>123456</amount>
<factura>010101</factura>
<field1>CA1</field1>
<field2>CA2</field2>
</end:preautorizacion>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preautorizacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>TRANS. APROBADA</descripcion>
<afiliacion>860084789</afiliacion>
<authid>2768</authid>
<authname>P-Banesco</authname>
<control>1234567890123456789</control>
<lote>13</lote>
<marca>MasterCard</marca>
<referencia>54</referencia>
<rifbanco>J-07013380-5</rifbanco>
<seqnum>150</seqnum>
<tarjeta>540628 * * * * * * 6732</tarjeta>
<terminal>10203040</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT>__DUPLICADO&lt;/UT></linea>
<linea>B_A_N_E_S_C_O_J-07013380-5</linea>
<linea>__PREAUTORIZACION</linea>
<linea>LABORATORIO_DE_PRUEBAS_</linea>
<linea>CHAGUARAMOS_</linea>
<linea>RIF:J-00343075-7_AFIL:860084789</linea>
<linea>TER:10203040_LOTE:13_REF:54_</linea>
<linea>NRO.CTA:540628 * * * * * * 6732_'M'</linea>
<linea>FECHA:30/11/2017_14:13:56_APROB:2768</linea>
<linea>SECUENCIA:150_CAJA:NAGEL01_</linea>
<linea>MONTO_BS.:1.234,56_</linea>
<linea>_</linea>
<linea>FIRMA:_</linea>
<linea>C.I.:__</linea>
</voucher>
<vtid>nagel01</vtid>
</return>
</ns2:preautorizacionResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 128/176

Transacción de Pre - Autorización
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preautorizacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:preautorizacionResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preautorizacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:preautorizacionResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 129/176

Transacción de Pre - Autorización
Respuesta Fallida – Número de Control Inválido:
Transacción de Pago Móvil C2P
Este servicio permite enviar pagos a través del número telefónico y la cuenta bancaria asociada a este.
Petición
Transacción de Pago Móvil C2P
URL:
https://<ip>/payment/ws/v2/procesar-compra-c2p
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preautorizacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>39</codigo>
<descripcion>NO ES CTA CREDITO</descripcion>
<afiliacion>860084789</afiliacion>
<authid/>
<authname>P-Banesco</authname>
<lote>13</lote>
<marca>MasterCard</marca>
<referencia>57</referencia>
<rifbanco>J-07013380-5</rifbanco>
<seqnum>153</seqnum>
<tarjeta>540628 * * * * * * * * 3211</tarjeta>
<terminal>10203040</terminal>
<voucher>
<linea>_</linea>
<linea>B_A_N_E_S_C_O_J-07013380-5</linea>
<linea>PREAUTORIZACION</linea>
<linea>LABORATORIO_DE_PRUEBAS_</linea>
<linea>CHAGUARAMOS_</linea>
<linea>RIF:J-00343075-7_AFIL:860084789</linea>
<linea>TER:10203040_LOTE:13_REF:57_</linea>
<linea>NRO.CTA:540628 * * * * * * * * 3211_'M'</linea>
<linea>FECHA:30/11/2017_14:22:45</linea>
<linea>SECUENCIA:153_CAJA:NAGEL01_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>NO_ES_CTA_CREDITO</linea>
<linea>__</linea>
</voucher>
<vtid>nagel01</vtid>
</return>
</ns2:preautorizacionResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:preautorizacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:preautorizacionResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 130/176

Transacción de Pago Móvil C2P
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:comprac2p>
<control>1234567890123456789</control>
<cod_afiliacion>202002170219</cod_afiliacion>
<cid>v18467420</cid>
<telefono>04121234567</telefono>
<codigobanco>0102</codigobanco>
<codigoc2p>87654321</codigoc2p>
<amount>5260000</amount>
<factura>66</factura>
</end:comprac2p>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 131/176

Transacción de Pago Móvil C2P
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprac2pResponsexmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>12345678</afiliacion>
<authid></authid>
<authname>P-BncC2P</authname>
<control>1234567890123456789</control>
<factura>66</factura>
<lote>0</lote>
<referencia></referencia>
<rifbanco>J-30984132-7</rifbanco>
<seqnum>30</seqnum>
<terminal>C2PEVE03</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>_BANCO_NACIONAL_DE_CREDITO</linea>
<linea>PAGO_MOVIL</linea>
<linea>EVERTEC_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J-00000002-0_AFIL:12345678</linea>
<linea>TER:C2PEVE03_LOTE:1_REF:27_</linea>
<linea>NRO.TELEFONO:04121234567</linea>
<linea>FECHA:27/09/2019_16:13:51_APROB:960844_</linea>
<linea>SECUENCIA:30_CAJA:EVERTEC04_</linea>
<linea>MONTO_BS._:52.600,00</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>evertec04</vtid>
</return>
</ns2:comprac2pResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprac2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:comprac2pResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 132/176

Transacción de Pago Móvil C2P
Respuesta Fallida – Error Procesando la Transacción:
Respuesta Fallida – Número de Control Inválido:
Transacción de Pago Móvil P2C
Este servicio permite consultar pagos realizados por el cliente contra un número telefónico asociado a una cuenta
bancaria.
Petición
Transacción de Pago Móvil P2C
URL:
https://<ip>/payment/ws/v2/procesar-compra-p2c
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprac2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de
autenticación</desripcion>
</return>
</ns2:comprac2pResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprac2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>transacción de compra no ejecutada</descripcion>
</return>
</ns2:comprac2pResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprac2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:comprac2pResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 133/176

Transacción de Pago Móvil P2C
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:comprap2c>
<control>1234567890123456789</control>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<telefonoCliente>04121234567</telefonoCliente>
<codigobancoCliente>0102</codigobancoCliente>
<telefonoComercio>04147654321</telefonoComercio>
<codigobancoComercio>0116</codigobancoComercio>
<amount>0.01</amount>
<tipoPago>10</tipoPago>
<factura>66</factura>
</end:comprap2c>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 134/176

Transacción de Pago Móvil P2C
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprap2cResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>1234567888</afiliacion>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<control>1234567890123456789</control>
<factura>66</factura>
<lote>6</lote>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>5035</seqnum>
<terminal>32165412</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANCO_PLAZA</linea>
<linea>PAGO_MOVIL</linea>
<linea>P2C_BANCO_PLAZA_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J0123456789_AFIL:1234567888</linea>
<linea>TER:32165412_LOTE:6_REF:246605630008_</linea>
<linea>NRO.TELEFONO:04249998877</linea>
<linea>FECHA:12/03/2020_09:06:04_</linea>
<linea>SECUENCIA:5035_CAJA:BANCOPLAZAP2C_</linea>
<linea>MONTO_BS.:1.250,75_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>bancoplazap2c</vtid>
</return>
</ns2:comprap2cResponse>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprap2cResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:comprap2cResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 135/176

Transacción de Pago Móvil P2C
Respuesta Fallida – Error Procesando la Transacción:
Respuesta Fallida – Número de Control Inválido:
Transacción de Criptomonedas - Logon
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprap2cResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales deautenticación</desripcion>
</return>
</ns2:comprap2cResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprap2cResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>PC</codigo>
<descripcion>Referencia utilizada en otra compra</descripcion>
<afiliacion>1234567888</afiliacion>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<factura>66</factura>
<lote>6</lote>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>5029</seqnum>
<terminal>32165412</terminal>
<voucher>
<linea>_</linea>
<linea>BANCO_PLAZA</linea>
<linea>_COMPRA</linea>
<linea>P2C_BANCO_PLAZA</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J0123456789_AFIL:1234567888</linea>
<linea>TER:32165412_LOTE:6_REF:00404963_</linea>
<linea>NRO.TELEFONO:04120300188_</linea>
<linea>FECHA:12/03/2020_08:48:12_</linea>
<linea>SECUENCIA:5029_CAJA:BANCOPLAZAP2C_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>REFERENCIA_UTILIZADA_EN_OTRA_COMPRA</linea>
</voucher>
<vtid>bancoplazap2c</vtid>
</return>
</ns2:comprap2cResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprap2cResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:comprap2cResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 136/176

Este servicio permite recuperar la lista de monedas disponibles en el momento de realizar la compra.
Petición
Transacción de Criptomonedas - Logon
URL:
https://<ip>/payment/ws/v2/procesar-crypto-get
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:logoncrypto>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
</end:logoncrypto>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:logoncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<listaCriptomonedas>BNB ,BTC ,DAI ,DASH ,ETH ,LTC ,USDT
</listaCriptomonedas>
<listaCriptomonedas>Binance Coin,Bitcoin,DAI
Stablecoin,Dash,Ethereum,Litecoin,Tether USD</listaCriptomonedas>
<vtid>crypto</vtid>
</return>
</ns2:logoncryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 137/176

Transacción de Criptomonedas - Logon
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
Transacción de Criptomonedas - Solicitud
Este servicio permite realizar la petición para ejecutar un pago con criptomendas. En donde por medio del monto en
bolívares y la selección del tipo de criptomoneda, tendrá como respuesta el valor en la moneda elegida, aparte de la
referencia y código QR, necesarios para el proceso de confirmación del pago.
Petición
Transacción de Criptomonedas - Solicitud
URL:
https://<ip>/payment/ws/v2/procesar-crypto-auth
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:logoncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:logoncryptoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:logoncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:logoncryptoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:logoncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>AG</codigo>
<descripcion> No se pudo realizar logon </descripcion>
</return>
</ns2:logoncryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 138/176

Transacción de Criptomonedas - Solicitud
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:solicitudcrypto>
<control>1234567890123456789</control>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
<amount>4000000.00</amount>
<tipo_moneda>BTC</tipo_moneda>
<factura>66</factura>
</end:solicitudcrypto>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudcryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADO</descripcion>
<amount>4000000.00</amount>
<control>1595171350596199353</control>
<factura>66</factura>
<montoCrypto>0.00229736</montoCrypto>
<nombreMoneda>Bitcoin</nombreMoneda>
<qrURL>&lt;![CDATA[https://chart.googleapis.com/chart?
chs=250x250&amp;cht=qr&amp;chl=bitcoin:1N98VEwsAUrbtHaPqphrf2LGQ3KYAr12jC?amount=0.00229736]]&gt;</qrURL>
<referencia>d72b0b8a-3ad4-4e2d-b354-a04f53907b3a</referencia>
<rifbanco></rifbanco>
<seqnum>1362</seqnum>
<tipoCryptoMoneda>BTC</tipoCryptoMoneda>
<vtid>vpos</vtid>
</return>
</ns2:solicitudcryptoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudcryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:solicitudcryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 139/176

Transacción de Criptomonedas - Solicitud
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
Transacción de Criptomonedas - Confirmación
Este servicio permite realizar la petición de confirmación de pago con criptomendas. Debe enviarse con el mismo
número de control que la solicitud, luego de que el usuario realizara el pago a través de su criptobilletera.
Petición
Transacción de Criptomonedas - Confirmación
URL:
https://<ip>/payment/ws/v2/procesar-crypto-confir
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudcryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:solicitudcryptoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudcryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>Y9</codigo>
<descripcion>Se alcanzo Monto Maximo del Lote Debe Hacer Cierre</descripcion>
<amount>2000000.00</amount>
<control>1595133029027199276</control>
<factura>16</factura>
<montoCrypto>0.00000000</montoCrypto>
<nombreMoneda>Bitcoin</nombreMoneda>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>1359</seqnum>
<tipoCryptoMoneda>BTC</tipoCryptoMoneda>
<vtid>vpos</vtid>
</return>
</ns2:solicitudcryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 140/176

Transacción de Criptomonedas - Confirmación
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:confirmacioncrypto>
<control>1234567890123456789</control>
<cod_afiliacion>1234567890123456789</cod_afiliacion>
</end:confirmacioncrypto>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacioncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADO</descripcion>
<afiliacion>67512300</afiliacion>
<amount>4000000.00</amount>
<authname>P-Cryptobuyer</authname>
<control>1595172846459199371</control>
<factura>66</factura>
<lote>1</lote>
<montoCrypto>0.00229667</montoCrypto>
<nombreMoneda>Bitcoin</nombreMoneda>
<qrURL>&lt;![CDATA[https://chart.googleapis.com/chart?
chs=250x250&amp;cht=qr&amp;chl=bitcoin:16JerZ2ryv337eGyXq5QXrooD3SP4cD1Wi?amount=0.00229667]]&gt;</qrURL>
<referencia>8a7532ac-04dc-48af-a80b-fabe880c1ba5</referencia>
<rifbanco></rifbanco>
<seqnum>7306</seqnum>
<terminal>67512300</terminal>
<tipoCryptoMoneda>BTC</tipoCryptoMoneda>
<voucher>
<linea>_</linea> <linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>CRYPTOBUYER</linea>
<linea>PAGO_CRIPTOMONEDA_</linea>
<linea>CRYPTO_HJ_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J123456789</linea>
<linea>TER:67512300_AFIL:67512300</linea>
<linea>FECHA:19/07/2020_11:05:03_</linea>
<linea>ID:_FABE880C1BA5_</linea>
<linea>SECUENCIA:7306_CAJA:CRYPTO_</linea>
<linea>MONTO_BS.:4.000.000,00_</linea>
<linea>MONTO_FIAT:_20,00_USD_</linea>
<linea>MONTO_CRIPTO:0.00229667_BTC</linea>
<linea>_</linea>
</voucher>
<vtid>crypto</vtid>
</return>
</ns2:confirmacioncryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 141/176

Transacción de Criptomonedas - Confirmación
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacioncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:confirmacioncryptoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacioncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:confirmacioncryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 142/176

Transacción de Criptomonedas - Confirmación
Transacción de Banplus Pay - Solicitud
Este servicio ejecuta la petición para realizar un pago con Banplus Pay. En donde por medio del monto en bolívares, la
selección del tipo de moneda Fiat a utilizar y el tipo de cuenta, tendrá como respuesta el valor en la moneda elegida.
Petición
Transacción Banplus Pay - Solicitud
URL:
https://<ip>/payment/ws/v2/procesar-banplusp-auth
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacioncryptoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>MF</codigo>
<descripcion>Pago por monto inferior</descripcion>
<afiliacion>67512300</afiliacion>
<amount>2000000.00</amount>
<authname>P-Cryptobuyer</authname>
<control>1595171427644199367</control>
<factura>66</factura>
<lote>1</lote>
<montoCrypto>0.00114930</montoCrypto>
<nombreMoneda>Bitcoin</nombreMoneda>
<qrURL>&lt;![CDATA[https://chart.googleapis.com/chart?
chs=250x250&amp;cht=qr&amp;chl=bitcoin:1MLToS1veFRWE33G2Dab4UoSngZt6BvCBz?amount=0.00114930]]&gt;</qrURL>
<referencia>deff03b5-84fc-4170-b999-e05e51fab803</referencia>
<rifbanco></rifbanco>
<seqnum>7304</seqnum>
<terminal>67512300</terminal>
<tipoCryptoMoneda>BTC</tipoCryptoMoneda>
<voucher>
<linea>_</linea>
<linea>CRYPTOBUYER</linea>
<linea>_PAGO_CRIPTOMONEDA</linea>
<linea>CRYPTO_HJ_</linea>
<linea>MONTALBAN_</linea>
<linea>RIF:J123456789_AFIL:67512300</linea>
<linea>TER:67512300_</linea>
<linea>FECHA:19/07/2020_10:41:18_</linea>
<linea>ID:_E05E51FAB803_</linea>
<linea>SECUENCIA:7304_CAJA:CRYPTO_</linea>
<linea>TRANSACCION_FALLIDA:_</linea>
<linea>PAGO_POR_MONTO_INFERIOR__</linea>
</voucher>
<vtid>crypto</vtid>
</return>
</ns2:confirmacioncryptoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 143/176

Transacción Banplus Pay - Solicitud
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:solicitudbanplusp>
<control>1234567890123456789</control>
<cod_afiliacion>12345678</cod_afiliacion>
<amount>4000000.00</amount>
<cid>V123456</cid>
<tipo_moneda>840</tipo_moneda>
<tipo_cuenta>720</tipo_cuenta>
<factura>12345678</factura>
</end:solicitudbanplusp>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<amount>400000000</amount>
<authid></authid>
<authname></authname>
<cid>V123456</cid>
<control>1234567890123456789</control>
<factura>123456</factura>
<montoDivisa>1.333,00</montoDivisa>
<rifbanco></rifbanco>
<seqnum>1536</seqnum>
<tipoMonedaFiat>840</tipoMonedaFiat>
<tipo_cuenta>720</tipo_cuenta>
<vtid>vpos</vtid>
</return>
</ns2:solicitudbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 144/176

Transacción Banplus Pay - Solicitud
Respuesta Fallida – Sin Header en la Petición:
Transacción de Banplus Pay - Confirmación
Este servicio permite realizar la petición de confirmación de pago con Banplus Pay. Debe enviarse con el mismo
número de control que la solicitud. Si el usuario validó el código OTP desde su aplicación bancaria, el campo ‘cod_otp’
puede ser enviado en blanco.
Petición
Transacción Banplus Pay - Confirmación
URL:
https://<ip>/payment/ws/v2/procesar-banplusp-confir
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:solicitudbanpluspResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:solicitudbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 145/176

Transacción Banplus Pay - Confirmación
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:confirmacionbanplusp>
<control>1234567890123456789</control>
<cod_afiliacion>123456</cod_afiliacion>
<cod_otp>123456</cod_otp>
</end:confirmacionbanplusp>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionbanpluspResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>99988809</afiliacion>
<amount>4000000.00</amount>
<authid>5563</authid>
<authname>P-BanplusPresidents</authname>
<cid>V123456</cid>
<control>1603465177076201743</control>
<factura>B4np1u5P</factura>
<lote>2</lote>
<montoDivisa>0,00</montoDivisa>
<referencia>5563</referencia>
<rifbanco></rifbanco>
<seqnum>1708</seqnum>
<terminal>88811143</terminal>
<tipoMonedaFiat>840</tipoMonedaFiat>
<tipo_cuenta>720</tipo_cuenta>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANPLUS</linea>
<linea>_VENTA_PRESIDENTS</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757</linea>
<linea>TER:88811143_AFIL:9998809__</linea>
<linea>FECHA:23/10/2020_10:26:24_</linea>
<linea>REFERENCIA:_5563_</linea>
<linea>SECUENCIA:1708_CAJA:VPOS_</linea>
<linea>MONTO_BS:_4.000.000,00_</linea>
<linea>MONTO_USD:_20,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:confirmacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 146/176

Transacción Banplus Pay - Confirmación
Respuesta Fallida – Sin Header en la Petición:
Transacción de Zelle
Este servicio permite realizar la validación de un pago realizado a través de Zelle.
Petición
Transacción Zelle
URL:
https://<ip>/payment/ws/v2/procesar-compra-zelle
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionbanpluspResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:confirmacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionbanpluspResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:confirmacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 147/176

Transacción Zelle
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:comprazelle>
<control>1234567890123456789</control>
<cod_afiliacion>123456</cod_afiliacion>
<cid>v6457425</cid>
<codigobancoComercio>BOFA</codigobancoComercio>
<referencia>1s3d4f5g6h7j</referencia>
<amount>4000.00</amount>
<factura>66</factura>
</end:comprazelle>
</soapenv:Body>
</soapenv:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprazelleResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>888941</afiliacion>
<authid></authid>
<authname>P-Zelle</authname>
<banco>61</banco>
<cid>v6457425</cid>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>66</factura>
<lote>1</lote>
<referencia>1s3d4f5g6h7j</referencia>
<rifbanco></rifbanco>
<seqnum>1274</seqnum>
<terminal>99600014</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>VERIFICACION_PAGO_ZELLE</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J138249197_AFIL:888941</linea>
<linea>TER:99600014_LOTE:1</linea>
<linea>REF:1S3D4F5G6H7J_</linea>
<linea>BANCO_COMERCIO:BOFA__</linea>
<linea>FECHA:22/04/2022_01:24:29_</linea>
<linea>SECUENCIA:1274_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000,00_</linea>
<linea>MONTO_USD:_1.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:comprazelleResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 148/176

Transacción Zelle
Respuesta Fallida – Sin Header en la Petición:
Transacción de Crédito Inmediato
Este servicio realiza la verificación de la transferencia realizada a través del Crédito Inmediato.
Petición
Transacción Crédito Inmediato
URL:
https://<ip>/payment/ws/v2/procesar-compra-creditoinmediato
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprazelleResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:comprazelleResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:comprazelleResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:comprazelleResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 149/176

Transacción Crédito Inmediato
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:compratransferencia>
<control>1234567890123456789</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<cid>v6457425</cid>
<cuentaOrigen>123456</cuentaOrigen>
<telefonoOrigen>04129568321</telefonoOrigen>
<codigobancoOrigen>0138</codigobancoOrigen>
<cuentaDestino>01051234567895214565</cuentaDestino>
<amount>4000.00</amount>
<factura>66</factura>
</end:compratransferencia>
</soapenv:Body>
</soapenv:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compratransferenciaResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>787789</afiliacion>
<authid></authid>
<authname>P-MercantilP2C</authname>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>66</factura>
<lote>1</lote>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>77</seqnum>
<terminal>2874900</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>MERCANTIL_P2C_</linea>
<linea>VERIFICACION_TRANSFERENCIA</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:279_</linea>
<linea>CUENTA:__</linea>
<linea>FECHA:29/11/2022_12:35:49_</linea>
<linea>SECUENCIA:77_CAJA:VPOS_</linea>
<linea>MONTO_BS.:4.000,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:compratransferenciaResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 150/176

Transacción Crédito Inmediato
Respuesta Fallida – Sin Header en la Petición:
Transacción de Débito Inmediato (Solicitud)
Este servicio realiza la solicitud de la transacción de Débito Inmediato.
Petición
Transacción Crédito Inmediato
URL:
https://<ip>/payment/ws/v2/procesar-debitoinmediato-auth
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compratransferenciaResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:compratransferenciaResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compratransferenciaResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:compratransferenciaResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 151/176

Transacción Crédito Inmediato
 
Para procesar la transacción se debe usar o la cuentaCuente o el telefonoCliente, de usar ambos,
se tomará siempre el teléfono como el dato para procesar la transacción.
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASENA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:solicitudDebitoInmediato>
<control>1735959482004513764</control>
<cod_afiliacion>1234567</cod_afiliacion>
<amount>4000.00</amount>
<cid>V18602635</cid>
<codigobancoCliente>0138</codigobancoCliente>
<cuentaCliente>1234567891234567</cuentaCliente>
<telefonoCliente>04241431736</telefonoCliente>
<factura>1408</factura>
</end:solicitudDebitoInmediato>
</soapenv:Body>
</soapenv:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:solicitudDebitoInmediatoResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>1919289111</afiliacion>
<amount>4000000.00</amount>
<authid></authid>
<authname>P-MiBancoDI</authname>
<control>1735959482004513764</control>
<factura>9059</factura>
<lote>33</lote>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>30065</seqnum>
<terminal>9366699</terminal>
<vtid>vpos</vtid>
</return>
</ns2:solicitudDebitoInmediatoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 152/176

Transacción Crédito Inmediato
Respuesta Fallida – Sin Header en la Petición:
Transacción de Débito Inmediato (Confirmación)
Este servicio realiza la confirmación de la transacción de Débito Inmediato.
Petición
Transacción Débito Inmediato (Confirmación)
URL:
https://<ip>/payment/ws/v2/procesar-debitoinmediato-confir
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionDebitoInmediatoResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:confirmacionDebitoInmediatoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionDebitoInmediatoResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:confirmacionDebitoInmediatoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 153/176

Transacción Débito Inmediato (Confirmación)
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASENA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:confirmacionDebitoInmediato>
<control>1735959482004513764</control>
<cod_afiliacion>1234567</cod_afiliacion>
<cod_otp>123456</cod_otp>
</end:confirmacionDebitoInmediato>
</soapenv:Body>
</soapenv:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionDebitoInmediatoResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>1919289111</afiliacion>
<amount>4000000.00</amount>
<authid>0</authid>
<authname>P-MiBancoDI</authname>
<control>1735959482004513764</control>
<factura>9059</factura>
<lote>33</lote>
<referencia>123456</referencia>
<rifbanco></rifbanco>
<seqnum>30066</seqnum>
<terminal>9366699</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>MI_BANCO_</linea>
<linea></linea>
<linea>DEBITO_INMEDIATO</linea>
<linea>UNICASA_MONTALBAN_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J-003430757_TER:9366699_</linea>
<linea>LOTE:33_REF:123456_</linea>
<linea>CELULAR:_4241431736</linea>
<linea>FECHA:03/01/2025_22:58:10_</linea>
<linea>SECUENCIA:30066_CAJA:VPOS_</linea>
<linea>CEDULA:_18602635_</linea>
<linea>MONTO_BS._:4.000.000,00</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:confirmacionDebitoInmediatoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 154/176

Transacción Débito Inmediato (Confirmación)
Respuesta Fallida – Sin Header en la Petición:
Transacción de Depósito
Este servicio realiza la verificación del depósito realizado a una cuenta a través del Depósito.
Petición
Transacción Depósito
URL:
https://<ip>/payment/ws/v2/procesar-compra-deposito
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionDebitoInmediatoResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:confirmacionDebitoInmediatoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:confirmacionDebitoInmediatoResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:confirmacionDebitoInmediatoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 155/176

Transacción Depósito
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:compradeposito>
<control>1234567890123456789</control>
<cod_afiliacion>201909260244</cod_afiliacion>
<cid>v6457425</cid>
<numDeposito>1234567</numDeposito>
<cuentaDestino>01051234567895214565</cuentaDestino>
<amount>4000.00</amount>
<factura>66</factura>
</end:compradeposito>
</soapenv:Body>
</soapenv:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compradepositoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>787789</afiliacion>
<authid></authid>
<authname>P-MercantilP2C</authname>
<cid>v6457425</cid>
<cod_afiliacion>201909260244</cod_afiliacion>
<factura>66</factura>
<lote>1</lote>
<referencia></referencia>
<rifbanco></rifbanco>
<seqnum>78</seqnum>
<terminal>2874900</terminal>
<voucher>
<linea>_</linea>
<linea><UT>DUPLICADO</UT>_</linea>
<linea>MERCANTIL_P2C</linea>
<linea>VERIFICACION_DEPOSITO</linea>
<linea>PROCESA_CREDICARD2_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:787789_</linea>
<linea>TER:2874900_LOTE:1</linea>
<linea>REF:107_</linea>
<linea>CUENTA:0102152145__</linea>
<linea>FECHA:15/11/2022_16:56:45_</linea>
<linea>SECUENCIA:19_CAJA:VPOS_</linea>
<linea>MONTO_BS.:300,00_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:compratransferenciaResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 156/176

Transacción Depósito
Respuesta Fallida – Sin Header en la Petición:
Transacción de C@mbio Pago Móvil
Este servicio ejecuta la petición para realizar un vuelto con Pago Móvil. En donde por medio del monto en bolívares y
la selección del tipo de moneda Fiat a utilizar, se realizará la devolución a un cliente.
Petición
Transacción de C@mbio Pago Móvil
URL:
https://<ip>/payment/ws/v2/procesar-cambio-pagomovil
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compratransferenciaResponse
xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:compratransferenciaResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:compratransferenciaResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:compratransferenciaResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 157/176

Transacción de C@mbio Pago Móvil
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:cambiopagomovil>
<control>123456789012345678977</control>
<cod_afiliacion>12345678</cod_afiliacion>
<cid>v1234567</cid>
<telefono>04121234567</telefono>
<codigobanco>0102</codigobanco>
<tipo_moneda>0</tipo_moneda>
<amount>15.00</amount>
<factura>66</factura>
</end:cambiopagomovil>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambiopagomovilResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>876543331</afiliacion>
<authid></authid>
<authname>P-BancoPlazaP2C</authname>
<control>123456789012345678977</control>
<factura>66</factura>
<lote>23</lote>
<nombreMoneda>Bs</nombreMoneda>
<referencia>3374702</referencia>
<rifbanco></rifbanco>
<seqnum>102</seqnum>
<terminal>7773</terminal>
<tipoMonedaFiat>0</tipoMonedaFiat>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANCO_PLAZA</linea>
<linea>__RECARGA_PAGO_MOVIL</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757_AFIL:876543331</linea>
<linea>TER:7773_LOTE:23_REF:3374702_</linea>
<linea>APROBACION:29024_</linea>
<linea>NRO.TELEFONO:04121234567</linea>
<linea>FECHA:06/05/2021_09:22:25_</linea>
<linea>SECUENCIA:102_CAJA:VPOS_</linea>
<linea>MONTO_BS.:0,01_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:cambiopagomovilResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 158/176

Transacción de C@mbio Pago Móvil
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Número de Control Inválido:
Transacción de C@mbio Privado
Este servicio ejecuta la petición para realizar un vuelto con bancos privados. En donde por medio del monto en
bolívares, dólares o euros, la selección del tipo de moneda Fiat y la selección de tipo de cuenta, se realizará la
devolución a un cliente.
Petición
Transacción de C@mbio Privado
URL:
https://<ip>/payment/ws/v2/procesar-cambio-privado
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambiopagomovilResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:cambiopagomovilResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambiopagomovilResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:cambiopagomovilResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambiopagomovilResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:cambiopagomovilResponse >
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 159/176

Transacción de C@mbio Privado
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:cambioprivado>
<control>1234567890123456789</control>
<cod_afiliacion>12345678</cod_afiliacion>
<cid>v1234567</cid>
<telefono>04121111111</telefono>
<codigobanco>0174</codigobanco>
<tipo_moneda>0</tipo_moneda>
<tipo_cuenta>10</tipo_cuenta>
<amount>15.01</amount>
<factura>66</factura>
</end:cambioprivado>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 160/176

Transacción de C@mbio Privado
Respuesta Fallida – Credenciales Inválidas:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambioprivadoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>99988809</afiliacion>
<authid>60457</authid>
<authname>P-BanplusPresidents</authname>
<control>1625258122935213132</control>
<factura>66</factura>
<lote>4</lote>
<nombreCuenta>Bolívares</nombreCuenta>
<nombreMoneda>Bs</nombreMoneda>
<referencia>6776669</referencia>
<rifbanco></rifbanco>
<seqnum>190</seqnum>
<terminal>88811143</terminal>
<voucher>
<linea>_</linea>
<linea>_</linea>
<linea>ULTIMA_TRANS_APROBADA</linea>
<linea>_</linea>
<linea>NO_SE_IMPRIMIO_VOUCHER_ORIG_</linea>
<linea>_</linea>
<linea>C2P_</linea>
<linea>DESCONOCIDO_</linea>
<linea>FECHA:_02/07/2021_16:36:00_</linea>
<linea>LOCALIDAD:_CARACAS_</linea>
<linea>RIF_:_J003430757</linea>
<linea>CAJA:_VPOS</linea>
<linea>SECUENCIA:_190_</linea>
<linea>TARJETA:</linea>
<linea>NRO.REF:_6776669_</linea>
<linea>APROBACION:_60457_</linea>
<linea>_</linea>
<linea>MONTO_:_15,01</linea>
<linea>_</linea>
<linea>_</linea>
<linea>NO_SE_ENCONTRO_VOUCHER_ASIGNADO_</linea>
<linea>PARA_ESTA_TRANSACCION,FAVOR_</linea>
<linea>REPORTAR_AL_0212.507.76.00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:cambioprivadoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambioprivadoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:cambioprivadoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 161/176

Transacción de C@mbio Privado
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Número de Control Inválido:
Cierre
Este servicio ejecuta la petición para realizar un cierre de todas las cajas pertenecientes a la afiliación.
Petición
Cierre
URL:
https://<ip>/payment/ws/v2/procesar-cierre
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambioprivadoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:cambioprivadoResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambioprivadoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:cambioprivadoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 162/176

Cierre
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:cierre>
<cod_afiliacion>12345678</cod_afiliacion>
</end:cierre>
</soapenv:Body>
</soapenv:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cierreResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<vterminales>
<vterminal>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<seqnum>0</seqnum>
<vtid>c2phj</vtid>
</vterminal>
<vterminal>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<seqnum>0</seqnum>
<vtid>vpos</vtid>
</vterminal>
</vterminales>
</return>
</ns2:cierreResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambioprivadoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:cambioprivadoResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 163/176

Cierre
Respuesta Fallida – Sin transacciones:
Transacción de Anulación
Este servicio permite enviar transacciones de anulación de las operaciones de compra con tarjetas de crédito
realizadas.
Petición
Transacción de Anulación
URL:
https://<ip>/payment/ws/v2/procesar-anulacion
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cambioprivadoResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:cambioprivadoResponse>
</S:Body>
</S:Envelope>
XML
<?xml version='1.0' encoding='UTF-8'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:cierreResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<vterminales>
<vterminal>
<codigo>99</codigo>
<descripcion>NO HAY TRANSACCIONES POR CERRAR</descripcion>
<seqnum>-</seqnum>
<vtid>-</vtid>
</vterminal>
</vterminales>
</return>
</ns2:cierreResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 164/176

Transacción de Anulación
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>digitel</usuario>
<contrasena>d1git3l!</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:anulacion>
<control>1234567890123456789</control>
<cod_afiliacion>2015100901</cod_afiliacion>
<terminal>10203040</terminal>
<seqnum>159</seqnum>
<monto>123456</monto>
<factura>010101</factura>
<referencia>63</referencia>
<ult>9279</ult>
<authid>4639</authid>
</end:anulacion>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>TRANS. APROBADA</descripcion>
<afiliacion>860084789</afiliacion>
<authid>4639</authid>
<authname>P-Banesco</authname>
<control>1234567890123456789</control>
<lote>13</lote>
<marca>MasterCard</marca>
<referencia>64</referencia>
<rifbanco>J-07013380-5</rifbanco>
<seqnum>160</seqnum>
<tarjeta>542007 * * * * * * 9279</tarjeta>
<terminal>10203040</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT>__DUPLICADO&lt;/UT></linea>
<linea>B_A_N_E_S_C_O_J-07013380-5</linea>
<linea>ANULACION_</linea>
<linea>LABORATORIO_DE_PRUEBAS_</linea>
<linea>CHAGUARAMOS_</linea>
<linea>RIF:J-00343075-7_AFIL:860084789</linea>
<linea>TER:10203040_LOTE:13_</linea>
<linea>REF:64_REF.O:63</linea>
<linea>NRO.CTA:542007 * * * * * * 9279_'M'</linea>
<linea>FECHA:01/12/2017_14:40:42_APROB:4639</linea>
<linea>SECUENCIA:160_CAJA:NAGEL01_</linea>
<linea>MONTO_BS.:-1.234,56_</linea>
<linea>_</linea>
<linea>FIRMA:</linea>
<linea>C.I.:___</linea>
</voucher>
</return>
</ns2:anulacionResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 165/176

Transacción de Anulación
Respuesta Fallida – Credenciales Inválidas
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
Respuesta Fallida – Número de Control Inválido:
Transacción de Anulación – Banplus Pay
Este servicio permite enviar transacciones de anulación de las operaciones de compra con Banplus Pay.
Petición
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:anulacionResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:anulacionResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>GA</codigo>
<descripcion>GA: Parámetros de entrada errados</descripcion>
</return>
</ns2:anulacionResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:anulacionResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 166/176

Transacción de Anulación
URL:
https://<ip>/payment/ws/v2/procesar-anulacion-banplusp
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:anulacionbanplusp>
<cod_afiliacion>12345678</cod_afiliacion>
<control>1234567890123456789</control>
<cid>V12345678</cid>
<referencia>54321</referencia>
<seqnum>12345678</seqnum>
<factura>123456</factura>
</end:anulacionbanplusp>
</soapenv:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 167/176

Transacción de Anulación
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>99988809</afiliacion>
<authname>P-BanplusPresidents</authname>
<lote>2</lote>
<rifbanco></rifbanco>
<seqnum>1726</seqnum>
<terminal>88811143</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANPLUS</linea>
<linea>ANULACION_VENTA_PRESIDENTS</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757</linea>
<linea>TER:88811143_AFIL:99988809__</linea>
<linea>FECHA:23/10/2020_12:00:22_</linea>
<linea>REFERENCIA:_1794_</linea>
<linea>SECUENCIA:1726_CAJA:VPOS_</linea
<linea>MONTO_BS:_-4.000.000,00</linea>
<linea>MONTO_USD:_-20,00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:anulacionbanpluspResponse>
</S:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:anulacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:anulacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 168/176

Transacción de Anulación
Respuesta Fallida – Número de Control Inválido:
Transacción de Anulación – Pago Móvil C2P
Este servicio permite enviar transacciones de anulación de las operaciones de compra con Pago Móvil C2P.
Petición
Transacción de Anulación
URL:
https://<ip>/payment/ws/v2/procesar-anulacion-c2p
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2: anulacionbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>GA</codigo>
<descripcion>GA: Parámetros de entrada errados</descripcion>
</return>
</ns2:anulacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:anulacionbanpluspResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 169/176

Transacción de Anulación
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>USUARIO</usuario>
<contrasena>CONTRASEÑA</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:anulacionc2p>
<cod_afiliacion>12345678</cod_afiliacion>
<control>1234567890123456789</control>
<cid>v18467420</cid>
<telefono>04121234567</telefono>
<seqnum>12345678</seqnum>
</end:anulacionc2p>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionbanpluspResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>APROBADA</descripcion>
<afiliacion>99988809</afiliacion>
<authname>P-BanplusPresidents</authname>
<lote>2</lote>
<rifbanco></rifbanco>
<seqnum>1726</seqnum>
<terminal>88811143</terminal>
<voucher>
<linea>_</linea>
<linea>&lt;UT&gt;__DUPLICADO&lt;/UT&gt;</linea>
<linea>BANPLUS</linea>
<linea>ANULACION_VENTA_PRESIDENTS</linea>
<linea>C2P_</linea>
<linea>CARACAS_</linea>
<linea>RIF:J003430757</linea>
<linea>TER:88811143_AFIL:99988809__</linea>
<linea>FECHA:23/10/2020_12:00:22_</linea>
<linea>REFERENCIA:_1794_</linea>
<linea>SECUENCIA:1726_CAJA:VPOS_</linea>
<linea>MONTO_BS:_-4.000.000,00</linea>
<linea>MONTO_USD:_-20,00_</linea>
<linea>_</linea>
<linea>_</linea>
</voucher>
<vtid>vpos</vtid>
</return>
</ns2:anulacionbanpluspResponse>
</S:Body>
</soapenv:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 170/176

Transacción de Anulación
Respuesta Fallida – Sin Header en la Petición:
Respuesta Fallida – Error Procesando la Transacción:
Respuesta Fallida – Número de Control Inválido:
Recepción de Llave para Desencriptación
Este servicio permite recibir por parte del cliente la llave que será usada para desencriptar la data sensible enviada en
el servicio de compra.
Petición
Recepción de Llave para Desencriptación
URL:
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionc2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:anulacionc2pResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionc2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:anulacionc2pResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2: anulacionc2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>GA</codigo>
<descripcion>GA: Parámetros de entrada errados</descripcion>
</return>
</ns2:anulacionc2pResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:anulacionc2pResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se encontro ese numero de control</descripcion>
</return>
</ns2:anulacionc2pResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 171/176

Recepción de Llave para Desencriptación
https://<ip>/payment/ws/v2/establecer-llave
Cabeceras:
Content-Type: text/xml
Protocolo de invocación:
SOAP 1.2
Protocolo de Seguridad:
HTTPS - SSL
Cuerpo sin Encriptamiento:
Respuesta Exitosa:
Respuesta Fallida – Credenciales Inválidas:
Respuesta Fallida – Sin Header en la Petición:
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:end="http://v2.endpoint.soap.transacciones.msc_base.models/">
<soapenv:Header>
<usuario>digitel</usuario>
<contrasena>d1git3l</contrasena>
</soapenv:Header>
<soapenv:Body>
<end:establecerLlave>
<cod_afiliacion>2015100901</cod_afiliacion>
<securityKey>LLAVE PRUEBA SOAP</securityKey>
</end:establecerLlave>
</soapenv:Body>
</soapenv:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:establecerLlaveResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>00</codigo>
<descripcion>Llave aplicada</descripcion>
</return>
</ns2:establecerLlaveResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:establecerLlaveResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Credenciales Inválidas</descripcion>
</return>
</ns2:establecerLlaveResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 172/176

Recepción de Llave para Desencriptación
Respuesta Fallida – Error Procesando la Transacción:
Recomendaciones para la Implementación del Payment Gateway Modo No
Universal (URL)
Configuraciones como requisitos importantes para las Certificaciones de Botón de Pago y
Vouchers
 
Las imágenes a continuación son meramente de carácter referencial y no pertenecen a la aplicación
Payment Gateway, ya que representan un modelo de aplicación Web para un Comercio
indeterminado.
Métodos de Identificación del Usuario
En el campo Cédula se debe permitir colocar letras, ya que se debe recordar que en algunas ocasiones la persona
(Cliente) realizará operaciones en nombre de una empresa y en esos casos, el identificador de la persona puede ser un
RIF. O en su defecto, crear un ‘select’ independiente con todos los tipos de identificación disponibles (V, E,J, G, P).
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:establecerLlaveResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>Requiere enviar las credenciales de autenticación</descripcion>
</return>
</ns2:establecerLlaveResponse>
</S:Body>
</S:Envelope>
XML
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:establecerLlaveResponse xmlns:ns2="http://v2.endpoint.soap.transacciones.msc_base.models/">
<return>
<codigo>99</codigo>
<descripcion>No se recibió ninguna llave de desencriptamiento</descripcion>
</return>
</ns2:establecerLlaveResponse>
</S:Body>
</S:Envelope>
XML
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 173/176

Métodos de registro de Cuenta de Usuario en el Site
Existen WebSites en donde para realizar operaciones con Botón de Pago el usuario se debe registrar con su respectiva
cuenta única. Este registro debe poseer la facilidad de poder almacenarse, para que en aquellos casos en donde el
Cliente realice una nueva compra, éste no tenga la necesidad de ingresar ciertos datos nuevamente (Opcional).
Enmascaramiento de Datos de Entrada como medida de seguridad
Cuando el Cliente ingresa datos de seguridad como el Número de la Tarjeta o Código Secreto, este se debe
enmascarar al momento de que el usuario se encuentre ingresando el siguiente Dato de Entrada en el formulario
(Obligatorio).
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 174/176

En el Código de Seguridad de la Tarjeta (CVV2)
Si se colocan más de tres dígitos o menos de tres dígitos para tarjetas como Visa o MasterCard, más de cuatro dígitos o
menos de cuatro dígitos para tarjetas Amex, o no se coloca ningún valor, aparte de no procesar la transacción debe
mostrar un mensaje de error. Ejemplo: “Datos Inválidos”.
Fecha de Vencimiento
La fecha de vencimiento de la tarjeta tiene que ser validada al momento de procesar la transacción, si el usuario coloca
en este campo, un valor fecha menor al actual, debe arrojar un error. Ejemplo:” Tarjeta vencida”.
Vouchers en el Site
Cuando una transacción es aprobada o rechazada, la aplicación debe generar un voucher configurado según los
estándares de certificación Bancaria (ver nro 4 “Respuestas XML”), además este, debe ser imprimible para el usuario
(Obligatorio).
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 175/176

Certificación y Manejo de Datos Sensibles
Para poder almacenar ‘data sensible’ como lo es, por ejemplo: los datos asociados a una tarjeta, el proyecto debe
cumplir con una serie de requisitos de seguridad física y lógica, y los cuales están estrechamente vinculados a las
normativas PCI que deben ser certificadas por los bancos adquirientes participantes:
Si un proyecto no cuenta con dicha certificación y se le llegará a detectar que está almacenando ‘data sensible’, el
mismo estará sujeto a suspensión del servicio Payment Gateway.
Toda interfaz de un proyecto con el Payment Gateway será certificada por Mega Soft computación previamente a su
implementación en producción.
Si, luego de haber sido certificada, se detecta que la interfaz con el Payment Gateway fue modificada, el proyecto
estará sujeto a suspensión del servicio Payment Gateway.
Así mismo, aquellos clientes que deseen hacer captura de datos de la tarjeta desde su página, deberán cumplir con los
siguientes requerimientos: * Deben contar con un Certificado Digital (SSL), de marca reconocida. * Deben contar con
un certificado PCI Compliance. * Deben poseer la aprobación/certificación de los bancos adquirientes participantes, así
como también de la certificación de Mega Soft Computación C.A.
Last updated 2025-07-09 16:06:52 -0400
9/7/25, 4:07 p.m. Manual de Especificaciones Técnicas del Payment Gateway V4.24.
file:///E:/MEGA/PG/4.24/Documentación 4.24/MAET-PAYM/MAET-PAYM-00_JUL.2025..html 176/176