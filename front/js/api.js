// ============================================================
//  Vite & Gourmand — api.js
//  Centralise tous les appels à l'API back-end
// ============================================================

import { API_URL } from "./config.js";

// ── Récupérer le token depuis le localStorage ──
const getToken = () => localStorage.getItem("token");

// ── Headers de base ──
const getHeaders = (withAuth = false) => {
    const headers = { "Content-Type": "application/json" };
    if (withAuth && getToken()) {
        headers["Authorization"] = `Bearer ${getToken()}`;
    }
    return headers;
};

// ── Appel générique : gère le JSON, les erreurs réseau et le token expiré ──
const appel = async (chemin, { method = "GET", body = null, auth = false } = {}) => {
    try {
        const response = await fetch(`${API_URL}${chemin}`, {
            method,
            headers: getHeaders(auth),
            body: body ? JSON.stringify(body) : null,
        });

        // Token expiré ou compte désactivé : on déconnecte
        if (response.status === 401 && auth) {
            localStorage.removeItem("token");
        }

        const data = await response.json().catch(() => ({}));
        if (data.success === undefined) data.success = response.ok;
        if (!response.ok && !data.message) data.message = "Une erreur est survenue.";
        return data;
    } catch {
        return { success: false, message: "Impossible de joindre le serveur. Réessayez plus tard." };
    }
};

// ── Vérifier si l'utilisateur est connecté ──
export const isConnected = () => !!getToken();

// ── Déconnexion ──
export const logout = () => {
    localStorage.removeItem("token");
    window.history.pushState({}, "", "/");
    window.route({ preventDefault: () => {}, target: { href: "/" } });
};

// ============================================================
//  AUTH
// ============================================================

export const login = async (email, password) => {
    const data = await appel("/login", { method: "POST", body: { username: email, password } });
    if (data.token) {
        localStorage.setItem("token", data.token);
    }
    return data;
};

export const inscription = (formData) => appel("/inscription", { method: "POST", body: formData });
export const getProfil = () => appel("/compte/profil", { auth: true });
export const updateProfil = (formData) => appel("/compte/profil", { method: "PUT", body: formData, auth: true });

// ============================================================
//  MENUS / HORAIRES / AVIS / CONTACT (publics)
// ============================================================

export const getMenus = (filtres = {}) => appel(`/menus?${new URLSearchParams(filtres)}`);
export const getMenu = (id) => appel(`/menus/${encodeURIComponent(id)}`);
export const getHoraires = () => appel("/horaires");
export const getAvis = () => appel("/avis");
export const sendContact = (formData) => appel("/contact", { method: "POST", body: formData });

// ============================================================
//  ESPACE CLIENT
// ============================================================

export const createCommande = (formData) => appel("/commandes", { method: "POST", body: formData, auth: true });
export const getCommandes = () => appel("/commandes", { auth: true });
export const annulerCommande = (id) => appel(`/commandes/${id}/annuler`, { method: "PUT", auth: true });
export const createAvis = (formData) => appel("/avis", { method: "POST", body: formData, auth: true });

// ============================================================
//  ESPACE EMPLOYÉ
// ============================================================

export const getCommandesEmploye = (filtres = {}) =>
    appel(`/employe/commandes?${new URLSearchParams(filtres)}`, { auth: true });
export const updateStatutCommande = (id, body) =>
    appel(`/employe/commandes/${id}/statut`, { method: "PUT", body, auth: true });
export const getAvisEnAttente = () => appel("/employe/avis", { auth: true });
export const modererAvis = (id, statut) =>
    appel(`/employe/avis/${id}`, { method: "PUT", body: { statut }, auth: true });
export const updateMenu = (id, body) => appel(`/employe/menus/${id}`, { method: "PUT", body, auth: true });
export const deleteMenu = (id) => appel(`/employe/menus/${id}`, { method: "DELETE", auth: true });
export const updateHoraire = (id, body) => appel(`/employe/horaires/${id}`, { method: "PUT", body, auth: true });

// ============================================================
//  ESPACE ADMINISTRATEUR
// ============================================================

export const getEmployes = () => appel("/admin/employes", { auth: true });
export const createEmploye = (body) => appel("/admin/employes", { method: "POST", body, auth: true });
export const setEmployeActif = (id, actif) =>
    appel(`/admin/employes/${id}/${actif ? "activer" : "desactiver"}`, { method: "PUT", auth: true });
export const getStatsCommandes = () => appel("/admin/stats/commandes", { auth: true });
export const getChiffreAffaires = (dateDebut, dateFin) =>
    appel(`/admin/stats/chiffre-affaires?${new URLSearchParams({ dateDebut, dateFin })}`, { auth: true });
