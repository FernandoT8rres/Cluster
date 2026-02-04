/**
 * Enhanced Profile Page JavaScript
 * Cluster Intranet System
 */

class ProfileManager {
  constructor() {
    this.currentTab = 'info';
    this.isDarkMode = false;
    this.animations = true;
    this.init();
  }

  init() {
    this.bindEvents();
    this.initializeComponents();
    this.loadUserPreferences();
  }

  bindEvents() {
    // Mobile sidebar toggle
    this.bindSidebarEvents();
    
    // Tab switching
    this.bindTabEvents();
    
    // Form interactions
    this.bindFormEvents();
    
    // Password visibility toggles
    this.bindPasswordToggles();
    
    // Theme selection
    this.bindThemeEvents();
    
    // Notification toggles
    this.bindNotificationEvents();
    
    // Save buttons
    this.bindSaveEvents();
    
    // Avatar upload
    this.bindAvatarEvents();
  }

  bindSidebarEvents() {
    const openSidebar = document.getElementById('openSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    const toggleSidebar = () => {
      sidebar.classList.toggle('open');
      sidebar.classList.toggle('-translate-x-full');
      sidebarOverlay.classList.toggle('hidden');
      document.body.classList.toggle('overflow-hidden');
    };

    if (openSidebar) openSidebar.addEventListener('click', toggleSidebar);
    if (closeSidebar) closeSidebar.addEventListener('click', toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

    // Close sidebar on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
        toggleSidebar();
      }
    });
  }

  bindTabEvents() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetTab = btn.getAttribute('data-tab');
        this.switchTab(targetTab, tabBtns, tabPanes);
      });
    });

    // Keyboard navigation for tabs
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
          case '1':
            e.preventDefault();
            this.switchTab('info', tabBtns, tabPanes);
            break;
          case '2':
            e.preventDefault();
            this.switchTab('security', tabBtns, tabPanes);
            break;
          case '3':
            e.preventDefault();
            this.switchTab('preferences', tabBtns, tabPanes);
            break;
        }
      }
    });
  }

  switchTab(targetTab, tabBtns, tabPanes) {
    if (this.currentTab === targetTab) return;

    // Remove active class from all buttons and panes
    tabBtns.forEach(b => {
      b.classList.remove('active');
      b.classList.add('text-gray-600');
    });
    tabPanes.forEach(pane => {
      pane.classList.remove('active');
    });

    // Add active class to clicked button and corresponding pane
    const activeBtn = document.querySelector(`[data-tab="${targetTab}"]`);
    const activePane = document.getElementById(`${targetTab}-tab`);

    if (activeBtn && activePane) {
      activeBtn.classList.add('active');
      activeBtn.classList.remove('text-gray-600');
      
      // Smooth transition
      setTimeout(() => {
        activePane.classList.add('active');
      }, 150);

      this.currentTab = targetTab;
      
      // Update URL without reload
      if (history.pushState) {
        history.pushState(null, null, `#${targetTab}`);
      }
    }
  }

  bindFormEvents() {
    const formInputs = document.querySelectorAll('.form-input');
    
    formInputs.forEach(input => {
      // Focus animations
      input.addEventListener('focus', (e) => {
        if (this.animations) {
          e.target.classList.add('transform', 'scale-105');
        }
        this.validateInput(e.target);
      });
      
      input.addEventListener('blur', (e) => {
        e.target.classList.remove('transform', 'scale-105');
        this.validateInput(e.target);
      });

      // Real-time validation
      input.addEventListener('input', (e) => {
        this.validateInput(e.target);
        
        // Password strength checking
        if (e.target.type === 'password' && e.target.placeholder.includes('Nueva')) {
          this.checkPasswordStrength(e.target.value);
        }
      });
    });

    // Form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleFormSubmission(form);
      });
    });
  }

  validateInput(input) {
    const value = input.value.trim();
    const type = input.type;
    let isValid = true;
    let message = '';

    // Remove previous validation classes
    input.classList.remove('border-red-500', 'border-green-500', 'border-yellow-500');

    switch(type) {
      case 'email':
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        isValid = emailRegex.test(value);
        message = isValid ? '' : 'Por favor ingresa un email válido';
        break;
      
      case 'tel':
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        isValid = phoneRegex.test(value.replace(/\s/g, ''));
        message = isValid ? '' : 'Por favor ingresa un teléfono válido';
        break;
      
      case 'password':
        isValid = value.length >= 8;
        message = isValid ? '' : 'La contraseña debe tener al menos 8 caracteres';
        break;
      
      default:
        isValid = value.length > 0;
        message = isValid ? '' : 'Este campo es requerido';
    }

    // Apply validation styles
    if (value.length > 0) {
      if (isValid) {
        input.classList.add('border-green-500');
      } else {
        input.classList.add('border-red-500');
      }
    }

    // Show/hide validation message
    this.showValidationMessage(input, message, isValid);
  }

  showValidationMessage(input, message, isValid) {
    let messageEl = input.parentElement.querySelector('.validation-message');
    
    if (message && !isValid) {
      if (!messageEl) {
        messageEl = document.createElement('p');
        messageEl.className = 'validation-message text-xs text-red-500 mt-1 transition-all duration-300';
        input.parentElement.appendChild(messageEl);
      }
      messageEl.textContent = message;
      messageEl.style.opacity = '1';
    } else if (messageEl) {
      messageEl.style.opacity = '0';
      setTimeout(() => {
        if (messageEl.parentElement) {
          messageEl.parentElement.removeChild(messageEl);
        }
      }, 300);
    }
  }

  checkPasswordStrength(password) {
    const strengthEl = document.querySelector('.password-strength');
    if (!strengthEl) return;

    const bars = strengthEl.querySelectorAll('.flex > div');
    let strength = 0;
    let strengthClass = '';

    // Reset bars
    bars.forEach(bar => {
      bar.className = 'flex-1 h-2 bg-gray-200 rounded transition-all duration-300';
    });

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    switch(strength) {
      case 1:
      case 2:
        strengthClass = 'weak';
        bars[0].classList.add('bg-red-500');
        break;
      case 3:
        strengthClass = 'fair';
        bars[0].classList.add('bg-yellow-500');
        bars[1].classList.add('bg-yellow-500');
        break;
      case 4:
        strengthClass = 'good';
        bars[0].classList.add('bg-blue-500');
        bars[1].classList.add('bg-blue-500');
        bars[2].classList.add('bg-blue-500');
        break;
      case 5:
        strengthClass = 'strong';
        bars.forEach(bar => bar.classList.add('bg-green-500'));
        break;
    }

    strengthEl.className = `password-strength mt-2 ${strengthClass}`;
    
    const strengthText = strengthEl.querySelector('p');
    if (strengthText) {
      const strengthLabels = {
        '': 'Fortaleza de la contraseña',
        'weak': 'Débil',
        'fair': 'Regular',
        'good': 'Buena',
        'strong': 'Fuerte'
      };
      strengthText.textContent = strengthLabels[strengthClass] || strengthLabels[''];
    }
  }

  bindPasswordToggles() {
    const toggleBtns = document.querySelectorAll('.toggle-password');
    
    toggleBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const input = btn.parentElement.querySelector('input');
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      });
    });
  }

  bindThemeEvents() {
    const themeOptions = document.querySelectorAll('.theme-option');
    
    themeOptions.forEach(option => {
      option.addEventListener('click', () => {
        // Remove active class from all options
        themeOptions.forEach(opt => {
          opt.classList.remove('border-red-500', 'active');
          opt.classList.add('border-gray-300');
        });
        
        // Add active class to selected option
        option.classList.remove('border-gray-300');
        option.classList.add('border-red-500', 'active');
        
        // Apply theme
        const themeText = option.querySelector('p').textContent;
        this.applyTheme(themeText.toLowerCase());
      });
    });
  }

  applyTheme(theme) {
    const body = document.body;
    
    // Remove existing theme classes
    body.classList.remove('dark-mode', 'light-mode', 'auto-mode');
    
    switch(theme) {
      case 'oscuro':
        body.classList.add('dark-mode');
        this.isDarkMode = true;
        break;
      case 'claro':
        body.classList.add('light-mode');
        this.isDarkMode = false;
        break;
      case 'auto':
        body.classList.add('auto-mode');
        this.isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        break;
    }
    
    // Save preference
    localStorage.setItem('theme', theme);
    
    // Show confirmation
    this.showNotification('Tema aplicado correctamente', 'success');
  }

  bindNotificationEvents() {
    const toggleSwitches = document.querySelectorAll('.toggle-switch input');
    
    toggleSwitches.forEach(toggle => {
      toggle.addEventListener('change', (e) => {
        const isChecked = e.target.checked;
        const settingName = e.target.closest('.notification-item, .security-option, .privacy-section > div')
                                  ?.querySelector('h4')?.textContent || 'Configuración';
        
        // Animate the toggle
        const slider = e.target.nextElementSibling;
        if (this.animations) {
          slider.classList.add('animate-pulse-shadow');
          setTimeout(() => {
            slider.classList.remove('animate-pulse-shadow');
          }, 1000);
        }
        
        // Save setting
        this.saveSetting(settingName, isChecked);
      });
    });
  }

  bindSaveEvents() {
    const saveButtons = document.querySelectorAll('.btn-primary');
    
    saveButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        if (btn.textContent.includes('Guardar') || btn.textContent.includes('Actualizar')) {
          e.preventDefault();
          this.handleSave(btn);
        }
      });
    });
  }

  bindAvatarEvents() {
    const avatarBtn = document.querySelector('.avatar-edit-btn');
    
    if (avatarBtn) {
      avatarBtn.addEventListener('click', () => {
        // Create file input
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        
        fileInput.addEventListener('change', (e) => {
          const file = e.target.files[0];
          if (file) {
            this.handleAvatarUpload(file);
          }
        });
        
        document.body.appendChild(fileInput);
        fileInput.click();
        document.body.removeChild(fileInput);
      });
    }
  }

  handleSave(button) {
    const originalText = button.textContent;
    
    // Show loading state
    button.textContent = 'Guardando...';
    button.disabled = true;
    button.classList.add('loading');
    
    // Simulate save operation
    setTimeout(() => {
      // Show success state
      button.textContent = '¡Guardado!';
      button.classList.remove('loading');
      button.classList.add('bg-green-500');
      
      // Show notification
      this.showNotification('Cambios guardados exitosamente', 'success');
      
      // Reset button
      setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('bg-green-500');
        button.disabled = false;
      }, 2000);
    }, 1500);
  }

  handleFormSubmission(form) {
    // Get all form inputs
    const inputs = form.querySelectorAll('.form-input');
    let isValid = true;
    
    // Validate all inputs
    inputs.forEach(input => {
      this.validateInput(input);
      if (input.classList.contains('border-red-500')) {
        isValid = false;
      }
    });
    
    if (isValid) {
      // Find submit button
      const submitBtn = form.querySelector('.btn-primary');
      if (submitBtn) {
        this.handleSave(submitBtn);
      }
    } else {
      this.showNotification('Por favor corrige los errores en el formulario', 'error');
    }
  }

  handleAvatarUpload(file) {
    // Validate file
    if (!file.type.startsWith('image/')) {
      this.showNotification('Por favor selecciona una imagen válida', 'error');
      return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
      this.showNotification('La imagen no debe ser mayor a 5MB', 'error');
      return;
    }
    
    // Create FileReader
    const reader = new FileReader();
    
    reader.onload = (e) => {
      const avatarImg = document.querySelector('.profile-avatar-container img');
      if (avatarImg) {
        avatarImg.src = e.target.result;
        
        // Add animation
        if (this.animations) {
          avatarImg.classList.add('animate-bounce');
          setTimeout(() => {
            avatarImg.classList.remove('animate-bounce');
          }, 2000);
        }
        
        this.showNotification('Avatar actualizado correctamente', 'success');
      }
    };
    
    reader.readAsDataURL(file);
  }

  saveSetting(settingName, value) {
    // Save to localStorage (in real app, this would be an API call)
    const settings = JSON.parse(localStorage.getItem('profileSettings') || '{}');
    settings[settingName] = value;
    localStorage.setItem('profileSettings', JSON.stringify(settings));
    
    console.log(`Setting saved: ${settingName} = ${value}`);
  }

  loadUserPreferences() {
    // Load theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      this.applyTheme(savedTheme);
      
      // Update theme selection UI
      const themeOptions = document.querySelectorAll('.theme-option');
      themeOptions.forEach(option => {
        const themeText = option.querySelector('p').textContent.toLowerCase();
        if (themeText === savedTheme) {
          option.click();
        }
      });
    }
    
    // Load other settings
    const settings = JSON.parse(localStorage.getItem('profileSettings') || '{}');
    Object.entries(settings).forEach(([key, value]) => {
      // Apply saved settings to toggles
      const toggles = document.querySelectorAll('.toggle-switch input');
      toggles.forEach(toggle => {
        const settingName = toggle.closest('.notification-item, .security-option, .privacy-section > div')
                                 ?.querySelector('h4')?.textContent;
        if (settingName === key) {
          toggle.checked = value;
        }
      });
    });
    
    // Check URL hash for initial tab
    const hash = window.location.hash.substring(1);
    if (hash && ['info', 'security', 'preferences'].includes(hash)) {
      const tabBtns = document.querySelectorAll('.tab-btn');
      const tabPanes = document.querySelectorAll('.tab-pane');
      this.switchTab(hash, tabBtns, tabPanes);
    }
  }

  initializeComponents() {
    // Initialize activity items click events
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(item => {
      item.addEventListener('click', () => {
        const title = item.querySelector('h4').textContent;
        this.showNotification(`Navegando a ${title}...`, 'info');
        
        // Add click animation
        if (this.animations) {
          item.style.transform = 'scale(0.95)';
          setTimeout(() => {
            item.style.transform = '';
          }, 150);
        }
      });
    });
    
    // Initialize tooltips (if needed)
    this.initializeTooltips();
    
    // Initialize auto-save
    this.initializeAutoSave();
  }

  initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
      // Simple tooltip implementation
      element.addEventListener('mouseenter', (e) => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip absolute z-50 px-2 py-1 text-xs text-white bg-gray-800 rounded shadow-lg pointer-events-none';
        tooltip.textContent = e.target.title;
        
        // Remove title to prevent default tooltip
        e.target.dataset.originalTitle = e.target.title;
        e.target.removeAttribute('title');
        
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
      });
      
      element.addEventListener('mouseleave', (e) => {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
          tooltip.remove();
        }
        
        // Restore title
        if (e.target.dataset.originalTitle) {
          e.target.title = e.target.dataset.originalTitle;
          delete e.target.dataset.originalTitle;
        }
      });
    });
  }

  initializeAutoSave() {
    const autoSaveInputs = document.querySelectorAll('.form-input[data-auto-save]');
    
    autoSaveInputs.forEach(input => {
      let timeout;
      
      input.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          this.autoSave(input);
        }, 2000); // Auto-save after 2 seconds of inactivity
      });
    });
  }

  autoSave(input) {
    const fieldName = input.name || input.id || 'unknown';
    const value = input.value;
    
    // Save to localStorage (in real app, this would be an API call)
    const autoSaveData = JSON.parse(localStorage.getItem('profileAutoSave') || '{}');
    autoSaveData[fieldName] = value;
    localStorage.setItem('profileAutoSave', JSON.stringify(autoSaveData));
    
    // Show subtle indication
    const indicator = document.createElement('span');
    indicator.className = 'text-xs text-green-600 ml-2';
    indicator.textContent = '✓ Guardado';
    
    // Remove existing indicator
    const existingIndicator = input.parentElement.querySelector('.text-green-600');
    if (existingIndicator) {
      existingIndicator.remove();
    }
    
    input.parentElement.appendChild(indicator);
    
    setTimeout(() => {
      if (indicator.parentElement) {
        indicator.remove();
      }
    }, 3000);
  }

  showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
    
    // Set notification style based on type
    const styles = {
      success: 'bg-green-500 text-white',
      error: 'bg-red-500 text-white',
      warning: 'bg-yellow-500 text-white',
      info: 'bg-blue-500 text-white'
    };
    
    notification.className += ` ${styles[type] || styles.info}`;
    
    // Set message
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
    
    // Animate in
    setTimeout(() => {
      notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
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

  // Public methods for external access
  getCurrentTab() {
    return this.currentTab;
  }

  setAnimations(enabled) {
    this.animations = enabled;
    document.body.classList.toggle('no-animations', !enabled);
  }

  exportUserData() {
    const userData = {
      profile: this.getFormData('info-tab'),
      security: this.getFormData('security-tab'),
      preferences: this.getFormData('preferences-tab'),
      settings: JSON.parse(localStorage.getItem('profileSettings') || '{}'),
      autoSave: JSON.parse(localStorage.getItem('profileAutoSave') || '{}')
    };
    
    // Create and download file
    const dataStr = JSON.stringify(userData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'perfil-usuario-claut.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
    
    this.showNotification('Datos exportados correctamente', 'success');
  }

  getFormData(tabId) {
    const tab = document.getElementById(tabId);
    if (!tab) return {};
    
    const formData = {};
    const inputs = tab.querySelectorAll('.form-input');
    
    inputs.forEach(input => {
      const key = input.name || input.id || input.placeholder;
      formData[key] = input.value;
    });
    
    return formData;
  }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  window.profileManager = new ProfileManager();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ProfileManager;
}
