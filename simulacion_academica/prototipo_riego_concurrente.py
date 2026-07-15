import threading
import time
import random
import queue
import mysql.connector
from datetime import datetime

# ============================================================================
# PROTOTIPO ACADEMICO: SISTEMA DE GESTION DE RIEGO (CONCURRENCIA)
# Algoritmos: Productor-Consumidor + Monitor (queue.Queue) + Semaforo
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

# 1. MONITOR: Cola con capacidad maxima (simula el buffer compartido)
#    Para la prueba de carga extrema cambiar a 2
CAPACIDAD_MAXIMA_COLA = 2
cola_riego = queue.Queue(maxsize=CAPACIDAD_MAXIMA_COLA)

# 2. SEMAFORO: Limita hidrantes activos al mismo tiempo
MAX_HIDRANTES_SIMULTANEOS = 3
semaforo_hidrantes = threading.Semaphore(MAX_HIDRANTES_SIMULTANEOS)

simulacion_activa = True

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

                # Simular lectura de sensores fisicos
                # Para prueba extrema: forzar sequia en todas las parcelas
                humedad = 10.0      # Humedad critica forzada
                temperatura = 40.0  # Temperatura critica forzada

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
# CLASE CONSUMIDOR (Extrae de la cola y riega usando el Semaforo)
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

            # SECCION CRITICA 2: Adquirir SEMAFORO (limita hidrantes fisicos)
            semaforo_hidrantes.acquire()
            hidrantes_en_uso = MAX_HIDRANTES_SIMULTANEOS - semaforo_hidrantes._value
            print(f"[SEMAFORO] Hidrante asignado a '{parcela_nombre}'. En uso: {hidrantes_en_uso}/{MAX_HIDRANTES_SIMULTANEOS}")

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
                # Liberar SEMAFORO
                semaforo_hidrantes.release()
                print(f"[SEMAFORO] Hidrante liberado de '{parcela_nombre}'.")
                cola_riego.task_done()

        cursor.close()
        db.close()

# ===========================================================================
# FUNCION PRINCIPAL
# ===========================================================================
def principal():
    global simulacion_activa
    print("=" * 70)
    print("PRUEBA DE CARGA EXTREMA - MONITOR Y SEMAFORO")
    print(f"Cola maxima (Monitor): {CAPACIDAD_MAXIMA_COLA} | Hidrantes (Semaforo): {MAX_HIDRANTES_SIMULTANEOS}")
    print("=" * 70)

    # 5 Productores para saturar rapido la cola de 2 espacios
    productores = [ProductorSensores(i) for i in range(1, 6)]

    # 3 Consumidores, cada uno con 8-12 seg de riego (lentos a proposito)
    consumidores = [ConsumidorRiego(i) for i in range(1, 4)]

    for p in productores:
        p.start()
    for c in consumidores:
        c.start()

    try:
        time.sleep(30)  # Correr 30 segundos para la prueba extrema
    except KeyboardInterrupt:
        pass
    finally:
        print("\nCERRANDO SIMULACION...")
        simulacion_activa = False
        print("SIMULACION COMPLETADA.")

if __name__ == "__main__":
    principal()
