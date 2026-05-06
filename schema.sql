
-- =========================================================
-- Tabella professori
-- =========================================================
CREATE TABLE professori (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- Tabella studenti
-- =========================================================
CREATE TABLE studenti (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    classe VARCHAR(10) NOT NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- Materie
-- =========================================================
CREATE TABLE materie (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(80) UNIQUE NOT NULL
);

-- =========================================================
-- Insegnamenti: quale prof insegna quale materia in quale classe
-- =========================================================
CREATE TABLE insegnamenti (
    id SERIAL PRIMARY KEY,
    professore_id INTEGER NOT NULL REFERENCES professori(id) ON DELETE CASCADE,
    materia_id INTEGER NOT NULL REFERENCES materie(id) ON DELETE CASCADE,
    classe VARCHAR(10) NOT NULL,
    UNIQUE (professore_id, materia_id, classe)
);

-- =========================================================
-- Verifiche con domande aperte
-- =========================================================
CREATE TABLE verifiche (
    id SERIAL PRIMARY KEY,
    professore_id INTEGER NOT NULL REFERENCES professori(id) ON DELETE CASCADE,
    materia_id INTEGER NOT NULL REFERENCES materie(id),
    classe VARCHAR(10) NOT NULL,
    titolo VARCHAR(150) NOT NULL,
    descrizione TEXT,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE domande (
    id SERIAL PRIMARY KEY,
    verifica_id INTEGER NOT NULL REFERENCES verifiche(id) ON DELETE CASCADE,
    testo TEXT NOT NULL,
    ordine INTEGER NOT NULL DEFAULT 1
);

-- =========================================================
-- Risposte degli studenti
-- =========================================================
CREATE TABLE risposte (
    id SERIAL PRIMARY KEY,
    domanda_id INTEGER NOT NULL REFERENCES domande(id) ON DELETE CASCADE,
    studente_id INTEGER NOT NULL REFERENCES studenti(id) ON DELETE CASCADE,
    testo TEXT NOT NULL,
    data_consegna TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (domanda_id, studente_id)
);

-- =========================================================
-- Dati demo
-- =========================================================
INSERT INTO professori (nome, cognome, email, password) VALUES
    ('Mario',  'Rossi',   'mario.rossi@scuola.it',   'prof123'),
    ('Elena',  'Bianchi', 'elena.bianchi@scuola.it', 'prof123');

INSERT INTO studenti (nome, cognome, email, password, classe) VALUES
    ('Luca',  'Verdi',  'luca.verdi@studenti.it',  'studente1', '5A'),
    ('Sofia', 'Neri',   'sofia.neri@studenti.it',  'studente2', '5A'),
    ('Marco', 'Gialli', 'marco.gialli@studenti.it','studente3', '4B'),
    ('Anna',  'Blu',    'anna.blu@studenti.it',    'studente4', '4B');

INSERT INTO materie (nome) VALUES
    ('Matematica'),
    ('Italiano'),
    ('Informatica'),
    ('Storia');

-- Mario Rossi insegna Matematica e Informatica in 5A; Storia in 4B
INSERT INTO insegnamenti (professore_id, materia_id, classe) VALUES
    (1, 1, '5A'),
    (1, 3, '5A'),
    (1, 4, '4B');

-- Elena Bianchi insegna Italiano in 5A e 4B
INSERT INTO insegnamenti (professore_id, materia_id, classe) VALUES
    (2, 2, '5A'),
    (2, 2, '4B');
