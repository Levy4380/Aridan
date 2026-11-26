# Arquitectura del backend

Documento de diseño del backend del organizador de reservas. Las reglas de negocio están definidas en [CLAUDE.md](CLAUDE.md); acá se describe **cómo** se implementan. Todavía no hay código: es la guía para arrancar.

> **Convención de idioma:** todo el código va en **inglés**: tablas, columnas, clases, métodos, variables, nombres de rutas y comandos. Lo único en español es lo que ve el usuario: las URLs y los textos de la interfaz. El glosario está en [CLAUDE.md](CLAUDE.md).

## 1. Visión general

```
Navegador
   │
   ▼
Livewire (pantallas: calendario, nueva reserva, buscador, ABMs)
   │   valida la entrada, muestra advertencias, pide confirmación
   ▼
Actions (app/Actions)                  Queries (app/Queries)
   │  create / update / move /           calendar, availability,
   │  cancel reservation,                suggestions
   │  deactivate apartment
   ▼
Eloquent models + domain (app/Models, app/Domain)
   │
   ▼
PostgreSQL (serverless en Laravel Cloud)
```

- **Monolito Laravel + Livewire.** No hay API REST: los componentes Livewire llaman directamente a las actions y queries.
- **La lógica de negocio no vive en los componentes Livewire.** Vive en **actions**, que escriben, y en **queries**, que leen. Los componentes solo orquestan la pantalla. Así las reglas se testean sin UI, y si mañana hace falta una API o una app, se reutilizan.
- **Base: PostgreSQL.** Todo pasa por Eloquent y migraciones. La única pieza específica de Postgres es la *exclusion constraint* de la sección 5. Los tests corren contra Postgres.

## 2. Convenciones

- **Idioma:** ver la convención de arriba.
  - **Rutas:** URL en español y nombre en inglés. Ejemplo: `Route::get('/reservas/nueva', ...)->name('reservations.create')`.
  - **Textos de la UI:** en español, centralizados en `lang/es/*.php`. Incluye los mensajes de validación, de error y de advertencia, que el código referencia por clave (`__('reservations.overlap')`).
- **Empleados = `User`:** el starter kit trae el modelo `User` y la tabla `users`. Se mantienen, para no pelear con la autenticación. La clave foránea en `reservations` se llama `employee_id` (apunta a `users.id`), y la relación es `Reservation::employee()`.
- **Zona horaria:** `America/Argentina/Buenos_Aires`. Las fechas de reserva son `date`, sin hora.
- **Moneda:** pesos argentinos, con `decimal(12,2)`. No hay multimoneda.
- **Tests:** Pest, contra una base Postgres de testing. No se usa SQLite, porque no tiene la *exclusion constraint*.

## 3. Modelo de datos

### `users` (empleados)
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| name, email, password | (starter kit) | |
| color | string(7) | Hex, ej. `#4fc3f7`. Con él se pintan sus reservas en el calendario. Puede repetirse (con advertencia). |
| is_active | boolean | Por defecto `true`. Si es `false`, no puede iniciar sesión. Los empleados no se borran. |

### `apartments` (departamentos)
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| number | string, unique | `"102"`, `"307"`. Es un string por si aparece algo como "PB-A". |
| capacity | unsigned tinyint | Personas. |
| is_active | boolean | Por defecto `true`. |
| description | text, nullable | "Con balcón", "a la calle", etc. |
| sort_order | unsigned smallint | Orden de las filas en el calendario. |
| timestamps | | |

### `guests` (clientes)
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| first_name | string | |
| last_name | string | |
| phone | string, **unique** | Obligatorio. Se guarda **normalizado** (ver abajo). |
| document_number | string, nullable, **unique** | DNI/CUIT. Opcional, pero no se puede repetir. Se guarda solo con dígitos. |
| timestamps | | |

- **Dos claves únicas:** `phone` y `document_number`.
  - Un índice `unique` sobre una columna nullable admite varios `NULL`, tanto en Postgres como en MySQL. Así, muchos clientes pueden no tener documento.
- **Normalización:** para que la unicidad funcione, los dos campos se normalizan en el modelo con mutators, antes de guardar y antes de buscar. Si no, "11 1234-5678" y "+5491112345678" serían dos clientes distintos.
  - `document_number`: solo dígitos, sin puntos ni guiones.
  - `phone`: formato canónico `549` + los 10 dígitos nacionales (código de área + número, sin el 0 ni el 15), en `App\Domain\PhoneNormalizer`. Así "11 15 5555-0142", "011 5555-0142" y "+54 9 11 5555-0142" son el mismo cliente. Lo que no parece un número argentino se guarda solo con dígitos. En pantalla se muestra legible ("11 5555-0142") con `Format::phone`.

### `reservations` (reservas)
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| apartment_id | FK → apartments | |
| guest_id | FK → guests | |
| employee_id | FK → users | El responsable: quien hizo la reserva. Por defecto el usuario autenticado, pero se puede elegir otro empleado activo, al crear o al editar. |
| check_in_date | date | |
| check_out_date | date | Siempre posterior a `check_in_date`. |
| early_check_in | boolean | Por defecto `false`. |
| late_check_out | boolean | Por defecto `false`. |
| start_slot | integer | **Derivado.** Ver la sección 4. |
| end_slot | integer | **Derivado**, exclusivo. Ver la sección 4. |
| people_count | unsigned tinyint | |
| payment_status | string | Enum PHP `PaymentStatus`: `pending`, `deposit`, `paid`. Lo elige el empleado; nunca se deriva de los pagos. |
| total_amount | decimal(12,2), nullable | Total de la reserva. Solo con `deposit` (`PaymentStatus::hasTotal()`); en otro estado se guarda `null`. Lo cobrado está en `payments`. |
| extra_mattresses | unsigned tinyint | Colchones extra. Por defecto `0`. |
| is_movable | boolean | Por defecto `true`. |
| notes | text, nullable | La "nota de la reserva". |
| timestamps | | |
| deleted_at | timestamp, nullable | Soft delete: fecha de cancelación. |
| cancelled_by_id | FK → users, nullable | Quién la canceló, para control. |

**Reservas canceladas:** quedan en la base pero **no ocupan el departamento**. El trait `SoftDeletes` las excluye de todas las consultas por defecto: superposición, calendario, disponibilidad y el chequeo de `DeactivateApartment`. Solo se consultan con `withTrashed()` / `onlyTrashed()`, por ejemplo en un listado de canceladas para control.

Índice: `(apartment_id, start_slot, end_slot)`.

### `payments` (pagos)

Lo cobrado de una reserva, en uno o más pagos (ej. la seña por transferencia y el resto en efectivo). Solo existen con `payment_status` `deposit` o `paid`: al pasar a `pending` se borran. Cada pago se factura por separado.

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| reservation_id | FK → reservations, cascade | |
| amount | decimal(12,2) | Mayor a 0. |
| method | string, nullable | Enum PHP `PaymentMethod`: `transfer`, `cash`. Si falta, advertencia `missing_amount`. |
| invoiced_at | timestamp, nullable, índice | Facturado: `null` = no facturado. Se cambia siempre (`SetInvoiced` o desde el formulario), también en canceladas. |
| invoiced_by_id | FK → users, nullable | Quién lo marcó como facturado. |
| timestamps | | |

`Reservation::paidAmount()` es la suma (o `null` sin pagos) y `Reservation::balance()` lo que falta del total cuando está señada.

### `extras` y `extra_reservation`

> **Desactivados por ahora** con `config('extras.enabled')` (`EXTRAS_ENABLED=false`, `Extra::enabled()`). Las tablas y el código quedan; apagado, `CreateReservation` / `UpdateReservation` no sincronizan `extra_ids` y `CalendarQuery` no los carga.
| `extras` | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| name | string, unique | Seed inicial: "Cochera", "Desayuno". Es el nombre que se muestra en la UI. |
| is_active | boolean | Permite retirar un extra sin borrar el historial. |

`extra_reservation`: `extra_id` + `reservation_id`, con PK compuesta. Es la única relación N:M, y el nombre sigue la convención alfabética de Laravel.

### `holidays` (feriados)
| Columna | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| date | date, unique | |
| name | string | Tal como lo da la API, en español. |
| type | string, nullable | Inamovible, trasladable, puente, etc., según lo que dé la API. |

### Relaciones Eloquent
- `Apartment hasMany Reservation`
- `Guest hasMany Reservation`
- `User hasMany Reservation`, vía `employee_id`
- `Reservation belongsTo Apartment, Guest, User (employee)`, `belongsToMany Extra` y `hasMany Payment`
- `Payment belongsTo Reservation` (también cancelada) y `User (invoicedBy)`

## 4. Slots (medias jornadas): el núcleo de la regla de superposición

La regla de [CLAUDE.md](CLAUDE.md) se implementa convirtiendo cada reserva en un rango entero de **slots**. Un slot es una media jornada: mañana (`Morning`) o tarde (`Afternoon`).

```
slot(date, half) = daysSinceEpoch(date) * 2 + (half == Morning ? 0 : 1)

start_slot = slot(check_in_date,  early_check_in ? Morning   : Afternoon)
end_slot   = slot(check_out_date, late_check_out ? Afternoon : Morning) + 1   // exclusive
```

Dos reservas del mismo departamento **chocan** si `start_A < end_B && start_B < end_A`.

| Caso, el mismo día D | A (sale D) | B (entra D) | ¿Choca? |
|---|---|---|---|
| Normal | end = D·2+1 | start = D·2+1 | No |
| B con `early_check_in` | end = D·2+1 | start = D·2 | **Sí** |
| A con `late_check_out` | end = D·2+2 | start = D·2+1 | **Sí** |

- **Encapsulado:** la lógica vive en un value object `App\Domain\StayRange`, que se construye con las fechas y los dos booleanos. Expone `startSlot()`, `endSlot()` y `overlaps(StayRange $other)`. Es la pieza más testeada del sistema.
- **Slots persistidos:** `start_slot` y `end_slot` se guardan en la tabla y los recalcula el modelo `Reservation` al guardar (evento `saving`). Así la consulta de superposición, la de calendario y la de disponibilidad son una simple comparación de enteros indexada, sin lógica de fechas en SQL ni funciones propias del motor.
- **Calendario:** el mismo cálculo sirve para dibujar la grilla. Cada columna del calendario es un slot.

## 5. Actions (escritura)

Cada action es una clase invocable en `app/Actions`, con una única responsabilidad, y corre dentro de una transacción.

| Action | Qué hace | Bloquea si… |
|---|---|---|
| `CreateReservation` | Recibe un `guest_id` existente o los datos de un guest nuevo; si es nuevo, lo crea. Después crea la reserva, asocia los extras y crea los pagos (`payments.*`: `amount`, `method`, `is_invoiced`; quien carga queda como `invoiced_by_id`). Recibe el `employee_id` del responsable, que la UI precarga con el usuario autenticado. | Hay superposición; el departamento está inactivo; `check_out_date <= check_in_date`; el guest nuevo repite `phone` o `document_number`; el responsable es un empleado inactivo. |
| `UpdateReservation` | Edita los datos de la reserva, incluido el responsable. Si el responsable no cambia, puede seguir siendo un empleado ya inactivo. Sincroniza los pagos: los que vienen con `id` se mantienen (con su facturación), los nuevos se crean y los que faltan se borran. | Lo mismo que `CreateReservation`, excluyendo a la propia reserva del chequeo, y un `payments.*.id` que no es de esta reserva. |
| `MoveReservation` | Cambia el departamento y, opcionalmente, las fechas. | Hay superposición en el destino o el destino está inactivo. |
| `CancelReservation` | Guarda `cancelled_by_id` y hace el soft delete. El departamento queda libre en esas fechas. | — |
| `DeactivateApartment` | Pone `is_active = false`. | El departamento tiene reservas futuras (`end_slot > currentSlot`). |
| `DeleteApartment` | Borra el depto para siempre, con sus reservas pasadas y canceladas (`forceDelete`; pagos y extras caen en cascada). Bloquea el depto con `lockForUpdate` mientras chequea. | Tiene reservas futuras o en curso, igual que `DeactivateApartment`. |
| `ActivateApartment` | Pone `is_active = true`. | — |
| `AddPayment` | Agrega un pago desde el detalle de la reserva, sin pasar por la edición completa, y guarda el estado que eligió el empleado (`deposit` o `paid`); el total solo se mantiene si queda señada. | La reserva está cancelada; el monto no es mayor a 0; el estado no es señado ni pagado. |
| `SetInvoiced` | Marca un **pago** como facturado o no, con quién y cuándo. | — (se puede cambiar siempre, también en canceladas) |
| `SaveGuest` (`app/Actions/Guests`) | Alta y edición de un cliente desde la pantalla Clientes. | Falta nombre, apellido o teléfono; el teléfono o el DNI/CUIT (normalizados) ya son de otro cliente. |
| `DeleteGuest` (`app/Actions/Guests`) | Borra el cliente para siempre. | Tiene alguna reserva, contando las canceladas. |
| `DeleteEmployee` (`app/Actions/Employees`) | Borra la cuenta para siempre. Pasa todas sus reservas (con `withTrashed`) al empleado elegido, pone en `null` `cancelled_by_id` e `invoiced_by_id` que lo apunten y borra sus sesiones (las passkeys caen en cascada). | Se elimina a sí mismo; tiene reservas y no se eligió un empleado activo distinto a quien pasarlas. |
| `CreateApartment` / `UpdateApartment` | Alta y edición de número, capacidad, descripción y orden. La activación va por las actions de arriba. | El número se repite; la capacidad es menor a 1. |

### Errores y advertencias
El principio es "**el sistema avisa, no bloquea**". Por eso se separan dos tipos de resultado:

- **Errores**: bloquean la operación. Se lanzan como `ValidationException`, y Livewire los muestra en el formulario automáticamente. Son solo los de la tabla de arriba.
- **Advertencias**: no bloquean, pero requieren que el empleado las confirme. Las calcula `App\Domain\ReservationWarnings` **antes** de ejecutar la action. Cada advertencia tiene una clave en inglés (`capacity_exceeded`) y su texto en español en `lang/es/`.
  - **`capacity_exceeded`:** `people_count > capacity + extra_mattresses` ("Faltan N lugares: sumá colchones extra").
  - **`not_movable`:** al mover una reserva con `is_movable = false` ("Esta reserva no es movible: ¿moverla igual?").
  - **`missing_amount`:** `payment_status` es `deposit` o `paid` pero no hay ningún pago con monto, o algún pago tiene monto sin tipo (o tipo sin monto). Un renglón vacío de más se ignora.
  - **`missing_total`:** `payment_status` es `deposit` y no hay `total_amount`.
  - **`deposit_covers_total`:** `payment_status` es `deposit` y lo cobrado ya alcanza el total ("¿No está pagada?"). El estado nunca cambia solo.
  - **`similar_guest_name`:** se está creando un guest nuevo y ya existe otro con el mismo nombre y apellido, pero con distinto teléfono. Puede ser la misma persona con otro número.

### Búsqueda y alta de clientes
En el formulario de reserva, el bloque de cliente funciona así:
1. El empleado escribe un DNI/CUIT o un teléfono. `GuestLookup` normaliza el valor y busca por `document_number` o `phone`.
2. **Si lo encuentra:** muestra el guest y lo selecciona, y la reserva usa su `guest_id`.
3. **Si no lo encuentra:** se habilitan los campos para cargar uno nuevo (nombre, apellido, teléfono obligatorio y DNI/CUIT opcional), que se crea junto con la reserva.
4. **Si al cargar uno nuevo el teléfono o el documento ya existen:** no es un error genérico. La UI ofrece "Este teléfono ya pertenece a *Nombre Apellido*: ¿usar ese cliente?".

**Flujo en Livewire:**
1. El empleado completa el formulario.
2. Se calculan las advertencias.
3. Si hay, se muestran con un botón "Confirmar igual".
4. Se ejecuta la action.

La action vuelve a validar los **errores**, nunca confía en la UI. Las advertencias no se revalidan.

### Concurrencia
Dos empleados podrían cargar a la vez reservas que se pisan. Para evitarlo, `CreateReservation`, `UpdateReservation` y `MoveReservation` hacen:

1. `DB::transaction(...)`.
2. `Apartment::lockForUpdate()->find($apartmentId)`: bloquea la fila del departamento de destino.
3. Chequean la superposición contra la base.
4. Insertan o actualizan.

**Red de seguridad en la base:** además del chequeo en código, una migración crea esta *exclusion constraint*:

```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;

ALTER TABLE reservations
  ADD CONSTRAINT reservations_no_overlap
  EXCLUDE USING gist (
    apartment_id WITH =,
    int4range(start_slot, end_slot) WITH &&
  )
  WHERE (deleted_at IS NULL);
```

- `int4range(start, end)` es semiabierto `[start, end)`, igual que los slots. Por eso un checkout a la mañana y un check-in a la tarde no chocan.
- El `WHERE deleted_at IS NULL` deja afuera a las reservas canceladas.
- Si algo esquivara la validación del código, la base rechaza el insert. La action convierte ese error (SQLSTATE `23P01`) en el mismo mensaje de "superposición".
- **Hay que verificar** que la Postgres de Laravel Cloud permita `btree_gist`. Si no la permite, se omite la constraint y queda solo el chequeo con `lockForUpdate`, que alcanza por sí solo.

## 6. Queries (lectura)

En `app/Queries`, sin efectos secundarios.

### `CalendarQuery`
- **Entrada:** una ventana de fechas `[from, to]`. Por defecto el mes visible, pero puede ser cualquier rango.
- **Salida:** un DTO `CalendarGrid`:
  - apartments (filas)
  - slots (columnas)
  - bloques de reserva, cada uno con su color, sus slots, los textos de sus 3 líneas, si es movible y sus extras
  - holidays de la ventana
  - libres y ocupados por slot
- **Qué departamentos muestra:** los **activos**, más los inactivos que tengan reservas en la ventana. Así no desaparece el historial de un departamento que se dio de baja.
- **Consulta:** `start_slot < windowEnd` y `end_slot > windowStart`, con eager loading de `guest`, `employee` y `extras`. Las reservas que cruzan los bordes de la ventana se recortan visualmente.

### `AvailabilityQuery`
- **Entrada:** fechas, `early_check_in` y `late_check_out`.
- **Salida:** para cada departamento activo:
  - si está libre en ese rango
  - las reservas que lo ocupan, marcando si son movibles
  - **slots libres antes y después del rango** (`freeSlotsBefore`, `freeSlotsAfter`), contados sin límite de mes y hasta la reserva anterior o siguiente, con un tope razonable (ej. 60 días)

### `ApartmentSuggestions`
Es parte del producto: el buscador y el drawer de alta la usan. Ordena el resultado de `AvailabilityQuery` y lo separa en tres grupos: disponibles, ocupados solo por reservas movibles y no disponibles. Es la evolución de las prioridades del buscador de la planilla. Orden de los disponibles:
1. **Ajuste de capacidad:** exacta > más grande (penaliza el exceso) > más chica, que exige adicionales.
2. **Fragmentación:** cuántos días quedan sueltos antes y después. Menos es mejor. Ejemplo: un departamento libre solo el 10 y el 11 gana frente a otro libre toda la semana.
3. **Desempate:** `apartments.sort_order`.

Queda aislada en su propia clase para poder ajustar los pesos sin tocar nada más.

**Mapa de ocupación:** para cada depto, `AvailabilityQuery` también devuelve el estado de cada slot en una ventana alrededor de lo pedido. Arranca 6 días antes de la entrada y dura como mínimo 21 días. Cada slot puede ser `free`, `fixed` o `movable`, y se marca si cae dentro de lo pedido. La UI lo dibuja como cuadrados por día, partidos en mañana y tarde.

## 7. Feriados

- **Comando:** `php artisan holidays:sync {year?}` descarga los feriados del año pedido y del siguiente, y hace un `upsert` por `date`.
- **Fuente:** ArgentinaDatos, `https://api.argentinadatos.com/v1/feriados/{año}`, que devuelve `[{fecha, tipo, nombre}]`. Verificada el 25/11/2026; implementada en `App\Holidays\ArgentinaDatosProvider`.
- **Abstracción:** la API se usa detrás de una interfaz `HolidayProvider`. Si cambia, se cambia una sola clase.
- **Programación:** el scheduler de Laravel lo corre mensualmente; Laravel Cloud soporta el scheduler.
- **Fallas:** si la API falla, se loguea y se mantienen los datos existentes. El calendario nunca consulta la API directamente.

## 8. Autenticación y acceso

- Login del starter kit (Fortify), **básico**: sin registro público, sin recuperación de contraseña ni verificación de email (no hay mail), sin 2FA ni passkeys. Todas las features opcionales de Fortify están apagadas en `config/fortify.php`. Cada empleado cambia su nombre, email y contraseña en `/perfil`.
- **Primer empleado:** se crea con el comando `php artisan employee:create` o un seeder. Hace falta porque no hay registro público.
- **El resto:** se gestiona desde la pantalla Empleados (`/empleados`). Ahí se dan de alta, se editan el nombre, el email y el color, y se activan o desactivan. Actions: `CreateEmployee`, `UpdateEmployee`, `DeactivateEmployee` / `ActivateEmployee` y `DeleteEmployee`. Ni `DeactivateEmployee` ni `DeleteEmployee` dejan que un empleado se desactive o se elimine a sí mismo, para que nunca se quede el sistema sin nadie que pueda entrar.
- **Login de inactivos:** un empleado con `is_active = false` no puede entrar. Se valida en el login (`Fortify::authenticateUsing` en `FortifyServiceProvider`, con el mensaje `auth.inactive` solo si la contraseña es correcta) y con el middleware `EnsureEmployeeIsActive`, que cierra la sesión si lo desactivan mientras está conectado.
- **Advertencia** `color_in_use`: aparece si el color elegido ya lo usa otro empleado. No bloquea.
- **Permisos:** todos los empleados autenticados pueden hacer todo; no hay roles ni policies por ahora. Todas las rutas van con el middleware `auth`.

## 9. Rutas (borrador)

| URL (español) | Nombre (inglés) | Pantalla |
|---|---|---|
| `/calendario` | `calendar` | Grilla de ocupación (inicio) |
| `/buscador` | `availability` | Búsqueda de disponibilidad. Es la única entrada para una reserva nueva: el alta se hace en un drawer dentro de esta pantalla. |
| `/reservas/{reservation}` | `reservations.show` | Abre el calendario en el mes de la reserva con su panel (detalle, edición, movimiento y cancelación). |
| `/departamentos` | `apartments.index` | ABM de departamentos |
| `/clientes` | `guests.index` | Listado y edición de clientes |
| `/pendientes-a-facturar` | `billing.pending` | Pagos no facturados de reservas señadas o pagadas, uno por fila (`PendingInvoicesQuery`); por defecto solo transferencias, `?efectivo=true` suma efectivo |
| `/empleados` | `employees.index` | Alta, edición, color y activación de empleados |

## 10. Estructura de carpetas

```
app/
  Actions/
    Reservations/    CreateReservation, UpdateReservation, MoveReservation, CancelReservation
    Apartments/      ActivateApartment, DeactivateApartment
  Domain/
    StayRange.php              value object de slots
    ReservationWarnings.php
    PhoneNormalizer.php
    GuestLookup.php            búsqueda por documento o teléfono
    PaymentStatus.php          enum
  Queries/
    CalendarQuery.php
    AvailabilityQuery.php
  Holidays/
    HolidayProvider.php        interfaz
    ArgentinaDatosProvider.php (o el que se elija)
  Console/Commands/
    SyncHolidays.php
    CreateEmployee.php
  Models/
    User.php  Apartment.php  Guest.php  Reservation.php  Extra.php  Holiday.php
  Livewire/                    pantallas (fuera del alcance de este documento)
lang/es/                       todos los textos visibles
database/
  migrations/  seeders/  factories/
tests/
  Unit/Domain/        StayRangeTest (tabla de casos de solape)
  Feature/Actions/    CreateReservationTest, MoveReservationTest, DeactivateApartmentTest…
  Feature/Queries/    CalendarQueryTest, AvailabilityQueryTest
```

## 11. Decisiones pendientes

1. ~~Cancelación~~ **Resuelto:** soft delete con `cancelled_by_id`. La reserva queda para control y no ocupa el departamento.
2. ~~Documento del cliente~~ **Resuelto:** opcional y único.
3. ~~Búsqueda de clientes~~ **Resuelto:** se busca un cliente existente por documento o teléfono, o se carga uno nuevo. `phone` es obligatorio y único.
4. ~~Motor de base de datos~~ **Resuelto:** PostgreSQL. Falta verificar que se pueda usar `btree_gist` en Laravel Cloud.
5. **Historial de cambios** (próxima iteración): se puede implementar con un observer sobre `Reservation` que escriba en `reservation_history`, con quién, cuándo y el diff en JSON. El diseño actual no lo impide.
