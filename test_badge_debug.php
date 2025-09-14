<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'profilo.php';

// Debug session e user_id
echo "<h2>Debug Badge Status</h2>";
echo "<p>Session User ID: " . ($_SESSION['user_id'] ?? 'NON DEFINITO') . "</p>";

// Test API call
$api_url = 'http://localhost/api/chat_notifications.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
echo "<p>API Response: $response</p>";

// Test diretto database
$conn = new mysqli("localhost", "root", "", "dbcrm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? 0;
$query = "SELECT * FROM user_conversation_status WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Database Status for User $user_id:</h3>";
echo "<table border='1'>";
echo "<tr><th>Conversation ID</th><th>Last Read</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row['conversation_id'] . "</td><td>" . $row['last_read_timestamp'] . "</td></tr>";
}
echo "</table>";

$stmt->close();
$conn->close();
?>

<!-- Widget di test -->
<div id="total-unread-badge" style="background: red; color: white; padding: 5px; margin: 10px;">0</div>

<script>
// Test immediate API call
console.log('🔍 Test diretto API badge...');
fetch('./api/chat_notifications.php', {
    method: 'GET',
    credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
    console.log('📊 Risposta API completa:', data);
    const badge = document.getElementById('total-unread-badge');
    badge.textContent = data.total || 0;
    badge.style.display = (data.total > 0) ? 'block' : 'none';
})
.catch(error => {
    console.error('❌ Errore test API:', error);
});
</script>
