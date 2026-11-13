// ============================================================
//  Vite & Gourmand — config.js
//  Adresse de l'API selon l'environnement
// ============================================================

// En local (Live Server, npm start...) : API Symfony lancée avec `symfony server:start`
const API_LOCALE = "http://127.0.0.1:8000/api";

// En production : URL publique de l'API déployée sur Railway
// ⚠️ À remplacer par l'URL donnée par Railway (onglet Settings > Networking > Domain)
const API_PRODUCTION = "https://vite-gourmand-api.up.railway.app/api";

const enLocal = ["localhost", "127.0.0.1"].includes(window.location.hostname);

export const API_URL = enLocal ? API_LOCALE : API_PRODUCTION;
