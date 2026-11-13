// ============================================================
//  home.js — Chargement des avis depuis l'API
// ============================================================
 
import { getAvis } from "./api.js";
import { escapeHtml } from "./utils.js";
 
const chargerAvis = async () => {
    const result = await getAvis();
 
    if (!result.success || result.data.length === 0) return;
 
    const container = document.getElementById("avis-container");
 
    // escapeHtml : un avis est saisi par un client, il ne doit jamais être interprété comme du HTML
    container.innerHTML = result.data.map((avis) => {
        const note = Math.min(5, Math.max(0, parseInt(avis.note, 10) || 0));
        return `
        <div class="col-md-4">
            <div class="review-card">
                <div class="stars">${"★".repeat(note)}${"☆".repeat(5 - note)}</div>
                <p class="review-text">« ${escapeHtml(avis.description)} »</p>
                <div class="review-author">${escapeHtml(avis.auteur)}</div>
            </div>
        </div>
    `;
    }).join("");
};
 
chargerAvis();
 