-- Tabella per i Professori
CREATE TABLE professori (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_cry TEXT NOT NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella per gli Studenti
CREATE TABLE studenti (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_cry TEXT NOT NULL,
    classe VARCHAR(10) NOT NULL, -- Esempio: '5A', '3B'
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO professori (nome, cognome, email, password_cry) 
VALUES 
('Mario', 'Rossi', 'mario.rossi@scuola.it', '$2b$12$K8p5v...'),
('Elena', 'Bianchi', 'elena.bianchi@scuola.it', '$2b$12$T9q6w...');
INSERT INTO studenti (nome, cognome, email, password_cry, classe) 
VALUES 
('Luca', 'Verdi', 'luca.verdi@studenti.it', '$2b$12$Z3x8y...', '5A'),
('Sofia', 'Neri', 'sofia.neri@studenti.it', '$2b$12$A1b2c...', '5A'),
('Marco', 'Gialli', 'marco.gialli@studenti.it', '$2b$12$D4e5f...', '4B');