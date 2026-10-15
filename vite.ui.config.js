import { defineConfig } from 'vite';
import { readFileSync } from 'node:fs';

// Classic bundles preserve the synchronous globals used by existing Blade JS.
// Compile source modules in production; never ship the upstream eval dev bundles.
export default defineConfig(({ mode }) => {
    if (!['helpers', 'core'].includes(mode)) throw new Error('UI build mode must be helpers or core.');
    return {
        publicDir: false,
        plugins: mode === 'helpers' ? [{
            name: 'ui-license-notices',
            generateBundle() {
                const sources = [
                    'resources/vendor/sneat/LICENSE', 'node_modules/jquery/LICENSE.txt',
                    'node_modules/bootstrap/LICENSE', 'node_modules/@popperjs/core/LICENSE.md',
                    'node_modules/perfect-scrollbar/LICENSE', 'node_modules/datatables.net/License.txt',
                    'node_modules/datatables.net-bs5/License.txt',
                ];
                this.emitFile({ type: 'asset', fileName: 'LICENSES.txt',
                    source: sources.map(file => `${file}\n\n${readFileSync(file, 'utf8')}`).join('\n\n') });
            },
        }] : [],
        build: {
            outDir: 'public/ui',
            emptyOutDir: mode === 'helpers',
            sourcemap: false,
            lib: {
                entry: `resources/js/ui-${mode}.js`,
                name: 'KostSanUi',
                formats: ['iife'],
                fileName: () => `${mode}.js`,
            },
        },
    };
});
