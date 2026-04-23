const esbuild = require('esbuild');

esbuild.build({
  entryPoints: ['js/index.js'],
  bundle: true,
  minify: true,
  sourcemap: false,
  format: 'esm',
  outfile: 'dist/claut-core.min.js',
}).catch(() => process.exit(1));
