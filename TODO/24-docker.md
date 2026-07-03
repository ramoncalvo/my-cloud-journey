# Docker: conceptos, buenas prácticas y seguridad

Ejemplos reales tomados de este mismo repo (`auth/python/Dockerfile`,
`auth/go/Dockerfile`, `auth/docker-compose.yml`) en vez de snippets abstractos.

## 1. Conceptos base

- **Imagen vs contenedor**

  1. La imagen (`auth-python:latest`) es el binario/artefacto inmutable construido una vez; el contenedor es una instancia en ejecución de esa imagen — puedes levantar 5 contenedores de la misma imagen, cada uno con su propio filesystem en capas (copy-on-write) y proceso aislado.

- **Layers (capas) y cache**

  1. Cada instrucción del Dockerfile (`COPY`, `RUN`) crea una capa inmutable; Docker reutiliza capas sin cambios entre builds. En `auth/python/Dockerfile`, `COPY requirements.txt .` + `RUN pip install` van *antes* de `COPY app ./app` a propósito: si solo cambias código de la app, Docker reusa la capa de `pip install` (la más lenta) del cache, en vez de reinstalar dependencias en cada build.

- **Registry**

  1. Docker Hub, Amazon ECR, Azure Container Registry, Google Artifact Registry — el lugar donde se publican las imágenes construidas para que otros entornos (staging, prod, otro desarrollador) las descarguen con `docker pull`.

## 2. Dockerfile: buenas prácticas

- **Multi-stage builds** — separar la etapa de compilación de la etapa de ejecución.

  1. `auth/go/Dockerfile`: la etapa `build` usa la imagen pesada `golang:1.23` (con todo el toolchain de compilación) para generar el binario; la imagen final parte de `gcr.io/distroless/static-debian12` y solo copia el binario ya compilado — el resultado final no tiene el compilador de Go, ni shell, ni gestor de paquetes, solo el binario. De cientos de MB a unos ~15-20MB.

- **Imágenes base mínimas**

  1. `distroless` (sin shell, sin package manager, superficie de ataque mínima) para binarios compilados como Go; `python:3.12-slim` (Debian mínimo, no la imagen completa) en vez de `python:3.12` a secas para reducir tamaño y CVEs de paquetes que ni usas.

- **Orden de instrucciones para maximizar cache**

  1. Copiar y instalar dependencias (`requirements.txt`, `package.json`, `go.mod`) *antes* de copiar el código fuente — el código cambia en cada commit, las dependencias no; invertir el orden invalida el cache de instalación en cada build sin necesidad.

- **`.dockerignore`**

  1. Excluir `.git/`, `node_modules/`, `.env`, `__pycache__/` del contexto de build — sin esto, `docker build` copia y hashea archivos innecesarios (más lento) y arriesga filtrar secretos locales dentro de una capa de la imagen.

- **Un proceso por contenedor**

  1. El contenedor de `auth/python` solo corre `uvicorn`; no intenta correr también un cron o un nginx dentro del mismo contenedor — cada responsabilidad es su propio contenedor, orquestados juntos por Compose/Kubernetes.

## 3. Seguridad en imágenes

- **Nunca correr como root dentro del contenedor**

  1. Un Dockerfile de producción agrega `USER nonroot` (o crea un usuario explícito) antes del `CMD` — si un atacante logra ejecutar código dentro del contenedor, no tiene privilegios de root del contenedor (que en configuraciones mal aisladas puede facilitar escape al host).

- **Nunca hornear secretos en una capa**

  1. `RUN echo $API_KEY > config.txt` dentro del Dockerfile deja el secreto en esa capa **para siempre**, aunque lo borres en una instrucción posterior — las capas anteriores siguen existiendo en la imagen y son extraíbles con `docker history`/`docker save`. Los secretos se inyectan en runtime (variables de entorno del contenedor, como hace `auth/docker-compose.yml` con `env_file: .env`), nunca en build time.

- **BuildKit secrets (`--secret`)** — para secretos que sí necesitas *durante* el build (ej. un token privado de npm/pip).

  1. `RUN --mount=type=secret,id=npm_token npm install` monta el secreto solo durante esa instrucción, sin dejarlo en ninguna capa final de la imagen.

- **Escaneo de imágenes (Trivy, Grype, Docker Scout)**

  1. Un job de CI corre `trivy image auth-python:latest` y falla el pipeline si encuentra una CVE crítica en una dependencia del sistema operativo base, antes de publicar la imagen al registry.

- **Firmar imágenes (Sigstore/cosign)** — ver también [18-cicd-github-actions.md](18-cicd-github-actions.md).

  1. El cluster de producción rechaza correr cualquier imagen sin firma verificada, evitando que alguien despliegue una imagen que no pasó por el pipeline oficial.

- **Imágenes reconstruidas regularmente**

  1. Reconstruir (`docker build --no-cache`) semanalmente aunque el código no cambie, para capturar parches de seguridad del sistema operativo base publicados desde el último build.

## 4. Docker Compose (orquestación local)

- **Un servicio por bloque, red compartida implícita**

  1. `auth/docker-compose.yml` define `python`, `nestjs`, `go` como servicios independientes; Compose crea una red bridge propia donde cada servicio puede llamar a los otros por nombre (`http://python:8000`), sin necesidad de conocer IPs.

- **`env_file` vs `environment`**

  1. `env_file: .env` carga variables compartidas (credenciales de las 3 nubes); `environment:` con valores explícitos sobreescribe una variable específica por servicio (ej. `BASE_URL` distinto por puerto) — Compose aplica `environment` con prioridad sobre `env_file` para la misma clave.

- **`depends_on` — orden de arranque, no espera de disponibilidad real**

  1. `depends_on: [db]` garantiza que el contenedor de `db` *arranque* antes, pero no que Postgres ya acepte conexiones — para eso se necesita un healthcheck + `condition: service_healthy`, o retry logic en la propia app.

- **Comandos de un solo servicio (`make up STACK=go`)**

  1. Como en el `Makefile` de este repo: `docker compose up -d go` reconstruye/levanta solo ese servicio sin tocar los demás — útil para iterar rápido en un solo stack sin reiniciar todo.

## 5. Redes en Docker

- **Bridge network (default)**

  1. El modo por defecto de Compose — cada contenedor tiene su propia IP interna en una red aislada del host, y Docker resuelve DNS interno por nombre de servicio.

- **Host network**

  1. El contenedor comparte la pila de red del host directamente (sin NAT) — más rápido pero pierdes el aislamiento de puertos; poco usado salvo casos de performance muy específicos.

- **None network**

  1. Sin red en absoluto — para contenedores batch que no necesitan ni deben tener conectividad de red.

- **Exposición de puertos (`ports` vs `expose`)**

  1. `ports: ["8000:8000"]` publica el puerto al host (accesible desde fuera de Docker); `expose: ["8000"]` solo lo hace visible a otros contenedores de la misma red Compose, no al host — los Dockerfiles de este repo usan `EXPOSE` como documentación, y es `docker-compose.yml` quien decide publicarlo al host con `ports`.

## 6. Volúmenes y persistencia

- **Named volumes vs bind mounts**

  1. Un named volume (`postgres_data:`) es gestionado por Docker, sobrevive a `docker compose down` (salvo `-v`), ideal para datos de una DB. Un bind mount (`./src:/app/src`) monta una carpeta del host directamente — típico en desarrollo para hot-reload sin reconstruir la imagen en cada cambio.

- **Contenedores son efímeros por diseño**

  1. Cualquier archivo escrito dentro del contenedor sin volumen se pierde al recrearlo (`docker compose up --force-recreate`) — solo lo que vive en un volumen o se sube externamente (S3, DB) persiste de verdad.

## 7. Recursos y healthchecks

- **Límites de CPU/memoria**

  1. `deploy.resources.limits: { memory: 512M }` evita que un contenedor con memory leak consuma toda la RAM del host y tumbe a los demás contenedores vecinos.

- **HEALTHCHECK**

  1. `HEALTHCHECK CMD curl -f http://localhost:8000/health || exit 1` en el Dockerfile permite que `docker ps` marque el contenedor como `unhealthy` si la app dejó de responder, aunque el proceso siga técnicamente vivo — Kubernetes usa el mismo concepto vía `livenessProbe` (ver doc de Kubernetes).

## 8. Multi-arquitectura (buildx)

- **Build para ARM + x86 desde una sola máquina**

  1. `docker buildx build --platform linux/amd64,linux/arm64 -t myapp .` — necesario si desarrollas en Mac con Apple Silicon (ARM) pero despliegas a instancias x86 en producción, o si quieres soportar instancias ARM (Graviton en AWS) más baratas.

## 9. Debugging

- **Entrar a un contenedor corriendo**

  1. `docker exec -it auth-python-1 sh` — inspeccionar el filesystem/procesos en vivo sin reconstruir nada; en una imagen `distroless` (sin shell) esto no funciona, hay que usar `docker debug` (Docker Desktop) o herramientas de debug efímeras adjuntas al mismo namespace.

- **Logs**

  1. `docker compose logs -f go` — el mismo patrón que el `make logs STACK=go` de este repo, sigue el stdout/stderr del contenedor en vivo.
