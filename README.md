# Casa Alba - Headless Checkout URLs Plugin

Plugin de WordPress/WooCommerce que modifica las URLs de retorno del checkout para redirigir al frontend headless en lugar del panel de administración de WordPress.

## 📋 Descripción

Este plugin soluciona el problema común en arquitecturas headless donde los clientes son redirigidos al CMS (WordPress) después de:
- Completar una compra
- Cancelar un pago
- Fallar un pago

En su lugar, este plugin redirige a los clientes a las páginas correspondientes en el frontend de la aplicación React/Vue/etc.

## 🚀 Características

- ✅ Modifica la URL de confirmación de pedido (order received/thank you page)
- ✅ Modifica la URL de cancelación de pedido
- ✅ Compatible con Store API de WooCommerce (usado por frontends headless)
- ✅ Compatible con gateways de pago externos (Mercado Pago, PayPal, etc.)
- ✅ Panel de configuración en WordPress admin
- ✅ Logging para debugging
- ✅ No modifica el comportamiento de WooCommerce, solo las URLs

## 📦 Instalación

### Opción 1: Instalación Manual

1. Descarga o copia el archivo `casa-alba-headless-checkout.php`
2. Conéctate a tu servidor WordPress por FTP/SSH
3. Navega a la carpeta de plugins: `/wp-content/plugins/`
4. Crea una carpeta llamada `casa-alba-headless-checkout`
5. Copia el archivo PHP dentro de esa carpeta
6. En el panel de WordPress, ve a **Plugins** → **Plugins Instalados**
7. Busca "Casa Alba - Headless Checkout URLs" y actívalo

### Opción 2: Via wp-config.php (Recomendado para producción)

Si prefieres definir la URL del frontend directamente en código:

1. Sigue los pasos 1-6 de la Opción 1
2. Edita tu archivo `wp-config.php`
3. Agrega esta constante antes de la línea `/* That's all, stop editing! */`:

```php
define('CASA_ALBA_FRONTEND_URL', 'https://productoscasaalba.cl');
```

4. Activa el plugin

### Opción 3: Via WP-CLI

```bash
cd /path/to/wordpress/wp-content/plugins
mkdir casa-alba-headless-checkout
cd casa-alba-headless-checkout
# Copia el archivo PHP aquí
wp plugin activate casa-alba-headless-checkout
```

## ⚙️ Configuración

### Configuración vía WordPress Admin

1. Ve a **Ajustes** → **Headless Checkout**
2. Ingresa la URL de tu frontend (sin barra final)
   - Ejemplo: `https://productoscasaalba.cl`
3. Haz clic en **Guardar Configuración**

### Configuración vía wp-config.php (Recomendado)

Agrega esta línea a tu `wp-config.php`:

```php
define('CASA_ALBA_FRONTEND_URL', 'https://productoscasaalba.cl');
```

**Nota:** La configuración en `wp-config.php` tiene prioridad sobre la configuración en el admin.

## 🔧 Requisitos del Frontend

El plugin redirigirá a las siguientes rutas en tu frontend. Asegúrate de que existan:

### 1. Página de Pedido Recibido
- **Ruta:** `/pedido-recibido`
- **Parámetros:** `?order_id={ID}&key={ORDER_KEY}`
- **Propósito:** Mostrar confirmación de pedido exitoso

### 2. Página de Pedido Cancelado
- **Ruta:** `/pedido-cancelado`
- **Parámetros:** `?order_id={ID}` (opcional)
- **Propósito:** Mostrar mensaje cuando el cliente cancela el pago

## 🛠️ Hooks y Filtros Modificados

El plugin intercepta los siguientes filtros de WooCommerce:

- `woocommerce_get_checkout_order_received_url` - URL de confirmación
- `woocommerce_get_cancel_order_url` - URL de cancelación
- `woocommerce_get_cancel_order_url_raw` - URL de cancelación (raw)
- `woocommerce_get_return_url` - URL de retorno de payment gateways
- `woocommerce_get_checkout_payment_url` - URL de pago
- `woocommerce_store_api_checkout_order_response` - Respuesta del Store API

## 🐛 Debugging

El plugin registra información en el error log de WordPress. Para habilitar el logging:

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Los logs se guardan en `/wp-content/debug.log`

Busca líneas que contengan: `Casa Alba Headless`

## 📝 Notas Importantes

1. **No desinstalar WooCommerce:** Este plugin requiere que WooCommerce esté instalado y activado.

2. **CORS:** Asegúrate de que tu WordPress CMS tenga configurado CORS correctamente para permitir requests desde tu frontend.

3. **SSL:** Se recomienda usar HTTPS tanto en el frontend como en el CMS.

4. **Cache:** Si usas plugins de caché en WordPress, limpia el caché después de activar este plugin.

5. **Payment Gateways:** El plugin es compatible con todos los payment gateways de WooCommerce. Los gateways externos (Mercado Pago, PayPal, etc.) redirigirán primero a su plataforma y luego a tu frontend.

## 🔄 Flujo de Pago con Payment Gateway Externo

```
Cliente → Frontend → WooCommerce Store API → Payment Gateway (ej: Mercado Pago)
                                                      ↓
Frontend ← Plugin (modifica URL) ← WooCommerce ← Gateway
```

## 🤝 Compatibilidad

- **WordPress:** 5.8+
- **PHP:** 7.4+
- **WooCommerce:** 6.0+
- **Payment Gateways:** Compatible con todos los gateways de WooCommerce

## 📄 Licencia

GPL v3

## 🆘 Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contacta al equipo de desarrollo de Casa Alba.

## 🔄 Changelog

### 1.0.0 (2025-01-28)
- Lanzamiento inicial
- Soporte para URLs de confirmación y cancelación
- Compatibilidad con Store API
- Panel de configuración en WordPress admin
- Logging para debugging
