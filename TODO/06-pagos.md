# Pagos

- **Stripe/PayPal webhooks (verificación de firma, idempotencia)**

  1. Webhook de Stripe verificado por firma para activar automáticamente una suscripción tras pago exitoso en un SaaS.

- **Manejo de subscripciones (proration, dunning, upgrades/downgrades)**

  1. Dunning management: reintentar cobro 3 veces en 7 días si falla la tarjeta de un cliente, luego suspender la cuenta.
  2. Proration al hacer upgrade de plan Basic a Pro a la mitad del ciclo de facturación en un SaaS B2B.

- **PCI compliance (nunca tocar datos de tarjeta directamente)**

  1. Un e-commerce usa Stripe Elements/Checkout para que el número de tarjeta nunca toque su propio servidor, delegando el alcance PCI al proveedor de pagos.
