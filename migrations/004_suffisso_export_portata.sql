-- Da eseguire UNA VOLTA SOLA su un database creato prima di questa modifica
-- (chi installa da zero non ne ha bisogno: la colonna è già in migrations/schema.sql).
--
-- Aggiunge un suffisso opzionale per portata, applicato SOLO nell'export InDesign dopo il nome
-- (es. "antipasti**" per un rimando a nota, "secondi e contorni"): non cambia il nome mostrato
-- nell'app, così chi gestisce i piatti non vede simboli/testo pensati solo per la stampa.
--
-- Da phpMyAdmin: apri il tuo database → scheda SQL → incolla ed esegui questo contenuto.

ALTER TABLE portate
    ADD COLUMN suffisso_export VARCHAR(30) NULL AFTER gruppo_impaginato;
