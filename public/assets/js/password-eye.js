/*
|--------------------------------------------------------------------------
| OEIL AFFICHER / MASQUER LE MOT DE PASSE
|--------------------------------------------------------------------------
| Ce script equippe automatiquement tous les champs mot de passe de la
| page d un petit bouton oeil : un clic affiche le mot de passe en clair,
| un autre clic le masque a nouveau. Aucune dependance externe.
|--------------------------------------------------------------------------
*/
(function () {
    "use strict";

    var SVG_OEIL = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
    var SVG_BARRE = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    function styliser() {
        if (document.getElementById("pwd-eye-style")) {
            return;
        }
        var s = document.createElement("style");
        s.id = "pwd-eye-style";
        s.textContent = ""
            + ".pwd-eye-wrap{position:relative;display:block;}"
            + ".pwd-eye-wrap input{padding-right:44px !important;}"
            + ".pwd-eye-btn{position:absolute;top:50%;right:12px;transform:translateY(-50%);"
            + "display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;"
            + "padding:0;border:none;background:transparent;cursor:pointer;color:#6b7280;border-radius:6px;"
            + "-webkit-tap-highlight-color:transparent;}"
            + ".pwd-eye-btn:hover{color:#1f2937;background:rgba(15,23,42,.06);}"
            + ".pwd-eye-btn:focus-visible{outline:2px solid #2563eb;outline-offset:1px;}";
        (document.head || document.documentElement).appendChild(s);
    }

    function poserOeil(inp) {
        if (!inp || inp.getAttribute("data-oeil")) {
            return;
        }
        inp.setAttribute("data-oeil", "1");

        var wrap = document.createElement("span");
        wrap.className = "pwd-eye-wrap";
        inp.parentNode.insertBefore(wrap, inp);
        wrap.appendChild(inp);

        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "pwd-eye-btn";
        btn.setAttribute("aria-label", "Afficher le mot de passe");
        btn.title = "Afficher le mot de passe";
        btn.innerHTML = '<span class="icone-oeil">' + SVG_OEIL + "</span>"
            + '<span class="icone-barre" style="display:none;">' + SVG_BARRE + "</span>";

        btn.addEventListener("click", function () {
            var visible = inp.type === "password";
            inp.type = visible ? "text" : "password";
            btn.querySelector(".icone-oeil").style.display = visible ? "none" : "";
            btn.querySelector(".icone-barre").style.display = visible ? "" : "none";
            var libelle = visible ? "Masquer le mot de passe" : "Afficher le mot de passe";
            btn.setAttribute("aria-label", libelle);
            btn.title = libelle;
            try {
                inp.focus({ preventScroll: true });
            } catch (e) {
                inp.focus();
            }
        });

        wrap.appendChild(btn);
    }

    function poserTous() {
        styliser();
        var champs = document.querySelectorAll('input[type="password"]');
        for (var i = 0; i < champs.length; i++) {
            poserOeil(champs[i]);
        }
    }

    function demarrer() {
        poserTous();
        if (window.MutationObserver && document.body) {
            new MutationObserver(function () {
                poserTous();
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", demarrer);
    } else {
        demarrer();
    }

    window.poserYeuxMotsDePasse = poserTous;
})();
