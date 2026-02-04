// auth-pattern-fix.js - Solución para errores de patrón en autenticación

class ClústerAuthManager {
    constructor() {
        this.apiBaseUrl = '../api/auth/';
        this.maxRetries = 3;
        this.retryDelay = 1000;
    }

    // Limpiar datos de localStorage que puedan tener patrones inválidos
    cleanStorageData() {
        try {
            const token = localStorage.getItem('clúster_token');
            const userStr = localStorage.getItem('clúster_user');

            // Verificar patrón del token
            if (token && !this.isValidJWTPattern(token)) {
                console.warn('🧹 Token con patrón inválido detectado, limpiando...');
                localStorage.removeItem('clúster_token');
            }

            // Verificar datos del usuario
            if (userStr) {
                try {
                    const user = JSON.parse(userStr);
                    if (!this.isValidUserData(user)) {
                        console.warn('🧹 Datos de usuario inválidos detectados, limpiando...');
                        localStorage.removeItem('clúster_user');
                    }
                } catch (e) {
                    console.warn('🧹 JSON de usuario corrupto, limpiando...');
                    localStorage.removeItem('clúster_user');
                }
            }
        } catch (error) {
            console.error('Error limpiando storage:', error);
            this.clearAllStorage();
        }
    }

    // Validar patrón JWT
    isValidJWTPattern(token) {
        if (!token || typeof token !== 'string') return false;
        
        // JWT debe tener exactamente 3 partes separadas por puntos
        const parts = token.split('.');
        if (parts.length !== 3) return false;

        // Cada parte debe contener solo caracteres válidos para base64url
        const base64UrlPattern = /^[A-Za-z0-9_-]+$/;
        return parts.every(part => base64UrlPattern.test(part) && part.length > 0);
    }

    // Validar datos de usuario
    isValidUserData(user) {
        if (!user || typeof user !== 'object') return false;

        const requiredFields = ['id', 'email', 'rol'];
        if (!requiredFields.every(field => field in user)) return false;

        // Validar email
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(user.email)) return false;

        // Validar rol
        const validRoles = ['admin', 'empleado', 'moderador'];
        if (!validRoles.includes(user.rol)) return false;

        return true;
    }

    // Limpiar string de caracteres problemáticos
    sanitizeString(str) {
        if (typeof str !== 'string') return '';
        
        // Remover caracteres de control y caracteres especiales problemáticos
        return str.replace(/[\x00-\x1F\x7F-\x9F]/g, '')
                  .replace(/[^\p{L}\p{N}\s@._-]/gu, '')
                  .trim();
    }

    // Limpiar datos antes de enviar
    sanitizeLoginData(email, password) {
        return {
            email: this.sanitizeString(email).toLowerCase(),
            password: password // No sanitizar password para no alterar la autenticación
        };
    }

    // Login mejorado con manejo de errores de patrón
    async login(email, password) {
        // Limpiar storage antes del login
        this.cleanStorageData();

        // Sanitizar datos
        const cleanData = this.sanitizeLoginData(email, password);

        // Validaciones básicas
        if (!cleanData.email || !cleanData.password) {
            throw new Error('Email y contraseña son requeridos');
        }

        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(cleanData.email)) {
            throw new Error('Formato de email inválido');
        }

        let lastError;
        
        // Intentar login con reintentos
        for (let attempt = 1; attempt <= this.maxRetries; attempt++) {
            try {
                console.log(`🚀 Intento de login ${attempt}/${this.maxRetries}`);
                
                const response = await fetch(this.apiBaseUrl + 'login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(cleanData)
                });

                console.log('📡 Response status:', response.status);

                // Obtener texto crudo primero
                const responseText = await response.text();
                console.log('📄 Response text:', responseText.substring(0, 200) + '...');

                // Intentar parsear JSON con manejo de errores mejorado
                let data;
                try {
                    // Limpiar respuesta de posibles caracteres problemáticos
                    const cleanResponseText = responseText
                        .replace(/[\x00-\x1F\x7F]/g, '') // Remover caracteres de control
                        .trim();

                    // Verificar que es JSON válido
                    if (!cleanResponseText.startsWith('{') && !cleanResponseText.startsWith('[')) {
                        throw new Error('Respuesta no es JSON válido');
                    }

                    data = JSON.parse(cleanResponseText);
                    console.log('✅ JSON parseado exitosamente');
                    
                } catch (parseError) {
                    console.error('❌ Error parseando JSON:', parseError);
                    
                    // Si falla el parseo, puede ser error de patrón en la respuesta
                    if (responseText.includes('pattern')) {
                        throw new Error('Error de validación de patrón en el servidor. Intenta con credenciales más simples.');
                    } else if (responseText.includes('<html>') || responseText.includes('<!DOCTYPE')) {
                        throw new Error('El servidor devolvió HTML en lugar de JSON. Verifica la configuración.');
                    } else {
                        throw new Error('Respuesta del servidor con formato inválido.');
                    }
                }

                // Verificar respuesta exitosa
                if (data.success) {
                    console.log('✅ Login exitoso');
                    
                    // Validar token antes de guardarlo
                    if (!this.isValidJWTPattern(data.token)) {
                        throw new Error('Token recibido tiene formato inválido');
                    }

                    // Validar datos de usuario
                    if (!this.isValidUserData(data.user)) {
                        throw new Error('Datos de usuario recibidos son inválidos');
                    }

                    // Guardar datos limpios
                    this.saveAuthData(data.token, data.user);

                    return {
                        success: true,
                        user: data.user,
                        token: data.token
                    };
                } else {
                    console.error('❌ Login falló:', data.message);
                    throw new Error(data.message || 'Error al iniciar sesión');
                }

            } catch (error) {
                lastError = error;
                console.error(`❌ Intento ${attempt} falló:`, error.message);
                
                // Si es el último intento o error específico, lanzar error
                if (attempt === this.maxRetries || 
                    error.message.includes('pattern') ||
                    error.message.includes('Credenciales incorrectas')) {
                    throw error;
                }

                // Esperar antes del siguiente intento
                await this.delay(this.retryDelay * attempt);
            }
        }

        throw lastError || new Error('Error desconocido en el login');
    }

    // Guardar datos de autenticación de forma segura
    saveAuthData(token, user) {
        try {
            // Verificar una vez más antes de guardar
            if (!this.isValidJWTPattern(token)) {
                throw new Error('Token inválido para guardar');
            }

            if (!this.isValidUserData(user)) {
                throw new Error('Datos de usuario inválidos para guardar');
            }

            // Limpiar datos de usuario antes de guardar
            const cleanUser = {
                id: parseInt(user.id),
                nombre: this.sanitizeString(user.nombre || ''),
                apellido: this.sanitizeString(user.apellido || ''),
                email: this.sanitizeString(user.email || ''),
                rol: this.sanitizeString(user.rol || ''),
                estado: this.sanitizeString(user.estado || 'activo'),
                telefono: this.sanitizeString(user.telefono || ''),
                avatar: this.sanitizeString(user.avatar || '')
            };

            localStorage.setItem('clúster_token', token);
            localStorage.setItem('clúster_user', JSON.stringify(cleanUser));

            console.log('💾 Datos guardados correctamente');

        } catch (error) {
            console.error('Error guardando datos:', error);
            throw new Error('Error guardando datos de sesión');
        }
    }

    // Limpiar todo el storage
    clearAllStorage() {
        try {
            localStorage.removeItem('clúster_token');
            localStorage.removeItem('clúster_user');
            sessionStorage.clear();
            console.log('🧹 Storage limpiado completamente');
        } catch (error) {
            console.error('Error limpiando storage:', error);
        }
    }

    // Utilidad para delay
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Verificar si hay sesión válida
    hasValidSession() {
        try {
            const token = localStorage.getItem('clúster_token');
            const userStr = localStorage.getItem('clúster_user');

            if (!token || !userStr) return false;

            if (!this.isValidJWTPattern(token)) {
                this.clearAllStorage();
                return false;
            }

            const user = JSON.parse(userStr);
            if (!this.isValidUserData(user)) {
                this.clearAllStorage();
                return false;
            }

            return true;

        } catch (error) {
            console.error('Error verificando sesión:', error);
            this.clearAllStorage();
            return false;
        }
    }

    // Obtener usuario actual
    getCurrentUser() {
        try {
            if (!this.hasValidSession()) return null;
            
            const userStr = localStorage.getItem('clúster_user');
            return JSON.parse(userStr);
        } catch (error) {
            console.error('Error obteniendo usuario actual:', error);
            return null;
        }
    }

    // Verificar si es admin
    isAdmin() {
        const user = this.getCurrentUser();
        return user && user.rol === 'admin';
    }
}

// Instancia global
window.clautAuth = new ClústerAuthManager();

// Auto-limpiar storage al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 ClústerAuthManager inicializado');
    window.clautAuth.cleanStorageData();
});

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ClústerAuthManager;
}