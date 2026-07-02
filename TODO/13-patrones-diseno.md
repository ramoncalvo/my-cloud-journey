# Patrones de diseño más comunes para full stack

## Patrones creacionales

- **Factory** — crear distintos tipos de "notificación" (email, SMS, push) sin que el código cliente sepa la clase concreta.
- **Singleton** — una única instancia de conexión a Redis o de configuración de la app compartida en todo el proceso.
- **Builder** — construir un objeto complejo de "consulta de reporte" paso a paso (filtros, agrupaciones, formato de salida) antes de ejecutarla.

## Patrones estructurales

- **Repository pattern** — capa que abstrae el acceso a datos (`UserRepository.findById()`) para que la lógica de negocio no sepa si detrás hay Postgres, Mongo o una API externa; facilita testing con mocks.
- **Adapter** — envolver el SDK de un proveedor de pagos viejo con una interfaz común, para poder cambiar de Stripe a otro proveedor sin tocar el resto del código.
- **Facade** — una clase `CheckoutService` que internamente coordina inventario, pagos y envío, exponiendo un solo método simple `checkout()` al resto de la app.
- **Decorator** — middleware que envuelve un handler HTTP agregando logging, auth o rate limiting sin modificar el handler original.

## Patrones de comportamiento

- **Strategy** — distintos algoritmos de cálculo de envío (estándar, express, internacional) intercambiables en tiempo de ejecución según el pedido.
- **Observer** — sistema de eventos internos: cuando se crea un pedido, notificar a inventario, facturación y analytics sin que el código de "crear pedido" conozca a esos suscriptores.
- **Command** — encapsular una acción de usuario (ej. "aprobar solicitud de crédito") como objeto, permitiendo colas, undo, o logging de auditoría.
- **Chain of Responsibility** — pipeline de validación de un formulario (validar formato → validar duplicados → validar reglas de negocio), donde cada validador decide si pasa al siguiente.
- **Template Method** — proceso genérico de "generar reporte" (obtener datos → transformar → exportar) donde subclases solo sobreescriben el paso de exportación (PDF, Excel, CSV).

## Patrones específicos de backend/API

- **Unit of Work** — agrupar varias operaciones de escritura en una sola transacción (crear pedido + descontar inventario + registrar pago) que se confirma o revierte como bloque.
- **DTO (Data Transfer Object)** — objetos separados para lo que entra/sale de la API, distintos del modelo de dominio interno, para no exponer campos internos por accidente.
- **Middleware / Pipeline pattern** — cadena de funciones que procesan un request (auth → logging → validación → handler) en frameworks como Express/FastAPI.
- **Dependency Injection** — inyectar el servicio de email o la conexión a DB en un controlador en vez de instanciarlo dentro, facilitando testing y desacoplamiento.

## Patrones específicos de frontend

- **Container/Presentational** — separar componentes que manejan lógica y estado de los que solo renderizan UI.
- **Compound Components (React)** — componentes como `<Select>` que comparten estado implícito entre padre e hijos.
- **Render props / Custom hooks** — extraer lógica reutilizable (ej. `useFetch`, `useDebounce`) fuera de los componentes.
