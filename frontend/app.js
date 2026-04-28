const API_URL = window.location.hostname === 'localhost'
    ? 'http://localhost:8080'
    : 'url-railways'

fetch(`${API_URL}/get-data`)
    .then(res => res.json())
    .then(data => console.log(data))