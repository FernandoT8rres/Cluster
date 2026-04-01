# 📧 Guía de Configuración — Sistema de Correo SMTP

## Sistema implementado el: 2026-04-01
## Proveedor: **Microsoft 365 (Outlook)** — `auxsistemas@clautmetropolitano.mx`

---

## ¿Por qué Outlook / Microsoft 365?

El correo `auxsistemas@clautmetropolitano.mx` está alojado en **Microsoft 365 (Exchange Online)** y se accede via Outlook. Microsoft tiene su propio servidor SMTP (`smtp.office365.com`) que puedes usar directamente con tus credenciales de Outlook.

---

## Datos de Configuración SMTP

| Campo | Valor |
|-------|-------|
| **Cuenta** | `auxsistemas@clautmetropolitano.mx` |
| **Servidor SMTP** | `smtp.office365.com` |
| **Puerto** | `587` |
| **Cifrado** | `TLS (STARTTLS)` |
| **Password** | La misma con la que entras a Outlook |

---

## 🔑 Cómo obtener/configurar la contraseña

### Caso A — SIN autenticación multifactor (MFA) — Lo más común en cuentas organizacionales

La contraseña es simplemente **la misma que usas para entrar a Outlook**.

1. Abir `build/.env`
2. Reemplazar `TU_CLAVE_OUTLOOK_AQUI` con tu contraseña real:
   ```
   MAIL_PASS=tu_contraseña_de_outlook
   ```

### Caso B — CON autenticación multifactor (MFA) activa

Si al entrar a Outlook te pide un segundo factor (SMS, app de autenticación), necesitas crear una **App Password** (contraseña de aplicación):

1. Ir a: **https://mysignins.microsoft.com/security-info**  
   *(o en Outlook: Mi cuenta → Seguridad → Contraseñas de aplicación)*
2. Clic en **"Agregar método"** → Seleccionar **"Contraseña de aplicación"**
3. Dale el nombre: `Claut Intranet SMTP`
4. Copiar la contraseña que te genera (solo se muestra una vez)
5. Pegarla en `build/.env` como `MAIL_PASS`

### Caso C — SMTP AUTH desactivado por el administrador de Microsoft 365

Si el script de prueba falla con `"5.7.57 SMTP"` o `"Client not authenticated"`, significa que el administrador de Microsoft 365 desactivó el acceso SMTP básico.

Solución: Pedir al administrador de Microsoft 365 que habilite SMTP AUTH para la cuenta:
- **Microsoft 365 Admin Center** → Users → `auxsistemas@clautmetropolitano.mx` → Mail → Manage email apps → activar **"Authenticated SMTP"**

---

## ✅ Verificar la Configuración

Una vez configurada la contraseña:

```bash
cd /Users/fernandotorres/Desktop/Claut_BD
php build/setup/test_smtp.php
```

### ¿Qué hace el script?
- Lee la configuración del `.env`
- Intenta conectar al servidor SMTP
- Envía un correo de prueba a `MAIL_ADMIN`
- Informa si fue exitoso o el error exacto

---

## 🏗️ Arquitectura del Sistema de Correo

```
build/
├── services/
│   ├── EmailService.php          ← Motor central de correo
│   └── phpmailer/
│       ├── PHPMailer.php         ← PHPMailer v6 standalone
│       ├── SMTP.php              ← Protocolo SMTP
│       └── Exception.php        ← Excepciones PHPMailer
│
├── api/auth/
│   ├── forgot-password.php       ← POST: Solicitar reset de contraseña
│   ├── reset-password.php        ← GET/POST: Validar token / nueva contraseña
│   ├── verify-account.php        ← GET: Verificar cuenta / POST: Reenviar
│   └── notify-approval.php       ← POST (admin): Notificar aprobación/rechazo
│
├── pages/
│   └── sign-in.html              ← Modal forgot-password + Modal reset integrados
│
├── setup/
│   ├── migrations/
│   │   └── 20260401_email_tokens.sql  ← Tabla de tokens
│   └── test_smtp.php             ← Script de verificación
│
└── .env                          ← MAIL_HOST, MAIL_PORT, MAIL_PASS (no subir a Git)
```

---

## 📩 Correos Implementados

| Correo | Método EmailService | Cuándo se envía |
|--------|---------------------|---------|
| Recuperación de contraseña | `sendPasswordReset()` | Usuario hace clic en "¿Olvidaste?" |
| Verificación al registrarse | `sendAccountVerification()` | Auto al crear cuenta nueva |
| Cuenta aprobada | `sendAccountApproved()` | Admin aprueba en panel |
| Cuenta rechazada | `sendAccountRejected()` | Admin rechaza en panel |
| Notificación básica | `sendNotification()` | Cualquier módulo del sistema |
| Alerta admin | `sendAdminAlert()` | Eventos de seguridad |

---

## 🗄️ Migración SQL Pendiente

**Ejecutar una sola vez en producción** (Hostinger MySQL):

```bash
# Opción A — desde línea de comandos:
mysql -u u695712029_claut_fer -p u695712029_claut_intranet \
  < build/setup/migrations/20260401_email_tokens.sql

# Opción B — copiar y pegar el contenido del SQL en:
# phpMyAdmin de Hostinger → base de datos → SQL → Ejecutar
```

Lo que crea la migración:
- Tabla `email_tokens` (tokens de reset y verificación)
- Columna `email_verificado` en `usuarios_perfil`

---

## ⚠️ Troubleshooting

### Error: `"5.7.57 SMTP; Client was not authenticated"`
**Causa**: SMTP AUTH desactivado en Microsoft 365.  
**Fix**: Pedir al admin de M365 que active "Authenticated SMTP" para esta cuenta (ver Caso C arriba).

### Error: `"Authentication unsuccessful"`
**Causa**: Contraseña incorrecta, o necesitas App Password porque MFA está activado.  
**Fix**: Crear App Password (ver Caso B arriba).

### Error: `"Connection timeout"`
**Causa**: El puerto 587 puede estar bloqueado por el servidor o por el ISP local.  
**Fix**: Probar ejecutar el script desde el servidor de producción (Hostinger), no desde tu Mac local. En producción raramente hay bloqueo de puerto 587.

### Los correos llegan a SPAM
**Causa**: El dominio `clautmetropolitano.mx` puede no tener SPF/DKIM correctamente configurado para Microsoft 365.  
**Fix**: Verificar en el DNS del dominio que existan los registros TXT de SPF y DKIM de Microsoft 365. El admin de Microsoft 365 puede obtener estos registros desde el Admin Center.

---

## 📋 Checklist de Puesta en Marcha

- [ ] Obtener la contraseña de Outlook para `auxsistemas@clautmetropolitano.mx`
- [ ] Copiarla en `build/.env` como `MAIL_PASS=tu_contraseña`
- [ ] Si hay MFA → crear App Password y usar esa en lugar de la contraseña normal
- [ ] Ejecutar migración SQL (tabla `email_tokens`)
- [ ] Verificar con: `php build/setup/test_smtp.php`
- [ ] Registrar un usuario de prueba → verificar correo de bienvenida
- [ ] Usar "¿Olvidaste?" en el login → verificar correo de reset
- [ ] Abrir enlace del correo → verificar modal de nueva contraseña funciona

---

*Documento actualizado: 2026-04-01*
*Ver también: `claude.md` → sección "📧 Sistema de Correo SMTP"*
