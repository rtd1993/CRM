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
    user: "crmuser",
    password: "Admin123!",
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

async function sendTelegramNotificationForUser(userId, senderName, message) {
    try {
        const user = await new Promise((resolve, reject) => {
            db.query("SELECT telegram_chat_id FROM utenti WHERE id = ?", [userId], (err, results) => {
                if (err) reject(err);
                else resolve(results[0]);
            });
        });
        
        if (user && user.telegram_chat_id) {
            const notificationText = `🔔 <b>Nuovo messaggio CRM</b>\n\n👤 <b>${senderName}</b>\n💬 ${message}\n\n📅 ${dayjs().format('DD/MM/YYYY HH:mm')}`;
            await mandaTelegram(user.telegram_chat_id, notificationText);
        }
    } catch (error) {
        console.error(`Errore notifica Telegram per utente ${userId}:`, error);
    }
}

io.on("connection", socket => {
    console.log("📡 Nuova connessione socket");
    
    let currentUserId = null;

    socket.on("register", utente_id => {
        currentUserId = utente_id;
        if (!utentiOnline.has(utente_id)) {
            utentiOnline.set(utente_id, new Set());
        }
        utentiOnline.get(utente_id).add(socket.id);
        
        // Notifica altri utenti che questo utente è online
        socket.broadcast.emit("user_online", { user_id: utente_id });
        
        console.log(`👤 Utente ${utente_id} registrato`);
    });

    socket.on("disconnect", () => {
        if (currentUserId) {
            const userSockets = utentiOnline.get(currentUserId);
            if (userSockets) {
                userSockets.delete(socket.id);
                if (userSockets.size === 0) {
                    utentiOnline.delete(currentUserId);
                    // Notifica che l'utente è offline
                    socket.broadcast.emit("user_offline", { user_id: currentUserId });
                }
            }
        }
        
        utentiOnline.forEach((sockets, id) => {
            sockets.delete(socket.id);
            if (sockets.size === 0) {
                utentiOnline.delete(id);
            }
        });
    });
    
    // Nuovo sistema messaggi chat
    socket.on("send_message", async data => {
        const { chat_id, message, user_id, user_name } = data;
        const timestamp = dayjs().format("YYYY-MM-DD HH:mm:ss");
        
        try {
            // Salva messaggio nel database
            await new Promise((resolve, reject) => {
                db.query(
                    "INSERT INTO chat_messages_new (chat_id, user_id, message, created_at) VALUES (?, ?, ?, ?)", 
                    [chat_id, user_id, message, timestamp], 
                    (err, result) => {
                        if (err) reject(err);
                        else resolve(result);
                    }
                );
            });
            
            // Ottieni partecipanti alla chat
            const participants = await new Promise((resolve, reject) => {
                db.query(
                    "SELECT user_id FROM chat_participants WHERE chat_id = ? AND is_active = 1", 
                    [chat_id], 
                    (err, results) => {
                        if (err) reject(err);
                        else resolve(results);
                    }
                );
            });
            
            // Invia messaggio a tutti i partecipanti online
            const messageData = {
                chat_id,
                user_id,
                user_name,
                message,
                created_at: timestamp,
                chat_type: data.chat_type || 'privata'
            };
            
            participants.forEach(participant => {
                const userSockets = utentiOnline.get(participant.user_id);
                if (userSockets) {
                    userSockets.forEach(socketId => {
                        io.to(socketId).emit("new_message", messageData);
                    });
                } else {
                    // Utente offline, invia notifica Telegram se configurato
                    sendTelegramNotificationForUser(participant.user_id, user_name, message);
                }
            });
            
            console.log(`💬 Messaggio inviato nella chat ${chat_id} da ${user_name}`);
            
        } catch (error) {
            console.error("❌ Errore invio messaggio:", error);
            socket.emit("message_error", { error: "Errore nell'invio del messaggio" });
        }
    });
    
    // Indicatore "sta scrivendo"
    socket.on("typing", data => {
        const { chat_id, user_id, user_name } = data;
        
        // Invia a tutti gli altri partecipanti della chat
        socket.broadcast.emit("user_typing", {
            chat_id,
            user_id,
            user_name
        });
    });
    
    // Gestione chat globale (compatibilità con vecchio sistema)
    socket.on("chat message", data => {
        const { utente_id, utente_nome, testo } = data;
        const timestamp = dayjs().format("YYYY-MM-DD HH:mm:ss");
        
        // Salva nel vecchio sistema (chat_id = 'general')
        db.query("INSERT INTO chat_messaggi (chat_id, user_id, message, timestamp) VALUES (?, ?, ?, ?)", 
            ['general', utente_id, testo, timestamp], err => {
                if (err) console.error("Errore DB chat globale:", err);
            });
        
        // Salva anche nel nuovo sistema (chat_id = 1 per chat globale)
        db.query("INSERT INTO chat_messages_new (chat_id, user_id, message, created_at) VALUES (?, ?, ?, ?)", 
            [1, utente_id, testo, timestamp], err => {
                if (err) console.error("Errore DB nuovo sistema:", err);
            });
        
        const messageData = {
            chat_id: 1,
            user_id: utente_id,
            user_name: utente_nome,
            message: testo,
            created_at: timestamp,
            chat_type: 'globale'
        };
        
        // Invia a tutti
        io.emit("chat message", data); // Compatibilità vecchio sistema
        io.emit("new_message", messageData); // Nuovo sistema
        
        // Notifica Telegram utenti offline
        utentiOnline.forEach((sockets, userId) => {
            if (userId != utente_id) {
                // Se l'utente non è online, invia notifica Telegram
                if (sockets.size === 0) {
                    sendTelegramNotificationForUser(userId, utente_nome, testo);
                }
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
        db.query("SELECT id, nome, telegram_chat_id FROM utenti WHERE telegram_chat_id IS NOT NULL AND telegram_chat_id != ''", (err, rows) => {
            if (err) return console.error("Errore utenti:", err);
            
            rows.forEach(u => {
                // Controlla se l'utente è offline E non è l'autore del messaggio
                if (!utentiOnline.has(u.id) && u.id !== utente_id) {
                    console.log(`📱 Invio notifica Telegram a ${u.nome} (offline)`);
                    
                    const notificationText = `🔔 <b>Nuovo messaggio CRM</b>

� <b>${utente_nome}</b>
💬 ${testo}

📅 ${dayjs().format('DD/MM/YYYY HH:mm')}`;
                    
                    mandaTelegram(u.telegram_chat_id, notificationText);
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
