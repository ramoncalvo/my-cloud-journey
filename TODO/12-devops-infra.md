# DevOps/Infra

- **CI/CD pipelines**

  1. Pipeline que corre tests y despliega automáticamente a producción vía Docker cuando se hace push a `main`, con un stage de rollback automático si el health check falla.

- **Infrastructure as Code (Terraform)**

  1. Versionar la configuración de infraestructura cloud (VMs, redes, túneles) en Terraform en vez de tenerla montada manualmente — si algo se corrompe, se recrea en minutos.

- **Blue-green / canary deployments**

  1. Blue-green en el sitio público de una empresa: mientras se sube una nueva versión, el tráfico sigue en la versión estable hasta confirmar que la nueva funciona, sin downtime.
  2. Canary deployment en una fintech: la nueva versión del API de pagos recibe solo 5% del tráfico real primero, y si las métricas de error se mantienen normales, se libera al 100%.
