/**
 * Auth Manager (auth.js)
 * Expone métodos para comprobaciones de sesión globales y decodificación de perfiles de usuario.
 */
import { API } from './api.js';

export const Auth = {
    /**
     * Valida la sesión de usuario de forma asíncrona enviando petición a validate-session
     */
    async validateSession() {
        try {
            const result = await API.get('/auth/validate-session.php');
            if (result.success && result.data && result.data.valid) {
                // Sincronizar datos globales a localStorage para fácil acceso síncrono si se necesita
                localStorage.setItem('user_info', JSON.stringify({
                    id: result.data.user_id,
                    rol: result.data.rol || 'empleado',
                    email: result.data.email
                }));
                return true;
            }
            return false;
        } catch (e) {
            console.error('Session validation failing:', e);
            return false;
        }
    },

    /**
     * Terminar la sesión
     */
    async logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_info');
        window.location.href = '/pages/sign-in.html';
    },

    /**
     * Fuerza el redireccionamiento para paneles protegidos
     */
    async requireAuth() {
        const isValid = await this.validateSession();
        if (!isValid) {
            this.logout();
        }
    }
};
