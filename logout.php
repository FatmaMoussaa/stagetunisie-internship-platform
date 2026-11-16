<?php
session_start();

// Vider les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers login
header("Location: index.php");
exit;