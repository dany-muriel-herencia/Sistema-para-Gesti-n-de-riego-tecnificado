const API_BASE = '../backend/api';

// State management to detect changes for the terminal
let previousState = {
    turnos: [],
    parcelas: [],
    hidrantes: []
};

// UI Elements
const els = {
    clock: document.getElementById('real-time-clock'),
    metrics: {
        parcelas: document.getElementById('metric-parcelas'),
        riego: document.getElementById('metric-riego'),
        hidrantes: document.getElementById('metric-hidrantes'),
        humedad: document.getElementById('metric-humedad'),
        temp: document.getElementById('metric-temp')
    },
    grids: {
        parcelas: document.getElementById('parcelas-grid'),
        hidrantes: document.getElementById('hidrantes-grid')
    },
    queues: {
        cola: document.getElementById('cola-riego-container'),
        proximos: document.getElementById('proximos-turnos-container')
    },
    concurrency: {
        active: document.getElementById('concurrency-active'),
        waiting: document.getElementById('concurrency-waiting')
    },
    terminal: document.getElementById('terminal-log')
};

// ==========================================
// UTILS
// ==========================================
function updateClock() {
    const now = new Date();
    els.clock.textContent = now.toLocaleTimeString('es-ES', { 
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false 
    });
}
setInterval(updateClock, 1000);
updateClock();

function logEvent(type, message) {
    const time = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    let colorClass = 'text-tertiary-fixed';
    if (type === 'ACTION') colorClass = 'text-secondary-fixed';
    if (type === 'ALERT') colorClass = 'text-error';

    const logHtml = `
    <div class="flex gap-4 opacity-90 mt-1">
        <span class="text-outline shrink-0">[${time}]</span>
        <span class="${colorClass} shrink-0">${type}</span>
        <span class="flex-1 ${type === 'ALERT' ? 'text-error-container' : ''}">${message}</span>
    </div>
    `;
    
    // Insert before the cursor
    const cursor = document.getElementById('terminal-cursor');
    cursor.insertAdjacentHTML('beforebegin', logHtml);
    els.terminal.scrollTop = els.terminal.scrollHeight;
}

// ==========================================
// DATA FETCHING & RENDERING
// ==========================================
async function fetchData() {
    try {
        const [resParcelas, resTurnos, resHidrantes, resSensores] = await Promise.all([
            fetch(`${API_BASE}/parcelas.php`),
            fetch(`${API_BASE}/riego.php`),
            fetch(`${API_BASE}/hidrantes.php`),
            fetch(`${API_BASE}/sensores.php`)
        ]);

        const dataParcelas = await resParcelas.json();
        const dataTurnos = await resTurnos.json();
        const dataHidrantes = await resHidrantes.json();
        const dataSensores = await resSensores.json();

        const parcelas = dataParcelas.success ? dataParcelas.parcelas : [];
        const turnos = dataTurnos.success ? dataTurnos.turnos : [];
        const hidrantes = dataHidrantes.success ? dataHidrantes.hidrantes : [];
        const sensores = dataSensores.success ? dataSensores.sensores : [];

        renderDashboard(parcelas, turnos, hidrantes, sensores);
        detectEvents(parcelas, turnos, hidrantes);
        
        // Save current state for next diff
        previousState = { parcelas, turnos, hidrantes };
        
    } catch (err) {
        console.error("Error fetching data:", err);
    }
}

async function fetchConcurrencyState() {
    try {
        // Cache buster to ensure fresh data
        const res = await fetch(`${API_BASE}/estado_concurrencia.json?t=${Date.now()}`);
        if(res.ok) {
            const data = await res.json();
            els.concurrency.active.textContent = `${data.semaforo_en_uso} Activos`;
            els.concurrency.waiting.textContent = `${data.cola_espera} En Espera`;
            // If the python script is stopped, the timestamp will get old. 
            // We could add logic to show "Desconectado" if timestamp is too old.
        } else {
            els.concurrency.active.textContent = `0 Activos`;
            els.concurrency.waiting.textContent = `0 En Espera`;
        }
    } catch(err) {
        // Ignored, python might not be running yet
    }
}

function renderDashboard(parcelas, turnos, hidrantes, sensores) {
    // 1. Metrics
    const activas = parcelas.filter(p => p.estado === 'activa').length;
    els.metrics.parcelas.textContent = activas;

    const regando = turnos.filter(t => t.estado === 'regando').length;
    els.metrics.riego.textContent = regando;

    const hidrantesDisponibles = hidrantes.filter(h => h.estado === 'disponible').length;
    const hidrantesEnUso = hidrantes.filter(h => h.estado === 'en_uso').length;
    els.metrics.hidrantes.innerHTML = `${hidrantesDisponibles - hidrantesEnUso}<span class="text-title-sm text-on-surface-variant">/${hidrantesDisponibles}</span>`;

    let humSum = 0, humCount = 0;
    let tempSum = 0, tempCount = 0;
    sensores.forEach(s => {
        humSum += parseFloat(s.humedad); humCount++;
        tempSum += parseFloat(s.temperatura); tempCount++;
    });
    els.metrics.humedad.textContent = humCount > 0 ? Math.round(humSum / humCount) + '%' : 'N/D';
    els.metrics.temp.innerHTML = tempCount > 0 ? Math.round(tempSum / tempCount) + '<span class="text-title-sm text-on-surface-variant">°C</span>' : 'N/D';

    // 2. Parcelas Grid
    els.grids.parcelas.innerHTML = '';
    parcelas.forEach(p => {
        // Find latest sensors for this parcel
        // Sort by id desc to get latest
        const pSensores = sensores.filter(s => s.parcela_id == p.id).sort((a,b) => b.id - a.id);
        const latestSensor = pSensores[0];
        const hum = latestSensor ? latestSensor.humedad : '--';
        const temp = latestSensor ? latestSensor.temperatura : '--';
        
        // Find if it's currently regando
        const regandoStatus = turnos.find(t => t.parcela_id == p.id && t.estado === 'regando');
        
        let bgClass = "bg-white/40 border-white/20";
        let statusHtml = `<span class="text-[10px] text-on-surface-variant uppercase">Humedad</span>`;
        let valColor = "text-on-surface";
        
        if (regandoStatus) {
            bgClass = "bg-primary/20 border-primary/40 border-2 animate-pulse";
            statusHtml = `<span class="text-[10px] text-secondary font-bold uppercase">Regando...</span>`;
            valColor = "text-on-surface";
        } else if (hum !== '--' && hum < 40) {
            bgClass = "bg-error/10 border-error/30 border-2";
            statusHtml = `<span class="text-[10px] text-error font-bold uppercase">Stress Crítico</span>`;
            valColor = "text-on-surface";
        }

        els.grids.parcelas.innerHTML += `
        <div class="${bgClass} backdrop-blur-sm rounded-lg p-3 flex flex-col justify-between hover:bg-white/60 transition-all cursor-pointer" onclick="openEditParcela(${p.id}, '${p.nombre}', ${hum !== '--' ? hum : 60}, ${temp !== '--' ? temp : 25})">
            <span class="text-label-xs font-bold text-primary flex items-center gap-1">
                ${p.nombre} ${regandoStatus ? '<span class="w-2 h-2 rounded-full bg-secondary"></span>' : ''}
            </span>
            <div class="flex flex-col">
                <span class="text-headline-md font-bold ${valColor}">${hum}${hum !== '--' ? '%' : ''}</span>
                ${statusHtml}
            </div>
        </div>`;
    });

    // 3. Cola de Riego & Turnos
    els.queues.cola.innerHTML = '';
    const pendingAndActive = turnos.filter(t => t.estado === 'pendiente' || t.estado === 'regando');
    
    if (pendingAndActive.length === 0) {
        els.queues.cola.innerHTML = `<div class="text-body-sm text-on-surface-variant italic">No hay peticiones en cola.</div>`;
    } else {
        pendingAndActive.forEach(t => {
            const pNombre = parcelas.find(p => p.id == t.parcela_id)?.nombre || `ID ${t.parcela_id}`;
            const isRegando = t.estado === 'regando';
            els.queues.cola.innerHTML += `
            <div class="flex items-center gap-3 p-3 ${isRegando ? 'bg-secondary-container/10 border-secondary' : 'bg-surface-container-low border-outline'} border-l-4 rounded-r-lg">
                <div class="flex-1">
                    <div class="text-body-md font-bold text-on-surface">Parcela ${pNombre}</div>
                    <div class="text-label-xs text-on-surface-variant">${isRegando ? 'Riego en curso' : 'Esperando asignación'}</div>
                </div>
                ${isRegando ? '<div class="w-6 h-6 rounded-full border-2 border-secondary border-t-transparent animate-spin"></div>' : '<span class="material-symbols-outlined text-outline text-[20px]">hourglass_empty</span>'}
            </div>`;
        });
    }

    // 4. Hidrantes Grid
    els.grids.hidrantes.innerHTML = '';
    hidrantes.forEach(h => {
        let style = "bg-surface-container-high text-outline-variant border-outline-variant";
        let title = "Disponible";
        if (h.estado === 'en_uso') {
            style = "bg-tertiary/20 text-tertiary border-tertiary";
            title = "En Uso";
        } else if (h.estado === 'inactivo') {
            style = "bg-error-container/20 text-error border-error/30";
            title = "Inactivo";
        }
        
        els.grids.hidrantes.innerHTML += `
        <div class="h-10 rounded-lg ${style} flex items-center justify-center font-bold border" title="${h.nombre} - ${title}">
            ${h.id}
        </div>`;
    });
}

function detectEvents(parcelas, turnos, hidrantes) {
    if (previousState.turnos.length === 0) return; // Skip first load

    // New turns created or state changes
    turnos.forEach(t => {
        const prevT = previousState.turnos.find(pt => pt.id === t.id);
        const pNombre = parcelas.find(p => p.id == t.parcela_id)?.nombre || t.parcela_id;
        
        if (!prevT) {
            logEvent('INFO', `Nueva petición de riego recibida para parcela ${pNombre}.`);
        } else if (prevT.estado !== t.estado) {
            if (t.estado === 'regando') {
                logEvent('ACTION', `Iniciando riego en parcela ${pNombre}. Válvula abierta.`);
            } else if (t.estado === 'completado') {
                logEvent('INFO', `Riego completado en parcela ${pNombre}.`);
            }
        }
    });

    // Hydrant additions
    if (hidrantes.length > previousState.hidrantes.length) {
        logEvent('ACTION', `Nuevo hidrante registrado en el sistema. Capacidad ampliada.`);
    }
}

// ==========================================
// FORM HANDLING
// ==========================================
document.getElementById('add-hydrant-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const feedback = document.getElementById('add-hydrant-feedback');
    const nombre = document.getElementById('new-hydrant-name').value.trim();
    const capacidad = parseInt(document.getElementById('new-hydrant-cap').value);

    const estado = document.getElementById('new-hydrant-estado').value;

    btn.disabled = true;
    
    try {
        const res = await fetch(`${API_BASE}/hidrantes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, capacidad, estado })
        });
        
        const data = await res.json();
        feedback.classList.remove('hidden');
        if (data.success) {
            feedback.textContent = '✅ Hidrante añadido';
            feedback.className = 'mt-1 text-[10px] text-tertiary';
            e.target.reset();
            fetchData(); // immediately refresh
        } else {
            feedback.textContent = `❌ Error: ${data.error}`;
            feedback.className = 'mt-1 text-[10px] text-error';
        }
    } catch(err) {
        feedback.classList.remove('hidden');
        feedback.textContent = `❌ Error de conexión`;
        feedback.className = 'mt-1 text-[10px] text-error';
    }
    
    setTimeout(() => { feedback.classList.add('hidden'); }, 4000);
    btn.disabled = false;
});

// Agregar Parcela Handler
document.getElementById('form-agregar-parcela').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const nombre = document.getElementById('add-parcela-nombre').value.trim();
    const cultivo = document.getElementById('add-parcela-cultivo').value.trim();
    const humedad = parseFloat(document.getElementById('add-parcela-humedad').value);
    const temperatura = parseFloat(document.getElementById('add-parcela-temp').value);

    btn.disabled = true;
    try {
        const res = await fetch(`${API_BASE}/parcelas.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, cultivo, humedad, temperatura, estado: 'activa' })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('modal-agregar-parcela').classList.add('hidden');
            e.target.reset();
            fetchData();
        } else {
            alert('Error al crear parcela: ' + data.error);
        }
    } catch(err) {
        alert('Error de conexión');
    }
    btn.disabled = false;
});

// Editar Parcela / Forzar Estrés Handler
window.openEditParcela = function(id, nombre, humedadActual, tempActual) {
    document.getElementById('edit-parcela-id').value = id;
    document.getElementById('edit-parcela-title').textContent = `Editar ${nombre}`;
    
    const humSlider = document.getElementById('edit-parcela-humedad-slider');
    const tempSlider = document.getElementById('edit-parcela-temp-slider');
    
    humSlider.value = Math.round(humedadActual);
    tempSlider.value = Math.round(tempActual);
    
    document.getElementById('edit-parcela-humedad-val').textContent = humSlider.value + '%';
    document.getElementById('edit-parcela-temp-val').textContent = tempSlider.value + '°C';
    
    document.getElementById('modal-editar-parcela').classList.remove('hidden');
};

document.getElementById('form-editar-parcela').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const id = document.getElementById('edit-parcela-id').value;
    const humedad = parseFloat(document.getElementById('edit-parcela-humedad-slider').value);
    const temperatura = parseFloat(document.getElementById('edit-parcela-temp-slider').value);

    btn.disabled = true;
    try {
        const res = await fetch(`${API_BASE}/parcelas.php?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ humedad, temperatura })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('modal-editar-parcela').classList.add('hidden');
            fetchData();
        } else {
            alert('Error al actualizar parcela: ' + data.error);
        }
    } catch(err) {
        alert('Error de conexión');
    }
    btn.disabled = false;
});

// Terminal Drawer Toggle
const terminal = document.getElementById('terminal-drawer');
const toggleBtn = document.getElementById('toggle-terminal');
let isExpanded = true;

toggleBtn.addEventListener('click', () => {
    if (isExpanded) {
        terminal.style.transform = 'translateY(160px)';
        toggleBtn.style.transform = 'rotate(180deg)';
    } else {
        terminal.style.transform = 'translateY(0)';
        toggleBtn.style.transform = 'rotate(0deg)';
    }
    isExpanded = !isExpanded;
});

// Init Polling
fetchData();
fetchConcurrencyState();
setInterval(fetchData, 3000);
setInterval(fetchConcurrencyState, 1000);
