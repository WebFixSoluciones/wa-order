# WA Order v0.9.0

Plugin de pedidos por WhatsApp para WordPress con soporte multi-sucursal.

Desarrollado por [WebFix](https://webfix.ec).

## Características
- 🛒 Carrito de compras flotante (Popup).
- 🏪 Gestión de Matriz y Sucursales con base de datos dedicada.
- 🛵 Cálculo de Delivery y Recogida en local.
- 📱 Formulario de datos validado para WhatsApp.
- 🔍 Búsqueda y filtrado por categorías de productos.
- 🎨 Diseño visual moderno y responsivo (Mobile First).
- ⚙️ Panel de administración con configuración de sucursales, colores y datos de contacto.
- 🗺️ Integración con Google Maps por sucursal.
- 🔒 Seguridad con nonces de WordPress y prefijo `wrm_` en todas las opciones.

## Requisitos
- WordPress 6.0+
- PHP 8.0+

## Instalación
1. Subir la carpeta `wa-order` a `/wp-content/plugins/`.
2. Activar el plugin desde el panel de WordPress.
3. Ir a **WA Order** en el menú lateral para configurar sucursales, WhatsApp y opciones de delivery.
4. Usar el shortcode `[wrm_menu]` en cualquier página o entrada.

## Uso del Shortcode
```
[wrm_menu]
```

## Changelog

### v0.9.0
- Actualización de versión mayor con mejoras de sincronización de sucursales.
- Optimización del modal de delivery y validaciones de formulario.
- Refactorización de assets CSS y JS para mayor rendimiento.
- Mejoras en columnas y administración de sucursales.

### v0.8.x
- Implementación inicial de multi-sucursal (Matriz + Sucursales).
- Carrito flotante popup con gestión de variantes y extras.
- Integración de Google Maps por sucursal.
- Panel de administración con selector de color primario.

## Licencia
GPL-2.0-or-later — © WebFix