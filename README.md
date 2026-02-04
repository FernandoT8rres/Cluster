# 🚗 Clúster Automotriz Metropolitano - Sistema de Intranet

[![Estado](https://img.shields.io/badge/Estado-Producción-success)](https://intranet.clautmetropolitano.mx)
[![Versión](https://img.shields.io/badge/Versión-2.0-blue)]()
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql)](https://mysql.com)
[![Three.js](https://img.shields.io/badge/Three.js-r128-black?logo=three.js)](https://threejs.org)

Sistema integral de gestión interna para el Clúster Automotriz Metropolitano, diseñado para facilitar la colaboración entre empresas socias, gestión de eventos, descuentos exclusivos y comunicación organizacional.

---

## 📋 Tabla de Contenidos

- [Características Principales](#-características-principales)
- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Tecnologías Utilizadas](#-tecnologías-utilizadas)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Módulos del Sistema](#-módulos-del-sistema)
- [Seguridad](#-seguridad)
- [API Documentation](#-api-documentation)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

---

## ✨ Características Principales

### 🎨 Animación Evervault Premium
- **Sistema de partículas 3D** con Three.js (400 partículas)
- **Scanner beam vertical** con efectos de glow dinámicos
- **Overlay ASCII** con código generado en tiempo real
- **Controles interactivos** (pause, reset, direction)
- **Drag & drop** + wheel scroll support
- **Responsive design** optimizado para todos los dispositivos

### 👥 Gestión de Usuarios
- Sistema de autenticación basado en sesiones PHP
- Roles y permisos (Admin, Empresa, Usuario)
- Perfil de usuario personalizable
- Seguridad con CSRF protection y rate limiting

### 🏢 Directorio de Empresas
- Catálogo completo de empresas socias
- Filtros por sector y búsqueda avanzada
- Logos y descripciones detalladas
- Integración con sistema de descuentos

### 💰 Sistema de Descuentos
- Descuentos exclusivos para empleados
- Tarjetas interactivas con efecto flip
- Filtrado por categoría y empresa
- Gestión administrativa de ofertas

### 📅 Gestión de Eventos
- Calendario de eventos corporativos
- Registro de asistencia
- Notificaciones automáticas
- Panel de administración completo

### 📰 Boletines y Comunicación
- Sistema de publicación de boletines
- Carga de documentos PDF
- Categorización y búsqueda
- Área pública para visitantes

### 📊 Dashboard Analítico
- Estadísticas en tiempo real
- Gráficos interactivos con Chart.js
- Métricas de participación
- Reportes personalizables

### 🎯 Gestión de Comités
- Organización de comités técnicos
- Asignación de miembros
- Seguimiento de actividades
- Documentación colaborativa

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │  HTML5   │  │   CSS3   │  │JavaScript│  │Three.js │ │
│  │ Tailwind │  │  Custom  │  │  ES6+    │  │ Canvas  │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                   MIDDLEWARE LAYER                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │   CSRF   │  │   Rate   │  │   JWT    │  │  Input  │ │
│  │Protection│  │ Limiter  │  │Validator │  │Validator│ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                    BACKEND LAYER                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │   PHP    │  │  Session │  │   API    │  │  File   │ │
│  │  8.0+    │  │  Manager │  │Endpoints │  │ Upload  │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                   DATABASE LAYER                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │              MySQL 8.0+                          │   │
│  │  • usuarios  • empresas  • eventos  • descuentos │   │
│  │  • boletines • comites   • banners  • logs       │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

---

## 🛠️ Tecnologías Utilizadas

### Frontend
- **HTML5** - Estructura semántica
- **CSS3** - Estilos modernos con variables CSS
- **Tailwind CSS** - Framework de utilidades
- **JavaScript ES6+** - Lógica del cliente
- **Three.js r128** - Gráficos 3D y partículas
- **Chart.js** - Visualización de datos
- **GSAP** - Animaciones avanzadas

### Backend
- **PHP 8.0+** - Lenguaje del servidor
- **MySQL 8.0+** - Base de datos relacional
- **PDO** - Capa de abstracción de datos
- **Sessions** - Gestión de autenticación

### Seguridad
- **CSRF Protection** - Tokens anti-falsificación
- **Rate Limiting** - Prevención de ataques
- **Input Validation** - Sanitización de datos
- **JWT** - Tokens de autenticación
- **Password Hashing** - bcrypt

### DevOps
- **Git** - Control de versiones
- **GitHub** - Repositorio remoto
- **Hostinger** - Hosting en producción

---

## 📁 Estructura del Proyecto

```
Claut_BD/
├── build/                          # Aplicación principal
│   ├── api/                        # API Endpoints
│   │   ├── auth/                   # Autenticación
│   │   ├── empresas.php            # Gestión de empresas
│   │   ├── descuentos.php          # Sistema de descuentos
│   │   ├── eventos.php             # Gestión de eventos
│   │   └── boletines.php           # Publicación de boletines
│   │
│   ├── js/                         # JavaScript Modules
│   │   ├── empresas-evervault.js   # Animación Evervault ⭐
│   │   ├── auth-session.js         # Autenticación
│   │   ├── dashboard-dinamico.js   # Dashboard
│   │   ├── descuentos-frontend.js  # Descuentos
│   │   └── eventos.js              # Eventos
│   │
│   ├── css/                        # Estilos
│   │   └── estilos-empresas.css    # Estilos de empresas
│   │
│   ├── middleware/                 # Capa de seguridad
│   │   ├── csrf-protection.php     # CSRF tokens
│   │   ├── rate-limiter.php        # Rate limiting
│   │   ├── jwt-validator.php       # JWT validation
│   │   └── api-validator.php       # API validation
│   │
│   ├── config/                     # Configuración
│   │   ├── database.php            # Conexión DB
│   │   ├── session-config.php      # Sesiones
│   │   └── env-loader.php          # Variables de entorno
│   │
│   ├── uploads/                    # Archivos subidos
│   │   ├── banners/                # Banners del carrusel
│   │   ├── logos/                  # Logos de empresas
│   │   └── documentos/             # Documentos PDF
│   │
│   ├── pages/                      # Páginas HTML
│   │   ├── dashboard.html          # Dashboard principal
│   │   ├── empresas-convenio.html  # Empresas socias ⭐
│   │   ├── descuentos.html         # Descuentos
│   │   ├── eventos.html            # Eventos
│   │   └── boletines.html          # Boletines
│   │
│   └── database/                   # Scripts SQL
│       └── claut_intranet.sql      # Schema completo
│
├── .env                            # Variables de entorno
├── .gitignore                      # Archivos ignorados
├── README.md                       # Este archivo
└── package.json                    # Dependencias NPM

```

---

## 🚀 Instalación

### Prerrequisitos

- PHP 8.0 o superior
- MySQL 8.0 o superior
- Servidor web (Apache/Nginx)
- Composer (opcional)
- Node.js (para desarrollo)

### Paso 1: Clonar el repositorio

```bash
git clone https://github.com/FernandoT8rres/Cluster.git
cd Cluster
```

### Paso 2: Configurar base de datos

```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE claut_intranet;
exit;

# Importar schema
mysql -u root -p claut_intranet < build/database/claut_intranet.sql
```

### Paso 3: Configurar variables de entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar con tus credenciales
nano .env
```

Contenido del `.env`:
```env
DB_HOST=localhost
DB_NAME=claut_intranet
DB_USER=tu_usuario
DB_PASS=tu_contraseña
DB_PORT=3306

SESSION_LIFETIME=3600
SESSION_NAME=CLAUT_SESSION
CSRF_TOKEN_LIFETIME=3600
```

### Paso 4: Configurar permisos

```bash
# Dar permisos de escritura a uploads
chmod -R 755 build/uploads
chown -R www-data:www-data build/uploads
```

### Paso 5: Iniciar servidor

```bash
# Desarrollo
php -S localhost:8000 -t build/

# Producción (configurar Apache/Nginx)
```

---

## ⚙️ Configuración

### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Seguridad
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

### Nginx

```nginx
server {
    listen 80;
    server_name intranet.clautmetropolitano.mx;
    root /var/www/Cluster/build;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## 📦 Módulos del Sistema

### 1. **Animación Evervault** ⭐ (Nuevo)

**Archivo:** `build/js/empresas-evervault.js`

Sistema de animación premium para el carrusel de empresas:

- **EmpresaCardStreamController:** Control de tarjetas y animación
- **EmpresaParticleSystem:** Partículas 3D con Three.js
- **EmpresaParticleScanner:** Scanner beam con Canvas 2D

**Características:**
- 400 partículas 3D en movimiento
- Scanner beam vertical con glow
- Código ASCII dinámico
- Drag & drop interactivo
- Wheel scroll support

### 2. **Sistema de Autenticación**

**Archivos:** 
- `build/js/auth-session.js`
- `build/api/auth/login-compatible.php`

**Características:**
- Login basado en sesiones PHP
- Verificación automática de sesión
- Redirección inteligente
- Logout seguro

### 3. **Dashboard Analítico**

**Archivo:** `build/js/dashboard-dinamico.js`

**Métricas:**
- Total de empresas socias
- Eventos activos
- Descuentos disponibles
- Usuarios registrados

### 4. **Gestión de Empresas**

**API:** `build/api/empresas.php`

**Endpoints:**
- `GET /api/empresas.php` - Listar empresas
- `POST /api/empresas.php` - Crear empresa
- `PUT /api/empresas.php?id={id}` - Actualizar
- `DELETE /api/empresas.php?id={id}` - Eliminar

### 5. **Sistema de Descuentos**

**Archivos:**
- `build/descuentos.html`
- `build/js/descuentos-frontend.js`

**Características:**
- Tarjetas con efecto flip
- Filtros por categoría
- Búsqueda en tiempo real
- Gestión administrativa

### 6. **Gestión de Eventos**

**API:** `build/api/eventos.php`

**Funcionalidades:**
- Crear/editar eventos
- Registro de asistencia
- Notificaciones
- Calendario interactivo

---

## 🔒 Seguridad

### Medidas Implementadas

#### 1. **CSRF Protection**
```php
// Generar token
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

// Validar token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token inválido');
}
```

#### 2. **Rate Limiting**
```php
// Límite: 100 requests por hora
$limiter = new RateLimiter();
if (!$limiter->check($ip, 100, 3600)) {
    http_response_code(429);
    die('Demasiadas solicitudes');
}
```

#### 3. **Input Validation**
```php
// Sanitizar entrada
$input = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
    die('Email inválido');
}
```

#### 4. **Password Hashing**
```php
// Hashear contraseña
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verificar
if (password_verify($password, $hash)) {
    // Login exitoso
}
```

#### 5. **Session Security**
```php
// Configuración segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
```

---

## 📡 API Documentation

### Base URL
```
https://intranet.clautmetropolitano.mx/api/
```

### Autenticación

**POST** `/api/auth/login-compatible.php`

Request:
```json
{
  "email": "usuario@ejemplo.com",
  "password": "contraseña123"
}
```

Response:
```json
{
  "success": true,
  "user": {
    "id": 1,
    "nombre": "Fernando Torres",
    "email": "fernando@ejemplo.com",
    "rol": "admin"
  }
}
```

### Empresas

**GET** `/api/empresas.php`

Response:
```json
{
  "success": true,
  "empresas": [
    {
      "id": 1,
      "nombre": "Empresa XYZ",
      "sector": "Tecnología",
      "logo_url": "/uploads/logos/empresa1.png",
      "descripcion": "Descripción...",
      "descuento_porcentaje": 15,
      "estado": "activo"
    }
  ]
}
```

### Descuentos

**GET** `/api/descuentos.php`

Response:
```json
{
  "success": true,
  "descuentos": [
    {
      "id": 1,
      "titulo": "20% en servicios",
      "descripcion": "Descuento especial",
      "empresa_id": 1,
      "empresa_nombre": "Empresa XYZ",
      "porcentaje": 20,
      "vigencia": "2026-12-31"
    }
  ]
}
```

---

## 🎨 Personalización

### Colores del Sistema

Editar en `build/css/estilos-empresas.css`:

```css
:root {
  --claut-primary: #c9302c;      /* Rojo Clúster */
  --claut-secondary: #1a1a1a;    /* Negro */
  --claut-accent: #f8f9fa;       /* Gris claro */
  --claut-success: #28a745;      /* Verde */
  --claut-warning: #ffc107;      /* Amarillo */
  --claut-danger: #dc3545;       /* Rojo */
}
```

### Logo

Reemplazar archivo en:
```
build/assets/img/logo-claut.png
```

---

## 🧪 Testing

### Ejecutar tests

```bash
# Tests unitarios (si están configurados)
php vendor/bin/phpunit

# Tests de integración
npm test
```

### Verificar seguridad

```bash
# Escanear vulnerabilidades
composer require --dev sensiolabs/security-checker
./vendor/bin/security-checker security:check
```

---

## 📈 Roadmap

### Versión 2.1 (Q2 2026)
- [ ] App móvil nativa (React Native)
- [ ] Notificaciones push
- [ ] Chat en tiempo real
- [ ] Integración con redes sociales

### Versión 2.2 (Q3 2026)
- [ ] Sistema de facturación
- [ ] Reportes avanzados
- [ ] API REST completa
- [ ] Webhooks

### Versión 3.0 (Q4 2026)
- [ ] Migración a microservicios
- [ ] GraphQL API
- [ ] Machine Learning para recomendaciones
- [ ] PWA completa

---

## 🤝 Contribución

### Cómo contribuir

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Convenciones de Commits

```
feat: Nueva característica
fix: Corrección de bug
docs: Documentación
style: Formato de código
refactor: Refactorización
test: Tests
chore: Mantenimiento
```

---

## 👥 Equipo

- **Fernando Torres** - Desarrollador Principal - [@FernandoT8rres](https://github.com/FernandoT8rres)

---

## 📄 Licencia

Este proyecto es propiedad del **Clúster Automotriz Metropolitano**.  
Todos los derechos reservados © 2026

---

## 📞 Contacto

**Clúster Automotriz Metropolitano**
- Website: [https://clautmetropolitano.mx](https://clautmetropolitano.mx)
- Intranet: [https://intranet.clautmetropolitano.mx](https://intranet.clautmetropolitano.mx)
- Email: contacto@clautmetropolitano.mx

---

## 🙏 Agradecimientos

- [Three.js](https://threejs.org) - Gráficos 3D
- [Tailwind CSS](https://tailwindcss.com) - Framework CSS
- [Chart.js](https://chartjs.org) - Visualización de datos
- [GSAP](https://greensock.com/gsap) - Animaciones
- Comunidad de desarrolladores PHP

---

**⭐ Si este proyecto te fue útil, considera darle una estrella en GitHub!**
