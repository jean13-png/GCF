/* GCF · interactions légères (menus, confirmations, flash auto) */
document.addEventListener('DOMContentLoaded', function () {

    // Sidebar en tiroir sur téléphone (espace connecté)
    var burger = document.querySelector('.contenu-app .burger, .topbar .burger');
    var sidebar = document.querySelector('.sidebar');
    var voile = document.querySelector('.voile');
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

    // Menu du site public sur téléphone
    var burgerPublic = document.querySelector('.navbar-publique .burger');
    var menuPublic = document.querySelector('.menu-public');
    if (burgerPublic && menuPublic) {
        burgerPublic.addEventListener('click', function () {
            menuPublic.classList.toggle('ouverte');
        });
        document.querySelectorAll('.menu-public a').forEach(function (a) {
            a.addEventListener('click', function () { menuPublic.classList.remove('ouverte'); });
        });
    }

    // Confirmation avant les actions sensibles
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Les messages flash s'effacent tout seuls après 7 secondes
    setTimeout(function () {
        document.querySelectorAll('.flash').forEach(function (el) {
            el.style.transition = 'opacity .6s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 700);
        });
    }, 7000);
});
