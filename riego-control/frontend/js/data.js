// ============================================
// DATA.JS - CONEXIÓN CON BASE DE DATOS (CORREGIDO)
// ============================================

// Detectar automáticamente la URL base
function getBaseUrl() {
    const hostname = window.location.hostname;
    const port = window.location.port;
    
    // Si estamos en localhost con el servidor PHP
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
        // Si el puerto es 8000 (servidor PHP)
        if (port === '8000') {
            return `http://${hostname}:${port}/backend/api`;
        }
        // Si no hay puerto (servidor Apache/XAMPP)
        return `http://${hostname}/Sistema-para-Gesti-n-de-riego-tecnificado/riego-control/backend/api`;
    }
    return '/backend/api';
}

// Configuración
const API_URL = getBaseUrl();

// Variable global para los datos
let DATOS = {
    hidrantes: [],
    parcelas: [],
    turnos: []
};

let DB_CONECTADO = false;

// ============================================
// FUNCIÓN PARA CARGAR DATOS DESDE API
// ============================================

async function cargarDatosDesdeAPI() {
    console.log('🔄 Cargando datos desde la API...');
    console.log('📡 URL base:', API_URL);
    
    try {
        // === 1. PROBAR CONEXIÓN ===
        console.log('📡 Probando conexión...');
        const testUrl = `${API_URL}/test_api.php`;
        console.log('📡 Test URL:', testUrl);
        
        const testResponse = await fetch(testUrl);
        if (!testResponse.ok) {
            throw new Error(`Error en test: ${testResponse.status}`);
        }
        
        const testData = await testResponse.json();
        console.log('✅ Test API respuesta:', testData);
        
        if (!testData.success) {
            console.warn('⚠️ API dice success: false, usando datos simulados');
            usarDatosSimulados();
            return DATOS;
        }
        
        // === 2. CARGAR PARCELAS ===
        console.log('📡 Cargando parcelas...');
        const parcelasResponse = await fetch(`${API_URL}/parcelas.php`);
        if (!parcelasResponse.ok) {
            throw new Error(`Error al cargar parcelas: ${parcelasResponse.status}`);
        }
        const parcelasData = await parcelasResponse.json();
        console.log('📡 Parcelas recibidas:', parcelasData);
        
        // === 3. CARGAR SENSORES ===
        console.log('📡 Cargando sensores...');
        const sensoresResponse = await fetch(`${API_URL}/sensores.php`);
        if (!sensoresResponse.ok) {
            throw new Error(`Error al cargar sensores: ${sensoresResponse.status}`);
        }
        const sensoresData = await sensoresResponse.json();
        console.log('📡 Sensores recibidos:', sensoresData);
        
        // === 4. CARGAR TURNOS ===
        console.log('📡 Cargando turnos...');
        const turnosResponse = await fetch(`${API_URL}/riego.php`);
        if (!turnosResponse.ok) {
            throw new Error(`Error al cargar turnos: ${turnosResponse.status}`);
        }
        const turnosData = await turnosResponse.json();
        console.log('📡 Turnos recibidos:', turnosData);
        
        // === 5. PROCESAR DATOS ===
        // Extraer los datos correctamente
        const parcelasList = parcelasData.parcelas || parcelasData.data || [];
        const sensoresList = sensoresData.sensores || sensoresData.data || [];
        const turnosList = turnosData.turnos || turnosData.data || [];
        
        console.log(`📊 Procesando ${parcelasList.length} parcelas...`);
        
        // Procesar parcelas con sus sensores
        const parcelas = parcelasList.map(p => {
            const sensor = sensoresList.find(s => s.parcela_id === p.id);
            return {
                id: parseInt(p.id) || p.id,
                nombre: p.nombre || `Parcela ${p.id}`,
                cultivo: p.cultivo || 'Sin cultivo',
                humedad: sensor ? parseFloat(sensor.humedad) : Math.round(20 + Math.random() * 30),
                temperatura: sensor ? parseFloat(sensor.temperatura) : Math.round(25 + Math.random() * 8),
                umbral: 30,
                sensor: sensor ? sensor.id : null,
                estado: p.estado || 'activa'
            };
        });
        
        // Hidrantes (de la BD o simulados)
        let hidrantes = [];
        try {
            const hidrantesResponse = await fetch(`${API_URL}/hidrantes.php`);
            if (hidrantesResponse.ok) {
                const hidrantesData = await hidrantesResponse.json();
                hidrantes = hidrantesData.hidrantes || hidrantesData.data || [];
                hidrantes = hidrantes.map(h => ({
                    id: parseInt(h.id) || h.id,
                    nombre: h.nombre || `Hidrante ${h.id}`,
                    disponible: h.estado === 'disponible' || h.disponible === true,
                    capacidad: h.capacidad || 1
                }));
            } else {
                // Si no hay endpoint de hidrantes, usar simulados
                hidrantes = [
                    { id: 1, nombre: 'Hidrante Norte', disponible: true, capacidad: 2 },
                    { id: 2, nombre: 'Hidrante Sur', disponible: true, capacidad: 1 },
                    { id: 3, nombre: 'Hidrante Este', disponible: false, capacidad: 1 }
                ];
            }
        } catch (e) {
            hidrantes = [
                { id: 1, nombre: 'Hidrante Norte', disponible: true, capacidad: 2 },
                { id: 2, nombre: 'Hidrante Sur', disponible: true, capacidad: 1 },
                { id: 3, nombre: 'Hidrante Este', disponible: false, capacidad: 1 }
            ];
        }
        
        // Procesar turnos
        const turnos = turnosList.map(t => {
            const parcela = parcelas.find(p => p.id === t.parcela_id);
            return {
                id: parseInt(t.id) || t.id,
                parcela_id: parseInt(t.parcela_id) || t.parcela_id,
                parcela: parcela ? parcela.nombre : `Parcela ${t.parcela_id}`,
                hidrante_id: t.hidrante_id ? parseInt(t.hidrante_id) : null,
                hidrante: t.hidrante_id ? `H-${String(t.hidrante_id).padStart(2, '0')}` : null,
                estado: t.estado || 'pendiente',
                duracion: t.duracion || 30,
                inicio: t.inicio || null,
                fin: t.fin || null
            };
        });
        
        // Asignar a DATOS
        DATOS = { parcelas, hidrantes, turnos };
        window.DATOS = DATOS;
        DB_CONECTADO = true;
        window.DB_CONECTADO = true;
        
        console.log('✅ Datos cargados desde la base de datos');
        console.log(`📊 ${parcelas.length} parcelas, ${hidrantes.length} hidrantes, ${turnos.length} turnos`);
        
        // Actualizar UI
        if (typeof renderMapa === 'function') {
            renderMapa();
        }
        if (typeof renderHidrantes === 'function') {
            renderHidrantes();
        }
        if (typeof renderTurnos === 'function') {
            renderTurnos();
        }
        if (typeof actualizarMetricas === 'function') {
            actualizarMetricas();
        }
        
        // Actualizar estado de conexión en la UI
        actualizarEstadoConexion(true);
        
        return DATOS;
        
    } catch (error) {
        console.error('❌ Error al cargar datos desde la API:', error);
        console.log('📊 Usando datos simulados de respaldo...');
        usarDatosSimulados();
        actualizarEstadoConexion(false);
        return DATOS;
    }
}

// ============================================
// DATOS SIMULADOS (RESPALDO)
// ============================================

function usarDatosSimulados() {
    DB_CONECTADO = false;
    window.DB_CONECTADO = false;
    
    DATOS = {
        hidrantes: [
            { id: 1, nombre: 'Hidrante Norte', disponible: true, capacidad: 2 },
            { id: 2, nombre: 'Hidrante Sur', disponible: true, capacidad: 1 },
            { id: 3, nombre: 'Hidrante Este', disponible: false, capacidad: 1 }
        ],
        parcelas: [
            { id: 1, nombre: 'Olivos A', cultivo: 'Olivo', humedad: 18, temperatura: 29.6, umbral: 28, sensor: 1, estado: 'activa' },
            { id: 2, nombre: 'Vid B', cultivo: 'Vid', humedad: 34, temperatura: 26.8, umbral: 30, sensor: 2, estado: 'activa' },
            { id: 3, nombre: 'Aji C', cultivo: 'Aji', humedad: 22, temperatura: 31.1, umbral: 32, sensor: 3, estado: 'activa' },
            { id: 4, nombre: 'Maiz D', cultivo: 'Maiz', humedad: 27, temperatura: 28.3, umbral: 29, sensor: 4, estado: 'activa' },
            { id: 5, nombre: 'Hospicio E', cultivo: 'Papa', humedad: 15, temperatura: 33.2, umbral: 35, sensor: 5, estado: 'seca' }
        ],
        turnos: [
            { id: 1, parcela_id: 1, parcela: 'Olivos A', hidrante_id: 1, hidrante: 'H-01', estado: 'completado', duracion: 45 },
            { id: 2, parcela_id: 2, parcela: 'Vid B', hidrante_id: 1, hidrante: 'H-01', estado: 'completado', duracion: 25 },
            { id: 3, parcela_id: 3, parcela: 'Aji C', hidrante_id: 2, hidrante: 'H-02', estado: 'pendiente', duracion: 30 },
            { id: 4, parcela_id: 4, parcela: 'Maiz D', hidrante_id: null, hidrante: null, estado: 'en espera', duracion: 0 }
        ]
    };
    
    window.DATOS = DATOS;
    
    console.log('📊 Datos de respaldo cargados (simulados)');
    console.log(`📊 ${DATOS.parcelas.length} parcelas, ${DATOS.hidrantes.length} hidrantes, ${DATOS.turnos.length} turnos`);
    
    // Actualizar UI con datos simulados
    if (typeof renderMapa === 'function') {
        renderMapa();
    }
    if (typeof renderHidrantes === 'function') {
        renderHidrantes();
    }
    if (typeof renderTurnos === 'function') {
        renderTurnos();
    }
    if (typeof actualizarMetricas === 'function') {
        actualizarMetricas();
    }
    
    actualizarEstadoConexion(false);
}

// ============================================
// ACTUALIZAR ESTADO DE CONEXIÓN EN UI
// ============================================

function actualizarEstadoConexion(conectado) {
    const dbStatus = document.getElementById('dbStatus');
    if (dbStatus) {
        if (conectado) {
            dbStatus.className = 'db-status online';
            dbStatus.textContent = '✅ Conectado';
        } else {
            dbStatus.className = 'db-status offline';
            dbStatus.textContent = '🔌 Sin conexión';
        }
    }
    
    // Agregar evento si existe la función
    if (typeof agregarEvento === 'function') {
        if (conectado) {
            agregarEvento('✅ Conectado a la base de datos', 'success');
        } else {
            agregarEvento('⚠️ Sin conexión - Usando datos locales', 'warning');
        }
    }
}

// ============================================
// VERIFICAR CONEXIÓN
// ============================================

async function verificarConexion() {
    try {
        const response = await fetch(`${API_URL}/test_api.php`);
        if (response.ok) {
            const data = await response.json();
            DB_CONECTADO = data.success === true;
            window.DB_CONECTADO = DB_CONECTADO;
            return DB_CONECTADO;
        }
        return false;
    } catch (error) {
        console.error('❌ Error verificando conexión:', error);
        return false;
    }
}

// ============================================
// INICIALIZACIÓN AUTOMÁTICA
// ============================================

// Cargar datos al iniciar
console.log('📦 data.js cargado');
console.log('📡 API URL:', API_URL);

// Intentar cargar datos automáticamente
if (!window.DATOS || window.DATOS.parcelas.length === 0) {
    console.log('🔄 Cargando datos automáticamente...');
    cargarDatosDesdeAPI();
}

// Exponer funciones globalmente
window.DATOS = DATOS;
window.cargarDatosDesdeAPI = cargarDatosDesdeAPI;
window.verificarConexion = verificarConexion;
window.DB_CONECTADO = DB_CONECTADO;
window.usarDatosSimulados = usarDatosSimulados;
window.actualizarEstadoConexion = actualizarEstadoConexion;

console.log('📊 DATOS iniciales:', window.DATOS);