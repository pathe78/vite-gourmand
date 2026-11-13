// ============================================================
//  commande.js — Commande avec appel API
// ============================================================

import { createCommande, isConnected, getProfil, getMenu } from "./api.js";

// Rediriger si non connecté
if (!isConnected()) {
    alert("Vous devez être connecté pour commander.");
    window.history.pushState({}, "", "/connexion");
    window.route({ preventDefault: () => {}, target: { href: "/connexion" } });
}

const menuId = sessionStorage.getItem("menuId");
let prixParPersonne = 0;
let minPersonnes    = 1;
let nbPersonnes     = 1;

// ── Charger le profil utilisateur ──
const chargerProfil = async () => {
    const result = await getProfil();
    if (result.success) {
        const user = result.data;
        document.getElementById("cmd-prenom").value    = user.prenom;
        document.getElementById("cmd-nom").value       = user.nom;
        document.getElementById("cmd-email").value     = user.email;
        document.getElementById("cmd-telephone").value = user.telephone;
    }
};

// ── Charger le menu sélectionné ──
const chargerMenu = async () => {
    if (!menuId) return;

    const result = await getMenu(menuId);
    if (result.success) {
        const menu = result.data;
        prixParPersonne = menu.prixParPersonne;
        minPersonnes    = menu.nombrePersonneMinimum;
        nbPersonnes     = minPersonnes;

        document.getElementById("menu-selected-name").textContent = menu.titre;
        document.getElementById("menu-selected-meta").textContent =
            `${menu.theme} · ${menu.regime} · ${menu.prixParPersonne}€/pers. · min. ${menu.nombrePersonneMinimum} personnes`;
        document.getElementById("qty-note").textContent = `minimum ${menu.nombrePersonneMinimum} personnes`;

        updateRecap();
    }
};

// ── Mise à jour du récapitulatif ──
const updateRecap = () => {
    const sousTotal  = nbPersonnes * prixParPersonne;
    const reduction  = nbPersonnes >= minPersonnes + 5;
    const montantRed = reduction ? sousTotal * 0.10 : 0;
    // Livraison : gratuite à Bordeaux, 5 € ailleurs (même règle que l'API)
    const adresse    = document.getElementById("cmd-adresse").value.toLowerCase();
    const livraison  = adresse && !adresse.includes("bordeaux") ? 5 : 0;
    const total      = sousTotal - montantRed + livraison;

    document.getElementById("recap-personnes").textContent  = nbPersonnes;
    document.getElementById("recap-sous-total").textContent = sousTotal + "€";
    document.getElementById("recap-total").textContent      = total.toFixed(2) + "€";
    document.getElementById("qty-value").textContent        = nbPersonnes;
    document.getElementById("recap-livraison").textContent  = livraison ? "5,00€" : "Gratuite";

    const discountBadge  = document.getElementById("discount-badge");
    const recapReduction = document.getElementById("recap-reduction");
    const recapRedVal    = document.getElementById("recap-reduction-val");

    if (reduction) {
        discountBadge.classList.remove("d-none");
        recapReduction.classList.remove("d-none");
        recapRedVal.textContent = "-" + montantRed.toFixed(2) + "€";
    } else {
        discountBadge.classList.add("d-none");
        recapReduction.classList.add("d-none");
    }
};

// ── Boutons + et - ──
document.getElementById("qty-minus").addEventListener("click", () => {
    if (nbPersonnes > minPersonnes) {
        nbPersonnes--;
        updateRecap();
    }
});

document.getElementById("qty-plus").addEventListener("click", () => {
    nbPersonnes++;
    updateRecap();
});

// ── Soumission ──
document.getElementById("form-commande").addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = await createCommande({
        menuId:         parseInt(menuId),
        datePrestation: document.getElementById("cmd-date").value,
        heureLivraison: document.getElementById("cmd-heure").value,
        adresseLivraison: document.getElementById("cmd-adresse").value.trim(),
        lieuPrestation: document.getElementById("cmd-lieu").value,
        nombrePersonne: nbPersonnes,
    });

    if (data.success) {
        document.getElementById("form-commande").classList.add("d-none");
        document.getElementById("commande-success").classList.remove("d-none");
        sessionStorage.removeItem("menuId");
    } else {
        alert(data.message);
    }
});

// Recalcul du total quand l'adresse change (frais de livraison)
document.getElementById("cmd-adresse").addEventListener("input", () => updateRecap());

// Impossible de choisir une date passée
document.getElementById("cmd-date").min = new Date(Date.now() + 86400000).toISOString().split("T")[0];

// ── Init ──
chargerProfil();
chargerMenu();