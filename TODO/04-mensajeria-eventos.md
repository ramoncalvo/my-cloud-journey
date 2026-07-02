# Mensajería y eventos

- **Message queues (RabbitMQ, SQS) vs pub/sub (Kafka, Redis pub/sub, EventBridge)**

  1. Cola con RabbitMQ para procesar en background el envío masivo de correos de una campaña de marketing sin bloquear la API.
  2. Kafka como bus de eventos en un banco: "cuenta creada", "transacción realizada" son consumidos por fraude, notificaciones y reportería de forma independiente.

- **Event-driven architecture / event sourcing**

  1. Sistema de inventario de una farmacia: cada movimiento de stock es un evento inmutable; el stock actual se calcula haciendo replay de eventos.
  2. Un sistema bancario reconstruye el saldo de una cuenta a partir del historial completo de eventos de depósitos/retiros, útil para auditoría.

- **CQRS**

  1. Un e-commerce separa el modelo de escritura (creación de pedidos, validaciones complejas) del modelo de lectura (vista optimizada del catálogo para el frontend), cada uno con su propia base optimizada.

- **Dead letter queues, retry con backoff exponencial**

  1. Reintentos de webhooks de pago fallidos: 3 intentos con backoff exponencial, y si todos fallan, el evento va a una DLQ para revisión manual.

- **At-least-once vs exactly-once delivery**

  1. Un sistema de notificaciones push acepta at-least-once (mejor duplicar una notificación que perderla), pero un sistema de cobro bancario necesita garantías cercanas a exactly-once (o idempotencia del lado del consumidor).
