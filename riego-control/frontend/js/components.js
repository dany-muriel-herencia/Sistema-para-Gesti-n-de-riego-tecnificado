// ============================================
// COMPONENTES - CENTRO DE CONTROL LA YARADA
// ============================================

function determinarEstado(humedad, temperatura) {
    if (humedad < 22 && temperatura > 28) return 'critico';
    if (humedad < 32 || temperatura > 30) return 'alerta';
    return 'optimo';
}

function getNivel(humedad, temperatura) {
    const estado = determinarEstado(humedad, temperatura);
    const map = {
        critico: { label: '🚨 CRÍTICO', color: '#e74c3c', emoji: '🔴' },
        alerta: { label: '⚡ ALERTA', color: '#f39c12', emoji: '🟡' },
        optimo: { label: '✅ ÓPTIMO', color: '#27ae60', emoji: '🟢' }
    };
    return map[estado] || { label: '❓', color: '#95a5a6', emoji: '⚪' };
}

function escapeHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

// ============================================
// RENDER MAPA DE CALOR
// ============================================

function renderMapa() {
    console.log('🔥 renderMapa() ejecutado');
    const contenedor = document.getElementById('heatmapContainer');
    if (!contenedor) {
        console.error('❌ Contenedor heatmapContainer no encontrado');
        return;
    }
    
    if (!window.DATOS || !window.DATOS.parcelas || window.DATOS.parcelas.length === 0) {
        contenedor.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-secondary);">
                <span style="font-size:48px;display:block;margin-bottom:16px;">🌱</span>
                <h3 style="color:var(--text-primary);">No hay parcelas registradas</h3>
                <p style="font-size:14px;color:var(--text-secondary);">Agrega parcelas desde el panel de control</p>
            </div>
        `;
        return;
    }
    
    console.log('📊 Renderizando ' + window.DATOS.parcelas.length + ' parcelas');
    let html = '';
    window.DATOS.parcelas.forEach(p => {
        const e = determinarEstado(p.humedad, p.temperatura);
        const n = getNivel(p.humedad, p.temperatura);
        const clase = e === 'critico' ? 'critico' : (e === 'alerta' ? 'alerta' : 'optimo');
        const barWidth = Math.min(100, 100 - (p.humedad / 50) * 100);
        const barColor = e === 'critico' ? '#e74c3c' : (e === 'alerta' ? '#f39c12' : '#27ae60');

        html += `
            <div class="parcela-card ${clase}" onclick="seleccionar(${p.id})">
                <div class="top">
                    <span class="name">${escapeHtml(p.nombre)}</span>
                    <span class="id">ID: ${p.id}</span>
                </div>
                <div class="body">
                    <div class="circle" style="background:radial-gradient(circle at 30% 30%, ${barColor}dd, ${barColor});">
                        ${p.humedad}%
                    </div>
                    <div class="info">
                        <div>🌱 <strong>${escapeHtml(p.cultivo)}</strong></div>
                        <div>🌡️ ${p.temperatura}°C</div>
                    </div>
                </div>
                <div class="stress-bar">
                    <div class="labels">
                        <span>Estrés</span>
                        <span style="color:${barColor};font-weight:700;">${Math.round(barWidth)}%</span>
                    </div>
                    <div class="track">
                        <div class="fill" style="width:${barWidth}%;background:${barColor};"></div>
                    </div>
                </div>
                <div class="footer">
                    <span>Umbral: ${p.umbral || 30}%</span>
                    <span class="status-badge" style="background:${n.color}">${n.emoji} ${n.label}</span>
                </div>
            </div>
        `;
    });
    contenedor.innerHTML = html;
    console.log('✅ Mapa renderizado correctamente');
}

// ============================================
// RENDER HIDRANTES - VERSIÓN FUTURISTA
// ============================================

function renderHidrantes() {
    console.log('🔥 renderHidrantes() ejecutado');
    const contenedor = document.getElementById('hidrantesContainer');
    if (!contenedor) {
        console.error('❌ Contenedor hidrantesContainer no encontrado');
        return;
    }
    
    if (!window.DATOS || !window.DATOS.hidrantes || window.DATOS.hidrantes.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align:center;padding:60px 20px;color:var(--text-secondary);">
                <span style="font-size:48px;display:block;margin-bottom:16px;">🚿</span>
                <h3 style="color:var(--text-primary);">No hay hidrantes registrados</h3>
                <p style="font-size:14px;color:var(--text-secondary);">Agrega hidrantes desde el panel de control</p>
            </div>
        `;
        return;
    }
    
    console.log('🚿 Renderizando ' + window.DATOS.hidrantes.length + ' hidrantes (estilo futurista)');
    
    const totalHidrantes = window.DATOS.hidrantes.length;
    const disponibles = window.DATOS.hidrantes.filter(h => h.disponible).length;
    const porcentaje = Math.round((disponibles / totalHidrantes) * 100);
    
    let html = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding:12px 20px;background:rgba(88,166,255,0.05);border-radius:12px;border:1px solid rgba(88,166,255,0.08);flex-wrap:wrap;gap:12px;">
            <div>
                <span style="font-size:13px;color:var(--text-secondary);font-weight:500;text-transform:uppercase;letter-spacing:0.5px;">🚿 Estado del Sistema</span>
                <div style="font-size:22px;font-weight:800;color:var(--text-primary);margin-top:2px;">
                    ${disponibles} <span style="font-size:14px;font-weight:400;color:var(--text-secondary);">de ${totalHidrantes} disponibles</span>
                </div>
            </div>
            <div style="text-align:right;">
                <span style="font-size:11px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Disponibilidad</span>
                <div style="font-size:24px;font-weight:900;color:${porcentaje > 50 ? 'var(--success)' : 'var(--warning)'};">
                    ${porcentaje}%
                </div>
            </div>
        </div>
        <div class="hidrantes-futuristic">
    `;
    
    window.DATOS.hidrantes.forEach(h => {
        const estado = h.disponible ? 'activo' : 'inactivo';
        const estadoLabel = h.disponible ? '🟢 ONLINE' : '🔴 OFFLINE';
        const estadoColor = h.disponible ? '#3fb950' : '#f85149';
        const porcentajeUso = h.disponible ? Math.round(65 + Math.random() * 30) : Math.round(10 + Math.random() * 20);
        
        html += `
            <div class="hidrante-card ${estado}" onclick="toggleHidranteFromCard(${h.id})">
                <div class="cyber-grid"></div>
                <div class="scan-line"></div>
                
                <div class="hidrante-header">
                    <span class="hidrante-id">#${String(h.id).padStart(2, '0')}</span>
                    <div class="hidrante-status-indicator">
                        <span class="hidrante-status-dot ${estado}"></span>
                        <span style="color:${estadoColor};font-size:10px;letter-spacing:0.5px;font-weight:600;">${estadoLabel}</span>
                    </div>
                </div>
                
                <div class="hidrante-nombre">
                    <span>${escapeHtml(h.nombre)}</span>
                </div>
                
                <div class="hidrante-metrics">
                    <div class="hidrante-metric">
                        <div class="label">⚡ Estado</div>
                        <div class="value ${estado}">${h.disponible ? 'ACTIVO' : 'INACTIVO'}</div>
                    </div>
                    <div class="hidrante-metric">
                        <div class="label">📊 Uso</div>
                        <div class="value ${estado}">${porcentajeUso}%</div>
                    </div>
                    <div class="hidrante-metric">
                        <div class="label">🔋 Capacidad</div>
                        <div class="value">${h.capacidad || 1}</div>
                    </div>
                    <div class="hidrante-metric">
                        <div class="label">🔄 Conexiones</div>
                        <div class="value">${h.disponible ? Math.round(Math.random() * 3) : 0}</div>
                    </div>
                </div>
                
                <div class="hidrante-status-bar">
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-secondary);margin-bottom:3px;">
                        <span>ACTIVIDAD</span>
                        <span>${porcentajeUso}%</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill ${estado}" style="width:${porcentajeUso}%;"></div>
                    </div>
                </div>
                
                <div class="hidrante-footer">
                    <span class="info">⏱️ ${h.disponible ? 'Última conexión: hace 2min' : 'Desconectado hace 15min'}</span>
                    <div class="actions">
                        <button class="btn-cyber ${estado}" onclick="event.stopPropagation();toggleHidranteFromCard(${h.id})">
                            ${h.disponible ? '⏸️ DETENER' : '▶️ ACTIVAR'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `</div>`;
    contenedor.innerHTML = html;
    
    // Actualizar el badge de hidrantes
    const badge = document.getElementById('hidrantesBadge');
    if (badge) badge.textContent = disponibles;
    
    const statusEl = document.getElementById('hidrantesStatus');
    if (statusEl) statusEl.textContent = disponibles + ' disponibles';
    
    console.log('✅ Hidrantes futuristas renderizados correctamente');
}

// ============================================
// RENDER TURNOS
// ============================================

function renderTurnos() {
    console.log('🔥 renderTurnos() ejecutado');
    const contenedor = document.getElementById('turnosContainer');
    if (!contenedor) {
        console.error('❌ Contenedor turnosContainer no encontrado');
        return;
    }
    
    if (!window.DATOS || !window.DATOS.turnos || window.DATOS.turnos.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align:center;padding:60px 20px;color:var(--text-secondary);">
                <span style="font-size:48px;display:block;margin-bottom:16px;">📋</span>
                <h3 style="color:var(--text-primary);">No hay turnos registrados</h3>
                <p style="font-size:14px;color:var(--text-secondary);">Los turnos de riego aparecerán aquí cuando sean programados</p>
            </div>
        `;
        return;
    }
    
    console.log('📋 Renderizando ' + window.DATOS.turnos.length + ' turnos');
    let html = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding:8px 16px;background:rgba(88,166,255,0.05);border-radius:8px;border:1px solid rgba(88,166,255,0.08);flex-wrap:wrap;gap:8px;">
            <span style="font-size:13px;color:var(--text-secondary);font-weight:500;">📋 Total de turnos: <strong style="color:var(--text-primary);">${window.DATOS.turnos.length}</strong></span>
            <span style="font-size:13px;color:var(--text-secondary);font-weight:500;">
                Pendientes: <strong style="color:var(--warning);">${window.DATOS.turnos.filter(t => t.estado === 'pendiente' || t.estado === 'en espera').length}</strong>
            </span>
        </div>
        <div class="turnos-list">
    `;
    
    window.DATOS.turnos.forEach(t => {
        const clase = t.estado === 'completado' ? 'completed' : (t.estado === 'pendiente' ? 'pending' : 'waiting');
        const label = t.estado === 'completado' ? '✅ completado' : (t.estado === 'pendiente' ? '⏳ pendiente' : '⏰ en espera');
        const icono = t.estado === 'completado' ? '✅' : (t.estado === 'pendiente' ? '⏳' : '⏰');
        const color = t.estado === 'completado' ? 'var(--success)' : (t.estado === 'pendiente' ? 'var(--warning)' : 'var(--info)');
        
        html += `
            <div class="list-item ${clase}" style="border-left-color:${color};">
                <span class="icon" style="font-size:20px;">${icono}</span>
                <div class="info">
                    <div class="title" style="font-weight:600;color:var(--text-primary);">
                        ${escapeHtml(t.parcela || 'Parcela ' + t.parcela_id)}
                    </div>
                    <div class="sub" style="font-size:13px;color:var(--text-secondary);">
                        🚿 ${t.hidrante || 'En espera'} • ⏱️ ${t.duracion || 0} min
                    </div>
                </div>
                <span class="status ${clase}" style="color:${color};font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:0.3px;">
                    ${label}
                </span>
            </div>
        `;
    });
    
    html += `</div>`;
    contenedor.innerHTML = html;
    
    const statusEl = document.getElementById('turnosStatus');
    if (statusEl) {
        const pendientes = window.DATOS.turnos.filter(t => t.estado === 'pendiente' || t.estado === 'en espera').length;
        statusEl.textContent = pendientes + ' pendientes';
    }
    
    console.log('✅ Turnos renderizados correctamente');
}

// ============================================
// ACTUALIZAR MÉTRICAS
// ============================================

function actualizarMetricas() {
    console.log('🔥 actualizarMetricas() ejecutado');
    
    if (!window.DATOS || !window.DATOS.parcelas) {
        console.warn('⚠️ DATOS no disponibles');
        return;
    }
    
    const total = window.DATOS.parcelas.length;
    let criticos = 0, alertas = 0, optimos = 0;
    window.DATOS.parcelas.forEach(p => {
        const e = determinarEstado(p.humedad, p.temperatura);
        if (e === 'critico') criticos++;
        else if (e === 'alerta') alertas++;
        else optimos++;
    });
    const hDisp = window.DATOS.hidrantes ? window.DATOS.hidrantes.filter(h => h.disponible).length : 0;
    const totalHidrantes = window.DATOS.hidrantes ? window.DATOS.hidrantes.length : 0;

    // Actualizar elementos (con verificación de existencia)
    const el = (id) => document.getElementById(id);
    
    const totalParcelas = el('totalParcelas');
    if (totalParcelas) totalParcelas.textContent = total;
    
    const alertasCount = el('alertasCount');
    if (alertasCount) alertasCount.textContent = alertas;
    
    const criticosCount = el('criticosCount');
    if (criticosCount) criticosCount.textContent = criticos;
    
    const hidrantesCount = el('hidrantesCount');
    if (hidrantesCount) hidrantesCount.innerHTML = hDisp + ' <span class="small">/' + totalHidrantes + '</span>';

    // Barras de estadísticas
    const maxVal = Math.max(criticos, alertas, optimos, 1);
    
    const barCritico = el('barCritico');
    if (barCritico) barCritico.style.height = (criticos / maxVal) * 100 + '%';
    
    const barAlerta = el('barAlerta');
    if (barAlerta) barAlerta.style.height = (alertas / maxVal) * 100 + '%';
    
    const barOptimo = el('barOptimo');
    if (barOptimo) barOptimo.style.height = (optimos / maxVal) * 100 + '%';
    
    const criticosLabel = el('criticosLabel');
    if (criticosLabel) criticosLabel.textContent = criticos;
    
    const alertasLabel = el('alertasLabel');
    if (alertasLabel) alertasLabel.textContent = alertas;
    
    const optimosLabel = el('optimosLabel');
    if (optimosLabel) optimosLabel.textContent = optimos;

    // Promedios
    const hums = window.DATOS.parcelas.map(p => p.humedad);
    const temps = window.DATOS.parcelas.map(p => p.temperatura);
    const hProm = hums.length ? (hums.reduce((a,b) => a+b, 0) / hums.length).toFixed(1) : '0';
    const tProm = temps.length ? (temps.reduce((a,b) => a+b, 0) / temps.length).toFixed(1) : '0';

    const humedadMedia = el('humedadMedia');
    if (humedadMedia) humedadMedia.textContent = hProm + '%';
    
    const tempPromedio = el('tempPromedio');
    if (tempPromedio) tempPromedio.innerHTML = Math.round(tProm) + ' <span class="small">°C</span>';
    
    const humedadPromedio = el('humedadPromedio');
    if (humedadPromedio) humedadPromedio.textContent = hProm + '%';
    
    const tempPromedioStat = el('tempPromedioStat');
    if (tempPromedioStat) tempPromedioStat.textContent = tProm + '°C';
    
    const hidrantesActivos = el('hidrantesActivos');
    if (hidrantesActivos) hidrantesActivos.textContent = hDisp + '/' + totalHidrantes;

    // Nivel de estrés
    let nivel = '✅ NORMAL', clase = 'success';
    if (criticos > 0) { nivel = '🚨 CRÍTICO'; clase = 'danger'; }
    else if (alertas > 0) { nivel = '⚡ ALERTA'; clase = 'warning'; }
    
    const nivelEstres = el('nivelEstres');
    if (nivelEstres) {
        nivelEstres.textContent = nivel;
        nivelEstres.className = 'value ' + clase;
    }
    
    console.log('✅ Métricas actualizadas');
}

// ============================================
// SELECCIONAR PARCELA
// ============================================

function seleccionarParcela(id) {
    if (!window.DATOS || !window.DATOS.parcelas) return;
    const p = window.DATOS.parcelas.find(x => x.id === id);
    if (p) {
        const e = determinarEstado(p.humedad, p.temperatura);
        const n = getNivel(p.humedad, p.temperatura);
        if (window.agregarEvento) {
            window.agregarEvento(`📍 ${p.nombre}: ${p.humedad}% - ${n.emoji} ${n.label}`, e);
        }
    }
}

// ============================================
// TOGGLE HIDRANTE DESDE TARJETA
// ============================================

function toggleHidranteFromCard(id) {
    if (!window.DATOS || !window.DATOS.hidrantes) return;
    const h = window.DATOS.hidrantes.find(x => x.id === id);
    if (h) {
        h.disponible = !h.disponible;
        const estado = h.disponible ? 'ACTIVADO' : 'DETENIDO';
        if (window.agregarEvento) {
            window.agregarEvento(`🚿 ${h.nombre}: ${estado}`, 'hidrante');
        }
        // Recargar la vista de hidrantes si está visible
        if (document.getElementById('page-hidrantes')?.style.display !== 'none') {
            renderHidrantes();
        }
        if (typeof actualizarMetricas === 'function') actualizarMetricas();
    }
}

// ============================================
// EXPONER FUNCIONES GLOBALMENTE
// ============================================

window.determinarEstado = determinarEstado;
window.getNivel = getNivel;
window.escapeHtml = escapeHtml;
window.renderMapa = renderMapa;
window.renderHidrantes = renderHidrantes;
window.renderTurnos = renderTurnos;
window.actualizarMetricas = actualizarMetricas;
window.seleccionar = seleccionarParcela;
window.toggleHidranteFromCard = toggleHidranteFromCard;

console.log('📦 components.js cargado correctamente');
console.log('📋 Funciones disponibles:', {
    renderMapa: typeof renderMapa,
    renderHidrantes: typeof renderHidrantes,
    renderTurnos: typeof renderTurnos,
    actualizarMetricas: typeof actualizarMetricas,
    determinarEstado: typeof determinarEstado,
    getNivel: typeof getNivel,
    toggleHidranteFromCard: typeof toggleHidranteFromCard
});