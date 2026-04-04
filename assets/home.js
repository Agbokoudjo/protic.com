import './styles/home.css';
import './styles/book.css'
import { initLazyMounts } from "./react/lazy-mount.jsx";

document.addEventListener('DOMContentLoaded',()=>{
    counterAnimanated();
    initLazyMounts();
});

document.addEventListener("turbo:load", ()=>{
    counterAnimanated();
    initLazyMounts();
});

/**
 * Compteur animé pour les stats
 */
function counterAnimanated() {
    const counters = document.querySelectorAll('.protic-stat__num');
    const run = el => {
        const target = +el.dataset.target, step = Math.ceil(target / 60);
        let cur = 0;
        const t = setInterval(() => {
            cur = Math.min(cur + step, target);
            el.textContent = cur;
            if (cur >= target) clearInterval(t);
        }, 28);
    };
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.5 });
    counters.forEach(c => io.observe(c));
}