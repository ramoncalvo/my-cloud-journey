# Patrones de frontend: React, Angular, Vue, Svelte

Mismos conceptos, resueltos con las primitivas propias de cada framework.

## React

### Patrones de componentes

- **Container/Presentational** — separar el componente que maneja datos/estado del que solo renderiza.

  1. `ProductListContainer` hace el fetch y maneja loading/error; `ProductList` solo recibe `products` como prop y los pinta.

- **Compound Components** — varios componentes que comparten estado implícito vía Context, exponiendo una API declarativa.

  1. `<Tabs><Tabs.List><Tabs.Tab>...</Tabs.Tab></Tabs.List><Tabs.Panel>...</Tabs.Panel></Tabs>` — el padre coordina cuál tab está activo sin que el consumidor pase props manualmente entre hermanos.

- **Render Props** — un componente recibe una función como children/prop que decide qué renderizar con el estado que él expone.

  1. `<MouseTracker>{(pos) => <Cursor x={pos.x} y={pos.y} />}</MouseTracker>` — cada vez más reemplazado por custom hooks, pero sigue apareciendo en librerías de UI (ej. Downshift para autocompletes).

- **Higher-Order Components (HOC)** — función que envuelve un componente y le inyecta props/comportamiento.

  1. `withAuth(Dashboard)` redirige a login si no hay sesión antes de montar `Dashboard`. Menos usado hoy (los hooks lo reemplazaron en la mayoría de casos), pero sigue vivo en librerías como `connect()` de Redux clásico.

- **Custom Hooks** — extraer lógica con estado reutilizable fuera del componente.

  1. `useDebounce(value, 300)` para no disparar una búsqueda en cada tecla; `useLocalStorage("theme")` para persistir preferencias sin repetir la lógica en cada componente que la necesita.

- **Provider Pattern (Context)** — compartir estado global sin prop-drilling.

  1. `<ThemeProvider>` o `<AuthProvider>` envolviendo toda la app, con un `useAuth()` hook que cualquier componente hijo consume sin pasar props manualmente por 5 niveles.

- **Controlled vs Uncontrolled Components**

  1. Un input controlado (`value` + `onChange` en estado de React) para validar en tiempo real; un input no controlado (`ref` + `defaultValue`) para un formulario grande donde no necesitas re-renderizar en cada tecla (lo que usa React Hook Form por performance).

- **Composition / Slots (children como props)**

  1. `<Card><Card.Header /><Card.Body /></Card>` en vez de un `<Card title="..." body="..." footer="...">` con 10 props — más flexible para layouts que varían.

### Patrones de estado

- **Lifting state up**

  1. Dos hermanos (`FilterPanel` y `ProductGrid`) necesitan el mismo estado de "filtros activos" → se sube al padre común en vez de duplicarlo o sincronizarlo manualmente.

- **State Reducer pattern (`useReducer`)**

  1. Un formulario multi-step con transiciones complejas (`SET_STEP`, `VALIDATE`, `SUBMIT`, `ERROR`) se modela mejor como reducer que como 6 `useState` sueltos que se desincronizan.

- **State machines (XState u otra)**

  1. Un flujo de checkout con estados explícitos (`idle → validating → processing → success/error`) donde transiciones inválidas (ej. pagar dos veces) son literalmente imposibles de representar, no solo "no deberían pasar".

- **Derived/computed state (`useMemo`)**

  1. `const total = useMemo(() => items.reduce(...), [items])` en vez de guardar `total` en su propio `useState` y tener que recordar actualizarlo cada vez que cambian `items`.

### Patrones de performance

- **Memoización (`React.memo`, `useMemo`, `useCallback`)**

  1. Una lista de 500 filas donde cada fila es `React.memo`, para que solo se re-renderice la fila que cambió y no las 499 restantes cuando el padre re-renderiza.

- **Code splitting (`React.lazy` + `Suspense`)**

  1. El editor de texto enriquecido (pesado, ~200kb) de un CMS se carga solo cuando el usuario entra a la pantalla de edición, no en el bundle inicial de toda la app.

- **Virtualización (react-window, react-virtual)**

  1. Una tabla de 50,000 transacciones bancarias solo renderiza las ~20 filas visibles en el viewport, reciclando los nodos DOM al hacer scroll.

- **Debounce/throttle de inputs**

  1. Un buscador de productos espera 300ms de silencio antes de disparar la petición a la API, evitando una request por cada letra tecleada.

### Patrones de datos

- **Fetch-on-render vs render-as-you-fetch (Suspense for data)**

  1. React Query/SWR con `useQuery` dispara el fetch antes de que el componente termine de montar (paralelo a otros fetches), en vez de esperar a montar y *entonces* pedir los datos en cascada.

- **Optimistic updates**

  1. Al dar "like" a un post, la UI se actualiza inmediatamente (antes de la respuesta del servidor) y se revierte solo si la petición falla — sensación de app instantánea.

### Manejo de errores

- **Error Boundaries**

  1. Un widget de terceros embebido (ej. un chat de soporte) está envuelto en su propio Error Boundary, para que si ese widget crashea no tumbe toda la página, solo esa sección.

### Patrones de arquitectura/organización

- **Atomic Design** — atoms → molecules → organisms → templates → pages.

  1. `Button` (atom) → `SearchBar` (molecule: input + button) → `Header` (organism: logo + searchbar + nav) → `HomeTemplate` → `HomePage`.

- **Feature-based folders (vs type-based)**

  1. `features/checkout/{components,hooks,api}` en vez de `components/`, `hooks/`, `api/` sueltos a nivel raíz — todo lo de una feature vive junto, más fácil de tocar sin saltar entre carpetas lejanas (el mismo espíritu que screaming architecture, aplicado a frontend).

## Angular

- **Dependency Injection (DI)** — nativo del framework, no un patrón añadido.

  1. `AuthService` se inyecta en cualquier componente/guard/interceptor que lo necesite; Angular resuelve el árbol de dependencias solo, sin que tú instancies nada a mano.

- **Smart/Dumb components** — el equivalente exacto de Container/Presentational.

  1. `ProductListPageComponent` (smart, inyecta el servicio y maneja el estado) vs `ProductCardComponent` (dumb, solo `@Input()`/`@Output()`).

- **Facade pattern**

  1. Una `CheckoutFacade` que envuelve el store de NgRx (dispatch de acciones + selectors) para que los componentes no conozcan Redux/NgRx directamente, solo llamen métodos simples (`facade.submitOrder()`).

- **Guards (CanActivate, CanDeactivate)**

  1. `authGuard` bloquea la navegación a `/admin` si no hay sesión; `unsavedChangesGuard` pregunta "¿seguro que quieres salir?" si hay un formulario sin guardar.

- **Resolvers**

  1. Precargar los datos de un producto *antes* de activar la ruta `/product/:id`, para que el componente nunca renderice en estado "loading" al entrar.

- **HTTP Interceptors** — el middleware/pipeline pattern aplicado a requests HTTP.

  1. Un interceptor agrega el JWT a cada request saliente; otro captura 401 globalmente y redirige a login sin que cada servicio lo repita.

- **Content projection (`ng-content`)** — el compound components/slots de Angular.

  1. `<app-modal><h2>Título</h2><p>Contenido</p></app-modal>` — el modal no sabe qué contenido lleva, solo dónde proyectarlo.

- **Reactive Forms vs Template-driven Forms**

  1. Un formulario de checkout complejo con validaciones cruzadas usa Reactive Forms (`FormGroup`, `Validators`); un formulario simple de newsletter usa template-driven (`ngModel`) por ser más rápido de escribir.

- **RxJS + `async` pipe**

  1. `products$ = this.http.get<Product[]>(...)` combinado con `*ngIf="products$ | async as products"` en el template — Angular se suscribe/desuscribe solo, sin `useEffect` manual.

- **OnPush change detection**

  1. Un componente con muchos hijos usa `ChangeDetectionStrategy.OnPush` para que Angular solo re-chequee cuando cambian sus `@Input()` por referencia, no en cada ciclo de digest — el equivalente a `React.memo`.

- **State management (NgRx = Redux pattern; Signals desde Angular 16+)**

  1. NgRx para un dashboard complejo con muchas fuentes de estado cruzadas; Signals (más nuevo, más liviano) para estado local/derivado sin la ceremonia de acciones/reducers de NgRx.

## Vue

- **Composition API + Composables** — el equivalente directo a custom hooks de React.

  1. `useDebounce(value, 300)` o `useFetch(url)` como función reutilizable que cualquier componente importa, igual que un hook de React pero sin las reglas de hooks (orden de llamadas) que React exige.

- **Provide/Inject** — el Context de Vue.

  1. `provide('theme', theme)` en un ancestro, `inject('theme')` en cualquier descendiente, sin pasar props por cada nivel intermedio.

- **Slots (default, named, scoped)** — slots con nombre y slots que exponen datos al padre, más potente que `children` de React de entrada.

  1. `<template #header="{ user }">Hola {{ user.name }}</template>` — un scoped slot le pasa datos del hijo al padre para que el padre decida cómo renderizarlos (esto es literalmente el render props pattern, nativo del template).

- **Renderless components**

  1. Un componente `<Mouse>` sin markup propio, solo lógica, que expone la posición del mouse vía scoped slot — la versión Vue del render props pattern de React, pero sin el boilerplate de una función como children.

- **Computed properties**

  1. `const total = computed(() => items.value.reduce(...))` — el `useMemo` de Vue, pero con tracking automático de dependencias (no hace falta declarar el array de deps).

- **Watchers (`watch`, `watchEffect`)**

  1. `watch(searchQuery, debounce(fetchResults, 300))` — efectos secundarios reactivos, el `useEffect` de Vue.

- **Store pattern (Pinia, sucesor de Vuex)**

  1. Un store `useCartStore()` con estado, getters y actions, consumido desde cualquier componente sin prop-drilling — conceptualmente Redux/Zustand, sintaxis más simple.

- **Teleport**

  1. Un modal definido dentro de un componente anidado profundamente, pero renderizado en `document.body` para evitar problemas de `z-index`/`overflow` — el Portal de React.

## Svelte

- **Reactive declarations (`$: total = ...`)**

  1. `$: total = items.reduce((a, b) => a + b.price, 0)` — se recalcula automáticamente cuando `items` cambia, sin `useMemo` ni array de dependencias: el compilador de Svelte detecta la dependencia por análisis estático.

- **Stores (`writable`, `readable`, `derived`)** — primitiva de estado nativa del lenguaje, no una librería externa.

  1. `const cart = writable([])` compartido entre componentes con solo importarlo; `const total = derived(cart, $cart => $cart.reduce(...))` para estado derivado reactivo entre componentes (Pinia/Zustand, pero built-in).

- **Actions (`use:accion`)** — la versión Svelte de un custom hook, pero para comportamiento de DOM.

  1. `<div use:clickOutside={closeMenu}>` encapsula "detectar click fuera de este elemento" como función reutilizable, sin librería externa.

- **Context API (`setContext`/`getContext`)**

  1. Igual que Provide/Inject de Vue o Context de React: un `ThemeProvider.svelte` hace `setContext('theme', theme)`, los hijos leen con `getContext('theme')`.

- **Component events (`createEventDispatcher`)**

  1. Un `<Modal on:close={handleClose}>` — comunicación hijo→padre explícita vía eventos, en vez de pasar un callback como prop (aunque también se puede hacer así).

- **SvelteKit: `load` functions y form actions**

  1. `load()` en `+page.ts` precarga los datos de una ruta antes de renderizar (equivalente al resolver de Angular o a Server Components de React); `actions` en `+page.server.ts` maneja el submit de un formulario del lado servidor sin JS de cliente adicional — progressive enhancement nativo.

## Tabla resumen: mismo concepto, distinto nombre

| Concepto | React | Angular | Vue | Svelte |
|---|---|---|---|---|
| Lógica reutilizable con estado | Custom hook | Service (DI) | Composable | Store / Action |
| Compartir estado sin prop-drilling | Context | DI / Service | Provide/Inject | Context API / Store |
| Contenido inyectado por el padre | children / slots | ng-content | slots | slots |
| Estado derivado memoizado | useMemo | computed (signal) / pipe | computed | `$:` reactive |
| Efecto secundario reactivo | useEffect | RxJS operators | watch | `$:` / onMount |
| Precargar datos antes de renderizar | Suspense / loader (RSC, Next) | Resolver | router-level (ej. vue-router beforeEnter) | `load()` (SvelteKit) |
| Interceptar requests HTTP | fetch wrapper manual | HttpInterceptor | axios interceptor | hooks.server.ts (SvelteKit) |
| Manejo de errores aislado | Error Boundary | ErrorHandler global | errorCaptured | `+error.svelte` (SvelteKit) |
