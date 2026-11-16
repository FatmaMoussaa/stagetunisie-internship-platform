<?php
session_start();
require_once 'config.php';

// Vérifie que l'admin est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Vérifie que l'id existe
if (!isset($_POST['id_stage'])) {
    $_SESSION['error'] = "Offre non spécifiée.";
    header("Location: admin_offres.php");
    exit;
}

$id_stage = $_POST['id_stage'];

// Supprime l'offre
$res = pg_query_params($conn, "DELETE FROM offres_stage WHERE id_stage=$1", array($id_stage));

if ($res) {
    $_SESSION['success'] = "Offre supprimée avec succès.";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression.";
}

header("Location: admin_offres.php");
exit;