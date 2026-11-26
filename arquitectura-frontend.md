# Arquitectura del frontend

Documento de diseño del frontend del organizador de reservas, armado a partir del **mockup** ("Reservas Apart — Mockup", 7 pantallas interactivas). Las reglas de negocio están en [CLAUDE.md](CLAUDE.md) y el backend en [arquitectura-backend.md](arquitectura-backend.md); acá se describe **qué pantallas hay, cómo se componen, cómo se navega entre ellas y con qué sistema visual se construyen**, en Livewire + Flux + Tailwind.

> **Convención de idioma:** igual que en el backend. Componentes, props, clases CSS propias, nombres de archivos y variables van en **inglés**; las URLs y los textos visibles, en **español** (en `lang/es/*.php`).

> **El mockup es la referencia visual, no el código.** Está hecho con datos de ejemplo de noviembre 2026 y estado en memoria. Lo que hay que copiar es la estructura, los textos, los estados y los estilos; la lógica sale de las queries y actions del backend.

---

## 1. Mapa de pantallas

| # | Pantalla (mockup) | URL | Ruta (nombre) | Tipo | Componente Livewire |
|---|---|---|---|---|---|
| 1 | Calendario | `/calendario` | `calendar` | Página (inicio) | `Calendar\Index` |
| 2 | Detalle de reserva | `/calendario?reserva={id}` y `/reservas/{reservation}` | `reservations.show` | Panel lateral sobre el calendario | `Reservations\Panel` |
| 3 | Editar reserva | mismo panel, modo edición | `reservations.edit` | Panel lateral | `Reservations\Panel` (modo `edit`) |
| 4 | Buscador + Nueva reserva | `/buscador` | `availability` | Página + drawer | `Availability\Search` + `Reservations\CreateForm` |
| 5 | Clientes | `/clientes` | `guests.index` | Página + drawer | `Guests\Index` |
| 6 | Departamentos | `/departamentos` | `apartments.index` | Página + drawer | `Apartments\Index` |
| 7 | Empleados | `/empleados` | `employees.index` | Página + drawer | `Employees\Index` |
| — | Login, perfil, contraseña | las del starter kit | las del starter kit | Página | starter kit |

El login y la configuración de perfil no están en el mockup: se usan los del starter kit, **restilizados con los tokens de la sección 3** y sin la ruta de registro.

### Navegación entre pantallas

```
                ┌──────────── header: Calendario · Buscador · Clientes · Departamentos · Empleados
                │
 Calendario ──"Nueva reserva" / "Buscar disponibilidad"──► Buscador ──"Reservar"──► Drawer Nueva reserva
    │  ▲                                                     ▲   ▲                       │  "Cambiar" vuelve al buscador
    │  └────────────── "Guardar reserva" ────────────────────┼───┼───────────────────────┘
    │ click en bloque                                        │   │
    ▼                                                        │   │
 Panel Detalle ──"Editar"──► Panel Editar ──"Cambiar" (fechas/pers./depto)─┘   │
    │  ├─"Mover de departamento" ─► modo Mover ─► "Mover al X" ─► confirmación  │
    │  ├─"Cancelar reserva" ─► confirmación ─► estado Cancelada                  │
    │  └─"Ver ficha" ─► Clientes (drawer del cliente) ─"Nueva reserva para este cliente"
    │                                   └─ historial ─► Panel Detalle
 Departamentos (drawer) ─ reservas futuras ─► Panel Detalle · "Ver en calendario" ─► Calendario
```

- La navegación principal usa `wire:navigate` (viene con el starter kit) para que el cambio de página no recargue todo.
- **El estado que importa va en la URL** (`#[Url]` de Livewire): la ventana del calendario, la búsqueda del buscador y la reserva abierta en el panel. Así un link se puede compartir, el botón "atrás" funciona y "Cambiar" puede volver al buscador con los datos intactos.

---

## 2. Layout general (todas las páginas)

### Header (`<x-app.header>`)
El mockup usa **navegación superior**, no barra lateral. El starter kit de Livewire trae las dos variantes de layout: se usa la de header (`layouts/app/header.blade.php`) y se descarta la de sidebar.

| Zona | Contenido | Estilo |
|---|---|---|
| Marca | Cuadrado negro 30×30 con "A" + "Aridan" (`<x-app.brand>`) | `ink`, radio 4px, 15px/700 |
| Nav | Calendario, Buscador, Clientes, Departamentos, Empleados | Links 14px/500 `muted`; el activo con fondo `accent-soft`, texto `accent`, 600 y `aria-current="page"` |
| Usuario | Nombre + avatar circular 32px **con el color del empleado** y su inicial | El avatar recuerda a cada uno cuál es su color. Al tocarlo, el menú del starter kit (perfil, salir) |

- Altura mínima 60px, fondo `surface`, borde inferior `border`.
- Debajo de `lg` la nav se pliega en un botón de menú (ver Responsive en §6).

### Contenedor de página
- Fondo `canvas` (#F4F4F1); `main` con padding 24px y `gap` 16–18px entre bloques.
- Ancho máximo: 1480px en el calendario (necesita lugar para 30 días), 1280px en el buscador, 1440px en los ABM.
- **Cabecera de página** (patrón repetido): título h1 26px/700 con `letter-spacing: -0.01em`, una línea de ayuda 14px `subtle` debajo, y el botón principal a la derecha ("Nuevo cliente", "Nuevo departamento", "Nuevo empleado").

---

## 3. Sistema de diseño

### 3.1 Principios visuales
- **Sobrio y de trabajo:** neutros cálidos, un solo color de acento (azul) y los colores fuertes reservados a **los empleados**. Así el color en el calendario siempre significa "quién es el responsable" y no compite con la interfaz.
- **Densidad de planilla:** filas compactas, números tabulares y mucha información en poco espacio, como el Excel que reemplaza.
- **Semántica fija:** verde = OK/activo, naranja = advertencia (no bloquea), rojo = error o acción destructiva, azul = acción/selección.

### 3.2 Paleta (tokens)

**Neutros**

| Token | Hex | Uso |
|---|---|---|
| `ink` | `#15171A` | Texto principal, marca, avatar de cliente |
| `ink-2` | `#2B2E33` | Texto de párrafos y descripciones |
| `muted` | `#44474D` | Etiquetas de campos, encabezados de tabla, títulos de sección |
| `subtle` | `#5E6166` | Ayudas, subtítulos, capacidad junto al número |
| `faint` | `#6B6E73` | Días de semana, ranking, texto deshabilitado |
| `disabled` | `#8A8D92` | Número de un depto inactivo |
| `canvas` | `#F4F4F1` | Fondo de página, tiles de dato, tarjetas de resumen |
| `surface` | `#FFFFFF` | Tarjetas, tablas, drawers, inputs |
| `surface-alt` | `#FAFAF8` | Encabezados de tabla y de grilla, pie de drawers |
| `track` | `#E9E9E4` | Fondo de controles segmentados; "libre" en el mapa de ocupación |
| `chip` | `#ECECE7` | Badges neutros ("Inactivo", "Pasada", "No disponibles") |
| `weekend` | `#F7F7F4` / `#F2F2EE` | Columna / encabezado de fin de semana |

**Bordes**

| Token | Hex | Uso |
|---|---|---|
| `border` | `#E2E2DC` | Contorno de tarjetas y del header |
| `divider` | `#E7E7E2` | Separadores internos, líneas de días en la grilla |
| `divider-soft` | `#EFEFEA` | Separador entre filas de tabla |
| `border-control` | `#DADAD4` | Botones secundarios, opciones de radio |
| `border-input` | `#CFCFC8` | Inputs, textarea, stepper |

**Acento y estados**

| Token | Hex | Uso |
|---|---|---|
| `accent` | `#1F5FD1` | Botón primario, links, selección, hoy, `accent-color` de checkboxes |
| `accent-hover` | `#174AA6` | Hover de links y botón primario |
| `accent-soft` | `#EEF3FD` | Nav activa, badge "Próxima", "sobra capacidad", encabezado de hoy |
| `accent-softer` | `#F3F7FE` | Fila u opción seleccionada, columna de hoy |
| `accent-best` | `#F7FAFF` | Fondo de la fila "Mejor opción" |
| `accent-disabled` | `#9AA8C2` | Botón primario deshabilitado |
| `success-bg` / `-border` / `-fg` / `-strong` | `#E3F1E8` / `#A9D5B9` / `#17603A` / `#124A2D` | "Activo", "Capacidad justa", "En curso", mensaje de éxito |
| `warning-bg` / `-border` / `-fg` / `-badge` | `#FDF0E6` / `#F1C9A5` / `#6E3507` / `#93450A` | Advertencias: capacidad, no movible, color repetido, no se puede desactivar |
| `danger-bg` / `-border` / `-fg` / `-solid` | `#FDECEA` / `#F3B8B1` / `#8A1C12` / `#B42318` | Errores, "Cancelada", botón "Sí, cancelar", asterisco de obligatorio |
| `holiday` | `#C2410C` | Punto de feriado en el calendario |

**Mapa de ocupación (buscador)**

| Token | Hex | Significado |
|---|---|---|
| `occ-fixed` | `#1E3A8A` | Reserva fija (no movible) |
| `occ-movable` | `#93C5FD` | Reserva movible |
| `occ-free` | `#E9E9E4` | Libre |
| `occ-requested` | `#FED7AA` + borde 2px `#C2410C` | Lo pedido (texto de fecha `#9A3412`) |

Fijas y movibles se distinguen por **luminosidad**, no solo por tono, para que se lean también con daltonismo.

**Paleta de empleados** (la que ya usa la planilla, más 5):

| Nombre | Hex | | Nombre | Hex |
|---|---|---|---|---|
| Celeste | `#4fc3f7` | | Gris | `#b0bec5` |
| Verde | `#81c784` | | Turquesa | `#4db6ac` |
| Rosa | `#f06292` | | Amarillo | `#fff176` |
| Naranja | `#ffb74d` | | Coral | `#ff8a65` |
| Violeta | `#ba68c8` | | Lavanda | `#9fa8da` |

- Son tonos claros: el texto del bloque va siempre en `ink`. Como también se permite "Otro color" libre, hay que calcular el contraste: si el color es oscuro, el texto pasa a blanco. Va en un helper (`App\Support\ColorContrast::textOn($hex)`), no en la vista.
- La paleta vive en `config/employee_colors.php`, una sola vez (en la planilla estaba duplicada a mano).
- Los bloques y muestras de color llevan un borde `rgba(20,22,26,0.2)` para que los colores claros (amarillo) no se pierdan sobre blanco.

### 3.3 Tipografía
- **Familia:** Space Grotesk (Google Fonts, pesos 400/500/600/700), con `system-ui, sans-serif` de respaldo. Reemplaza a la Instrument Sans que trae el starter kit.
- **`font-variant-numeric: tabular-nums` en el `body`:** fechas, montos y contadores quedan alineados en columnas, como en una planilla.

| Rol | Tamaño / peso | Dónde |
|---|---|---|
| H1 de página | 26 / 700, `-0.01em` | "Noviembre 2026", "Clientes" |
| Título del panel | 22 / 700 | Nombre del huésped en el detalle |
| Título de drawer | 20 / 700 | "Nueva reserva", "Editar reserva" |
| H2 de sección | 17 / 700 | "Disponibles (6)" |
| Número de depto | 19 / 700 (buscador), 17 (opciones), 15 (calendario) | |
| Valor de tile | 16 / 700 | "Pagado · $200.000" |
| Cuerpo / botones | 14 / 400–600 | |
| Secundario | 13 | Ayudas, links chicos, resumen |
| **Eyebrow de sección** | 13 / 700, MAYÚSCULAS, `0.04em`, `muted` | "CLIENTE", "PAGO", "CONDICIONES" |
| Etiqueta de campo / leyenda | 12 / 600 | |
| Líneas 2 y 3 del bloque | 11 / 500 | |
| Día de la semana | 10 / 600 | |
| M / T | 9 | |

### 3.4 Forma, espacio y elevación
- **Radios:** 4px en casi todo (botones, inputs, tarjetas), 3px en piezas internas (segmentos, badges), 2px en muestras y cuadraditos del mapa, 6px en drawers, círculo en avatares.
- **Alturas de control:** botón de página 40px; botón de drawer o pie 44px (objetivo táctil); input 40–42px; botón de ícono 36×36; segmento 36px dentro de un riel con 3px de padding.
- **Espaciado:** padding de página 24, de tarjeta 16–18, de drawer 22; `gap` 12 entre campos de una grilla, 5 entre etiqueta e input, 20 entre secciones de un formulario.
- **Elevación:** solo los drawers. Fondo de overlay `rgba(21,23,26,0.45)`. Las tarjetas no tienen sombra: se separan con borde.
- **No hay modales centrados: todo se abre en un drawer.** Un drawer es un panel que entra desde la derecha (`flux:modal variant="flyout"`, 560px en escritorio, ancho completo en pantallas chicas) y deja ver la pantalla de fondo. Se usa para el alta de reserva, el detalle de reserva, y las fichas de depto, empleado y cliente. Las confirmaciones (cancelar una reserva) van dentro del mismo drawer, no en una ventana aparte.
- **Iconos:** de trazo, 13–20px, `stroke-width` 2–2.6, en `currentColor`. Flux trae Heroicons; los que no existen ahí van como componentes Blade propios (ver 4.3).

### 3.5 Implementación en Tailwind 4 + Flux

El starter kit trae Tailwind 4, que se configura en CSS. Los tokens van en `resources/css/app.css`:

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';

@theme {
  --font-sans: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;

  --color-ink: #15171A;
  --color-ink-2: #2B2E33;
  --color-muted: #44474D;
  --color-subtle: #5E6166;
  --color-faint: #6B6E73;
  --color-canvas: #F4F4F1;
  --color-surface: #FFFFFF;
  --color-surface-alt: #FAFAF8;
  --color-track: #E9E9E4;
  --color-chip: #ECECE7;
  --color-border: #E2E2DC;
  --color-divider: #E7E7E2;
  --color-border-control: #DADAD4;
  --color-border-input: #CFCFC8;

  --color-success-bg: #E3F1E8;  --color-success-fg: #17603A;
  --color-warning-bg: #FDF0E6;  --color-warning-border: #F1C9A5;  --color-warning-fg: #6E3507;
  --color-danger-bg:  #FDECEA;  --color-danger-border:  #F3B8B1;  --color-danger-fg:  #8A1C12;
  --color-danger: #B42318;
  --color-holiday: #C2410C;
  --color-occ-fixed: #1E3A8A;
  --color-occ-movable: #93C5FD;
  --color-occ-requested: #FED7AA;

  --radius-ui: 4px;
}

/* Acento de Flux (botones primarios, foco, checkboxes) */
@theme {
  --color-accent: #1F5FD1;
  --color-accent-content: #1F5FD1;
  --color-accent-foreground: #FFFFFF;
}

body { font-variant-numeric: tabular-nums; }
```

- **Acento:** Flux toma el color de acento de esas tres variables, así que todos sus componentes quedan en el azul del mockup sin tocarlos uno por uno.
- **Neutros de Flux:** Flux usa la escala `zinc`. Para que sus componentes queden en los grises cálidos del mockup se redefine `--color-zinc-50…950` en `@theme` con la escala cálida. Es la forma que documenta Flux para cambiar el color base.
- **Colores dinámicos** (empleado y huésped): Tailwind no puede generar clases para un hex que viene de la base. Se pasan por `style="--employee: {{ $color }}"` y una clase usa `bg-(--employee)`.
- **Modo oscuro:** el mockup es solo claro. El starter kit trae selector de apariencia: **se deja fijo en claro** en esta iteración (se quita la opción del perfil), porque los colores de empleado están pensados sobre fondo claro.
- **Fuente:** se carga con `<link>` a Google Fonts en el `<head>` del layout, o se autoaloja con `@font-face` para no depender de un tercero.

---

## 4. Componentes

### 4.1 Mapeo a Flux

Flux es la única librería de componentes (regla del proyecto). Algunos componentes son de **Flux Pro**; si no hay licencia Pro, esos se arman como componente Blade propio con los tokens de arriba. **Verificado con Flux 2.13:** la versión gratuita incluye tabla y toast; el date picker es Pro.

| Necesidad del mockup | Flux | Si no está en Flux gratuito |
|---|---|---|
| Botón primario / secundario / peligro / ícono | `flux:button` (`variant="primary"`, `"filled"`, `"danger"`, `square`) | — |
| Input, textarea, label, error | `flux:input`, `flux:textarea`, `flux:field`, `flux:label`, `flux:error`, `flux:description` | — |
| Checkbox ("Movible", "Entrada temprana") | `flux:checkbox` | — |
| Interruptor "Activo" | `flux:switch` (en el mockup es un checkbox; un switch se entiende mejor) | — |
| Control segmentado (Pendiente/Señado/Pagado, Mes/2 semanas) | `flux:radio.group variant="segmented"` | `<x-ui.segmented>` |
| Radio en tarjeta (elegir depto) | `flux:radio.group variant="cards"` | `<x-ui.radio-card>` |
| Drawer (todo formulario o ficha: alta de reserva, detalle, depto, empleado, cliente) | `flux:modal variant="flyout"` | — |
| Badge de estado | `flux:badge` (colores `green`, `zinc`, `blue`, `red`, `orange`) | — |
| Avisos (advertencia, error, éxito) | `flux:callout` (`variant="warning"`, `"danger"`, `"success"`) | `<x-ui.callout>` |
| Avatar con iniciales | `flux:avatar` | — |
| Navegación del header | `flux:navbar`, `flux:navbar.item`, `flux:profile` | — |
| Tablas de los ABM | `flux:table` (incluida en Flux gratuito) | — |
| Fecha | `flux:date-picker` (Pro) | `<input type="date">` nativo, como en el mockup |
| Notificación al guardar | `flux:toast` (incluido en Flux gratuito) | — |

### 4.2 Componentes Blade propios (`resources/views/components`)

Son piezas del dominio que se repiten en varias pantallas. Son Blade anónimos (sin estado); el estado vive en el componente Livewire que los usa.

| Componente | Props | Dónde se usa | Descripción |
|---|---|---|---|
| `<x-employee-swatch>` | `color`, `size` | Leyenda, pie de drawers, detalle, historial | Cuadrado 10–12px con el color del empleado y borde translúcido. |
| `<x-status-badge>` | `status` | ABM, historial | Badge 12px/700: Activo / Inactivo / Próxima / En curso / Pasada / Cancelada, con su par de colores. |
| `<x-section-title>` | slot | Drawers y panel | El *eyebrow* en mayúsculas. |
| `<x-stay-summary>` | `stay`, `changeUrl` | Drawer Nueva reserva, Editar | Tarjeta `canvas` con ícono de calendario, "dom 08/11 (tarde) → jue 12/11 (mañana)", "4 noches · 3 personas…" y botón **Cambiar**. |
| `<x-apartment-option>` | `apartment`, `fit`, `gap`, `best`, `selected` | Drawer Nueva reserva, modo Mover | Tarjeta-radio: número, capacidad + ajuste, días sueltos y badge "Mejor opción". Seleccionada: borde 2px `accent` y fondo `accent-softer`. |
| `<x-fit-badge>` | `capacity`, `people` | Buscador | "Capacidad justa" (verde), "Sobra 1 lugar" / "Sobran N" (azul), "Falta 1 · colchón" / "Faltan N · colchones" (naranja). |
| `<x-occupancy-map>` | `days`, `label` | Buscador | Fila de cuadrados de 26px por día, partidos en M y T por una línea blanca de 1px. Borde 2px naranja en los días pedidos. `role="img"` + `aria-label`, y `title` por día ("mar 10/11 · Ana Gómez (movible)"). |
| `<x-guest-card>` | `guest`, `action` | Drawer Nueva reserva, Editar, Detalle | Avatar negro con iniciales + nombre + "Tel. … · DNI … · N reservas" + acción ("Cambiar" o "Ver ficha"). Si no hay DNI dice "DNI sin cargar". |
| `<x-stat-tile>` | `label`, `value`, `icon?` | Detalle | Tile `canvas` con etiqueta 12px y valor 16px/700. |
| `<x-extra-toggle>` | `extra`, `pressed` | Drawer Nueva reserva, Editar | Botón 44px con ícono + nombre y `aria-pressed`. Activo: borde 2px `accent` y fondo `accent-softer`. |
| `<x-stepper>` | `model`, `min`, `max`, `label` | Colchones extra | − / valor / +, 40×42, con `aria-label` en los botones y `aria-live` en el valor. Rango 0–6. |
| `<x-color-swatches>` | `palette`, `selected`, `takenBy` | Empleados | Muestras de 44×44; la elegida con tilde y doble anillo (blanco + `accent`); las usadas por otro llevan **su inicial**. Más el `<input type="color">` "Otro color". |
| `<x-reservation-block>` | `block` | Calendario | El bloque de la reserva (ver 5.1). |
| `<x-icon.*>` | — | Varios | Iconos que no trae Heroicons (ver 4.3). |

### 4.3 Iconografía

| Concepto | Ícono | Fuente |
|---|---|---|
| No movible | Candado | Heroicons `lock-closed` |
| Entrada temprana | Sol saliendo sobre el horizonte | Propio `<x-icon.sunrise>` |
| Salida tardía | Luna | Heroicons `moon` |
| Cochera | "P" en un cuadrado redondeado | Propio `<x-icon.parking>` |
| Desayuno | Taza con vapor | Propio `<x-icon.breakfast>` |
| Sigue en el mes anterior/siguiente | Chevron izq./der. | Heroicons `chevron-left/right` |
| Buscar, agregar, cerrar, calendario | Lupa, +, ×, calendario | Heroicons |

Los extras están **apagados por ahora** (`extras.enabled`): no aparecen en bloques, leyenda, formulario ni detalle. Los iconos de extras salen del catálogo: si se agrega un extra nuevo sin ícono, se muestra solo su nombre. Se mapea en `config/extras.php` (`'Cochera' => 'parking'`).

---

## 5. Pantallas en detalle

### 5.1 Calendario (`/calendario`) — pantalla de inicio

Es la grilla del Excel: **filas = departamentos, columnas = días partidos en M/T, bloques de color = reservas**.

**Barra de herramientas**
- ← / → (mes anterior / siguiente), título del período ("Noviembre 2026"), botón **Hoy**.
- Segmentado **Mes / 2 semanas**: cambia la ventana. La ventana es solo un rango de fechas: no hay lógica de meses (ver CLAUDE.md).
- Resumen: "17 deptos activos · 35 reservas".
- A la derecha: **Imprimir** (secundario) y **Buscar disponibilidad / reservar** (primario, un solo botón: buscar y reservar son el mismo flujo, porque toda reserva nueva empieza en el buscador).
- **Imprimir** abre el diálogo de impresión del navegador, que también guarda como PDF. Imprime **solo la grilla del mes completo** de la ventana visible (en la vista de 2 semanas pasa primero a Mes), en una hoja A4 horizontal: sin el header de la app, sin título, sin botones ni leyenda. Son reglas `@media print` en `app.css` (clases `print:hidden` y la sección "Print / PDF of the calendar"); no hay librería de PDF.

**Leyenda** (12px): "Responsable" + muestra de color y nombre de cada empleado activo · separador · iconos de No movible, Entrada temprana, Salida tardía, Cochera, Desayuno.

**Grilla**

```
┌─────────────┬────┬────┬────┬────┬─────
│ Depto·pers. │ D  │ L  │ M  │ M  │ ...   ← día de semana 10px
│             │ 1  │ 2  │ 3  │[24]│       ← número; hoy en píldora azul
│             │    │    │ •  │    │       ← punto naranja = feriado
│             │M│T │M│T │M│T │M│T │       ← medias jornadas 9px
├─────────────┼────┴────┴────┴────┴─────
│ 102  3 pers.│   ▐Lucía López        ▌▐Pablo…   ← bloques
│ 306  2 pers.│ ░░░░ (rayado: inactivo) ░░░░
├─────────────┼────┬────┬────┬────
│ Libres(noche)│ 12 │ 11 │ 3  │ ...   ← en rojo y negrita si quedan ≤ 3
└─────────────┴────┴────┴────┴────
```

- **Columna fija** de 132px (`position: sticky; left: 0`) con número de depto (15px/700), capacidad y badge "Inactivo" si corresponde. La grilla scrollea en horizontal dentro de su tarjeta; la página no.
- **Columnas:** el calendario usa **todo el ancho de la pantalla** (sin el máximo de 1440px de las otras páginas). Cada día se estira para llenarlo y nunca mide menos de `--day-w` (52px en Mes, 96px en 2 semanas); si no entra, la grilla scrollea dentro de su tarjeta. Cada día se parte en dos medias jornadas, con una línea central tenue. Los fines de semana tienen fondo `weekend`; hoy, `accent-softer`, y el número en píldora `accent`.
- **Filas** de 62px; el bloque mide 52px de alto, con 5px arriba y abajo.
- **Departamentos inactivos:** fondo rayado (`repeating-linear-gradient` 135°) y número en gris. Se muestran solo si tienen reservas en la ventana (regla de `CalendarQuery`).
- **Fila de pie "Libres (noche)":** cuántos deptos activos quedan libres esa noche. En rojo y negrita cuando son 3 o menos: es la alarma de "nos estamos llenando".

**Bloque de reserva** (`<x-reservation-block>`), las mismas 3 líneas que la planilla:

| Línea | Contenido | Ejemplo |
|---|---|---|
| 1 (12px/700) | [‹] [ícono entrada temprana] **Huésped** [ícono salida tardía] [›] | `Martín Medina` |
| 2 (11px/500) | Pax (+ faltantes) · estado de pago y monto | `3 pers. (+1) · Señado $100.000` |
| 3 (11px) | [candado] Empleado [· No movible] [cochera] [desayuno] | `Tomás · No movible` |

- **Fondo = color del empleado responsable**, borde `rgba(20,22,26,0.22)`, radio 3px y texto con elipsis.
- **Posición por slots:** empieza en la T del día de entrada (o en la M si es entrada temprana) y termina en la M del día de salida (o en la T si es salida tardía). Así se ve que un checkout a la mañana y un check-in a la tarde conviven en la misma celda.
- **Recortado por la ventana:** si la reserva empieza antes o termina después del rango visible, ese lado queda sin redondear y muestra un chevron (‹ o ›).
- "(+1)": personas por encima de la capacidad del depto, para ver de un vistazo quién necesita colchón.
- Es un `<a>` con `aria-label` completo ("Depto 105, Martín Medina, 4 pers. · Señado $200.000, responsable Rodrigo"). Al tocarlo se abre el **panel de detalle** sin salir del calendario.

**Implementación recomendada: CSS grid por slots, sin JavaScript.** El mockup posiciona los bloques con `left/width` absolutos; en Blade es más simple y robusto que cada fila sea una grilla de `2 × días` columnas y que el bloque ocupe `grid-column: {start} / {end}`:

```blade
<div class="grid" style="grid-template-columns: 132px repeat({{ $grid->slotCount }}, calc(var(--day-w) / 2))">
    <x-calendar.apartment-cell :apartment="$row->apartment" />
    @foreach ($row->blocks as $block)
        <x-reservation-block :block="$block"
            style="grid-column: {{ $block->startColumn }} / {{ $block->endColumn }}" />
    @endforeach
</div>
```

- `startColumn = start_slot − windowStartSlot + 2` (la columna 1 es la del depto), recortado a la ventana. Son los mismos enteros que el backend ya guarda: la vista no calcula fechas.
- El DTO `CalendarGrid` de `CalendarQuery` trae todo listo para pintar: textos de las 3 líneas, color, flags e iconos, `clipLeft/clipRight`, feriados y libres por noche. El componente Livewire solo pasa la ventana.
- Estado en la URL: `?desde=2026-11-01&rango=mes&reserva=123`.
- **Arrastrar reservas** (mover con el mouse) no está en el mockup ni en esta iteración. Si se pide, es el caso en el que CLAUDE.md permite sumar una librería JS solo en esta pantalla.

### 5.2 Panel de reserva: Detalle (`Reservations\Panel`)

Es un **panel lateral (flyout) de ~560px** que se abre sobre el calendario. También se abre desde el buscador ("Ver reserva"), desde la ficha del cliente y desde la de un depto. La URL directa `/reservas/{id}` lleva al calendario centrado en esa reserva con el panel abierto.

**Contenido**
1. **Encabezado:** muestra del color + "Responsable: Tomás · 02/11/2026"; nombre del huésped en 22px (tachado si está cancelada); "Depto 205 · dom 8/11 (tarde) al jue 12/11 (mañana) · 4 noches"; botón × para cerrar.
2. **Tiles 2×2:** Personas ("3 · capacidad 4"), Pago ("Pagado · $200.000", o "Señado · $80.000 de $200.000" + "faltan $120.000"), Condición ("No movible", con candado, o "Movible"), Extras ("Ninguno" o la lista; solo con `extras.enabled`, hoy apagado).
3. **Cliente:** `<x-guest-card>` con "Ver ficha" (abre el drawer en Clientes).
4. **Nota de la reserva:** el texto libre tal cual.
5. **Acciones:** **Editar** (primario) · **Mover de departamento** (secundario) · **Cancelar reserva** (texto rojo, borde rojo suave, alineado a la derecha).

**Modos del panel** (una propiedad `$mode` del componente: `view | edit | move | cancel`)

| Modo | Qué muestra | Salida |
|---|---|---|
| `move` | Si la reserva **no es movible**: callout naranja "Esta reserva no es movible" + checkbox **"Moverla igual"**; el botón queda deshabilitado (`accent-disabled`) hasta marcarlo. Debajo, "Libres del 8/11 al 12/11 para 3 personas": `<x-apartment-option>` con los deptos libres para esas mismas fechas, ordenados como en el buscador. Botón dinámico **"Mover al 305"** y **Volver**. | Callout verde "Reserva movida al depto 305. El 205 quedó libre del 8/11 al 12/11." + "Volver al calendario". |
| `cancel` | `role="alertdialog"`: "¿Cancelar esta reserva?" + "Queda guardada para control, pero deja de ocupar el depto 205 del 8/11 al 12/11." Botones **Sí, cancelar** (rojo sólido) y **No, volver**. | Banner rojo "Reserva cancelada — Cancelada por Tomás el 24/11/2026…", nombre tachado y "Volver al calendario". |
| `edit` | Ver 5.3. | Vuelve a `view` con los datos actualizados. |

- **Mover** usa `AvailabilityQuery` + `ApartmentSuggestions` con las fechas de la reserva, excluyéndola a ella misma, y ejecuta `MoveReservation`. El checkbox "Moverla igual" es la confirmación de la advertencia `not_movable`.
- **Cancelar** ejecuta `CancelReservation`. El calendario se refresca (evento Livewire `reservation-changed`) y el bloque desaparece.
- El botón "Deshacer (demo)" del mockup es solo para la demo: **no se implementa**.

**Pagos en el detalle:** sección **Pagos** con "Cobrado: $X · faltan $Y" y un renglón por pago (monto, tipo, fecha, badge Facturado / No facturado, quién y cuándo, y el botón para cambiarlo, también en canceladas). Si no está cancelada, **Agregar pago** abre ahí mismo el formulario "Nuevo pago" (`AddsPayment` → `AddPayment`): monto, tipo y Facturado. Si la reserva está Reservada, pide en qué queda (Señado / Pagado) y, si es Señado, el total; si está Señada sin total, también pide el total. Si el pago llega al total de una señada, ofrece **"Marcar la reserva como Pagada"** ya tildado; el empleado decide. Las advertencias (`missing_amount`, `missing_total`, `deposit_covers_total`) se confirman con "Guardar igual".

### 5.3 Panel de reserva: Editar

Mismo panel, modo `edit`, ~580px. Edita **lo que no cambia la ocupación**. Para cambiar fechas, personas o departamento se vuelve al buscador, que es el que sabe de disponibilidad.

1. **Encabezado:** "Editar reserva" + "Raúl Silva · Depto 205" + ×.
2. **`<x-stay-summary>`** con **Cambiar** → `/buscador?reserva=123` (ver 5.4, modo edición), y la ayuda "Para cambiar fechas, personas o departamento se vuelve al buscador, que revisa la disponibilidad."
3. **Cliente:** `<x-guest-card>` con "Cambiar", que abre el `GuestPicker` (ver 5.5) dentro del panel.
4. **Pago:** el mismo bloque que en Nueva reserva (5.4), con los pagos ya cargados y su Facturado.
5. **Condiciones:** checkbox Movible con su explicación; toggles de extras; stepper de colchones.
6. **Responsable y nota:** selector de empleado (muestra de color + nombre) con los empleados activos; si el responsable actual está inactivo, aparece igual como opción seleccionada, marcado "inactivo". Debajo, la nota de la reserva.
7. **Pie fijo** (`surface-alt`): **Cancelar** · **Guardar cambios**.

El responsable **se puede cambiar al editar**, por ejemplo cuando otro empleado cargó una reserva que hizo un compañero. El historial de quién modificó qué es de la próxima iteración.

### 5.4 Buscador (`/buscador`) + drawer Nueva reserva

**La única puerta de entrada para una reserva nueva.**

**Formulario de búsqueda** (tarjeta blanca, una fila que hace wrap)
- Entrada (date), Salida (date), Personas (number, 90px de ancho), checkboxes **Entrada temprana** y **Salida tardía**.
- A la derecha, el resumen en vivo: "3 noches · del lun 09/11 al jue 12/11 · 3 pers.".
- **No hay botón "Buscar":** los resultados se recalculan al cambiar cualquier campo (`wire:model.live`, con `debounce` en personas). Es lo que hace que cargar sea "tan rápido como escribir en una celda".
- Validación: si la salida no es posterior a la entrada, callout rojo "La salida tiene que ser posterior a la entrada." y se ocultan los resultados.
- Todo va en la URL: `/buscador?entrada=2026-11-09&salida=2026-11-12&personas=3&temprana=0&tardia=0`.

**Grupo 1 — Disponibles (N)**
- Leyenda del mapa: Reserva fija · Reserva movible · Libre · Lo pedido · "Cada cuadrado es un día: mitad izquierda mañana, derecha tarde."
- Encabezado de columnas: #, Depto, Capacidad, Días sueltos y las fechas de la ventana del mapa (9px; las pedidas en negrita naranja).
- **Fila:** ranking · número (19px) + capacidad · `<x-fit-badge>` (+ "Mejor opción" en azul en la primera) · días sueltos ("Encaja exacto en el hueco", "Justo antes · 4 días después", "2 días antes · más de 2 semanas después") · `<x-occupancy-map>` · botón **Reservar**.
- **La primera fila** es la mejor opción: fondo `accent-best` y botón primario; las demás tienen botón secundario.
- El orden viene de `ApartmentSuggestions` (capacidad → fragmentación → `sort_order`). La vista no ordena.
- **Ventana del mapa:** desde 6 días antes de la entrada, con un largo de `max(21, noches + 12)` días.
- Sin resultados: "No hay departamentos libres para esas fechas. Revisá los ocupados por reservas movibles, más abajo."
- La tabla tiene `min-width: 1180px` y scrollea en horizontal dentro de la tarjeta.

**Grupo 2 — Ocupados por reservas movibles (N)**
- "Se podrían liberar moviendo esas reservas a otro departamento."
- Fila: número + capacidad · "Ocupado por Ana Gómez (12/11 al 16/11)" · **Ver reserva** (abre el panel en modo `move`).

**Grupo 3 — No disponibles:** chips grises en una línea: "105 · ocupado", "306 · inactivo".

**Drawer "Nueva reserva"** (`Reservations\CreateForm`, panel lateral, 560px)

Se abre al tocar "Reservar". Recibe las fechas, personas, flags y el depto elegido, **ya fijados**.

| Sección | Contenido | Reglas |
|---|---|---|
| Resumen | `<x-stay-summary>`: "lun 09/11 (tarde) → jue 12/11 (mañana)", "3 noches · 3 personas" (+ "entrada temprana"/"salida tardía") | **Cambiar** cierra el drawer y deja el buscador como estaba. |
| Departamento | Radio de **3 opciones**: el elegido + los mejores siguientes, en orden de ranking, con el elegido marcado y "Mejor opción" en el primero | Se puede cambiar sin volver al buscador. |
| Cliente | `GuestPicker` (ver 5.5) | Obligatorio. |
| Pago | **Un solo bloque con borde, con todo lo del pago.** Segmentado **Reservado / Señado / Pagado** (por defecto Señado). Con Señado: **Total de la reserva**. Con Señado o Pagado: uno o más renglones de pago, cada uno con **Monto**, **Tipo de pago** (Transferencia / Efectivo), checkbox **Facturado** y "Quitar" (si hay más de uno); botón **Agregar otro pago**; resumen en vivo "Cobrado $80.000 de $200.000 · faltan $120.000" (o "Cobrado: $X" sin total). Con Reservado: "Sin pago todavía: los pagos se cargan cuando se seña o se paga." | Advertencias `missing_amount`, `missing_total` y `deposit_covers_total`. Un renglón sin monto no se guarda. |
| Condiciones | Checkbox **Movible** (marcado por defecto) + explicación; toggles **Cochera** y **Desayuno** (solo con `extras.enabled`, hoy apagado); stepper **Colchones extra** (0–6) | Si `personas > capacidad + colchones`: callout naranja **en vivo**, "El 104 es para 2 y son 3: falta 1 lugar. Sumá un colchón extra." |
| Responsable y nota | Selector de empleado (muestra de color + nombre), con los empleados activos; debajo, textarea **Nota de la reserva** con placeholder "Preferencias, horario de llegada, reserva familiar…" | Responsable: por defecto el usuario autenticado; se cambia cuando se carga una reserva que hizo otro empleado. Nota: opcional. |
| Pie | **Cancelar** · **Guardar reserva** | |

**Al guardar:**
1. Se calculan las advertencias (`ReservationWarnings`).
2. Si hay alguna, el pie muestra un callout naranja con la lista, y el botón pasa a **"Guardar igual"**. El sistema avisa, no bloquea.
3. Se ejecuta `CreateReservation`. Si falla por superposición (otro empleado la cargó al mismo tiempo), se muestra el error en el drawer y se recalculan los resultados del buscador.
4. Si sale bien, redirige al calendario en la ventana de la reserva, con un aviso de éxito y el bloque nuevo resaltado unos segundos.

**Modo edición del buscador** (`/buscador?editar=123`; el parámetro no es `reserva` porque `?reserva=` abre el panel en cualquier página): lo abre el **Cambiar** del panel Editar. Arranca con los datos de la reserva (lo que ya venga en la URL tiene prioridad, así una recarga no pierde lo que se estaba probando), muestra una franja "Cambiando la reserva de Raúl Silva (depto 205, del 08/12 al 12/12)" con "Volver a la reserva", y calcula la disponibilidad **excluyendo esa reserva**: su propio depto aparece como disponible, marcado "Actual". El botón de cada fila dice **"Usar este"** y abre una confirmación corta arriba de los resultados ("¿Pasar la reserva de … al depto 305, del … al …, para N pers.?"), con las advertencias que correspondan (capacidad, no movible) y **Confirmar cambio** / "Guardar igual". Ejecuta `UpdateReservation` con las nuevas fechas, personas y depto, y el resto de la reserva queda igual. Vuelve al calendario con el panel abierto y el bloque resaltado.

**Desde un cliente** (`/buscador?cliente=45`): lo abre "Nueva reserva para este cliente". El drawer arranca con ese cliente ya elegido.

### 5.5 Selector de cliente (`Guests\GuestPicker`)

Lo usan el drawer Nueva reserva y el panel Editar. **Implementado como trait** (`App\Livewire\Concerns\PicksGuest`) **+ vista parcial** (`livewire/partials/guest-picker`), no como componente anidado: el cliente nuevo se crea junto con la reserva, así que sus datos tienen que viajar en el mismo formulario. Implementa el flujo de búsqueda de clientes de arquitectura-backend.md:

1. Input con lupa **"Buscar por teléfono o DNI/CUIT"** (`wire:model.live.debounce.300ms`). `GuestLookup` normaliza y busca.
2. **Lo encuentra:** `<x-guest-card>` con "Tel. 11 5555-0142 · DNI 30.123.456 · 2 reservas anteriores" y **Cambiar**, que vuelve a la búsqueda.
3. **No lo encuentra:** "No hay ningún cliente con ese teléfono o documento" + **"+ Cargar cliente nuevo"**, que despliega ahí mismo Nombre, Apellido, Teléfono\* (precargado con lo buscado si era un teléfono) y DNI/CUIT (opcional).
4. **Conflicto al cargar uno nuevo:** si el teléfono o el DNI ya existen, callout "Este teléfono ya pertenece a *Martín Fernández*: ¿usar ese cliente?" con el botón **Usar ese cliente**.
5. Comunica la elección al padre con un evento (`guest-selected`) o con `#[Modelable]`.

> En el mockup, "Cambiar" y "+ Cargar cliente nuevo" llevan a la pantalla Clientes. En la app real **se resuelve dentro del drawer**: salir de la carga para crear un cliente rompería el flujo.

### 5.6 Clientes (`/clientes`)

- Cabecera: "Clientes" + "El teléfono es obligatorio y único. El DNI/CUIT es opcional, pero no se puede repetir." + **Nuevo cliente**.
- **Buscador** arriba de la tabla: "Buscar por nombre, teléfono o DNI/CUIT". Ignora puntos, guiones y espacios.
- **Tabla:** Nombre · Teléfono · DNI/CUIT ("Sin cargar" en gris) · Reservas (sin contar las canceladas) · Última estadía (la última que ya empezó; las reservas futuras no cuentan). Cada fila es un botón que abre la ficha. Para pocas filas no hace falta paginar; si crecen, se suma paginación de Livewire.
- Vacío: "No hay clientes que coincidan. Podés cargar uno nuevo."
- **Drawer de ficha** (560px):
  - Título con el nombre + "Cliente desde mar 2025".
  - Campos en 2 columnas: Nombre, Apellido, Teléfono \*, DNI/CUIT (opcional).
  - Si es nuevo: "Si el teléfono o el DNI ya existen, te vamos a ofrecer usar ese cliente en lugar de crear uno repetido."
  - Botones **Guardar cambios** / **Crear cliente** y **Nueva reserva para este cliente** (→ `/buscador?cliente={id}`).
  - **Historial de reservas:** una fila por reserva con el color del empleado, "Depto 202 · 3 pers.", "10/11 al 14/11/2026 · Señado $100.000" y badge **Próxima / En curso / Pasada / Cancelada**. Las canceladas van tachadas y con fondo `surface-alt`. Al tocar una, se abre su panel de detalle. **Es el "historial del cliente" que pide CLAUDE.md.**
  - **Eliminar** (patrón común en los tres drawers): botón **Eliminar …** abajo a la izquierda, con borde rojo. Abre en el mismo drawer `<x-delete-confirmation>` (fondo `danger-bg`, como "Cancelar reserva"), con lo que se pierde y **Sí, eliminar** / **No, volver**. Si está bloqueado, muestra el motivo y solo **No, volver**. Bloqueado si tiene reservas: "No se puede eliminar: tiene 3 reservas (contando las canceladas)."

### 5.6b Pendientes a facturar (`/pendientes-a-facturar`)

- Cabecera: "Pendientes a facturar" + qué pagos entran. En el menú: **A facturar**.
- **Filtro:** checkbox "Incluir pagos en efectivo" (en la URL, `?efectivo=true`). Por defecto solo transferencias. Al lado, el resumen: "N pagos · $X cobrados sin facturar".
- **Tabla** (`Billing\Pending`, consulta `PendingInvoicesQuery`): **un pago por fila**. Cliente · DNI/CUIT · Reserva (depto y fechas) · Estado (badge Señado / Pagado de la reserva, + Cancelada) · Tipo de pago ("Falta tipo de pago" en naranja si no hay) · Monto del pago. Toda la fila es clickeable.
- **Drawer de facturación** (`Billing\InvoiceDrawer`, montado en el layout, evento `open-invoice` con el id del pago): monto del pago en grande con sus badges (y "La reserva tiene otro pago: $X cobrados en total" si hay más), CUIT/DNI, nombre del cliente y el botón **Facturado**, que marca el pago y lo saca de la lista. Si el cliente no tiene DNI/CUIT, un aviso con "Ver ficha". "Ver reserva" abre su panel encima.
- **Facturado en los formularios:** checkbox en cada renglón de pago en Nueva reserva y en Editar. En el detalle de la reserva, la sección **Pagos** lista cada pago con monto, tipo, badge Facturado / No facturado, quién y cuándo, y el botón para cambiarlo (también en canceladas).

### 5.7 Departamentos (`/departamentos`)

- Cabecera: "Departamentos" + "17 activos de 18. Un departamento con reservas futuras no se puede desactivar." + **Nuevo departamento**.
- **Tabla:** Depto · Capacidad · Descripción · Próxima reserva ("En curso · Elena Romero · 22/11–30/11" o "—") · Ocupación (barra de 60×6px en `accent` + %) · Estado (badge).
- **Drawer** (560px): "Depto 105" + badge de estado; Número, Capacidad (personas), Descripción; interruptor **Activo** ("Solo los departamentos activos se pueden reservar.").
  - **Bloqueo:** si tiene reservas futuras, el interruptor queda deshabilitado y aparece el callout naranja "No se puede desactivar: tiene 2 reservas futuras. Primero movelas o cancelalas." Es uno de los dos bloqueos duros del sistema; la action `DeactivateApartment` lo vuelve a validar.
  - **Reservas futuras:** lista con color del empleado, huésped y fechas. Cada una abre su panel.
  - Botones **Guardar cambios** / **Crear departamento** y **Ver en calendario**.
  - **Eliminar** (patrón común en los tres drawers): botón **Eliminar …** abajo a la izquierda, con borde rojo. Abre en el mismo drawer `<x-delete-confirmation>` (fondo `danger-bg`, como "Cancelar reserva"), con lo que se pierde y **Sí, eliminar** / **No, volver**. Si está bloqueado, muestra el motivo y solo **No, volver**. Bloqueado con reservas futuras o en curso; si no, avisa "También se borran sus 4 reservas pasadas o canceladas, con sus pagos."

### 5.8 Empleados (`/empleados`)

- Cabecera: "Empleados" + "Quienes cargan reservas. Cada uno tiene un color, y con ese color se pintan sus reservas en el calendario." + **Nuevo empleado**.
- **Tabla:** Empleado (avatar con su color e inicial + nombre) · Email (usuario) · Reservas · Estado.
- **Drawer** (560px):
  - Nombre, Email y, solo al crear, **Contraseña inicial** ("La puede cambiar después desde su perfil." El perfil es el del starter kit).
  - **Color en el calendario:** `<x-color-swatches>` con las 10 muestras. Las que ya usa otro llevan su inicial ("La letra sobre cada color indica quién ya lo usa."), más **Otro color** (`input type="color"`).
  - **Color repetido:** callout naranja "Víctor ya usa este color. Se puede, pero en el calendario no se va a distinguir de quién es cada reserva." (advertencia `color_in_use`, no bloquea).
  - **Vista previa:** "Así se ven sus reservas", un bloque de ejemplo pintado con el color elegido y su nombre.
  - Al editar: interruptor **Activo**, "Si lo desactivás no puede entrar al sistema. Sus reservas se mantienen con su color."
  - **Guardar cambios** / **Crear empleado** (+ **Cancelar** al crear).
  - **Eliminar** (patrón común en los tres drawers): botón **Eliminar …** abajo a la izquierda, con borde rojo. Abre en el mismo drawer `<x-delete-confirmation>` (fondo `danger-bg`, como "Cancelar reserva"), con lo que se pierde y **Sí, eliminar** / **No, volver**. Si está bloqueado, muestra el motivo y solo **No, volver**. Si tiene reservas, la confirmación pide **a quién pasan** (selector con los empleados activos, por defecto quien elimina). No aparece al editarse a uno mismo.

---

## 6. Patrones transversales

### Formularios y validación
- Cada formulario largo usa un **Livewire Form object** (`App\Livewire\Forms\ReservationForm`, `GuestForm`, `ApartmentForm`, `EmployeeForm`). Ahí viven las reglas de validación de UI; las actions vuelven a validar los errores.
- **Errores** (bloquean): debajo del campo con `flux:error`, en rojo. Los de dominio que no son de un campo (superposición, depto inactivo) van en un callout rojo arriba del pie del drawer.
- **Advertencias** (no bloquean): callout naranja. Las que dependen de un solo campo se muestran **en vivo** (capacidad, color repetido); el resto aparece al guardar, y el botón pasa a "Guardar igual".
- Obligatorio: asterisco rojo (`danger`). Opcional: "(opcional)" en `subtle`. Se marcan solo los que no son obvios.

### Estados de carga
- `wire:loading` sobre los resultados del buscador (opacidad reducida, sin spinner a pantalla completa) y en los botones de guardar (deshabilitado + "Guardando…").
- Navegación con `wire:navigate` y la barra de progreso que trae Livewire.

### Drawers separados de las páginas
- **Los drawers no viven en las páginas: están montados una sola vez en el layout** (`layouts/app.blade.php`): `Reservations\Panel`, `Guests\Drawer`, `Apartments\Drawer` y `Employees\Drawer`. Las páginas solo muestran listados.
- **Se abren desde cualquier pantalla con un evento:** `open-reservation`, `open-guest`, `open-apartment`, `open-employee` (con `id`, o sin él para un alta). Ejemplo: `wire:click="$dispatch('open-guest', { id: 45 })"`.
- **Cerrar te deja donde estabas.** Se apilan: desde el panel de una reserva, "Ver ficha" abre el cliente encima; al cerrarlo volvés al panel, y al cerrar el panel, a la pantalla de fondo. Desde el historial del cliente, una reserva abre su panel encima.
- **Links directos en cualquier página:** `?reserva=123` y `?cliente=45` abren el drawer sobre la pantalla actual. `/reservas/{id}` sigue llevando al calendario en el mes de la reserva.
- La excepción es el drawer de **Nueva reserva**, que vive en el buscador porque toda alta empieza ahí.
- Mientras están cerrados no consultan nada: solo cargan datos al abrirse.

### Refresco entre componentes
- Cada escritura dispara un evento: `reservation-changed`, `guest-changed`, `apartment-changed`, `employee-changed`. Las pantallas y los drawers abiertos que muestran esos datos lo escuchan (`#[On(...)]`) y recalculan. Así, guardar en un drawer actualiza la pantalla de fondo.
- No hay tiempo real entre empleados en esta iteración: lo que cargó otro se ve al recargar o al navegar. La superposición igual está protegida por la action y la *exclusion constraint*.

### Formatos (helpers en `App\Support\Format`)

| Dato | Formato | Ejemplo |
|---|---|---|
| Fecha en bloque o lista | `dd/mm` o `dd/mm/aaaa` | `10/11`, `10/11/2026` |
| Fecha con día | día abreviado + fecha | `dom 08/11`, `mié 11/11` |
| Fecha con media jornada | + `(mañana)` / `(tarde)` | `dom 08/11 (tarde) → jue 12/11 (mañana)` |
| Noches | singular/plural | `1 noche`, `4 noches` |
| Monto | pesos, separador de miles con punto, sin decimales si son cero | `$200.000` (con `Number::currency($n, 'ARS', 'es_AR')` o un helper propio) |
| Personas | | `3 pers.` (compacto), `3 personas` (formularios) |
| Teléfono / DNI | se muestran tal como se cargaron; se **guardan** normalizados | `11 5555-0142`, `30.123.456` |

El input de monto acepta "$ 80.000", "80000" o "80.000" y lo normaliza al guardar.

### Accesibilidad (se mantiene del mockup)
- Botones y links reales; nada de `onclick` en un `div`.
- **Filas de tabla clickeables:** en los ABM, toda la fila abre la ficha. Se hace estirando el botón de la primera celda sobre la fila (`after:absolute after:inset-0` con la fila en `relative`): un solo destino de clic, que sigue siendo un botón real para teclado y lectores de pantalla.
- **Cursor:** todo lo clickeable muestra la mano (`cursor: pointer`) y lo deshabilitado, `not-allowed`. Está definido una sola vez en `app.css` (capa `base`), así cubre los botones propios y los de Flux sin repetir clases.
- `aria-label` en los botones de ícono (cerrar, mes anterior/siguiente, −/+ colchones) y en cada bloque del calendario.
- `aria-pressed` en los toggles de extras y en las muestras de color; `aria-current="page"` en la nav; `role="alert"` / `role="status"` / `role="alertdialog"` en los avisos.
- Mapa de ocupación con `role="img"` + `aria-label`, y `title` por día.
- Objetivos táctiles de 44px en las acciones de drawers y paneles.
- Contraste: los grises de texto (`muted`, `subtle`, `faint`) superan 4.5:1 sobre blanco y sobre `canvas`.

### Responsive
- La herramienta se usa sobre todo en escritorio, pero todo funciona en el celular **sin scroll horizontal de la página** (verificado a 320 y 375 px). Cortes: `sm` (640px) para teléfono y `lg` (1024px) para la nav.
  - **Header:** debajo de `lg` la nav se pliega en un botón de menú (Alpine, `x-data`) que abre los mismos links en una lista a lo ancho; el header queda en una línea.
  - **Contenedor:** padding 16px en el teléfono, 24px desde `sm`. El h1 de página baja a 22px.
  - **Calendario:** la toolbar se reacomoda (período + Hoy / rango / acciones), "Imprimir" queda solo con el ícono y la leyenda se pliega en un `<details>` "Referencias". La grilla va de borde a borde, la columna de depto baja a 64px (`--apartment-w`) y, debajo de `lg`, al cargar y al tocar "Hoy" la grilla scrollea hasta ayer (evento `calendar-today`).
  - **Buscador:** debajo de `lg` cada depto disponible es una tarjeta (número, ajuste, días sueltos, botón) con el mapa de ocupación en su propia franja con scroll lateral y los números de día arriba.
  - **Tablas de los ABM** (`.stack-table` en `app.css`): debajo de `sm` cada fila es una tarjeta; la primera celda es el título y las demás muestran su columna con `data-label`.
  - **Drawers:** ocupan toda la pantalla debajo de `md` (sin la franja que deja el `<dialog>`), con padding 20px y botones de pie a lo ancho.

---

### Instalable en el celular (PWA, sobre todo iOS)

- Se puede agregar a la pantalla de inicio y se abre como app, sin barra del navegador. En iOS (16.4 o posterior), desde Safari o Chrome: Compartir → **Agregar a inicio** (en iOS todos los navegadores usan WebKit, así que la app instalada es la misma; antes de 16.4, solo Safari). En Android, Chrome ofrece **Instalar app**.
- `public/manifest.webmanifest`: `display: standalone`, abre en `/calendario` (`start_url`), `scope: /`, fondo `canvas` (#f4f4f1) y `theme_color` blanco, como el header.
- Íconos: la marca del header, una "A" en Space Grotesk 700 blanca sobre `ink` (#15171a). Están `public/apple-touch-icon.png` (180, la que usa iOS), `public/icons/icon-192.png` / `icon-512.png` (`any`), `icon-maskable-512.png` (con la letra dentro de la zona segura) y `favicon.ico` (16/32/48, con esquinas redondeadas). Si cambia la marca, se regeneran los PNG.
- En `partials/head`: `<link rel="manifest">`, `apple-touch-icon`, `apple-mobile-web-app-capable`, `apple-mobile-web-app-title` y `apple-mobile-web-app-status-bar-style` (`default`: barra de estado clara, el contenido empieza debajo).
- **Atajo "Instalar app"** en el menú de la cuenta (`resources/js/app.js`, `window.aridanInstall`): donde el navegador lo permite (Chrome en Android o escritorio) abre su diálogo de instalación (`beforeinstallprompt`); si no, en iOS por ejemplo, abre el drawer "Instalar Aridan" con los pasos (Compartir → Agregar a inicio → Agregar; en otros navegadores, menú ⋮ → Instalar app). No aparece cuando ya se abrió como app instalada.
- **Sin modo offline por ahora:** no hay service worker. Sin conexión, la app no carga.

## 7. Estructura de carpetas

```
app/
  Livewire/
    Calendar/Index.php
    Availability/Search.php
    Reservations/CreateForm.php
    Reservations/Panel.php            # view / edit / move / cancel
    Guests/Index.php                  # listado
    Guests/Drawer.php                 # ficha (montada en el layout)
    Concerns/PicksGuest.php           # selector de cliente (trait + partial)
    Apartments/Index.php  Apartments/Drawer.php
    Employees/Index.php   Employees/Drawer.php
    Forms/ReservationForm.php  GuestForm.php  ApartmentForm.php  EmployeeForm.php
  Support/
    Format.php                        # fechas, montos, noches
    ColorContrast.php                 # texto negro o blanco sobre el color del empleado
config/
  employee_colors.php                 # paleta de 10 colores
  extras.php                          # extra → ícono
resources/
  css/app.css                         # @theme con los tokens
  views/
    components/
      layouts/app/header.blade.php    # layout con header (del starter kit, adaptado)
      app/header.blade.php
      ui/                             # segmented, radio-card, table, callout (si no están en Flux gratuito)
      icon/                           # sunrise, parking, breakfast
      calendar/                       # apartment-cell, day-header, free-row
      reservation-block.blade.php
      employee-swatch.blade.php  status-badge.blade.php  section-title.blade.php
      stay-summary.blade.php  apartment-option.blade.php  fit-badge.blade.php
      occupancy-map.blade.php  guest-card.blade.php  stat-tile.blade.php
      extra-toggle.blade.php  stepper.blade.php  color-swatches.blade.php
    livewire/
      calendar/index.blade.php
      availability/search.blade.php
      reservations/create-form.blade.php
      reservations/panel.blade.php
      guests/index.blade.php  guests/guest-picker.blade.php
      apartments/index.blade.php
      employees/index.blade.php
lang/es/
  calendar.php  availability.php  reservations.php  guests.php
  apartments.php  employees.php  warnings.php
tests/
  Feature/Livewire/                   # un test por pantalla: renderiza, abre drawer, guarda, muestra advertencia
```

---

## 8. Diferencias entre el mockup y la documentación (a resolver)

| # | Tema | Mockup | Documentación | Propuesta |
|---|---|---|---|---|
| 1 | Nombre del estado sin pago | "Pendiente" | CLAUDE.md dice "Reservado" | Usar **"Reservado"**, que es como lo llaman hoy. Si se prefiere "Pendiente", actualizar CLAUDE.md. |
| 2 | Detalle y edición | Dos pantallas separadas | El backend tiene solo `/reservas/{reservation}` = edición | **Hecho:** un solo panel con modos (`Reservations\Panel`); `/reservas/{reservation}` (`reservations.show`) abre el calendario con el panel. |
| 3 | Cambiar fechas de una reserva existente | "Cambiar" lleva al buscador | El buscador está pensado solo para altas | **Hecho:** modo edición del buscador (5.4), con `AvailabilityQuery` excluyendo la reserva. |
| 4 | Cliente nuevo desde el drawer | Link a la pantalla Clientes | El backend dice "en el mismo formulario" | Resolver dentro del drawer con `GuestPicker` (5.5). |
| 5 | Advertencias | Capacidad en vivo, sin paso de confirmación | El backend pide "Confirmar igual" | Las dos cosas: aviso en vivo + "Guardar igual" si quedan advertencias al guardar. |
| 6 | Datos que el detalle no muestra | Faltan entrada temprana / salida tardía, colchones y tipo de pago | Son campos de la reserva | Agregarlos a los tiles (Personas: "3 + 1 colchón"; Pago: "Pagado · $200.000 · efectivo"; una línea "Entrada temprana" junto a las fechas). |
| 7 | Columna "Ocupación nov." en Departamentos | Atada al mes | "El mes no existe como entidad" | Calcularla sobre una ventana de fechas (los próximos 30 días o la ventana visible del calendario) y titularla "Ocupación próx. 30 días". |
| 8 | Orden de los deptos | No se edita en ningún lado | `apartments.sort_order` existe | **Hecho:** campo "Orden" en el drawer de departamento. |
| 9 | Modo oscuro | No existe | El starter kit lo trae | Dejar la app fija en claro por ahora. |
| 10 | Listado de canceladas | Solo aparecen en el historial del cliente | El backend menciona un listado "para control" | Por ahora alcanza con el historial; un filtro "Ver canceladas" en el calendario puede ir en una iteración siguiente. |
| 11 | "Deshacer (demo)" al cancelar | Existe | No hay "descancelar" | No se implementa. |
| 12 | Ventanas | Modales centrados (alta de reserva, fichas de depto, empleado y cliente) | — | **Decidido:** no hay modales, todo se abre en un **drawer** lateral, como el detalle de reserva. El mockup no se actualiza: esta tabla registra la diferencia. |

## 9. Orden de construcción sugerido

1. **Base:** tokens en `app.css`, fuente, layout con header y nav, restyling del login.
2. **ABM simples:** Departamentos y Empleados (prueban tabla, drawer, switch, callout y color). Así hay datos para lo demás.
3. **Calendario** solo lectura: grilla por slots, bloques, leyenda, feriados, libres por noche.
4. **Buscador:** formulario en vivo, tres grupos, mapa de ocupación.
5. **Drawer Nueva reserva** + `GuestPicker` + advertencias.
6. **Panel de reserva:** detalle, cancelar, mover, editar.
7. **Clientes:** tabla, ficha e historial.
8. Modo edición del buscador y "Nueva reserva para este cliente".
