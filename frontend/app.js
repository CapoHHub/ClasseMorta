/* =================================================================
   ClasseMorta - app.js
   ================================================================= */

const API_URL =
  window.location.hostname === "localhost"
    ? "http://localhost:8080"
    : "https://classemorta-production.up.railway.app/api";

window.API_URL = API_URL;

function buildApiError(status, data) {
    const baseMsg = (data && data.message) || `Errore HTTP ${status}`;
    const detail  = (data && data.error)   ? ` (${data.error})` : "";
    const err = new Error(baseMsg + detail);
    err.status = status;
    err.data = data;
    if (data) console.error("API error:", data);
    return err;
}

// ---------------- AUTH STORAGE ----------------
const TOKEN_KEY = "classemorta_jwt";
const USER_KEY  = "classemorta_user";

function setAuth(token, utente) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(utente));
}
function clearAuth() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
}
function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}
function getUser() {
    try {
        return JSON.parse(localStorage.getItem(USER_KEY));
    } catch (e) {
        return null;
    }
}
function isProf() {
    const u = getUser();
    return u && u.ruolo === "professore";
}
function isStud() {
    const u = getUser();
    return u && u.ruolo === "studente";
}

async function apiFetch(endpoint, options = {}) {
    const headers = Object.assign({}, options.headers || {});
    const token = getToken();
    if (token && !headers.Authorization) {
        headers.Authorization = `Bearer ${token}`;
    }
    if (options.body && !headers["Content-Type"]) {
        headers["Content-Type"] = "application/json";
    }

    const res = await fetch(`${API_URL}${endpoint}`, { ...options, headers });
    let data = null;
    const text = await res.text();
    if (text) {
        try { data = JSON.parse(text); } catch (e) { data = { raw: text }; }
    }

    if (res.status === 401) {
        clearAuth();
        if (!window.location.pathname.endsWith("login.html")) {
            window.location.href = "login.html";
        }
    }
    if (!res.ok) {
        throw buildApiError(res.status, data);
    }
    return data;
}

function apiXHR(method, endpoint, body = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, `${API_URL}${endpoint}`, true);
        const token = getToken();
        if (token) xhr.setRequestHeader("Authorization", `Bearer ${token}`);
        if (body)  xhr.setRequestHeader("Content-Type", "application/json");
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            let data = null;
            try { data = xhr.responseText ? JSON.parse(xhr.responseText) : null; }
            catch (e) { data = { raw: xhr.responseText }; }

            if (xhr.status === 401) {
                clearAuth();
                if (!window.location.pathname.endsWith("login.html")) {
                    window.location.href = "login.html";
                }
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(data);
            } else {
                reject(buildApiError(xhr.status, data));
            }
        };
        xhr.onerror = () => reject(new Error("Errore di rete"));
        xhr.send(body ? JSON.stringify(body) : null);
    });
}

// ---------------- NAVBAR ----------------
function renderNavbar(activeKey = "") {
    const user = getUser();
    if (!user) return;

    const links = user.ruolo === "professore"
        ? [
            { key: "dashboard", href: "dashboard.html", label: "Dashboard" },
            { key: "studenti",  href: "studenti.html",  label: "Studenti" },
            { key: "voti",      href: "voti.html",      label: "Voti" },
            { key: "verifiche", href: "verifiche.html", label: "Verifiche" },
        ]
        : [
            { key: "dashboard", href: "dashboard.html",        label: "Dashboard" },
            { key: "voti",      href: "voti-studente.html",    label: "I miei voti" },
            { key: "verifiche", href: "verifiche.html",        label: "Verifiche" },
        ];

    const initials = ((user.nome || "?")[0] + (user.cognome || "")[0]).toUpperCase();

    const headerEl = document.querySelector(".cm-header");
    if (!headerEl) return;
    headerEl.innerHTML = `
        <div class="cm-header-inner">
            <a class="cm-brand" href="dashboard.html">
                <img src="assets/logo.png" alt="ClasseMorta">
                <span class="cm-brand-text">
                    ClasseMorta
                    <small>Registro Elettronico</small>
                </span>
            </a>
            <nav class="cm-nav">
                ${links.map(l => `
                    <a href="${l.href}" class="${l.key === activeKey ? 'active' : ''}">${l.label}</a>
                `).join("")}
            </nav>
            <div class="cm-user">
                <div class="cm-user-avatar">${initials}</div>
                <div class="cm-user-info">
                    <b>${escapeHtml(user.nome)} ${escapeHtml(user.cognome)}</b>
                    <span>${user.ruolo}${user.classe ? " · " + user.classe : ""}</span>
                </div>
                <button class="cm-logout" onclick="logout()">Esci</button>
            </div>
        </div>
    `;
}

function logout() {
    clearAuth();
    window.location.href = "login.html";
}

function requireAuth(ruoloAtteso = null) {
    const user = getUser();
    if (!user || !getToken()) {
        window.location.href = "login.html";
        return null;
    }
    if (ruoloAtteso && user.ruolo !== ruoloAtteso) {
        window.location.href = "dashboard.html";
        return null;
    }
    return user;
}

// ---------------- UTIL ----------------
function escapeHtml(s) {
    if (s === null || s === undefined) return "";
    return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function votoClass(voto) {
    const v = parseFloat(voto);
    if (isNaN(v)) return "";
    if (v < 5)  return "cm-voto-rosso";
    if (v <= 6) return "cm-voto-giallo";
    return "cm-voto-verde";
}

function showAlert(elementId, type, msg) {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.className = `cm-alert cm-alert-${type}`;
    el.textContent = msg;
}
function hideAlert(elementId) {
    const el = document.getElementById(elementId);
    if (el) el.className = "cm-alert hidden";
}

function formatDate(iso) {
    if (!iso) return "-";
    try {
        const d = new Date(iso);
        return d.toLocaleString("it-IT", { dateStyle: "medium", timeStyle: "short" });
    } catch (e) { return iso; }
}

// Esposizione globale
window.apiFetch    = apiFetch;
window.apiXHR      = apiXHR;
window.setAuth     = setAuth;
window.clearAuth   = clearAuth;
window.getToken    = getToken;
window.getUser     = getUser;
window.isProf      = isProf;
window.isStud      = isStud;
window.renderNavbar = renderNavbar;
window.logout      = logout;
window.requireAuth = requireAuth;
window.escapeHtml  = escapeHtml;
window.votoClass   = votoClass;
window.showAlert   = showAlert;
window.hideAlert   = hideAlert;
window.formatDate  = formatDate;
