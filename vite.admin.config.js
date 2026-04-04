import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import inject from '@rollup/plugin-inject';
//import react from "@vitejs/plugin-react";
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    base: '/build/admin/',
    debug:true,
    plugins: [
        inject({
            $: 'jquery',
            jQuery: 'jquery'
        }),
        symfonyPlugin({
            refresh: true,
           stimulus: true ,
            viteDevServerHostname: "0.0.0.0"
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
                form_validator: 'assets/form_validator.js',
                login : "./assets/login.js",
            },
        }
    },
    server: {
        host  : "0.0.0.0",  // IPv4 forcé
        port  : 5173,
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
            host     : "0.0.0.0",
            port     : 5173,
            protocol : "ws"
        }
    }
});