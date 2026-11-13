import Route from "./Route.js";
import { allRoutes, websiteName } from "./allRoutes.js";
import { lireToken } from "../js/utils.js";
import { getHoraires } from "../js/api.js";

// Création d'une route pour la page 404 (page introuvable)
const route404 = new Route("404", "Page introuvable", "/pages/404.html");

// Met à jour la navbar selon l'état de connexion (Connexion ↔ Mon compte / Déconnexion)
const majNavbar = () => {
    const zone = document.getElementById("nav-auth");
    if (!zone) return;
    if (lireToken()) {
        zone.innerHTML = `
            <a class="nav-link d-inline-block me-2" href="/compte" data-link onclick="route(event)">Mon compte</a>
            <button type="button" class="btn-connexion" id="btn-logout">Déconnexion</button>`;
        document.getElementById("btn-logout").addEventListener("click", () => {
            localStorage.removeItem("token");
            window.history.pushState({}, "", "/");
            loadContentPage();
        });
    } else {
        zone.innerHTML = `<a class="btn-connexion" href="/connexion" data-link onclick="route(event)">Connexion</a>`;
    }
};

// Horaires du footer chargés depuis l'API (modifiables par les employés)
const chargerHoraires = async () => {
    const zone = document.getElementById("footer-horaires");
    if (!zone) return;
    const result = await getHoraires();
    if (!result.success || !result.data.length) return;
    zone.textContent = "";
    result.data.forEach((h) => {
        const ligne = document.createElement("div");
        ligne.textContent = h.heureOuverture === "Fermé"
            ? `${h.jour} : fermé`
            : `${h.jour} : ${h.heureOuverture} – ${h.heureFermeture}`;
        zone.appendChild(ligne);
    });
};

// Fonction pour récupérer la route correspondant à une URL donnée
const getRouteByUrl = (url) => {
    let currentRoute = null;
    allRoutes.forEach((element) => {
        if (element.url === url) {
            currentRoute = element;
        }
    });
    return currentRoute !== null ? currentRoute : route404;
};

// Fonction pour charger le contenu de la page
const loadContentPage = async () => {
    const path = window.location.pathname;
    const actualRoute = getRouteByUrl(path);

    // Récupération du contenu HTML de la route
    const html = await fetch(actualRoute.pathHtml).then((data) => data.text());
    document.getElementById("main-page").innerHTML = html;

    // Suppression de l'ancien script de page s'il existe
    const oldScript = document.getElementById("page-script");
    if (oldScript) oldScript.remove();

    // Chargement du script JS associé à la route
    if (actualRoute.pathJS !== "") {
        const scriptTag = document.createElement("script");
        scriptTag.setAttribute("type", "module");
        // Le paramètre ?v= force le navigateur à ré-exécuter le module à chaque visite
        // (sinon un module ES déjà chargé n'est exécuté qu'une seule fois)
        scriptTag.setAttribute("src", "/" + actualRoute.pathJS.replace(/^\//, "") + "?v=" + Date.now());
        scriptTag.setAttribute("id", "page-script");
        document.body.appendChild(scriptTag);
    }

    majNavbar();
    window.scrollTo(0, 0);

    // Mise à jour du titre de la page
    document.title = actualRoute.title + " - " + websiteName;

    // Mise à jour des liens actifs dans la navbar
    document.querySelectorAll("[data-link]").forEach((link) => {
        link.classList.toggle(
            "active",
            link.getAttribute("href") === path
        );
    });
};

// Gestion des clics sur les liens de navigation
const routeEvent = (event) => {
    event = event || window.event;
    event.preventDefault();
    // closest("a") : le clic peut avoir lieu sur un élément à l'intérieur du lien
    const lien = event.target.closest ? (event.target.closest("a") || event.target) : event.target;
    window.history.pushState({}, "", lien.href);
    loadContentPage();
};

// Retour en arrière dans l'historique du navigateur
window.onpopstate = loadContentPage;

// Exposition globale de la fonction de routage
window.route = routeEvent;

// Chargement initial de la page
loadContentPage();
chargerHoraires();