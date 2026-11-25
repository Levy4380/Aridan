# Aridan — Gestión de reservas

## Tu rol

Sos un **ingeniero de software senior, experto en Laravel, Livewire, PHP y frontend** (Blade, Flux, Tailwind). Tu trabajo es construir la **web de gestión de reservas de Aridan**, un apart que es un emprendimiento familiar.

Hoy toda la gestión vive en una planilla de Google Sheets con un Apps Script, y depende de que una sola persona lo sepa todo. Tu objetivo es **mejorar la gestión sin cambiar la forma de trabajar**: la web tiene que sentirse tan rápida y familiar como la planilla, pero con una sola fuente de datos, reglas claras y la información en el sistema y no en la cabeza de alguien.

**No hay migración de datos:** la base arranca vacía, salvo los seeders (departamentos, extras y el primer empleado).

## Dónde está cada cosa

Este archivo es el **punto de partida**: el rol, las reglas de negocio y un resumen de la arquitectura. El detalle está en:

| Documento | Qué tiene | Leelo cuando… |
|---|---|---|
| [arquitectura-backend.md](arquitectura-backend.md) | Modelo de datos con columnas y tipos, slots, actions, queries, errores vs. advertencias, concurrencia, *exclusion constraint*, feriados, auth, rutas y carpetas. | Toques migraciones, modelos, actions, queries, comandos o tests de dominio. |
| [arquitectura-frontend.md](arquitectura-frontend.md) | Mapa de pantallas y navegación, layout, sistema de diseño (tokens, paleta, tipografía), mapeo a Flux, componentes Blade, cada pantalla en detalle, patrones transversales, diferencias con el mockup y orden de construcción. | Toques vistas, componentes Livewire, estilos o textos de la UI. |

Si una regla de este archivo y un documento de arquitectura se contradicen, **manda este archivo**. Avisá la contradicción en vez de elegir en silencio.

## Cómo trabajar

- **Leé el documento de arquitectura que corresponda antes de implementar.** No inventes nombres de clases, columnas o rutas que ya están definidos ahí.
- **La lógica de negocio no va en los componentes Livewire.** Va en actions (escritura, `app/Actions`) y queries (lectura, `app/Queries`), con el dominio en `app/Domain`. Livewire solo orquesta la pantalla.
- **Tests con Pest contra Postgres** (base de testing separada, nunca SQLite). Toda regla de negocio tiene su test; `StayRange` y la superposición son lo más testeado del sistema.
- **Pint antes de cada commit.**
- **No agregues dependencias** fuera del stack sin preguntar: ni otra librería de componentes, ni JavaScript propio en el calendario salvo que la interacción lo exija.
- **Seguí el orden de construcción** de `arquitectura-frontend.md` (sección 9), salvo que se pida otra cosa.
- **No implementes las próximas iteraciones** listadas al final.
- Ante una decisión de negocio que no está escrita, **preguntá**. Ante una técnica con una opción obvia, tomala y mencionala.

## Convención de idioma (obligatoria)

- **Todo el código va SIEMPRE en inglés:** tablas, columnas, modelos, clases, métodos, variables, enums, nombres de rutas (`reservations.create`), comandos de artisan, tests y mensajes de commit.
- **Solo va en español lo que ve el usuario:**
  - Las URLs (`/reservas`, `/calendario`, `/departamentos`).
  - Los textos de la interfaz (etiquetas, botones, errores, advertencias), centralizados en `lang/es/*.php` y referenciados por clave.

| Español | Código |
|---|---|
| departamento | `Apartment` / `apartments` |
| empleado | `User` / `users` (la FK se llama `employee_id`) |
| cliente / huésped | `Guest` / `guests` |
| reserva | `Reservation` / `reservations` |
| extra | `Extra` / `extras` |
| feriado | `Holiday` / `holidays` |
| movible | `is_movable` |
| entrada temprana / salida tardía | `early_check_in` / `late_check_out` |
| estado de pago (reservado / señado / pagado) | `PaymentStatus` (`pending` / `deposit` / `paid`) |
| tipo de pago (transferencia / efectivo) | `PaymentMethod` (`transfer` / `cash`) |
| colchones extra | `extra_mattresses` |
| media jornada | `slot` |

## Stack

Se despliega en **Laravel Cloud** (plan pago ya contratado).

| Capa | Elección |
|---|---|
| Aplicación | PHP + **Laravel** (última estable), monolito sin API REST. |
| UI | **Livewire** con el **starter kit oficial**, componentes **Flux** (única librería de UI), **Tailwind 4**, Vite. Layout con header, no sidebar. |
| Auth | La del starter kit, **sin registro público**. El primer empleado se crea con `php artisan employee:create` o un seeder. |
| Datos | **PostgreSQL** (serverless en Laravel Cloud), Eloquent + migraciones. Única excepción de SQL a mano: la *exclusion constraint* con `btree_gist` (**verificar que Laravel Cloud la soporte**). |
| Calidad | **Pest** contra Postgres, **Laravel Pint**. |
| Infra | Laravel Cloud con scheduler (sincronización de feriados). Sin colas ni mail por ahora. Local: PHP + Composer + Node y Postgres en Docker, con versiones iguales a las de Laravel Cloud. |
| Región | Zona horaria `America/Argentina/Buenos_Aires`, idioma `es`, moneda ARS (`decimal(12,2)`). |

## Principios de producto

- **La vista principal sigue siendo la grilla del Excel:** filas = departamentos, columnas = días partidos en mañana/tarde. Cada reserva es un bloque cuyo **color identifica al empleado responsable** de la reserva, con 3 líneas: huésped / pax, pago y monto / movible y responsable.
- **Cargar una reserva tiene que ser tan rápido como escribir en una celda.** Toda reserva nueva arranca en el **buscador** (fechas, personas, entrada temprana, salida tardía). "Reservar" en un depto abre un **modal** con esos datos fijados; "Cambiar" vuelve al buscador. Los campos obligatorios son los mínimos.
- **El texto libre se mantiene:** siempre hay observaciones para lo que no entra en campos. Lo que se repetía en el texto de la planilla (pax, monto, movible, entrada temprana, extras) es un campo propio. **El sistema nunca interpreta textos ni palabras clave.**
- **El sistema avisa, no bloquea**, salvo dos casos: **la superposición de reservas** y **desactivar un departamento con reservas futuras**. Todo lo demás (capacidad, datos incompletos, color repetido) es una advertencia que se confirma con "Guardar igual".
- **El conocimiento queda en el sistema:** qué empleado es responsable de cada reserva, estado de pago y lo cobrado, si es movible e historial del cliente.
- **No reproducir el desorden de la planilla:** nada de hojas por mes ni copias. Una sola fuente de datos; el calendario es solo una vista.

## Reglas de negocio

### Departamentos
- Cada uno tiene **número** (piso + unidad, string único), **capacidad** en personas, estado **activo/inactivo**, descripción opcional y un orden para el calendario.
- Solo se reservan los activos. **No se puede desactivar uno con reservas futuras.** El conjunto cambia con el tiempo.
- **Seed inicial** (18 deptos, todos activos), como `número: capacidad`:
  - Piso 1: 102: 3 · 104: 2 · 105: 4 · 106: 2 · 107: 2
  - Piso 2: 201: 2 · 202: 4 · 203: 5 · 204: 2 · 205: 4 · 206: 2 · 207: 4
  - Piso 3: 301: 3 · 303: 5 · 304: 2 · 305: 4 · 306: 2 · 307: 5

### Empleados
- Son los usuarios del sistema. Login básico; **todos tienen la misma jerarquía**, sin roles ni permisos.
- Cada uno tiene un **color**, con el que se pintan las reservas de las que es responsable. En la reserva se elige el empleado, nunca el color. La paleta está en `arquitectura-frontend.md` (3.2) y vive en `config/employee_colors.php`.
- Desde la pantalla Empleados cualquiera puede dar de alta a otro (nombre, email, contraseña inicial), editar nombre, email y color, y activar/desactivar.
- **Color repetido:** se permite con advertencia.
- **No se borran, se desactivan.** Un inactivo no puede iniciar sesión (ni seguir conectado) y sus reservas mantienen su color.
- **Nadie se puede desactivar a sí mismo:** lo tiene que hacer otro empleado. Así el sistema nunca se queda sin alguien que pueda entrar.

### Clientes (huéspedes)
- **Nombre** y **apellido**; **teléfono** obligatorio y **único**; **DNI/CUIT** opcional pero **único** cuando se carga. Ambos se guardan **normalizados** (solo dígitos, formato canónico) para que la unicidad funcione.
- Al crear una reserva se busca el cliente por DNI/CUIT o teléfono; si no existe, se carga en el mismo modal.
- Un cliente puede tener varias reservas, incluso simultáneas (ej. una familia que alquila dos deptos): son **reservas distintas con el mismo cliente**.

### Reservas
Cada reserva tiene:
- **1 empleado responsable**, **1 departamento** y **1 cliente** titular.
  - El responsable es quien hizo la reserva, que **no siempre es quien la carga**: un empleado puede cargar una reserva que hizo otro.
  - Al crear, el responsable arranca como el usuario autenticado y se puede cambiar por cualquier otro empleado activo. También se puede cambiar al editar.
  - Un empleado inactivo no se puede elegir, pero las reservas que ya tiene a su cargo lo conservan como responsable.
- **Fecha de entrada y de salida** (salida siempre posterior a la entrada). Es un rango continuo: puede cruzar mes o año sin tratamiento especial.
- **Entrada temprana** y **salida tardía** (booleanos). Ver la regla de superposición.
- **Cantidad de personas** (entero). Si se habla de un rango ("3 o 4"), se carga el máximo.
- **Estado de pago:** *Reservado* (sin pago), *Señado* (parcial) o *Pagado* (total).
- **Monto:** un solo campo con lo cobrado, cuando está señado o pagado. No hay regla fija entre seña y total.
- **Tipo de pago:** transferencia o efectivo, junto con el monto.
- **Extras:** checkboxes. Primera iteración: **cochera** y **desayuno**.
- **Colchones extra:** contador desde 0, para cuando hay más personas que capacidad.
- **Movible** (booleano explícito que carga el empleado): si la reserva se puede cambiar de departamento. Deja de ser movible cuando el huésped pidió ese depto puntual o se le ofreció ese depto en particular (ej. se le mostraron fotos). No se guarda el motivo, solo la consecuencia.
- **Observaciones:** texto libre, solo informativo. Las reservas internas (familia, cortesía) son reservas normales identificadas con una nota.

**Cancelación:** soft delete con quién y cuándo la canceló. Queda en la base para control pero **no ocupa el departamento**: no cuenta para superposición, disponibilidad, calendario ni el bloqueo al desactivar un depto.

**Sin "ventanas":** no hay temporadas, turnos ni duraciones predefinidas. Cualquier rango de fechas, mientras el depto esté activo.

### Superposición (media jornada)
- Un departamento **no puede tener dos reservas activas en simultáneo**. La unidad mínima es la **media jornada**: mañana (M) o tarde (T).
- **Caso normal:** ocupa desde la **tarde** del día de entrada hasta la **mañana** del día de salida. Un checkout a la mañana y un check-in a la tarde conviven el mismo día.
- **Entrada temprana:** ocupa también la mañana del día de entrada.
- **Salida tardía:** ocupa también la tarde del día de salida.
- **Cálculo:** cada reserva es un rango semiabierto de slots `[start_slot, end_slot)`, y dos reservas chocan si `start_A < end_B` y `start_B < end_A`. Fórmulas, tabla de casos y el value object `StayRange` en `arquitectura-backend.md` (sección 4).
- Se valida en la action con `lockForUpdate` y, como red de seguridad, con la *exclusion constraint* de Postgres (sección 5 del backend).

### Capacidad
- **No se bloquea** una reserva con más personas que la capacidad: se cubre con colchones extra. Si `personas > capacidad + colchones`, el sistema **avisa**.
- Un depto más grande que la reserva está permitido.

### Buscador y sugerencia de departamentos
Con fechas, personas, entrada temprana y salida tardía, el buscador arma tres grupos:

1. **Disponibles**, ordenados de mejor a peor:
   1. **Ajuste de capacidad:** primero la justa, después los más grandes (cuanto menos sobre, mejor) y al final los más chicos, que necesitan colchones.
   2. **Fragmentación:** cuántos días sueltos deja antes y después. Menos es mejor (para una reserva del 10 al 11, gana un depto libre solo esos días frente a otro libre toda la semana). Se cuenta sin cortar por mes.
   3. **Desempate:** el orden de los deptos.

   El primero se marca como **mejor opción**. Cada fila muestra un **mapa de ocupación** por día, partido en mañana y tarde, que distingue reservas fijas, movibles, lo libre y lo pedido.
2. **Ocupados por reservas movibles:** se podrían liberar moviendo reservas con `is_movable = true`.
3. **No disponibles:** ocupados por reservas fijas, o inactivos.

"Reservar" abre el modal de alta, que sugiere 3 deptos de la lista con el elegido marcado.

### Feriados
- Solo referencia visual en el calendario: no afectan ninguna regla.
- Se traen de una **API pública de feriados de Argentina** (a elegir y verificar al implementar) con `php artisan holidays:sync`, detrás de una interfaz `HolidayProvider`, y se guardan en una tabla local. El calendario nunca consulta la API.

### El mes no existe
- No hay tabla, campo ni lógica atada a meses. El **calendario es una consulta** sobre una ventana de fechas cualquiera (`start_slot < window_end` y `end_slot > window_start`); una reserva que cruza el fin de mes aparece cortada en los dos.
- Disponibilidad, sugerencias y métricas se calculan sobre fechas, nunca sobre meses.

## Arquitectura en resumen

```
Livewire (pantallas)  →  Actions (escriben) / Queries (leen)  →  Eloquent + app/Domain  →  PostgreSQL
```

- **Modelo de datos:** `users` (con `color`, `is_active`), `apartments`, `guests`, `reservations` (FKs `employee_id`, `apartment_id`, `guest_id`; slots persistidos; soft delete con `cancelled_by_id`), `extras` + `extra_reservation` (única N:M) y `holidays`. → backend §3.
- **Slots:** `StayRange` calcula `start_slot`/`end_slot`, que el modelo persiste al guardar. Calendario, disponibilidad y superposición son comparaciones de enteros indexadas. → backend §4.
- **Actions:** `CreateReservation`, `UpdateReservation`, `MoveReservation`, `CancelReservation`, `ActivateApartment` / `DeactivateApartment` y las de empleados. Transaccionales; revalidan los errores, nunca confían en la UI. → backend §5 y §8.
- **Errores vs. advertencias:** los errores bloquean (`ValidationException`); las advertencias (`capacity_exceeded`, `not_movable`, `missing_amount`, `similar_guest_name`, `color_in_use`) las calcula `ReservationWarnings` y se confirman. → backend §5, frontend §6.
- **Queries:** `CalendarQuery`, `AvailabilityQuery`, `ApartmentSuggestions`. → backend §6.
- **Pantallas y rutas:** `/calendario` (inicio), `/buscador` (con el modal de alta), panel lateral de reserva (detalle / editar / mover / cancelar), `/clientes`, `/departamentos`, `/empleados`. El estado importante va en la URL (`#[Url]`). → backend §9, frontend §1 y §5.
- **Sistema de diseño:** sobrio, neutros cálidos, un solo acento azul; los colores fuertes son de los empleados. Densidad de planilla, números tabulares, fuente Space Grotesk, tokens en `resources/css/app.css`. → frontend §3.
- **Componentes:** Flux primero; si algo es de Flux Pro y no hay licencia, componente Blade propio con los tokens. Piezas de dominio como `<x-reservation-block>` y `<x-occupancy-map>`. → frontend §4.

## Errores de la planilla que la web no tiene que repetir

- El alta **pisaba reservas sin avisar**: no había control de superposición.
- **No respetaba la media jornada**: un checkout y un check-in el mismo día chocaban.
- "Ocupado" se detectaba por el **color de la celda**: una celda pintada por error bloqueaba un depto.
- Todo estaba **atado al mes**: no se podía reservar ni buscar cruzando de mes.
- El buscador **no medía la fragmentación** (en la práctica ordenaba solo por capacidad).
- **Datos duplicados** a mano: lista de deptos y paleta en el código, responsable como texto libre, color desconectado del empleado, el total de deptos escrito a mano en las métricas.
- **Validaciones mínimas** y ni edición, ni cancelación, ni historial.

## Decisiones pendientes

- Verificar que Laravel Cloud soporte `btree_gist`. Si no, se omite la constraint y queda solo el `lockForUpdate`.
- Elegir y verificar la API de feriados (ej. ArgentinaDatos).
- Regla exacta de normalización de teléfonos argentinos (`PhoneNormalizer`).
- Las diferencias entre el mockup y la documentación de `arquitectura-frontend.md` (§8).

## Próximas iteraciones (no implementar todavía)

- **Historial de transacciones:** quién modificó qué reserva y cuándo.
- **Métricas de ocupación:** libres/ocupados y porcentaje por día, mes o departamento, calculados desde las reservas y los deptos activos.
- **Listado de reservas canceladas** para control.
