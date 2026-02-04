    // Funciones específicas para el dashboard

    /**
     * Cargar empresas en convenio desde la base de datos
     */
    // Función mejorada para cargar empresas en convenio en el index
async function cargarEmpresasConvenio() {
    const tableBody = document.getElementById('companiesTableBody');
    const noCompaniesMessage = document.getElementById('noCompaniesMessage');
    const table = document.getElementById('companiesTable');
    
    if (!tableBody) return;
    
    try {
        console.log('🏭 Cargando empresas en convenio...');
        
        const response = await fetch('./api/empresas_convenio.php?activo=1&limit=5&orderBy=created_at&order=DESC');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Respuesta de empresas:', result);
        
        if (result.success && result.data && result.data.length > 0) {
            tableBody.innerHTML = '';
            
            result.data.forEach((empresa, index) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors';
                
                const estadoColor = empresa.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                const estadoTexto = empresa.activo ? 'Activo' : 'Inactivo';
                const descuentoTexto = empresa.descuento ? `${empresa.descuento}%` : 'N/A';
                const logoUrl = empresa.logo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(empresa.nombre_empresa) + '&background=random';
                
                row.innerHTML = `
                    <td class="p-3 align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <div class="flex items-center">
                            <img src="${logoUrl}" alt="${empresa.nombre_empresa}" 
                                 class="w-10 h-10 rounded-lg object-cover mr-3"
                                 onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random'">
                            <div>
                                <h6 class="mb-0 text-sm font-semibold leading-normal dark:text-white">
                                    ${empresa.nombre_empresa}
                                </h6>
                                ${empresa.sitio_web ? `
                                    <a href="${empresa.sitio_web}" target="_blank" class="text-xs text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-external-link-alt mr-1"></i>Sitio web
                                    </a>
                                ` : `
                                    <span class="text-xs text-gray-500">${empresa.email || 'Sin contacto'}</span>
                                `}
                            </div>
                        </div>
                    </td>
                    <td class="p-3 text-center align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            ${empresa.categoria || 'General'}
                        </span>
                    </td>
                    <td class="p-3 text-center align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <span class="text-sm font-bold ${empresa.descuento ? 'text-green-600' : 'text-gray-500'}">
                            ${descuentoTexto}
                        </span>
                        ${empresa.destacado ? `
                            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-star mr-1"></i>Destacado
                            </span>
                        ` : ''}
                    </td>
                    <td class="p-3 text-center align-middle bg-transparent border-b whitespace-nowrap dark:border-white/40">
                        <span class="px-2 py-1 text-xs rounded-full ${estadoColor}">
                            ${estadoTexto}
                        </span>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
            
            if (table) table.classList.remove('hidden');
            if (noCompaniesMessage) noCompaniesMessage.classList.add('hidden');
            
            console.log(`✅ ${result.data.length} empresas cargadas`);
            
        } else {
            console.log('⚠️ No hay empresas en convenio en la base de datos');
            
            if (table) table.classList.add('hidden');
            if (noCompaniesMessage) noCompaniesMessage.classList.remove('hidden');
        }
        
    } catch (error) {
        console.error('❌ Error cargando empresas:', error);
        
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" class="p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i>
                    <p class="text-gray-600">Error al cargar las empresas</p>
                    <button onclick="cargarEmpresasConvenio()" class="mt-3 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-retry mr-2"></i>Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
}

    // ==================== FUNCIONES PARA EMPRESAS DESTACADAS ====================
    
    /**
     * Cargar empresas destacadas desde la base de datos
     */
    async function cargarEmpresasDestacadas() {
      try {
        // Crear instancia del widget si no existe
        if (typeof EmpresasDestacadasWidget !== 'undefined') {
          const widget = new EmpresasDestacadasWidget('empresasDestacadasContainer');
          await widget.init();
          window.empresasDestacadasWidget = widget; // Guardar referencia global
        } else {
          console.warn('Widget de empresas destacadas no disponible');
          // Cargar directamente sin el widget
          await cargarEmpresasDestacadasDirecto();
        }
      } catch (error) {
        console.error('Error cargando empresas destacadas:', error);
      }
    }
    
    /**
     * Actualizar empresas destacadas
     */
    async function actualizarEmpresasDestacadas() {
      try {
        showLoading();
        if (window.empresasDestacadasWidget) {
          await window.empresasDestacadasWidget.actualizar();
        } else {
          await cargarEmpresasDestacadas();
        }
        hideLoading();
        mostrarNotificacion('Empresas destacadas actualizadas', 'success');
      } catch (error) {
        hideLoading();
        mostrarNotificacion('Error al actualizar empresas destacadas', 'error');
      }
    }
    
    /**
     * Cargar empresas destacadas directamente (fallback)
     */
    async function cargarEmpresasDestacadasDirecto() {
      const container = document.getElementById('empresasDestacadasContainer');
      if (!container) return;
      
      try {
        const response = await fetch('api/empresas_convenio.php?destacado=1&activo=1&limit=8');
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
          container.innerHTML = result.data.map(empresa => {
            const logoUrl = empresa.logo_url || 
              `https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=128`;
            
            return `
              <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                <div class="h-32 bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center">
                  <img src="${logoUrl}" alt="${empresa.nombre_empresa}" 
                       class="w-20 h-20 rounded-full bg-white p-2 shadow-lg"
                       onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random'">
                </div>
                <div class="p-4">
                  <h4 class="font-bold text-gray-800 mb-1">${empresa.nombre_empresa}</h4>
                  ${empresa.descuento > 0 ? 
                    `<span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                      ${empresa.descuento}% Descuento
                    </span>` : ''}
                  <p class="text-gray-600 text-sm mt-2">${empresa.descripcion || 'Empresa colaboradora'}</p>
                  ${empresa.sitio_web ? 
                    `<a href="${empresa.sitio_web}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">
                      Visitar <i class="fas fa-external-link-alt ml-1"></i>
                    </a>` : ''}
                </div>
              </div>
            `;
          }).join('');
        } else {
          container.innerHTML = `
            <div class="col-span-full text-center py-12">
              <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
              <p class="text-gray-500 mb-4">No hay empresas destacadas</p>
              <a href="demo_empresas.html" class="inline-block px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                <i class="fas fa-plus mr-2"></i>Gestionar Empresas
              </a>
            </div>
          `;
        }
      } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `
          <div class="col-span-full text-center py-12">
            <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i>
            <p class="text-gray-600">Error al cargar empresas destacadas</p>
            <button onclick="cargarEmpresasDestacadas()" class="mt-3 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
              <i class="fas fa-retry mr-2"></i>Reintentar
            </button>
          </div>
        `;
      }
    }

    // Función para refrescar la tabla de empresas
    async function refreshCompaniesTable() {
      try {
        showLoading();
        await cargarEmpresasConvenio();
        hideLoading();
        mostrarNotificacion('Tabla actualizada correctamente', 'success');
      } catch (error) {
        hideLoading();
        mostrarNotificacion('Error al actualizar la tabla', 'error');
      }
    }

    // ==================== FUNCIONES PARA EMPRESAS DESTACADAS ====================
    
    /**
     * Cargar empresas destacadas desde la base de datos
     */
    async function cargarEmpresasDestacadas() {
      try {
        // Crear instancia del widget si no existe
        if (typeof EmpresasDestacadasWidget !== 'undefined') {
          const widget = new EmpresasDestacadasWidget('empresasDestacadasContainer');
          await widget.init();
          window.empresasDestacadasWidget = widget; // Guardar referencia global
        } else {
          console.warn('Widget de empresas destacadas no disponible');
          // Cargar directamente sin el widget
          await cargarEmpresasDestacadasDirecto();
        }
      } catch (error) {
        console.error('Error cargando empresas destacadas:', error);
      }
    }
    
    /**
     * Actualizar empresas destacadas
     */
    async function actualizarEmpresasDestacadas() {
      try {
        showLoading();
        if (window.empresasDestacadasWidget) {
          await window.empresasDestacadasWidget.actualizar();
        } else {
          await cargarEmpresasDestacadas();
        }
        hideLoading();
        mostrarNotificacion('Empresas destacadas actualizadas', 'success');
      } catch (error) {
        hideLoading();
        mostrarNotificacion('Error al actualizar empresas destacadas', 'error');
      }
    }
    
    /**
     * Cargar empresas destacadas directamente (fallback)
     */
    async function cargarEmpresasDestacadasDirecto() {
      const container = document.getElementById('empresasDestacadasContainer');
      if (!container) return;
      
      try {
        const response = await fetch('api/empresas_convenio.php?destacado=1&activo=1&limit=8');
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
          container.innerHTML = result.data.map(empresa => {
            const logoUrl = empresa.logo_url || 
              `https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=128`;
            
            return `
              <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                <div class="h-32 bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center">
                  <img src="${logoUrl}" alt="${empresa.nombre_empresa}" 
                       class="w-20 h-20 rounded-full bg-white p-2 shadow-lg"
                       onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(empresa.nombre_empresa)}&background=random&size=128'">
                </div>
                <div class="p-4">
                  <h4 class="font-bold text-lg text-gray-800 mb-1 truncate">${empresa.nombre_empresa}</h4>
                  ${empresa.categoria ? 
                    `<span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded mb-2">${empresa.categoria}</span>` : ''}
                  ${empresa.descuento > 0 ? 
                    `<p class="text-green-600 font-bold">${empresa.descuento}% Descuento</p>` : ''}
                  <p class="text-gray-600 text-sm mt-2 line-clamp-2">
                    ${empresa.beneficios || empresa.descripcion || 'Beneficios exclusivos para socios'}
                  </p>
                  ${empresa.sitio_web ? 
                    `<a href="${empresa.sitio_web}" target="_blank" 
                        class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">
                      <i class="fas fa-external-link-alt mr-1"></i>Visitar sitio
                    </a>` : ''}
                </div>
              </div>
            `;
          }).join('');
        } else {
          // Mostrar mensaje de no hay empresas destacadas
          container.innerHTML = `
            <div class="col-span-full">
              <div class="bg-gray-100 rounded-lg p-8 text-center">
                <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">
                  No hay empresas destacadas
                </h3>
                <p class="text-gray-500 mb-4">
                  Las empresas marcadas como destacadas aparecerán aquí.
                </p>
                <a href="demo_empresas.html" 
                   class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                  <i class="fas fa-plus mr-2"></i>Gestionar Empresas
                </a>
              </div>
            </div>
          `;
        }
      } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `
          <div class="col-span-full">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
              <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i>
              <p class="text-red-700">Error al cargar empresas destacadas</p>
              <button onclick="cargarEmpresasDestacadas()" 
                      class="mt-3 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                <i class="fas fa-retry mr-2"></i>Reintentar
              </button>
            </div>
          </div>
        `;
      }
    }

    // Mostrar/ocultar loading spinner
    function showLoading() {
      document.getElementById('loadingSpinner').classList.remove('hidden');
    }

    function hideLoading() {
      document.getElementById('loadingSpinner').classList.add('hidden');
    }

    // Actualizar estadísticas con animación
    function updateStatCard(elementId, value, growth = null) {
      const element = document.getElementById(elementId);
      if (element) {
        // Animar el número
        animateValue(element, 0, value, 1000);
      }

      if (growth !== null) {
        const growthElement = document.getElementById(elementId.replace('stats', '') + 'Growth');
        if (growthElement) {
          growthElement.textContent = `+${growth}%`;
        }
      }
    }

    // Animación de números
    function animateValue(element, start, end, duration) {
      let startTimestamp = null;
      const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const current = Math.floor(progress * (end - start) + start);
        element.textContent = current;
        if (progress < 1) {
          window.requestAnimationFrame(step);
        }
      };
      window.requestAnimationFrame(step);
    }

    // Sistema completo de autenticación y restricciones
    class AuthManager {
      constructor() {
        // No usamos localStorage - datos vienen de la sesión del servidor
        this.token = null;
        this.user = {};
        this.isAuthenticated = false;
        this.init();
      }

      async init() {
        await this.checkAuthentication();
        this.updateUI();
        this.setupEventListeners();
        this.applyRestrictions();
      }

      async checkAuthentication() {
        if (!this.token) {
          this.isAuthenticated = false;
          return;
        }

        try {
          // Por ahora simplemente verificamos si existe el token y el usuario
          // En producción, verificaríamos con el servidor
          if (this.user && this.user.nombre) {
            this.isAuthenticated = true;
          } else {
            this.clearAuth();
          }
        } catch (error) {
          console.error('Error verificando autenticación:', error);
          this.isAuthenticated = false;
        }
      }

      clearAuth() {
        // No usamos localStorage - limpiar solo las variables locales
        this.token = null;
        this.user = {};
        this.isAuthenticated = false;
      }

      updateUI() {
        const welcomeMessage = document.getElementById('welcomeMessage');
        const userInfo = document.getElementById('userInfo');
        const loginMenuItem = document.getElementById('loginMenuItem');
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        const logoutMenuItem = document.getElementById('logoutMenuItem');
        const btnAgregarAnuncio = document.getElementById('btnAgregarAnuncio');
        const authRequiredMessage = document.getElementById('authRequiredMessage');

        // Buscar elementos de login/signup en sidebar
        const sidebarLinks = document.querySelectorAll('aside a');
        let signInLink = null;
        let signUpLink = null;
        
        sidebarLinks.forEach(link => {
          if (link.href && link.href.includes('sign-in.html')) {
            signInLink = link.closest('li');
          }
          if (link.href && link.href.includes('sign-up.html')) {
            signUpLink = link.closest('li');
          }
        });

        if (this.isAuthenticated) {
          // Usuario autenticado
          console.log('Usuario autenticado:', this.user);
          
          if (welcomeMessage) {
            welcomeMessage.innerHTML = `<h6 class="mb-0 font-bold text-white capitalize">Bienvenido, ${this.user.nombre || 'Usuario'}</h6>`;
          }

          // Mostrar info del usuario en el header
          if (userInfo) {
            userInfo.classList.remove('hidden');
            userInfo.classList.add('flex');
            
            const userName = document.getElementById('userName');
            const userRole = document.getElementById('userRole');
            
            if (userName) userName.textContent = `${this.user.nombre} ${this.user.apellido || ''}`.trim();
            if (userRole) userRole.textContent = this.getRoleName(this.user.rol);
          }

          // Ocultar botón de login en navbar
          if (loginMenuItem) loginMenuItem.classList.add('hidden');
          
          // Ocultar opciones de login/registro en sidebar
          if (signInLink) signInLink.classList.add('hidden');
          if (signUpLink) signUpLink.classList.add('hidden');

          // Mostrar menú de usuario y logout
          if (userMenuDropdown) userMenuDropdown.classList.remove('hidden');
          if (logoutMenuItem) logoutMenuItem.classList.remove('hidden');
          
          // Mostrar botón de agregar anuncio
          if (btnAgregarAnuncio) {
            btnAgregarAnuncio.classList.remove('hidden');
          }
          
          // Ocultar mensaje de acceso restringido
          if (authRequiredMessage) {
            authRequiredMessage.classList.add('hidden');
          }

        } else {
          // Usuario no autenticado
          console.log('Usuario NO autenticado');
          
          if (welcomeMessage) {
            welcomeMessage.innerHTML = `<h6 class="mb-0 font-bold text-white capitalize">Bienvenido a Clúster Intranet</h6>`;
          }

          // Ocultar info del usuario
          if (userInfo) {
            userInfo.classList.add('hidden');
          }
          
          // Mostrar botón de login en navbar
          if (loginMenuItem) loginMenuItem.classList.remove('hidden');
          
          // Mostrar opciones de login/registro en sidebar
          if (signInLink) signInLink.classList.remove('hidden');
          if (signUpLink) signUpLink.classList.remove('hidden');

          // Ocultar menú de usuario y logout
          if (userMenuDropdown) userMenuDropdown.classList.add('hidden');
          if (logoutMenuItem) logoutMenuItem.classList.add('hidden');
          
          // Ocultar botón de agregar anuncio
          if (btnAgregarAnuncio) {
            btnAgregarAnuncio.classList.add('hidden');
          }
          
          // Mostrar mensaje de acceso restringido
          if (authRequiredMessage) {
            authRequiredMessage.classList.remove('hidden');
          }
        }
      }

      applyRestrictions() {
        const restrictedItems = document.querySelectorAll('[data-restricted="true"]');
        
        restrictedItems.forEach(item => {
          const lockIcon = item.querySelector('.restricted-icon');
          
          if (this.isAuthenticated) {
            // Quitar restricciones
            item.classList.remove('opacity-50', 'cursor-not-allowed');
            item.style.pointerEvents = 'auto';
            
            // Ocultar icono de candado
            if (lockIcon) lockIcon.classList.add('hidden');
            
          } else {
            // Aplicar restricciones
            item.classList.add('opacity-50', 'cursor-not-allowed');
            item.style.pointerEvents = 'none';
            
            // Mostrar icono de candado
            if (lockIcon) lockIcon.classList.remove('hidden');
            
            // Agregar click handler al contenedor padre
            const parent = item.closest('li');
            if (parent) {
              parent.style.cursor = 'not-allowed';
              parent.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleRestrictedClick(e);
              });
            }
          }
        });
      }

      handleRestrictedClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Mostrar notificación
        this.showNotification('Debes iniciar sesión para acceder a esta sección', 'warning');
      }

      setupEventListeners() {
        // Configurar menú desplegable
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenuContent = document.getElementById('userMenuContent');
        
        if (userMenuButton && userMenuContent) {
          userMenuButton.addEventListener('click', (e) => {
            e.preventDefault();
            userMenuContent.classList.toggle('hidden');
          });

          // Cerrar menú al hacer clic fuera
          document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !userMenuContent.contains(e.target)) {
              userMenuContent.classList.add('hidden');
            }
          });
        }

        // Configurar botones de logout
        const logoutBtn = document.getElementById('logoutBtn');
        const logoutBtnMenu = document.getElementById('logoutBtnMenu');
        
        [logoutBtn, logoutBtnMenu].forEach(btn => {
          if (btn) {
            btn.addEventListener('click', () => this.handleLogout());
          }
        });
      }

      async handleLogout() {
        try {
          // Limpiar datos locales y cerrar sesión en servidor
          this.clearAuth();
          
          // Mostrar notificación
          this.showNotification('Sesión cerrada correctamente', 'success');
          
          // Redirigir al login después de 1 segundo
          setTimeout(() => {
            window.location.href = './pages/sign-in.html?logout=1';
          }, 1000);
        } catch (error) {
          console.error('Error durante logout:', error);
        }
      }

      showNotification(message, type = 'info') {
        if (typeof mostrarNotificacion === 'function') {
          mostrarNotificacion(message, type);
        } else {
          // Implementación básica de notificación
          const container = document.getElementById('notificationContainer');
          if (!container) return;
          
          const notification = document.createElement('div');
          const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 
                          type === 'error' ? 'bg-red-100 border-red-500 text-red-700' :
                          type === 'warning' ? 'bg-yellow-100 border-yellow-500 text-yellow-700' : 
                          'bg-blue-100 border-blue-500 text-blue-700';
          
          notification.className = `${bgColor} border-l-4 p-4 rounded-lg shadow-lg mb-2`;
          notification.innerHTML = `
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-3"></i>
                <p>${message}</p>
              </div>
              ${!this.isAuthenticated && type === 'warning' ? `
                <a href="./pages/sign-in.html" class="ml-4 px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">
                  Iniciar Sesión
                </a>
              ` : ''}
            </div>
          `;
          
          container.appendChild(notification);
          
          setTimeout(() => {
            notification.remove();
          }, 5000);
        }
      }

      getRoleName(rol) {
        const roles = {
          'admin': 'Administrador',
          'empleado': 'Empleado',
          'moderador': 'Moderador',
          'usuario': 'Usuario'
        };
        return roles[rol] || 'Usuario';
      }
    }

    // Reemplazar checkAuthenticationUI con la nueva clase
    async function checkAuthenticationUI() {
      // Verificar autenticación desde el servidor, no localStorage
      const token = null; // No usamos tokens en localStorage
      // Obtener usuario desde la sesión del servidor
      let user = {};
      if (window.authSessionManager && window.authSessionManager.currentUser) {
        user = window.authSessionManager.currentUser;
      }

      const loginMenuItem = document.getElementById('loginMenuItem');
      const logoutMenuItem = document.getElementById('logoutMenuItem');
      const userMenuDropdown = document.getElementById('userMenuDropdown');
      const userInfo = document.getElementById('userInfo');
      const userName = document.getElementById('userName');
      const userRole = document.getElementById('userRole');
      const userAvatar = document.getElementById('userAvatar');
      const welcomeUserName = document.getElementById('welcomeUserName');
      const welcomeMessage = document.getElementById('welcomeMessage');

      if (token && user.nombre) {
        try {
          // Verificar token con el servidor
          const currentUser = await api.getCurrentUser();

          // Usuario autenticado y token válido
          loginMenuItem?.classList.add('hidden');
          logoutMenuItem?.classList.remove('hidden');
          userMenuDropdown?.classList.remove('hidden');

          if (userInfo) {
            userInfo.classList.remove('hidden');
            userInfo.classList.add('flex');
          }

          // Actualizar datos del usuario desde el servidor
          const userData = currentUser.data || user;

          if (userName) userName.textContent = `${userData.nombre} ${userData.apellido || ''}`;
          if (userRole) {
            const rolMap = {
              'admin': 'Administrador',
              'empleado': 'Empleado',
              'moderador': 'Moderador'
            };
            userRole.textContent = rolMap[userData.rol] || 'Usuario';
          }
          if (userAvatar && userData.avatar) userAvatar.src = userData.avatar;

          // Actualizar mensaje de bienvenida
          if (welcomeUserName) {
            welcomeUserName.textContent = userData.nombre;
          }
          if (welcomeMessage) {
            welcomeMessage.classList.remove('hidden');
          }

          // Los datos se mantienen en el servidor, no en localStorage

          // Actualizar nombre en sidebar si es necesario
          updateSidebarUserInfo(userData);
          
          // Mostrar botón de agregar anuncio
          actualizarVisibilidadBotonAgregar();

        } catch (error) {
          console.error('Error verificando token:', error);
          // Token inválido o expirado - usar datos de sesión del servidor
          showAsUnauthenticated();
        }
      } else {
        showAsUnauthenticated();
      }

      function showAsUnauthenticated() {
        // Usuario no autenticado
        loginMenuItem?.classList.remove('hidden');
        logoutMenuItem?.classList.add('hidden');
        userMenuDropdown?.classList.add('hidden');
        if (userInfo) {
          userInfo.classList.add('hidden');
          userInfo.classList.remove('flex');
        }
        if (welcomeUserName) {
          welcomeUserName.textContent = 'Usuario';
        }
        if (welcomeMessage) {
          welcomeMessage.classList.add('hidden');
        }
        
        // Ocultar botón de agregar anuncio
        actualizarVisibilidadBotonAgregar();
      }
    }

    // Funciones de soporte (simplificadas ya que AuthManager maneja la mayoría)
    function updateSidebarUserInfo(user) {
      // Ya manejado por AuthManager
    }

    function setupUserMenu() {
      // Ya manejado por AuthManager
    }

    // ==================== FUNCIONES PARA AGREGAR ANUNCIOS ====================
    
    /**
     * Abrir modal para agregar anuncio
     */
    function abrirModalAgregarAnuncio() {
      const modal = document.getElementById('modalAgregarAnuncio');
      if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Limpiar formulario
        document.getElementById('formAgregarAnuncio').reset();
        document.getElementById('estadoAnuncio').value = 'publicado';
        
        // Focus en el primer campo
        document.getElementById('tituloAnuncio').focus();
      }
    }
    
    /**
     * Cerrar modal para agregar anuncio
     */
    function cerrarModalAgregarAnuncio() {
      const modal = document.getElementById('modalAgregarAnuncio');
      if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
      }
    }
    
    /**
     * Configurar formulario de agregar anuncio
     */
    function configurarFormularioAnuncio() {
      const form = document.getElementById('formAgregarAnuncio');
      if (!form) return;
      
      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardarAnuncio');
        const textoOriginal = btnGuardar.innerHTML;
        
        try {
          // Mostrar loading
          btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
          btnGuardar.disabled = true;
          
          // Recopilar datos del formulario
          const formData = new FormData(form);
          const datos = {
            titulo: formData.get('titulo'),
            resumen: formData.get('resumen') || '',
            contenido: formData.get('contenido'),
            estado: formData.get('estado'),
            destacado: formData.get('destacado') ? 1 : 0,
            imagen: formData.get('imagen') || ''
          };
          
          // Validar datos
          if (!datos.titulo.trim()) {
            throw new Error('El título es requerido');
          }
          
          if (!datos.contenido.trim()) {
            throw new Error('El contenido es requerido');
          }
          
          // Enviar a la API
          await crearAnuncio(datos);
          
          // Cerrar modal
          cerrarModalAgregarAnuncio();
          
          // Mostrar notificación de éxito
          if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion('Anuncio creado correctamente', 'success');
          }
          
          // Recargar anuncios
          setTimeout(() => {
            cargarAnuncios();
          }, 1000);
          
        } catch (error) {
          console.error('Error creando anuncio:', error);
          
          if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion(error.message || 'Error al crear el anuncio', 'error');
          } else {
            alert('Error: ' + (error.message || 'No se pudo crear el anuncio'));
          }
          
        } finally {
          // Restaurar botón
          btnGuardar.innerHTML = textoOriginal;
          btnGuardar.disabled = false;
        }
      });
    }
    
    /**
     * Función para crear anuncio via API
     */
    async function crearAnuncio(datos) {
      // No necesitamos token para crear anuncios - usamos sesión del servidor
      const token = null;
      
      const response = await fetch('./api/boletines.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // Para enviar cookies de sesión
        body: JSON.stringify(datos)
      });
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Error HTTP: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (!result.success && result.message) {
        throw new Error(result.message);
      }
      
      return result;
    }
    
    /**
     * Mostrar/ocultar botón de agregar anuncio según autenticación
     */
    function actualizarVisibilidadBotonAgregar() {
      const btnAgregar = document.getElementById('btnAgregarAnuncio');
      // Verificar autenticación desde sesión del servidor
      const isAuthenticated = window.authSessionManager && window.authSessionManager.isAuthenticated;
      
      if (btnAgregar) {
        if (isAuthenticated) {
          btnAgregar.classList.remove('hidden');
        } else {
          btnAgregar.classList.add('hidden');
        }
      }
    }

    // ==================== CARGAR ANUNCIOS REALES DE LA BASE DE DATOS ====================
    // Esta sección maneja la carga de boletines desde la tabla 'boletines' de la BD
    // Estructura de la tabla esperada:
    // - id (int, PRIMARY KEY)
    // - titulo (varchar(200))
    // - contenido (text) 
    // - resumen (text)
    // - imagen (varchar(255))
    // - autor_id (int, FK a usuarios)
    // - fecha_publicacion (timestamp)
    // - estado (enum: borrador, publicado, archivado)
    // - visualizaciones (int)
    // - destacado (tinyint(1))

    /**
     * Función principal para cargar anuncios importantes desde la base de datos
     * SIN DATOS DE EJEMPLO - Solo datos reales de la BD
     */
    async function cargarAnuncios() {
      const announcementsList = document.getElementById('announcementsList');
      if (!announcementsList) {
        console.warn('Elemento announcementsList no encontrado');
        return;
      }

      try {
        console.log('🔄 Cargando anuncios importantes desde la base de datos...');

        // Mostrar skeleton de carga
        mostrarSkeletonAnuncios();

        // Hacer petición directa a la API para obtener boletines publicados más recientes
        const url = './api/boletines.php?estado=publicado&limit=4&orderBy=fecha_publicacion&order=DESC';
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();
        console.log('📡 Respuesta de la API:', data);

        let anuncios = [];

        // Verificar diferentes formatos de respuesta
        if (data && data.success && Array.isArray(data.data) && data.data.length > 0) {
          anuncios = data.data;
          console.log(`✅ ${anuncios.length} anuncios destacados cargados desde la base de datos`);
          renderizarAnunciosImportantes(anuncios);

        } else if (data && Array.isArray(data.boletines) && data.boletines.length > 0) {
          anuncios = data.boletines;
          console.log(`✅ ${anuncios.length} anuncios destacados cargados desde la base de datos`);
          renderizarAnunciosImportantes(anuncios);

        } else if (Array.isArray(data) && data.length > 0) {
          anuncios = data;
          console.log(`✅ ${anuncios.length} anuncios destacados cargados desde la base de datos`);
          renderizarAnunciosImportantes(anuncios);

        } else {
          console.log('📋 No hay anuncios destacados en la base de datos - intentando cargar cualquier boletín publicado');
          
          // Fallback: cargar cualquier boletín publicado si no hay destacados
          const fallbackUrl = './api/boletines.php?estado=publicado&limit=4&orderBy=fecha_publicacion&order=DESC';
          const fallbackResponse = await fetch(fallbackUrl);
          
          if (fallbackResponse.ok) {
            const fallbackData = await fallbackResponse.json();
            
            if (fallbackData && Array.isArray(fallbackData.data) && fallbackData.data.length > 0) {
              anuncios = fallbackData.data;
              console.log(`✅ ${anuncios.length} anuncios recientes cargados desde la base de datos`);
              renderizarAnunciosImportantes(anuncios);
            } else if (Array.isArray(fallbackData) && fallbackData.length > 0) {
              anuncios = fallbackData;
              console.log(`✅ ${anuncios.length} anuncios recientes cargados desde la base de datos`);
              renderizarAnunciosImportantes(anuncios);
            } else {
              mostrarMensajeNoAnuncios();
            }
          } else {
            mostrarMensajeNoAnuncios();
          }
        }

      } catch (error) {
        console.error('❌ Error cargando anuncios desde la base de datos:', error);
        mostrarErrorAnuncios(error.message);
        
        // Fallback adicional: verificar si hay archivo de inicialización
        verificarInicializacionBD();
      }
    }

    /**
     * Mostrar skeleton de carga
     */
    function mostrarSkeletonAnuncios() {
      const announcementsList = document.getElementById('announcementsList');
      if (!announcementsList) return;
      
      announcementsList.innerHTML = `
          <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit skeleton-announcement">
              <div class="flex items-center">
                  <div class="flex flex-col">
                      <div class="animate-pulse bg-gray-200 rounded h-4 w-32 mb-2"></div>
                      <div class="animate-pulse bg-gray-200 rounded h-3 w-40"></div>
                  </div>
              </div>
              <div class="flex">
                  <div class="animate-pulse bg-gray-200 rounded-full h-6 w-6"></div>
              </div>
          </li>
          <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit skeleton-announcement">
              <div class="flex items-center">
                  <div class="flex flex-col">
                      <div class="animate-pulse bg-gray-200 rounded h-4 w-28 mb-2"></div>
                      <div class="animate-pulse bg-gray-200 rounded h-3 w-36"></div>
                  </div>
              </div>
              <div class="flex">
                  <div class="animate-pulse bg-gray-200 rounded-full h-6 w-6"></div>
              </div>
          </li>
          <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit skeleton-announcement">
              <div class="flex items-center">
                  <div class="flex flex-col">
                      <div class="animate-pulse bg-gray-200 rounded h-4 w-30 mb-2"></div>
                      <div class="animate-pulse bg-gray-200 rounded h-3 w-38"></div>
                  </div>
              </div>
              <div class="flex">
                  <div class="animate-pulse bg-gray-200 rounded-full h-6 w-6"></div>
              </div>
          </li>
      `;
    }

    /**
     * Renderizar anuncios en la sección de anuncios importantes
     */
    function renderizarAnunciosImportantes(anuncios) {
      const announcementsList = document.getElementById('announcementsList');
      if (!announcementsList) return;

      console.log(`Renderizando ${anuncios.length} anuncios importantes`);

      // Limpiar skeleton existente
      const skeletons = announcementsList.querySelectorAll('.skeleton-announcement');
      skeletons.forEach(skeleton => skeleton.remove());

      if (anuncios && anuncios.length > 0) {
        announcementsList.innerHTML = anuncios.map(anuncio => {
          // Obtener icono y color basado en tipo/prioridad
          const icono = obtenerIconoAnuncio(anuncio.tipo, anuncio.prioridad);
          const colorClase = obtenerColorAnuncio(anuncio.prioridad, anuncio.tipo);

          // Formatear fecha
          const fecha = formatearFechaRelativa(anuncio.fecha_publicacion);

          // Truncar resumen
          const resumen = anuncio.resumen || truncarTexto(anuncio.contenido, 60) || 'Ver más detalles';

          return `
                <li class="relative flex justify-between py-2 pr-4 mb-2 border-0 rounded-t-lg rounded-xl text-inherit hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-center w-full">
                        <div class="flex flex-col flex-1">
                            <h6 class="mb-1 text-sm leading-normal dark:text-white font-semibold flex items-center">
                                <span class="w-2 h-2 rounded-full ${colorClase} mr-2 flex-shrink-0"></span>
                                ${anuncio.titulo}
                            </h6>
                            <span class="text-xs leading-tight dark:text-white dark:opacity-80 text-slate-500 ml-4">
                                ${resumen}
                            </span>
                            <div class="flex items-center justify-between mt-1 ml-4">
                                <span class="text-xs text-slate-400">${fecha}</span>
                                ${anuncio.prioridad === 'alta' || anuncio.prioridad === 'urgente' ?
                                    '<span class="px-2 py-0.5 text-xs bg-red-100 text-red-600 rounded-full font-medium">Importante</span>' : ''
                                }
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-2">
                            <button onclick="verDetalleAnuncio(${anuncio.id})" 
                                class="group ease-in leading-pro text-xs rounded-full p-2 h-8 w-8 inline-block cursor-pointer border-0 bg-transparent text-center align-middle font-bold text-slate-700 hover:bg-slate-200 shadow-none transition-all dark:text-white"
                                title="Ver detalles">
                                <i class="fas fa-chevron-right text-xs group-hover:translate-x-0.5 transition-all duration-200"></i>
                            </button>
                        </div>
                    </div>
                </li>
            `;
        }).join('');

      } else {
        mostrarMensajeNoAnuncios();
      }
    }

    /**
     * Mostrar mensaje cuando no hay anuncios
     */
    function mostrarMensajeNoAnuncios() {
      const announcementsList = document.getElementById('announcementsList');
      if (!announcementsList) return;

      announcementsList.innerHTML = `
          <li class="relative flex flex-col items-center justify-center py-8 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                  <i class="fas fa-bullhorn text-2xl text-gray-400"></i>
              </div>
              <h6 class="mb-2 text-sm font-semibold text-slate-700 dark:text-white">No hay anuncios disponibles</h6>
              <p class="text-xs text-slate-500 dark:text-white/80 max-w-xs mb-4">
                  Los anuncios importantes aparecerán aquí cuando se publiquen en el sistema.
              </p>
              <div class="flex space-x-2">
                  <button onclick="cargarAnuncios()" class="px-4 py-2 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                      <i class="fas fa-refresh mr-1"></i>
                      Actualizar
                  </button>
                  <button onclick="inicializarBaseDatos()" class="px-4 py-2 text-xs bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                      <i class="fas fa-database mr-1"></i>
                      Inicializar BD
                  </button>
              </div>
          </li>
      `;
    }

    /**
     * Mostrar mensaje de error
     */
    function mostrarErrorAnuncios(errorMessage) {
      const announcementsList = document.getElementById('announcementsList');
      if (!announcementsList) return;

      announcementsList.innerHTML = `
          <li class="relative flex flex-col items-center justify-center py-8 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                  <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
              </div>
              <h6 class="mb-2 text-sm font-semibold text-slate-700 dark:text-white">Error al cargar anuncios</h6>
              <p class="text-xs text-slate-500 dark:text-white/80 max-w-xs mb-4">
                  ${errorMessage || 'No se pudieron cargar los anuncios desde la base de datos'}
              </p>
              <div class="flex flex-wrap justify-center gap-2">
                  <button onclick="cargarAnuncios()" class="px-4 py-2 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                      <i class="fas fa-refresh mr-1"></i>
                      Reintentar
                  </button>
                  <button onclick="inicializarBaseDatos()" class="px-4 py-2 text-xs bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                      <i class="fas fa-database mr-1"></i>
                      Inicializar BD
                  </button>
                  <a href="boletines.html" class="px-4 py-2 text-xs bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                      <i class="fas fa-external-link-alt mr-1"></i>
                      Ver Boletines
                  </a>
                  <a href="test_boletines_system.php" target="_blank" class="px-4 py-2 text-xs bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                      <i class="fas fa-bug mr-1"></i>
                      Debug Sistema
                  </a>
              </div>
          </li>
      `;
    }

    /**
     * Obtener icono basado en tipo y prioridad del anuncio
     */
    function obtenerIconoAnuncio(tipo, prioridad) {
      if (prioridad === 'urgente') {
        return 'fas fa-exclamation-triangle';
      } else if (prioridad === 'alta') {
        return 'fas fa-exclamation-circle';
      }

      const iconos = {
        'anuncio': 'fas fa-bullhorn',
        'noticia': 'fas fa-newspaper',
        'boletin': 'fas fa-file-alt',
        'comunicado': 'fas fa-envelope-open-text'
      };

      return iconos[tipo] || 'fas fa-info-circle';
    }

    /**
     * Obtener color basado en prioridad y tipo
     */
    function obtenerColorAnuncio(prioridad, tipo) {
      const colores = {
        'urgente': 'bg-red-500',
        'alta': 'bg-orange-500',
        'media': 'bg-blue-500',
        'baja': 'bg-gray-500'
      };

      return colores[prioridad] || 'bg-blue-500';
    }    
    /**
     * Función para formatear fecha relativa
     */
    function formatearFechaRelativa(fecha) {
            if (!fecha) return 'Fecha no disponible';

            try {
              const fechaObj = new Date(fecha);
              const ahora = new Date();
              const diffMs = ahora - fechaObj;
              const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
              const diffHoras = Math.floor(diffMs / (1000 * 60 * 60));
              const diffMinutos = Math.floor(diffMs / (1000 * 60));

              if (diffMinutos < 1) {
                return 'Ahora mismo';
              } else if (diffMinutos < 60) {
                return `Hace ${diffMinutos} min`;
              } else if (diffHoras < 24) {
                return `Hace ${diffHoras} h`;
              } else if (diffDias === 1) {
                return 'Ayer';
              } else if (diffDias < 7) {
                return `Hace ${diffDias} días`;
              } else if (diffDias < 30) {
                const semanas = Math.floor(diffDias / 7);
                return `Hace ${semanas} semana${semanas > 1 ? 's' : ''}`;
              } else {
                return fechaObj.toLocaleDateString('es-ES', {
                  day: 'numeric',
                  month: 'short',
                  year: fechaObj.getFullYear() !== ahora.getFullYear() ? 'numeric' : undefined
                });
              }
            } catch (error) {
              console.error('Error formateando fecha:', error);
              return 'Fecha inválida';
            }
          }

          /**
           * Función para truncar texto
           */
          function truncarTexto(texto, limite = 100) {
            if (!texto) return '';
            if (texto.length <= limite) return texto;

            // Truncar en la palabra más cercana
            const truncado = texto.substring(0, limite);
            const ultimoEspacio = truncado.lastIndexOf(' ');

            if (ultimoEspacio > limite * 0.8) { // Si el último espacio está cerca del límite
              return truncado.substring(0, ultimoEspacio) + '...';
            }

            return truncado + '...';
          }

          /**
           * Función para ver detalle de anuncio
           */
          async function verDetalleAnuncio(anuncioId) {
            console.log('🔍 Viendo detalle del anuncio:', anuncioId);

            try {
              // Mostrar modal de carga
              mostrarModalCarga();

              // Cargar detalle del anuncio desde la API
              const anuncio = await api.getBoletin(anuncioId);

              // Mostrar modal con el detalle
              mostrarModalAnuncio(anuncio);

            } catch (error) {
              console.error('Error cargando detalle del anuncio:', error);
              cerrarModalCarga();

              if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('No se pudo cargar el detalle del anuncio', 'error');
              }

              // Como fallback, redirigir a boletines
              window.location.href = `boletines.html#anuncio-${anuncioId}`;
            }
          }

          /**
           * Mostrar modal de carga
           */
          function mostrarModalCarga() {
            let modal = document.getElementById('modalCarga');
            if (!modal) {
              modal = document.createElement('div');
              modal.id = 'modalCarga';
              modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
              modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 flex flex-col items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mb-3"></div>
                    <p class="text-sm text-gray-600">Cargando anuncio...</p>
                </div>
            `;
              document.body.appendChild(modal);
            }
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
          }

          /**
           * Cerrar modal de carga
           */
          function cerrarModalCarga() {
            const modal = document.getElementById('modalCarga');
            if (modal) {
              modal.classList.add('hidden');
              document.body.style.overflow = 'auto';
            }
          }

          /**
           * Mostrar modal con detalle del anuncio
           */
          function mostrarModalAnuncio(anuncio) {
            // Cerrar modal de carga
            cerrarModalCarga();

            // Crear modal si no existe
            let modal = document.getElementById('anuncioDetalleModal');
            if (!modal) {
              modal = document.createElement('div');
              modal.id = 'anuncioDetalleModal';
              modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
              modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex-1">
                                <h3 id="detalleModalTitulo" class="text-2xl font-bold text-gray-900 mb-3"></h3>
                                <div id="detalleModalMeta" class="flex items-center flex-wrap gap-3 text-sm text-gray-500"></div>
                            </div>
                            <button onclick="cerrarModalDetalleAnuncio()" class="text-gray-400 hover:text-gray-600 ml-4">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div id="detalleModalImagen" class="mb-6 hidden">
                            <img id="imagenAnuncio" src="" alt="" class="w-full max-h-64 object-cover rounded-lg">
                        </div>
                        
                        <div id="detalleModalResumen" class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-900 rounded hidden"></div>
                        
                        <div id="detalleModalContenido" class="prose max-w-none text-gray-700 leading-relaxed mb-6"></div>
                        
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span id="detalleModalVisualizaciones"></span>
                                <span id="detalleModalAutor"></span>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="cerrarModalDetalleAnuncio()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                    Cerrar
                                </button>
                                <a href="boletines.html" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    Ver todos los boletines
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
              document.body.appendChild(modal);

              // Cerrar modal al hacer clic fuera
              modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                  cerrarModalDetalleAnuncio();
                }
              });
            }

            // Llenar contenido del modal
            const data = anuncio.data || anuncio;

            document.getElementById('detalleModalTitulo').textContent = data.titulo;

            // Meta información
            const metaInfo = [];
            if (data.fecha_publicacion) {
              metaInfo.push(`<span><i class="fas fa-calendar mr-1"></i>${formatearFechaRelativa(data.fecha_publicacion)}</span>`);
            }
            if (data.destacado && parseInt(data.destacado) === 1) {
              metaInfo.push('<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium"><i class="fas fa-star mr-1"></i>Destacado</span>');
            }
            if (data.estado) {
              const estadoClase = data.estado === 'publicado' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
              metaInfo.push(`<span class="px-2 py-1 ${estadoClase} rounded-full text-xs font-medium">${data.estado}</span>`);
            }

            document.getElementById('detalleModalMeta').innerHTML = metaInfo.join('');

            // Imagen (si existe)
            const imagenContainer = document.getElementById('detalleModalImagen');
            const imagenElement = document.getElementById('imagenAnuncio');
            if (data.imagen && data.imagen.trim()) {
              imagenElement.src = data.imagen;
              imagenElement.alt = data.titulo;
              imagenContainer.classList.remove('hidden');
            } else {
              imagenContainer.classList.add('hidden');
            }

            // Resumen (si existe y es diferente del contenido)
            const resumenContainer = document.getElementById('detalleModalResumen');
            if (data.resumen && data.resumen.trim() && data.resumen !== data.contenido) {
              resumenContainer.textContent = data.resumen;
              resumenContainer.classList.remove('hidden');
            } else {
              resumenContainer.classList.add('hidden');
            }

            // Contenido principal
            const contenido = data.contenido || data.resumen || 'No hay contenido disponible.';
            document.getElementById('detalleModalContenido').innerHTML = contenido.replace(/\n/g, '<br>');

            // Información adicional
            const visualizaciones = data.visualizaciones ? `<i class="fas fa-eye mr-1"></i>${data.visualizaciones} visualizaciones` : '';
            const autor = (data.autor || (data.autor_nombre && data.autor_apellido ? `${data.autor_nombre} ${data.autor_apellido}` : '')) ?
              `<i class="fas fa-user mr-1"></i>Por: ${data.autor || `${data.autor_nombre} ${data.autor_apellido}`}` : '';

            document.getElementById('detalleModalVisualizaciones').innerHTML = visualizaciones;
            document.getElementById('detalleModalAutor').innerHTML = autor;

            // Mostrar modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
          }

          /**
           * Cerrar modal de detalle de anuncio
           */
          function cerrarModalDetalleAnuncio() {
            const modal = document.getElementById('anuncioDetalleModal');
            if (modal) {
              modal.classList.add('hidden');
              document.body.style.overflow = 'auto';
            }
          }

          /**
           * Verificar si la base de datos necesita inicialización
           */
          async function verificarInicializacionBD() {
            try {
              console.log('🔍 Verificando estado de la base de datos...');
              
              // Verificar si existe el archivo de inicialización
              const initResponse = await fetch('init_database_with_announcements.php');
              
              if (initResponse.ok) {
                console.log('🛠️ Archivo de inicialización encontrado');
                mostrarOpcionInicializacion();
              } else {
                console.log('⚠️ No se encontró archivo de inicialización');
              }
            } catch (error) {
              console.error('Error verificando inicialización:', error);
            }
          }

          /**
           * Mostrar opción de inicialización de BD en el mensaje de error
           */
          function mostrarOpcionInicializacion() {
            const announcementsList = document.getElementById('announcementsList');
            if (!announcementsList) return;

            const inicializarBtn = announcementsList.querySelector('button[onclick="inicializarBaseDatos()"]');
            if (inicializarBtn) {
              inicializarBtn.classList.remove('hidden');
              inicializarBtn.innerHTML = '<i class="fas fa-database mr-1"></i>Inicializar BD (Disponible)';
            }
          }

          /**
           * Inicializar base de datos con datos de ejemplo
           */
          async function inicializarBaseDatos() {
            if (!confirm('¿Estás seguro de que quieres inicializar la base de datos con boletines de ejemplo?\n\nEsto creará algunos registros de prueba en la tabla boletines.')) {
              return;
            }

            try {
              mostrarNotificacion('Inicializando base de datos...', 'info');
              
              const response = await fetch('init_database_with_announcements.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                }
              });

              const result = await response.json();

              if (result.success) {
                mostrarNotificacion('Base de datos inicializada correctamente', 'success');
                
                // Recargar anuncios después de 2 segundos
                setTimeout(() => {
                  cargarAnuncios();
                }, 2000);
              } else {
                mostrarNotificacion(result.message || 'Error al inicializar la base de datos', 'error');
              }

            } catch (error) {
              console.error('Error inicializando base de datos:', error);
              mostrarNotificacion('Error al conectar con el servidor', 'error');
            }
          }

          /**
           * Función de inicialización del dashboard
           */
          // Instancia global del AuthManager
          let authManager = null;
          
          // Variables para el slider de banners
          let bannersData = [];
          let currentBannerIndex = 0;
          let bannerInterval = null;
          
          /**
           * Cargar banners desde la base de datos para el slider
           */
          async function cargarBannersSlider() {
            try {
              console.log('🎬 Cargando banners para el slider...');
              console.log('🌐 URL base:', window.location.origin);
              console.log('📍 Ruta actual:', window.location.pathname);
              
              const apiUrl = './api/banners.php?action=active';
              console.log('🔗 URL de API:', apiUrl);
              
              const response = await fetch(apiUrl);
              
              if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
              }
              
              // Obtener el texto primero para manejar posibles warnings de PHP
              const responseText = await response.text();
              
              // Limpiar warnings de PHP si existen
              let cleanedResponse = responseText;
              if (responseText.includes('<br />')) {
                const jsonStart = responseText.indexOf('{');
                if (jsonStart !== -1) {
                  cleanedResponse = responseText.substring(jsonStart);
                }
              }
              
              const result = JSON.parse(cleanedResponse);
              
              if (result.success && result.data && result.data.length > 0) {
                bannersData = result.data;
                console.log(`✅ ${bannersData.length} banners cargados para el slider`);
                inicializarSlider();
              } else {
                console.log('⚠️ No hay banners en la base de datos');
                mostrarSliderPorDefecto();
              }
              
            } catch (error) {
              console.error('❌ Error cargando banners:', error);
              mostrarSliderPorDefecto();
            }
          }
          
          /**
           * Inicializar el slider con los banners cargados
           */
          function inicializarSlider() {
            console.log('🔧 Iniciando inicializarSlider...');
            
            const slidesContainer = document.getElementById('slidesContainer');
            const sliderIndicators = document.getElementById('sliderIndicators');
            const sliderLoading = document.getElementById('sliderLoading');
            const btnNext = document.getElementById('sliderBtnNext');
            const btnPrev = document.getElementById('sliderBtnPrev');
            
            console.log('📊 Estado del slider:');
            console.log('   - slidesContainer:', slidesContainer ? 'encontrado' : 'NO encontrado');
            console.log('   - bannersData.length:', bannersData.length);
            console.log('   - sliderLoading:', sliderLoading ? 'encontrado' : 'NO encontrado');
            
            if (!slidesContainer) {
              console.error('❌ slidesContainer no encontrado');
              return;
            }
            
            if (bannersData.length === 0) {
              console.error('❌ No hay banners en bannersData');
              return;
            }
            
            // Ocultar loading
            if (sliderLoading) {
              sliderLoading.style.display = 'none';
            }
            
            // Crear slides
            slidesContainer.innerHTML = bannersData.map((banner, index) => {
              const imagenUrl = banner.imagen_url || banner.imagen || '';
              const titulo = banner.titulo || `Banner ${index + 1}`;
              const descripcion = banner.descripcion || 'Conecta con el clúster automotriz líder de México';
              
              return `
                <div class="banner-slide absolute w-full h-full transition-all duration-500 ${index === 0 ? 'opacity-100' : 'opacity-0'}" 
                     data-slide-index="${index}"
                     style="background-image: url('${imagenUrl}'); background-size: cover; background-position: center;">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                  <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                    <h5 class="text-2xl font-bold mb-2 drop-shadow-lg">${titulo}</h5>
                    <p class="text-sm opacity-90 drop-shadow-md">${descripcion}</p>
                    ${banner.enlace ? `
                      <a href="${banner.enlace}" class="inline-block mt-3 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-colors">
                        Ver más →
                      </a>
                    ` : ''}
                  </div>
                </div>
              `;
            }).join('');
            
            console.log('✅ HTML generado e insertado en slidesContainer');
            console.log('🔢 Número de slides creados:', slidesContainer.children.length);
            
            // Verificar que el primer slide sea visible
            const primerSlide = slidesContainer.querySelector('.banner-slide');
            if (primerSlide) {
              console.log('👁️ Primer slide encontrado:', primerSlide);
              const styles = getComputedStyle(primerSlide);
              console.log('   - Opacity:', styles.opacity);
              console.log('   - Display:', styles.display);
              console.log('   - Background-image:', styles.backgroundImage);
            }
            
            // Crear indicadores si hay más de un banner
            if (bannersData.length > 1) {
              sliderIndicators.innerHTML = bannersData.map((_, index) => `
                <button class="slider-dot w-2 h-2 rounded-full bg-white/50 hover:bg-white/80 transition-all ${index === 0 ? 'bg-white w-8' : ''}" 
                        data-dot-index="${index}"
                        onclick="cambiarBanner(${index})"></button>
              `).join('');
              
              // Mostrar botones de control
              if (btnNext) {
                btnNext.style.opacity = '1';
                btnNext.onclick = siguienteBanner;
              }
              if (btnPrev) {
                btnPrev.style.opacity = '1';
                btnPrev.onclick = anteriorBanner;
              }
              
              // Iniciar autoplay
              iniciarAutoplayBanners();
            } else {
              // Ocultar controles si solo hay un banner
              sliderIndicators.style.display = 'none';
              if (btnNext) btnNext.style.display = 'none';
              if (btnPrev) btnPrev.style.display = 'none';
            }
          }
          
          /**
           * Mostrar slider por defecto cuando no hay banners
           */
          function mostrarSliderPorDefecto() {
            const slidesContainer = document.getElementById('slidesContainer');
            const sliderLoading = document.getElementById('sliderLoading');
            const sliderIndicators = document.getElementById('sliderIndicators');
            
            if (sliderLoading) {
              sliderLoading.style.display = 'none';
            }
            
            if (slidesContainer) {
              slidesContainer.innerHTML = `
                <div style="position: absolute; 
                            top: 0; left: 0; right: 0; bottom: 0;
                            background-color: #1e293b;
                            background-image: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
                            background-size: cover;
                            background-position: center;
                            z-index: 100;">
                    <!-- Overlay para consistencia visual -->
                    <div style="position: absolute; 
                                top: 0; left: 0; right: 0; bottom: 0;
                                background: linear-gradient(
                                    to top, 
                                    rgba(0,0,0,0.7) 0%, 
                                    rgba(0,0,0,0.4) 50%, 
                                    rgba(0,0,0,0.2) 100%
                                );">
                    </div>
                    <!-- Contenido con el mismo estilo que los banners -->
                    <div style="position: absolute; 
                                bottom: 0; left: 0; right: 0;
                                padding: 2rem;
                                background: linear-gradient(
                                    to top, 
                                    rgba(0,0,0,0.8) 0%, 
                                    rgba(0,0,0,0.6) 50%, 
                                    rgba(0,0,0,0.0) 100%
                                );">
                        <div style="background: rgba(0,0,0,0.4); 
                                    padding: 1.5rem; 
                                    border-radius: 0.75rem; 
                                    backdrop-filter: blur(10px);
                                    border: 1px solid rgba(255,255,255,0.1);
                                    text-align: center;">
                            <div style="font-size: 2.5rem; margin-bottom: 1rem;">⚠️</div>
                            <h5 style="font-size: 1.25rem; 
                                       font-weight: bold; 
                                       margin-bottom: 0.75rem;
                                       color: #ffffff;
                                       text-shadow: 
                                           0 2px 4px rgba(0,0,0,0.9),
                                           0 1px 2px rgba(0,0,0,0.8);
                                       line-height: 1.2;">
                                Sin conexión a base de datos
                            </h5>
                            <p style="font-size: 0.875rem; 
                                      color: #e2e8f0;
                                      line-height: 1.5;
                                      margin: 0;
                                      text-shadow: 
                                          0 1px 3px rgba(0,0,0,0.9),
                                          0 1px 2px rgba(0,0,0,0.8);">
                                No se pueden cargar los banners desde la base de datos
                            </p>
                        </div>
                    </div>
                </div>
              `;
            }
            
            if (sliderIndicators) {
              sliderIndicators.style.display = 'none';
            }
            
            // Ocultar botones de control
            const btnNext = document.getElementById('sliderBtnNext');
            const btnPrev = document.getElementById('sliderBtnPrev');
            if (btnNext) btnNext.style.display = 'none';
            if (btnPrev) btnPrev.style.display = 'none';
          }
          
          /**
           * Cambiar a un banner específico
           */
          function cambiarBanner(index) {
            if (index === currentBannerIndex || !bannersData.length) return;
            
            const slides = document.querySelectorAll('.banner-slide');
            const dots = document.querySelectorAll('.slider-dot');
            
            // Ocultar slide actual
            if (slides[currentBannerIndex]) {
              slides[currentBannerIndex].classList.remove('opacity-100');
              slides[currentBannerIndex].classList.add('opacity-0');
            }
            if (dots[currentBannerIndex]) {
              dots[currentBannerIndex].classList.remove('bg-white', 'w-8');
              dots[currentBannerIndex].classList.add('bg-white/50');
            }
            
            // Mostrar nuevo slide
            currentBannerIndex = index;
            
            if (slides[currentBannerIndex]) {
              slides[currentBannerIndex].classList.remove('opacity-0');
              slides[currentBannerIndex].classList.add('opacity-100');
            }
            if (dots[currentBannerIndex]) {
              dots[currentBannerIndex].classList.remove('bg-white/50');
              dots[currentBannerIndex].classList.add('bg-white', 'w-8');
            }
            
            // Reiniciar autoplay
            if (bannerInterval) {
              clearInterval(bannerInterval);
              iniciarAutoplayBanners();
            }
          }
          
          /**
           * Siguiente banner
           */
          function siguienteBanner() {
            const nextIndex = (currentBannerIndex + 1) % bannersData.length;
            cambiarBanner(nextIndex);
          }
          
          /**
           * Banner anterior
           */
          function anteriorBanner() {
            const prevIndex = (currentBannerIndex - 1 + bannersData.length) % bannersData.length;
            cambiarBanner(prevIndex);
          }
          
          /**
           * Iniciar autoplay de banners
           */
          function iniciarAutoplayBanners() {
            if (bannersData.length > 1) {
              bannerInterval = setInterval(siguienteBanner, 5000); // Cambiar cada 5 segundos
            }
          }
          
          /**
           * Detener autoplay de banners
           */
          function detenerAutoplayBanners() {
            if (bannerInterval) {
              clearInterval(bannerInterval);
              bannerInterval = null;
            }
          }
          
          // Event listeners para pausar autoplay en hover
          document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('bannersSlider');
            if (slider) {
              slider.addEventListener('mouseenter', detenerAutoplayBanners);
              slider.addEventListener('mouseleave', iniciarAutoplayBanners);
            }
          });

          async function inicializarDashboard() {
            console.log('🚀 Inicializando dashboard Clúster Intranet...');
            console.log('🔧 Versión del sistema: 1.0.0');
            console.log('🌐 URL actual:', window.location.href);
            
            try {
              // Crear instancia del AuthManager
              console.log('👤 Inicializando sistema de autenticación...');
              authManager = new AuthManager();
              window.authManager = authManager; // Hacerlo global para debugging
              
              // Esperar a que se complete la verificación
              await new Promise(resolve => setTimeout(resolve, 100));
              
              // Configurar menú de usuario ya está en AuthManager
              console.log('🔧 Sistema de autenticación configurado');
              
              // Configurar formulario de anuncios
              console.log('📝 Configurando formulario de anuncios...');
              configurarFormularioAnuncio();
              
              // Actualizar visibilidad del botón agregar
              actualizarVisibilidadBotonAgregar();
              
              // Cargar anuncios importantes desde la BD
              console.log('📢 Cargando anuncios importantes...');
              await cargarAnuncios();
              
              // Cargar banners para el slider
              console.log('🎬 Cargando slider de banners...');
              await cargarBannersSlider();
              
              // Cargar empresas en convenio
              console.log('🏭 Cargando empresas en convenio...');
              await cargarEmpresasConvenio();
              
              // Cargar empresas destacadas
              console.log('⭐ Cargando empresas destacadas...');
              await cargarEmpresasDestacadas();
              
              // Cargar otras secciones dinámicas si existen
              if (typeof cargarEmpresasComites === 'function') {
                console.log('🏢 Cargando empresas y comités...');
                await cargarEmpresasComites();
              } else {
                console.log('⚠️ Función cargarEmpresasComites no disponible');
              }
              
              if (typeof cargarEstadisticas === 'function') {
                console.log('📊 Cargando estadísticas...');
                await cargarEstadisticas();
              } else {
                console.log('⚠️ Función cargarEstadisticas no disponible');
              }
              
              console.log('✅ Dashboard inicializado correctamente');
              
              // Log de estado final
              setTimeout(() => {
                console.log('📋 Estado final del dashboard:');
                const anunciosList = document.getElementById('announcementsList');
                const anunciosCount = anunciosList ? anunciosList.children.length : 0;
                console.log(`   - Anuncios cargados: ${anunciosCount}`);
                console.log(`   - Elementos skeleton: ${document.querySelectorAll('.skeleton-announcement').length}`);
                console.log('🎯 Sistema listo para usar');
              }, 2000);
              
            } catch (error) {
              console.error('❌ Error inicializando dashboard:', error);
              console.error('📍 Stack trace:', error.stack);
              
              // Mostrar notificación de error si la función existe
              if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error inicializando el dashboard', 'error');
              }
            }
          }

          // ==================== INICIALIZACIÓN AUTOMÁTICA ====================
          
          // Inicializar cuando el DOM esté listo
          document.addEventListener('DOMContentLoaded', function() {
            console.log('📋 DOM cargado - iniciando dashboard');
            inicializarDashboard();
          });

          // Hacer funciones globales para debugging
          window.cargarBannersSlider = cargarBannersSlider;
          window.inicializarSlider = inicializarSlider;
          window.mostrarSliderPorDefecto = mostrarSliderPorDefecto;
          window.bannersData = bannersData;

          // Fallback si DOMContentLoaded ya se disparó
          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarDashboard);
          } else {
            // DOM ya está listo
            setTimeout(inicializarDashboard, 100);
          }
