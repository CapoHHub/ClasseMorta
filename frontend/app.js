// URL base API: locale in sviluppo, origin corrente in produzione.
const API_URL = window.location.hostname === "localhost"
    ? "http://localhost:8080"
    : window.location.origin;

function apiRequest(endpoint) {
    fetch(`${API_URL}${endpoint}`)
        .then(async (res) => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                throw new Error(`Risposta non JSON dal backend: ${text}`);
            }
        })
        .then((data) => console.log(data))
        .catch((err) => console.error(err));
}

window.apiRequest = apiRequest;

// Elementi UI del form login.
const form = document.getElementById("loginForm");
const message = document.getElementById("loginMessage");

// Esegue la logica solo nella pagina che contiene il form.
if (form && message) {
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        message.textContent = "Accesso in corso...";
        message.className = "mt-4 text-sm text-slate-600";

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;

        try {
            const response = await fetch(`${API_URL}/api/login`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, password }),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "Credenziali non valide");
            }

            localStorage.setItem("jwt", data.token);
            message.textContent = "Login riuscito, JWT salvato in localStorage.";
            message.className = "mt-4 text-sm text-green-600";
        } catch (error) {
            message.textContent = error.message || "Errore durante il login";
            message.className = "mt-4 text-sm text-red-600";
        }
    });
}
