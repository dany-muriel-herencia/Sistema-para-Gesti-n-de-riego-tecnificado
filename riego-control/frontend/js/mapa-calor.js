// ============================================
// MAPA DE CALOR - LA YARADA
// ============================================

class MapaCalor {
    constructor(options = {}) {
        this.container = options.container || document.getElementById('heatmapContainer');
        this.parcelas = options.parcelas || [];
        this.onParcelaClick = options.onParcelaClick || null;
        this.columnas = options.columnas || 4;
        
        this.colores = {
            critico: '#e74c3c',
            alerta: '#f39c12',
            optimo: '#27ae60',
            sin_datos: '#95a5a6'
        };
        
        this.estados = {
            critico: { label: '🚨 Crítico', icon: '🔴' },
            alerta: { label: '⚡ Alerta', icon: '🟡' },
            optimo: { label: '✅ Óptimo', icon: '🟢' },
            sin_datos: { label: '⚪ Sin datos', icon: '⚪' }
        };
        
        this.render();
    }

    render() {
        if (!this.container) {
            console.error('❌ Contenedor no encontrado');
            return;
        }

        this.container.innerHTML = '';
        
        const wrapper = document.createElement('div');
        wrapper.className = 'heatmap-wrapper';
        
        // Título y leyenda
        const header = document.createElement('div');
        header.className = 'heatmap-header';
        header.innerHTML = `
            <div class="heatmap-legend">
                <span><span class="legend-color" style="background:${this.colores.optimo}"></span> Óptimo</span>
                <span><span class="legend-color" style="background:${this.colores.alerta}"></span> Alerta</span>
                <span><span class="legend-color" style="background:${this.colores.critico}"></span> Crítico</span>
            </div>
        `;
        wrapper.appendChild(header);
        
        // Grid
        const grid = document.createElement('div');
        grid.className = 'heatmap-grid';
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = `repeat(${this.columnas}, 1fr)`;
        grid.style.gap = '16px';
        
        if (this.parcelas && this.parcelas.length > 0) {
            this.parcelas.forEach((parcela, index) => {
                const card = this.createCard(parcela, index);
                grid.appendChild(card);
            });
        } else {
            const empty = document.createElement('div');
            empty.className = 'heatmap-empty';
            empty.textContent = 'No hay datos de parcelas';
            empty.style.gridColumn = '1 / -1';
            empty.style.padding = '40px';
            empty.style.textAlign = 'center';
            empty.style.color = '#95a5a6';
            grid.appendChild(empty);
        }
        
        wrapper.appendChild(grid);
        this.container.appendChild(wrapper);
        this.injectStyles();
    }

    createCard(parcela, index) {
        const card = document.createElement('div');
        card.className = 'parcela-card';
        card.dataset.parcelaId = parcela.id || `P-${String(index + 1).padStart(2, '0')}`;
        
        const estado = this.determinarEstado(parcela);
        const color = this.colores[estado] || this.colores.sin_datos;
        const estadoInfo = this.estados[estado] || this.estados.sin_datos;
        
        card.style.borderColor = color;
        card.style.borderWidth = '2px';
        card.style.borderStyle = 'solid';
        card.style.borderRadius = '12px';
        card.style.padding = '14px 16px';
        card.style.background = `${color}10`;
        card.style.cursor = 'pointer';
        card.style.transition = 'all 0.3s ease';
        
        // Hover
        card.onmouseenter = () => {
            card.style.transform = 'translateY(-4px)';
            card.style.boxShadow = '0 8px 25px rgba(0,0,0,0.10)';
        };
        card.onmouseleave = () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = 'none';
        };
        
        // Click
        if (this.onParcelaClick) {
            card.addEventListener('click', () => {
                this.onParcelaClick(parcela, index, card);
            });
        }
        
        const barWidth = Math.min(100, 100 - (parcela.humedad / 50) * 100);
        
        card.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span style="font-weight:700;font-size:14px;color:#1a1e1c;">${this.escapeHtml(parcela.nombre)}</span>
                <span style="font-size:11px;color:#72796e;background:#f0f2f0;padding:2px 10px;border-radius:12px;">${this.escapeHtml(parcela.id)}</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <div style="width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:16px;flex-shrink:0;background:radial-gradient(circle at 30% 30%, ${color}dd, ${color});box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                    ${parcela.humedad}%
                </div>
                <div style="font-size:12px;color:#42493e;line-height:1.6;">
                    <div>🌱 <strong>${this.escapeHtml(parcela.cultivo)}</strong></div>
                    <div>🌡️ ${parcela.temperatura}°C</div>
                </div>
            </div>
            <div style="margin-top:8px;">
                <div style="display:flex;justify-content:space-between;font-size:10px;color:#72796e;margin-bottom:2px;">
                    <span>Estrés</span>
                    <span style="color:${color};font-weight:700;">${Math.round(barWidth)}%</span>
                </div>
                <div style="width:100%;height:5px;background:#e6e8ea;border-radius:4px;overflow:hidden;">
                    <div style="width:${barWidth}%;height:100%;border-radius:4px;background:${color};transition:width 0.8s ease;"></div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #e6e8ea;font-size:11px;color:#72796e;">
                <span>Umbral: ${parcela.umbral || 30}%</span>
                <span style="padding:2px 12px;border-radius:12px;font-size:10px;font-weight:700;color:white;background:${color};">${estadoInfo.icon} ${estadoInfo.label}</span>
            </div>
        `;
        
        return card;
    }

    determinarEstado(parcela) {
        const humedad = parseFloat(parcela.humedad) || 0;
        const temperatura = parseFloat(parcela.temperatura) || 0;
        
        if (humedad < 22 && temperatura > 28) return 'critico';
        if (humedad < 32 || temperatura > 30) return 'alerta';
        return 'optimo';
    }

    updateData(parcelas) {
        this.parcelas = parcelas;
        this.render();
    }

    updateParcela(parcelaId, newData) {
        const index = this.parcelas.findIndex(p => p.id === parcelaId);
        if (index !== -1) {
            Object.assign(this.parcelas[index], newData);
            this.render();
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    injectStyles() {
        const styleId = 'mapa-calor-styles';
        if (document.getElementById(styleId)) return;
        
        const styles = `
            .heatmap-header {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 12px;
            }
            .heatmap-legend {
                display: flex;
                gap: 16px;
                font-size: 12px;
                color: #42493e;
            }
            .heatmap-legend .legend-color {
                display: inline-block;
                width: 12px;
                height: 12px;
                border-radius: 4px;
                margin-right: 4px;
                vertical-align: middle;
            }
            .parcela-card {
                transition: all 0.3s ease;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = styleId;
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }

    destroy() {
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MapaCalor;
}