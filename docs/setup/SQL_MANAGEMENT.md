# 📚 Guía de Gestión de Archivos SQL - Sistema Claut Intranet

## 🎯 Propósito de Este Documento

Esta guía documenta todos los archivos SQL del sistema, su propósito, y cómo actuar en diferentes escenarios (instalación, migración, respaldo, etc.).

---

## 📁 Archivos SQL Conservados (3 archivos)

### 1. `build/setup/install_database.sql`

**Propósito:** Script principal de instalación de la base de datos completa

**Contiene:**
- Creación de todas las tablas del sistema
- Datos iniciales necesarios
- Índices y claves foráneas
- Datos de ejemplo (usuarios, empresas, eventos)

**Usado por:**
- `build/setup/init_database.php` (línea 29)

**Cuándo usarlo:**
- ✅ Instalación inicial del sistema
- ✅ Reinstalación en nuevo servidor
- ✅ Restauración completa de estructura

**Comando para ejecutar:**
```bash
mysql -u usuario -p claut_intranet < build/setup/install_database.sql
```

**O desde PHP:**
```bash
php build/setup/init_database.php
```

---

### 2. `build/setup/create_database.sql`

**Propósito:** Creación de la base de datos y usuario MySQL

**Contiene:**
- `CREATE DATABASE claut_intranet`
- `CREATE USER` con credenciales
- `GRANT PRIVILEGES` para el usuario

**Cuándo usarlo:**
- ✅ Primera instalación (antes de install_database.sql)
- ✅ Creación de BD en servidor nuevo
- ✅ Configuración de permisos de usuario

**Comando para ejecutar:**
```bash
mysql -u root -p < build/setup/create_database.sql
```

**Importante:**
- Ejecutar como usuario root o con privilegios CREATE DATABASE
- Editar credenciales antes de ejecutar en producción

---

### 3. `build/database/claut_intranet.sql`

**Propósito:** Respaldo completo de la estructura y datos

**Contiene:**
- Estructura completa de todas las tablas
- Datos de ejemplo
- Configuraciones iniciales

**Cuándo usarlo:**
- ✅ Respaldo de referencia
- ✅ Restauración de emergencia
- ✅ Documentación de estructura
- ✅ Migración a otro servidor

**Comando para ejecutar:**
```bash
mysql -u usuario -p claut_intranet < build/database/claut_intranet.sql
```

---

## 🗑️ Archivos SQL Eliminados (15 archivos)

### Categoría 1: Migraciones Ejecutadas (6 archivos)

Estos archivos ya cumplieron su propósito y las tablas/columnas ya existen.

| Archivo | Propósito | Estado |
|---------|-----------|--------|
| `fix_usuarios_perfil_schema.sql` | Corrección de esquema de usuarios | ✅ Ejecutado |
| `update_usuarios_perfil.sql` | Actualización de perfiles | ✅ Ejecutado |
| `add_user_tracking_columns.sql` | Agregar columnas de tracking | ✅ Ejecutado |
| `update_descuentos_estructura.sql` | Actualizar estructura descuentos | ✅ Ejecutado |
| `crear_tabla_archivos_boletines.sql` | Crear tabla de archivos | ✅ Ejecutado |
| `create_boletines_table.sql` | Crear tabla boletines | ✅ Ejecutado |

**Backup ubicado en:** `backup_sql_YYYYMMDD_HHMMSS/`

---

### Categoría 2: Estructuras Duplicadas (3 archivos)

Información ya incluida en `install_database.sql`

| Archivo | Duplicado de |
|---------|--------------|
| `boletines_estructura.sql` | install_database.sql |
| `descuentos_schema.sql` | install_database.sql |
| `create_mensajes_comites.sql` | install_database.sql |

---

### Categoría 3: Datos de Ejemplo (3 archivos)

Datos de prueba ya obsoletos

| Archivo | Contenido |
|---------|-----------|
| `empresas_convenio_data.sql` | Empresas de ejemplo |
| `empresas_ejemplos_destacadas.sql` | Empresas destacadas |
| `empresas_convenio.sql` | Datos de convenio |

---

### Categoría 4: Scripts de Inicialización (3 archivos)

Scripts referenciados pero ya no necesarios

| Archivo | Referenciado por | Necesario |
|---------|------------------|-----------|
| `seeds/empresas_seed.sql` | `utils/init_database.php` | ❌ No (ya hay datos) |
| `banner_system.sql` | `setup/init_banner_system.php` | ❌ No (tabla ya existe) |
| `backend/database_setup.sql` | Backend | ❌ No (redundante) |

---

## 🔄 Escenarios de Uso

### Escenario 1: Instalación Inicial Completa

**Pasos:**

1. **Crear base de datos:**
```bash
mysql -u root -p < build/setup/create_database.sql
```

2. **Instalar estructura y datos:**
```bash
mysql -u usuario -p claut_intranet < build/setup/install_database.sql
```

O usar el script PHP:
```bash
php build/setup/init_database.php
```

3. **Verificar instalación:**
```bash
mysql -u usuario -p -e "USE claut_intranet; SHOW TABLES;"
```

---

### Escenario 2: Migración a Nuevo Servidor

**Pasos:**

1. **Exportar datos actuales:**
```bash
mysqldump -u usuario -p claut_intranet > backup_produccion_$(date +%Y%m%d).sql
```

2. **En el nuevo servidor, crear BD:**
```bash
mysql -u root -p < build/setup/create_database.sql
```

3. **Importar datos:**
```bash
mysql -u usuario -p claut_intranet < backup_produccion_YYYYMMDD.sql
```

---

### Escenario 3: Restauración de Emergencia

**Si perdiste la base de datos:**

1. **Usar respaldo de producción:**
```bash
mysql -u usuario -p claut_intranet < backup_produccion_YYYYMMDD.sql
```

2. **Si no hay respaldo, usar estructura base:**
```bash
mysql -u usuario -p claut_intranet < build/database/claut_intranet.sql
```
⚠️ Esto restaurará solo la estructura y datos de ejemplo

---

### Escenario 4: Agregar Nueva Tabla/Columna

**NO crear archivos SQL sueltos. Usar migraciones:**

1. **Crear script de migración:**
```sql
-- migrations/YYYYMMDD_nombre_descriptivo.sql
ALTER TABLE tabla_existente ADD COLUMN nueva_columna VARCHAR(255);
```

2. **Ejecutar:**
```bash
mysql -u usuario -p claut_intranet < migrations/YYYYMMDD_nombre_descriptivo.sql
```

3. **Actualizar install_database.sql** para futuras instalaciones

---

### Escenario 5: Respaldo Programado

**Crear respaldos automáticos:**

```bash
#!/bin/bash
# backup_db.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u usuario -p claut_intranet > backups/claut_$DATE.sql
gzip backups/claut_$DATE.sql
# Eliminar backups > 30 días
find backups/ -name "*.sql.gz" -mtime +30 -delete
```

**Programar con cron:**
```bash
0 2 * * * /path/to/backup_db.sh
```

---

## ⚠️ Advertencias Importantes

### ❌ NO Hacer:

1. **NO ejecutar migraciones antiguas** en producción
   - Las migraciones en `backup_sql_*/` ya se ejecutaron
   - Ejecutarlas de nuevo puede causar errores

2. **NO editar install_database.sql directamente** en producción
   - Solo editar para futuras instalaciones
   - Usar migraciones para cambios en producción

3. **NO eliminar create_database.sql o install_database.sql**
   - Son necesarios para reinstalaciones

### ✅ SÍ Hacer:

1. **Respaldar antes de cambios importantes**
```bash
mysqldump -u usuario -p claut_intranet > backup_antes_cambio.sql
```

2. **Probar migraciones en desarrollo primero**

3. **Documentar cambios en este archivo**

---

## 📊 Estructura Actual de la Base de Datos

### Tablas Principales:

| Tabla | Propósito | Registros Aprox. |
|-------|-----------|------------------|
| `usuarios` | Usuarios del sistema | Variable |
| `empresas` | Empresas en convenio | Variable |
| `eventos` | Eventos y actividades | Variable |
| `boletines` | Boletines informativos | Variable |
| `comites` | Comités activos | Variable |
| `descuentos` | Descuentos disponibles | Variable |
| `documentos` | Documentos compartidos | Variable |
| `notificaciones` | Notificaciones de usuarios | Variable |
| `banner_carrusel` | Banners del carrusel | ~5 |

### Verificar Estructura:

```bash
mysql -u usuario -p -e "USE claut_intranet; SHOW TABLES;"
```

---

## 🔍 Troubleshooting

### Problema: "Table already exists"

**Solución:**
```sql
DROP TABLE IF EXISTS nombre_tabla;
-- Luego ejecutar la creación
```

### Problema: "Access denied"

**Solución:**
```sql
-- Como root
GRANT ALL PRIVILEGES ON claut_intranet.* TO 'usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Problema: "Unknown database"

**Solución:**
```bash
mysql -u root -p < build/setup/create_database.sql
```

---

## 📝 Historial de Cambios

| Fecha | Cambio | Archivos Afectados |
|-------|--------|-------------------|
| 2026-01-29 | Limpieza SQL - Eliminados 15 archivos obsoletos | Ver lista arriba |
| 2026-01-29 | Conservados 3 archivos críticos | install, create, claut_intranet |

---

## 🔗 Referencias

- **Documentación MySQL:** https://dev.mysql.com/doc/
- **Guía de Migraciones:** Ver `SYSTEM_ARCHITECTURE.md`
- **Respaldos:** `backup_sql_*/`

---

**Última actualización:** 29 de enero de 2026  
**Mantenido por:** Equipo de Desarrollo Claut  
**Versión:** 1.0
