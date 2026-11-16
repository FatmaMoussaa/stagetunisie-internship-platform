<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role']!=='etudiant') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$id_entreprise = $_POST['id_entreprise'] ?? null;
$note = $_POST['note'] ?? null;
$commentaire = trim($_POST['commentaire'] ?? '');

if($id_entreprise && $note && $commentaire){
    $res = pg_query_params($conn, "INSERT INTO avis(id_user,id_entreprise,note,commentaire) VALUES($1,$2,$3,$4)",
        array($id_user,$id_entreprise,$note,$commentaire)
    );
    if($res){
        $_SESSION['success_avis'] = "Avis envoyé avec succès !";
    } else {
        $_SESSION['error_avis'] = "Erreur lors de l'envoi de l'avis.";
    }
} else {
    $_SESSION['error_avis'] = "Veuillez remplir tous les champs.";
}

header("Location: etudiant.php");
exit;
?>