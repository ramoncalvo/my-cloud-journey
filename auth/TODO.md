# TODO — protocolos de autenticacion/SSO a explorar

Este lab arranco con OIDC (OpenID Connect) sobre OAuth2 porque es lo que
Cognito, Azure AD y Google usan de forma nativa para login de usuarios en
apps web. Estos son otros protocolos relevantes que valdria la pena
añadir como siguientes pasos, con el motivo por el que importan:

## [ ] SAML 2.0 (Security Assertion Markup Language)

**Por que**: es el estandar de SSO empresarial mas usado antes de OIDC,
basado en XML en vez de JSON/JWT. AWS IAM Identity Center, Okta y Active
Directory Federation Services lo usan mucho para federar identidad
corporativa. Si el lab quiere simular "empresa grande con IdP propio
integrandose con las 3 nubes", SAML es el protocolo real que tocaria usar,
no OIDC. Buen ejercicio: montar un IdP SAML de prueba (ej. simplesamlphp)
y federarlo contra AWS IAM Identity Center.

## [ ] WS-Federation

**Por que**: protocolo de Microsoft, predecesor de SAML/OIDC en el mundo
.NET / Active Directory clasico. Cada vez mas en desuso, pero puede
aparecer en integraciones con sistemas legacy de empresas que aun no han
migrado a OIDC/SAML. Relevante mas como cultura general que como algo a
implementar en el lab.

## [ ] FIDO2 / WebAuthn / Passkeys

**Por que**: resuelve un problema distinto al de OAuth2/OIDC — no es
"como delega la app el acceso" sino "como se autentica el usuario sin
contraseña" (biometria, llave de seguridad). Se combina *con* OIDC, no lo
sustituye. Relevante porque es el estandar moderno hacia el que van
Google/Microsoft/Apple; buen siguiente paso una vez el flujo OIDC basico
funcione, para ver como un proveedor (ej. Cognito o Google) puede pedir
un passkey en vez de contraseña dentro del mismo login.

## [ ] Kerberos

**Por que**: protocolo de autenticacion de redes internas, tipico en
Active Directory on-prem. Poco relevante para apps web modernas, pero
aparece si el lab alguna vez simula integracion con infraestructura
on-prem/hibrida (ej. AD Connect hacia Azure AD). Baja prioridad.

## No aplica como alternativa de SSO (se descartan)

- **OAuth 1.0a** — obsoleto, predecesor de OAuth2.
- **CAS** (Central Authentication Service) — SSO academico previo a OIDC, poco uso fuera de universidades.
- **API Keys / HTTP Basic / mTLS** — no son SSO de usuario humano, son autenticacion maquina-a-maquina o de servicio.

## Prioridad sugerida

1. SAML 2.0 — mayor valor real para un perfil devops/enterprise.
2. FIDO2/WebAuthn — tendencia moderna, complementa lo ya construido.
3. WS-Federation y Kerberos — solo si aparece un caso concreto que lo requiera.
