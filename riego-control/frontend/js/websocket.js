// ============================================
// WEBSOCKET CLIENT - LA YARADA
// ============================================

class WebSocketClient {
    constructor(options = {}) {
        this.url = options.url || 'ws://localhost:8080';
        this.reconnectDelay = options.reconnectDelay || 3000;
        this.maxReconnectAttempts = options.maxReconnectAttempts || 10;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.ws = null;
        this.eventHandlers = {
            'sensor_data': [],
            'riego_event': [],
            'monitor_event': [],
            'hidrante_update': [],
            'parcela_update': [],
            'system_log': [],
            'error': [],
            'open': [],
            'close': []
        };
        this.pendingMessages = [];
        this.heartbeatInterval = null;
        
        this.connect();
    }

    connect() {
        try {
            console.log(`🔗 Conectando a WebSocket: ${this.url}`);
            
            this.ws = new WebSocket(this.url);
            
            this.ws.onopen = (event) => {
                this.isConnected = true;
                this.reconnectAttempts = 0;
                console.log('✅ WebSocket conectado');
                
                this.startHeartbeat();
                this.flushPendingMessages();
                this.sendHandshake();
                
                this.triggerEvent('open', event);
                this.triggerEvent('system_log', {
                    type: 'connection',
                    message: 'Sistema conectado al servidor WebSocket',
                    timestamp: new Date().toISOString()
                });
                
                if (window.agregarEvento) {
                    window.agregarEvento('🌐 Conectado al servidor WebSocket', 'success');
                }
            };

            this.ws.onmessage = (event) => {
                this.handleMessage(event.data);
            };

            this.ws.onerror = (error) => {
                console.error('❌ Error WebSocket:', error);
                this.triggerEvent('error', error);
                
                if (window.agregarEvento) {
                    window.agregarEvento('❌ Error en conexión WebSocket', 'error');
                }
            };

            this.ws.onclose = (event) => {
                this.isConnected = false;
                this.stopHeartbeat();
                console.warn(`🔌 WebSocket desconectado: ${event.code}`);
                
                this.triggerEvent('close', event);
                
                if (window.agregarEvento) {
                    window.agregarEvento(`🔌 Desconectado: ${event.reason || 'Sin motivo'}`, 'warning');
                }
                
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    const delay = this.reconnectDelay * Math.min(this.reconnectAttempts, 5);
                    console.log(`🔄 Reintentando (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
                    
                    if (window.agregarEvento) {
                        window.agregarEvento(`🔄 Reintentando (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`, 'info');
                    }
                    
                    setTimeout(() => this.connect(), delay);
                } else {
                    console.error('❌ Máximo de reintentos alcanzado');
                    if (window.agregarEvento) {
                        window.agregarEvento('❌ Máximo de reintentos alcanzado', 'error');
                    }
                }
            };
        } catch (error) {
            console.error('❌ Error al crear WebSocket:', error);
            setTimeout(() => this.connect(), this.reconnectDelay);
        }
    }

    startHeartbeat() {
        this.stopHeartbeat();
        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
                this.send({
                    type: 'ping',
                    timestamp: new Date().toISOString()
                });
            }
        }, 30000);
    }

    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }

    sendHandshake() {
        this.send({
            type: 'handshake',
            client: 'dashboard',
            version: '1.0',
            timestamp: new Date().toISOString(),
            suscripciones: ['sensor_data', 'riego_event', 'monitor_event', 'hidrante_update', 'parcela_update']
        });
    }

    handleMessage(data) {
        try {
            const message = typeof data === 'string' ? JSON.parse(data) : data;
            
            if (!message.type) {
                console.warn('⚠️ Mensaje sin tipo:', message);
                return;
            }

            switch (message.type) {
                case 'sensor_data':
                    this.handleSensorData(message.payload);
                    break;
                case 'riego_event':
                    this.handleRiegoEvent(message.payload);
                    break;
                case 'monitor_event':
                    this.handleMonitorEvent(message.payload);
                    break;
                case 'hidrante_update':
                    this.handleHidranteUpdate(message.payload);
                    break;
                case 'parcela_update':
                    this.handleParcelaUpdate(message.payload);
                    break;
                case 'pong':
                    break;
                case 'system_log':
                    this.handleSystemLog(message.payload);
                    break;
                default:
                    console.log('📨 Mensaje:', message.type);
            }

        } catch (error) {
            console.error('❌ Error al procesar mensaje:', error);
        }
    }

    handleSensorData(payload) {
        if (!payload) return;
        const sensors = Array.isArray(payload) ? payload : [payload];
        
        sensors.forEach(sensor => {
            if (window.agregarEvento) {
                window.agregarEvento(
                    `📊 Sensor: ${sensor.parcela || 'Parcela'} - Humedad ${sensor.humedad}% - Temp ${sensor.temperatura}°C`,
                    'sensor'
                );
            }
            
            // Actualizar UI
            if (window.DATOS) {
                const parcela = window.DATOS.parcelas.find(p => 
                    p.id === sensor.parcela_id || p.id === `P-${String(sensor.parcela_id).padStart(2, '0')}`
                );
                if (parcela) {
                    parcela.humedad = sensor.humedad;
                    parcela.temperatura = sensor.temperatura;
                    if (typeof renderMapa === 'function') renderMapa();
                    if (typeof actualizarMetricas === 'function') actualizarMetricas();
                }
            }
        });

        this.triggerEvent('sensor_data', sensors);
    }

    handleRiegoEvent(payload) {
        if (!payload) return;
        const eventos = Array.isArray(payload) ? payload : [payload];
        
        eventos.forEach(evento => {
            if (window.agregarEvento) {
                window.agregarEvento(
                    `🚿 ${evento.tipo || 'Riego'}: ${evento.parcela || 'Parcela'} - ${evento.estado || 'Procesando'}`,
                    'riego'
                );
            }
        });

        this.triggerEvent('riego_event', payload);
    }

    handleMonitorEvent(payload) {
        if (!payload) return;
        
        if (window.agregarEvento) {
            window.agregarEvento(`🚦 Monitor: ${payload.mensaje || payload.message || 'Evento de monitor'}`, 'monitor');
        }

        this.triggerEvent('monitor_event', payload);
    }

    handleHidranteUpdate(payload) {
        if (!payload) return;
        const hidrantes = Array.isArray(payload) ? payload : [payload];
        
        hidrantes.forEach(hidrante => {
            if (window.agregarEvento) {
                const estado = hidrante.disponible ? 'disponible' : 'ocupado';
                window.agregarEvento(`🚿 ${hidrante.nombre || hidrante.id}: ${estado.toUpperCase()}`, 'hidrante');
            }
        });

        this.triggerEvent('hidrante_update', payload);
    }

    handleParcelaUpdate(payload) {
        if (!payload) return;
        const parcelas = Array.isArray(payload) ? payload : [payload];
        
        parcelas.forEach(parcela => {
            if (window.agregarEvento) {
                window.agregarEvento(
                    `🌱 ${parcela.nombre || parcela.id}: Humedad ${parcela.humedad}%`,
                    'sistema'
                );
            }
        });

        this.triggerEvent('parcela_update', payload);
    }

    handleSystemLog(payload) {
        if (!payload) return;
        
        if (window.agregarEvento) {
            window.agregarEvento(`📋 Sistema: ${payload.message}`, 'sistema');
        }

        this.triggerEvent('system_log', payload);
    }

    send(message) {
        if (this.isConnected && this.ws && this.ws.readyState === WebSocket.OPEN) {
            const data = typeof message === 'string' ? message : JSON.stringify(message);
            this.ws.send(data);
            return true;
        } else {
            this.pendingMessages.push(message);
            return false;
        }
    }

    flushPendingMessages() {
        while (this.pendingMessages.length > 0) {
            const message = this.pendingMessages.shift();
            this.send(message);
        }
    }

    on(eventType, handler) {
        if (this.eventHandlers[eventType]) {
            this.eventHandlers[eventType].push(handler);
        } else {
            this.eventHandlers[eventType] = [handler];
        }
        return this;
    }

    off(eventType, handler) {
        if (this.eventHandlers[eventType]) {
            this.eventHandlers[eventType] = this.eventHandlers[eventType].filter(h => h !== handler);
        }
        return this;
    }

    triggerEvent(eventType, data) {
        if (this.eventHandlers[eventType]) {
            this.eventHandlers[eventType].forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    console.error(`❌ Error en handler de ${eventType}:`, error);
                }
            });
        }
    }

    disconnect() {
        this.stopHeartbeat();
        if (this.ws) {
            this.ws.close();
        }
        this.isConnected = false;
        console.log('🔌 WebSocket desconectado manualmente');
    }

    reconnect() {
        this.disconnect();
        this.reconnectAttempts = 0;
        setTimeout(() => this.connect(), 1000);
    }

    getConnectionState() {
        return {
            isConnected: this.isConnected,
            wsState: this.ws ? this.ws.readyState : 'closed',
            reconnectAttempts: this.reconnectAttempts,
            maxReconnectAttempts: this.maxReconnectAttempts,
            pendingMessages: this.pendingMessages.length
        };
    }
}

// Inicializar WebSocket
document.addEventListener('DOMContentLoaded', function() {
    if (window.wsClient) {
        console.log('⚠️ WebSocket client ya existe');
        return;
    }

    const wsClient = new WebSocketClient({
        url: 'ws://localhost:8080',
        reconnectDelay: 3000,
        maxReconnectAttempts: 10
    });

    window.wsClient = wsClient;

    wsClient.on('sensor_data', (data) => {
        console.log('📊 Datos de sensor recibidos:', data);
    });

    wsClient.on('riego_event', (data) => {
        console.log('🚿 Evento de riego:', data);
    });

    wsClient.on('monitor_event', (data) => {
        console.log('🚦 Evento de monitor:', data);
    });

    wsClient.on('hidrante_update', (data) => {
        console.log('🚿 Actualización de hidrante:', data);
    });

    wsClient.on('parcela_update', (data) => {
        console.log('🌱 Actualización de parcela:', data);
    });

    wsClient.on('open', () => {
        console.log('✅ WebSocket abierto');
    });

    wsClient.on('close', () => {
        console.log('🔌 WebSocket cerrado');
    });

    console.log('🌐 WebSocket Client inicializado');
});

// Exportar para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketClient;
}