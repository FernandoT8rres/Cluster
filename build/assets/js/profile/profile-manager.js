/**
 * Profile Page JavaScript
 * Handles tab switching, animations, form validation, and interactive features
 */

class ProfileManager {
  constructor() {
    this.currentTab = 'profile-tabs-info';
    this.init();
  }

  init() {
    this.initializeEventListeners();
    this.initializeAnimations();
    this.initializeFormValidation();
    this.initializeNotifications();
    this.setupTabSwitching();
  }

  initializeEventListeners() {
    // Tab switching
    const navLinks = document.querySelectorAll('[nav-link]');
    navLinks.forEach(link => {
      link.addEventListener('click', (e) => this.handleTabSwitch(e));
    });

    // Form inputs
    const inputs = document.querySelectorAll('.profile-input');
    inputs.forEach(input => {
      input.addEventListener('focus', (e) => this.handleInputFocus(e));
      input.addEventListener('blur', (e) => this.handleInputBlur(e));
      input.addEventListener('input', (e) => this.handleInputChange(e));
    });

    // Toggle switches
    const toggles = document.querySelectorAll('.profile-toggle input');
    toggles.forEach(toggle => {
      toggle.addEventListener('change', (e) => this.handleToggleChange(e));
    });

    // Buttons
    const saveBtn = document.querySelector('.profile-btn-primary');
    if (saveBtn) {
      saveBtn.addEventListener('click', (e) => this.handleSave(e));
    }

    const cancelBtn = document.querySelector('.profile-btn-secondary');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', (e) => this.handleCancel(e));
    }

    // Activity cards
    const activityCards = document.querySelectorAll('.activity-card');
    activityCards.forEach(card => {
      card.addEventListener('click', (e) => this.handleActivityCardClick(e));
    });
  }

  handleTabSwitch(e) {
    e.preventDefault();
    
    const clickedLink = e.currentTarget;
    const targetId = clickedLink.getAttribute('href').substring(1);
    
    if (targetId === this.currentTab) return;

    // Update navigation
    document.querySelectorAll('[nav-link]').forEach(link => {
      link.classList.remove('active');
    });
    clickedLink.classList.add('active');

    // Switch tabs with animation
    this.switchTab(this.currentTab, targetId);
    this.currentTab = targetId;
  }

  switchTab(fromId, toId) {
    const fromTab = document.getElementById(fromId);
    const toTab = document.getElementById(toId);

    if (!fromTab || !toTab) return;

    // Hide current tab
    fromTab.classList.remove('show', 'active');
    fromTab.style.opacity = '0';
    fromTab.style.transform = 'translateY(20px)';

    // Show new tab after delay
    setTimeout(() => {
      toTab.classList.add('show', 'active');
      toTab.style.opacity = '1';
      toTab.style.transform = 'translateY(0)';
      
      // Add entry animation to cards in the new tab
      this.animateTabContent(toTab);
    }, 200);
  }

  animateTabContent(tab) {
    const cards = tab.querySelectorAll('.profile-card, .activity-card, .settings-section');
    cards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      
      setTimeout(() => {
        card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, index * 100);
    });
  }

  setupTabSwitching() {
    // Initialize first tab as active
    const firstTab = document.getElementById(this.currentTab);
    if (firstTab) {
      firstTab.classList.add('show', 'active');
      firstTab.style.opacity = '1';
      firstTab.style.transform = 'translateY(0)';
    }
  }

  handleInputFocus(e) {
    const input = e.target;
    const container = input.closest('.w-full');
    
    if (container) {
      container.classList.add('profile-input-focused');
    }
    
    input.classList.add('profile-input-active');
  }

  handleInputBlur(e) {
    const input = e.target;
    const container = input.closest('.w-full');
    
    if (container) {
      container.classList.remove('profile-input-focused');
    }
    
    input.classList.remove('profile-input-active');
  }

  handleInputChange(e) {
    const input = e.target;
    this.validateInput(input);
  }

  validateInput(input) {
    const value = input.value.trim();
    const type = input.type;
    let isValid = true;

    switch (type) {
      case 'email':
        isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        break;
      case 'tel':
        isValid = /^[\+]?[\d\s\-\(\)]+$/.test(value) && value.length >= 10;
        break;
      default:
        isValid = value.length > 0;
    }

    this.updateInputValidation(input, isValid);
    return isValid;
  }

  updateInputValidation(input, isValid) {
    const container = input.closest('.w-full');
    if (!container) return;

    container.classList.remove('profile-success', 'profile-error');
    
    if (input.value.trim().length > 0) {
      container.classList.add(isValid ? 'profile-success' : 'profile-error');
    }
  }

  initializeFormValidation() {
    // Add real-time validation
    const requiredInputs = document.querySelectorAll('input[required]');
    requiredInputs.forEach(input => {
      input.setAttribute('required', 'true');
    });
  }

  handleToggleChange(e) {
    const toggle = e.target;
    const container = toggle.closest('.notification-item');
    
    if (container) {
      container.classList.add('profile-loading');
      
      // Simulate API call
      setTimeout(() => {
        container.classList.remove('profile-loading');
        this.showNotification(
          toggle.checked ? 'Notificación activada' : 'Notificación desactivada',
          'success'
        );
      }, 500);
    }
  }

  handleSave(e) {
    e.preventDefault();
    
    const button = e.target;
    const form = document.querySelector('.profile-form') || document.body;
    const inputs = form.querySelectorAll('.profile-input');
    
    let isFormValid = true;
    
    // Validate all inputs
    inputs.forEach(input => {
      if (!this.validateInput(input)) {
        isFormValid = false;
      }
    });

    if (!isFormValid) {
      this.showNotification('Por favor, corrige los errores en el formulario', 'error');
      return;
    }

    // Show loading state
    button.classList.add('profile-loading');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

    // Simulate API call
    setTimeout(() => {
      button.classList.remove('profile-loading');
      button.disabled = false;
      button.innerHTML = 'Guardar Cambios';
      
      this.showNotification('Cambios guardados exitosamente', 'success');
      
      // Add success animation
      this.animateSuccess();
    }, 2000);
  }

  handleCancel(e) {
    e.preventDefault();
    
    if (confirm('¿Estás seguro de que deseas descartar los cambios?')) {
      // Reset form values
      const inputs = document.querySelectorAll('.profile-input');
      inputs.forEach(input => {
        input.value = input.defaultValue || '';
        this.updateInputValidation(input, true);
      });
      
      this.showNotification('Cambios descartados', 'info');
    }
  }

  handleActivityCardClick(e) {
    const card = e.currentTarget;
    const cardType = card.dataset.type || 'default';
    
    // Add click animation
    card.style.transform = 'scale(0.95)';
    setTimeout(() => {
      card.style.transform = '';
    }, 150);

    // Handle different card types
    switch (cardType) {
      case 'events':
        this.showActivityDetails('eventos', 'Eventos del mes');
        break;
      case 'documents':
        this.showActivityDetails('documentos', 'Documentos recientes');
        break;
      case 'committees':
        this.showActivityDetails('comites', 'Comités activos');
        break;
      case 'points':
        this.showActivityDetails('puntos', 'Sistema de puntos');
        break;
      default:
        this.showNotification('Funcionalidad en desarrollo', 'info');
    }
  }

  showActivityDetails(type, title) {
    // This could open a modal or navigate to a detail page
    this.showNotification(`Mostrando: ${title}`, 'info');
  }

  animateSuccess() {
    const cards = document.querySelectorAll('.profile-card');
    cards.forEach((card, index) => {
      setTimeout(() => {
        card.style.transform = 'scale(1.02)';
        card.style.boxShadow = '0 8px 25px rgba(40, 167, 69, 0.15)';
        
        setTimeout(() => {
          card.style.transform = '';
          card.style.boxShadow = '';
        }, 300);
      }, index * 100);
    });
  }

  initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('profile-fade-in');
        }
      });
    }, observerOptions);

    // Observe all animatable elements
    const animatableElements = document.querySelectorAll(
      '.profile-card, .activity-card, .settings-section'
    );
    
    animatableElements.forEach(el => observer.observe(el));
  }

  initializeNotifications() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('profile-notifications')) {
      const container = document.createElement('div');
      container.id = 'profile-notifications';
      container.className = 'fixed top-4 right-4 z-50 space-y-2';
      document.body.appendChild(container);
    }
  }

  showNotification(message, type = 'info', duration = 3000) {
    const container = document.getElementById('profile-notifications');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `
      profile-notification 
      p-4 rounded-lg shadow-lg 
      transform translate-x-full 
      transition-all duration-300 ease-out
      ${this.getNotificationClasses(type)}
    `;

    const icon = this.getNotificationIcon(type);
    notification.innerHTML = `
      <div class="flex items-center">
        <i class="${icon} mr-3"></i>
        <span class="font-medium">${message}</span>
        <button class="ml-4 text-current opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;

    container.appendChild(notification);

    // Animate in
    setTimeout(() => {
      notification.style.transform = 'translateX(0)';
    }, 100);

    // Auto remove
    setTimeout(() => {
      if (notification.parentNode) {
        notification.style.transform = 'translateX(full)';
        setTimeout(() => {
          if (notification.parentNode) {
            notification.remove();
          }
        }, 300);
      }
    }, duration);
  }

  getNotificationClasses(type) {
    const classes = {
      success: 'bg-green-100 border-green-500 text-green-800',
      error: 'bg-red-100 border-red-500 text-red-800',
      warning: 'bg-yellow-100 border-yellow-500 text-yellow-800',
      info: 'bg-blue-100 border-blue-500 text-blue-800'
    };
    return classes[type] || classes.info;
  }

  getNotificationIcon(type) {
    const icons = {
      success: 'fas fa-check-circle',
      error: 'fas fa-exclamation-circle',
      warning: 'fas fa-exclamation-triangle',
      info: 'fas fa-info-circle'
    };
    return icons[type] || icons.info;
  }
}

// Enhanced scroll behavior
class ScrollEnhancer {
  constructor() {
    this.init();
  }

  init() {
    this.setupSmoothScroll();
    this.setupScrollToTop();
    this.setupNavbarScroll();
  }

  setupSmoothScroll() {
    // Add smooth scrolling to all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  }

  setupScrollToTop() {
    // Create scroll to top button
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.className = `
      fixed bottom-6 right-6 
      w-12 h-12 
      bg-gradient-to-br from-red-500 to-red-700 
      text-white rounded-full 
      shadow-lg hover:shadow-xl 
      transition-all duration-300 
      z-40 opacity-0 pointer-events-none
      flex items-center justify-center
    `;
    scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollToTopBtn.id = 'scroll-to-top';
    document.body.appendChild(scrollToTopBtn);

    // Show/hide on scroll
    window.addEventListener('scroll', () => {
      if (window.scrollY > 300) {
        scrollToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
      } else {
        scrollToTopBtn.classList.add('opacity-0', 'pointer-events-none');
      }
    });

    // Scroll to top functionality
    scrollToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  setupNavbarScroll() {
    // Add navbar scroll effects
    const navbar = document.querySelector('[navbar-main]');
    if (navbar) {
      window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
          navbar.classList.add('backdrop-blur-md', 'bg-white/80');
        } else {
          navbar.classList.remove('backdrop-blur-md', 'bg-white/80');
        }
      });
    }
  }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
  // Initialize profile manager
  window.profileManager = new ProfileManager();
  
  // Initialize scroll enhancer
  window.scrollEnhancer = new ScrollEnhancer();

  // Add loading states for initial content
  const cards = document.querySelectorAll('.profile-card, .activity-card');
  cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
      card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, index * 100);
  });

  // Initialize tooltips for activity cards
  const activityCards = document.querySelectorAll('.activity-card');
  activityCards.forEach(card => {
    card.setAttribute('title', 'Click para ver detalles');
  });

  console.log('Profile page initialized successfully');
});