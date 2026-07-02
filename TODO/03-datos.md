# Datos

- **Diseño de bases de datos relacionales (normalización, índices, EXPLAIN)**

  1. Índices compuestos en Postgres para acelerar búsqueda de clientes por (estado, giro, tamaño) en un CRM B2B.
  2. Usar `EXPLAIN ANALYZE` en una farmacia online para descubrir por qué la búsqueda de productos por categoría tarda 3 segundos y arreglarlo con un índice faltante.

- **NoSQL (cuándo Mongo/DynamoDB vs SQL)**

  1. Un catálogo de productos con esquema muy variable (cada categoría tiene atributos distintos) vive mejor en MongoDB que forzando columnas nulas en SQL.
  2. DynamoDB para el carrito de compras de un e-commerce de alto tráfico, donde necesitas lecturas/escrituras predecibles a escala sin joins complejos.

- **Caching strategies (cache-aside, write-through, TTL, invalidation)**

  1. Cache-aside con Redis: catálogo de servicios de una empresa de telecom (cambia poco), evitando golpear la DB en cada carga de landing.
  2. Write-through en un sistema de precios de aerolínea: cada actualización de precio se escribe a DB y cache al mismo tiempo para que nunca haya inconsistencia.

- **Redis más allá de cache: pub/sub, streams, locks distribuidos, rate limiting**

  1. Lock distribuido en Redis para evitar que dos usuarios reserven el mismo asiento de cine al mismo tiempo.
  2. Redis Streams para procesar eventos de clics en tiempo real en un dashboard de analytics de marketing.

- **Database migrations y versionado de esquema**

  1. Un banco usa migraciones versionadas (Alembic/Flyway) para agregar una columna `kyc_status` sin downtime, con rollback plan documentado.

- **Read replicas / sharding / particionamiento**

  1. Read replicas en un hospital: reportes pesados de facturación corren contra una réplica, sin afectar el sistema transaccional de admisión de pacientes.
  2. Sharding por `tenant_id` en un SaaS multi-cliente de seguros, donde cada aseguradora tiene su propio dataset aislado.
  3. Particionamiento por rango de fecha en una tabla de transacciones bancarias (una partición por mes), para que las consultas de "últimos 30 días" no tengan que escanear años de historial.

- **Niveles de aislamiento de transacciones (Read Committed, Repeatable Read, Serializable)**

  1. Un sistema de reservas de vuelos usa `Serializable` en el paso de "confirmar asiento" para evitar que dos usuarios reserven el mismo asiento aunque ambos lean disponibilidad al mismo tiempo (a costa de más contención).
  2. Un dashboard de reportería usa `Read Committed` (el default en Postgres) porque puede tolerar ver datos ligeramente desfasados a cambio de mejor performance.

- **Locks a nivel DB: `SELECT FOR UPDATE`, advisory locks**

  1. `SELECT ... FOR UPDATE` en el saldo de una cuenta bancaria antes de descontar un pago, para que ninguna otra transacción lo modifique hasta que termine la operación.
  2. Advisory lock en Postgres para asegurar que solo una instancia de un cron job (corriendo en varios servidores) ejecute la tarea de "cerrar el día contable", evitando doble ejecución.

- **Transacciones distribuidas / two-phase commit vs eventual consistency**

  1. Transferencia entre dos bancos distintos (dos bases de datos separadas): en vez de un 2PC frágil, se usa el patrón "reservar fondos → confirmar → liberar si falla" con eventual consistency y reconciliación posterior.

- **N+1 queries y cómo evitarlas**

  1. Un endpoint que lista pedidos y, por cada uno, hace una query aparte para traer sus productos (100 pedidos = 101 queries) se soluciona con `JOIN` o `eager loading` (ej. `selectinload` en SQLAlchemy), bajando a 1-2 queries totales.

- **Connection pooling y agotamiento de conexiones**

  1. Un backend serverless (Lambda) que abre una conexión nueva a Postgres por cada invocación agota el límite de conexiones de la DB bajo tráfico alto; se resuelve con un pooler externo (RDS Proxy, PgBouncer) que reutiliza conexiones entre invocaciones.
