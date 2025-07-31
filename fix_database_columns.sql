-- Script per rinominare i campi della tabella clienti
-- Sostituisce spazi, slash e apostrofi con underscore

USE crm;

-- Backup della tabella prima delle modifiche
CREATE TABLE clienti_backup AS SELECT * FROM clienti;

-- Rinomina tutti i campi problematici
ALTER TABLE clienti CHANGE `Inizio rapporto` `Inizio_rapporto` date;
ALTER TABLE clienti CHANGE `Fine rapporto` `Fine_rapporto` date;
ALTER TABLE clienti CHANGE `Inserito gestionale` `Inserito_gestionale` tinyint(1);
ALTER TABLE clienti CHANGE `Codice ditta` `Codice_ditta` varchar(50);
ALTER TABLE clienti CHANGE `Cognome/Ragione sociale` `Cognome_Ragione_sociale` varchar(255);
ALTER TABLE clienti CHANGE `Codice fiscale` `Codice_fiscale` varchar(50);
ALTER TABLE clienti CHANGE `Partita IVA` `Partita_IVA` varchar(50);
ALTER TABLE clienti CHANGE `Soci Amministratori` `Soci_Amministratori` varchar(255);
ALTER TABLE clienti CHANGE `Sede Legale` `Sede_Legale` text;
ALTER TABLE clienti CHANGE `Sede Operativa` `Sede_Operativa` text;
ALTER TABLE clienti CHANGE `Data di nascita/costituzione` `Data_di_nascita_costituzione` date;
ALTER TABLE clienti CHANGE `Luogo di nascita` `Luogo_di_nascita` varchar(100);
ALTER TABLE clienti CHANGE `Numero carta d'identità` `Numero_carta_identita` varchar(50);
ALTER TABLE clienti CHANGE `Rilasciata dal Comune di` `Rilasciata_dal_Comune_di` varchar(100);
ALTER TABLE clienti CHANGE `Data di rilascio` `Data_di_rilascio` date;
ALTER TABLE clienti CHANGE `Valida per l'espatrio` `Valida_per_espatrio` tinyint(1);
ALTER TABLE clienti CHANGE `Stato civile` `Stato_civile` varchar(50);
ALTER TABLE clienti CHANGE `Data di scadenza` `Data_di_scadenza` date;
ALTER TABLE clienti CHANGE `Descrizione attivita` `Descrizione_attivita` text;
ALTER TABLE clienti CHANGE `Codice ATECO` `Codice_ATECO` varchar(50);
ALTER TABLE clienti CHANGE `Camera di commercio` `Camera_di_commercio` varchar(100);
ALTER TABLE clienti CHANGE `Codice inps` `Codice_inps` varchar(50);
ALTER TABLE clienti CHANGE `Codice inps_2` `Codice_inps_2` varchar(50);
ALTER TABLE clienti CHANGE `Codice inail` `Codice_inail` varchar(50);
ALTER TABLE clienti CHANGE `Cod.PIN Inail` `Cod_PIN_Inail` varchar(50);
ALTER TABLE clienti CHANGE `Cassa Edile` `Cassa_Edile` varchar(100);
ALTER TABLE clienti CHANGE `Numero Cassa Professionisti` `Numero_Cassa_Professionisti` varchar(50);
ALTER TABLE clienti CHANGE `Liquidazione IVA` `Liquidazione_IVA` varchar(50);
ALTER TABLE clienti CHANGE `User Aruba` `User_Aruba` varchar(100);
ALTER TABLE clienti CHANGE `Scadenza PEC` `Scadenza_PEC` date;
ALTER TABLE clienti CHANGE `Rinnovo Pec` `Rinnovo_Pec` date;
ALTER TABLE clienti CHANGE `Link cartella` `Link_cartella` text;

-- Verifica che tutto sia stato rinominato correttamente
SHOW COLUMNS FROM clienti;
