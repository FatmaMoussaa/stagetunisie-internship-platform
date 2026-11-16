<?php
session_start();
require_once 'config.php'; 

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", array($email));
    if (pg_num_rows($result) === 1) {
        $user = pg_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'etudiant') {
                header("Location: etudiant.php");
            } else if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Utilisateur non trouvé.";
    }
}

$admin_nom = $_SESSION['nom'] ?? '';
?>
