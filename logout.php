<?php
session_start();
session_destroy();
header('Location: /fleur-c-print/login.php'); // ← ADD /fleur-c-print/
exit;
?>