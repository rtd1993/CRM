-- Tabella per i template email
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `oggetto` varchar(500) NOT NULL,
  `corpo` text NOT NULL,
  `data_creazione` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_modifica` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabella per il log degli invii email
CREATE TABLE IF NOT EXISTS `email_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `template_id` int NOT NULL,
  `oggetto` varchar(500) NOT NULL,
  `corpo` text NOT NULL,
  `destinatario_email` varchar(255) NOT NULL,
  `destinatario_nome` varchar(255) NOT NULL,
  `data_invio` datetime DEFAULT CURRENT_TIMESTAMP,
  `stato` enum('inviata','fallita') DEFAULT 'inviata',
  `messaggio_errore` text,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `email_log_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clienti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_log_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Inserisco alcuni template di esempio
INSERT INTO `email_templates` (`nome`, `oggetto`, `corpo`) VALUES
('Comunicazione generale', 'Comunicazione importante da AS Contabilmente', 
'Gentile {nome_cliente},\n\nSperiamo che tutto proceda al meglio per lei e la sua attività.\n\nLe scriviamo per comunicarle:\n\n[INSERIRE TESTO DELLA COMUNICAZIONE]\n\nRimaniamo a disposizione per qualsiasi chiarimento.\n\nCordiali saluti,\nIl team di AS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com'),

('Scadenza documenti', 'Promemoria scadenze - {nome_cliente}',
'Gentile {nome_cliente},\n\nLe ricordiamo che sono in scadenza i seguenti documenti/adempimenti:\n\n[ELENCO SCADENZE]\n\nLa preghiamo di contattarci al più presto per procedere con gli adempimenti necessari.\n\nGrazie per la collaborazione.\n\nCordiali saluti,\nAS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com'),

('Richiesta documenti', 'Richiesta documentazione - {nome_cliente}',
'Gentile {nome_cliente},\n\nPer procedere con le pratiche in corso, abbiamo bisogno della seguente documentazione:\n\n[ELENCO DOCUMENTI RICHIESTI]\n\nLa preghiamo di inviarci i documenti richiesti entro [DATA SCADENZA].\n\nRimaniamo a disposizione per qualsiasi chiarimento.\n\nCordiali saluti,\nAS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com'),

('Auguri festività', 'Auguri di {festivita} da AS Contabilmente',
'Gentile {nome_cliente},\n\nTutto il team di AS Contabilmente desidera farle i migliori auguri di {festivita}.\n\nLe auguriamo un periodo di serenità e felicità insieme ai suoi cari.\n\nCordiali saluti,\nAS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com');
