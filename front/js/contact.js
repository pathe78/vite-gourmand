// ============================================================
//  contact.js — Contact avec appel API
// ============================================================

import { sendContact } from "./api.js";

document.getElementById("form-contact").addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.getElementById("contact-email").value;

    const data = await sendContact({
        titre:       document.getElementById("contact-titre").value,
        description: document.getElementById("contact-message").value,
        email:       email,
    });

    if (data.success) {
        document.getElementById("form-contact").classList.add("d-none");
        document.getElementById("contact-email-confirm").textContent = email;
        document.getElementById("contact-success").classList.remove("d-none");
    } else {
        alert(data.message);
    }
});