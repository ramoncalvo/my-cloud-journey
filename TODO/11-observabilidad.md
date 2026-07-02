# Observabilidad

- **Logging estructurado**

  1. Pipeline de campañas de email marketing: cada envío, bounce o reply queda como evento JSON con campos consistentes (`prospect_id`, `campaign_id`, `status`) para poder filtrarlo después.

- **Distributed tracing (OpenTelemetry)**

  1. Flujo GenAI en una consultora: petición del cliente → llamada a RAG → llamada a LLM → respuesta, para saber exactamente dónde se va el tiempo cuando algo es lento.

- **Métricas (Prometheus/Grafana)**

  1. Dashboard de CPU/memoria/latencia de un servidor de trading algorítmico, con alertas si el proceso se cae o si un indicador de riesgo supera cierto umbral.
