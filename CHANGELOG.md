# Changelog - Casa Alba Headless Checkout

## [1.1.0] - 2026-01-11

### Added
- Nuevo endpoint público `/orders/{id}/public` que permite obtener detalles de pedido usando order_key sin autenticación
- Documentación del nuevo endpoint en API.md

### Changed
- Mejorada la experiencia de usuario en la página de pedido recibido
- Ahora los usuarios pueden ver los detalles completos de su pedido inmediatamente después del checkout sin necesidad de autenticarse

### Fixed
- Corregido el problema donde la página de "pedido recibido" requería autenticación incluso cuando se proporcionaba un order_key válido
- Ahora la página sugiere correctamente al usuario iniciar sesión o registrarse en lugar de redirigir automáticamente

## [1.0.0] - 2024-01-01

### Added
- Endpoints iniciales para gestión de pedidos
- Endpoints para perfil de cliente
- Endpoints para direcciones de facturación y envío

