// ============================================================
//  Vite & Gourmand — utils.js
//  Fonctions partagées par toutes les pages
// ============================================================

// Échappe le HTML d'une donnée venant de l'API avant de l'insérer avec innerHTML.
// Empêche une faille XSS : un avis contenant <script> ou <img onerror=...>
// sera affiché comme du texte au lieu d'être exécuté par le navigateur.
export const escapeHtml = (valeur) =>
    String(valeur ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

// Navigation interne sans recharger la page (utilise le router)
export const naviguer = (url) => {
    window.history.pushState({}, "", url);
    window.route({ preventDefault: () => {}, target: { href: url } });
};

// Lit le contenu (payload) du token JWT stocké : rôles, email, expiration.
// Sert uniquement à adapter l'affichage : la vraie vérification des droits est faite par l'API.
export const lireToken = () => {
    const token = localStorage.getItem("token");
    if (!token) return null;
    try {
        const payload = JSON.parse(atob(token.split(".")[1].replace(/-/g, "+").replace(/_/g, "/")));
        // Token expiré : on le supprime
        if (payload.exp && payload.exp * 1000 < Date.now()) {
            localStorage.removeItem("token");
            return null;
        }
        return payload;
    } catch {
        localStorage.removeItem("token");
        return null;
    }
};

export const aLeRole = (role) => lireToken()?.roles?.includes(role) ?? false;

// Libellés lisibles des statuts de commande
export const STATUTS_COMMANDE = {
    en_attente:          "En attente",
    accepte:             "Acceptée",
    en_preparation:      "En préparation",
    en_cours_livraison:  "En cours de livraison",
    livre:               "Livrée",
    en_attente_materiel: "En attente du retour de matériel",
    termine:             "Terminée",
    annule:              "Annulée",
};

export const formatPrix = (n) => Number(n).toFixed(2).replace(".", ",") + " €";
