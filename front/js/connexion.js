// ============================================================
//  connexion.js — Connexion avec appel API
// ============================================================

import { login } from "./api.js";

// Bascule entre connexion et mot de passe oublié
document.getElementById("btn-forgot").addEventListener("click", (e) => {
    e.preventDefault();
    document.getElementById("form-connexion").classList.add("d-none");
    document.getElementById("form-forgot").classList.remove("d-none");
});

document.getElementById("btn-back-login").addEventListener("click", (e) => {
    e.preventDefault();
    document.getElementById("form-forgot").classList.add("d-none");
    document.getElementById("form-connexion").classList.remove("d-none");
});

// Soumission formulaire connexion
document.getElementById("form-connexion").addEventListener("submit", async (e) => {
    e.preventDefault();

    const email    = e.target.querySelector("input[type='email']").value;
    const password = e.target.querySelector("input[type='password']").value;

    const data = await login(email, password);

    if (data.token) {
        // Redirection vers l'espace personnel
        window.history.pushState({}, "", "/compte");
        window.route({ preventDefault: () => {}, target: { href: "/compte" } });
    } else if (data.message === "Ce compte a été désactivé.") {
        alert(data.message);
    } else {
        alert("Email ou mot de passe incorrect.");
    }
});

// Soumission formulaire mot de passe oublié
document.getElementById("form-forgot").addEventListener("submit", (e) => {
    e.preventDefault();
    document.getElementById("forgot-success").classList.remove("d-none");
});

// Onglets
document.getElementById("tab-connexion").addEventListener("click", () => {
    window.history.pushState({}, "", "/connexion");
});

document.getElementById("tab-inscription").addEventListener("click", () => {
    window.history.pushState({}, "", "/inscription");
    window.route({ preventDefault: () => {}, target: { href: "/inscription" } });
});