# Algoritmos comunes para problemas de full-stack/backend

## Búsqueda y ordenamiento

- **Binary search**

  1. Buscar el punto de inserción de un nuevo producto en un catálogo ya ordenado por precio, sin recorrerlo entero.
  2. Encontrar el primer registro de log con timestamp >= a una fecha dada, sobre un archivo de logs ordenado cronológicamente.

- **Entender trade-offs de algoritmos de ordenamiento**

  1. Saber cuándo un `ORDER BY` con índice evita que Postgres haga un sort en memoria, vs cuándo hace un external sort en disco por falta de índice — mismo problema, distinto costo según el algoritmo que termina usando el motor.

## Grafos

- **BFS (Breadth-First Search)**

  1. Encontrar el camino más corto en clics dentro de un embudo de conversión de un e-commerce (home → categoría → producto → checkout).
  2. Sugerencias de "amigos en común" en una red social: explorar el grafo de conexiones nivel por nivel.

- **DFS (Depth-First Search)**

  1. Detectar ciclos en un grafo de dependencias de microservicios o de tareas de un pipeline CI/CD (evitar que el servicio A dependa de B que depende de A).
  2. Recorrer recursivamente un árbol de categorías/subcategorías de un catálogo para construir un breadcrumb o un menú anidado.

- **Dijkstra / A***

  1. Calcular la ruta más barata o más rápida en una app de delivery entre el restaurante y el cliente, con "costo" = tiempo o distancia.

- **Topological sort**

  1. Ordenar la ejecución de un DAG de tareas (ej. un pipeline de Airflow o de CI/CD) respetando las dependencias declaradas entre pasos.

- **Union-Find (Disjoint Set)**

  1. Detectar clusters de cuentas relacionadas en un sistema antifraude (mismo dispositivo, misma tarjeta, misma dirección → agrupar como "posible red de fraude").

## Programación dinámica

- **Memoization**

  1. Cachear el resultado de un cálculo de precio dinámico (surge pricing) para no recomputar la misma combinación de parámetros (hora, demanda, zona) en cada request.

- **Knapsack**

  1. Optimizar qué productos incluir en una promoción con presupuesto de descuento limitado, maximizando el valor total sin pasarse del presupuesto.

- **Edit distance (Levenshtein)**

  1. Sugerencias de "quisiste decir..." en un buscador de productos cuando el usuario escribe mal el nombre.

- **Longest Common Subsequence**

  1. Diffing de versiones de un documento colaborativo (tipo Google Docs) para mostrar qué cambió entre dos revisiones.

## Two pointers / sliding window

- **Sliding window**

  1. Calcular rate limiting ("¿cuántos requests hizo este cliente en los últimos 60 segundos?") sin recorrer todo el historial en cada request nuevo.

- **Two pointers**

  1. Detectar pares de transacciones que suman un monto sospechoso en un sistema antifraude, sobre una lista ya ordenada por monto.

## Recursión y backtracking

- **Backtracking**

  1. Generar todas las combinaciones válidas de un configurador de producto (ej. armar una PC con componentes compatibles entre sí), descartando ramas incompatibles apenas se detectan.

- **Recursión sobre árboles**

  1. Calcular el total de una jerarquía de categorías con subtotales (ej. inventario agregado por categoría → subcategoría → producto).

## Hashing

- **Hash maps**

  1. Deduplicar eventos de analytics en tiempo real por su ID, en O(1) por evento en vez de escanear una lista.

- **Consistent hashing**

  1. Distribuir sesiones o claves de cache entre varios nodos de Redis, de forma que agregar/quitar un nodo solo re-mapee una fracción pequeña de las claves en vez de invalidar todo el cache.

- **Bloom filters**

  1. Chequeo rápido de "¿este email ya está en la lista de bloqueados?" sin cargar millones de registros en memoria — a costa de aceptar falsos positivos ocasionales (nunca falsos negativos).

## Árboles y heaps

- **Trie**

  1. Autocompletado de búsqueda de productos o de direcciones mientras el usuario escribe.

- **Heap / priority queue**

  1. Cola de tickets de un sistema de soporte ordenada por prioridad/SLA, donde siempre se atiende primero el más urgente sin importar el orden de llegada.

- **BST (Binary Search Tree) balanceado**

  1. Mantener un leaderboard ordenado en tiempo real de un juego o competencia, con inserciones/consultas de posición en O(log n).

## Algoritmos de strings

- **KMP / Rabin-Karp (búsqueda de patrones)**

  1. Búsqueda eficiente de un patrón dentro de logs muy grandes (el equivalente a un `grep` optimizado, sin re-escanear desde cero en cada posible coincidencia fallida).

## Algoritmos greedy

- **Greedy / interval scheduling**

  1. Asignación de citas médicas para maximizar el número de pacientes atendidos en un día, eligiendo siempre la cita que termina antes entre las que caben.

- **Huffman coding**

  1. Compresión de datos antes de guardarlos o transmitirlos, asignando códigos más cortos a los valores más frecuentes.

## Algoritmos de distribución y escala

- **Load balancing algorithms (round robin, least connections, weighted)**

  1. Un balanceador de carga decide a qué instancia de backend mandar cada request nuevo: round robin para tráfico uniforme, least connections cuando las requests tienen duración muy variable.

- **Rate limiting algorithms (token bucket, leaky bucket, sliding window counter)**

  1. Ver también [01-apis-comunicacion.md](01-apis-comunicacion.md) — mismo problema (limitar requests por cliente), pero aquí importa el algoritmo concreto: token bucket permite ráfagas cortas, leaky bucket suaviza el tráfico a una tasa constante.

- **Consistent hashing**

  1. Ver arriba (Hashing) — es tanto un algoritmo de hashing como una técnica de distribución de carga/datos entre nodos.
