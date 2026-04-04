import './styles/about.css'
import {  mountFaq } from "./react/controllers/FaqComponent";

document.addEventListener('DOMContentLoaded',()=>{
    mountFaq();
});

document.addEventListener("turbo:load", ()=>{
    mountFaq();
});