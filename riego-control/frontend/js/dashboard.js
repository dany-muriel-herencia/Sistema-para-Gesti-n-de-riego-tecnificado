// ============================================
// DASHBOARD - CENTRO DE CONTROL LA YARADA
// ============================================

const ESTADO = {
    pausado: false,
    eventos: [],
    total: 0,
    timer: 0,
    interval: null,
    simInterval: null,
    inicializado: false,
    loadingTimeout: null
};

window.ESTADO = ESTADO;

const ICONS = {
    productor: '🌱', consumidor: '⏳', monitor: '🚦', sensor: '📊',
    hidrante: '🚿', alerta: '🔔', critico: '🚨', riego: '💧',
    success: '✅', warning: '⚠️', error: '❌', sistema: '⚙️'
};

function escapeHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

// ============================================
// FUNCIÓN PRINCIPAL PARA AGREGAR EVENTOS
// ============================================
function agregarEvento(msg, type = 'info') {
    if (ESTADO.pausado) return;
    
    const time = new Date().toLocaleTimeString('es-PE');
    const icon = ICONS[type] || '📌';
    const isCritico = type === 'critico';
    
    // === CONSOLA PRINCIPAL (consoleOutput) ===
    const output = document.getElementById('consoleOutput');
    if (output) {
        const log = document.createElement('div');
        log.className = `log ${isCritico ? 'critico' : ''}`;
        log.style.borderLeftColor = type === 'critico' ? '#e74c3c' : (type === 'alerta' ? '#f39c12' : '#95a5a6');
        log.innerHTML = `
            <span class="time">[${time}]</span>
            <span class="icon">${icon}</span>
            <span class="msg">${escapeHtml(msg)}</span>
        `;
        output.appendChild(log);
        while (output.children.length > 100) output.removeChild(output.firstChild);
        output.scrollTop = output.scrollHeight;
    }
    
    // === CONSOLA SECUNDARIA (consoleOutput2) ===
    const output2 = document.getElementById('consoleOutput2');
    if (output2) {
        const log2 = document.createElement('div');
        log2.className = `log ${isCritico ? 'critico' : ''}`;
        log2.style.borderLeftColor = type === 'critico' ? '#e74c3c' : (type === 'alerta' ? '#f39c12' : '#95a5a6');
        log2.innerHTML = `
            <span class="time">[${time}]</span>
            <span class="icon">${icon}</span>
            <span class="msg">${escapeHtml(msg)}</span>
        `;
        output2.appendChild(log2);
        while (output2.children.length > 100) output2.removeChild(output2.firstChild);
        output2.scrollTop = output2.scrollHeight;
    }
    
    // === ACTUALIZAR CONTADORES ===
    ESTADO.eventos.push({ time, msg, type });
    ESTADO.total++;
    
    const counter = document.getElementById('eventCounter');
    if (counter) counter.textContent = `${ESTADO.total} eventos`;
    
    const counter2 = document.getElementById('eventCounter2');
    if (counter2) counter2.textContent = `${ESTADO.total} eventos`;
    
    const badge = document.getElementById('consolaBadge');
    if (badge) badge.textContent = ESTADO.total;
}

window.agregarEvento = agregarEvento;

// ============================================
// EVENTOS SIMULADOS
// ============================================

const EVENTOS_CRITICOS = [
    '🚨 ALERTA CRÍTICA! Hospicio E - Humedad 15% - ¡RIEGO URGENTE!',
    '🔥 Estrés extremo en Sector A - 33.2°C',
    '⚠️ Olivos A en estado crítico - Humedad <20%',
    '🚨 ¡EMERGENCIA! Riego necesario en Sector C'
];

const EVENTOS_NORMALES = [
    '🌱 Productor: Olivos A encolada - Estrés ALTO',
    '⏳ Consumidor: Procesando cola de riego...',
    '🚦 Monitor: Verificando hidrantes disponibles',
    '📊 Sensor S-01: Olivos A - Humedad 18%',
    '🚿 Hidrante Norte: Disponible',
    '🔔 Alerta: Estrés alto en Aji C',
    '💧 Iniciando riego en Olivos A',
    '✅ Riego completado en Vid B',
    '⚠️ Temperatura elevada en Hospicio E',
    '⚙️ Sistema: Actualizando datos'
];

function generarEvento() {
    let msg, type;
    
    if (Math.random() < 0.25) {
        msg = EVENTOS_CRITICOS[Math.floor(Math.random() * EVENTOS_CRITICOS.length)];
        type = 'critico';
        if (window.DATOS && window.DATOS.parcelas && window.DATOS.parcelas.length > 0) {
            const p = window.DATOS.parcelas[Math.floor(Math.random() * window.DATOS.parcelas.length)];
            p.humedad = Math.max(10, p.humedad - Math.random() * 8);
            p.humedad = Math.round(p.humedad * 10) / 10;
            if (typeof renderMapa === 'function') renderMapa();
            if (typeof actualizarMetricas === 'function') actualizarMetricas();
        }
    } else {
        const e = EVENTOS_NORMALES[Math.floor(Math.random() * EVENTOS_NORMALES.length)];
        msg = e;
        type = e.includes('Productor') ? 'productor' :
               e.includes('Consumidor') ? 'consumidor' :
               e.includes('Monitor') ? 'monitor' :
               e.includes('Sensor') ? 'sensor' :
               e.includes('Hidrante') ? 'hidrante' :
               e.includes('Alerta') ? 'alerta' :
               e.includes('Iniciando') ? 'riego' :
               e.includes('completado') ? 'success' : 'sistema';
    }
    agregarEvento(msg, type);
}

function cambiarHumedad() {
    if (!window.DATOS || !window.DATOS.parcelas || window.DATOS.parcelas.length === 0) {
        agregarEvento('❌ No hay parcelas para cambiar humedad', 'error');
        return;
    }
    const p = window.DATOS.parcelas[Math.floor(Math.random() * window.DATOS.parcelas.length)];
    const cambio = (Math.random() - 0.5) * 16;
    p.humedad = Math.max(10, Math.min(50, p.humedad + cambio));
    p.humedad = Math.round(p.humedad * 10) / 10;
    const e = typeof determinarEstado === 'function' ? determinarEstado(p.humedad, p.temperatura) : 'optimo';
    const emoji = e === 'critico' ? '🚨' : (e === 'alerta' ? '⚡' : '💧');
    agregarEvento(`${emoji} ${p.nombre}: ${p.humedad}% - ${e.toUpperCase()}`, e);
    if (typeof renderMapa === 'function') renderMapa();
    if (typeof actualizarMetricas === 'function') actualizarMetricas();
}

function toggleHidrante() {
    if (!window.DATOS || !window.DATOS.hidrantes || window.DATOS.hidrantes.length === 0) {
        agregarEvento('❌ No hay hidrantes para cambiar', 'error');
        return;
    }
    const h = window.DATOS.hidrantes[Math.floor(Math.random() * window.DATOS.hidrantes.length)];
    h.disponible = !h.disponible;
    const estado = h.disponible ? 'ACTIVADO' : 'DETENIDO';
    agregarEvento(`🚿 ${h.nombre}: ${estado}`, 'hidrante');
    if (typeof renderHidrantes === 'function') renderHidrantes();
    if (typeof actualizarMetricas === 'function') actualizarMetricas();
}

function toggleHidranteFromCard(id) {
    if (!window.DATOS || !window.DATOS.hidrantes) return;
    const h = window.DATOS.hidrantes.find(x => x.id === id);
    if (h) {
        h.disponible = !h.disponible;
        const estado = h.disponible ? 'ACTIVADO' : 'DETENIDO';
        agregarEvento(`🚿 ${h.nombre}: ${estado}`, 'hidrante');
        if (document.getElementById('page-hidrantes')?.style.display !== 'none') {
            if (typeof renderHidrantes === 'function') renderHidrantes();
        }
        if (typeof actualizarMetricas === 'function') actualizarMetricas();
    }
}

function agregarParcela() {
    if (!window.DATOS) return;
    const nombres = ['Santa Rosa', 'Los Olivos', 'El Porvenir', 'San Isidro', 'Villa Hermosa'];
    const cultivos = ['Palta', 'Mango', 'Limón', 'Naranja', 'Fresa'];
    const newId = window.DATOS.parcelas.length > 0 ? Math.max(...window.DATOS.parcelas.map(p => p.id)) + 1 : 1;
    const nom = nombres[Math.floor(Math.random() * nombres.length)];
    const cul = cultivos[Math.floor(Math.random() * cultivos.length)];
    const hum = 12 + Math.random() * 38;
    const temp = 24 + Math.random() * 10;
    window.DATOS.parcelas.push({
        id: newId,
        nombre: `${nom} ${String.fromCharCode(65 + window.DATOS.parcelas.length)}`,
        cultivo: cul,
        humedad: Math.round(hum * 10) / 10,
        temperatura: Math.round(temp * 10) / 10,
        umbral: Math.round(25 + Math.random() * 15),
        sensor: null
    });
    const e = typeof determinarEstado === 'function' ? determinarEstado(hum, temp) : 'optimo';
    agregarEvento(`${e === 'critico' ? '🚨' : '🌱'} Nueva: ${nom} (${cul}) - ${e.toUpperCase()}`, e);
    if (typeof renderMapa === 'function') renderMapa();
    if (typeof actualizarMetricas === 'function') actualizarMetricas();
}

function resetearDatos() {
    if (!window.DATOS) return;
    if (confirm('¿Resetear datos?')) {
        window.DATOS.parcelas = [
            { id: 1, nombre: 'Olivos A', cultivo: 'Olivo', humedad: 18, temperatura: 29.6, umbral: 28, sensor: 1 },
            { id: 2, nombre: 'Vid B', cultivo: 'Vid', humedad: 34, temperatura: 26.8, umbral: 30, sensor: 2 },
            { id: 3, nombre: 'Aji C', cultivo: 'Aji', humedad: 22, temperatura: 31.1, umbral: 32, sensor: 3 },
            { id: 4, nombre: 'Maiz D', cultivo: 'Maiz', humedad: 27, temperatura: 28.3, umbral: 29, sensor: 4 },
            { id: 5, nombre: 'Hospicio E', cultivo: 'Papa', humedad: 15, temperatura: 33.2, umbral: 35, sensor: 5 }
        ];
        window.DATOS.hidrantes = [
            { id: 1, nombre: 'Hidrante Norte', disponible: true },
            { id: 2, nombre: 'Hidrante Sur', disponible: true },
            { id: 3, nombre: 'Hidrante Este', disponible: false }
        ];
        window.DATOS.turnos = [
            { id: 1, parcela_id: 1, parcela: 'Olivos A', hidrante_id: 1, hidrante: 'H-01', estado: 'completado', duracion: 45 },
            { id: 2, parcela_id: 2, parcela: 'Vid B', hidrante_id: 1, hidrante: 'H-01', estado: 'completado', duracion: 25 },
            { id: 3, parcela_id: 3, parcela: 'Aji C', hidrante_id: 2, hidrante: 'H-02', estado: 'pendiente', duracion: 30 },
            { id: 4, parcela_id: 4, parcela: 'Maiz D', hidrante_id: null, hidrante: null, estado: 'en espera', duracion: 0 }
        ];
        agregarEvento('🔄 Datos reseteados', 'sistema');
        if (typeof renderMapa === 'function') renderMapa();
        if (typeof renderHidrantes === 'function') renderHidrantes();
        if (typeof renderTurnos === 'function') renderTurnos();
        if (typeof actualizarMetricas === 'function') actualizarMetricas();
    }
}

function limpiarConsola() {
    const output = document.getElementById('consoleOutput');
    if (output) output.innerHTML = '';
    const output2 = document.getElementById('consoleOutput2');
    if (output2) output2.innerHTML = '';
    ESTADO.eventos = [];
    ESTADO.total = 0;
    const counter = document.getElementById('eventCounter');
    if (counter) counter.textContent = '0 eventos';
    const counter2 = document.getElementById('eventCounter2');
    if (counter2) counter2.textContent = '0 eventos';
    const badge = document.getElementById('consolaBadge');
    if (badge) badge.textContent = '0';
    agregarEvento('🧹 Consola limpiada', 'sistema');
}

function togglePausa() {
    ESTADO.pausado = !ESTADO.pausado;
    const btn = document.getElementById('pauseBtn');
    if (btn) btn.innerHTML = ESTADO.pausado ? '▶️ Reanudar' : '⏸️ Pausar';
    const btn2 = document.getElementById('pauseBtn2');
    if (btn2) btn2.innerHTML = ESTADO.pausado ? '▶️ Reanudar' : '⏸️ Pausar';
    agregarEvento(ESTADO.pausado ? '⏸️ Pausado' : '▶️ Reanudado', 'sistema');
}

function seleccionar(id) {
    if (!window.DATOS || !window.DATOS.parcelas) return;
    const p = window.DATOS.parcelas.find(x => x.id === id);
    if (p) {
        const e = typeof determinarEstado === 'function' ? determinarEstado(p.humedad, p.temperatura) : 'optimo';
        const n = typeof getNivel === 'function' ? getNivel(p.humedad, p.temperatura) : { emoji: '📌', label: 'INFO' };
        agregarEvento(`📍 ${p.nombre}: ${p.humedad}% - ${n.emoji} ${n.label}`, e);
    }
}

function recargarDatos() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) loading.classList.remove('hidden');
    agregarEvento('🔄 Recargando datos...', 'sistema');
    if (typeof cargarDatosDesdeAPI === 'function') {
        cargarDatosDesdeAPI().then(() => {
            if (typeof renderMapa === 'function') renderMapa();
            if (typeof renderHidrantes === 'function') renderHidrantes();
            if (typeof renderTurnos === 'function') renderTurnos();
            if (typeof actualizarMetricas === 'function') actualizarMetricas();
            agregarEvento('✅ Datos recargados', 'success');
            if (loading) loading.classList.add('hidden');
        }).catch(() => {
            if (loading) loading.classList.add('hidden');
        });
    } else {
        if (loading) loading.classList.add('hidden');
    }
}

function iniciarTimer() {
    let seg = 0;
    setInterval(() => {
        seg++;
        const m = String(Math.floor(seg / 60)).padStart(2, '0');
        const s = String(seg % 60).padStart(2, '0');
        const timerDisplay = document.getElementById('timerDisplay');
        if (timerDisplay) timerDisplay.textContent = `${m}:${s}`;
        const reloj = document.getElementById('reloj');
        if (reloj) reloj.textContent = new Date().toLocaleTimeString('es-ES', { hour12: false });
    }, 1000);
}

function iniciarSimulacion() {
    ESTADO.simInterval = setInterval(() => {
        if (!ESTADO.pausado) {
            if (Math.random() < 0.4) generarEvento();
            if (Math.random() < 0.2) cambiarHumedad();
        }
        const updateTime = document.getElementById('updateTime');
        if (updateTime) {
            updateTime.textContent = `Actualizado: ${new Date().toLocaleTimeString('es-PE')}`;
        }
    }, 5000);
}

// ============================================
// OCULTAR LOADING MANUALMENTE (FALLO DE SEGURIDAD)
// ============================================
function ocultarLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.classList.add('hidden');
        console.log('✅ Loading ocultado (manual)');
    }
}

// ============================================
// INICIALIZACIÓN PRINCIPAL
// ============================================

// ============================================
// INICIALIZACIÓN PRINCIPAL
// ============================================

async function init() {
    console.log('🌾 Iniciando Dashboard...');
    
    if (ESTADO.inicializado) {
        console.log('⚠️ Dashboard ya inicializado');
        // Forzar ocultar loading por si acaso
        ocultarLoading();
        return;
    }
    
    // === FORZAR OCULTAR LOADING DESPUÉS DE 3 SEGUNDOS (MÁXIMO) ===
    // Esto garantiza que el loading se oculte aunque algo falle
    setTimeout(() => {
        ocultarLoading();
        console.log('⏰ Loading ocultado por timeout de seguridad');
    }, 3000);
    
    try {
        // Cargar datos desde la API
        if (typeof cargarDatosDesdeAPI === 'function') {
            console.log('📡 Cargando datos desde API...');
            await cargarDatosDesdeAPI();
            console.log('✅ Datos cargados correctamente');
        } else {
            console.warn('⚠️ cargarDatosDesdeAPI no está definida');
            // Usar datos existentes en window.DATOS
            if (!window.DATOS || !window.DATOS.parcelas || window.DATOS.parcelas.length === 0) {
                console.warn('⚠️ No hay datos, usando datos de respaldo');
                window.DATOS = {
                    parcelas: [
                        { id: 1, nombre: 'Olivos A', cultivo: 'Olivo', humedad: 18, temperatura: 29.6, umbral: 28 },
                        { id: 2, nombre: 'Vid B', cultivo: 'Vid', humedad: 34, temperatura: 26.8, umbral: 30 },
                        { id: 3, nombre: 'Aji C', cultivo: 'Aji', humedad: 22, temperatura: 31.1, umbral: 32 },
                        { id: 4, nombre: 'Maiz D', cultivo: 'Maiz', humedad: 27, temperatura: 28.3, umbral: 29 }
                    ],
                    hidrantes: [
                        { id: 1, nombre: 'Hidrante Norte', disponible: true },
                        { id: 2, nombre: 'Hidrante Sur', disponible: true },
                        { id: 3, nombre: 'Hidrante Este', disponible: false }
                    ],
                    turnos: [
                        { id: 1, parcela_id: 1, parcela: 'Olivos A', hidrante_id: 1, hidrante: 'H-01', estado: 'completado', duracion: 45 },
                        { id: 2, parcela_id: 2, parcela: 'Vid B', hidrante_id: 1, hidrante: 'H-01', estado: 'completado', duracion: 25 },
                        { id: 3, parcela_id: 3, parcela: 'Aji C', hidrante_id: 2, hidrante: 'H-02', estado: 'pendiente', duracion: 30 }
                    ]
                };
                window.DB_CONECTADO = false;
            }
        }
    } catch (error) {
        console.error('❌ Error al cargar datos:', error);
        agregarEvento('❌ Error al cargar datos: ' + error.message, 'error');
    }
    
    // === OCULTAR LOADING INMEDIATAMENTE ===
    ocultarLoading();
    
    console.log('📊 DATOS CARGADOS:', window.DATOS);
    console.log('📊 Parcelas:', window.DATOS?.parcelas?.length || 0);
    console.log('🚿 Hidrantes:', window.DATOS?.hidrantes?.length || 0);
    console.log('📋 Turnos:', window.DATOS?.turnos?.length || 0);
    
    // Esperar un momento para que el DOM esté listo
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Renderizar componentes
    if (typeof renderMapa === 'function') {
        try { 
            renderMapa(); 
            console.log('✅ Mapa renderizado'); 
        } catch(e) { 
            console.error('❌ Error en renderMapa:', e); 
        }
    } else {
        console.warn('⚠️ renderMapa no está definida');
    }
    
    if (typeof renderHidrantes === 'function') {
        try { 
            renderHidrantes(); 
            console.log('✅ Hidrantes renderizados'); 
        } catch(e) { 
            console.error('❌ Error en renderHidrantes:', e); 
        }
    } else {
        console.warn('⚠️ renderHidrantes no está definida');
    }
    
    if (typeof renderTurnos === 'function') {
        try { 
            renderTurnos(); 
            console.log('✅ Turnos renderizados'); 
        } catch(e) { 
            console.error('❌ Error en renderTurnos:', e); 
        }
    } else {
        console.warn('⚠️ renderTurnos no está definida');
    }
    
    if (typeof actualizarMetricas === 'function') {
        try { 
            actualizarMetricas(); 
            console.log('✅ Métricas actualizadas'); 
        } catch(e) { 
            console.error('❌ Error en actualizarMetricas:', e); 
        }
    } else {
        console.warn('⚠️ actualizarMetricas no está definida');
    }
    
    // Eventos de bienvenida
    agregarEvento('🚀 HydroControl - La Yarada activo', 'sistema');
    agregarEvento('🌱 ' + (window.DATOS?.parcelas?.length || 0) + ' parcelas en monitoreo', 'sensor');
    agregarEvento('🚿 ' + (window.DATOS?.hidrantes?.filter(h => h.disponible).length || 0) + ' hidrantes disponibles', 'hidrante');
    
    // Estado de conexión
    const dbStatus = document.getElementById('dbStatus');
    if (window.DB_CONECTADO) {
        agregarEvento('✅ Conectado a la base de datos', 'success');
        if (dbStatus) {
            dbStatus.className = 'db-status online';
            dbStatus.textContent = '✅ Conectado';
        }
    } else {
        agregarEvento('⚠️ Sin conexión - Usando datos locales', 'warning');
        if (dbStatus) {
            dbStatus.className = 'db-status offline';
            dbStatus.textContent = '🔌 Sin conexión';
        }
    }
    
    // Iniciar temporizadores
    iniciarTimer();
    iniciarSimulacion();
    
    ESTADO.inicializado = true;
    
    console.log('✅ Dashboard listo!');
    console.log('📊 Estado final:', {
        parcelas: window.DATOS?.parcelas?.length || 0,
        hidrantes: window.DATOS?.hidrantes?.length || 0,
        turnos: window.DATOS?.turnos?.length || 0,
        conectado: window.DB_CONECTADO || false
    });
}

// ============================================
// OCULTAR LOADING - VERSIÓN MEJORADA
// ============================================

function ocultarLoading() {
    console.log('🔄 Intentando ocultar loading...');
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.classList.add('hidden');
        loading.style.opacity = '0';
        loading.style.pointerEvents = 'none';
        console.log('✅ Loading ocultado correctamente');
    } else {
        console.warn('⚠️ Loading overlay no encontrado en el DOM');
        // Intentar buscar por clase
        const loadingAlt = document.querySelector('.loading-overlay');
        if (loadingAlt) {
            loadingAlt.classList.add('hidden');
            loadingAlt.style.opacity = '0';
            loadingAlt.style.pointerEvents = 'none';
            console.log('✅ Loading ocultado por clase');
        }
    }
}
// ============================================
// EXPONER FUNCIONES GLOBALES
// ============================================

window.generarEvento = generarEvento;
window.cambiarHumedad = cambiarHumedad;
window.toggleHidrante = toggleHidrante;
window.toggleHidranteFromCard = toggleHidranteFromCard;
window.agregarParcela = agregarParcela;
window.resetearDatos = resetearDatos;
window.seleccionar = seleccionar;
window.limpiarConsola = limpiarConsola;
window.togglePausa = togglePausa;
window.recargarDatos = recargarDatos;
window.init = init;
window.agregarEvento = agregarEvento;
window.ocultarLoading = ocultarLoading;

console.log('📦 dashboard.js cargado correctamente');

// ============================================
// INICIAR CUANDO EL DOM ESTÉ LISTO
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM cargado, iniciando init()...');
    init();
});