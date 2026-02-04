/**
 * Módulo para renderizar boletines dinámicamente
 * Maneja la visualización de la lista de boletines desde la BD
 */

class BulletinRenderer {
    constructor() {
        this.bulletinsContainer = null;
        this.loadingIndicator = null;
    }

    /**
     * Inicializar el renderizador
     */
    init() {
        // Buscar el contenedor de boletines en el DOM
        const bulletinsGrid = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3');
        if (bulletinsGrid) {
            this.bulletinsContainer = bulletinsGrid;
        } else {
            console.error('Contenedor de boletines no encontrado');
            return false;
        }

        // Crear indicador de carga
        this.createLoadingIndicator();
        
        return true;
    }

    /**
     * Crear indicador de carga
     */
    createLoadingIndicator() {
        this.loadingIndicator = document.createElement('div');
        this.loadingIndicator.className = 'col-span-full flex justify-center items-center py-12';
        this.loadingIndicator.innerHTML = `
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-clúster-red mx-auto mb-4"></div>
                <p class="text-gray-600">Cargando boletines...</p>
            </div>
        `;
    }

    /**
     * Mostrar indicador de carga
     */
    showLoading() {
        if (this.bulletinsContainer && this.loadingIndicator) {
            this.bulletinsContainer.innerHTML = '';
            this.bulletinsContainer.appendChild(this.loadingIndicator);
        }
    }

    /**
     * Mostrar mensaje cuando no hay boletines
     */
    showEmptyState() {
        if (this.bulletinsContainer) {
            this.bulletinsContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay boletines disponibles</h3>
                    <p class="text-gray-500">Los boletines aparecerán aquí cuando estén disponibles.</p>
                </div>
            `;
        }
    }

    /**
     * Mostrar mensaje de error
     */
    showError() {
        if (this.bulletinsContainer) {
            this.bulletinsContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Error al cargar boletines</h3>
                    <p class="text-gray-500 mb-4">Hubo un problema al obtener los boletines de la base de datos.</p>
                    <button onclick="bulletinRenderer.loadBulletins()" 
                            class="bg-clúster-red text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        Reintentar
                    </button>
                </div>
            `;
        }
    }

    /**
     * Renderizar lista de boletines
     */
    renderBulletins(bulletins) {
        if (!this.bulletinsContainer) {
            console.error('Contenedor de boletines no inicializado');
            return;
        }

        if (!bulletins || bulletins.length === 0) {
            this.showEmptyState();
            return;
        }

        this.bulletinsContainer.innerHTML = '';

        bulletins.forEach(bulletin => {
            const bulletinCard = this.createBulletinCard(bulletin);
            this.bulletinsContainer.appendChild(bulletinCard);
        });
    }

    /**
     * Crear tarjeta de boletín individual
     */
    createBulletinCard(bulletin) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-3xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group cursor-pointer';
        card.setAttribute('data-bulletin-id', bulletin.id);
        card.onclick = () => bulletinViewer.openBulletin(bulletin.id);

        // Determinar el estado del boletín
        const isNew = this.isRecentBulletin(bulletin.fecha_creacion);
        const statusBadge = this.getStatusBadge(bulletin, isNew);

        // Formatear fecha
        const formattedDate = this.formatDate(bulletin.fecha_creacion);

        // Obtener descripción truncada
        const description = this.truncateText(bulletin.descripcion || 'Sin descripción disponible', 100);

        card.innerHTML = `
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    ${statusBadge}
                    <span class="text-gray-500 text-sm">
                        ${formattedDate}
                    </span>
                </div>
                <h3 class="text-xl font-semibold text-clúster-dark mb-3 group-hover:text-clúster-red transition-colors">
                    ${this.escapeHtml(bulletin.titulo)}
                </h3>
                <p class="text-gray-600 mb-4 line-clamp-3">
                    ${description}
                </p>
                <div class="flex items-center justify-between">
                    <div class="flex items-center text-gray-500 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                            </path>
                        </svg>
                        ${bulletin.tipo_archivo || 'Documento'}
                    </div>
                    <div class="text-clúster-red font-medium text-sm">
                        Ver más →
                    </div>
                </div>
            </div>
        `;

        return card;
    }

    /**
     * Obtener badge de estado del boletín
     */
    getStatusBadge(bulletin, isNew) {
        if (isNew) {
            return `<span class="bg-clúster-red text-white px-3 py-1 rounded-full text-sm font-medium">Nuevo</span>`;
        } else if (bulletin.es_especial) {
            return `<span class="bg-clúster-red/20 text-clúster-red px-3 py-1 rounded-full text-sm font-medium">Especial</span>`;
        } else {
            return `<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-medium">Archivo</span>`;
        }
    }

    /**
     * Verificar si un boletín es reciente (últimos 30 días)
     */
    isRecentBulletin(dateString) {
        const bulletinDate = new Date(dateString);
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        return bulletinDate >= thirtyDaysAgo;
    }

    /**
     * Formatear fecha para mostrar
     */
    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long'
            });
        } catch (error) {
            return 'Fecha no disponible';
        }
    }

    /**
     * Truncar texto a un número específico de caracteres
     */
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength).trim() + '...';
    }

    /**
     * Escapar HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Cargar y renderizar boletines desde la base de datos
     */
    async loadBulletins() {
        this.showLoading();
        
        try {
            const bulletins = await bulletinDB.fetchBulletins();
            this.renderBulletins(bulletins);
        } catch (error) {
            console.error('Error loading bulletins:', error);
            this.showError();
        }
    }
}

// Crear instancia global
const bulletinRenderer = new BulletinRenderer();
