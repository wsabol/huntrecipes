import {defineConfig} from 'vite';
import {copyFileSync} from 'node:fs';
import path from 'node:path';

const root = import.meta.dirname;

// Laravel's Vite helper reads public/build/manifest.json. Release packaging also
// keeps a copy outside the document root for its asset checks.
const MANIFEST_PATH = 'bootstrap/build-manifest.json';

function copyBuildManifest() {
    return {
        name: 'hunt-recipes-build-manifest',
        apply: 'build',
        writeBundle(options) {
            const emitted = path.resolve(options.dir, 'manifest.json');

            copyFileSync(emitted, path.resolve(root, MANIFEST_PATH));
        },
    };
}

export default defineConfig({
    base: '/build/',
    plugins: [copyBuildManifest()],
    // public/ is the Laravel docroot, not a Vite static dir — don't copy it into the bundle.
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js',
            ],
        },
    },
});
