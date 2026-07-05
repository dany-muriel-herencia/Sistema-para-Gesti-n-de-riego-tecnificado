#!/usr/bin/env python3
import json
import subprocess
import sys
import time
from pathlib import Path

SCRIPT = Path(__file__).resolve().parent / 'riego_concurrente.py'
PYTHON = 'python'

TEST_CASES = [
    (2, 1),
    (2, 2),
    (2, 5),
    (5, 1),
    (5, 2),
    (5, 5),
    (10, 1),
    (10, 2),
    (10, 5),
    (30, 1),
    (30, 2),
    (30, 5),
]


def run_case(parcelas: int, hidrantes: int, mode: str) -> dict:
    command = [PYTHON, str(SCRIPT), f'--mode={mode}', f'--parcelas={parcelas}', f'--hidrantes={hidrantes}']
    inicio = time.perf_counter()
    proc = subprocess.run(command, capture_output=True, text=True)
    fin = time.perf_counter()
    salida = proc.stdout.strip()
    error = proc.stderr.strip()

    if proc.returncode != 0:
        return {'parcelas': parcelas, 'hidrantes': hidrantes, 'modo': mode, 'tiempo': fin - inicio, 'status': 'ERROR', 'detalle': error}

    race_metrics = {'negativos': '-', 'inconsistencias': '-'}
    for linea in error.splitlines():
        if linea.startswith('SIN_CONTROL negativos='):
            partes = linea.replace('SIN_CONTROL ', '').split()
            for parte in partes:
                if '=' in parte:
                    clave, valor = parte.split('=', 1)
                    race_metrics[clave] = valor

    datos = json.loads(salida)
    espera = sum(1 for e in datos.get('eventos', []) if 'espera por hidrante' in e)
    return {
        'parcelas': parcelas,
        'hidrantes': hidrantes,
        'modo': mode,
        'tiempo': fin - inicio,
        'parcelas_en_espera': espera,
        'status': 'OK',
        'negativos': race_metrics['negativos'],
        'inconsistencias': race_metrics['inconsistencias'],
    }


def main() -> int:
    print('modo\tparcelas\thidrantes\ttiempo_s\tparcelas_en_espera\tnegativos\tinconsistencias\tstatus')
    for mode in ['controlled', 'sin_control']:
        for parcelas, hidrantes in TEST_CASES:
            resultado = run_case(parcelas, hidrantes, mode)
            print(
                f"{resultado['modo']}\t{resultado['parcelas']}\t{resultado['hidrantes']}\t{resultado['tiempo']:.4f}\t{resultado['parcelas_en_espera']}\t{resultado['negativos']}\t{resultado['inconsistencias']}\t{resultado['status']}"
            )
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
