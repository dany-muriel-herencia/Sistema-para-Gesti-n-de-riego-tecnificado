#!/usr/bin/env python3
import argparse
import datetime
import json
import random
import sys
import threading
import time
from typing import Any, Dict, List, Optional

DURATION_BY_ESTRES = {'alto': 45, 'medio': 30, 'bajo': 15}
SIMULATED_RIEGO_SECONDS = 0.08


def timestamp() -> str:
    return datetime.datetime.now().isoformat(timespec='milliseconds')


def safe_print(*args: Any, **kwargs: Any) -> None:
    print(*args, **kwargs, file=sys.stderr)


class HidranteState:
    def __init__(self, data: Dict[str, Any]) -> None:
        self.id = data.get('id')
        self.nombre = data.get('nombre', '')
        self.disponible = bool(data.get('disponible', False))
        self.capacidad_simultanea = int(data.get('capacidad_simultanea', 1))
        self.available_count = self.capacidad_simultanea if self.disponible else 0

    def to_dict(self) -> Dict[str, Any]:
        return {
            'id': self.id,
            'nombre': self.nombre,
            'disponible': self.disponible,
            'capacidad_simultanea': self.capacidad_simultanea,
        }


class SimuladorRiego:
    def __init__(self, data: Dict[str, Any], controlled: bool = True) -> None:
        self.parcelas = [
            p for p in data.get('parcelas', [])
            if p.get('estres_hidrico') in ('medio', 'alto')
        ]
        self.hidrantes = [HidranteState(h) for h in data.get('hidrantes', [])]
        self.eventos: List[str] = []
        self.eventos_lock = threading.Lock()
        self.turnos: List[Dict[str, Any]] = []
        self.turnos_lock = threading.Lock()
        self.assignment_lock = threading.Lock()
        self.round_robin_index = 0
        self.controlled = controlled
        self.total_capacity = sum(
            h.capacidad_simultanea for h in self.hidrantes if h.disponible
        )
        self.global_semaphore = (
            threading.Semaphore(self.total_capacity)
            if self.controlled and self.total_capacity > 0 else None
        )
        self.race_info = {'negativos': 0, 'excedidos': 0}

    def log_event(self, message: str) -> None:
        with self.eventos_lock:
            self.eventos.append(f"{timestamp()} - {message}")

    def add_turno(self, turno: Dict[str, Any]) -> None:
        with self.turnos_lock:
            self.turnos.append(turno)

    def _select_hidrante_index(self) -> Optional[int]:
        available_indices = [
            i for i, h in enumerate(self.hidrantes)
            if h.disponible and h.available_count > 0
        ]
        if not available_indices:
            return None
        return available_indices[self.round_robin_index % len(available_indices)]

    def _reserve_hidrante(self) -> Optional[HidranteState]:
        if self.controlled:
            with self.assignment_lock:
                index = self._select_hidrante_index()
                if index is None:
                    return None
                hidrante = self.hidrantes[index]
                hidrante.available_count -= 1
                self.round_robin_index = (
                    self.round_robin_index + 1
                ) % len([
                    h for h in self.hidrantes
                    if h.disponible and h.available_count >= 0
                ])
                return hidrante

        index = self._select_hidrante_index()
        if index is None:
            return None
        hidrante = self.hidrantes[index]
        time.sleep(0)
        hidrante.available_count -= 1
        if hidrante.available_count < 0:
            self.race_info['negativos'] += 1
            self.log_event(
                f"race_condicion: hidrante {hidrante.id} contador negativo {hidrante.available_count}"
            )
        self.round_robin_index = (
            self.round_robin_index + 1
        ) % max(1, len([
            h for h in self.hidrantes if h.disponible and h.available_count > -1000
        ]))
        return hidrante

    def release_hidrante(self, hidrante: HidranteState) -> None:
        if self.controlled:
            with self.assignment_lock:
                hidrante.available_count += 1
                return

        hidrante.available_count += 1
        if hidrante.available_count > hidrante.capacidad_simultanea:
            self.race_info['excedidos'] += 1
            self.log_event(
                f"race_condicion: hidrante {hidrante.id} contador excedido {hidrante.available_count}"
            )

    def obtener_hidrante(self, parcela: Dict[str, Any]) -> (Optional[HidranteState], bool):
        waited = False
        if self.controlled:
            if self.global_semaphore is None:
                return None, False
            if not self.global_semaphore.acquire(blocking=False):
                self.log_event(f"{parcela.get('id')} espera hidrante")
                waited = True
                self.global_semaphore.acquire()

        hidrante = self._reserve_hidrante()
        if hidrante is None and self.controlled and self.global_semaphore is not None:
            self.global_semaphore.release()
        return hidrante, waited

    def procesar_parcela(self, parcela: Dict[str, Any], turno_id: int) -> None:
        self.log_event(f"{parcela.get('id')} encolado")
        hidrante, waited = self.obtener_hidrante(parcela)

        if hidrante is None:
            self.log_event(f"{parcela.get('id')} bloqueado")
            self.add_turno({
                'id': turno_id,
                'parcela_id': parcela.get('id'),
                'hidrante_id': None,
                'duracion_minutos': DURATION_BY_ESTRES.get(
                    parcela.get('estres_hidrico'), 30
                ),
                'estado': 'bloqueado',
            })
            return

        self.log_event(f"{parcela.get('id')} obtiene hidrante {hidrante.id}")
        estado = 'en espera' if waited else 'en riego'
        self.add_turno({
            'id': turno_id,
            'parcela_id': parcela.get('id'),
            'hidrante_id': hidrante.id,
            'duracion_minutos': DURATION_BY_ESTRES.get(
                parcela.get('estres_hidrico'), 30
            ),
            'estado': estado,
        })
        time.sleep(SIMULATED_RIEGO_SECONDS)
        self.log_event(f"{parcela.get('id')} termina riego")
        self.release_hidrante(hidrante)
        if self.controlled and self.global_semaphore is not None:
            self.global_semaphore.release()

    def ejecutar(self) -> Dict[str, Any]:
        if not self.hidrantes:
            return {
                'turnos': [],
                'hidrantes': [],
                'eventos': ['No hay hidrantes configurados.'],
            }

        threads: List[threading.Thread] = []
        for idx, parcela in enumerate(self.parcelas, start=1):
            hilo = threading.Thread(
                target=self.procesar_parcela, args=(parcela, idx)
            )
            threads.append(hilo)
            hilo.start()

        for hilo in threads:
            hilo.join()

        resultado = {
            'turnos': self.turnos,
            'hidrantes': [hidrante.to_dict() for hidrante in self.hidrantes],
            'eventos': self.eventos,
        }

        if not self.controlled:
            resultado['eventos'].append(
                f"race_summary: negativos={self.race_info['negativos']} excedidos={self.race_info['excedidos']}"
            )

        return resultado


def ejecutar_desde_stdin(controlled: bool) -> None:
    try:
        contenido = sys.stdin.read()
        payload = json.loads(contenido)
    except json.JSONDecodeError:
        sys.exit(1)

    simulador = SimuladorRiego(payload, controlled=controlled)
    resultado = simulador.ejecutar()
    print(json.dumps(resultado, ensure_ascii=False))


def generar_datos_simulados(parcelas: int, hidrantes: int) -> Dict[str, Any]:
    datos = {'parcelas': [], 'hidrantes': []}
    for i in range(1, parcelas + 1):
        estres = random.choice(['alto', 'medio'])
        datos['parcelas'].append({
            'id': f'P-{i:02d}',
            'nombre': f'Parcela {i}',
            'cultivo': 'maiz',
            'estres_hidrico': estres,
        })

    for j in range(1, hidrantes + 1):
        datos['hidrantes'].append({
            'id': f'H-{j:02d}',
            'nombre': f'Hidrante {j}',
            'disponible': True,
            'capacidad_simultanea': max(1, (parcelas // max(1, hidrantes)) // 2 or 1),
        })

    return datos


def main() -> int:
    parser = argparse.ArgumentParser(description='Simulador concurrente de riego.')
    parser.add_argument(
        '--mode', choices=['controlled', 'sin_control'], default='controlled'
    )
    parser.add_argument(
        '--parcelas', type=int, default=0, help='Número de parcelas simuladas.'
    )
    parser.add_argument(
        '--hidrantes', type=int, default=0, help='Número de hidrantes simulados.'
    )
    args = parser.parse_args()

    if args.parcelas > 0 and args.hidrantes > 0:
        datos = generar_datos_simulados(args.parcelas, args.hidrantes)
        inicio = time.perf_counter()
        resultado = SimuladorRiego(
            datos, controlled=(args.mode == 'controlled')
        ).ejecutar()
        fin = time.perf_counter()
        print(json.dumps(resultado, ensure_ascii=False))
        safe_print(f"TIEMPO_TOTAL={fin - inicio:.4f}")
        return 0

    if not sys.stdin.isatty():
        ejecutar_desde_stdin(controlled=(args.mode == 'controlled'))
        return 0

    parser.print_help()
    return 1


if __name__ == '__main__':
    raise SystemExit(main())
