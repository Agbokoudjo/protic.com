import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import react from "@vitejs/plugin-react";
//proticeditions.com/vite.app.config.js
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    base: '/build/app/',
    optimizeDeps: {
    force: true, // Force l'optimisation au démarrage
  },
    plugins: [
         react(),
        symfonyPlugin({
            refresh: false,
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
        outDir: 'public/build/app',
        rollupOptions: {
            input: {
                app: "./assets/app.js",
                home: "./assets/home.js",
                contact: "./assets/contact.js",
                form: 'assets/form.js',
                catalogue: "./assets/catalogue.js",
                about: "./assets/about.js",
                session_conflit: "./assets/session_conflit.js",
                book_show: "./assets/book_show.js"
            },
        }
    },
    server: {
        host  : "127.0.0.1",  // IPv4 forcé
        port  : 5174,
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
            port     : 5174,
            protocol : "ws"
        }
    }
});
