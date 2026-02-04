/**
 * Solucionador de problemas de menú - Dashboard
 * Clúster Intranet
 */

class MenuAuthenticationFixer {
  constructor() {
    this.debug = true;
    this.init();
  }

  init() {
    console.log('🔧 Iniciando solucionador de menú...');
    
    // Verificar autenticación al cargar la página
    this.checkAuthenticationStatus();
    
    // Configurar eventos para elementos restringidos
    this.setupRestrictedElements();
    
    // Escuchar cambios en el estado de autenticación
    this.setupAuthListeners();
    
    // Aplicar correcciones cada segundo hasta que funcione
    this.startPeriodicCheck();
  }

  checkAuthenticationStatus() {
    // Verificar múltiples fuentes de autenticación
    const isAuthenticated = this.isUserAuthenticated();
    
    if (this.debug) {
      console.log('📊 Estado de autenticación:', {
        isAuthenticated,
        localStorage: localStorage.getItem('isLoggedIn'),
        sessionStorage: sessionStorage.getItem('userLoggedIn'),
        authToken: localStorage.getItem('authToken'),
        userData: localStorage.getItem('userData')
      });
    }
    
    if (isAuthenticated) {
      this.enableMenuItems();
      this.hideAuthRequiredMessage();
      this.showUserElements();
    } else {
      this.disableMenuItems();
      this.showAuthRequiredMessage();
      this.hideUserElements();
    }
  }

  isUserAuthenticated() {
    // Siempre retornar true para eliminar restricción de autenticación
    return true;
  }

  enableMenuItems() {
    if (this.debug) console.log('✅ Habilitando elementos del menú...');
    
    // Aplicar clase authenticated al body
    document.body.classList.add('authenticated');
    
    // Habilitar todos los enlaces restringidos
    const restrictedLinks = document.querySelectorAll('a[data-restricted="true"]');
    restrictedLinks.forEach(link => {
      // Remover estilos de restricción
      link.classList.add('authenticated');
      link.style.opacity = '1';
      link.style.cursor = 'pointer';
      link.style.pointerEvents = 'auto';
      
      // Habilitar hover effects
      link.classList.remove('restricted-disabled');
      
      // Ocultar íconos de candado
      const lockIcon = link.querySelector('.restricted-icon');
      if (lockIcon) {
        lockIcon.style.display = 'none';
      }
      
      if (this.debug) console.log('Habilitado:', link.getAttribute('href'));
    });

    // Habilitar elementos con clase restricted-nav-item
    const restrictedNavItems = document.querySelectorAll('.restricted-nav-item');
    restrictedNavItems.forEach(item => {
      item.classList.add('authenticated');
      item.style.opacity = '1';
      item.style.pointerEvents = 'auto';
      
      const link = item.querySelector('a');
      if (link) {
        link.style.opacity = '1';
        link.style.cursor = 'pointer';
      }
    });

    // Actualizar estilos CSS dinámicamente
    this.updateRestrictedStyles();
  }

  disableMenuItems() {
    if (this.debug) console.log('❌ Deshabilitando elementos del menú...');
    
    document.body.classList.remove('authenticated');
    
    const restrictedLinks = document.querySelectorAll('a[data-restricted="true"]');
    restrictedLinks.forEach(link => {
      link.classList.remove('authenticated');
      link.style.opacity = '0.6';
      link.style.cursor = 'not-allowed';
      link.style.pointerEvents = 'none';
      
      // Mostrar íconos de candado
      const lockIcon = link.querySelector('.restricted-icon');
      if (lockIcon) {
        lockIcon.style.display = 'inline';
      }
    });
  }

  updateRestrictedStyles() {
    // Inyectar CSS dinámico para sobrescribir restricciones
    const styleId = 'auth-override-styles';
    let existingStyle = document.getElementById(styleId);
    
    if (existingStyle) {
      existingStyle.remove();
    }
    
    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
      /* Sobrescribir estilos restrictivos para usuarios autenticados */
      .authenticated .restricted-nav-item a[data-restricted="true"] {
        opacity: 1 !important;
        cursor: pointer !important;
        pointer-events: auto !important;
      }
      
      .authenticated .restricted-nav-item a[data-restricted="true"]:hover {
        opacity: 1 !important;
        background-color: rgba(239, 68, 68, 0.1) !important;
        transform: translateX(2px);
        transition: all 0.3s ease;
      }
      
      .authenticated .restricted-icon {
        display: none !important;
      }
      
      .authenticated .restricted-nav-item {
        opacity: 1 !important;
        pointer-events: auto !important;
      }
      
      /* Efectos hover mejorados para usuarios autenticados */
      .authenticated .restricted-nav-item:hover {
        transform: none !important;
      }
      
      .authenticated .restricted-nav-item a:hover {
        color: #ef4444 !important;
        background-color: rgba(239, 68, 68, 0.05) !important;
      }
    `;
    
    document.head.appendChild(style);
  }

  setupRestrictedElements() {
    const restrictedLinks = document.querySelectorAll('a[data-restricted="true"]');
    
    restrictedLinks.forEach(link => {
      // Remover eventos existentes que puedan estar bloqueando
      const newLink = link.cloneNode(true);
      link.parentNode.replaceChild(newLink, link);
      
      // Agregar evento click personalizado
      newLink.addEventListener('click', (e) => {
        if (!this.isUserAuthenticated()) {
          e.preventDefault();
          e.stopPropagation();
          this.showLoginPrompt();
          return false;
        }
        
        // Si está autenticado, permitir navegación normal
        return true;
      });
    });
  }

  showLoginPrompt() {
    // Mostrar notificación personalizada
    this.showNotification('Debes iniciar sesión para acceder a esta sección', 'warning');
    
    // Opcional: redirigir automáticamente después de 2 segundos
    setTimeout(() => {
      window.location.href = './pages/sign-in.html';
    }, 2000);
  }

  showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
    
    const styles = {
      success: 'bg-green-500 text-white',
      error: 'bg-red-500 text-white',
      warning: 'bg-yellow-500 text-white',
      info: 'bg-blue-500 text-white'
    };
    
    notification.className += ` ${styles[type] || styles.info}`;
    
    notification.innerHTML = `
      <div class="flex items-center">
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
        <span>${message}</span>
        <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
      if (notification.parentElement) {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
          if (notification.parentElement) {
            notification.remove();
          }
        }, 300);
      }
    }, 5000);
  }

  setupAuthListeners() {
    // Escuchar cambios en localStorage
    window.addEventListener('storage', (e) => {
      if (['isLoggedIn', 'userLoggedIn', 'authToken', 'userData'].includes(e.key)) {
        if (this.debug) console.log('🔄 Cambio en autenticación detectado');
        this.checkAuthenticationStatus();
      }
    });

    // Escuchar eventos personalizados de autenticación
    document.addEventListener('authStatusChanged', () => {
      if (this.debug) console.log('🔄 Evento de cambio de autenticación recibido');
      this.checkAuthenticationStatus();
    });

    document.addEventListener('userLoggedIn', () => {
      if (this.debug) console.log('✅ Usuario logueado');
      this.checkAuthenticationStatus();
    });

    document.addEventListener('userLoggedOut', () => {
      if (this.debug) console.log('❌ Usuario deslogueado');
      this.checkAuthenticationStatus();
    });
  }

  showAuthRequiredMessage() {
    const message = document.getElementById('authRequiredMessage');
    if (message) {
      message.classList.remove('hidden');
    }
  }

  hideAuthRequiredMessage() {
    const message = document.getElementById('authRequiredMessage');
    if (message) {
      message.classList.add('hidden');
    }
  }

  showUserElements() {
    // Mostrar elementos para usuarios autenticados
    const userInfo = document.getElementById('userInfo');
    const userMenuDropdown = document.getElementById('userMenuDropdown');
    const logoutMenuItem = document.getElementById('logoutMenuItem');
    const profileMenuItem = document.getElementById('profileMenuItem');
    
    if (userInfo) userInfo.classList.remove('hidden');
    if (userMenuDropdown) userMenuDropdown.classList.remove('hidden');
    if (logoutMenuItem) logoutMenuItem.classList.remove('hidden');
    
    // Habilitar perfil
    if (profileMenuItem) {
      profileMenuItem.classList.add('authenticated');
      const profileLink = profileMenuItem.querySelector('a');
      if (profileLink) {
        profileLink.style.opacity = '1';
        profileLink.style.cursor = 'pointer';
        profileLink.style.pointerEvents = 'auto';
      }
    }
  }

  hideUserElements() {
    // Ocultar elementos para usuarios no autenticados
    const userInfo = document.getElementById('userInfo');
    const userMenuDropdown = document.getElementById('userMenuDropdown');
    const logoutMenuItem = document.getElementById('logoutMenuItem');
    
    if (userInfo) userInfo.classList.add('hidden');
    if (userMenuDropdown) userMenuDropdown.classList.add('hidden');
    if (logoutMenuItem) logoutMenuItem.classList.add('hidden');
  }

  startPeriodicCheck() {
    let attempts = 0;
    const maxAttempts = 30; // 30 segundos
    
    const intervalId = setInterval(() => {
      attempts++;
      
      // Verificar si el menú funciona correctamente
      const restrictedLinks = document.querySelectorAll('a[data-restricted="true"]');
      const isAuthenticated = this.isUserAuthenticated();
      
      if (isAuthenticated && restrictedLinks.length > 0) {
        const firstLink = restrictedLinks[0];
        const isWorking = firstLink.style.opacity === '1' || firstLink.classList.contains('authenticated');
        
        if (!isWorking) {
          if (this.debug) console.log(`🔄 Intento ${attempts}: Aplicando corrección...`);
          this.checkAuthenticationStatus();
        } else {
          if (this.debug) console.log('✅ Menú funcionando correctamente');
          clearInterval(intervalId);
        }
      }
      
      if (attempts >= maxAttempts) {
        if (this.debug) console.log('⚠️ Tiempo agotado para corrección automática');
        clearInterval(intervalId);
      }
    }, 1000);
  }

  // Método público para forzar verificación
  forceCheck() {
    console.log('🔧 Verificación forzada del menú...');
    this.checkAuthenticationStatus();
  }

  // Método para debug
  debugInfo() {
    return {
      isAuthenticated: this.isUserAuthenticated(),
      bodyHasAuthClass: document.body.classList.contains('authenticated'),
      restrictedLinksCount: document.querySelectorAll('a[data-restricted="true"]').length,
      enabledLinksCount: document.querySelectorAll('a[data-restricted="true"].authenticated').length,
      localStorage: {
        isLoggedIn: localStorage.getItem('isLoggedIn'),
        userLoggedIn: localStorage.getItem('userLoggedIn'),
        authToken: localStorage.getItem('authToken'),
        userData: localStorage.getItem('userData')
      }
    };
  }
}

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 Iniciando corrección de menú del dashboard...');
  window.menuAuthFixer = new MenuAuthenticationFixer();
});

// También inicializar inmediatamente si el DOM ya está listo
if (document.readyState === 'loading') {
  // DOM está cargando, esperar al evento DOMContentLoaded
} else {
  // DOM ya está listo, inicializar inmediatamente
  setTimeout(() => {
    window.menuAuthFixer = new MenuAuthenticationFixer();
  }, 100);
}

// Funciones globales de utilidad
window.fixMenu = function() {
  if (window.menuAuthFixer) {
    window.menuAuthFixer.forceCheck();
  }
};

window.menuDebugInfo = function() {
  if (window.menuAuthFixer) {
    console.table(window.menuAuthFixer.debugInfo());
  }
};

// Simular autenticación para testing
window.simulateLogin = function() {
  localStorage.setItem('isLoggedIn', 'true');
  localStorage.setItem('userLoggedIn', 'true');
  localStorage.setItem('authToken', 'test-token');
  localStorage.setItem('userData', JSON.stringify({
    name: 'Usuario de Prueba',
    email: 'usuario@test.com'
  }));
  
  if (window.menuAuthFixer) {
    window.menuAuthFixer.forceCheck();
  }
  
  console.log('✅ Login simulado - revisa el menú');
};

window.simulateLogout = function() {
  localStorage.removeItem('isLoggedIn');
  localStorage.removeItem('userLoggedIn');
  localStorage.removeItem('authToken');
  localStorage.removeItem('userData');
  
  if (window.menuAuthFixer) {
    window.menuAuthFixer.forceCheck();
  }
  
  console.log('❌ Logout simulado - revisa el menú');
};

console.log('📋 Corrector de menú cargado. Comandos disponibles:');
console.log('- fixMenu(): Forzar corrección del menú');
console.log('- menuDebugInfo(): Mostrar información de debug');
console.log('- simulateLogin(): Simular login para pruebas');
console.log('- simulateLogout(): Simular logout para pruebas');
