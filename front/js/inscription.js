// ============================================================
//  inscription.js — Inscription avec appel API
// ============================================================

import { inscription } from "./api.js";

const password = document.getElementById("password");
const passwordConfirm = document.getElementById("password-confirm");

const rules = {
    "rule-length":  (v) => v.length >= 10,
    "rule-upper":   (v) => /[A-Z]/.test(v),
    "rule-lower":   (v) => /[a-z]/.test(v),
    "rule-number":  (v) => /[0-9]/.test(v),
    "rule-special": (v) => /[^A-Za-z0-9]/.test(v),
};

password.addEventListener("input", () => {
    const val = password.value;
    Object.entries(rules).forEach(([id, test]) => {
        const rule = document.getElementById(id);
        const dot  = rule.querySelector(".pwd-dot");
        if (test(val)) {
            dot.classList.add("ok");
            rule.classList.add("ok");
        } else {
            dot.classList.remove("ok");
            rule.classList.remove("ok");
        }
    });
});

passwordConfirm.addEventListener("input", () => {
    const error = document.getElementById("pwd-match-error");
    error.classList.toggle("d-none", passwordConfirm.value === password.value);
});

document.getElementById("tab-connexion").addEventListener("click", () => {
    window.history.pushState({}, "", "/connexion");
    window.route({ preventDefault: () => {}, target: { href: "/connexion" } });
});

document.getElementById("form-inscription").addEventListener("submit", async (e) => {
    e.preventDefault();

    if (password.value !== passwordConfirm.value) {
        document.getElementById("pwd-match-error").classList.remove("d-none");
        return;
    }

    const allValid = Object.values(rules).every((test) => test(password.value));
    if (!allValid) {
        alert("Votre mot de passe ne respecte pas les critères de sécurité.");
        return;
    }

    const data = await inscription({
        email:      document.getElementById("email").value,
        password:   password.value,
        nom:        document.getElementById("nom").value,
        prenom:     document.getElementById("prenom").value,
        telephone:  document.getElementById("telephone").value,
        adresse:    document.getElementById("adresse").value,
        ville:      document.getElementById("adresse").value.split(",")[1]?.trim() || "",
        codePostal: document.getElementById("adresse").value.match(/\d{5}/)?.[0] || "",
    });

    if (data.success) {
        document.getElementById("form-inscription").classList.add("d-none");
        document.getElementById("inscription-success").classList.remove("d-none");
    } else {
        alert(data.message);
    }
});