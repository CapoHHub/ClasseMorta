// URL base API: locale in sviluppo, origin corrente in produzione.
const API_URL = window.location.hostname === "localhost"
    ? "http://localhost:8080"
    : window.location.origin;

const login = (API_URL) => {// Elementi UI del form login.
const form = document.getElementById("loginForm");
const message = document.getElementById("loginMessage");

// Esegue la logica solo nella pagina che contiene il form.
if (form && message) {
    form.addEventListener("submit", async (event) => {
        // Evita submit HTML standard per gestire tutto via fetch.
        event.preventDefault();
        // Feedback immediato durante la richiesta.
        message.textContent = "Accesso in corso...";
        message.className = "mt-4 text-sm text-slate-600";

        // Legge i valori inseriti dall'utente.
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;

        try {
            // Chiamata al backend per autenticazione.
            const response = await fetch(`${API_URL}/api/login`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ email, password }),
            });

            // Parse della risposta JSON.
            const data = await response.json();
            // Gestione errore applicativo ritornato dall'API.
            if (!response.ok) {
                throw new Error(data.message || "Credenziali non valide");
            }

            // Persistenza token per richieste successive.
            localStorage.setItem("jwt", data.token);
            message.textContent = "Login riuscito, JWT salvato in localStorage.";
            message.className = "mt-4 text-sm text-green-600";
        } catch (error) {
            // Gestione errori di rete/API.
            message.textContent = error.message || "Errore durante il login";
            message.className = "mt-4 text-sm text-red-600";
        }
    });
}
}