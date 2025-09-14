?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevTools - Sistema di Sviluppo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .devtools-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .section-card { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
            overflow: hidden;
        }
        .section-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            border-bottom: 3px solid #5a67d8;
        }
        .section-content { padding: 25px; }
        .stat-box { 
            background: #f8f9fa; 
            border-left: 4px solid #007bff; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 0 5px 5px 0;
        }
        .resource-bar { 
            background: #e9ecef; 
            height: 20px; 
            border-radius: 10px; 
            overflow: hidden; 
            margin: 5px 0;
        }
        .resource-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #28a745, #ffc107, #dc3545); 
            transition: width 0.3s ease;
        }
        .service-control { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 15px; 
            margin: 10px 0; 
            border: 1px solid #dee2e6; 
            border-radius: 8px;
        }
        .status-indicator { 
            width: 12px; 
            height: 12px; 
            border-radius: 50%; 
            display: inline-block; 
            margin-right: 8px;
        }
        .status-running { background-color: #28a745; }
        .status-stopped { background-color: #dc3545; }
        .status-unknown { background-color: #ffc107; }
        .query-editor { 
            font-family: 'Consolas', 'Monaco', monospace; 
            background: #2d3748; 
            color: #e2e8f0; 
            border: none; 
            border-radius: 5px;
        }
        .cleanup-item { 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            border-radius: 5px; 
            padding: 15px; 
            margin: 10px 0;
        }
        .nav-pills .nav-link.active { 
            background-color: #667eea; 
        }
        .loading { 
            opacity: 0.6; 
            pointer-events: none; 
        }
        .table-responsive { 
            max-height: 400px; 
            overflow-y: auto; 
        }
        .network-status { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 10px; 
            margin: 5px 0; 
            background: #f8f9fa; 
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="devtools-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tools text-primary"></i> DevTools - Sistema di Sviluppo</h1>
            <button class="btn btn-outline-primary" onclick="refreshAllSections()">
                <i class="fas fa-sync-alt"></i> Aggiorna Tutto
            </button>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4" id="devtoolsTabs">
            <li class="nav-item">
                <button class="nav-link active" id="stats-tab" data-bs-toggle="pill" data-bs-target="#stats">
                    <i class="fas fa-chart-bar"></i> Statistiche
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources">
                    <i class="fas fa-server"></i> Risorse
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="network-tab" data-bs-toggle="pill" data-bs-target="#network">
                    <i class="fas fa-network-wired"></i> Network
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="services-tab" data-bs-toggle="pill" data-bs-target="#services">
                    <i class="fas fa-cogs"></i> Servizi
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="mysql-tab" data-bs-toggle="pill" data-bs-target="#mysql">
                    <i class="fas fa-database"></i> MySQL
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="cleanup-tab" data-bs-toggle="pill" data-bs-target="#cleanup">
                    <i class="fas fa-broom"></i> Pulizia
                </button>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content" id="devtoolsContent">
            
            <!-- Sezione 1: Statistiche -->
            <div class="tab-pane fade show active" id="stats">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-bar"></i> Statistiche Sistema</h3>
                        <p class="mb-0">Panoramica dei dati nel database e utilizzo del sistema</p>
                    </div>
                    <div class="section-content">
                        <div class="row" id="stats-content">
                            <div class="col-12 text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Caricamento statistiche...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione 2: Risorse -->
            <div class="tab-pane fade" id="resources">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-server"></i> Utilizzo Risorse</h3>
                        <p class="mb-0">Monitoraggio spazio disco, memoria e prestazioni</p>
                    </div>
                    <div class="section-content">
                        <div id="resources-content">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Caricamento informazioni risorse...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione 3: Network -->
            <div class="tab-pane fade" id="network">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-network-wired"></i> Informazioni Network</h3>
                        <p class="mb-0">Stato connessione, ping e diagnostica rete</p>
                    </div>
                    <div class="section-content">
                        <div id="network-content">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Caricamento informazioni di rete...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione 4: Servizi -->
            <div class="tab-pane fade" id="services">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-cogs"></i> Gestione Servizi</h3>
                        <p class="mb-0">Controllo e monitoraggio servizi sistema</p>
                    </div>
                    <div class="section-content">
                        <div id="services-content">
                            <div class="service-control">
                                <div>
                                    <span class="status-indicator status-unknown"></span>
                                    <strong>Apache Web Server</strong>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="controlService('apache2', 'start')">
                                        <i class="fas fa-play"></i> Start
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="controlService('apache2', 'restart')">
                                        <i class="fas fa-redo"></i> Restart
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="controlService('apache2', 'stop')">
                                        <i class="fas fa-stop"></i> Stop
                                    </button>
                                </div>
                            </div>

                            <div class="service-control">
                                <div>
                                    <span class="status-indicator status-unknown"></span>
                                    <strong>MySQL Database</strong>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="controlService('mysql', 'start')">
                                        <i class="fas fa-play"></i> Start
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="controlService('mysql', 'restart')">
                                        <i class="fas fa-redo"></i> Restart
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="controlService('mysql', 'stop')">
                                        <i class="fas fa-stop"></i> Stop
                                    </button>
                                </div>
                            </div>

                            <div class="service-control">
                                <div>
                                    <span class="status-indicator status-unknown"></span>
                                    <strong>Node.js Server</strong>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="controlService('nodejs', 'start')">
                                        <i class="fas fa-play"></i> Start
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="controlService('nodejs', 'restart')">
                                        <i class="fas fa-redo"></i> Restart
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="controlService('nodejs', 'stop')">
                                        <i class="fas fa-stop"></i> Stop
                                    </button>
                                </div>
                            </div>

                            <div class="service-control">
                                <div>
                                    <span class="status-indicator status-unknown"></span>
                                    <strong>Rclone Sync</strong>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="controlService('rclone', 'start')">
                                        <i class="fas fa-play"></i> Start
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="controlService('rclone', 'restart')">
                                        <i class="fas fa-redo"></i> Restart
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="controlService('rclone', 'stop')">
                                        <i class="fas fa-stop"></i> Stop
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione 5: MySQL -->
            <div class="tab-pane fade" id="mysql">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-database"></i> Gestione MySQL</h3>
                        <p class="mb-0">Query personalizzate e amministrazione database</p>
                    </div>
                    <div class="section-content">
                        <div class="mb-4">
                            <h5>Query Personalizzata</h5>
                            <textarea class="form-control query-editor" rows="6" id="sqlQuery" placeholder="Inserisci la tua query SQL qui...
Esempi:
- SELECT * FROM utenti LIMIT 10;
- SHOW TABLES;
- DESCRIBE nome_tabella;
- UPDATE tabella SET campo = 'valore' WHERE id = 1;"></textarea>
                            <div class="mt-2">
                                <button class="btn btn-primary" onclick="executeCustomQuery()">
                                    <i class="fas fa-play"></i> Esegui Query
                                </button>
                                <button class="btn btn-secondary" onclick="clearQuery()">
                                    <i class="fas fa-eraser"></i> Pulisci
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Quick Actions</h5>
                            <div class="btn-group mb-2">
                                <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SHOW TABLES')">Show Tables</button>
                                <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SHOW PROCESSLIST')">Process List</button>
                                <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SELECT COUNT(*) as total_clients FROM clienti')">Count Clients</button>
                                <button class="btn btn-outline-info btn-sm" onclick="quickQuery('SELECT COUNT(*) as total_messages FROM chat_messages')">Count Messages</button>
                            </div>
                        </div>

                        <div id="query-results">
                            <!-- Risultati query verranno inseriti qui -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione 6: Pulizia -->
            <div class="tab-pane fade" id="cleanup">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-broom"></i> Pulizia e Manutenzione</h3>
                        <p class="mb-0">Strumenti per ottimizzazione e pulizia sistema</p>
                    </div>
                    <div class="section-content">
                        <div class="cleanup-item">
                            <h6><i class="fas fa-file-alt text-warning"></i> Pulizia File di Log</h6>
                            <p class="mb-2">Elimina tutti i file di log vecchi per liberare spazio</p>
                            <button class="btn btn-warning btn-sm" onclick="performCleanup('logs')">
                                <i class="fas fa-trash"></i> Pulisci Log
                            </button>
                        </div>

                        <div class="cleanup-item">
                            <h6><i class="fas fa-comments text-info"></i> Archivia Chat Vecchie</h6>
                            <p class="mb-2">Elimina messaggi chat più vecchi di 6 mesi</p>
                            <button class="btn btn-info btn-sm" onclick="performCleanup('old_chats')">
                                <i class="fas fa-archive"></i> Archivia Chat
                            </button>
                        </div>

                        <div class="cleanup-item">
                            <h6><i class="fas fa-database text-success"></i> Ottimizzazione Database</h6>
                            <p class="mb-2">Ottimizza tutte le tabelle del database per migliorare le prestazioni</p>
                            <button class="btn btn-success btn-sm" onclick="performCleanup('optimize_db')">
                                <i class="fas fa-tachometer-alt"></i> Ottimizza DB
                            </button>
                        </div>

                        <div class="cleanup-item">
                            <h6><i class="fas fa-box text-primary"></i> Archiviazione Chat</h6>
                            <p class="mb-2">Sposta le chat inattive in archivio</p>
                            <button class="btn btn-primary btn-sm" onclick="performCleanup('archive_chats')">
                                <i class="fas fa-box-open"></i> Archivia
                            </button>
                        </div>

                        <div id="cleanup-results" class="mt-3">
                            <!-- Risultati operazioni di pulizia -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Carica dati al cambio tab
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            
            // Event listeners per i tab
            document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    const target = event.target.getAttribute('data-bs-target');
                    switch(target) {
                        case '#stats': loadStats(); break;
                        case '#resources': loadResources(); break;
                        case '#network': loadNetwork(); break;
                        case '#services': loadServices(); break;
                    }
                });
            });
        });

        function loadStats() {
            fetch('devtools.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'ajax_action=get_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStats(data.data);
                } else {
                    document.getElementById('stats-content').innerHTML = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
                }
            });
        }

        function displayStats(stats) {
            const html = `
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6><i class="fas fa-users text-primary"></i> Clienti Totali</h6>
                        <h3 class="text-primary">${stats.clienti}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6><i class="fas fa-comments text-success"></i> Messaggi Chat</h6>
                        <h3 class="text-success">${stats.messaggi}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6><i class="fas fa-file-alt text-warning"></i> Pratiche ENEA</h6>
                        <h3 class="text-warning">${stats.enea}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6><i class="fas fa-thermometer-half text-info"></i> Conto Termico</h6>
                        <h3 class="text-info">${stats.conto_termico}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6><i class="fas fa-tasks text-secondary"></i> Task Totali</h6>
                        <h3 class="text-secondary">${stats.task}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box">
                        <h6><i class="fas fa-check-circle text-success"></i> Task Completati</h6>
                        <h3 class="text-success">${stats.task_completati}</h3>
                    </div>
                </div>
            `;
            document.getElementById('stats-content').innerHTML = html;
        }

        function loadResources() {
            fetch('devtools.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'ajax_action=get_resources'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResources(data.data);
                } else {
                    document.getElementById('resources-content').innerHTML = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
                }
            });
        }

        function displayResources(resources) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-hdd text-primary"></i> Spazio Disco</h6>
                        <div class="resource-bar">
                            <div class="resource-fill" style="width: ${resources.disk.percent}%"></div>
                        </div>
                        <small>Usato: ${resources.disk.used} / Totale: ${resources.disk.total} (${resources.disk.percent}%)</small>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-memory text-success"></i> Memoria PHP</h6>
                        <p>Corrente: ${resources.memory.current}</p>
                        <p>Picco: ${resources.memory.peak}</p>
                    </div>
                </div>
            `;
            
            if (resources.load) {
                html += `
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6><i class="fas fa-tachometer-alt text-warning"></i> Carico Sistema</h6>
                            <p>1min: ${resources.load['1min']} | 5min: ${resources.load['5min']} | 15min: ${resources.load['15min']}</p>
                        </div>
                    </div>
                `;
            }
            
            if (resources.local_drive) {
                html += `
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6><i class="fas fa-folder text-info"></i> Cartella Local Drive</h6>
                            <p>Dimensione: ${resources.local_drive}</p>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('resources-content').innerHTML = html;
        }

        function loadNetwork() {
            fetch('devtools.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'ajax_action=get_network'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayNetwork(data.data);
                } else {
                    document.getElementById('network-content').innerHTML = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
                }
            });
        }

        function displayNetwork(network) {
            const html = `
                <div class="network-status">
                    <span><i class="fas fa-server text-primary"></i> IP Server</span>
                    <strong>${network.server_ip}</strong>
                </div>
                <div class="network-status">
                    <span><i class="fas fa-user text-success"></i> IP Client</span>
                    <strong>${network.client_ip}</strong>
                </div>
                <div class="network-status">
                    <span><i class="fas fa-desktop text-info"></i> Hostname</span>
                    <strong>${network.hostname}</strong>
                </div>
                <div class="network-status">
                    <span><i class="fas fa-globe text-warning"></i> Ping Google</span>
                    <strong class="${network.ping_google === 'FAIL' ? 'text-danger' : 'text-success'}">${network.ping_google}</strong>
                </div>
                <hr>
                <h6>Stato Porte</h6>
                <div class="network-status">
                    <span><i class="fas fa-database"></i> MySQL (3306)</span>
                    <span class="${network.ports.mysql === 'OPEN' ? 'text-success' : 'text-danger'}">${network.ports.mysql}</span>
                </div>
                <div class="network-status">
                    <span><i class="fas fa-server"></i> Apache (80)</span>
                    <span class="${network.ports.apache === 'OPEN' ? 'text-success' : 'text-danger'}">${network.ports.apache}</span>
                </div>
                <div class="network-status">
                    <span><i class="fas fa-lock"></i> HTTPS (443)</span>
                    <span class="${network.ports.https === 'OPEN' ? 'text-success' : 'text-danger'}">${network.ports.https}</span>
                </div>
            `;
            document.getElementById('network-content').innerHTML = html;
        }

        function controlService(service, action) {
            const button = event.target.closest('button');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + action.charAt(0).toUpperCase() + action.slice(1);
            
            fetch('devtools.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax_action=service_control&service=${service}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-' + (action === 'start' ? 'play' : action === 'stop' ? 'stop' : 'redo') + '"></i> ' + action.charAt(0).toUpperCase() + action.slice(1);
                
                if (data.success) {
                    alert('Comando eseguito con successo: ' + data.output);
                } else {
                    alert('Errore: ' + data.error);
                }
            });
        }

        function executeCustomQuery() {
            const sql = document.getElementById('sqlQuery').value.trim();
            if (!sql) {
                alert('Inserisci una query SQL');
                return;
            }
            
            fetch('devtools.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax_action=execute_query&sql=${encodeURIComponent(sql)}`
            })
            .then(response => response.json())
            .then(data => {
                displayQueryResults(data);
            });
        }

        function quickQuery(sql) {
            document.getElementById('sqlQuery').value = sql;
            executeCustomQuery();
        }

        function clearQuery() {
            document.getElementById('sqlQuery').value = '';
            document.getElementById('query-results').innerHTML = '';
        }

        function displayQueryResults(data) {
            let html = '';
            
            if (data.success) {
                if (data.type === 'select' && data.data.length > 0) {
                    html = '<h6>Risultati Query (' + data.data.length + ' righe)</h6>';
                    html += '<div class="table-responsive"><table class="table table-striped table-sm">';
                    html += '<thead class="table-dark"><tr>';
                    
                    // Headers
                    Object.keys(data.data[0]).forEach(key => {
                        html += '<th>' + key + '</th>';
                    });
                    html += '</tr></thead><tbody>';
                    
                    // Rows
                    data.data.forEach(row => {
                        html += '<tr>';
                        Object.values(row).forEach(value => {
                            html += '<td>' + (value || '') + '</td>';
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                } else if (data.type === 'modify') {
                    html = '<div class="alert alert-success">Query eseguita con successo. Righe interessate: ' + data.affected_rows + '</div>';
                } else {
                    html = '<div class="alert alert-info">Query eseguita con successo. Nessun risultato da visualizzare.</div>';
                }
            } else {
                html = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
            }
            
            document.getElementById('query-results').innerHTML = html;
        }

        function performCleanup(type) {
            if (!confirm('Sei sicuro di voler procedere con questa operazione di pulizia?')) {
                return;
            }
            
            fetch('devtools.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax_action=cleanup_action&cleanup_type=${type}`
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.success) {
                    html = '<div class="alert alert-success">' + data.message + '</div>';
                } else {
                    html = '<div class="alert alert-danger">Errore: ' + data.error + '</div>';
                }
                document.getElementById('cleanup-results').innerHTML = html;
            });
        }

        function refreshAllSections() {
            loadStats();
            loadResources();
            loadNetwork();
        }
    </script>
</body>
</html><style>
.devtools-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.section h3 {
    margin-top: 0;
    color: #495057;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 10px;
    margin-bottom: 10px;
}

.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-danger { background: #dc3545; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn-info { background: #17a2b8; color: white; }
.btn-secondary { background: #6c757d; color: white; }

.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.service-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.service-card h4 {
    margin: 0 0 15px 0;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 10px;
}

.service-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
}

.service-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.service-controls .btn {
    flex: 1;
    min-width: 80px;
    padding: 6px 12px;
    font-size: 12px;
}

.status-active { color: #28a745; }
.status-inactive { color: #dc3545; }

.terminal-output {
    background: #1a1a1a;
    color: #00fd00;
    font-family: 'Courier New', monospace;
    padding: 15px;
    border-radius: 6px;
    min-height: 100px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #333;
}

.table-responsive {
    overflow-x: auto;
    margin-top: 15px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.table th, .table td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #dee2e6;
}

.table th {
    background: #e9ecef;
    font-weight: bold;
}

.table tr:nth-child(even) {
    background: #f8f9fa;
}

.alert {
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.info-box {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.code-block {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    font-family: 'Courier New', monospace;
    margin: 10px 0;
}
</style>

<div class="devtools-container">
    <h2>🔧 DevTools – Console di Sviluppo</h2>

    <?php if ($messaggio): ?>
        <div class="alert <?= strpos($messaggio, 'color: green') !== false ? 'alert-success' : 'alert-error' ?>">
            <?= strip_tags($messaggio) ?>
        </div>
    <?php endif; ?>

    <!-- Sezione SQL Console -->
    <div class="section">
        <h3>💻 SQL Console</h3>
        <p>Esegui query SQL personalizzate direttamente sul database.</p>
        
        <form method="post">
            <div class="form-group">
                <label for="sql">Query SQL:</label>
                <textarea name="sql" id="sql" rows="6" class="form-control" placeholder="Scrivi una query SQL (es: SELECT * FROM utenti LIMIT 10)..." required><?= htmlspecialchars($_POST['sql'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">▶️ Esegui Query</button>
        </form>

        <?php if ($campi): ?>
            <h4>Risultato SELECT</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php foreach ($campi as $c): ?><th><?= htmlspecialchars($c) ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($risultati as $r): ?>
                            <tr>
                                <?php foreach ($campi as $c): ?><td><?= htmlspecialchars($r[$c] ?? '') ?></td><?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sezione Visualizzazione Tabelle -->
    <div class="section">
        <h3>📊 Visualizzazione Tabelle Database</h3>
        <p>Esplora i dati contenuti nelle tabelle del database.</p>
        
        <form method="get">
            <div class="form-group">
                <label for="table">Seleziona tabella:</label>
                <select name="table" id="table" class="form-control" onchange="this.form.submit()" style="width: 300px;">
                    <option value="">-- Scegli una tabella --</option>
                    <?php foreach ($tabelle as $t): ?>
                        <option value="<?= $t ?>" <?= $t === $tabella_selezionata ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (!empty($campi_tabella)): ?>
            <h4>Contenuto tabella: <strong><?= $tabella_selezionata ?></strong></h4>
            <p><em>Mostra i primi 100 record</em></p>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php foreach ($campi_tabella as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tabella_dati)): ?>
                            <?php foreach ($tabella_dati as $row): ?>
                                <tr>
                                    <?php foreach ($campi_tabella as $col): ?><td><?= htmlspecialchars($row[$col] ?? '') ?></td><?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= count($campi_tabella) ?>" style="text-align:center;">Nessun dato presente nella tabella.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sezione Gestione Servizi -->
    <div class="section">
        <h3>🖥️ Gestione Servizi Sistema</h3>
        <p>Controlla e gestisci tutti i servizi del sistema in modo organizzato.</p>
        
        <div class="service-grid">
            <!-- Apache2 Web Server -->
            <div class="service-card">
                <h4>🌐 Apache2 Web Server</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active apache2 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl apache2</small>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="apache_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                </div>
            </div>

            <!-- MySQL Database -->
            <div class="service-card">
                <h4>🗄️ MySQL Database</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active mysql 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl mysql</small>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="mysql_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                </div>
            </div>

            <!-- Node.js Socket Service -->
            <div class="service-card">
                <h4>⚡ Node.js Socket</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active node-socket 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl node-socket</small>
                </div>
                <div style="font-size: 11px; color: #6c757d; margin-bottom: 10px;">
                    <strong>Dipendenze:</strong> Apache2 + MySQL<br>
                    <em>Avvio ritardato di 5 secondi</em>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="node_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                </div>
            </div>

            <!-- LocalTunnel Service -->
            <div class="service-card">
                <h4>🚀 LocalTunnel</h4>
                <?php
                $status = trim(shell_exec("systemctl is-active localtunnel 2>&1"));
                $status_class = ($status === "active") ? "status-active" : "status-inactive";
                $status_text = ($status === "active") ? "🟢 Attivo" : "🔴 Inattivo";
                ?>
                <div class="service-status">
                    <span><?= $status_text ?></span>
                    <small>systemctl localtunnel</small>
                </div>
                <div class="service-controls">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_start">
                        <button type="submit" class="btn btn-success">▶️ Start</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_stop">
                        <button type="submit" class="btn btn-danger">⏹️ Stop</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_restart">
                        <button type="submit" class="btn btn-warning">🔄 Restart</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_status">
                        <button type="submit" class="btn btn-info">📊 Status</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="service_action" value="lt_logs">
                        <button type="submit" class="btn btn-secondary">📋 Logs</button>
                    </form>
                </div>
                <?php if ($status === "active"): ?>
                    <div style="margin-top: 10px; padding: 8px; background: #d4edda; border-radius: 4px; font-size: 12px;">
                        <strong>🌐 URL:</strong> <a href="https://ascontabilemente.loca.lt" target="_blank">https://ascontabilemente.loca.lt</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h4>🛠️ Installazione LocalTunnel Service</h4>
        <div class="info-box">
            <p><strong>Prima installazione:</strong> Se LocalTunnel non è ancora configurato come servizio, esegui:</p>
            <div class="code-block">
                chmod +x install_localtunnel.sh<br>
                sudo ./install_localtunnel.sh
            </div>
            <p>Questo script installerà Node.js, LocalTunnel e configurerà il servizio per l'avvio automatico.</p>
        </div>

        <h4>Output Operazioni</h4>
        <div class="terminal-output" id="service_output">
            <?= nl2br(htmlspecialchars($service_output)) ?>
        </div>
    </div>

    <!-- Sezione Accesso Remoto -->
    <div class="section">
        <h3>🌐 Accesso Remoto e SSH</h3>
        <p>Informazioni per accedere al server da remoto.</p>
        
        <div class="info-box">
            <h4>Connessione SSH</h4>
            <div class="code-block">
                ssh admin@<?= htmlspecialchars($_SERVER['SERVER_ADDR'] ?? '192.168.1.29') ?>
            </div>
            <p><strong>Password:</strong> admin</p>
        </div>

        <div class="info-box">
            <h4>Client SSH Consigliati</h4>
            <ul>
                <li><a href="https://mobaxterm.mobatek.net/" target="_blank">MobaXterm</a> - Client SSH completo per Windows</li>
                <li><a href="https://www.putty.org/" target="_blank">PuTTY</a> - Client SSH leggero</li>
                <li><strong>PowerShell</strong> - Prompt integrato di Windows 10+</li>
            </ul>
        </div>
    </div>
</div>

<script>
function pollServiceLog() {
    fetch('service_log.txt?rnd=' + Math.random())
        .then(response => response.text())
        .then(log => {
            const output = document.getElementById('service_output');
            if (log.trim()) {
                output.innerHTML = log.replace(/\n/g, '<br>');
                output.scrollTop = output.scrollHeight;
            }
        })
        .catch(err => console.log('Log polling error:', err));
}

// Poll service log every 3 seconds
setInterval(pollServiceLog, 3000);

// Auto-expand textarea based on content
document.getElementById('sql').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 200) + 'px';
});
</script>

</main>
</body>
</html>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
