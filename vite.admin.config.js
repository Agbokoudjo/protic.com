import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import inject from '@rollup/plugin-inject';
//import react from "@vitejs/plugin-react";
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    base: '/build/admin/',
    debug:true,
    optimizeDeps: {
    force: true, // Force l'optimisation au démarrage
  },
    plugins: [
        inject({
            $: 'jquery',
            jQuery: 'jquery'
        }),
        symfonyPlugin({
            refresh: true,
           stimulus: true ,
            viteDevServerHostname: "127.0.0.1"
        }),
        viteStaticCopy({
        targets: [
            {
                src: 'assets/images/*',
                dest: 'assets/images'
            }
        ]
    }),
    ],
    build: {
        outDir: 'public/build/admin',
        rollupOptions: {
            input: {
                sonata: "./assets/sonata.js",
                base_sonata_admin: "./assets/base_sonata_admin.js",
                login: "./assets/login.js",
                mobile_guard: "./assets/mobile_guard.js",
                form: 'assets/form.js'
            },
        }
    },
    server: {
        host  : "127.0.0.1",  // IPv4 forcé
        port  : 5173,
        strictPort: true,
        cors  : {
            origin: [
                "https://protic.local",
                "http://protic.local",
                "http://127.0.0.1:8081",
                "https://proticeditions.com",
                /\.ngrok-free\.app$/
            ]
        },
        hmr: {
            host     : "127.0.0.1",
            port     : 5173,
            protocol : "ws"
        }
    }
});
