# Arquitectura y resiliencia

- **Microservicios vs monolito modular**

  1. Una startup en etapa temprana elige monolito modular para moverse rápido; una vez que el equipo crece a 50+ ingenieros, separa "pagos" y "catálogo" en microservicios porque escalan y despliegan a ritmos distintos.

- **Domain-Driven Design (bounded contexts)**

  1. En un banco, "Onboarding de clientes" y "Gestión de créditos" son bounded contexts separados aunque compartan la entidad "Cliente" — cada uno con su propio modelo y lenguaje.

- **Circuit breakers, retries, timeouts (resiliencia)**

  1. Circuit breaker al llamar a un proveedor externo de scoring crediticio: si falla 5 veces seguidas, se corta la llamada y se devuelve una respuesta cacheada o un flujo alterno, en vez de colgar toda la aplicación.

- **Saga pattern (transacciones distribuidas)**

  1. Flujo de "crear cliente + generar contrato + activar suscripción" en un SaaS: si falla el paso 3, se ejecutan compensaciones que revierten 1 y 2.

- **Service mesh (Istio/Linkerd) — nivel avanzado**

  1. Una empresa con 40+ microservicios usa Istio para manejar mTLS, retries y observabilidad entre servicios sin tener que codificarlo en cada uno individualmente.
