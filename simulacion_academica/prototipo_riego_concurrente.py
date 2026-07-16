import threading
import time
import random
import queue
import mysql.connector
from datetime import datetime
import json
import os

# ============================================================================
# PROTOTIPO ACADEMICO: SISTEMA DE GESTION DE RIEGO (CONCURRENCIA)
# Algoritmos: Productor-Consumidor + Monitor (queue.Queue) + Semaforo Dinamico
# Requisito: pip install mysql-connector-python
# ============================================================================

# Configuracion de Base de Datos Laragon
DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'root',
    'password': '',
    'database': 'sistema_riego'
}

# ===========================================================================
# VARIABLES GLOBALES COMPARTIDAS ENTRE HILOS
# ===========================================================================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_PATH = os.path.join(BASE_DIR, '..', 'riego-control', 'backend', 'api', 'estado_concurrencia.json')

# 1. MONITOR: Cola con capacidad maxima (simula el buffer compartido)
#    Para la prueba de carga extrema cambiar a 2
CAPACIDAD_MAXIMA_COLA = 2
cola_riego = queue.Queue(maxsize=CAPACIDAD_MAXIMA_COLA)

simulacion_activa = True

# ===========================================================================
# SEMAFORO DINAMICO — Mecanismo elegido: opcion (b)
#
# Se usa un CONTADOR PROPIO protegido por threading.Lock + threading.Condition
# en lugar de threading.Semaphore, porque:
#   - threading.Semaphore NO permite cambiar su capacidad una vez creado.
#   - La opcion (a) (recrear el Semaphore) requiere coordinar la migracion
#     de los permisos en uso al nuevo objeto, lo que es propenso a condiciones
#     de carrera dificiles de depurar.
#   - La opcion (b) encapsula todo el estado en una sola estructura: un entero
#     `en_uso`, un entero `limite` y un Condition. Cambiar `limite` en caliente
#     es tan simple como actualizar el entero y llamar notify_all() para
#     despertar a los hilos que estaban esperando (ahora pueden haber cupo).
#
# Interfaz publica:
#   acquire()            -> bloquea hasta que en_uso < limite
#   release()            -> libera un permiso y notifica a hilos en espera
#   actualizar_limite(n) -> cambia el limite en caliente sin reiniciar hilos
# ===========================================================================

class SemaforoDinamico:
    """
    Semaforo con limite ajustable en tiempo de ejecucion.
    Usa Condition (que internamente contiene un Lock) para proteger
    el estado compartido (en_uso, limite) y para bloquear/despertar hilos.
    """

    def __init__(self, limite_inicial: int):
        self._condition = threading.Condition()
        self._limite = max(1, limite_inicial)
        self._en_uso = 0

    # ------------------------------------------------------------------ #
    def acquire(self, timeout: float = None) -> bool:
        """
        Adquiere un permiso. Bloquea hasta que haya cupo (en_uso < limite)
        o hasta que expire el timeout.
        Retorna True si adquirio el permiso, False si expiro el timeout.
        """
        with self._condition:
            if timeout is not None:
                deadline = time.monotonic() + timeout
                while self._en_uso >= self._limite:
                    restante = deadline - time.monotonic()
                    if restante <= 0:
                        return False
                    self._condition.wait(timeout=restante)
            else:
                while self._en_uso >= self._limite:
                    self._condition.wait()
            self._en_uso += 1
            return True

    # ------------------------------------------------------------------ #
    def release(self):
        """
        Libera un permiso y notifica a todos los hilos en espera.
        """
        with self._condition:
            if self._en_uso > 0:
                self._en_uso -= 1
            self._condition.notify_all()

    # ------------------------------------------------------------------ #
    def actualizar_limite(self, nuevo_limite: int):
        """
        Cambia el limite del semaforo en caliente.
        Si el nuevo limite es mayor, los hilos bloqueados seran notificados
        y podran adquirir permisos inmediatamente.
        Si el nuevo limite es menor, los hilos en uso terminan normalmente
        (no se interrumpen); solo se dejaran de aceptar nuevos hasta bajar.
        """
        nuevo_limite = max(1, nuevo_limite)
        with self._condition:
            if nuevo_limite != self._limite:
                self._limite = nuevo_limite
                self._condition.notify_all()

    # ------------------------------------------------------------------ #
    @property
    def limite(self) -> int:
        with self._condition:
            return self._limite

    @property
    def en_uso(self) -> int:
        with self._condition:
            return self._en_uso


# Instancia global del semaforo dinamico (limite inicial = 3 como antes,
# pero se actualizara en la primera consulta a MySQL)
semaforo_hidrantes = SemaforoDinamico(limite_inicial=3)


# ===========================================================================
# CONEXION A BASE DE DATOS
# ===========================================================================
def get_db_connection():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except mysql.connector.Error as err:
        print(f"[ERROR DB] {err}")
        return None


# ===========================================================================
# HILO MONITOR DE HIDRANTES
# Consulta periodicamente la tabla `hidrantes` y actualiza el limite
# del semaforo sin reiniciar el proceso.
# ===========================================================================
class MonitorHidrantes(threading.Thread):
    """
    Hilo daemon que cada INTERVALO_SEG segundos ejecuta:
        SELECT SUM(capacidad) FROM hidrantes WHERE estado = 'disponible'
    y llama a semaforo_hidrantes.actualizar_limite(resultado).

    Si el resultado cambia respecto al limite actual, lo registra en consola
    para que el cambio sea visible durante la simulacion.
    """

    INTERVALO_SEG = 10  # Frecuencia de refresco

    def __init__(self):
        super().__init__(daemon=True)
        self.name = "MonitorHidrantes"

    def _consultar_capacidad(self, cursor) -> int:
        """
        Consulta la suma de capacidades de los hidrantes disponibles.
        Retorna al menos 1 para que el semaforo nunca quede bloqueado.
        """
        cursor.execute(
            "SELECT COALESCE(SUM(capacidad), 0) AS total "
            "FROM hidrantes WHERE estado = 'disponible'"
        )
        row = cursor.fetchone()
        total = int(row['total']) if row and row['total'] else 0
        return max(1, total)

    def run(self):
        global simulacion_activa

        db = get_db_connection()
        if not db:
            print("[MONITOR HIDRANTES] No pudo conectar a DB. Capacidad fija en valor inicial.")
            return

        cursor = db.cursor(dictionary=True)

        # Primera lectura inmediata antes de esperar el intervalo
        try:
            nuevo = self._consultar_capacidad(cursor)
            anterior = semaforo_hidrantes.limite
            semaforo_hidrantes.actualizar_limite(nuevo)
            print(f"[MONITOR HIDRANTES] Capacidad inicial desde DB: {nuevo} hidrantes simultaneos.")
        except mysql.connector.Error as e:
            print(f"[MONITOR HIDRANTES] Error en primera lectura: {e}")

        while simulacion_activa:
            time.sleep(self.INTERVALO_SEG)
            if not simulacion_activa:
                break

            try:
                nuevo = self._consultar_capacidad(cursor)
                anterior = semaforo_hidrantes.limite

                if nuevo != anterior:
                    semaforo_hidrantes.actualizar_limite(nuevo)
                    print(
                        f"\n{'='*60}\n"
                        f"[MONITOR HIDRANTES] *** CAPACIDAD ACTUALIZADA ***\n"
                        f"  Anterior: {anterior}  ->  Nuevo: {nuevo}\n"
                        f"  (Cambio detectado en tabla `hidrantes` de MySQL)\n"
                        f"{'='*60}\n"
                    )
                else:
                    print(f"[MONITOR HIDRANTES] Capacidad sin cambios: {nuevo} (en uso: {semaforo_hidrantes.en_uso})")

            except mysql.connector.Error as e:
                print(f"[MONITOR HIDRANTES] Error consultando DB: {e}")

        cursor.close()
        db.close()


# ===========================================================================
# HILO MONITOR DE CONCURRENCIA (EXPORTA ESTADO A JSON PARA FRONTEND)
# ===========================================================================
class MonitorConcurrencia(threading.Thread):
    def __init__(self):
        super().__init__(daemon=True)
        self.name = "MonitorConcurrencia"

    def run(self):
        global simulacion_activa
        while simulacion_activa:
            time.sleep(1)
            estado = {
                "semaforo_en_uso": semaforo_hidrantes.en_uso,
                "semaforo_limite": semaforo_hidrantes.limite,
                "cola_espera": cola_riego.qsize(),
                "timestamp": int(time.time())
            }
            try:
                # Ensure directory exists just in case
                os.makedirs(os.path.dirname(JSON_PATH), exist_ok=True)
                with open(JSON_PATH, 'w') as f:
                    json.dump(estado, f)
            except Exception as e:
                print(f"[ERROR JSON] No se pudo escribir {JSON_PATH}: {e}")


# ===========================================================================
# CLASE PRODUCTOR (Lee parcelas de MySQL y evalua estres hidrico)
# ===========================================================================
class ProductorSensores(threading.Thread):
    def __init__(self, id_productor):
        super().__init__(daemon=True)
        self.id_productor = id_productor

    def run(self):
        global simulacion_activa

        db = get_db_connection()
        if not db:
            print(f"[PRODUCTOR {self.id_productor}] No pudo conectar a DB. Terminando.")
            return

        cursor = db.cursor(dictionary=True)

        while simulacion_activa:
            time.sleep(random.uniform(0.5, 1.5))  # Produce rapido para saturar

            cursor.execute("SELECT id, nombre FROM parcelas WHERE estado = 'activa'")
            parcelas = cursor.fetchall()

            for parcela in parcelas:
                if not simulacion_activa:
                    break

                # Consultar lectura real de sensores fisicos desde MySQL
                cursor.execute(
                    "SELECT humedad, temperatura FROM sensores "
                    "WHERE parcela_id = %s ORDER BY fecha_medicion DESC LIMIT 1",
                    (parcela['id'],)
                )
                sensor_data = cursor.fetchone()

                if sensor_data:
                    humedad = float(sensor_data['humedad'])
                    temperatura = float(sensor_data['temperatura'])
                else:
                    # Valores por defecto sin estres si no hay sensores
                    humedad = 60.0
                    temperatura = 25.0

                # Algoritmo de estres hidrico
                if humedad < 30.0 or temperatura > 35.0:
                    item_riego = {
                        'parcela_id': parcela['id'],
                        'nombre': parcela['nombre']
                    }

                    # SECCION CRITICA: Insertar en el MONITOR (cola compartida)
                    try:
                        cola_riego.put(item_riego, timeout=0.5)
                        print(f"[PRODUCTOR {self.id_productor}] Sequia en '{parcela['nombre']}' -> ENCOLADA (Cola: {cola_riego.qsize()}/{CAPACIDAD_MAXIMA_COLA})")
                    except queue.Full:
                        # El MONITOR bloquea al productor: cola llena
                        print(f"[MONITOR BLOQUEADO] Cola llena ({CAPACIDAD_MAXIMA_COLA}/{CAPACIDAD_MAXIMA_COLA}). PRODUCTOR {self.id_productor} pausado.")

        cursor.close()
        db.close()


# ===========================================================================
# CLASE CONSUMIDOR (Extrae de la cola y riega usando el Semaforo Dinamico)
# ===========================================================================
class ConsumidorRiego(threading.Thread):
    def __init__(self, id_consumidor):
        super().__init__(daemon=True)
        self.id_consumidor = id_consumidor

    def run(self):
        global simulacion_activa

        db = get_db_connection()
        if not db:
            print(f"[CONSUMIDOR {self.id_consumidor}] No pudo conectar a DB. Terminando.")
            return

        cursor = db.cursor()

        while simulacion_activa:
            try:
                # MONITOR: Extraer de la cola (bloquea si esta vacia)
                item = cola_riego.get(timeout=3)
            except queue.Empty:
                continue

            parcela_id = item['parcela_id']
            parcela_nombre = item['nombre']

            print(f"[CONSUMIDOR {self.id_consumidor}] Evaluando regar '{parcela_nombre}'...")

            # SECCION CRITICA 2: Adquirir SEMAFORO DINAMICO (limita hidrantes fisicos)
            adquirido = semaforo_hidrantes.acquire()
            if not adquirido:
                cola_riego.task_done()
                continue

            limite_actual = semaforo_hidrantes.limite
            en_uso_actual = semaforo_hidrantes.en_uso
            print(f"[SEMAFORO] Hidrante asignado a '{parcela_nombre}'. En uso: {en_uso_actual}/{limite_actual}")

            try:
                # Registrar en DB que empezo el riego
                inicio = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                sql_insert = "INSERT INTO turnos_riego (parcela_id, hidrante_id, inicio, estado) VALUES (%s, %s, %s, %s)"
                cursor.execute(sql_insert, (parcela_id, self.id_consumidor, inicio, 'regando'))
                db.commit()
                turno_id = cursor.lastrowid

                # Simular tiempo de riego (lento para saturar la cola)
                tiempo_riego = random.uniform(8.0, 12.0)
                print(f"[CONSUMIDOR {self.id_consumidor}] Regando '{parcela_nombre}' por {tiempo_riego:.1f}s...")
                time.sleep(tiempo_riego)

                # Actualizar DB: riego completado
                fin = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                sql_update = "UPDATE turnos_riego SET fin = %s, estado = 'completado' WHERE id = %s"
                cursor.execute(sql_update, (fin, turno_id))
                db.commit()

                print(f"[CONSUMIDOR {self.id_consumidor}] Riego COMPLETADO en '{parcela_nombre}'. Turno #{turno_id} guardado en DB.")

            finally:
                # Liberar SEMAFORO DINAMICO
                semaforo_hidrantes.release()
                print(f"[SEMAFORO] Hidrante liberado de '{parcela_nombre}'. En uso ahora: {semaforo_hidrantes.en_uso}/{semaforo_hidrantes.limite}")
                cola_riego.task_done()

        cursor.close()
        db.close()


# ===========================================================================
# FUNCION PRINCIPAL
# ===========================================================================
def principal():
    global simulacion_activa
    print("=" * 70)
    print("PRUEBA DE CARGA EXTREMA - MONITOR Y SEMAFORO DINAMICO")
    print(f"Cola maxima (Monitor): {CAPACIDAD_MAXIMA_COLA} | Hidrantes: consultando MySQL...")
    print("=" * 70)

    # Hilo monitor de hidrantes: actualiza el semaforo cada 10 segundos
    monitor = MonitorHidrantes()
    monitor.start()

    # Hilo monitor de concurrencia: exporta estado a JSON cada 1 segundo
    monitor_conc = MonitorConcurrencia()
    monitor_conc.start()

    # 5 Productores para saturar rapido la cola de 2 espacios
    productores = [ProductorSensores(i) for i in range(1, 6)]

    # 3 Consumidores, cada uno con 8-12 seg de riego (lentos a proposito)
    consumidores = [ConsumidorRiego(i) for i in range(1, 4)]

    for p in productores:
        p.start()
    for c in consumidores:
        c.start()

    try:
        time.sleep(60)  # Correr 60 segundos para dar tiempo a ver el cambio dinamico
    except KeyboardInterrupt:
        pass
    finally:
        print("\nCERRANDO SIMULACION...")
        simulacion_activa = False
        print("SIMULACION COMPLETADA.")

if __name__ == "__main__":
    principal()
