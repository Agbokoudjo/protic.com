import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import react from "@vitejs/plugin-react";
export default defineConfig({
    base: '/build/app/',
    plugins: [
         react(),
        symfonyPlugin({
            refresh: false,
             stimulus: true ,
            viteDevServerHostname: "localhost"
        }),
    ],
    build: {
        outDir: 'public/build/app',
        rollupOptions: {
            input: {
                app: "./assets/app.js",
                home: "./assets/home.js",
                contact: "./assets/contact.js",
                form_validator: 'assets/form_validator.js',
                catalogue: "./assets/catalogue.js",
                about : "./assets/about.js"
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