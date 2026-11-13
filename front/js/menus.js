// ============================================================
//  menus.js — Filtres dynamiques + appel API
// ============================================================

import { getMenus } from "./api.js";
import { escapeHtml } from "./utils.js";

let tousLesMenus = [];

// Charger les menus depuis l'API
const chargerMenus = async () => {
    const result = await getMenus();

    document.getElementById("loading").classList.add("d-none");
    document.getElementById("menus-container").classList.remove("d-none");

    if (result.success) {
        tousLesMenus = result.data;
        afficherMenus(tousLesMenus);
    }
};

// Badge thème
const getBadgeClass = (theme) => {
    if (!theme) return "classique";
    const t = theme.toLowerCase();
    if (t.includes("noël") || t.includes("noel")) return "noel";
    if (t.includes("pâques") || t.includes("paques")) return "paques";
    if (t.includes("événement") || t.includes("evenement")) return "evenement";
    return "classique";
};

// Emoji par thème
const getEmoji = (theme) => {
    if (!theme) return "🍽️";
    const t = theme.toLowerCase();
    if (t.includes("noël") || t.includes("noel")) return "🍗";
    if (t.includes("pâques") || t.includes("paques")) return "🐣";
    if (t.includes("événement") || t.includes("evenement")) return "🎉";
    return "🍽️";
};

// Afficher les menus
const afficherMenus = (menus) => {
    const container = document.getElementById("menus-container");
    document.getElementById("nb-resultats").textContent = menus.length;

    if (menus.length === 0) {
        container.innerHTML = "";
        document.getElementById("no-result").classList.remove("d-none");
        return;
    }

    document.getElementById("no-result").classList.add("d-none");

    container.innerHTML = menus.map((menu) => `
        <div class="col-md-4">
            <div class="menu-card">
                <div class="menu-card-img" style="background:#FDF0E8;">${getEmoji(menu.theme)}</div>
                <div class="menu-card-body">
                    <span class="badge-theme ${getBadgeClass(menu.theme)}">${escapeHtml(menu.theme || 'Classique')}</span>
                    <h3 class="menu-card-title">${escapeHtml(menu.titre)}</h3>
                    <p class="menu-card-persons">👥 Minimum ${Number(menu.nombrePersonneMinimum)} personnes</p>
                    <p class="menu-card-desc">${escapeHtml(menu.description)}</p>
                    ${menu.quantiteRestante <= 0 ? '<p class="text-danger small mb-2">Victime de son succès : plus disponible</p>' : ''}
                    <div class="menu-card-footer">
                        <div class="menu-card-price">${Number(menu.prixParPersonne)}€ <span>/ pers.</span></div>
                        <a href="/menu" onclick="sessionStorage.setItem('menuId', ${Number(menu.id)}); route(event);" class="btn-voir">Voir le menu</a>
                    </div>
                </div>
            </div>
        </div>
    `).join("");
};

// Filtrer les menus
const filtrerMenus = () => {
    const prixMax   = parseFloat(document.getElementById("filtre-prix-max").value) || Infinity;
    const prixMin   = parseFloat(document.getElementById("filtre-prix-min").value) || 0;
    const theme     = document.getElementById("filtre-theme").value.toLowerCase();
    const regime    = document.getElementById("filtre-regime").value.toLowerCase();
    const personnes = parseInt(document.getElementById("filtre-personnes").value) || 0;

    const filtered = tousLesMenus.filter((menu) => {
        return (
            menu.prixParPersonne <= prixMax &&
            menu.prixParPersonne >= prixMin &&
            (!theme  || menu.theme?.toLowerCase().includes(theme)) &&
            (!regime || menu.regime?.toLowerCase().includes(regime)) &&
            menu.nombrePersonneMinimum >= personnes
        );
    });

    afficherMenus(filtered);
};

const resetFiltres = () => {
    document.getElementById("filtre-prix-max").value  = "";
    document.getElementById("filtre-prix-min").value  = "";
    document.getElementById("filtre-theme").value     = "";
    document.getElementById("filtre-regime").value    = "";
    document.getElementById("filtre-personnes").value = "";
    afficherMenus(tousLesMenus);
};

document.getElementById("filtre-prix-max").addEventListener("input", filtrerMenus);
document.getElementById("filtre-prix-min").addEventListener("input", filtrerMenus);
document.getElementById("filtre-theme").addEventListener("change", filtrerMenus);
document.getElementById("filtre-regime").addEventListener("change", filtrerMenus);
document.getElementById("filtre-personnes").addEventListener("input", filtrerMenus);
document.getElementById("btn-reset").addEventListener("click", resetFiltres);
document.getElementById("btn-reset-2").addEventListener("click", resetFiltres);

chargerMenus();