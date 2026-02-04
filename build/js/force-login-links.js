// force-login-links.js
// Script que fuerza el funcionamiento correcto de los enlaces de login

console.log('🔧 Forzando funcionamiento de enlaces de login...');

// Función que se ejecuta cada 500ms para asegurar que los enlaces funcionen
function enforceLoginLinks() {
    // Buscar todos los enlaces de login
    const loginLinks = document.querySelectorAll('a[href*="sign-in"], a[href*="pages/sign-in"]');
    
    loginLinks.forEach((link, index) => {
        // Asegurar que el href esté correcto
        if (!link.href.endsWith('pages/sign-in.html')) {
            link.href = './pages/sign-in.html';
        }
        
        // Remover cualquier preventDefault que pueda estar interfiriendo
        link.onclick = function(e) {
            console.log(`🖱️ Click en enlace de login ${index + 1} - Navegando a:`, this.href);
            // Permitir navegación normal
            return true;
        };
        
        // Agregar atributos adicionales para asegurar funcionamiento
        link.setAttribute('data-login-link', 'true');
        
        // Forzar que sea clickeable
        link.style.pointerEvents = 'auto';
        link.style.cursor = 'pointer';
    });
}

// Función específica para el botón de "Iniciar Sesión" del navbar
function fixNavbarLoginButton() {
    const navbarLoginBtn = document.querySelector('#loginMenuItem a');
    if (navbarLoginBtn) {
        navbarLoginBtn.href = './pages/sign-in.html';
        navbarLoginBtn.onclick = function(e) {
            console.log('🖱️ Click en botón de login del navbar');
            window.location.href = './pages/sign-in.html';
            return false; // Prevenir cualquier comportamiento por defecto
        };
        console.log('✅ Botón de login del navbar configurado');
    }
}

// Función específica para los botones del mensaje de acceso restringido
function fixAccessRestrictedButtons() {
    const restrictedBtns = document.querySelectorAll('#authRequiredMessage a[href*="sign-in"]');
    restrictedBtns.forEach(btn => {
        btn.href = './pages/sign-in.html';
        btn.onclick = function(e) {
            console.log('🖱️ Click en botón de acceso restringido');
            window.location.href = './pages/sign-in.html';
            return false;
        };
    });
    
    if (restrictedBtns.length > 0) {
        console.log(`✅ ${restrictedBtns.length} botones de acceso restringido configurados`);
    }
}

// Función que intercepta todos los clicks en elementos que contengan "sign-in"
function interceptAllLoginClicks() {
    document.addEventListener('click', function(e) {
        const target = e.target;
        
        // Verificar si el click fue en un enlace de login o en un elemento dentro de uno
        let loginLink = null;
        
        if (target.href && target.href.includes('sign-in')) {
            loginLink = target;
        } else {
            // Buscar en elementos padre
            loginLink = target.closest('a[href*="sign-in"]');
        }
        
        if (loginLink) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🚀 Interceptado click en enlace de login - Navegando...');
            window.location.href = './pages/sign-in.html';
            
            return false;
        }
    }, true); // Usar capture phase para interceptar antes que otros handlers
}

// Función principal que ejecuta todas las correcciones
function forceLoginLinkFunctionality() {
    enforceLoginLinks();
    fixNavbarLoginButton();
    fixAccessRestrictedButtons();
}

// Ejecutar inmediatamente
forceLoginLinkFunctionality();

// Ejecutar cada 500ms para asegurar que siempre funcione
const intervalId = setInterval(forceLoginLinkFunctionality, 500);

// Interceptar todos los clicks relacionados con login
interceptAllLoginClicks();

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(forceLoginLinkFunctionality, 100);
    setTimeout(forceLoginLinkFunctionality, 500);
    setTimeout(forceLoginLinkFunctionality, 1000);
});

// Función para detener el interval (útil para debugging)
window.stopLoginLinkForcing = function() {
    clearInterval(intervalId);
    console.log('⏹️ Forzado de enlaces de login detenido');
};

console.log('✅ Sistema de forzado de enlaces de login activado');
console.log('💡 Usa stopLoginLinkForcing() para detener si es necesario');
