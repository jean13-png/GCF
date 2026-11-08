/* GCF · interactions légères (menus, animations d'accueil, confirmations, flash) */
document.addEventListener('DOMContentLoaded', function () {
    var mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Sidebar en tiroir sur téléphone (espace connecté) ──────────
    var burger = document.querySelector('.contenu-app .burger, .topbar .burger');
    var sidebar = document.querySelector('.sidebar');
    var voile = document.querySelector('.voile:not(#voilePublic)');
    if (burger && sidebar) {
        burger.addEventListener('click', function () {
            sidebar.classList.toggle('ouverte');
            if (voile) { voile.classList.toggle('visible', sidebar.classList.contains('ouverte')); }
        });
        if (voile) {
            voile.addEventListener('click', function () {
                sidebar.classList.remove('ouverte');
                voile.classList.remove('visible');
            });
        }
    }

    // ── Menu du site public sur téléphone ──────────────────────────
    var burgerPublic = document.querySelector('.navbar-publique .burger');
    var menuPublic = document.querySelector('.menu-public');
    var voilePublic = document.getElementById('voilePublic');

    function fermerMenuPublic() {
        if (!menuPublic) { return; }
        menuPublic.classList.remove('ouverte');
        if (burgerPublic) {
            burgerPublic.classList.remove('ouvert');
            burgerPublic.setAttribute('aria-expanded', 'false');
            var icone = burgerPublic.querySelector('i');
            if (icone) { icone.className = 'fas fa-bars'; }
        }
        if (voilePublic) { voilePublic.classList.remove('visible'); }
    }

    if (burgerPublic && menuPublic) {
        burgerPublic.addEventListener('click', function () {
            var ouvert = menuPublic.classList.toggle('ouverte');
            burgerPublic.classList.toggle('ouvert', ouvert);
            burgerPublic.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            var icone = burgerPublic.querySelector('i');
            if (icone) { icone.className = ouvert ? 'fas fa-xmark' : 'fas fa-bars'; }
            if (voilePublic) { voilePublic.classList.toggle('visible', ouvert); }
        });
        document.querySelectorAll('.menu-public a').forEach(function (a) {
            a.addEventListener('click', fermerMenuPublic);
        });
        if (voilePublic) { voilePublic.addEventListener('click', fermerMenuPublic); }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { fermerMenuPublic(); }
        });
    }

    // ── Apparition douce des blocs au défilement (page d'accueil) ──
    var reveals = document.querySelectorAll('.reveal');
    if (mouvementReduit || !('IntersectionObserver' in window)) {
        reveals.forEach(function (el) { el.classList.add('visible'); });
    } else {
        var observateur = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (entree) {
                if (entree.isIntersecting) {
                    entree.target.classList.add('visible');
                    observateur.unobserve(entree.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(function (el) { observateur.observe(el); });
    }

    // ── Compteurs animés des chiffres clés ─────────────────────────
    var compteurs = document.querySelectorAll('[data-compteur]');
    if (compteurs.length) {
        var animer = function (el) {
            var cible = parseInt(el.getAttribute('data-compteur'), 10) || 0;
            if (mouvementReduit || cible <= 0) { el.textContent = String(cible); return; }
            var duree = 900;
            var debut = null;
            var pas = function (t) {
                if (debut === null) { debut = t; }
                var progression = Math.min((t - debut) / duree, 1);
                var eased = 1 - Math.pow(1 - progression, 3);
                el.textContent = String(Math.round(cible * eased));
                if (progression < 1) { window.requestAnimationFrame(pas); }
            };
            window.requestAnimationFrame(pas);
        };
        if (!('IntersectionObserver' in window)) {
            compteurs.forEach(animer);
        } else {
            var observateurCompteurs = new IntersectionObserver(function (entrees) {
                entrees.forEach(function (entree) {
                    if (entree.isIntersecting) {
                        animer(entree.target);
                        observateurCompteurs.unobserve(entree.target);
                    }
                });
            }, { threshold: 0.4 });
            compteurs.forEach(function (el) { observateurCompteurs.observe(el); });
        }
    }

    // ── Confirmation avant les actions sensibles ───────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ── Les messages flash s'effacent tout seuls après 7 secondes ──
    setTimeout(function () {
        document.querySelectorAll('.flash').forEach(function (el) {
            el.style.transition = 'opacity .6s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 700);
        });
    }, 7000);
});
