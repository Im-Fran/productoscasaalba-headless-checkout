# Casa Alba - Headless Checkout API

Este plugin proporciona endpoints REST API para gestionar perfiles de clientes, direcciones y pedidos en un frontend headless.

## Requisitos

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+
- Plugin Casa Alba Headless Authentication (para autenticación JWT)

## Endpoints de API

Todos los endpoints requieren autenticación JWT (excepto los endpoints públicos mencionados explícitamente).

### Base URL

```
/wp-json/casa-alba/v1/
```

### Autenticación

Incluir el token JWT en el header:
```
Authorization: Bearer {token}
```

---

## Perfil del Cliente

### Obtener Perfil

**GET** `/customer/profile`

Obtiene el perfil del usuario autenticado.

**Response:**
```json
{
  "id": 123,
  "email": "usuario@ejemplo.com",
  "username": "usuario",
  "first_name": "Juan",
  "last_name": "Pérez",
  "display_name": "Juan Pérez",
  "phone": "+56912345678",
  "date_created": "2024-01-15 10:30:00",
  "orders_count": 5,
  "total_spent": 125000
}
```

### Actualizar Perfil

**PUT** `/customer/profile`

Actualiza el perfil del usuario autenticado.

**Body:**
```json
{
  "first_name": "Juan",
  "last_name": "Pérez",
  "display_name": "Juan P.",
  "phone": "+56912345678",
  "email": "nuevoemail@ejemplo.com",
  "current_password": "contraseña_actual",
  "new_password": "nueva_contraseña"
}
```

**Notas:**
- Todos los campos son opcionales
- Para cambiar la contraseña, se requieren ambos campos `current_password` y `new_password`
- La nueva contraseña debe tener al menos 8 caracteres
- El email debe ser único en el sistema

**Response:**
```json
{
  "message": "Perfil actualizado correctamente",
  "profile": {
    "id": 123,
    "email": "nuevoemail@ejemplo.com",
    ...
  }
}
```

---

## Direcciones del Cliente

### Obtener Direcciones

**GET** `/customer/addresses`

Obtiene las direcciones de facturación y envío del usuario autenticado.

**Response:**
```json
{
  "billing": {
    "first_name": "Juan",
    "last_name": "Pérez",
    "company": "Empresa S.A.",
    "address_1": "Av. Principal 123",
    "address_2": "Depto 4B",
    "city": "Santiago",
    "state": "RM",
    "postcode": "8320000",
    "country": "CL",
    "phone": "+56912345678",
    "email": "usuario@ejemplo.com"
  },
  "shipping": {
    "first_name": "Juan",
    "last_name": "Pérez",
    "company": "Empresa S.A.",
    "address_1": "Av. Principal 123",
    "address_2": "Depto 4B",
    "city": "Santiago",
    "state": "RM",
    "postcode": "8320000",
    "country": "CL"
  }
}
```

### Actualizar Direcciones

**PUT** `/customer/addresses`

Actualiza las direcciones del usuario autenticado.

**Body:**
```json
{
  "billing": {
    "first_name": "Juan",
    "last_name": "Pérez",
    "company": "Empresa S.A.",
    "address_1": "Av. Principal 123",
    "address_2": "Depto 4B",
    "city": "Santiago",
    "state": "RM",
    "postcode": "8320000",
    "country": "CL",
    "phone": "+56912345678",
    "email": "usuario@ejemplo.com"
  },
  "shipping": {
    "first_name": "Juan",
    "last_name": "Pérez",
    "company": "Empresa S.A.",
    "address_1": "Av. Secundaria 456",
    "address_2": "",
    "city": "Valparaíso",
    "state": "VS",
    "postcode": "2340000",
    "country": "CL"
  }
}
```

**Notas:**
- Puedes actualizar solo `billing`, solo `shipping`, o ambos
- Todos los campos dentro de cada objeto son opcionales
- Solo se actualizarán los campos proporcionados

**Response:**
```json
{
  "message": "Direcciones actualizadas correctamente",
  "addresses": {
    "billing": { ... },
    "shipping": { ... }
  }
}
```

---

## Pedidos

### Listar Pedidos

**GET** `/customer/orders`

Obtiene todos los pedidos del usuario autenticado.

**Query Parameters:**
- `page` (opcional, default: 1): Página de resultados
- `per_page` (opcional, default: 10): Resultados por página
- `status` (opcional, default: 'any'): Filtrar por estado (pending, processing, completed, etc.)

**Response:**
```json
{
  "orders": [
    {
      "id": 456,
      "order_number": "456",
      "date_created": "2024-01-20 15:30:00",
      "date_created_gmt": "2024-01-20T15:30:00+00:00",
      "status": "completed",
      "status_label": "Completado",
      "total": 75000,
      "total_formatted": "$75.000",
      "currency": "CLP",
      "payment_method": "webpay",
      "payment_method_title": "Webpay Plus",
      "items_count": 3
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 25,
    "total_pages": 3
  }
}
```

### Obtener Detalle de Pedido

**GET** `/customer/orders/{id}`

Obtiene el detalle completo de un pedido específico.

**Response:**
```json
{
  "id": 456,
  "order_number": "456",
  "order_key": "wc_order_abc123",
  "date_created": "2024-01-20 15:30:00",
  "date_created_gmt": "2024-01-20T15:30:00+00:00",
  "date_modified": "2024-01-20 16:00:00",
  "status": "pending",
  "status_label": "Pendiente de pago",
  "currency": "CLP",
  "total": 75000,
  "total_formatted": "$75.000",
  "subtotal": 70000,
  "subtotal_formatted": "$70.000",
  "total_tax": 0,
  "total_tax_formatted": "$0",
  "shipping_total": 5000,
  "shipping_total_formatted": "$5.000",
  "discount_total": 0,
  "discount_total_formatted": "$0",
  "payment_method": "webpay",
  "payment_method_title": "Webpay Plus",
  "customer_note": "",
  "billing": {
    "first_name": "Juan",
    "last_name": "Pérez",
    ...
  },
  "shipping": {
    "first_name": "Juan",
    "last_name": "Pérez",
    ...
  },
  "line_items": [
    {
      "id": 789,
      "product_id": 100,
      "variation_id": 0,
      "name": "Producto Ejemplo",
      "quantity": 2,
      "subtotal": 40000,
      "subtotal_formatted": "$40.000",
      "total": 40000,
      "total_formatted": "$40.000",
      "sku": "PROD-001",
      "image": "https://ejemplo.com/wp-content/uploads/2024/01/producto.jpg"
    }
  ],
  "shipping_lines": [
    {
      "id": 1,
      "method_title": "Envío estándar",
      "method_id": "flat_rate",
      "total": 5000,
      "total_formatted": "$5.000"
    }
  ],
  "links": {
    "pay": {
      "url": "https://ejemplo.com/checkout/order-pay/456/?key=wc_order_abc123",
      "label": "Pagar pedido"
    },
    "cancel": {
      "url": "https://productoscasaalba.cl/pedido-cancelado?order_id=456",
      "label": "Cancelar pedido"
    }
  }
}
```

**Notas sobre `links`:**
- `pay`: Aparece solo si el pedido está pendiente de pago (status: pending, on-hold)
- `cancel`: Aparece solo si el pedido está pendiente de pago
- `reorder`: Aparece solo si el pedido está completado, cancelado o fallido

**Códigos de error:**
- `404`: Pedido no encontrado o no pertenece al usuario autenticado

### Obtener Detalle de Pedido (Público con Order Key)

**GET** `/orders/{id}/public`

Obtiene el detalle completo de un pedido específico usando el order key. No requiere autenticación.

**Query Parameters:**
- `key` (requerido): Order key del pedido (ej: wc_order_abc123)

**Ejemplo:**
```
GET /wp-json/casa-alba/v1/orders/456/public?key=wc_order_abc123
```

**Response:**
```json
{
  "id": 456,
  "order_number": "456",
  "order_key": "wc_order_abc123",
  "date_created": "2024-01-20 15:30:00",
  "date_created_gmt": "2024-01-20T15:30:00+00:00",
  "date_modified": "2024-01-20 16:00:00",
  "status": "pending",
  "status_label": "Pendiente de pago",
  "currency": "CLP",
  "total": 75000,
  "total_formatted": "$75.000",
  "subtotal": 70000,
  "subtotal_formatted": "$70.000",
  "total_tax": 0,
  "total_tax_formatted": "$0",
  "shipping_total": 5000,
  "shipping_total_formatted": "$5.000",
  "discount_total": 0,
  "discount_total_formatted": "$0",
  "payment_method": "webpay",
  "payment_method_title": "Webpay Plus",
  "customer_note": "",
  "billing": { ... },
  "shipping": { ... },
  "line_items": [ ... ],
  "shipping_lines": [ ... ],
  "links": {
    "pay": {
      "url": "https://ejemplo.com/checkout/order-pay/456/?key=wc_order_abc123",
      "label": "Pagar pedido"
    }
  }
}
```

**Uso típico:**
Este endpoint se utiliza principalmente en la página de "Pedido Recibido" para mostrar los detalles del pedido inmediatamente después del checkout, sin requerir que el usuario inicie sesión.

**Notas:**
- No requiere autenticación JWT
- El order_key debe coincidir exactamente con el pedido solicitado
- Solo muestra el enlace de "pagar" en los links (no muestra cancelar ni reordenar)
- Este endpoint es seguro porque el order_key es un token único generado por WooCommerce

**Códigos de error:**
- `400`: Falta el parámetro 'key'
- `404`: Pedido no encontrado o order key inválido

### Cancelar Pedido

**POST** `/customer/orders/{id}/cancel`

Cancela un pedido que está pendiente de pago.

**Response:**
```json
{
  "message": "Pedido cancelado correctamente",
  "order_id": 456,
  "status": "cancelled"
}
```

**Códigos de error:**
- `404`: Pedido no encontrado
- `400`: El pedido no puede ser cancelado (solo se pueden cancelar pedidos en estado pending u on-hold)

### Volver a Pedir

**POST** `/customer/orders/{id}/reorder`

Agrega todos los productos de un pedido anterior al carrito actual.

**Response:**
```json
{
  "message": "3 productos agregados al carrito",
  "items_added": 3,
  "cart_item_count": 3,
  "items_failed": []
}
```

Si algunos productos no pudieron agregarse:
```json
{
  "message": "2 productos agregados al carrito",
  "items_added": 2,
  "cart_item_count": 2,
  "items_failed": [
    {
      "name": "Producto No Disponible",
      "reason": "Producto no disponible"
    }
  ],
  "warning": "Algunos productos no pudieron ser agregados al carrito"
}
```

**Códigos de error:**
- `404`: Pedido no encontrado
- `400`: El pedido no puede ser reordenado (solo se pueden reordenar pedidos completados, cancelados o fallidos)

---

## Códigos de Estado HTTP

- `200`: Éxito
- `400`: Petición incorrecta (datos inválidos)
- `401`: No autorizado (token inválido o expirado)
- `404`: Recurso no encontrado
- `409`: Conflicto (ej: email ya existe)
- `500`: Error del servidor

## Ejemplos de Uso

### JavaScript/TypeScript

```typescript
// Obtener perfil
const response = await fetch('https://ejemplo.com/wp-json/casa-alba/v1/customer/profile', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
const profile = await response.json();

// Actualizar perfil
const updateResponse = await fetch('https://ejemplo.com/wp-json/casa-alba/v1/customer/profile', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    first_name: 'Juan',
    last_name: 'Pérez',
    phone: '+56912345678'
  })
});

// Listar pedidos
const ordersResponse = await fetch('https://ejemplo.com/wp-json/casa-alba/v1/customer/orders?page=1&per_page=10', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
const orders = await ordersResponse.json();

// Obtener detalle de pedido
const orderResponse = await fetch('https://ejemplo.com/wp-json/casa-alba/v1/customer/orders/456', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
const order = await orderResponse.json();

// Cancelar pedido
const cancelResponse = await fetch('https://ejemplo.com/wp-json/casa-alba/v1/customer/orders/456/cancel', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Volver a pedir
const reorderResponse = await fetch('https://ejemplo.com/wp-json/casa-alba/v1/customer/orders/456/reorder', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### cURL

```bash
# Obtener perfil
curl -X GET "https://ejemplo.com/wp-json/casa-alba/v1/customer/profile" \
  -H "Authorization: Bearer {token}"

# Actualizar perfil
curl -X PUT "https://ejemplo.com/wp-json/casa-alba/v1/customer/profile" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Juan",
    "last_name": "Pérez",
    "phone": "+56912345678"
  }'

# Listar pedidos
curl -X GET "https://ejemplo.com/wp-json/casa-alba/v1/customer/orders?page=1&per_page=10" \
  -H "Authorization: Bearer {token}"

# Obtener detalle de pedido
curl -X GET "https://ejemplo.com/wp-json/casa-alba/v1/customer/orders/456" \
  -H "Authorization: Bearer {token}"

# Cancelar pedido
curl -X POST "https://ejemplo.com/wp-json/casa-alba/v1/customer/orders/456/cancel" \
  -H "Authorization: Bearer {token}"

# Volver a pedir
curl -X POST "https://ejemplo.com/wp-json/casa-alba/v1/customer/orders/456/reorder" \
  -H "Authorization: Bearer {token}"
```

## Notas de Seguridad

- Todos los endpoints requieren autenticación JWT válida
- Los usuarios solo pueden acceder a sus propios datos
- Las contraseñas deben tener al menos 8 caracteres
- El cambio de contraseña requiere la contraseña actual
- Los emails deben ser únicos en el sistema

## Changelog

### Version 1.0.0
- Endpoints de perfil de cliente (GET, PUT)
- Endpoints de direcciones (GET, PUT)
- Endpoints de pedidos (GET lista, GET detalle)
- Endpoint para cancelar pedidos
- Endpoint para volver a pedir

