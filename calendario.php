<?php
// File: calendario.php

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';

include __DIR__ . '/includes/header.php';
?>

<h2>Calendario Appuntamenti</h2>
<iframe src="https://calendar.google.com/calendar/embed?src=your_calendar_id" style="border: 0" width="100%" height="600" frameborder="0" scrolling="no"></iframe>

</main>
</body>
</html>
