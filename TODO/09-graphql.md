# GraphQL

- **Schema-first design (types, queries, mutations, subscriptions)**

  1. Un e-commerce define un schema donde `Product` tiene `reviews`, `inventory` y `pricing` como campos anidados, permitiendo que el frontend pida exactamente los campos que necesita en una sola query en vez de golpear 3 endpoints REST distintos.

- **Resolvers y el problema N+1 en GraphQL**

  1. Un query que pide una lista de pedidos con su cliente asociado (`orders { customer { name } }`) puede disparar un resolver por cada pedido para traer al cliente; se resuelve con **DataLoader** (batching + caching por request) para agrupar esas llamadas en una sola consulta a la DB.

- **Over-fetching / under-fetching (la razón de ser de GraphQL vs REST)**

  1. Una app móvil de un banco solo necesita `saldo` y `nombre` de la cuenta para la pantalla de inicio; con REST tradicional el endpoint `/account/123` regresaría el objeto completo (over-fetching), mientras que con GraphQL el cliente pide solo esos dos campos.
  2. Un dashboard web necesita datos de 3 recursos relacionados (cliente, pedidos, pagos) en una sola pantalla; con REST serían 3 requests (under-fetching por endpoint), con GraphQL es una sola query anidada.

- **Autorización a nivel de campo (field-level authorization)**

  1. En un ERP, el campo `salario` dentro del tipo `Empleado` solo se resuelve si el usuario autenticado tiene rol de RH; cualquier otro rol recibe `null` o error en ese campo específico, aunque el resto del objeto sí se devuelva.

- **Complejidad de queries y protección contra abuso**

  1. Un API pública de GraphQL limita la profundidad de anidación de queries (ej. máximo 5 niveles) y calcula un "costo" por query para evitar que un cliente pida una consulta recursiva gigante (`user { friends { friends { friends { ... } } } }`) que tumbe el servidor.

- **Caching en GraphQL (más complejo que REST por diseño)**

  1. Un cliente usa Apollo Client con cache normalizado por `id` de cada entidad, para que si actualizas el `nombre` de un producto en una mutation, todas las queries en pantalla que referencian ese producto se actualicen automáticamente sin refetch manual.

- **Subscriptions (tiempo real sobre GraphQL, vía WebSocket)**

  1. Un sistema de soporte al cliente usa una GraphQL subscription para notificar en vivo cuando llega un nuevo mensaje en un chat de soporte, sin que el frontend tenga que hacer polling.

- **Federación de schemas (Apollo Federation) — nivel avanzado**

  1. Una empresa con microservicios separados (Usuarios, Pedidos, Inventario) expone cada uno su propio subgraph, y un gateway de Apollo Federation los combina en un schema único para el frontend, sin que un solo equipo tenga que mantener todo el schema.

- **GraphQL vs REST: cuándo NO usar GraphQL**

  1. Un servicio interno simple de "subir archivo" o un webhook de terceros (que espera un contrato fijo tipo REST) no se beneficia de GraphQL — la flexibilidad de queries no aporta nada y añade complejidad innecesaria de resolvers y schema.
