# APIs y comunicación

- **gRPC (contratos, streaming bidireccional)**

  1. Comunicación interna entre microservicios de un banco (cuentas ↔ transacciones) donde la latencia importa más que la legibilidad humana.
  2. Streaming de posiciones de inventario en tiempo real entre el sistema de bodega y el de e-commerce en una cadena de retail.

- **API versioning (URL vs header vs content negotiation)**

  1. Una fintech mantiene `/v1/pagos` y `/v2/pagos` simultáneamente porque apps móviles viejas siguen en producción y no puedes forzar upgrade.
  2. Un ERP expone `Accept: application/vnd.empresa.v2+json` para que integradores externos elijan versión sin romper URLs.

- **Rate limiting / throttling (token bucket, sliding window)**

  1. API pública de un SaaS de facturación: 100 req/min en plan free, 10k en plan enterprise.
  2. Una aerolínea limita búsquedas de vuelos por IP para evitar scraping de precios por bots de comparadores.

- **Idempotencia (keys en POST/PUT para pagos y reintentos)**

  1. Endpoint de "crear pedido" en e-commerce: evita pedidos duplicados si el cliente reintenta por timeout.
  2. Transferencias bancarias: el mismo `Idempotency-Key` evita que un reintento de red duplique el cargo.

- **API Gateway (Kong, Traefik, o el de cloud)**

  1. Un hospital centraliza auth, logging y rate limiting de todas sus APIs clínicas en un solo gateway antes de llegar a microservicios internos.
  2. Una plataforma de delivery expone un solo dominio público que enruta internamente a servicios de pedidos, pagos y tracking.

- **OpenAPI/Swagger spec-first design**

  1. Un equipo de seguros define el contrato OpenAPI antes de escribir código, para que frontend y backend trabajen en paralelo con mocks generados del spec.
