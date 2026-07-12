// ============================================
// CONSOLA DE EVENTOS - LA YARADA
// ============================================

class Consola {
    constructor(options = {}) {
        this.container = options.container || document.getElementById('consoleOutput');
        this.maxEvents = options.maxEvents || 100;
        this.autoScroll = options.autoScroll !== undefined ? options.autoScroll : true;
        this.timestamp = options.timestamp !== undefined ? options.timestamp : true;
        this.eventos = [];
        this.isPaused = false;
        
        this.levelConfig = {
            productor: { icon: '🌱', color: '#2E7D32' },
            consumidor: { icon: '⏳', color: '#E65100' },
            monitor: { icon: '🚦', color: '#7B1FA2' },
            sensor: { icon: '📊', color: '#00695C' },
            hidrante: { icon: '🚿', color: '#0D47A1' },
            alerta: { icon: '🔔', color: '#FF9800' },
            critico: { icon: '🚨', color: '#e74c3c' },
            riego: { icon: '💧', color: '#2196F3' },
            success: { icon: '✅', color: '#4CAF50' },
            warning: { icon: '⚠️', color: '#FF9800' },
            error: { icon: '❌', color: '#e74c3c' },
            sistema: { icon: '⚙️', color: '#455A64' },
            info: { icon: 'ℹ️', color: '#2196F3' }
        };
        
        this.init();
    }

    init() {
        if (!this.container) {
            console.error('❌ Contenedor de consola no encontrado');
            return;
        }
        this.injectStyles();
    }

    addEvent(message, level = 'info', data = null) {
        if (this.isPaused) return;
        
        const eventObj = {
            id: Date.now() + '_' + Math.random().toString(36).substr(2, 6),
            timestamp: new Date(),
            message: message,
            level: level,
            data: data
        };
        
        this.eventos.push(eventObj);
        
        if (this.eventos.length > this.maxEvents) {
            this.eventos.shift();
        }
        
        this.renderEvent(eventObj);
        return eventObj;
    }

    renderEvent(eventObj) {
        if (!this.container) return;
        
        const el = document.createElement('div');
        const config = this.levelConfig[eventObj.level] || this.levelConfig.info;
        const isCritico = eventObj.level === 'critico';
        const timeStr = this.timestamp ? 
            eventObj.timestamp.toLocaleTimeString('es-PE') : '';
        
        el.className = `console-log ${isCritico ? 'critico' : ''}`;
        el.style.display = 'flex';
        el.style.gap = '12px';
        el.style.padding = '2px 8px';
        el.style.borderLeft = `3px solid ${config.color}`;
        el.style.borderRadius = '3px';
        el.style.animation = 'slideIn 0.3s ease';
        
        if (isCritico) {
            el.style.background = 'rgba(231, 76, 60, 0.06)';
        }
        
        el.innerHTML = `
            ${this.timestamp ? `<span style="color:#72796e;font-size:11px;min-width:70px;opacity:0.6;">[${timeStr}]</span>` : ''}
            <span style="font-size:14px;">${config.icon}</span>
            <span style="flex:1;${isCritico ? 'color:#c0392b;font-weight:600;' : ''}">${this.escapeHtml(eventObj.message)}</span>
        `;
        
        // Hover
        el.onmouseenter = () => {
            el.style.background = 'rgba(0,0,0,0.03)';
        };
        el.onmouseleave = () => {
            el.style.background = isCritico ? 'rgba(231, 76, 60, 0.06)' : '';
        };
        
        this.container.appendChild(el);
        
        while (this.container.children.length > this.maxEvents) {
            this.container.removeChild(this.container.firstChild);
        }
        
        if (this.autoScroll) {
            this.container.scrollTop = this.container.scrollHeight;
        }
    }

    clear() {
        if (this.container) {
            this.container.innerHTML = '';
        }
        this.eventos = [];
    }

    togglePause() {
        this.isPaused = !this.isPaused;
        return this.isPaused;
    }

    getEvents() {
        return this.eventos;
    }

    getLastEvent() {
        return this.eventos[this.eventos.length - 1] || null;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    injectStyles() {
        const styleId = 'consola-styles';
        if (document.getElementById(styleId)) return;
        
        const styles = `
            @keyframes slideIn {
                from { opacity: 0; transform: translateX(-8px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .console-log {
                transition: background 0.2s ease;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = styleId;
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Consola;
}