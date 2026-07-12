// ============================================
// DATA.JS - CONEXIÓN CON BASE DE DATOS
// ============================================

// Usar la configuración o detectar automáticamente
const API_URL = (typeof CONFIG !== 'undefined' && CONFIG.api) 
    ? CONFIG.api.baseUrl 
    : (() => {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            return `http://${window.location.hostname}:8000/backend/api`;
        }
        return '/backend/api';
    })();

// Variable global para los datos
let DATOS = {
    hidrantes: [],
    parcelas: [],
    turnos: []
};

let DB_CONECTADO = false;

// Función de utilidad para fetch con timeout
async function fetchWithTimeout(url, options = {}, timeout = 10000) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);
    
    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(options.headers || {})
            }
        });
        clearTimeout(timeoutId);
        return response;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error(`Timeout al conectar con: ${url}`);
        }
        throw error;
    }
}

async function cargarDatosDesdeAPI() {
    try {
        console.log('🔄 Cargando datos desde la API...');
        console.log('📡 URL base:', API_URL);
        
        // === PROBAR CONEXIÓN ===
        const testUrl = `${API_URL}/test_api.php`;
        console.log('📡 Probando conexión en:', testUrl);
        
        let testResponse;
        try {
            testResponse = await fetchWithTimeout(testUrl, {}, 5000);
        } catch (e) {
            console.warn('⚠️ Error en test de conexión:', e.message);
            
            // Intentar con la URL alternativa
            const altUrl = `http://localhost:8000/backend/api/test_api.php`;
            console.log('🔄 Intentando URL alternativa:', altUrl);
            
            try {
                testResponse = await fetchWithTimeout(altUrl, {}, 5000);
                if (testResponse.ok) {
                    window.API_URL_ACTUAL = 'http://localhost:8000/backend/api';
                    console.log('✅ URL alternativa funciona:', altUrl);
                } else {
                    throw new Error('La URL alternativa no responde');
                }
            } catch (e2) {
                console.error('❌ No se pudo conectar a ninguna URL');
                throw new Error('No se puede conectar al servidor. Verifica que el servidor PHP esté corriendo.');
            }
        }
        
        if (!testResponse || !testResponse.ok) {
            throw new Error(`Error en test de conexión: ${testResponse ? testResponse.status : 'sin respuesta'}`);
        }
        
        const testData = await testResponse.json();
        console.log('✅ Test API respuesta:', testData);
        
        if (!testData.success) {
            console.warn('⚠️ Test API devolvió success: false');
            DB_CONECTADO = false;
            usarDatosSimulados();
            // Asegurar que window.DATOS tenga los datos
            window.DATOS = DATOS;
            return DATOS;
        }
        
        // Actualizar URL si cambió
        const baseUrl = window.API_URL_ACTUAL || API_URL;
        console.log('📡 Usando URL:', baseUrl);
        
        // === CARGAR PARCELAS ===
        console.log('📡 Cargando parcelas...');
        const resParcelas = await fetchWithTimeout(`${baseUrl}/parcelas.php`, {}, 10000);
        if (!resParcelas.ok) {
            throw new Error(`Error al cargar parcelas: ${resParcelas.status}`);
        }
        const dataParcelas = await resParcelas.json();
        console.log('📡 Parcelas recibidas:', dataParcelas);
        
        // === CARGAR SENSORES ===
        console.log('📡 Cargando sensores...');
        const resSensores = await fetchWithTimeout(`${baseUrl}/sensores.php`, {}, 10000);
        if (!resSensores.ok) {
            throw new Error(`Error al cargar sensores: ${resSensores.status}`);
        }
        const dataSensores = await resSensores.json();
        console.log('📡 Sensores recibidos:', dataSensores);
        
        // === CARGAR TURNOS ===
        console.log('📡 Cargando turnos...');
        const resTurnos = await fetchWithTimeout(`${baseUrl}/riego.php`, {}, 10000);
        if (!resTurnos.ok) {
            throw new Error(`Error al cargar turnos: ${resTurnos.status}`);
        }
        const dataTurnos = await resTurnos.json();
        console.log('📡 Turnos recibidos:', dataTurnos);
        
        // === PROCESAR DATOS ===
        const parcelasList = dataParcelas.parcelas || dataParcelas.data || [];
        const sensoresList = dataSensores.sensores || dataSensores.data || [];
        const turnosList = dataTurnos.turnos || dataTurnos.data || [];
        
        console.log('📊 Procesando ' + parcelasList.length + ' parcelas...');
        
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
        
        // Hidrantes fijos (se pueden cargar desde BD después)
        const hidrantes = [
            { id: 1, nombre: 'Hidrante Norte', disponible: true, capacidad: 2 },
            { id: 2, nombre: 'Hidrante Sur', disponible: true, capacidad: 1 },
            { id: 3, nombre: 'Hidrante Este', disponible: false, capacidad: 1 }
        ];
        
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
        
        // Asignar a DATOS local y global
        DATOS = { parcelas, hidrantes, turnos };
        window.DATOS = DATOS;  // ← IMPORTANTE: Asignar a window.DATOS
        DB_CONECTADO = true;
        window.DB_CONECTADO = true;
        
        console.log('✅ Datos cargados desde la base de datos');
        console.log(`📊 ${parcelas.length} parcelas, ${hidrantes.length} hidrantes, ${turnos.length} turnos`);
        console.log('📡 Puerto DB: 3307');
        console.log('📊 DATOS asignado a window.DATOS:', window.DATOS);
        
        return DATOS;
        
    } catch (error) {
        console.error('❌ Error al cargar datos desde la API:', error);
        console.log('📊 Usando datos simulados de respaldo...');
        usarDatosSimulados();
        window.DATOS = DATOS;
        return DATOS;
    }
}

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
    
    window.DATOS = DATOS;  // ← IMPORTANTE: Asignar a window.DATOS
    
    console.log('📊 Datos de respaldo cargados (simulados)');
    console.log(`📊 ${DATOS.parcelas.length} parcelas, ${DATOS.hidrantes.length} hidrantes, ${DATOS.turnos.length} turnos`);
}

// Función para verificar el estado de la conexión
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

// Si DATOS no tiene datos, cargar automáticamente
if (!window.DATOS || window.DATOS.parcelas.length === 0) {
    console.log('🔄 Cargando datos automáticamente...');
    cargarDatosDesdeAPI().then(() => {
        console.log('✅ Datos iniciales cargados');
    });
}

// Exponer globalmente
window.DATOS = DATOS;
window.cargarDatosDesdeAPI = cargarDatosDesdeAPI;
window.verificarConexion = verificarConexion;
window.DB_CONECTADO = DB_CONECTADO;

console.log('📦 data.js cargado');
console.log('📡 API URL:', API_URL);
console.log('📊 DATOS iniciales:', window.DATOS);