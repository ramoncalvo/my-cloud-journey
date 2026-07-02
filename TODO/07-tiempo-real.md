# Tiempo real

- **WebSockets + fallback a long polling/SSE**

  1. Dashboard en vivo de operaciones bursátiles que actualiza precios por WebSocket, con fallback a long polling si el firewall corporativo del cliente bloquea WS.

- **Server-Sent Events (más simple que WS para casos unidireccionales)**

  1. Notificaciones de "tu pedido está en camino" en una app de delivery, donde el servidor solo empuja actualizaciones y el cliente no necesita enviar datos de vuelta.

- **WebRTC (si hay video/audio)**

  1. Función de videollamada de soporte técnico dentro del portal de un banco o telecom.
