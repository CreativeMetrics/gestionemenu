-- Schema gestionemenu
-- Charset utf8mb4 per emoji/caratteri speciali e per corretta gestione di accenti e simbolo €.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    ruolo ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    attivo TINYINT(1) NOT NULL DEFAULT 1,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stagione ENUM('primavera', 'estate', 'autunno', 'inverno') NOT NULL,
    anno SMALLINT UNSIGNED NOT NULL,
    stato ENUM('bozza', 'in_revisione', 'pubblicato', 'archiviato') NOT NULL DEFAULT 'bozza',
    duplicato_da_menu_id INT UNSIGNED NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pubblicato_il DATETIME NULL,
    UNIQUE KEY uniq_stagione_anno (stagione, anno),
    CONSTRAINT fk_menu_duplicato FOREIGN KEY (duplicato_da_menu_id) REFERENCES menus(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS portate (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    gruppo_impaginato ENUM('principale', 'dolci_drink') NOT NULL DEFAULT 'principale',
    CONSTRAINT fk_portata_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    INDEX idx_portate_menu (menu_id, ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS piatti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portata_id INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descrizione TEXT NULL,
    prezzo_testo VARCHAR(100) NOT NULL DEFAULT '',
    prezzo_numero DECIMAL(8,2) NULL,
    tracce_di VARCHAR(255) NULL,
    note_interne TEXT NULL,
    ordine INT NOT NULL DEFAULT 0,
    foto_path VARCHAR(255) NULL,
    creato_da INT UNSIGNED NULL,
    aggiornato_da INT UNSIGNED NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aggiornato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_piatto_portata FOREIGN KEY (portata_id) REFERENCES portate(id) ON DELETE CASCADE,
    CONSTRAINT fk_piatto_creato_da FOREIGN KEY (creato_da) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_piatto_aggiornato_da FOREIGN KEY (aggiornato_da) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_piatti_portata (portata_id, ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS allergeni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codice TINYINT UNSIGNED NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    glifo_unicode VARCHAR(20) NULL,
    ordine INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS piatto_allergeni (
    piatto_id INT UNSIGNED NOT NULL,
    allergene_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (piatto_id, allergene_id),
    CONSTRAINT fk_pa_piatto FOREIGN KEY (piatto_id) REFERENCES piatti(id) ON DELETE CASCADE,
    CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergeni(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS piatto_storico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    piatto_id INT UNSIGNED NOT NULL,
    nome_piatto_snapshot VARCHAR(255) NOT NULL,
    user_id INT UNSIGNED NULL,
    campo VARCHAR(50) NOT NULL,
    valore_precedente TEXT NULL,
    valore_nuovo TEXT NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_storico_piatto FOREIGN KEY (piatto_id) REFERENCES piatti(id) ON DELETE CASCADE,
    CONSTRAINT fk_storico_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_storico_piatto (piatto_id, creato_il)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS impostazioni (
    chiave VARCHAR(100) PRIMARY KEY,
    valore TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: 14 allergeni UE (Reg. 1169/2011).
-- Il font "Allergen Outline" usato in InDesign non ha glifi su codepoint Unicode dedicati:
-- ogni icona corrisponde a una normale lettera maiuscola ASCII digitata con quel font.
-- Le 7 lettere sotto sono state estratte dalla legenda allergeni dell'IDML "Menu A4 - autunno 2026"
-- (Stories u1976/u19a4, u19bb/u19d2, u1a00/u19e9). Le altre 7 (Crostacei, Pesce, Arachidi, Soia,
-- Semi di Sesamo, Lupini, Molluschi) non compaiono in quel menu: vanno individuate aprendo il font
-- Allergen Outline in InDesign e provando le lettere libere (si veda README §"Mappa glifi allergeni").
INSERT INTO allergeni (codice, nome, glifo_unicode, ordine) VALUES
    (1, 'Glutine', 'B', 1),
    (2, 'Crostacei', NULL, 2),
    (3, 'Uovo', 'A', 3),
    (4, 'Pesce', NULL, 4),
    (5, 'Arachidi', NULL, 5),
    (6, 'Soia', NULL, 6),
    (7, 'Latte e derivati', 'I', 7),
    (8, 'Frutta a Guscio', 'D', 8),
    (9, 'Sedano', 'G', 9),
    (10, 'Senape', 'C', 10),
    (11, 'Semi di Sesamo', NULL, 11),
    (12, 'Solfiti', 'M', 12),
    (13, 'Lupini', NULL, 13),
    (14, 'Molluschi', NULL, 14)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Seed: impostazioni di default per export InDesign (rinominabili da pannello)
-- indesign_font_allergeni / indesign_fontstyle_allergeni: font/stile locale applicato alle lettere-icona
-- (in aggiunta allo stile carattere nominato, così le icone sono corrette anche se lo stile carattere
-- non è stato ancora creato nel documento InDesign).
INSERT INTO impostazioni (chiave, valore) VALUES
    ('indesign_encoding', 'UNICODE-WIN'),
    ('indesign_stile_portata', 'Portata'),
    ('indesign_stile_piatto', 'NomePiatto'),
    ('indesign_stile_descrizione', 'Descrizione'),
    ('indesign_stile_prezzo', 'Prezzo'),
    ('indesign_stile_carattere_allergeni', 'IconeAllergeni'),
    ('indesign_font_allergeni', 'Allergen'),
    ('indesign_fontstyle_allergeni', 'Outline')
ON DUPLICATE KEY UPDATE valore = valore;
