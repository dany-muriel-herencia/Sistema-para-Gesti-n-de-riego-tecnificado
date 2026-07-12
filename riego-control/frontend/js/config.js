// ============================================
// CONFIGURACIÓN - LA YARADA
// ============================================

function getBaseUrl() {
    if (typeof window !== 'undefined') {
        const hostname = window.location.hostname;
        const port = window.location.port;
        
        // Si estamos en localhost
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            // El servidor PHP normalmente corre en el puerto 8000
            return `http://${hostname}:8000/backend/api`;
        }
        return `/backend/api`;
    }
    return 'http://localhost:8000/backend/api';
}

// ===== CONFIGURACIÓN PRINCIPAL =====
const CONFIG = {
    api: {
        // URL base para las API
        baseUrl: getBaseUrl(),
        endpoints: {
            parcelas: '/parcelas.php',
            sensores: '/sensores.php',
            riego: '/riego.php',
            test: '/test_api.php'
        }
    },
    // Configuración de la base de datos (solo informativo)
    database: {
        host: '127.0.0.1',
        port: 3307,  // ← Puerto 3307
        name: 'sistema_riego'
    },
    websocket: {
        url: 'ws://localhost:8080',
        reconnectDelay: 3000,
        maxReconnectAttempts: 10
    },
    simulacion: {
        intervaloEventos: 5000,
        probabilidadEvento: 0.4,
        probabilidadHumedad: 0.2
    },
    estres: {
        critico: { humedad: 22, temperatura: 28 },
        alerta: { humedad: 32, temperatura: 30 }
    },
    debug: true
};

// Función para obtener URL de API
function getApiUrl(endpoint) {
    return CONFIG.api.baseUrl + CONFIG.api.endpoints[endpoint];
}

// Exponer globalmente
window.CONFIG = CONFIG;
window.getApiUrl = getApiUrl;

console.log('📡 API URL:', CONFIG.api.baseUrl);
console.log('📡 Puerto DB:', CONFIG.database.port);
console.log('📡 Hostname:', window.location.hostname);

// Exportar para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CONFIG, getApiUrl };
}