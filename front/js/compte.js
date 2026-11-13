// ============================================================
//  compte.js — Espace client / employé / administrateur
//  Les onglets dépendent du rôle lu dans le token JWT.
//  ⚠️ Cela ne sert qu'à l'affichage : chaque action est de toute façon
//  vérifiée côté serveur (security.yaml → access_control).
// ============================================================

import * as api from "./api.js";
import { escapeHtml, naviguer, lireToken, STATUTS_COMMANDE, formatPrix } from "./utils.js";

// ── Accès réservé aux utilisateurs connectés ──
const token = lireToken();
if (!token) {
    naviguer("/connexion");
    throw new Error("Non connecté");
}

const estAdmin   = token.roles.includes("ROLE_ADMIN");
const estEmploye = estAdmin || token.roles.includes("ROLE_EMPLOYE");

// ============================================================
//  OUTILS D'AFFICHAGE
// ============================================================

const message = (texte, type = "success") => {
    const zone = document.getElementById("compte-message");
    zone.className = `alert alert-${type}`;
    zone.textContent = texte;
    zone.scrollIntoView({ behavior: "smooth", block: "center" });
    clearTimeout(message.timer);
    message.timer = setTimeout(() => zone.classList.add("d-none"), 5000);
};

const resultat = (data, recharger) => {
    message(data.message || (data.success ? "Opération réussie." : "Une erreur est survenue."), data.success ? "success" : "danger");
    if (data.success && recharger) recharger();
};

const badgeStatut = (statut) =>
    `<span class="statut-badge statut-${escapeHtml(statut)}">${escapeHtml(STATUTS_COMMANDE[statut] || statut)}</span>`;

const vide = (texte) => `<p class="text-muted mb-0">${texte}</p>`;

// ============================================================
//  ONGLETS
// ============================================================

const onglets = [
    { id: "profil", libelle: "Mon profil", visible: true, charger: chargerProfil },
    { id: "commandes", libelle: "Mes commandes", visible: !estEmploye, charger: chargerMesCommandes },
    { id: "gestion-commandes", libelle: "Commandes", visible: estEmploye, charger: chargerCommandesEmploye },
    { id: "avis", libelle: "Avis à valider", visible: estEmploye, charger: chargerAvisAttente },
    { id: "menus", libelle: "Menus", visible: estEmploye, charger: chargerMenus },
    { id: "horaires", libelle: "Horaires", visible: estEmploye, charger: chargerHoraires },
    { id: "employes", libelle: "Employés", visible: estAdmin, charger: chargerEmployes },
    { id: "stats", libelle: "Statistiques", visible: estAdmin, charger: chargerStats },
].filter((o) => o.visible);

const afficherOnglet = (id) => {
    document.querySelectorAll(".compte-panel").forEach((p) => p.classList.toggle("d-none", p.dataset.panel !== id));
    document.querySelectorAll("#compte-tabs button").forEach((b) => {
        b.classList.toggle("active", b.dataset.tab === id);
        b.setAttribute("aria-selected", b.dataset.tab === id);
    });
    onglets.find((o) => o.id === id)?.charger();
    try { sessionStorage.setItem("ongletCompte", id); } catch { /* stockage indisponible */ }
};

const initOnglets = () => {
    const nav = document.getElementById("compte-tabs");
    nav.innerHTML = onglets.map((o) => `
        <li class="nav-item" role="presentation">
            <button class="compte-tab" type="button" role="tab" data-tab="${o.id}">${o.libelle}</button>
        </li>`).join("");
    nav.addEventListener("click", (e) => {
        const bouton = e.target.closest("button[data-tab]");
        if (bouton) afficherOnglet(bouton.dataset.tab);
    });

    document.getElementById("compte-role-label").textContent =
        estAdmin ? "Espace administrateur" : estEmploye ? "Espace employé" : "Espace client";

    let depart = onglets[estEmploye ? 1 : 0].id;
    try {
        const memo = sessionStorage.getItem("ongletCompte");
        if (onglets.some((o) => o.id === memo)) depart = memo;
    } catch { /* stockage indisponible */ }
    afficherOnglet(depart);
};

// ============================================================
//  PROFIL
// ============================================================

async function chargerProfil() {
    const result = await api.getProfil();
    if (!result.success) {
        if (!lireToken()) naviguer("/connexion");
        return;
    }
    const u = result.data;
    document.getElementById("compte-bonjour").textContent = `Bonjour ${u.prenom} 👋`;
    document.getElementById("p-prenom").value = u.prenom;
    document.getElementById("p-nom").value = u.nom;
    document.getElementById("p-email").value = u.email;
    document.getElementById("p-telephone").value = u.telephone;
    document.getElementById("p-adresse").value = u.adresse;
    document.getElementById("p-ville").value = u.ville;
    document.getElementById("p-cp").value = u.codePostal;
}

document.getElementById("form-profil").addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = await api.updateProfil({
        prenom: document.getElementById("p-prenom").value.trim(),
        nom: document.getElementById("p-nom").value.trim(),
        telephone: document.getElementById("p-telephone").value.trim(),
        adresse: document.getElementById("p-adresse").value.trim(),
        ville: document.getElementById("p-ville").value.trim(),
        codePostal: document.getElementById("p-cp").value.trim(),
    });
    resultat(data, chargerProfil);
});

// ============================================================
//  CLIENT : MES COMMANDES (+ annulation, + avis)
// ============================================================

async function chargerMesCommandes() {
    const zone = document.getElementById("liste-mes-commandes");
    const result = await api.getCommandes();
    if (!result.success) { zone.innerHTML = vide(escapeHtml(result.message)); return; }
    if (!result.data.length) {
        zone.innerHTML = vide('Aucune commande pour le moment. <a href="/menus" onclick="route(event)">Découvrir nos menus</a>');
        return;
    }

    zone.innerHTML = `
        <div class="table-responsive">
        <table class="table compte-table align-middle">
            <thead><tr><th>N°</th><th>Menu</th><th>Prestation</th><th>Pers.</th><th>Total</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            ${result.data.map((c) => `
                <tr>
                    <td class="small">${escapeHtml(c.numeroCommande)}</td>
                    <td>${escapeHtml(c.menu)}</td>
                    <td>${escapeHtml(c.datePrestation)} à ${escapeHtml(c.heureLivraison)}</td>
                    <td>${Number(c.nombrePersonne)}</td>
                    <td>${formatPrix(c.prixMenu + c.prixLivraison)}</td>
                    <td>${badgeStatut(c.statut)}${c.motifAnnulation ? `<div class="small text-muted mt-1">${escapeHtml(c.motifAnnulation)}</div>` : ""}</td>
                    <td class="text-end">
                        ${c.statut === "en_attente" ? `<button class="btn-mini danger" data-annuler="${Number(c.id)}">Annuler</button>` : ""}
                        ${c.statut === "termine" && !c.avisDonne ? `<button class="btn-mini" data-avis="${Number(c.id)}">Donner mon avis</button>` : ""}
                        ${c.statut === "termine" && c.avisDonne ? `<span class="small text-muted">Avis envoyé</span>` : ""}
                    </td>
                </tr>`).join("")}
            </tbody>
        </table>
        </div>`;
}

let commandeAvisId = null;
document.getElementById("liste-mes-commandes").addEventListener("click", async (e) => {
    const annuler = e.target.closest("[data-annuler]");
    const avis = e.target.closest("[data-avis]");
    if (annuler && confirm("Annuler cette commande ?")) {
        resultat(await api.annulerCommande(annuler.dataset.annuler), chargerMesCommandes);
    }
    if (avis) {
        commandeAvisId = avis.dataset.avis;
        document.getElementById("form-avis").reset();
        bootstrap.Modal.getOrCreateInstance("#modal-avis").show();
    }
});

document.getElementById("form-avis").addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = await api.createAvis({
        commandeId: Number(commandeAvisId),
        note: Number(document.getElementById("avis-note").value),
        description: document.getElementById("avis-texte").value.trim(),
    });
    bootstrap.Modal.getOrCreateInstance("#modal-avis").hide();
    resultat(data, chargerMesCommandes);
});

// ============================================================
//  EMPLOYÉ : COMMANDES
// ============================================================

// Statuts proposés à l'employé, dans l'ordre du cycle de vie d'une commande
const STATUTS_EMPLOYE = ["accepte", "en_preparation", "en_cours_livraison", "livre", "en_attente_materiel", "termine"];

const selectFiltre = document.getElementById("emp-filtre-statut");
Object.entries(STATUTS_COMMANDE).forEach(([valeur, libelle]) => selectFiltre.add(new Option(libelle, valeur)));

async function chargerCommandesEmploye() {
    const zone = document.getElementById("liste-commandes-employe");
    const filtres = {};
    if (selectFiltre.value) filtres.statut = selectFiltre.value;
    const client = document.getElementById("emp-filtre-client").value.trim();
    if (client) filtres.client = client;

    const result = await api.getCommandesEmploye(filtres);
    if (!result.success) { zone.innerHTML = vide(escapeHtml(result.message)); return; }
    if (!result.data.length) { zone.innerHTML = vide("Aucune commande ne correspond."); return; }

    zone.innerHTML = result.data.map((c) => {
        const modifiable = !["termine", "annule"].includes(c.statut);
        return `
        <div class="commande-ligne">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <strong>${escapeHtml(c.numeroCommande)}</strong> · ${escapeHtml(c.menu)} ${badgeStatut(c.statut)}
                    <div class="small text-muted">
                        ${escapeHtml(c.client)} — ${escapeHtml(c.email)} — ${escapeHtml(c.telephone || "")}<br>
                        Le ${escapeHtml(c.datePrestation)} à ${escapeHtml(c.heureLivraison)} · ${Number(c.nombrePersonne)} pers. · ${formatPrix(c.prixMenu + c.prixLivraison)}
                        ${c.adresseLivraison ? `<br>📍 ${escapeHtml(c.adresseLivraison)}` : ""}
                        ${c.motifAnnulation ? `<br>Motif d'annulation : ${escapeHtml(c.motifAnnulation)}` : ""}
                    </div>
                </div>
                ${modifiable ? `
                <div class="d-flex gap-2 align-items-start">
                    <select class="filtre-input" data-statut-select="${Number(c.id)}" aria-label="Nouveau statut">
                        ${STATUTS_EMPLOYE.map((s) => `<option value="${s}" ${s === c.statut ? "selected" : ""}>${STATUTS_COMMANDE[s]}</option>`).join("")}
                    </select>
                    <button class="btn-mini" data-maj="${Number(c.id)}">Mettre à jour</button>
                    <button class="btn-mini danger" data-annuler-emp="${Number(c.id)}">Annuler</button>
                </div>` : ""}
            </div>
        </div>`;
    }).join("");
}

document.getElementById("emp-filtre-btn").addEventListener("click", chargerCommandesEmploye);
document.getElementById("emp-filtre-client").addEventListener("keydown", (e) => {
    if (e.key === "Enter") chargerCommandesEmploye();
});

let commandeAnnulationId = null;
document.getElementById("liste-commandes-employe").addEventListener("click", async (e) => {
    const maj = e.target.closest("[data-maj]");
    const annuler = e.target.closest("[data-annuler-emp]");
    if (maj) {
        const statut = document.querySelector(`[data-statut-select="${maj.dataset.maj}"]`).value;
        resultat(await api.updateStatutCommande(maj.dataset.maj, { statut }), chargerCommandesEmploye);
    }
    if (annuler) {
        commandeAnnulationId = annuler.dataset.annulerEmp;
        document.getElementById("form-annulation").reset();
        bootstrap.Modal.getOrCreateInstance("#modal-annulation").show();
    }
});

document.getElementById("form-annulation").addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = await api.updateStatutCommande(commandeAnnulationId, {
        statut: "annule",
        modeContact: document.getElementById("annul-contact").value,
        motifAnnulation: document.getElementById("annul-motif").value.trim(),
    });
    bootstrap.Modal.getOrCreateInstance("#modal-annulation").hide();
    resultat(data, chargerCommandesEmploye);
});

// ============================================================
//  EMPLOYÉ : AVIS
// ============================================================

async function chargerAvisAttente() {
    const zone = document.getElementById("liste-avis-attente");
    const result = await api.getAvisEnAttente();
    if (!result.success) { zone.innerHTML = vide(escapeHtml(result.message)); return; }
    if (!result.data.length) { zone.innerHTML = vide("Aucun avis en attente. 🎉"); return; }

    zone.innerHTML = result.data.map((a) => {
        const note = Math.min(5, Math.max(0, Number(a.note) || 0));
        return `
        <div class="commande-ligne d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="stars">${"★".repeat(note)}${"☆".repeat(5 - note)}</div>
                <p class="mb-1">« ${escapeHtml(a.description)} »</p>
                <div class="small text-muted">${escapeHtml(a.auteur)}</div>
            </div>
            <div class="d-flex gap-2 align-items-start">
                <button class="btn-mini" data-moderer="${Number(a.id)}" data-statut="valide">Valider</button>
                <button class="btn-mini danger" data-moderer="${Number(a.id)}" data-statut="refuse">Refuser</button>
            </div>
        </div>`;
    }).join("");
}

document.getElementById("liste-avis-attente").addEventListener("click", async (e) => {
    const bouton = e.target.closest("[data-moderer]");
    if (bouton) resultat(await api.modererAvis(bouton.dataset.moderer, bouton.dataset.statut), chargerAvisAttente);
});

// ============================================================
//  EMPLOYÉ : MENUS (modification / suppression)
// ============================================================

async function chargerMenus() {
    const zone = document.getElementById("liste-menus-employe");
    const result = await api.getMenus();
    if (!result.success) { zone.innerHTML = vide(escapeHtml(result.message)); return; }

    zone.innerHTML = result.data.map((m) => `
        <form class="commande-ligne row g-2 align-items-end" data-menu="${Number(m.id)}">
            <div class="col-md-4"><label class="auth-label">Titre</label><input class="auth-input" name="titre" maxlength="100" value="${escapeHtml(m.titre)}" required></div>
            <div class="col-4 col-md-2"><label class="auth-label">Prix / pers. (€)</label><input class="auth-input" name="prixParPersonne" type="number" min="1" step="0.5" value="${Number(m.prixParPersonne)}" required></div>
            <div class="col-4 col-md-2"><label class="auth-label">Pers. min.</label><input class="auth-input" name="nombrePersonneMinimum" type="number" min="1" value="${Number(m.nombrePersonneMinimum)}" required></div>
            <div class="col-4 col-md-2"><label class="auth-label">Stock</label><input class="auth-input" name="quantiteRestante" type="number" min="0" value="${Number(m.quantiteRestante)}" required></div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn-mini" type="submit">Enregistrer</button>
                <button class="btn-mini danger" type="button" data-supprimer="${Number(m.id)}" aria-label="Supprimer">🗑</button>
            </div>
            <div class="col-12"><label class="auth-label">Description</label><textarea class="auth-input" name="description" rows="2" required>${escapeHtml(m.description)}</textarea></div>
        </form>`).join("");
}

const zoneMenus = document.getElementById("liste-menus-employe");
zoneMenus.addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const donnees = Object.fromEntries(new FormData(form));
    ["prixParPersonne", "nombrePersonneMinimum", "quantiteRestante"].forEach((k) => (donnees[k] = Number(donnees[k])));
    resultat(await api.updateMenu(form.dataset.menu, donnees), chargerMenus);
});
zoneMenus.addEventListener("click", async (e) => {
    const bouton = e.target.closest("[data-supprimer]");
    if (bouton && confirm("Supprimer définitivement ce menu ?")) {
        resultat(await api.deleteMenu(bouton.dataset.supprimer), chargerMenus);
    }
});

// ============================================================
//  EMPLOYÉ : HORAIRES
// ============================================================

async function chargerHoraires() {
    const zone = document.getElementById("liste-horaires");
    const result = await api.getHoraires();
    if (!result.success) { zone.innerHTML = vide(escapeHtml(result.message)); return; }

    zone.innerHTML = result.data.map((h) => `
        <form class="row g-2 align-items-center mb-2" data-horaire="${Number(h.id)}">
            <div class="col-4 col-md-3 fw-medium">${escapeHtml(h.jour)}</div>
            <div class="col-3 col-md-2"><input class="auth-input" name="heureOuverture" value="${escapeHtml(h.heureOuverture)}" aria-label="Ouverture ${escapeHtml(h.jour)}" required></div>
            <div class="col-3 col-md-2"><input class="auth-input" name="heureFermeture" value="${escapeHtml(h.heureFermeture)}" aria-label="Fermeture ${escapeHtml(h.jour)}" required></div>
            <div class="col-2 col-md-2"><button class="btn-mini" type="submit">OK</button></div>
        </form>`).join("");
}

document.getElementById("liste-horaires").addEventListener("submit", async (e) => {
    e.preventDefault();
    resultat(await api.updateHoraire(e.target.dataset.horaire, Object.fromEntries(new FormData(e.target))), chargerHoraires);
});

// ============================================================
//  ADMIN : EMPLOYÉS
// ============================================================

async function chargerEmployes() {
    const zone = document.getElementById("liste-employes");
    const result = await api.getEmployes();
    if (!result.success) { zone.innerHTML = vide(escapeHtml(result.message)); return; }
    if (!result.data.length) { zone.innerHTML = vide("Aucun employé."); return; }

    zone.innerHTML = `
        <div class="table-responsive">
        <table class="table compte-table align-middle">
            <thead><tr><th>Nom</th><th>E-mail</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            ${result.data.map((u) => `
                <tr>
                    <td>${escapeHtml(u.prenom)} ${escapeHtml(u.nom)}</td>
                    <td class="small">${escapeHtml(u.email)}</td>
                    <td>${u.actif ? '<span class="statut-badge statut-termine">Actif</span>' : '<span class="statut-badge statut-annule">Désactivé</span>'}</td>
                    <td class="text-end"><button class="btn-mini ${u.actif ? "danger" : ""}" data-employe="${Number(u.id)}" data-actif="${u.actif ? 0 : 1}">${u.actif ? "Désactiver" : "Réactiver"}</button></td>
                </tr>`).join("")}
            </tbody>
        </table>
        </div>`;
}

document.getElementById("liste-employes").addEventListener("click", async (e) => {
    const bouton = e.target.closest("[data-employe]");
    if (bouton) resultat(await api.setEmployeActif(bouton.dataset.employe, bouton.dataset.actif === "1"), chargerEmployes);
});

document.getElementById("form-employe").addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = await api.createEmploye({
        prenom: document.getElementById("e-prenom").value.trim(),
        nom: document.getElementById("e-nom").value.trim(),
        email: document.getElementById("e-email").value.trim(),
        password: document.getElementById("e-password").value,
    });
    if (data.success) e.target.reset();
    resultat(data, chargerEmployes);
});

// ============================================================
//  ADMIN : STATISTIQUES (MongoDB) + graphique Chart.js
// ============================================================

let graphique = null;

async function chargerStats() {
    const erreur = document.getElementById("stats-erreur");
    const result = await api.getStatsCommandes();
    if (!result.success) {
        erreur.textContent = result.message;
        erreur.classList.remove("d-none");
        return;
    }
    erreur.classList.add("d-none");

    if (graphique) graphique.destroy();
    graphique = new Chart(document.getElementById("chart-commandes"), {
        type: "bar",
        data: {
            labels: result.data.map((s) => s.titreMenu),
            datasets: [{ label: "Commandes", data: result.data.map((s) => s.nbCommandes), backgroundColor: "#B35A1F", borderRadius: 6 }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

// Période par défaut : les 30 derniers jours
const aujourdHui = new Date();
document.getElementById("ca-fin").value = aujourdHui.toISOString().split("T")[0];
document.getElementById("ca-debut").value = new Date(aujourdHui - 30 * 86400000).toISOString().split("T")[0];

document.getElementById("form-ca").addEventListener("submit", async (e) => {
    e.preventDefault();
    const zone = document.getElementById("ca-resultat");
    const result = await api.getChiffreAffaires(document.getElementById("ca-debut").value, document.getElementById("ca-fin").value);
    if (!result.success) { zone.innerHTML = `<div class="alert alert-warning">${escapeHtml(result.message)}</div>`; return; }

    zone.innerHTML = `
        <p class="fs-5">Total du ${escapeHtml(result.dateDebut)} au ${escapeHtml(result.dateFin)} : <strong>${formatPrix(result.chiffreAffaires)}</strong></p>
        ${result.data.length ? `
        <table class="table compte-table table-responsive">
            <thead><tr><th>Menu</th><th>Commandes</th><th>Chiffre d'affaires</th></tr></thead>
            <tbody>${result.data.map((s) => `<tr><td>${escapeHtml(s.titreMenu)}</td><td>${Number(s.nbCommandes)}</td><td>${formatPrix(s.chiffreAffaires)}</td></tr>`).join("")}</tbody>
        </table>` : vide("Aucune commande sur cette période.")}`;
});

// ── Démarrage ──
initOnglets();
// Le profil est aussi chargé hors de son onglet pour afficher « Bonjour Prénom »
if (!document.querySelector('#compte-tabs button[data-tab="profil"].active')) chargerProfil();
