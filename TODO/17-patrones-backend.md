# Patrones de backend: Go, Python, NestJS

Mismos conceptos, resueltos con las primitivas propias de cada lenguaje/framework.

## Go

- **Interfaces implícitas (structural typing) — "accept interfaces, return structs"**

  1. `io.Reader`/`io.Writer` permiten que una función de parseo funcione igual sobre un archivo, una conexión de red o un buffer en memoria, sin que el código sepa cuál es — solo le importa que cumpla la interfaz.

- **Functional options pattern** — configurar constructores con muchos parámetros opcionales sin una firma gigante.

  1. `NewServer(WithPort(8080), WithTimeout(30*time.Second))` en vez de `NewServer(port, timeout, retries, logger, ...)` con 8 argumentos posicionales donde es fácil confundir el orden.

- **Error wrapping (`fmt.Errorf("...: %w", err)`) + `errors.Is`/`errors.As`**

  1. Envolver un error de base de datos con contexto (`"failed to fetch user: %w"`) sin perder la capacidad de que el caller siga pudiendo chequear `errors.Is(err, sql.ErrNoRows)` para decidir si es un 404 o un 500.

- **Context propagation (`context.Context`)**

  1. Cancelar una llamada lenta a una API externa en cuanto el cliente HTTP se desconecta, o propagar un `request_id` a través de toda la cadena de llamadas para tracing distribuido.

- **Worker pool (goroutines + channels)**

  1. Procesar un lote de miles de miniaturas de imágenes con N workers fijos leyendo de un channel de trabajos, en vez de lanzar una goroutine sin límite por imagen (que agotaría memoria/FDs).

- **Fan-out / fan-in**

  1. Consultar 3 microservicios en paralelo (inventario, precio, envío) y agregar sus respuestas en un solo objeto antes de responder al cliente.

- **Middleware pattern (envolver `http.Handler`)**

  1. Encadenar logging → auth → recovery alrededor de cada handler HTTP, cada uno como una función que envuelve al siguiente — el mismo concepto que el middleware de Express/NestJS, pero como composición explícita de funciones.

- **Repository pattern vía interfaces**

  1. `type UserRepository interface { FindByID(id string) (*User, error) }` con una implementación Postgres para producción y una implementación en memoria para tests, intercambiables por DI manual (pasar la interfaz al constructor).

- **Composición vía embedding (no herencia)**

  1. Un struct `BaseModel` con `CreatedAt`/`UpdatedAt` embebido en `User` y `Order`, para reusar esos campos sin una jerarquía de clases — Go no tiene herencia, solo composición.

- **Graceful shutdown**

  1. Escuchar `SIGTERM`, dejar de aceptar requests nuevas, esperar a que las in-flight terminen, y solo entonces salir — crítico para que Kubernetes no mate el proceso a mitad de una request durante un rolling deploy.

- **Table-driven tests**

  1. Un solo test que itera sobre un slice de `{input, expected}` para validar una función, en vez de un test separado por caso — el patrón de testing más idiomático en Go.

## Python

- **Decoradores**

  1. `@retry(times=3)` envolviendo una llamada a una API externa poco confiable; `@lru_cache` memoizando una función pura costosa sin tocar su lógica interna.

- **Context managers (`with`)**

  1. `with db.transaction():` garantiza rollback automático si algo lanza una excepción dentro del bloque; un context manager custom para adquirir/liberar un lock distribuido sin arriesgarte a olvidar el `finally`.

- **Generadores / iteradores (evaluación perezosa)**

  1. Exportar un CSV de millones de filas escribiéndolo fila por fila con un generador, en vez de construir la lista completa en memoria antes de escribir.

- **Dependency Injection declarativa (`Depends` de FastAPI)**

  1. Inyectar la sesión de DB o el usuario autenticado actual en un handler de ruta con `def get_order(db: Session = Depends(get_db))`, sin instanciarlos a mano en cada endpoint.

- **Dataclasses / Pydantic models como DTOs**

  1. Un modelo Pydantic valida y serializa automáticamente el body de un request/response de una API — si el cliente manda un campo con tipo incorrecto, FastAPI responde 422 antes de que tu código de negocio se entere.

- **Protocols / ABCs (interfaces estructurales, como en Go)**

  1. Un `Protocol` `PaymentGateway` implementado por `StripeGateway` y `PaypalGateway`, intercambiables vía inyección de dependencias sin que el código que los usa sepa cuál es la implementación concreta.

- **Mixins**

  1. `TimestampMixin` agregando `created_at`/`updated_at` a varios modelos de SQLAlchemy sin duplicar la definición de esas columnas en cada clase.

- **Async/await + `asyncio.gather`**

  1. Llamadas paralelas a 3 APIs de verificación en un flujo de onboarding (ver también [05-concurrencia-performance.md](05-concurrencia-performance.md)), reduciendo el tiempo de respuesta de secuencial a paralelo.

- **Factory functions**

  1. `Shape.create("circle")` devolviendo una instancia de `Circle` o `Square` según un parámetro, centralizando la lógica de qué clase concreta instanciar.

- **Singleton vía instancia a nivel de módulo**

  1. `settings = Settings()` definido una vez en `config.py` e importado en todos lados — Python no necesita un patrón Singleton formal con clases, el sistema de módulos ya garantiza una sola instancia por proceso.

## NestJS

- **Dependency Injection nativa (igual que Angular)**

  1. `UsersService` se inyecta en `UsersController` vía el constructor; el contenedor IoC de Nest resuelve el árbol de dependencias solo, sin `new` manual.

- **Modules** — encapsulación por feature.

  1. `AuthModule` agrupa su controller, service y estrategia de autenticación, y se importa en `AppModule` como una unidad autocontenida.

- **Guards** — autorización a nivel de ruta.

  1. `@UseGuards(JwtAuthGuard)` protegiendo un endpoint — el mismo concepto que los route guards de Angular, aplicado a rutas HTTP en vez de rutas de frontend.

- **Interceptors** — envuelven el ciclo request/response (AOP).

  1. Un `LoggingInterceptor` mide la duración de cada request; un `CacheInterceptor` cachea la respuesta de un GET sin tocar el controller.

- **Pipes** — validación/transformación de datos entrantes.

  1. `ValidationPipe` junto a un DTO con decoradores de `class-validator` rechaza automáticamente un body malformado antes de que llegue al controller.

- **Middleware** — al estilo Express, corre antes del handler.

  1. Un `RequestIdMiddleware` agrega un ID de correlación a cada request entrante, para poder rastrearlo en logs distribuidos.

- **Exception Filters** — manejo de errores centralizado.

  1. Un `HttpExceptionFilter` global transforma cualquier excepción lanzada (de cualquier capa) en un JSON de error con forma consistente, en vez de que cada controller maneje sus propios `try/catch`.

- **Custom providers (`useValue`, `useClass`, `useFactory`)**

  1. Reemplazar el `EmailService` real por un `MockEmailService` en tests, cambiando solo el binding del provider sin tocar el código que lo consume.

- **Repository pattern (vía TypeORM/Prisma)**

  1. `@InjectRepository(User)` inyecta una abstracción sobre la tabla `users`, para que el service no escriba SQL crudo ni sepa qué motor de DB hay detrás.

- **CQRS module (`@nestjs/cqrs`)**

  1. Separar `CreateOrderCommand` (escritura) de `GetOrderQuery` (lectura) usando el paquete oficial de CQRS de Nest, cada uno con su propio handler.

- **Abstracción de transporte en microservicios**

  1. El mismo controller/lógica de negocio se expone por HTTP en desarrollo y se cambia a transporte Kafka/Redis/gRPC en producción modificando solo la configuración del microservicio, sin tocar el código de negocio.

## Tabla resumen: mismo concepto, distinto nombre

| Concepto | Go | Python | NestJS |
|---|---|---|---|
| Interfaces / structural typing | `interface` implícita | `Protocol` / ABC | TypeScript `interface` (borrado en runtime) |
| Inyección de dependencias | Manual (constructor injection) | `Depends()` (FastAPI) | Contenedor IoC nativo |
| Middleware/pipeline de requests | Envolver `http.Handler` | Middleware de Starlette/FastAPI | Middleware + Interceptors + Pipes |
| Validación de datos entrantes | Structs + validación manual/`validator` | Pydantic models | DTOs + `class-validator` + `ValidationPipe` |
| Manejo de errores | `error` + `errors.Is/As` | Excepciones + `try/except` | Excepciones + Exception Filters |
| Concurrencia | Goroutines + channels | `asyncio` (cooperativo) | Event loop de Node (cooperativo) |
| Repository pattern | Interfaz + implementación concreta | Protocol/ABC + implementación | `@InjectRepository` (TypeORM/Prisma) |
| Configuración con muchos opcionales | Functional options pattern | `**kwargs` / dataclass con defaults | Constructor con objeto de opciones |
| Testing idiomático | Table-driven tests | `pytest` fixtures + parametrize | Jest + testing module de Nest |
