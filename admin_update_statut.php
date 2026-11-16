<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role']!=='admin'){
    header("Location: login.php");
    exit;
}

if(isset($_GET['id'], $_GET['action'])){
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if(!in_array($action, ['accepte','refuse'])){
        die('Action invalide');
    }

    $res = pg_query_params($conn, "UPDATE candidatures SET statut=$1 WHERE id_candidature=$2", [$action, $id]);

    if($res){
        header("Location: admin_condidatures.php");
        exit;
    } else {
        echo "Erreur lors de la mise à jour.";
    }
}