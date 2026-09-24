-- Da eseguire UNA VOLTA SOLA su un database creato prima di questa modifica
-- (chi installa da zero non ne ha bisogno: le colonne sono già in migrations/schema.sql).
--
-- Aggiunge il tracciamento di "quando è stato scaricato l'ultimo export InDesign" per ciascuna
-- delle due parti del menu (principale / dolci & drink), e permette allo storico modifiche di
-- sopravvivere all'eliminazione di un piatto (serve per l'elenco "modifiche dall'ultimo export",
-- che deve poter mostrare anche i piatti nel frattempo rimossi).
--
-- Da phpMyAdmin: apri il tuo database → scheda SQL → incolla ed esegui questo contenuto.

ALTER TABLE menus
    ADD COLUMN export_principale_il DATETIME NULL AFTER pubblicato_il,
    ADD COLUMN export_dolci_drink_il DATETIME NULL AFTER export_principale_il;

ALTER TABLE piatto_storico
    DROP FOREIGN KEY fk_storico_piatto;

ALTER TABLE piatto_storico
    MODIFY piatto_id INT UNSIGNED NULL,
    ADD COLUMN menu_id_snapshot INT UNSIGNED NULL AFTER valore_nuovo,
    ADD COLUMN gruppo_impaginato_snapshot ENUM('principale', 'dolci_drink') NULL AFTER menu_id_snapshot;

ALTER TABLE piatto_storico
    ADD CONSTRAINT fk_storico_piatto FOREIGN KEY (piatto_id) REFERENCES piatti(id) ON DELETE SET NULL;

ALTER TABLE piatto_storico
    ADD INDEX idx_storico_eliminazioni (menu_id_snapshot, gruppo_impaginato_snapshot, creato_il);
