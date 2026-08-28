import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import os from 'os';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function getLocalIP() {
    const interfaces = os.networkInterfaces();
    for (const name of Object.keys(interfaces)) {
        for (const interfaceInfo of interfaces[name]) {
            if (interfaceInfo.family === 'IPv4' && !interfaceInfo.internal) {
                return interfaceInfo.address;
            }
        }
    }
    return 'localhost';
}

const sslKeyPath = path.resolve(__dirname, 'vite.key');
const sslCertPath = path.resolve(__dirname, 'vite.crt');

let httpsConfig = false;
if (fs.existsSync(sslKeyPath) && fs.existsSync(sslCertPath)) {
    httpsConfig = {
        key: fs.readFileSync(sslKeyPath),
        cert: fs.readFileSync(sslCertPath),
    };
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    const appUrl = env.APP_URL || 'http://localhost';
    const isHttps = appUrl.startsWith('https://');

    // Penentuan host HMR (Hot Module Replacement):
    // 1. Menggunakan VITE_DEV_HOST dari .env jika diisi (misal untuk HP / IP tertentu)
    // 2. Menggunakan hostname dari APP_URL (misal dev-portal.lokal atau portal-sma.test)
    // 3. Fallback ke IP lokal laptop
    let hmrHost = env.VITE_DEV_HOST;
    if (!hmrHost) {
        try {
            hmrHost = new URL(appUrl).hostname;
        } catch (e) {
            hmrHost = getLocalIP();
        }
    }

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
            // Mengizinkan koneksi dari luar (HP/LAN)
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true,
            https: isHttps ? httpsConfig : false,
            hmr: {
                host: hmrHost,
                protocol: isHttps ? 'wss' : 'ws',
            },
        },
        build: {
            chunkSizeWarningLimit: 1500,
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (id.includes('node_modules')) {
                            if (id.includes('primevue') || id.includes('@primevue') || id.includes('@primeuix') || id.includes('primeicons')) {
                                return 'vendor-primevue';
                            }
                            if (id.includes('vue') || id.includes('@inertiajs') || id.includes('pinia')) {
                                return 'vendor-vue';
                            }
                            return 'vendor';
                        }
                    },
                },
            },
        },
    };
});