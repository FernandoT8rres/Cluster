/**
 * Módulo para manejo de base de datos de boletines
 * Funciones para conectar con la BD y obtener información de documentos
 */

class BulletinDatabase {
    constructor() {
        this.apiUrl = 'http://localhost:3000/api'; // Cambia por tu URL de API
        this.bulletins = [];
    }

    /**
     * Obtener todos los boletines desde la base de datos
     */
    async fetchBulletins() {
        try {
            const response = await fetch(`${this.apiUrl}/boletines`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.bulletins = data.boletines || [];
            return this.bulletins;
        } catch (error) {
            console.error('Error fetching bulletins:', error);
            // Fallback: retornar array vacío si hay error
            return [];
        }
    }

    /**
     * Obtener un boletín específico por ID
     */
    async getBulletinById(id) {
        try {
            const response = await fetch(`${this.apiUrl}/boletines/${id}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.boletin;
        } catch (error) {
            console.error('Error fetching bulletin by ID:', error);
            return null;
        }
    }

    /**
     * Obtener metadatos de un documento
     */
    async getDocumentMetadata(documentId) {
        try {
            const response = await fetch(`${this.apiUrl}/documentos/${documentId}/metadata`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.metadata;
        } catch (error) {
            console.error('Error fetching document metadata:', error);
            return null;
        }
    }

    /**
     * Obtener URL de visualización de documento
     */
    async getDocumentViewUrl(documentId) {
        try {
            const response = await fetch(`${this.apiUrl}/documentos/${documentId}/view-url`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.viewUrl;
        } catch (error) {
            console.error('Error fetching document view URL:', error);
            return null;
        }
    }

    /**
     * Obtener URL de descarga de documento
     */
    async getDocumentDownloadUrl(documentId) {
        try {
            const response = await fetch(`${this.apiUrl}/documentos/${documentId}/download-url`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.downloadUrl;
        } catch (error) {
            console.error('Error fetching document download URL:', error);
            return null;
        }
    }
}

// Crear instancia global
const bulletinDB = new BulletinDatabase();
