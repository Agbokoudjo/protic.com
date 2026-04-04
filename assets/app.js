import { registerReactControllerComponents } from "vite-plugin-symfony/stimulus/helpers/react"
import { startStimulusApp } from "vite-plugin-symfony/stimulus/helpers"
import '@vitejs/plugin-react/preamble';
import './styles/app.css';
import 'bootstrap/dist/js/bootstrap.min.js'
import { disableUserInteractions } from './utils';
import jQuery from 'jquery';
window.jQuery = jQuery;
window.$ = jQuery;

const app = startStimulusApp();
registerReactControllerComponents(import.meta.glob('./react/controllers/**/*.js(x)\?',{ eager: true })); 
import.meta.glob('./images/**/*', { eager: true });

window.addEventListener('DOMContentLoaded', () => {
    disableUserInteractions("iws-config");
})
