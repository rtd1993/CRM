const { createServer } = require("http");
const { Server } = require("socket.io");
const mysql = require("mysql2");
const axios = require("axios");
const dayjs = require("dayjs");
require("dotenv").config();

const httpServer = createServer();
const io = new Server(httpServer, {
    cors: { origin: "*" }
});

// DB connection
const db = mysql.createConnection({
    host: "localhost",
    user: "crm_user",
    password: "crm_pass",
    database: "crm"
});

// Utenti online tracciati
const utentiOnline = new Map(); // utente_id => Set(socket.id)

function mandaTelegram(chat_id, messaggio) {
    const url = `https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/sendMessage`;
    return axios.post(url, {
        chat_id: chat_id,
        text: messaggio,
        parse_mode: 'HTML'
    }).catch(err => console.error("Errore Telegram:", err.message));
}

io.on("connection", socket => {
    console.log("📡 Nuova connessione socket");

    socket.on("register", utente_id => {
        if (!utentiOnline.has(utente_id)) {
            utentiOnline.set(utente_id, new Set());
        }
        utentiOnline.get(utente_id).add(socket.id);
    });

    socket.on("disconnect", () => {
        utentiOnline.forEach((sockets, id) => {
            sockets.delete(socket.id);
            if (sockets.size === 0) {
                utentiOnline.delete(id);
            }
        });
    });

    // Chat di gruppo
    socket.on("chat message", data => {
        const { utente_id, utente_nome, testo } = data;
        const timestamp = dayjs().format("YYYY-MM-DD HH:mm:ss");

        db.query("INSERT INTO chat_messaggi (utente_id, messaggio, timestamp) VALUES (?, ?, ?)", 
            [utente_id, testo, timestamp], err => {
                if (err) return console.error("Errore DB chat:", err);
            });

        io.emit("chat message", {
            utente_nome,
            testo,
            orario: timestamp
        });

        // Telegram agli offline
        db.query("SELECT id, telegram_chat_id FROM utenti WHERE telegram_chat_id IS NOT NULL", (err, rows) => {
            if (err) return console.error("Errore utenti:", err);
            rows.forEach(u => {
                if (!utentiOnline.has(u.id)) {
                    mandaTelegram(u.telegram_chat_id, `💬 <b>${utente_nome}</b> ha scritto: ${testo}`);
                }
            });
        });
    });

    // Appunti pratica
    socket.on("nuovo appunto", data => {
        const { utente_id, utente_nome, pratica_id, testo } = data;
        const timestamp = dayjs().format("YYYY-MM-DD HH:mm:ss");

        db.query("INSERT INTO chat_pratiche (utente_id, pratica_id, messaggio, timestamp) VALUES (?, ?, ?, ?)", 
            [utente_id, pratica_id, testo, timestamp], err => {
                if (err) return console.error("Errore DB appunti:", err);
            });

        io.emit("appunto aggiunto", {
            utente_nome,
            pratica_id,
            testo,
            data_inserimento: timestamp
        });

        // Recupera il nome cliente per la notifica
        db.query("SELECT `Cognome/Ragione sociale` AS nome FROM clienti WHERE id = ?", [pratica_id], (err, results) => {
            const cliente_nome = (results && results[0]) ? results[0].nome : `#${pratica_id}`;
            const msg = `📌 <b>${utente_nome}</b> ha inserito un appunto sulla pratica <b>${cliente_nome}</b>`;

            io.emit("chat message", {
                utente_nome: "[Sistema]",
                testo: msg,
                orario: timestamp
            });

            // Telegram agli offline
            db.query("SELECT id, telegram_chat_id FROM utenti WHERE telegram_chat_id IS NOT NULL", (err, rows) => {
                if (err) return console.error("Errore utenti:", err);
                rows.forEach(u => {
                    if (!utentiOnline.has(u.id)) {
                        mandaTelegram(u.telegram_chat_id, msg);
                    }
                });
            });
        });
    });
});

httpServer.listen(3001, () => {
    console.log("✅ Socket.IO attivo su porta 3001");
});
