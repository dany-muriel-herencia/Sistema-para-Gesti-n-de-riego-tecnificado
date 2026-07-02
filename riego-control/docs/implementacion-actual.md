# Implementacion actual del sistema de riego

Este documento explica como esta escrito actualmente el codigo del proyecto `riego-control`. La aplicacion esta organizada como un sistema MVC en PHP para gestionar parcelas, sensores y turnos de riego, con una capa adicional de algoritmos de concurrencia pensada para simular la asignacion de hidrantes.

## Resumen general

El proyecto representa un centro de control de riego agricola. La idea principal es registrar parcelas, recibir o consultar lecturas de sensores, evaluar el estado de humedad y temperatura, y organizar turnos de riego usando hidrantes disponibles.

La implementacion actual combina dos enfoques:

- Una base MVC/API conectada a MySQL mediante PDO.
- Componentes de simulacion de concurrencia, como monitor, semaforo, productor-consumidor y lectores-escritores.

El codigo ya tiene modelos, controladores, endpoints API, vistas y scripts SQL. Sin embargo, algunas partes todavia no estan totalmente conectadas entre si. Por ejemplo, `index.php` espera metodos de planificacion y clima que no existen en los controladores actuales.

## Estructura de carpetas

```text
riego-control/
  app/
    Controllers/
    Models/
    Services/
    Views/
  backend/
    algorithms/
    analyzer/
    api/
    config/
    monitor/
  database/
  docs/
  frontend/
```

### `app/Controllers`

Contiene los controladores principales. Su trabajo es recibir datos, llamar a los modelos y devolver arreglos listos para mostrarse como JSON o enviarse a las vistas.

- `ParcelaController.php`: administra parcelas.
- `SensorController.php`: administra sensores.
- `RiegoController.php`: administra turnos de riego.

Estos controladores no contienen SQL directamente. Delegan el acceso a datos a los modelos.

### `app/Models`

Contiene las clases que representan entidades del sistema:

- `Parcela.php`
- `Sensor.php`
- `TurnoRiego.php`

Cada modelo tiene:

- Propiedades privadas.
- Constructor.
- Getters y setters.
- `fromArray()` para crear objetos desde arreglos.
- `toArray()` para convertir objetos a arreglos.
- Metodos CRUD como `all()`, `find()`, `save()` y `delete()`.

Los modelos usan `Database::getConnection()` para conectarse a MySQL.

### `backend/api`

Contiene endpoints PHP que exponen el sistema como API JSON:

- `parcelas.php`
- `sensores.php`
- `riego.php`

Cada archivo detecta el metodo HTTP con `$_SERVER['REQUEST_METHOD']` y responde segun corresponda:

- `GET`: listar o buscar por `id`.
- `POST`: crear.
- `PUT` / `PATCH`: actualizar.
- `DELETE`: eliminar.

La respuesta se envia con `json_encode()` y cabecera `Content-Type: application/json`.

### `backend/config`

Aqui esta la configuracion general:

- `database.php`: crea una conexion PDO a MySQL.
- `app.php`: define valores generales del sistema, como nombre, version, zona horaria y umbrales de estres hidrico.

`database.php` busca un archivo `.env`. Si no existe, usa valores por defecto:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_riego
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### `database`

Contiene la estructura inicial de la base de datos:

- `schema.sql`: crea la base `sistema_riego` y las tablas.
- `seed.sql`: inserta datos iniciales.

Las tablas principales son:

- `parcelas`
- `sensores`
- `hidrantes`
- `turnos_riego`

La relacion principal es:

- Un sensor pertenece a una parcela.
- Un turno de riego pertenece a una parcela.
- Un turno de riego puede estar asociado a un hidrante.

### `backend/algorithms`

Contiene clases que modelan problemas de concurrencia:

- `ProductorConsumidor.php`
- `LectoresEscritores.php`
- `Monitor.php`

Estas clases registran eventos en arreglos internos para mostrar como se comportaria la sincronizacion.

`ProductorConsumidor` mantiene una cola de parcelas que necesitan riego. Si una parcela tiene estres hidrico `alto` o `medio`, se encola; si no, se descarta.

`LectoresEscritores` simula acceso concurrente a recursos compartidos. Permite registrar cuando un lector entra o sale, y cuando un escritor espera, actualiza y libera el recurso.

`Monitor` controla el acceso al riego usando un semaforo. Si hay capacidad disponible, concede el riego; si no hay hidrantes libres, deja la solicitud en espera.

### `backend/monitor`

Contiene clases de apoyo para la sincronizacion:

- `Semaforo.php`
- `Hidrant.php`

`Semaforo` mantiene una cantidad de recursos disponibles. El metodo `wait()` consume un recurso si existe disponibilidad; `signal()` libera un recurso.

`Hidrant` representa un hidrante con identificador, nombre, disponibilidad y capacidad simultanea.

### `backend/analyzer`

`StressAnalyzer.php` evalua el estres hidrico segun temperatura y humedad.

La regla actual es:

- Si humedad es menor a `30` y temperatura mayor a `30`, el estres es `alto`.
- Si humedad esta entre `30` y `60`, el estres es `medio`.
- En los demas casos, el estres es `bajo`.

### `app/Views`

Contiene las vistas PHP:

- `dashboard.php`
- `mapa.php`
- `consola.php`

La vista mas completa es `dashboard.php`. Espera recibir:

- `$parcelas`
- `$clima`
- `$turnos`
- `$hidrantes`
- `$eventos`

Con esos datos renderiza metricas, una tabla de parcelas, turnos de riego, hidrantes y una consola de eventos.

### `frontend`

Contiene estilos, componentes y archivos JavaScript. En esta version el frontend esta preparado, pero varias piezas todavia son placeholders o estan poco desarrolladas.

Por ejemplo, `frontend/js/mapa-calor.js` solo contiene un comentario que indica que ahi deberia ir la logica para renderizar el mapa de calor.

## Flujo actual de la API

Un flujo tipico para listar parcelas es:

1. El navegador o cliente solicita `backend/api/parcelas.php`.
2. El endpoint crea un `ParcelaController`.
3. El controlador llama a `Parcela::all()`.
4. El modelo abre conexion con `Database::getConnection()`.
5. Se ejecuta un `SELECT` sobre la tabla `parcelas`.
6. Cada fila se transforma en un objeto `Parcela`.
7. El controlador convierte los objetos a arreglos.
8. El endpoint responde JSON.

El mismo patron se repite para sensores y turnos de riego.

## Flujo esperado del dashboard

`index.php` intenta construir la pantalla principal asi:

```php
$parcelaController = new ParcelaController();
$riegoController = new RiegoController();

$parcelas = $parcelaController->listar();
$clima = $parcelaController->clima();
$plan = $riegoController->planificar();
```

Despues pasa esos datos a `app/Views/dashboard.php`.

El problema es que, en la implementacion actual, `ParcelaController` no tiene el metodo `clima()` y `RiegoController` no tiene el metodo `planificar()`. Por eso el dashboard todavia no puede funcionar completo con el codigo tal como esta.

## Estado actual de cada parte

### Implementado

- CRUD de parcelas.
- CRUD de sensores.
- CRUD de turnos de riego.
- Conexion PDO a MySQL.
- Scripts SQL de esquema y datos iniciales.
- Endpoints API JSON.
- Clases base para monitor, semaforo e hidrante.
- Analizador simple de estres hidrico.

### Preparado pero aun poco conectado

- Servicios en `app/Services`.
- Vistas `mapa.php` y `consola.php`.
- Componentes frontend.
- Logica de mapa de calor.
- Algoritmos de concurrencia dentro del flujo real del dashboard.

### Pendiente o inconsistente

- `index.php` llama metodos no existentes: `ParcelaController::clima()` y `RiegoController::planificar()`.
- `dashboard.php` espera campos como `humedad`, `temperatura`, `estres_hidrico` y `duracion_minutos`, pero los modelos actuales no devuelven todos esos campos en los arreglos principales.
- El README menciona datos simulados desde JSON, pero los modelos actuales trabajan principalmente con MySQL.
- Las clases de concurrencia usan propiedades como `$parcela->id` y `$parcela->estresHidrico`, pero el modelo `Parcela` actual tiene propiedades privadas y no define `estresHidrico`.
- Hay mensajes con caracteres mal codificados en algunos endpoints, especialmente en textos con tildes.

## Lectura por capas

La forma mas facil de entender el proyecto es verlo por capas:

```text
Cliente / Navegador
       |
       v
backend/api/*.php o index.php
       |
       v
Controllers
       |
       v
Models
       |
       v
Database PDO / MySQL
```

Los algoritmos de concurrencia estan al costado de ese flujo principal. La intencion es que el controlador de riego los use para construir un plan, pero esa union todavia falta.

## Siguiente paso recomendado

Para completar la implementacion actual, lo mas importante seria decidir una sola fuente principal de datos:

- Si se usara MySQL, entonces hay que conectar sensores, parcelas, hidrantes y turnos reales desde la base de datos.
- Si se usara JSON simulado, entonces los modelos/controladores deben leer `database/sensores_simulados.json` y no depender de PDO.

Luego se deberian implementar `clima()` y `planificar()` para que `index.php` y `dashboard.php` puedan funcionar sin errores.
