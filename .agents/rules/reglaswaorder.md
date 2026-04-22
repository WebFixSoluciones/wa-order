---
trigger: always_on
---

# Reglas del Proyecto: WA Order (v0.8.3+)
# Desarrollado por: WebFix

## 1. Contexto del Proyecto
- **Propósito**: Plugin de WordPress para pedidos por WhatsApp con gestión de sucursales (Matriz + Sucursales).
- **Stack Técnico**: PHP 8.0+, jQuery (Frontend), WordPress Hooks API, CSS Nativo.
- **Roadmap Actual**: v0.8.3 (Consolidación de sincronización y UI/UX).

## 2. Principios de Arquitectura (Escalabilidad)
- **Fuente Única de Verdad**: Nunca dupliques datos de sucursales. Usa siempre la estructura centralizada en `locations-db.php` y la función JS `getAvailableLocations()`.
- **Prefijo de Seguridad**: Todas las funciones PHP, clases y opciones de base de datos deben usar el prefijo `wrm_` para evitar conflictos con otros plugins.
- **Modularidad**: Mantén la lógica separada. 
    - `includes/`: Lógica de servidor y base de datos.
    - `assets/`: Lógica de cliente y estilos.
- **Internalización (i18n)**: Usa siempre las funciones de traducción de WordPress `__()` o `_e()` en PHP, y el objeto `WRMCart.i18n` en JavaScript.

## 3. Reglas de Desarrollo (JavaScript/jQuery)
- **Estado Global**: El estado de la sucursal seleccionada se maneja únicamente en el objeto `selectedLocationIdx`.
- **Sincronización**: Cualquier cambio en la selección de sucursal debe disparar `syncSelectedLocation()` para actualizar simultáneamente el carrito, el modal y los datos de envío.
- **Validaciones**: Las validaciones de campos de delivery deben estar separadas entre el flujo del modal (`validateDeliveryModal`) y el flujo base.

## 4. Reglas de Interfaz (UI/UX)
- **Mobile First**: El plugin debe ser 100% funcional en dispositivos móviles. Los selectores de sucursales deben usar scroll horizontal o layouts claros en pantallas pequeñas.
- **Consistencia Visual**: Usa las variables CSS definidas en `:root` (ubicadas en `menu-front.css`) para colores primarios y espaciados.
- **Google Maps**: El enlace de mapas solo debe renderizarse si `maps_enabled` es true y existe una URL válida.

## 5. Control de Versiones y Roadmap
- **Versión Actual**: v0.8.3-alpha.
- **Objetivo Próximo (v0.8.3-beta)**: Rediseño visual de las cards de sucursales y optimización del modal de delivery.
- **Commits**: Seguir el estándar de Conventional Commits (ej: `feat:`, `fix:`, `style:`, `refactor:`).