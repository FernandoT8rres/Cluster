/**
 * Visualizador Universal de Documentos
 * Soporta PDF, Word, Excel, imágenes, videos y más
 */

class DocumentViewer {
    constructor() {
        this.supportedTypes = {
            pdf: ['application/pdf'],
            image: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            video: ['video/mp4', 'video/webm', 'video/ogg'],
            audio: ['audio/mp3', 'audio/wav', 'audio/ogg'],
            text: ['text/plain', 'text/html', 'text/css', 'text/javascript'],
            office: [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ]
        };
    }

    /**
     * Determina el tipo de documento basado en el MIME type
     */
    getDocumentType(mimeType) {
        for (const [type, mimes] of Object.entries(this.supportedTypes)) {
            if (mimes.includes(mimeType)) {
                return type;
            }
        }
        return 'unknown';
    }

    /**
     * Genera el HTML apropiado para mostrar el documento
     */
    generateViewerHTML(url, mimeType, filename) {
        const docType = this.getDocumentType(mimeType);
        
        switch (docType) {
            case 'pdf':
                return this.generatePDFViewer(url, filename);
            
            case 'image':
                return this.generateImageViewer(url, filename);
            
            case 'video':
                return this.generateVideoViewer(url, filename);
            
            case 'audio':
                return this.generateAudioViewer(url, filename);
            
            case 'text':
                return this.generateTextViewer(url, filename);
            
            case 'office':
                return this.generateOfficeViewer(url, filename, mimeType);
            
            default:
                return this.generateDownloadViewer(url, filename, mimeType);
        }
    }

    generatePDFViewer(url, filename) {
        return `
            <div class="pdf-viewer w-full h-full">
                <iframe src="${url}" 
                        class="w-full h-full border-0 rounded-lg"
                        title="Vista PDF: ${this.escapeHtml(filename)}"
                        allowfullscreen>
                    <p>Su navegador no soporta PDFs. 
                       <a href="${url}" target="_blank">Descargar PDF</a>
                    </p>
                </iframe>
            </div>
        `;
    }

    generateImageViewer(url, filename) {
        return `
            <div class="image-viewer w-full h-full flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden">
                <img src="${url}" 
                     alt="${this.escapeHtml(filename)}"
                     class="max-w-full max-h-full object-contain cursor-zoom-in"
                     onclick="this.classList.toggle('object-cover'); this.classList.toggle('object-contain')"
                     loading="lazy">
            </div>
        `;
    }

    generateVideoViewer(url, filename) {
        return `
            <div class="video-viewer w-full h-full flex items-center justify-center bg-black rounded-lg">
                <video controls 
                       class="max-w-full max-h-full"
                       preload="metadata">
                    <source src="${url}" type="video/mp4">
                    <source src="${url}" type="video/webm">
                    Su navegador no soporta videos HTML5.
                    <a href="${url}">Descargar video</a>
                </video>
            </div>
        `;
    }

    generateAudioViewer(url, filename) {
        return `
            <div class="audio-viewer w-full h-full flex items-center justify-center bg-gray-100 rounded-lg">
                <div class="text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">${this.escapeHtml(filename)}</h3>
                    <audio controls class="w-full max-w-md">
                        <source src="${url}" type="audio/mpeg">
                        <source src="${url}" type="audio/wav">
                        Su navegador no soporta audio HTML5.
                    </audio>
                </div>
            </div>
        `;
    }

    generateTextViewer(url, filename) {
        return `
            <div class="text-viewer w-full h-full">
                <iframe src="${url}" 
                        class="w-full h-full border-0 rounded-lg bg-white"
                        title="Vista de texto: ${this.escapeHtml(filename)}">
                    <p>No se puede mostrar el archivo. 
                       <a href="${url}" target="_blank">Abrir en nueva ventana</a>
                    </p>
                </iframe>
            </div>
        `;
    }

    generateOfficeViewer(url, filename, mimeType) {
        // Para documentos de Office, intentamos usar Office Online Viewer
        const officeViewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}`;
        
        return `
            <div class="office-viewer w-full h-full">
                <iframe src="${officeViewerUrl}" 
                        class="w-full h-full border-0 rounded-lg"
                        title="Vista Office: ${this.escapeHtml(filename)}"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                </iframe>
                <div class="fallback-viewer w-full h-full items-center justify-center bg-gray-100 rounded-lg" style="display: none;">
                    <div class="text-center p-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">${this.escapeHtml(filename)}</h3>
                        <p class="text-gray-600 mb-4">No se puede mostrar vista previa del documento</p>
                        <a href="${url}" 
                           class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors"
                           target="_blank">
                            Descargar documento
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    generateDownloadViewer(url, filename, mimeType) {
        const icon = this.getFileIcon(mimeType);
        
        return `
            <div class="download-viewer w-full h-full flex items-center justify-center bg-gray-100 rounded-lg">
                <div class="text-center p-8">
                    <div class="text-6xl mb-4">${icon}</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">${this.escapeHtml(filename)}</h3>
                    <p class="text-gray-600 mb-4">Este tipo de archivo no se puede previsualizar</p>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-500">Tipo: ${mimeType}</p>
                        <a href="${url}" 
                           class="inline-block bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors"
                           download="${filename}">
                            📥 Descargar archivo
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    getFileIcon(mimeType) {
        const iconMap = {
            'application/pdf': '📄',
            'application/msword': '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
            'application/vnd.ms-excel': '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
            'application/vnd.ms-powerpoint': '📽️',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': '📽️',
            'application/zip': '📦',
            'application/x-rar-compressed': '📦',
            'text/plain': '📄',
            'text/html': '🌐',
            'text/css': '🎨',
            'text/javascript': '⚙️'
        };

        if (mimeType.startsWith('image/')) return '🖼️';
        if (mimeType.startsWith('video/')) return '🎥';
        if (mimeType.startsWith('audio/')) return '🎵';

        return iconMap[mimeType] || '📎';
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Método principal para mostrar un documento en un contenedor
     */
    displayDocument(containerSelector, url, mimeType, filename) {
        const container = document.querySelector(containerSelector);
        if (!container) {
            console.error('Contenedor no encontrado:', containerSelector);
            return;
        }

        const viewerHTML = this.generateViewerHTML(url, mimeType, filename);
        container.innerHTML = viewerHTML;

        // Ocultar placeholder si existe
        const placeholder = container.parentElement?.querySelector('#documentPlaceholder');
        if (placeholder) {
            placeholder.classList.add('hidden');
        }
    }

    /**
     * Verifica si un tipo de archivo es soportado para previsualización
     */
    isPreviewSupported(mimeType) {
        return this.getDocumentType(mimeType) !== 'unknown';
    }
}

// Instancia global del visualizador
window.documentViewer = new DocumentViewer();
