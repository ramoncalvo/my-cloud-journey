# Testing

- **Testing pyramid (unit, integration, e2e)**

  1. Sistema de trading algorítmico: unit tests para las señales técnicas, integration tests para la conexión con el exchange, e2e para el flujo completo "señal → orden → alerta".

- **Contract testing (Pact)**

  1. Entre el frontend y backend de un widget embebido en varios sitios de clientes, para que un cambio en el API no rompa el widget sin que nadie se entere hasta producción.

- **Load testing (k6, Locust)**

  1. Simular 500 usuarios concurrentes completando un formulario de cotización de seguros antes de lanzar una campaña de ads, para ver si el backend aguanta el pico.
