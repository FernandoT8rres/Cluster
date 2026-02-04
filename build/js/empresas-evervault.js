// ============================================
// EVERVAULT-STYLE CAROUSEL FOR EMPRESAS
// ============================================

const codeChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789(){}[]<>;:,._-+=!@#$%^&*|\\/\"'`~?";

// ============================================
// EMPRESA CARD STREAM CONTROLLER
// ============================================
class EmpresaCardStreamController {
    constructor() {
        this.container = document.getElementById('empresaCardStream');
        this.cardLine = document.getElementById('empresaCardLine');
        this.speedIndicator = document.getElementById('speedValue');

        this.position = 0;
        this.velocity = 120;
        this.direction = -1;
        this.isAnimating = true;
        this.isDragging = false;

        this.lastTime = 0;
        this.lastMouseX = 0;
        this.mouseVelocity = 0;
        this.friction = 0.95;
        this.minVelocity = 30;

        this.containerWidth = 0;
        this.cardLineWidth = 0;
        this.empresas = [];

        this.init();
    }

    async init() {
        await this.loadEmpresas();
        this.populateCardLine();
        this.calculateDimensions();
        this.setupEventListeners();
        this.updateCardPosition();
        this.animate();
        this.startPeriodicUpdates();
    }

    async loadEmpresas() {
        try {
            console.log('🔍 Cargando empresas desde API...');
            const response = await fetch('./api/empresas.php');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('📦 Datos recibidos de API:', data);

            // Verificar diferentes estructuras de respuesta
            if (data.success && Array.isArray(data.empresas)) {
                // Filtrar solo empresas activas
                this.empresas = data.empresas.filter(e => e.estado === 'activo');
                console.log(`✅ Cargadas ${this.empresas.length} empresas activas de ${data.empresas.length} totales`);
            } else if (Array.isArray(data)) {
                // Si la respuesta es directamente un array
                this.empresas = data.filter(e => e.estado === 'activo');
                console.log(`✅ Cargadas ${this.empresas.length} empresas activas de ${data.length} totales`);
            } else {
                console.error('❌ Estructura de datos inesperada:', data);
                this.empresas = [];
            }
        } catch (error) {
            console.error('❌ Error en fetch de empresas:', error);
            this.empresas = [];
        }
    }

    calculateDimensions() {
        this.containerWidth = this.container.offsetWidth;
        const cardWidth = 400;
        const cardGap = 60;
        const cardCount = this.cardLine.children.length;
        this.cardLineWidth = (cardWidth + cardGap) * cardCount;
    }

    setupEventListeners() {
        this.cardLine.addEventListener('mousedown', (e) => this.startDrag(e));
        document.addEventListener('mousemove', (e) => this.onDrag(e));
        document.addEventListener('mouseup', () => this.endDrag());

        this.cardLine.addEventListener('touchstart', (e) => this.startDrag(e.touches[0]), { passive: false });
        document.addEventListener('touchmove', (e) => this.onDrag(e.touches[0]), { passive: false });
        document.addEventListener('touchend', () => this.endDrag());

        this.cardLine.addEventListener('wheel', (e) => this.onWheel(e));
        this.cardLine.addEventListener('selectstart', (e) => e.preventDefault());
        this.cardLine.addEventListener('dragstart', (e) => e.preventDefault());

        window.addEventListener('resize', () => this.calculateDimensions());
    }

    startDrag(e) {
        e.preventDefault();
        this.isDragging = true;
        this.isAnimating = false;
        this.lastMouseX = e.clientX;
        this.mouseVelocity = 0;

        const transform = window.getComputedStyle(this.cardLine).transform;
        if (transform !== 'none') {
            const matrix = new DOMMatrix(transform);
            this.position = matrix.m41;
        }

        this.cardLine.classList.add('dragging');
        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'grabbing';
    }

    onDrag(e) {
        if (!this.isDragging) return;
        e.preventDefault();

        const deltaX = e.clientX - this.lastMouseX;
        this.position += deltaX;
        this.mouseVelocity = deltaX * 60;
        this.lastMouseX = e.clientX;

        this.cardLine.style.transform = `translateX(${this.position}px)`;
        this.updateCardClipping();
    }

    endDrag() {
        if (!this.isDragging) return;

        this.isDragging = false;
        this.cardLine.classList.remove('dragging');

        if (Math.abs(this.mouseVelocity) > this.minVelocity) {
            this.velocity = Math.abs(this.mouseVelocity);
            this.direction = this.mouseVelocity > 0 ? 1 : -1;
        } else {
            this.velocity = 120;
        }

        this.isAnimating = true;
        this.updateSpeedIndicator();

        document.body.style.userSelect = '';
        document.body.style.cursor = '';
    }

    animate() {
        const currentTime = performance.now();
        const deltaTime = (currentTime - this.lastTime) / 1000;
        this.lastTime = currentTime;

        if (this.isAnimating && !this.isDragging) {
            if (this.velocity > this.minVelocity) {
                this.velocity *= this.friction;
            } else {
                this.velocity = Math.max(this.minVelocity, this.velocity);
            }

            this.position += this.velocity * this.direction * deltaTime;
            this.updateCardPosition();
            this.updateSpeedIndicator();
        }

        requestAnimationFrame(() => this.animate());
    }

    updateCardPosition() {
        const containerWidth = this.containerWidth;
        const cardLineWidth = this.cardLineWidth;

        if (this.position < -cardLineWidth) {
            this.position = containerWidth;
        } else if (this.position > containerWidth) {
            this.position = -cardLineWidth;
        }

        this.cardLine.style.transform = `translateX(${this.position}px)`;
        this.updateCardClipping();
    }

    updateSpeedIndicator() {
        if (this.speedIndicator) {
            this.speedIndicator.textContent = Math.round(this.velocity);
        }
    }

    toggleAnimation() {
        this.isAnimating = !this.isAnimating;
        const btn = document.querySelector('.evervault-control-btn');
        if (btn) {
            btn.textContent = this.isAnimating ? '⏸️ Pause' : '▶️ Play';
        }
    }

    resetPosition() {
        this.position = this.containerWidth;
        this.velocity = 120;
        this.direction = -1;
        this.isAnimating = true;
        this.isDragging = false;

        this.cardLine.style.transform = `translateX(${this.position}px)`;
        this.cardLine.classList.remove('dragging');

        this.updateSpeedIndicator();

        const btn = document.querySelector('.evervault-control-btn');
        if (btn) btn.textContent = '⏸️ Pause';
    }

    changeDirection() {
        this.direction *= -1;
        this.updateSpeedIndicator();
    }

    onWheel(e) {
        e.preventDefault();
        const scrollSpeed = 20;
        const delta = e.deltaY > 0 ? scrollSpeed : -scrollSpeed;
        this.position += delta;
        this.updateCardPosition();
        this.updateCardClipping();
    }

    generateCode(width, height) {
        const randInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
        const pick = (arr) => arr[randInt(0, arr.length - 1)];

        const library = [
            "// Sistema de gestión empresarial Clúster",
            "const EMPRESAS_API = '/api/empresas.php';",
            "const DESCUENTOS_API = '/api/descuentos.php';",
            "function loadEmpresas() { return fetch(EMPRESAS_API); }",
            "class EmpresaCard { constructor(data) { this.data = data; } }",
            "const empresaManager = { empresas: [], activas: [] };",
            "function filterBySecor(sector) { return empresas.filter(e => e.sector === sector); }",
            "const config = { apiUrl: '/api', timeout: 5000 };",
            "async function getEmpresaById(id) { const res = await fetch(`/api/empresas.php?id=${id}`); return res.json(); }",
            "const sectores = ['Tecnología', 'Salud', 'Educación', 'Comercio'];",
        ];

        let flow = library.join(' ');
        const totalChars = width * height;

        while (flow.length < totalChars + width) {
            const extra = pick(library).replace(/\s+/g, ' ').trim();
            flow += ' ' + extra;
        }

        let out = '';
        let offset = 0;
        for (let row = 0; row < height; row++) {
            let line = flow.slice(offset, offset + width);
            if (line.length < width) line = line + ' '.repeat(width - line.length);
            out += line + (row < height - 1 ? '\n' : '');
            offset += width;
        }
        return out;
    }

    calculateCodeDimensions(cardWidth, cardHeight) {
        const fontSize = 11;
        const lineHeight = 13;
        const charWidth = 6;
        const width = Math.floor(cardWidth / charWidth);
        const height = Math.floor(cardHeight / lineHeight);
        return { width, height, fontSize, lineHeight };
    }

    createEmpresaCard(empresa, index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'empresa-card-wrapper';
        wrapper.setAttribute('data-empresa-id', empresa.id);

        const normalCard = document.createElement('div');
        normalCard.className = 'empresa-card empresa-card-normal';
        normalCard.onclick = () => this.openEmpresaModal(empresa);

        const logoUrl = empresa.logo_url || `https://via.placeholder.com/400x250/1a1a1a/ffffff?text=${encodeURIComponent(empresa.nombre)}`;

        normalCard.innerHTML = `
            <img class="empresa-card-image" src="${logoUrl}" alt="${empresa.nombre}">
            <div class="empresa-card-overlay">
                <h3 class="empresa-card-title">${empresa.nombre}</h3>
                <p class="empresa-card-sector">${empresa.sector || 'General'}</p>
                ${empresa.descuento_porcentaje ? `<span class="empresa-card-discount">${empresa.descuento_porcentaje}% OFF</span>` : ''}
            </div>
        `;

        const asciiCard = document.createElement('div');
        asciiCard.className = 'empresa-card empresa-card-ascii';

        const asciiContent = document.createElement('div');
        asciiContent.className = 'ascii-content';

        const { width, height, fontSize, lineHeight } = this.calculateCodeDimensions(400, 250);
        asciiContent.style.fontSize = fontSize + 'px';
        asciiContent.style.lineHeight = lineHeight + 'px';
        asciiContent.textContent = this.generateCode(width, height);

        asciiCard.appendChild(asciiContent);
        wrapper.appendChild(normalCard);
        wrapper.appendChild(asciiCard);

        return wrapper;
    }

    updateCardClipping() {
        const scannerX = window.innerWidth / 2;
        const scannerWidth = 8;
        const scannerLeft = scannerX - scannerWidth / 2;
        const scannerRight = scannerX + scannerWidth / 2;
        let anyScanningActive = false;

        document.querySelectorAll('.empresa-card-wrapper').forEach((wrapper) => {
            const rect = wrapper.getBoundingClientRect();
            const cardLeft = rect.left;
            const cardRight = rect.right;
            const cardWidth = rect.width;

            const normalCard = wrapper.querySelector('.empresa-card-normal');
            const asciiCard = wrapper.querySelector('.empresa-card-ascii');

            if (cardLeft < scannerRight && cardRight > scannerLeft) {
                anyScanningActive = true;
                const scannerIntersectLeft = Math.max(scannerLeft - cardLeft, 0);
                const scannerIntersectRight = Math.min(scannerRight - cardLeft, cardWidth);

                const normalClipRight = (scannerIntersectLeft / cardWidth) * 100;
                const asciiClipLeft = (scannerIntersectRight / cardWidth) * 100;

                normalCard.style.setProperty('--clip-right', `${normalClipRight}%`);
                asciiCard.style.setProperty('--clip-left', `${asciiClipLeft}%`);

                if (!wrapper.hasAttribute('data-scanned') && scannerIntersectLeft > 0) {
                    wrapper.setAttribute('data-scanned', 'true');
                    const scanEffect = document.createElement('div');
                    scanEffect.className = 'scan-effect';
                    wrapper.appendChild(scanEffect);
                    setTimeout(() => {
                        if (scanEffect.parentNode) {
                            scanEffect.parentNode.removeChild(scanEffect);
                        }
                    }, 600);
                }
            } else {
                if (cardRight < scannerLeft) {
                    normalCard.style.setProperty('--clip-right', '100%');
                    asciiCard.style.setProperty('--clip-left', '100%');
                } else if (cardLeft > scannerRight) {
                    normalCard.style.setProperty('--clip-right', '0%');
                    asciiCard.style.setProperty('--clip-left', '0%');
                }
                wrapper.removeAttribute('data-scanned');
            }
        });

        if (window.empresaScannerSystem) {
            window.empresaScannerSystem.setScanningActive(anyScanningActive);
        }
    }

    updateAsciiContent() {
        document.querySelectorAll('.ascii-content').forEach((content) => {
            if (Math.random() < 0.15) {
                const { width, height } = this.calculateCodeDimensions(400, 250);
                content.textContent = this.generateCode(width, height);
            }
        });
    }

    populateCardLine() {
        this.cardLine.innerHTML = '';

        if (this.empresas.length === 0) {
            this.cardLine.innerHTML = '<div style="color: white; text-align: center; width: 100vw;">No hay empresas disponibles</div>';
            return;
        }

        // Duplicar empresas para efecto infinito
        const empresasToShow = [...this.empresas, ...this.empresas, ...this.empresas];

        empresasToShow.forEach((empresa, index) => {
            const cardWrapper = this.createEmpresaCard(empresa, index);
            this.cardLine.appendChild(cardWrapper);
        });
    }

    startPeriodicUpdates() {
        setInterval(() => {
            this.updateAsciiContent();
        }, 200);

        const updateClipping = () => {
            this.updateCardClipping();
            requestAnimationFrame(updateClipping);
        };
        updateClipping();
    }

    openEmpresaModal(empresa) {
        // Usar el modal handler existente si está disponible
        if (window.modalHandler && typeof window.modalHandler.showModal === 'function') {
            window.modalHandler.showModal(
                empresa.nombre,
                empresa.descripcion || 'Empresa socia de Clúster Automotriz Metropolitano',
                empresa.logo_url,
                empresa.descuento_porcentaje ? `${empresa.descuento_porcentaje}% de descuento` : '',
                empresa.sector || 'General'
            );
        } else {
            console.log('Abrir modal para empresa:', empresa.nombre);
        }
    }
}

// ============================================
// PARTICLE SYSTEM (THREE.JS)
// ============================================
class EmpresaParticleSystem {
    constructor() {
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.particles = null;
        this.particleCount = 400;
        this.canvas = document.getElementById('empresaParticleCanvas');

        if (!this.canvas) {
            console.error('❌ Canvas de partículas no encontrado');
            return;
        }

        this.init();
    }

    init() {
        this.scene = new THREE.Scene();

        this.camera = new THREE.OrthographicCamera(
            -window.innerWidth / 2,
            window.innerWidth / 2,
            125,
            -125,
            1,
            1000
        );
        this.camera.position.z = 100;

        this.renderer = new THREE.WebGLRenderer({
            canvas: this.canvas,
            alpha: true,
            antialias: true
        });
        this.renderer.setSize(window.innerWidth, 250);
        this.renderer.setClearColor(0x000000, 0);

        this.createParticles();
        this.animate();

        window.addEventListener('resize', () => this.onWindowResize());
    }

    createParticles() {
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(this.particleCount * 3);
        const colors = new Float32Array(this.particleCount * 3);
        const sizes = new Float32Array(this.particleCount);
        const velocities = new Float32Array(this.particleCount);

        const canvas = document.createElement('canvas');
        canvas.width = 100;
        canvas.height = 100;
        const ctx = canvas.getContext('2d');

        const half = canvas.width / 2;
        const gradient = ctx.createRadialGradient(half, half, 0, half, half, half);
        gradient.addColorStop(0.025, '#fff');
        gradient.addColorStop(0.1, 'hsl(217, 61%, 33%)');
        gradient.addColorStop(0.25, 'hsl(217, 64%, 6%)');
        gradient.addColorStop(1, 'transparent');

        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(half, half, half, 0, Math.PI * 2);
        ctx.fill();

        const texture = new THREE.CanvasTexture(canvas);

        for (let i = 0; i < this.particleCount; i++) {
            positions[i * 3] = (Math.random() - 0.5) * window.innerWidth * 2;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 250;
            positions[i * 3 + 2] = 0;

            colors[i * 3] = 1;
            colors[i * 3 + 1] = 1;
            colors[i * 3 + 2] = 1;

            const orbitRadius = Math.random() * 200 + 100;
            sizes[i] = (Math.random() * (orbitRadius - 60) + 60) / 8;

            velocities[i] = Math.random() * 60 + 30;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        this.velocities = velocities;

        const alphas = new Float32Array(this.particleCount);
        for (let i = 0; i < this.particleCount; i++) {
            alphas[i] = (Math.random() * 8 + 2) / 10;
        }
        geometry.setAttribute('alpha', new THREE.BufferAttribute(alphas, 1));
        this.alphas = alphas;

        const material = new THREE.ShaderMaterial({
            uniforms: {
                pointTexture: { value: texture },
                size: { value: 15.0 }
            },
            vertexShader: `
                attribute float alpha;
                varying float vAlpha;
                varying vec3 vColor;
                uniform float size;
                
                void main() {
                    vAlpha = alpha;
                    vColor = color;
                    vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
                    gl_PointSize = size;
                    gl_Position = projectionMatrix * mvPosition;
                }
            `,
            fragmentShader: `
                uniform sampler2D pointTexture;
                varying float vAlpha;
                varying vec3 vColor;
                
                void main() {
                    gl_FragColor = vec4(vColor, vAlpha) * texture2D(pointTexture, gl_PointCoord);
                }
            `,
            transparent: true,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
            vertexColors: true
        });

        this.particles = new THREE.Points(geometry, material);
        this.scene.add(this.particles);
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        if (this.particles) {
            const positions = this.particles.geometry.attributes.position.array;
            const alphas = this.particles.geometry.attributes.alpha.array;
            const time = Date.now() * 0.001;

            for (let i = 0; i < this.particleCount; i++) {
                positions[i * 3] += this.velocities[i] * 0.016;

                if (positions[i * 3] > window.innerWidth / 2 + 100) {
                    positions[i * 3] = -window.innerWidth / 2 - 100;
                    positions[i * 3 + 1] = (Math.random() - 0.5) * 250;
                }

                positions[i * 3 + 1] += Math.sin(time + i * 0.1) * 0.5;

                const twinkle = Math.floor(Math.random() * 10);
                if (twinkle === 1 && alphas[i] > 0) {
                    alphas[i] -= 0.05;
                } else if (twinkle === 2 && alphas[i] < 1) {
                    alphas[i] += 0.05;
                }

                alphas[i] = Math.max(0, Math.min(1, alphas[i]));
            }

            this.particles.geometry.attributes.position.needsUpdate = true;
            this.particles.geometry.attributes.alpha.needsUpdate = true;
        }

        this.renderer.render(this.scene, this.camera);
    }

    onWindowResize() {
        this.camera.left = -window.innerWidth / 2;
        this.camera.right = window.innerWidth / 2;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(window.innerWidth, 250);
    }

    destroy() {
        if (this.renderer) {
            this.renderer.dispose();
        }
        if (this.particles) {
            this.scene.remove(this.particles);
            this.particles.geometry.dispose();
            this.particles.material.dispose();
        }
    }
}

// ============================================
// PARTICLE SCANNER (CANVAS 2D)
// ============================================
class EmpresaParticleScanner {
    constructor() {
        this.canvas = document.getElementById('empresaScannerCanvas');
        if (!this.canvas) {
            console.error('❌ Canvas de scanner no encontrado');
            return;
        }

        this.ctx = this.canvas.getContext('2d');
        this.animationId = null;

        this.w = window.innerWidth;
        this.h = 300;
        this.particles = [];
        this.count = 0;
        this.maxParticles = 800;
        this.intensity = 0.8;
        this.lightBarX = this.w / 2;
        this.lightBarWidth = 3;
        this.fadeZone = 60;

        this.scanTargetIntensity = 1.8;
        this.scanTargetParticles = 2500;
        this.scanTargetFadeZone = 35;

        this.scanningActive = false;

        this.baseIntensity = this.intensity;
        this.baseMaxParticles = this.maxParticles;
        this.baseFadeZone = this.fadeZone;

        this.currentIntensity = this.intensity;
        this.currentMaxParticles = this.maxParticles;
        this.currentFadeZone = this.fadeZone;
        this.transitionSpeed = 0.05;
        this.currentGlowIntensity = 1;

        this.setupCanvas();
        this.createGradientCache();
        this.initParticles();
        this.animate();

        window.addEventListener('resize', () => this.onResize());
    }

    setupCanvas() {
        this.canvas.width = this.w;
        this.canvas.height = this.h;
        this.canvas.style.width = this.w + 'px';
        this.canvas.style.height = this.h + 'px';
        this.ctx.clearRect(0, 0, this.w, this.h);
    }

    onResize() {
        this.w = window.innerWidth;
        this.lightBarX = this.w / 2;
        this.setupCanvas();
    }

    createGradientCache() {
        this.gradientCanvas = document.createElement('canvas');
        this.gradientCtx = this.gradientCanvas.getContext('2d');
        this.gradientCanvas.width = 16;
        this.gradientCanvas.height = 16;

        const half = this.gradientCanvas.width / 2;
        const gradient = this.gradientCtx.createRadialGradient(half, half, 0, half, half, half);
        gradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
        gradient.addColorStop(0.3, 'rgba(196, 181, 253, 0.8)');
        gradient.addColorStop(0.7, 'rgba(139, 92, 246, 0.4)');
        gradient.addColorStop(1, 'transparent');

        this.gradientCtx.fillStyle = gradient;
        this.gradientCtx.beginPath();
        this.gradientCtx.arc(half, half, half, 0, Math.PI * 2);
        this.gradientCtx.fill();
    }

    random(min, max) {
        if (arguments.length < 2) {
            max = min;
            min = 0;
        }
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    randomFloat(min, max) {
        return Math.random() * (max - min) + min;
    }

    createParticle() {
        const intensityRatio = this.intensity / this.baseIntensity;
        const speedMultiplier = 1 + (intensityRatio - 1) * 1.2;
        const sizeMultiplier = 1 + (intensityRatio - 1) * 0.7;

        return {
            x: this.lightBarX + this.randomFloat(-this.lightBarWidth / 2, this.lightBarWidth / 2),
            y: this.randomFloat(0, this.h),
            vx: this.randomFloat(0.2, 1.0) * speedMultiplier,
            vy: this.randomFloat(-0.15, 0.15) * speedMultiplier,
            radius: this.randomFloat(0.4, 1) * sizeMultiplier,
            alpha: this.randomFloat(0.6, 1),
            decay: this.randomFloat(0.005, 0.025) * (2 - intensityRatio * 0.5),
            originalAlpha: 0,
            life: 1.0,
            time: 0,
            twinkleSpeed: this.randomFloat(0.02, 0.08) * speedMultiplier,
            twinkleAmount: this.randomFloat(0.1, 0.25)
        };
    }

    initParticles() {
        for (let i = 0; i < this.maxParticles; i++) {
            const particle = this.createParticle();
            particle.originalAlpha = particle.alpha;
            this.count++;
            this.particles[this.count] = particle;
        }
    }

    updateParticle(particle) {
        particle.x += particle.vx;
        particle.y += particle.vy;
        particle.time++;

        particle.alpha = particle.originalAlpha * particle.life +
            Math.sin(particle.time * particle.twinkleSpeed) * particle.twinkleAmount;

        particle.life -= particle.decay;

        if (particle.x > this.w + 10 || particle.life <= 0) {
            this.resetParticle(particle);
        }
    }

    resetParticle(particle) {
        particle.x = this.lightBarX + this.randomFloat(-this.lightBarWidth / 2, this.lightBarWidth / 2);
        particle.y = this.randomFloat(0, this.h);
        particle.vx = this.randomFloat(0.2, 1.0);
        particle.vy = this.randomFloat(-0.15, 0.15);
        particle.alpha = this.randomFloat(0.6, 1);
        particle.originalAlpha = particle.alpha;
        particle.life = 1.0;
        particle.time = 0;
    }

    drawParticle(particle) {
        if (particle.life <= 0) return;

        let fadeAlpha = 1;

        if (particle.y < this.fadeZone) {
            fadeAlpha = particle.y / this.fadeZone;
        } else if (particle.y > this.h - this.fadeZone) {
            fadeAlpha = (this.h - particle.y) / this.fadeZone;
        }

        fadeAlpha = Math.max(0, Math.min(1, fadeAlpha));

        this.ctx.globalAlpha = particle.alpha * fadeAlpha;
        this.ctx.drawImage(
            this.gradientCanvas,
            particle.x - particle.radius,
            particle.y - particle.radius,
            particle.radius * 2,
            particle.radius * 2
        );
    }

    drawLightBar() {
        const verticalGradient = this.ctx.createLinearGradient(0, 0, 0, this.h);
        verticalGradient.addColorStop(0, 'rgba(255, 255, 255, 0)');
        verticalGradient.addColorStop(this.fadeZone / this.h, 'rgba(255, 255, 255, 1)');
        verticalGradient.addColorStop(1 - this.fadeZone / this.h, 'rgba(255, 255, 255, 1)');
        verticalGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

        this.ctx.globalCompositeOperation = 'lighter';

        const targetGlowIntensity = this.scanningActive ? 3.5 : 1;
        this.currentGlowIntensity += (targetGlowIntensity - this.currentGlowIntensity) * this.transitionSpeed;

        const glowIntensity = this.currentGlowIntensity;
        const lineWidth = this.lightBarWidth;

        // Core beam
        const coreGradient = this.ctx.createLinearGradient(
            this.lightBarX - lineWidth / 2, 0,
            this.lightBarX + lineWidth / 2, 0
        );
        coreGradient.addColorStop(0, 'rgba(255, 255, 255, 0)');
        coreGradient.addColorStop(0.5, `rgba(255, 255, 255, ${1 * glowIntensity})`);
        coreGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

        this.ctx.globalAlpha = 1;
        this.ctx.fillStyle = coreGradient;
        this.ctx.fillRect(this.lightBarX - lineWidth / 2, 0, lineWidth, this.h);

        // Glow layers
        const glow1Gradient = this.ctx.createLinearGradient(
            this.lightBarX - lineWidth * 2, 0,
            this.lightBarX + lineWidth * 2, 0
        );
        glow1Gradient.addColorStop(0, 'rgba(139, 92, 246, 0)');
        glow1Gradient.addColorStop(0.5, `rgba(196, 181, 253, ${0.8 * glowIntensity})`);
        glow1Gradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

        this.ctx.globalAlpha = this.scanningActive ? 1.0 : 0.8;
        this.ctx.fillStyle = glow1Gradient;
        this.ctx.fillRect(this.lightBarX - lineWidth * 2, 0, lineWidth * 4, this.h);

        // Mask with vertical gradient
        this.ctx.globalCompositeOperation = 'destination-in';
        this.ctx.globalAlpha = 1;
        this.ctx.fillStyle = verticalGradient;
        this.ctx.fillRect(0, 0, this.w, this.h);
    }

    render() {
        const targetIntensity = this.scanningActive ? this.scanTargetIntensity : this.baseIntensity;
        const targetMaxParticles = this.scanningActive ? this.scanTargetParticles : this.baseMaxParticles;
        const targetFadeZone = this.scanningActive ? this.scanTargetFadeZone : this.baseFadeZone;

        this.currentIntensity += (targetIntensity - this.currentIntensity) * this.transitionSpeed;
        this.currentMaxParticles += (targetMaxParticles - this.currentMaxParticles) * this.transitionSpeed;
        this.currentFadeZone += (targetFadeZone - this.currentFadeZone) * this.transitionSpeed;

        this.intensity = this.currentIntensity;
        this.maxParticles = Math.floor(this.currentMaxParticles);
        this.fadeZone = this.currentFadeZone;

        this.ctx.globalCompositeOperation = 'source-over';
        this.ctx.clearRect(0, 0, this.w, this.h);

        this.drawLightBar();

        this.ctx.globalCompositeOperation = 'lighter';
        for (let i = 1; i <= this.count; i++) {
            if (this.particles[i]) {
                this.updateParticle(this.particles[i]);
                this.drawParticle(this.particles[i]);
            }
        }

        // Create new particles based on intensity
        if (Math.random() < this.intensity && this.count < this.maxParticles) {
            const particle = this.createParticle();
            particle.originalAlpha = particle.alpha;
            this.count++;
            this.particles[this.count] = particle;
        }

        // Cleanup excess particles
        if (this.count > this.maxParticles + 200) {
            const excessCount = Math.min(15, this.count - this.maxParticles);
            for (let i = 0; i < excessCount; i++) {
                delete this.particles[this.count - i];
            }
            this.count -= excessCount;
        }
    }

    animate() {
        this.render();
        this.animationId = requestAnimationFrame(() => this.animate());
    }

    setScanningActive(active) {
        this.scanningActive = active;
    }

    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        this.particles = [];
        this.count = 0;
    }
}

// ============================================
// GLOBAL CONTROL FUNCTIONS
// ============================================
let empresaCardStream;
let empresaParticleSystem;
let empresaScannerSystem;

function toggleAnimation() {
    if (empresaCardStream) {
        empresaCardStream.toggleAnimation();
    }
}

function resetPosition() {
    if (empresaCardStream) {
        empresaCardStream.resetPosition();
    }
}

function changeDirection() {
    if (empresaCardStream) {
        empresaCardStream.changeDirection();
    }
}

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Esperar a que Three.js esté disponible
    if (typeof THREE !== 'undefined') {
        empresaCardStream = new EmpresaCardStreamController();
        empresaParticleSystem = new EmpresaParticleSystem();
        empresaScannerSystem = new EmpresaParticleScanner();

        window.empresaScannerSystem = empresaScannerSystem;

        console.log('✅ Sistema Evervault de empresas inicializado');
    } else {
        console.error('❌ Three.js no está disponible');
    }
});
