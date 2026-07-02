# Auth y seguridad

- **OAuth2 / OIDC (authorization code, client credentials, PKCE)**

  1. "Login con Google" en el portal de clientes de una farmacia online (authorization code + PKCE en móvil).
  2. Un servicio backend-a-backend de logística usa client credentials para autenticarse contra la API de un proveedor de paquetería, sin usuario humano de por medio.

- **JWT (refresh tokens, rotation, revocation strategies)**

  1. App bancaria: cada refresh invalida el token anterior (rotation) para detectar robo de sesión si alguien reutiliza uno viejo.
  2. Sistema de recursos humanos revoca todos los tokens de un empleado despedido de inmediato, sin esperar expiración natural.

- **RBAC vs ABAC vs ReBAC**

  1. RBAC en un ERP: rol "Auditor" solo lee, "Contador" escribe facturas, "Admin" todo.
  2. ABAC en un hospital: un médico solo accede al expediente de un paciente si está asignado a su departamento *y* dentro de horario de guardia (atributos combinados).
  3. ReBAC en una plataforma de documentos tipo Notion: acceso depende de la relación "es miembro del workspace" o "fue invitado a esta carpeta específica".

- **CORS, CSRF, XSS mitigation**

  1. Un banco configura CORS estricto para que solo su dominio de app web pueda llamar a la API, bloqueando peticiones desde otros orígenes.
  2. Un formulario de transferencia bancaria usa tokens CSRF para evitar que un sitio malicioso ejecute transferencias en nombre del usuario logueado.

- **Secrets management (Vault, cloud KMS)**

  1. Una aseguradora guarda las credenciales de su base de datos y llaves de API de terceros en Vault en vez de en variables de entorno planas, con rotación automática cada 90 días.

- **OWASP Top 10**

  1. Auditoría de seguridad en un e-commerce antes de Black Friday: revisar inyección SQL en filtros de búsqueda, control de acceso roto en endpoints de admin, y validación de inputs en el checkout.
