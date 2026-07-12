// ============================================
// ROUTER - NAVEGACIÓN ENTRE PESTAÑAS
// ============================================

const Router = {
    pages: {
        dashboard: 'pages/dashboard.html',
        hidrantes: 'pages/hidrantes.html',
        turnos: 'pages/turnos.html',
        consola: 'pages/consola.html',
        estadisticas: 'pages/estadisticas.html'
    },
    
    currentPage: 'dashboard',
    container: null,
    isLoading: false,
    
    init() {
        this.container = document.getElementById('page-container');
        if (!this.container) {
            console.error('❌ Contenedor de páginas no encontrado');
            return;
        }
        
        this.setupTabs();
        this.loadPage('dashboard');
        console.log('✅ Router inicializado');
    },
    
    setupTabs() {
        const tabs = document.querySelectorAll('.tab-btn');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const page = tab.dataset.tab;
                if (page && page !== this.currentPage) {
                    this.loadPage(page);
                }
            });
        });
    },
    
    async loadPage(page) {
        if (this.isLoading) return;
        if (!this.pages[page]) {
            console.error(`❌ Página no encontrada: ${page}`);
            return;
        }
        
        this.isLoading = true;
        this.currentPage = page;
        
        // Actualizar pestañas
        this.updateTabs(page);
        
        // Mostrar loading
        this.container.innerHTML = `
            <div style="display:flex;justify-content:center;align-items:center;padding:60px;color:var(--text-secondary);">
                <div style="text-align:center;">
                    <div style="width:40px;height:40px;border:3px solid var(--border-color);border-top-color:var(--primary);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px;"></div>
                    <p>Cargando ${page}...</p>
                </div>
            </div>
        `;
        
        try {
            const response = await fetch(this.pages[page]);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const html = await response.text();
            this.container.innerHTML = html;
            
            // Ejecutar scripts después de cargar la página
            this.executeScripts();
            
            // Disparar evento de carga
            document.dispatchEvent(new CustomEvent('pageLoaded', { detail: { page } }));
            
        } catch (error) {
            console.error(`❌ Error al cargar ${page}:`, error);
            this.container.innerHTML = `
                <div style="padding:60px;text-align:center;color:var(--danger);">
                    <span style="font-size:48px;">⚠️</span>
                    <h3>Error al cargar la página</h3>
                    <p style="color:var(--text-secondary);">${error.message}</p>
                    <button class="btn btn-primary" onclick="Router.loadPage('${page}')" style="margin-top:16px;">
                        🔄 Reintentar
                    </button>
                </div>
            `;
        }
        
        this.isLoading = false;
    },
    
    updateTabs(page) {
        const tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === page);
        });
    },
    
    executeScripts() {
        // Ejecutar scripts dentro de la página cargada
        const scripts = this.container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }
};

// ============================================
// INICIALIZAR ROUTER
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    Router.init();
});

// Exponer Router globalmente
window.Router = Router;