-- Da eseguire UNA VOLTA SOLA su un database creato prima di questa modifica
-- (chi installa da zero non ne ha bisogno: schema.sql non la inserisce più).
--
-- Il documento InDesign reale non ha mai avuto uno stile paragrafo separato per la descrizione:
-- nome piatto e descrizione condividono sempre lo stesso stile (basato sul nome). Rimuove
-- l'impostazione ormai inutile dal pannello. Nessun impatto se non la esegui: resterebbe solo
-- un valore non più letto da nessuna parte del codice.
--
-- Da phpMyAdmin: apri il tuo database → scheda SQL → incolla ed esegui questo contenuto.

DELETE FROM impostazioni WHERE chiave = 'indesign_stile_descrizione';
