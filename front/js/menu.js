// ============================================================
//  menu.js — Détail d'un menu depuis l'API
// ============================================================

import { getMenu, isConnected } from "./api.js";
import { escapeHtml } from "./utils.js";

// Récupération de l'id du menu depuis le sessionStorage
const menuId = sessionStorage.getItem("menuId");

if (!menuId) {
    window.history.pushState({}, "", "/menus");
    window.route({ preventDefault: () => {}, target: { href: "/menus" } });
}

// Charger le menu
const chargerMenu = async () => {
    const result = await getMenu(menuId);

    if (!result.success) {
        window.history.pushState({}, "", "/menus");
        window.route({ preventDefault: () => {}, target: { href: "/menus" } });
        return;
    }

    const menu = result.data;

    // ── Breadcrumb ──
    document.getElementById("breadcrumb-menu").textContent = menu.titre;

    // ── Badge thème ──
    const badge = document.getElementById("menu-badge");
    badge.textContent = menu.theme || "Classique";
    badge.className   = `badge-theme ${getBadgeClass(menu.theme)}`;

    // ── Infos principales ──
    document.getElementById("menu-titre").textContent       = menu.titre;
    document.getElementById("menu-description").textContent = menu.description;
    document.getElementById("menu-prix").textContent        = menu.prixParPersonne + "€ / pers.";
    document.getElementById("menu-personnes").textContent   = menu.nombrePersonneMinimum + " personnes";
    document.getElementById("menu-regime").textContent      = "🍖 " + (menu.regime || "Classique");
    document.getElementById("menu-stock").textContent       = "✅ " + menu.quantiteRestante + " commandes disponibles";

    // ── Prix récap ──
    document.getElementById("recap-prix-pers").textContent   = menu.prixParPersonne + "€";
    document.getElementById("recap-prix-min").textContent    = menu.nombrePersonneMinimum + " pers.";
    document.getElementById("recap-prix-seuil").textContent  = "dès " + (menu.nombrePersonneMinimum + 5) + " personnes";
    document.getElementById("recap-prix-total").textContent  = (menu.prixParPersonne * menu.nombrePersonneMinimum) + "€";

    // ── Plats ──
    const platsContainer = document.getElementById("plats-container");
    if (menu.plats && menu.plats.length > 0) {
        platsContainer.innerHTML = menu.plats.map((plat) => `
            <div class="col-md-4">
                <div class="plat-card">
                    <div class="plat-type">${escapeHtml(plat.typePlat)}</div>
                    <div class="plat-name">${escapeHtml(plat.titre)}</div>
                    <div class="allergenes mt-2">
                        ${plat.allergenes.length
                            ? plat.allergenes.map(a => `<span class="allergen-tag">${escapeHtml(a)}</span>`).join("")
                            : '<span class="text-muted small">Aucun allergène majeur</span>'}
                    </div>
                </div>
            </div>
        `).join("");
    } else {
        platsContainer.innerHTML = `<p class="text-muted">Composition du menu à venir.</p>`;
    }

    // ── Bouton commander ──
    const btnCommander = document.getElementById("btn-commander");
    btnCommander.addEventListener("click", (e) => {
        e.preventDefault();
        if (!isConnected()) {
            // Rediriger vers connexion si non connecté
            window.history.pushState({}, "", "/connexion");
            window.route({ preventDefault: () => {}, target: { href: "/connexion" } });
        } else {
            sessionStorage.setItem("menuId", menu.id);
            window.history.pushState({}, "", "/commande");
            window.route({ preventDefault: () => {}, target: { href: "/commande" } });
        }
    });
};

// Badge class selon thème
const getBadgeClass = (theme) => {
    if (!theme) return "classique";
    const t = theme.toLowerCase();
    if (t.includes("noël") || t.includes("noel")) return "noel";
    if (t.includes("pâques") || t.includes("paques")) return "paques";
    if (t.includes("événement") || t.includes("evenement")) return "evenement";
    return "classique";
};

chargerMenu();