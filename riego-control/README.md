# Centro de Control de Riego - La Yarada

Proyecto MVC en PHP para simular el control de riego de parcelas agricolas en La Yarada, Tacna, usando algoritmos de sincronizacion y concurrencia.

## Fuente de datos actual

La primera version trabaja con datos simulados desde `database/sensores_simulados.json`. Ese archivo representa lecturas de humedad, temperatura, clima e hidrantes disponibles como si provinieran de sensores reales.

## Arquitectura

- `app/Controllers`: reciben las solicitudes y coordinan el flujo.
- `app/Models`: representan parcelas, sensores y turnos de riego.
- `app/Views`: muestran dashboard, mapa y consola.
- `backend/algorithms`: mantiene Productor-Consumidor, Lectores-Escritores y Monitor.
- `app/Services`: se conserva solo como punto de extension futuro; no participa en esta version.

Para integrar Arduino, ESP32, APIs o WebSockets en otra version, debe cambiarse el origen de datos sin modificar la logica principal de riego.
