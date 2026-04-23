/**
 * API Wrapper Manager (api.js)
 * Maneja todas las llamadas asíncronas para proveer control estricto de errores, headers de token JWT y transformaciones consistentes.
 */

export const API_BASE_URL = window.location.origin + '/api';

/**
 * Función principal para peticiones tipo fetch (GET/POST/PUT/DELETE)
 */
export async function fetchApi(endpoint, options = {}) {
    const defaultHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };

    // Añadir token si existe en localStorage
    const token = localStorage.getItem('auth_token');
    if (token) {
        defaultHeaders['Authorization'] = `Bearer ${token}`;
    }

    const config = {
        credentials: 'include',
        ...options,
        headers: {
            ...defaultHeaders,
            ...options.headers
        }
    };

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
        const data = await response.json();

        if (!response.ok || data.success === false) {
            throw new Error(data.message || data.error || 'Error en la petición');
        }

        return data;
    } catch (error) {
        console.error(`[API Error] ${endpoint}:`, error.message);
        throw error;
    }
}

/**
 * Helpers nativos para agilizar tipado
 */
export const API = {
    get: (endpoint, extraHeaders = {}) => fetchApi(endpoint, { method: 'GET', headers: extraHeaders }),
    post: (endpoint, body) => fetchApi(endpoint, { method: 'POST', body: JSON.stringify(body) }),
    put: (endpoint, body) => fetchApi(endpoint, { method: 'PUT', body: JSON.stringify(body) }),
    delete: (endpoint) => fetchApi(endpoint, { method: 'DELETE' })
};
