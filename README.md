# Aridan

**Aridan** es el gestor de reservas de un apart familiar: una aplicación web para cargar, ver y organizar las reservas de sus departamentos.

## El negocio

Aridan es un emprendimiento familiar: un apart con 18 departamentos repartidos en tres pisos. Cada uno es distinto, con su propio tamaño y su capacidad, de dos a cinco personas. Entre todos los atiende un equipo chico, en el que cualquiera toma una consulta, ofrece un departamento y cierra una reserva.

Hasta ahora todo se manejaba en una planilla: una hoja por mes, una fila por departamento y cada reserva pintada con el color de quien la tomó. Funcionaba, pero mucho del conocimiento quedaba en la cabeza de una sola persona. Cuánto se cobró, qué huésped pidió *ese* departamento "sí o sí", cuál se puede mover a otro para hacer lugar o quién es cliente de siempre eran cosas que se escribían a mano en una celda o, directamente, no se escribían.

En un apart, el día a día es acomodar piezas:

- Un huésped se va a la mañana y otro entra esa misma tarde.
- Una familia llega más temprano o pide quedarse hasta la tarde.
- Un grupo alquila dos departamentos a nombre de la misma persona.
- Llega una consulta para cuatro personas y el departamento justo está ocupado. Hay que decidir si conviene uno más grande, uno más chico con un colchón extra, o mover una reserva que todavía no tiene departamento fijo.

## Qué hace Aridan

La idea es **mejorar la gestión sin cambiar la forma de trabajar**:

- **Calendario como el de la planilla:** filas por departamento, días partidos en mañana y tarde, y cada reserva pintada con el color del empleado responsable.
- **Buscador de disponibilidad:** con las fechas y la cantidad de personas, sugiere el mejor departamento. Prioriza la capacidad justa y lo que menos "parte" la disponibilidad. También muestra qué departamentos se podrían liberar moviendo reservas movibles.
- **Carga rápida:** del buscador se pasa a un drawer con los datos ya fijados. Solo se piden los campos mínimos, y siempre hay un campo libre de observaciones.
- **Sin choques:** el sistema no deja guardar dos reservas que se pisen en el mismo departamento, ni siquiera por media jornada. Para el resto, avisa pero no bloquea.
- **Todo queda registrado:** quién es responsable de cada reserva, cuánto se cobró y cómo, si se puede mover y el historial de cada cliente.

Las reglas de negocio completas y las guías de arquitectura están en [CLAUDE.md](CLAUDE.md), [arquitectura-backend.md](arquitectura-backend.md) y [arquitectura-frontend.md](arquitectura-frontend.md).

## Stack

Laravel + Livewire (starter kit oficial, Flux, Tailwind), PostgreSQL, Pest. Se despliega en Laravel Cloud.

## Desarrollo local

Requisitos: PHP 8.4 (con `pdo_pgsql`), Composer, Node 22 y Docker.

```bash
# 1. Base de datos (Postgres 18, con las bases aridan y aridan_testing)
docker compose up -d

# 2. Dependencias y configuración
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# 3. Levantar la app (servidor, Vite y logs)
composer run dev
```

La app queda en <http://localhost:8000>. En local, el seeder crea un empleado de prueba: `test@example.com` / `password`.

En local, el seeder también carga datos de prueba (empleados, clientes y dos meses de reservas) para ver el calendario con contenido. En producción solo carga los departamentos y los extras.

Para traer los feriados de Argentina (en producción lo hace el scheduler una vez por mes):

```bash
php artisan holidays:sync
```

No hay registro público. Los empleados se crean con:

```bash
php artisan employee:create
```

```bash
# Tests (contra la base aridan_testing)
php artisan test

# Estilo de código, antes de cada commit
vendor/bin/pint
```
