// PARCHE PARA CORREGIR EL SISTEMA DE AUTENTICACIÓN EN index.js
// Versión: 1.1 - Fecha: 2025-08-20

// Sobrescribir el método applyRestrictions de AuthManager
if (typeof AuthManager !== 'undefined' && AuthManager.prototype) {
  console.log('🔧 Aplicando parche de autenticación...');
  
  AuthManager.prototype.applyRestrictions = function() {
    console.log('🔧 Aplicando restricciones de acceso...');
    const restrictedItems = document.querySelectorAll('[data-restricted="true"]');
    
    console.log(`📋 Encontrados ${restrictedItems.length} elementos restringidos`);
    console.log(`🔑 Estado de autenticación: ${this.isAuthenticated ? 'AUTENTICADO' : 'NO AUTENTICADO'}`);
    
    restrictedItems.forEach((item, index) => {
      const lockIcon = item.querySelector('.restricted-icon');
      const parentLi = item.closest('li.restricted-nav-item');
      
      console.log(`🔍 Procesando elemento ${index + 1}:`, item.textContent.trim());
      
      if (this.isAuthenticated) {
        // QUITAR RESTRICCIONES - Usuario autenticado
        console.log(`✅ Habilitando elemento: ${item.textContent.trim()}`);
        
        // Remover clases de restricción del enlace
        item.classList.remove('opacity-50', 'cursor-not-allowed');
        item.style.pointerEvents = 'auto';
        item.style.cursor = 'pointer';
        
        // Remover clases de restricción del li padre
        if (parentLi) {
          parentLi.classList.remove('opacity-50', 'cursor-not-allowed');
          parentLi.style.pointerEvents = 'auto';
          parentLi.style.cursor = 'pointer';
          
          // Remover event listeners de restricción si existen
          parentLi.onclick = null;
          
          // Marcar como autenticado
          item.classList.add('authenticated');
          parentLi.classList.add('authenticated');
        }
        
        // Ocultar icono de candado
        if (lockIcon) {
          lockIcon.classList.add('hidden');
        }
        
      } else {
        // APLICAR RESTRICCIONES - Usuario no autenticado
        console.log(`🔒 Restringiendo elemento: ${item.textContent.trim()}`);
        
        // Aplicar clases de restricción al enlace
        item.classList.add('opacity-50', 'cursor-not-allowed');
        item.style.pointerEvents = 'none';
        item.style.cursor = 'not-allowed';
        
        // Aplicar clases de restricción al li padre
        if (parentLi) {
          parentLi.classList.add('opacity-50', 'cursor-not-allowed');
          parentLi.style.pointerEvents = 'auto'; // Permitir click para mostrar notificación
          parentLi.style.cursor = 'not-allowed';
          
          // Quitar clase de autenticado
          item.classList.remove('authenticated');
          parentLi.classList.remove('authenticated');
          
          // Agregar click handler al contenedor padre
          parentLi.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.handleRestrictedClick(e);
            return false;
          };
        }
        
        // Mostrar icono de candado
        if (lockIcon) {
          lockIcon.classList.remove('hidden');
        }
      }
    });
    
    console.log('✅ Restricciones aplicadas correctamente');
  };

  // Sobrescribir el método handleRestrictedClick para mejor manejo
  AuthManager.prototype.handleRestrictedClick = function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('🚫 Click en elemento restringido detectado');
    
    // Mostrar notificación
    this.showNotification('Debes iniciar sesión para acceder a esta sección', 'warning');
    
    return false;
  };

  // Agregar método para refresh manual
  AuthManager.prototype.refresh = async function() {
    console.log('🔄 Refrescando estado de autenticación...');
    await this.checkAuthentication();
    this.updateUI();
    this.applyRestrictions();
  };

  console.log('✅ Parche de autenticación aplicado exitosamente');
}

// Funciones de testing para desarrolladores
window.simularLogin = function(nombre = 'Usuario Test', apellido = 'Apellido', rol = 'empleado') {
  console.log('🧪 Simulando login para testing...');
  
  const userData = {
    id: 1,
    nombre: nombre,
    apellido: apellido,
    email: 'test@claut.mx',
    rol: rol,
    avatar: './assets/img/team-2.jpg'
  };
  
  const token = 'test_token_' + Date.now();
  
  localStorage.setItem('clúster_token', token);
  localStorage.setItem('clúster_user', JSON.stringify(userData));
  
  // Refrescar AuthManager si existe
  if (window.authManager) {
    window.authManager.refresh();
  }
  
  console.log('✅ Login simulado completado');
};

window.simularLogout = function() {
  console.log('🧪 Simulando logout para testing...');
  
  localStorage.removeItem('clúster_token');
  localStorage.removeItem('clúster_user');
  
  // Refrescar AuthManager si existe
  if (window.authManager) {
    window.authManager.refresh();
  }
  
  console.log('✅ Logout simulado completado');
};

// Información de debugging
console.log('🛠️ Funciones de testing disponibles:');
console.log('   - simularLogin("Nombre", "Apellido", "rol") - Simula login');
console.log('   - simularLogout() - Simula logout');
console.log('   - authManager.refresh() - Refresca el estado (si authManager existe)');
