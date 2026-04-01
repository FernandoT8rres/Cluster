/* Premium Header and Navbar JS extracted from dashboard.html */
document.addEventListener('DOMContentLoaded', function() {
    // Header Scroll Behavior
    const header = document.querySelector('.claut-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const headerNav = document.getElementById('clautMainNav');
    if (mobileBtn && headerNav) {
        mobileBtn.addEventListener('click', function() {
            headerNav.classList.toggle('show');
            this.classList.toggle('active');
        });
    }

    // Add Force Full Width Layout for desktop if needed
    setTimeout(function() {
        if (window.innerWidth >= 1025) {
            const style = document.createElement('style');
            style.innerHTML = `
                body .porsche-navbar {
                    margin: 80px 120px 0 120px !important;
                    width: auto !important;
                }
            `;
            document.head.appendChild(style);
        }
    }, 1000);
});
