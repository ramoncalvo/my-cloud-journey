# Accesibilidad (a11y): conceptos, implementación y testing

## 1. Por qué importa (más allá de "hacer el bien")

- **No es solo para personas ciegas**

  1. Un usuario con movilidad reducida en las manos navega solo con teclado (sin mouse); alguien con daltonismo no distingue un error "solo en rojo" si no hay también un ícono o texto; alguien con luz solar fuerte en la calle necesita suficiente contraste de color para leer la pantalla del celular — accesibilidad cubre discapacidad visual, motora, auditiva y cognitiva, y también situaciones temporales (brazo enyesado, ambiente ruidoso).

- **Requisito legal, no solo buena práctica**

  1. ADA (EEUU) y Section 508 exigen accesibilidad en sitios de gobierno y, por jurisprudencia reciente, en muchos sitios comerciales — varias empresas grandes (Domino's, Target) han perdido demandas millonarias por sitios web no accesibles. El European Accessibility Act (EAA) exige cumplimiento en la UE desde 2025 para productos y servicios digitales.

## 2. WCAG (Web Content Accessibility Guidelines)

- **Niveles A, AA, AAA**

  1. **A** (mínimo): alt text en imágenes, contenido operable con teclado. **AA** (el estándar que casi toda regulación exige): contraste de color 4.5:1 mínimo para texto normal, texto redimensionable sin romper el layout. **AAA** (el más estricto, rara vez se exige completo): contraste 7:1, sin dependencia de color en absoluto para transmitir información.

- **4 principios (POUR)** — Perceivable, Operable, Understandable, Robust.

  1. **Perceivable**: el contenido se puede percibir de alguna forma (texto alternativo para imágenes, subtítulos para video). **Operable**: la interfaz se puede operar (funciona con teclado, no depende de gestos complejos). **Understandable**: el contenido y la operación son comprensibles (mensajes de error claros, navegación consistente). **Robust**: funciona con tecnología asistiva actual y futura (HTML semántico, ARIA correcto).

## 3. HTML semántico — la base de todo

- **Usar el elemento correcto en vez de un `<div>` con estilos**

  1. `<button>` en vez de `<div onClick={...}>` — el `<button>` ya viene con foco de teclado, se activa con Enter/Espacio, y un lector de pantalla anuncia "botón" automáticamente; un `<div>` clickeable no hace nada de eso sin trabajo extra manual (y es fácil olvidar algún caso).

- **Landmarks (`<nav>`, `<main>`, `<header>`, `<footer>`, `<aside>`)**

  1. Un usuario de lector de pantalla puede saltar directamente a `<main>` con un atajo de teclado, sin tener que escuchar todo el menú de navegación en cada página — sin landmarks semánticos, todo suena como una lista plana de `<div>`s indistinguibles.

- **Jerarquía de encabezados correcta (`h1` → `h2` → `h3`, sin saltar niveles)**

  1. Un lector de pantalla permite navegar "salto de encabezado en encabezado" como si fuera una tabla de contenidos — si saltas de `h1` a `h4` porque "se veía del tamaño que quería", rompes esa navegación para quien depende de ella (el tamaño visual se controla con CSS, no eligiendo mal el nivel semántico).

## 4. ARIA — cuando el HTML nativo no alcanza

- **Regla de oro: "no ARIA es mejor que ARIA mal usado"**

  1. Un `<div role="button" tabindex="0">` que solo declara el rol pero no maneja `onKeyDown` para Enter/Espacio es *peor* que no tener ARIA — le dice al lector de pantalla "esto es un botón" pero luego no se comporta como uno, generando confusión activa en vez de solo falta de información.

- **Estados dinámicos (`aria-expanded`, `aria-hidden`, `aria-live`)**

  1. Un dropdown/acordeón anuncia `aria-expanded="true"/"false"` para que el lector de pantalla sepa si está abierto o cerrado; una notificación tipo toast usa `aria-live="polite"` para que se anuncie automáticamente sin que el usuario tenga que estar enfocado ahí cuando aparece.

- **Labels accesibles (`aria-label`, `aria-labelledby`)**

  1. Un botón de ícono solo (una lupa, sin texto visible) necesita `aria-label="Buscar"` — visualmente se entiende por el ícono, pero un lector de pantalla no "ve" el ícono, solo leería "botón" sin ningún contexto sin el label.

## 5. Navegación por teclado

- **Todo lo interactivo debe ser alcanzable y operable solo con teclado**

  1. Tab para moverse entre elementos interactivos, Enter/Espacio para activar, flechas para navegar dentro de un componente compuesto (un menú, un date picker) — probar "¿puedo usar toda la app sin tocar el mouse?" es el test más rápido y revelador de problemas de accesibilidad.

- **Focus visible**

  1. Nunca hacer `outline: none` sin poner un estilo de foco alternativo — es la única forma en que un usuario de teclado sabe *dónde* está parado en la página; quitarlo "porque se ve feo" rompe completamente la navegación para ese usuario.

- **Focus trap en modales**

  1. Al abrir un modal, el foco de teclado debe quedar atrapado dentro de él (Tab no debe poder salir accidentalmente al contenido de fondo) y regresar al elemento que abrió el modal al cerrarlo — sin esto, un usuario de teclado puede "perderse" navegando contenido invisible detrás del overlay.

- **Skip links**

  1. Un enlace "Saltar al contenido principal", visualmente oculto pero el primer elemento tabulable de la página, visible solo cuando recibe foco — evita que un usuario de teclado tenga que tabular por 20 enlaces de navegación en cada página antes de llegar al contenido real.

## 6. Contraste y color

- **Ratio de contraste mínimo (WCAG AA: 4.5:1 texto normal, 3:1 texto grande)**

  1. Texto gris claro sobre fondo blanco puede verse "elegante" en el mockup de diseño pero fallar el mínimo de contraste — herramientas como el contrast checker de WebAIM validan esto automáticamente antes de aprobar un diseño.

- **Nunca depender solo del color para transmitir información**

  1. Un formulario que marca un campo inválido *solo* poniendo el borde en rojo es invisible para alguien con daltonismo — agregar también un ícono de error y un mensaje de texto ("Este campo es requerido") comunica lo mismo sin depender del color.

## 7. Formularios accesibles

- **`<label>` asociado explícitamente a cada input**

  1. `<label for="email">Email</label><input id="email">` — sin esto, un lector de pantalla anuncia el input sin decir para qué es; un placeholder no es un reemplazo de label (desaparece al escribir, y muchos lectores de pantalla no lo anuncian igual).

- **Mensajes de error asociados y anunciados**

  1. `aria-describedby` apuntando al mensaje de error, más `aria-live` para que se anuncie automáticamente al aparecer tras un submit fallido, en vez de un mensaje de error que solo se ve pero no se anuncia a quien usa lector de pantalla.

## 8. Multimedia

- **Alt text en imágenes**

  1. Una imagen decorativa lleva `alt=""` (vacío a propósito, para que el lector de pantalla la ignore); una imagen con información real lleva `alt="Gráfico de ventas mostrando un aumento del 30% en Q4"` — describir el *contenido/propósito*, no repetir "imagen de..." innecesariamente.

- **Subtítulos y transcripciones**

  1. Un video corporativo incluye subtítulos (accesibilidad auditiva) y una transcripción de texto completa (accesibilidad + SEO + usuarios que prefieren leer) — requisito legal en muchos países para contenido educativo/gubernamental.

## 9. Accesibilidad cognitiva y de movimiento

- **`prefers-reduced-motion`**

  1. Un usuario con vestibular disorder (mareo por movimiento) tiene esa preferencia activada en su SO; el sitio detecta `@media (prefers-reduced-motion: reduce)` y desactiva animaciones/parallax agresivos automáticamente, en vez de forzarlas a todos por igual.

- **Lenguaje claro y consistencia**

  1. Usar el mismo texto para la misma acción en toda la app ("Eliminar" siempre, no "Eliminar" en una pantalla y "Borrar"/"Quitar" en otra) — reduce carga cognitiva, beneficia especialmente a usuarios con discapacidad cognitiva pero mejora la experiencia para todos.

- **Tiempo suficiente para completar acciones**

  1. Una sesión que expira en 60 segundos sin aviso es una barrera para alguien que lee más lento; dar la opción de extender el tiempo antes de expirar (con una alerta previa) es un requisito explícito de WCAG.

## 10. Testing de accesibilidad

- **Automatizado (cubre ~30-40% de los problemas reales, no reemplaza testing manual)**

  1. **axe-core** (integrable en tests de Jest/Cypress, falla el build si hay violaciones); **Lighthouse** (auditoría de Chrome DevTools, da un score); **WAVE** (extensión de navegador con reporte visual directo sobre la página).

- **Manual con teclado**

  1. Navegar el flujo completo de checkout de un e-commerce usando solo Tab/Enter/Espacio/flechas, sin tocar el mouse — revela problemas de focus trap, orden de tabulación ilógico, o elementos inalcanzables que ninguna herramienta automatizada detecta.

- **Manual con lector de pantalla**

  1. Probar con VoiceOver (Mac/iOS, gratis, integrado), NVDA (Windows, gratis) o JAWS (Windows, de pago, el más usado en entornos corporativos/gobierno) — escuchar cómo realmente suena la página revela problemas de ARIA mal usado, labels faltantes, o anuncios de estado que nunca ocurren.

- **Integración en CI** (ver también [18-cicd-github-actions.md](18-cicd-github-actions.md))

  1. Un job de GitHub Actions corre `axe` contra el build de staging en cada PR y comenta las violaciones encontradas directamente en el PR, igual que un linter de código — atrapar regresiones de accesibilidad antes del merge, no en un audit anual.

## 11. Accesibilidad en frameworks (relacionado con [16-patrones-frontend.md](16-patrones-frontend.md))

- **React**: Radix UI, React Aria (Adobe) o Headless UI dan componentes (modal, dropdown, tabs) con ARIA y manejo de teclado ya resuelto correctamente — reimplementar un dropdown accesible desde cero es mucho más difícil de lo que parece (focus management, ARIA, navegación por flechas).
- **Vue**: Headless UI también tiene versión Vue; Vuetify/PrimeVue (component libraries completas) ya incluyen soporte ARIA en sus componentes.
- **Angular**: Angular CDK (`@angular/cdk/a11y`) provee `FocusTrap`, `LiveAnnouncer` y utilidades de manejo de foco listas para usar, sin reinventar la lógica de accesibilidad de bajo nivel.
- **Regla general**: para componentes complejos (date picker, combobox, tabs), preferir una librería headless con accesibilidad ya resuelta antes que construir uno desde cero — es la parte de UI donde más fácil es fallar sutilmente sin darte cuenta.
