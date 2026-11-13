import Route from "./Route.js";

// Définir ici vos routes
export const allRoutes = [
    new Route("/",          "Accueil",           "/pages/home.html"  ,      "js/home.js"),
    new Route("/menus",     "Nos menus",         "/pages/menus.html",     "js/menus.js"),
    new Route("/menu",      "Détail du menu",    "/pages/menu.html",      "js/menu.js"),
    new Route("/commande",  "Commander",         "/pages/commande.html",  "js/commande.js"),
    new Route("/connexion", "Connexion",         "/pages/connexion.html", "js/connexion.js"),
    new Route("/inscription","Inscription",      "/pages/inscription.html","js/inscription.js"),
    new Route("/compte",    "Mon compte",        "/pages/compte.html",    "js/compte.js"),
    new Route("/contact",   "Contact",           "/pages/contact.html",   "js/contact.js"),
    new Route("/mentions-legales", "Mentions légales", "/pages/mentions-legales.html"),
    new Route("/cgv",       "Conditions générales de vente", "/pages/cgv.html"),
];

// Le titre s'affiche comme ceci : Route.titre - websiteName
export const websiteName = "Vite & Gourmand";