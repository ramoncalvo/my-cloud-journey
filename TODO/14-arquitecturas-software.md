# Arquitecturas de software más comunes

- **Layered / N-tier architecture** — separación clásica en capas (presentación → lógica de negocio → acceso a datos), típica en un ERP corporativo tradicional.
- **Monolito modular** — una sola aplicación desplegable pero con módulos internos bien separados (facturación, usuarios, inventario), común en startups en etapa temprana que quieren orden sin la complejidad operativa de microservicios.
- **Microservicios** — servicios independientes desplegados por separado (pagos, catálogo, envíos), cada uno con su propia base de datos, típico en plataformas de e-commerce a gran escala.
- **Event-Driven Architecture (EDA)** — servicios que reaccionan a eventos en vez de llamarse directamente entre sí, común en sistemas bancarios donde "transacción creada" dispara fraude, notificaciones y contabilidad de forma desacoplada.
- **Hexagonal / Ports and Adapters (Clean Architecture)** — el núcleo de negocio no depende de frameworks ni de la DB; se conecta a través de "puertos" (interfaces) y "adaptadores" intercambiables — útil cuando esperas cambiar de proveedor de DB o de mensajería sin reescribir la lógica de negocio.
- **CQRS + Event Sourcing** — separar comandos (escritura) de queries (lectura), y guardar el estado como secuencia de eventos, típico en sistemas financieros que necesitan auditoría completa del historial.
- **Serverless / Function-as-a-Service** — funciones individuales (ej. AWS Lambda) que procesan eventos puntuales (subir imagen → generar thumbnail), sin mantener servidores corriendo 24/7, común en tareas esporádicas o de bajo tráfico constante.
- **Service-Oriented Architecture (SOA)** — precursora de microservicios, con servicios más grandes conectados vía un ESB (Enterprise Service Bus), aún común en bancos y aseguradoras con sistemas legacy.
- **Micro-frontends** — dividir un frontend grande en piezas independientes desplegadas por separado (ej. "checkout" y "catálogo" como apps distintas), útil cuando varios equipos trabajan en la misma plataforma web sin pisarse.
- **Vertical Slice Architecture (VSA)** — organizar el código por feature/caso de uso completo (de API a DB) en vez de por capa técnica, reduciendo el acoplamiento entre features no relacionadas — alternativa a layered architecture que evita el "god service" compartido.
- **Serverless BFF (Backend for Frontend)** — una capa API dedicada por tipo de cliente (móvil, web, partner externo), cada una adaptando datos a las necesidades específicas de ese consumidor.
- **Modular Monolith → Strangler Fig migration** — patrón de migración gradual de un monolito legacy a microservicios, reemplazando funcionalidad pieza por pieza detrás de un proxy, sin un "big bang rewrite".
