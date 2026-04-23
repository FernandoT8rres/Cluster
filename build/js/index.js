// file: build/js/index.js
import { API } from './modules/api.js';
import { UI } from './modules/ui.js';
import { Auth } from './modules/auth.js';

// Exponer globalmente para retrocompatibilidad con scripts en línea
window.ClautAPI = API;
window.ClautUI = UI;
window.ClautAuth = Auth;

// Inicialización de la aplicación
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 Claut Core v2 initialized');
    
    // Auto-validar sesión si el archivo auth-session.js no está haciendo el trabajo principal
    // o integrarlo con el flujo existente
    try {
        if (!window.location.pathname.includes('sign-in') && !window.location.pathname.includes('sign-up')) {
            await Auth.validateSession();
        }
    } catch(e) {
        console.error('Core Init Error', e);
    }
});
