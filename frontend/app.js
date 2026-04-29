const API_URL = window.location.hostname === 'localhost'
    ? 'http://localhost:8080'
    : 'url-railways'


// Richiesta ad un endpoint su backend

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



