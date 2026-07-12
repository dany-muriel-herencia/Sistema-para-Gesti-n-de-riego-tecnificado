#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script para convertir un proyecto completo en un archivo de texto plano,
mostrando la estructura de carpetas con indentación y el contenido de cada archivo.
"""

import os
import sys
from pathlib import Path

# ------------------------------------------------------------
# CONFIGURACIÓN (puedes editar estas variables)
# ------------------------------------------------------------
ARCHIVO_SALIDA = "proyecto_completo.txt"
CARPETAS_EXCLUIDAS = {".git", "__pycache__", "node_modules", ".idea", ".vscode", "build", "dist"}
# Extensiones que consideramos archivos de texto (puedes ampliarlas)
EXTENSIONES_TEXTO = {
    # Código fuente
    ".py", ".js", ".jsx", ".ts", ".tsx", ".java", ".c", ".cpp", ".h", ".hpp",
    ".cs", ".go", ".rb", ".php", ".swift", ".kt", ".rs", ".scala", ".pl", ".pm",
    ".sh", ".bash", ".zsh", ".fish", ".ps1", ".bat", ".cmd",
    # Web
    ".html", ".htm", ".css", ".scss", ".sass", ".less", ".vue", ".svelte",
    # Datos / config
    ".json", ".xml", ".yaml", ".yml", ".toml", ".ini", ".cfg", ".conf",
    ".md", ".rst", ".txt", ".tex", ".latex",
    # Otros
    ".sql", ".r", ".m", ".f", ".f90", ".jl", ".lua", ".dart",
    ".gradle", ".properties", ".env", ".gitignore", ".dockerignore",
}
# ------------------------------------------------------------

def es_archivo_texto(ruta):
    """Determina si un archivo es de texto según su extensión."""
    return ruta.suffix.lower() in EXTENSIONES_TEXTO or ruta.name in EXTENSIONES_TEXTO

def obtener_lenguaje(archivo):
    """Devuelve una etiqueta de lenguaje basada en la extensión."""
    ext = archivo.suffix.lower()
    if ext == ".py":
        return "Python"
    elif ext in (".js", ".jsx"):
        return "JavaScript"
    elif ext in (".ts", ".tsx"):
        return "TypeScript"
    elif ext in (".java"):
        return "Java"
    elif ext in (".c", ".h"):
        return "C"
    elif ext in (".cpp", ".hpp", ".cc", ".hh"):
        return "C++"
    elif ext == ".cs":
        return "C#"
    elif ext == ".go":
        return "Go"
    elif ext == ".rb":
        return "Ruby"
    elif ext == ".php":
        return "PHP"
    elif ext == ".swift":
        return "Swift"
    elif ext == ".kt":
        return "Kotlin"
    elif ext == ".rs":
        return "Rust"
    elif ext == ".html":
        return "HTML"
    elif ext == ".css":
        return "CSS"
    elif ext in (".json", ".yaml", ".yml", ".toml"):
        return "Config"
    elif ext == ".md":
        return "Markdown"
    elif ext == ".sql":
        return "SQL"
    elif ext in (".sh", ".bash", ".zsh", ".fish"):
        return "Shell"
    elif ext == ".ps1":
        return "PowerShell"
    else:
        return "Texto"

def generar_arbol(directorio_raiz):
    """Genera una representación del árbol de directorios con indentación."""
    lineas = []
    directorio_raiz = Path(directorio_raiz).resolve()
    for raiz, dirs, archivos in os.walk(directorio_raiz):
        # Filtrar carpetas excluidas
        dirs[:] = [d for d in dirs if d not in CARPETAS_EXCLUIDAS]
        nivel = Path(raiz).relative_to(directorio_raiz).parts
        indent = "  " * len(nivel)
        carpeta_actual = Path(raiz).name
        if raiz != str(directorio_raiz):
            lineas.append(f"{indent}📁 {carpeta_actual}/")
        for archivo in sorted(archivos):
            ruta_completa = Path(raiz) / archivo
            lenguaje = obtener_lenguaje(ruta_completa)
            lineas.append(f"{indent}  📄 {archivo}  ({lenguaje})")
    return lineas

def main():
    ruta_proyecto = Path.cwd()  # Toma el directorio actual
    print(f"Procesando proyecto en: {ruta_proyecto}")
    print(f"Generando árbol de directorios y contenido...")

    # Recopilar todos los archivos de texto
    archivos_procesar = []
    for raiz, dirs, archivos in os.walk(ruta_proyecto):
        dirs[:] = [d for d in dirs if d not in CARPETAS_EXCLUIDAS]
        for archivo in archivos:
            ruta_completa = Path(raiz) / archivo
            if es_archivo_texto(ruta_completa):
                archivos_procesar.append(ruta_completa)

    # Construir el árbol de directorios
    lineas_arbol = generar_arbol(ruta_proyecto)

    # Escribir archivo de salida
    try:
        with open(ARCHIVO_SALIDA, "w", encoding="utf-8") as f_out:
            f_out.write(f"# PROYECTO: {ruta_proyecto.name}\n")
            f_out.write(f"# FECHA: {__import__('datetime').datetime.now()}\n")
            f_out.write("# =========================================\n\n")
            f_out.write("# ESTRUCTURA DE CARPETAS\n")
            f_out.write("# =========================================\n")
            for linea in lineas_arbol:
                f_out.write(linea + "\n")

            f_out.write("\n\n# CONTENIDO DE ARCHIVOS\n")
            f_out.write("# =========================================\n\n")

            for ruta_archivo in sorted(archivos_procesar, key=lambda p: str(p)):
                rel_path = ruta_archivo.relative_to(ruta_proyecto)
                lenguaje = obtener_lenguaje(ruta_archivo)
                f_out.write(f"\n--- ARCHIVO: {rel_path}  ({lenguaje}) ---\n")
                try:
                    with open(ruta_archivo, "r", encoding="utf-8") as f_in:
                        contenido = f_in.read()
                    f_out.write(contenido)
                except Exception as e:
                    f_out.write(f"[ERROR AL LEER ARCHIVO: {e}]\n")
                # Asegurar que termine con un salto de línea
                if not contenido.endswith("\n"):
                    f_out.write("\n")
                f_out.write("-" * 50 + "\n")

        print(f"\n✅ Proyecto convertido exitosamente en: {ARCHIVO_SALIDA}")
        print(f"Total archivos procesados: {len(archivos_procesar)}")
    except Exception as e:
        print(f"❌ Error al escribir el archivo de salida: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()