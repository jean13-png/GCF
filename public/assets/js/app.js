/* GCF · interactions légères (menus, confirmations, flash auto) */
document.addEventListener('DOMContentLoaded', function () {

    // ── Carousel (Hero) ──────────────────────────────────────────
    var carousel = document.querySelector('.hero-carousel');
    if (carousel) {
        var slides = document.querySelectorAll('.slide');
        var indicators = document.querySelectorAll('.indicator');
        var currentSlide = 0;
        var autoPlayInterval;

        function showSlide(n) {
            slides.forEach(function (s) { s.classList.remove('active'); });
            indicators.forEach(function (i) { i.classList.remove('active'); });

            slides[n].classList.add('active');
            indicators[n].classList.add('active');
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(currentSlide);
        }

        function startAutoPlay() {
            autoPlayInterval = setInterval(nextSlide, 6000);
        }

        function stopAutoPlay() {
            clearInterval(autoPlayInterval);
        }

        // Événements des boutons
        document.querySelector('.carousel-next').addEventListener('click', function () {
            stopAutoPlay();
            nextSlide();
            startAutoPlay();
        });

        document.querySelector('.carousel-prev').addEventListener('click', function () {
            stopAutoPlay();
            prevSlide();
            startAutoPlay();
        });

        // Indicateurs
        indicators.forEach(function (indicator, index) {
            indicator.addEventListener('click', function () {
                stopAutoPlay();
                currentSlide = index;
                showSlide(currentSlide);
                startAutoPlay();
            });
        });

        // Initialiser
        showSlide(currentSlide);
        startAutoPlay();

        // Arrêter l'autoplay au survol
        carousel.addEventListener('mouseenter', stopAutoPlay);
        carousel.addEventListener('mouseleave', startAutoPlay);
    }

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
