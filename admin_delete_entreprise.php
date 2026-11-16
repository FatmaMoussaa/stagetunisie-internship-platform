<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Vérifie que l'ID est passé
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Aucune entreprise sélectionnée.";
    header("Location: admin_entreprises.php");
    exit;
}

$id_entreprise = $_GET['id'];

// Supprime l'entreprise
$delete = pg_query_params($conn, "DELETE FROM entreprises WHERE id_entreprise=$1", array($id_entreprise));

if ($delete) {
    $_SESSION['success'] = "Entreprise supprimée avec succès.";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression.";
}

header("Location: admin_liste_entreprise.php");
exit;
?>