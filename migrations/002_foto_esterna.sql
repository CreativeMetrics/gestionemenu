-- Da eseguire UNA VOLTA SOLA su un database creato prima di questa modifica
-- (chi installa da zero non ne ha bisogno: la colonna è già in migrations/schema.sql).
--
-- Aggiunge il flag "foto già presente altrove" ai piatti: permette di segnare come già
-- coperti, senza doverli ricaricare nell'app, i piatti la cui foto esiste già altrove
-- (es. usata direttamente in InDesign) — escludendoli dalla lista "Foto mancanti".
--
-- Da phpMyAdmin: apri il tuo database → scheda SQL → incolla ed esegui questo contenuto.

ALTER TABLE piatti
    ADD COLUMN foto_esterna TINYINT(1) NOT NULL DEFAULT 0 AFTER foto_path;
