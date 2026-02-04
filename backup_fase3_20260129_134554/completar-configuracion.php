<?php
/**
 * Script para completar la configuración de archivos faltantes
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><title>🔧 Completar Configuración - Clúster</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h1 { color: #C7252B; border-bottom: 3px solid #C7252B; padding-bottom: 10px; }
.status { padding: 10px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.btn { padding: 8px 16px; background: #C7252B; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn:hover { background: #8B1538; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Completar Configuración de Archivos</h1>";

// Crear dashboard.html si no existe
if (!file_exists('dashboard.html')) {
    echo "<div class='status warning'>⚠️ dashboard.html no encontrado, creándolo...</div>";
    
    $dashboardContent = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="apple-touch-icon" sizes="76x76" href="./assets/img/apple-icon.png" />
  <link rel="icon" type="image/png" href="./assets/img/favicon.png" />
  <title>Clúster Intranet - Dashboard</title>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link href="./assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="./assets/css/nucleo-svg.css" rel="stylesheet" />
  <link href="./assets/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
</head>
<body class="m-0 font-sans antialiased font-normal bg-white text-start text-base leading-default text-slate-500">
  <!-- Navigation -->
  <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>
  
  <!-- Sidenav -->
  <aside class="fixed inset-y-0 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-soft-xl dark:bg-gray-950 xl:ml-6 dark:border-gray-800 z-990 xl:translate-x-0 rounded-2xl xl:left-0 xl:w-64" id="sidenav-main">
    <div class="h-19">
      <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden" aria-hidden="true" id="iconSidenav"></i>
      <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap dark:text-white text-slate-700" href="dashboard.html">
        <img src="./assets/img/apple-icon.png" class="inline h-full max-w-full transition-all duration-200 ease-nav-brand max-h-8" alt="main_logo" />
        <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand">Clúster Intranet</span>
      </a>
    </div>

    <hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

    <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
      <ul class="flex flex-col pl-0 mb-0">
        <li class="mt-0.5 w-full">
          <a class="py-2.7 dark:text-white dark:opacity-80 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg bg-blue-500/13 px-4 font-semibold text-slate-700 transition-colors" href="dashboard.html">
            <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
              <i class="relative top-0 text-sm leading-normal text-blue-500 ni ni-tv-2"></i>
            </div>
            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Dashboard</span>
          </a>
        </li>

        <li class="mt-0.5 w-full">
          <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-60" href="profile.html">
            <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
              <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-single-02"></i>
            </div>
            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Perfil</span>
          </a>
        </li>

        <li class="mt-0.5 w-full">
          <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-60" href="boletines.html">
            <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
              <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-collection"></i>
            </div>
            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Boletines</span>
          </a>
        </li>

        <li class="mt-0.5 w-full">
          <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-60" href="eventos.html">
            <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
              <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-calendar-grid-58"></i>
            </div>
            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Eventos</span>
          </a>
        </li>

        <li class="mt-0.5 w-full">
          <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-60" href="comites.html">
            <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
              <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-bullet-list-67"></i>
            </div>
            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Comités</span>
          </a>
        </li>

        <li class="w-full mt-4">
          <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase dark:text-white opacity-60">Páginas</h6>
        </li>

        <li class="mt-0.5 w-full">
          <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-60" href="pages/sign-in.html">
            <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
              <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-single-copy-04"></i>
            </div>
            <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Iniciar Sesión</span>
          </a>
        </li>
      </ul>
    </div>
  </aside>

  <!-- Main content -->
  <main class="ease-soft-in-out xl:ml-68.5 relative h-full max-h-screen rounded-xl transition-all duration-200">
    <!-- Navbar -->
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all shadow-none duration-250 ease-soft-in rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="true">
      <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
        <nav>
          <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
            <li class="text-sm leading-normal">
              <a class="opacity-50 text-slate-700" href="javascript:;">Páginas</a>
            </li>
            <li class="text-sm pl-2 capitalize leading-normal text-slate-700 before:float-left before:pr-2 before:text-gray-600 before:content-['/']" aria-current="page">Dashboard</li>
          </ol>
          <h6 class="mb-0 font-bold capitalize">Dashboard</h6>
        </nav>

        <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
          <ul class="flex flex-row justify-end pl-0 mb-0 list-none md-max:w-full">
            <li class="flex items-center">
              <a href="javascript:;" class="block p-0 text-sm transition-all ease-nav-brand text-slate-500">
                <i class="fa fa-user sm:mr-1"></i>
                <span class="hidden sm:inline">Bienvenido</span>
              </a>
            </li>
            <li class="flex items-center pl-4 xl:hidden">
              <a href="javascript:;" class="block p-0 text-sm transition-all ease-nav-brand text-slate-500" sidenav-trigger>
                <div class="w-4.5 overflow-hidden">
                  <i class="ease-soft mb-0.75 relative block h-0.5 rounded-sm bg-slate-500 transition-all"></i>
                  <i class="ease-soft mb-0.75 relative block h-0.5 rounded-sm bg-slate-500 transition-all"></i>
                  <i class="ease-soft relative block h-0.5 rounded-sm bg-slate-500 transition-all"></i>
                </div>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Cards -->
    <div class="w-full px-6 py-6 mx-auto">
      <!-- Welcome Card -->
      <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 mb-6">
          <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 border-transparent border-solid shadow-soft-xl rounded-2xl bg-clip-border">
            <div class="p-6 pb-0 mb-0 bg-white rounded-t-2xl">
              <h6>Bienvenido a Clúster Intranet</h6>
              <p class="leading-normal text-sm">Panel principal de la intranet corporativa</p>
            </div>
            <div class="flex-auto px-0 pt-0 pb-2">
              <div class="p-6">
                <div class="text-center">
                  <h2 class="text-3xl font-bold text-slate-700 mb-4">¡Hola! 👋</h2>
                  <p class="text-lg text-slate-600 mb-6">Bienvenido al sistema de intranet de Clúster</p>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-xl">
                      <i class="ni ni-collection text-3xl mb-3"></i>
                      <h4 class="font-semibold mb-2">Boletines</h4>
                      <p class="text-sm opacity-90">Consulta los boletines más recientes</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white p-6 rounded-xl">
                      <i class="ni ni-calendar-grid-58 text-3xl mb-3"></i>
                      <h4 class="font-semibold mb-2">Eventos</h4>
                      <p class="text-sm opacity-90">Mantente al día con los eventos</p>
                    </div>
                    <div class="bg-gradient-to-r from-red-500 to-pink-600 text-white p-6 rounded-xl">
                      <i class="ni ni-bullet-list-67 text-3xl mb-3"></i>
                      <h4 class="font-semibold mb-2">Comités</h4>
                      <p class="text-sm opacity-90">Información de comités activos</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="./assets/js/plugins/perfect-scrollbar.min.js" async></script>
  <script src="./assets/js/argon-dashboard-tailwind.js?v=1.0.1" async></script>
</body>
</html>';

    if (file_put_contents('dashboard.html', $dashboardContent)) {
        echo "<div class='status success'>✅ dashboard.html creado exitosamente</div>";
    } else {
        echo "<div class='status error'>❌ Error creando dashboard.html</div>";
    }
} else {
    echo "<div class='status success'>✅ dashboard.html ya existe</div>";
}

// Verificar que el index.html no interfiera
if (file_exists('index.html')) {
    echo "<div class='status warning'>⚠️ index.html encontrado - podría interferir con la redirección</div>";
    
    // Renombrarlo para evitar conflictos
    if (rename('index.html', 'index-old.html')) {
        echo "<div class='status success'>✅ index.html renombrado a index-old.html para evitar conflictos</div>";
    }
}

echo "<h2>🧪 Verificación Final</h2>";
echo "<div class='status info'>";
echo "<h4>Estado de archivos importantes:</h4>";
echo "<ul>";
echo "<li><strong>index.php en raíz:</strong> " . (file_exists('../index.php') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "<li><strong>.htaccess en raíz:</strong> " . (file_exists('../.htaccess') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "<li><strong>dashboard.html:</strong> " . (file_exists('dashboard.html') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "<li><strong>pages/sign-in.html:</strong> " . (file_exists('pages/sign-in.html') ? '✅ Existe' : '❌ No existe') . "</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 Prueba la Configuración</h2>";
echo "<div class='status success'>";
echo "<p>Ahora tu dominio debería estar configurado correctamente:</p>";
echo "<ol>";
echo "<li><strong>Dominio principal:</strong> <a href='https://intranet.clústermetropolitano.mx' target='_blank'>https://intranet.clústermetropolitano.mx</a> → Debe mostrar login</li>";
echo "<li><strong>Después del login:</strong> Redirige automáticamente al dashboard</li>";
echo "<li><strong>Dashboard protegido:</strong> <a href='https://intranet.clústermetropolitano.mx/build/dashboard.html' target='_blank'>Dashboard</a> → Requiere login</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='https://intranet.clústermetropolitano.mx' target='_blank' class='btn'>🌐 Probar Dominio</a>";
echo "<a href='https://intranet.clústermetropolitano.mx/build/pages/sign-in.html' target='_blank' class='btn'>🔐 Login Directo</a>";
echo "</div>";

echo "</div></body></html>";
?>