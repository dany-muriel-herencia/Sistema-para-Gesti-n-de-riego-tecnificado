# Informe Académico: Simulación de Concurrencia en Sistema de Riego

## 1. Carátula
**Título:** Implementación de Algoritmos de Concurrencia en la Gestión de Riego Tecnificado
**Estudiante:** [Tu Nombre]
**Materia:** Sistemas Operativos / Concurrencia
**Fecha:** [Fecha de Entrega]

---

## 2. Introducción
El presente trabajo aborda la necesidad de optimizar y asegurar la integridad de los datos en un sistema de riego tecnificado que opera con múltiples sensores y actuadores simultáneamente. En entornos reales, diversos sensores de humedad y temperatura envían datos concurrentemente, mientras que el sistema debe decidir qué parcelas regar sin exceder la capacidad máxima de la bomba de agua (hidrantes). 

Para resolver este problema, se implementó un prototipo funcional aplicando los conceptos de **Productor-Consumidor**, **Monitores** y **Semáforos**, garantizando el paralelismo real y evitando condiciones de carrera.

---

## 3. Desarrollo

### 3.1. Análisis y Modelado del Sistema
El sistema se modeló identificando los siguientes componentes de concurrencia:

1. **Productores (Sensores):** Múltiples hilos que leen constantemente el estado de las parcelas. Si detectan estrés hídrico (baja humedad, alta temperatura), generan una "solicitud de riego".
2. **Consumidores (Controladores de Riego):** Hilos encargados de tomar las solicitudes pendientes y accionar los hidrantes.
3. **Variable Compartida (Buffer):** Una cola de riego donde los productores colocan las solicitudes y de donde los consumidores las extraen.
4. **Recurso Limitado:** El sistema físico solo soporta un máximo de **N hidrantes** abiertos simultáneamente debido a la presión del agua.

### 3.2. Mecanismos de Sincronización Aplicados

*   **Monitores (Condition Variables):** Se aplicaron para proteger la *Cola de Riego*. 
    *   Si la cola está llena, el Monitor bloquea a los productores para que no intenten encolar más parcelas, evitando desbordamientos de memoria.
    *   Si la cola está vacía, el Monitor bloquea a los consumidores, evitando esperas activas (busy waiting) que consumen CPU de forma ineficiente.
*   **Semáforos:** Se utilizaron para gestionar el acceso a los *Hidrantes*. 
    *   Se inicializa un semáforo con valor `MAX_HIDRANTES = 3`. 
    *   Cada vez que un consumidor saca una parcela de la cola, debe hacer un `acquire()` sobre el semáforo. Si ya hay 3 hidrantes abiertos, el hilo consumidor se bloquea hasta que otro termine y haga un `release()`.

### 3.3. Código y Explicación
El prototipo fue desarrollado en **Python**, utilizando la librería nativa `threading`, la cual permite la creación de hilos (threads) reales a nivel de sistema operativo.

*(Nota: Adjuntar el código del archivo `prototipo_riego_concurrente.py` en esta sección o hacer referencia a él).*

---

## 4. Pruebas y Análisis de Resultados

Se realizaron pruebas sometiendo el sistema a diferentes cargas de trabajo (ej. 5 productores enviando datos rápidamente frente a 2 consumidores).

**Resultados observados:**
1. **Evitación de Condiciones de Carrera:** Gracias al uso de la cola thread-safe (Monitor), ningún productor sobrescribió los datos de otro al intentar insertar en el mismo índice del buffer.
2. **Control de Recursos:** Al monitorear la salida por consola, se verificó que en ningún momento hubo más de 3 mensajes de "Hidrante Asignado" activos simultáneamente. El Semáforo bloqueó exitosamente al 4to hilo que intentó acceder al recurso.
3. **Manejo de Carga:** Cuando los productores generaron solicitudes más rápido de lo que los consumidores podían regar, el tamaño de la cola creció hasta llegar al máximo (`10`). En ese punto, el Monitor hizo pausar a los productores, demostrando un control de flujo perfecto (backpressure).

---

## 5. Conclusiones

*   **Aplicabilidad:** El patrón Productor-Consumidor demostró ser la arquitectura ideal para sistemas de IoT y domótica donde la recolección de datos (productores) y la actuación mecánica (consumidores) ocurren a velocidades distintas.
*   **Seguridad:** La implementación de Semáforos y Monitores es crucial; sin ellos, el sistema habría intentado encender todas las bombas de agua a la vez, lo que en la vida real causaría una pérdida de presión generalizada o un fallo eléctrico por sobrecarga.
*   **Limitaciones:** La simulación en Python está limitada por el GIL (Global Interpreter Lock). Aunque es excelente para operaciones de I/O y esperas (como simular tiempo de riego), si los algoritmos de estrés hídrico fueran extremadamente complejos matemáticamente, se requeriría el uso de `multiprocessing` en lugar de `threading` para aprovechar múltiples núcleos del procesador al 100%. Sin embargo, para la gestión de recursos compartidos evaluada en este trabajo, `threading` cumplió el objetivo satisfactoriamente.
