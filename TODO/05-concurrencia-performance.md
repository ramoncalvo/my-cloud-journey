# Concurrencia y performance

- **Async/await patterns (Python asyncio, Node event loop)**

  1. Un backend de FastAPI hace 5 llamadas a APIs externas (verificación de identidad, score crediticio, geolocalización) en paralelo con `asyncio.gather` en vez de secuencial, reduciendo el tiempo de respuesta de 5s a 1s.

- **Race conditions, deadlocks, locks (optimistic vs pessimistic)**

  1. Lock optimista para evitar que dos usuarios reserven el mismo departamento en un sistema de bienes raíces al mismo tiempo.
  2. Lock pessimista (`SELECT FOR UPDATE`) en un banco al procesar una transferencia, para que dos transacciones concurrentes no dejen el saldo en estado inconsistente.

- **Worker pools / job queues (Celery, BullMQ)**

  1. Worker pool con Celery para procesar en paralelo la generación de reportes PDF de facturación mensual de miles de clientes sin bloquear la API principal.

- **Backpressure**

  1. Un pipeline que ingiere datos de sensores IoT de una planta industrial aplica backpressure para no saturar memoria si los sensores mandan ráfagas de lecturas.

- **Multithreading vs multiprocessing vs async (cuándo usar cada uno)**

  1. En Python, un servicio que procesa imágenes (CPU-bound: resize, compresión) usa `multiprocessing` para saltarse el GIL y usar varios núcleos; un servicio que hace muchas llamadas a APIs externas (I/O-bound) usa `asyncio` en vez de threads, porque no hay trabajo real de CPU que paralelizar.
  2. Un batch nocturno que recalcula el riesgo crediticio de millones de clientes reparte el trabajo entre procesos (multiprocessing.Pool) para usar los 8 núcleos del servidor, en vez de un solo hilo secuencial que tardaría horas.

- **GIL (Global Interpreter Lock) en Python — implicaciones reales**

  1. Explicar en entrevista por qué agregar `threading` a un cómputo pesado en Python (ej. cálculo de indicadores técnicos sobre miles de velas) no acelera nada — el GIL serializa la ejecución — y que la solución correcta es `multiprocessing` o mover el cómputo a Numpy/C extensions que sí liberan el GIL.

- **Thread pools / connection pools**

  1. Un servicio Java/Node con un thread pool fijo (ej. 20 hilos) para atender requests HTTP, evitando crear un hilo nuevo por cada conexión entrante bajo carga alta.
  2. Pool de conexiones a Postgres (ej. PgBouncer o el pool de SQLAlchemy) en un backend con 50 workers, para no agotar el límite de conexiones máximas de la base de datos cuando hay picos de tráfico.

- **Deadlocks reales y cómo prevenirlos**

  1. Dos transacciones bancarias que transfieren dinero en direcciones opuestas (A→B y B→A simultáneamente) intentan bloquear las mismas dos filas en orden distinto y quedan en deadlock; se previene bloqueando siempre en el mismo orden (ej. por ID de cuenta ascendente).
  2. Un job de actualización de inventario y un job de reportería intentan lockear las mismas tablas en orden inverso; el motor de DB detecta el deadlock y aborta una de las dos transacciones automáticamente — el código debe saber reintentar.

- **Producer-consumer pattern**

  1. Un sistema de procesamiento de órdenes en bolsa: hilos/procesos "productores" leen el feed de mercado y encolan eventos; un pool de "consumidores" los procesa y ejecuta la lógica de trading, desacoplando velocidad de ingesta de velocidad de procesamiento.

- **Condiciones de carrera en operaciones "read-then-write"**

  1. Un contador de "likes" o de inventario disponible que se lee y luego se actualiza (`stock = stock - 1`) en dos pasos separados puede perder actualizaciones si dos requests concurrentes leen el mismo valor antes de que ninguno escriba; se resuelve con operaciones atómicas (`UPDATE stock = stock - 1 WHERE stock > 0`) o locks.

- **Semáforos / límite de concurrencia**

  1. Un scraper o cliente de API externo usa un semáforo (`asyncio.Semaphore(10)`) para no disparar más de 10 requests simultáneos a un proveedor que tiene rate limit propio, evitando que te bloqueen la IP.
