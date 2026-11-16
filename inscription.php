<?php
session_start();
require_once 'config.php';

if (isset($_POST['submit'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $result = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", array($email));
        if (pg_num_rows($result) > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = pg_query_params($conn, 
                "INSERT INTO utilisateurs (nom, email, password, role) VALUES ($1, $2, $3, $4)", 
                array($nom, $email, $password_hash, 'etudiant')
            );

            if ($insert) {
                $_SESSION['success'] = "Compte créé avec succès. Vous pouvez vous connecter.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Erreur lors de la création du compte.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --rose: #c34271;
  --rose-clair: #f070a1;
  --vert: #16ffbd;
  --vert-fonce: #12c998;
  --bg: #f8fafc;
  --blanc: #ffffff;
  --shadow: rgba(0,0,0,0.12);
  --text: #2d3e50;
}

/* RESET */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
body { background: var(--bg); color: var(--text); display:flex; flex-direction:column; min-height:100vh; }

.header-elégant { background:white; padding:20px 40px; box-shadow:0 5px 20px var(--shadow); display:flex; justify-content:space-between; align-items:center; border-bottom-left-radius:15px; border-bottom-right-radius:15px; }
.logo a { font-size:28px; font-weight:700; color: var(--vert); text-decoration:none; }
.logo span { color: var(--rose); }
.nav-principale a { margin:0 15px; text-decoration:none; color:#555; font-weight:500; }
.nav-principale a.active, .nav-principale a:hover { color: var(--rose); }
.header-auth a { margin-left:15px; text-decoration:none; font-weight:500; }
.auth-label { padding:8px 15px; border-radius:20px; border:1px solid var(--rose); color: var(--rose); }
.auth-label:hover { background: var(--rose); color:white; }

/* CARTE */
.wrapper { flex:1; display:flex; justify-content:center; align-items:center; padding:40px; }
.form-card {
    width:500px;
    background: var(--blanc);
    padding:50px 40px;
    border-radius:20px;
    box-shadow:0 20px 50px var(--shadow);
    display:flex;
    flex-direction:column;
    gap:25px;
}
.form-card h2 { text-align:center; color: var(--rose); margin-bottom:15px; font-size:2rem; }

/* INPUTS */
.form-card input {
    width:100%; padding:15px 18px; border-radius:12px; border:1px solid #ddd; font-size:15px;
    outline:none; transition: all 0.25s; margin-bottom:15px;
}
.form-card input:focus { border-color: var(--rose); box-shadow:0 0 10px rgba(195,66,113,0.2); }

/* BOUTON INSCRIPTION */
.btn {
    width:100%; padding:15px; border:none; border-radius:30px;
    background: linear-gradient(135deg, var(--rose), var(--rose-clair));
    color:white; font-weight:600; cursor:pointer; transition: all 0.3s;
    font-size:15px;
}
.btn:hover { transform:translateY(-2px); box-shadow:0 12px 25px rgba(0,0,0,0.15); }

/* TEXTE DEJA INSCRIT */
.login-text {
    text-align:center; font-size:13px; color:#5a6b7a; margin-top:-5px;
}
.login-text a {
    color: var(--rose); font-weight:500; text-decoration:none; transition:0.3s;
}
.login-text a:hover { color: var(--vert); text-decoration:underline; }

/* BOUTON RETOUR ACCUEIL */
.btn-back {
    width:100%; padding:15px; border:none; border-radius:30px;
    background: var(--vert); color:#fff; font-weight:600; cursor:pointer; text-align:center; transition:0.3s;
    font-size:15px;
    margin-top:10px;
}
.btn-back:hover { background: var(--vert-fonce); transform:translateY(-2px); }

/* MESSAGES */
.message { padding:12px; border-radius:12px; text-align:center; font-weight:500; }
.error { background:#fcdcdc; color:#c34242; }
.success { background:#d4f7dc; color:var(--vert); }

@media(max-width:520px){
    .form-card { width:90%; padding:40px 25px; }
}
</style>
</head>
<body>
<header class="header-elégant">
  <div class="logo"><a href="#">Stage<span>Tunisie</span></a></div>
  <nav class="nav-principale">
    <a href="index.php" class="active">Accueil</a>
    <a href="nosentreprises.php">Entreprises</a>
    <a href="nosoffres.php">Offres</a>
    <a href="blog.php">Blog</a>
  </nav>
  <div class="header-auth">
    <a class="auth-label" href="login.php">Connexion</a>
    <a class="auth-label" href="inscription.php">Inscription</a>
  </div>
</header>

<div class="wrapper">
    <div class="form-card">
        <h2>Inscription</h2>

        <?php if(isset($error)) echo "<div class='message error'>$error</div>"; ?>
        <?php if(isset($_SESSION['success'])) {
            echo "<div class='message success'>".$_SESSION['success']."</div>";
            unset($_SESSION['success']);
        } ?>

        <form method="POST" action="">
            <input type="text" name="nom" placeholder="Nom complet" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <button class="btn" type="submit" name="submit">S'inscrire</button>
        </form>

        <!-- Petit texte sous formulaire -->
        <div class="login-text">
            Déjà inscrit ? <a href="login.php">Se connecter</a>
        </div>

        <!-- Bouton retour accueil -->
        <button class="btn-back" onclick="window.location.href='index.php'">← Retour à l'accueil</button>
    </div>
</div>

</body>
</html>