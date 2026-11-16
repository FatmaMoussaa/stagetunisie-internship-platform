<?php
session_start();
require_once 'config.php';

// ================= LOGIN =================
if(isset($_POST['login'])){
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $result = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", [$email]);

  if(pg_num_rows($result) === 1){
      $user = pg_fetch_assoc($result);

      if(password_verify($password, $user['password'])){
          $_SESSION['user_id'] = $user['id_user'];
          $_SESSION['nom'] = $user['nom'];
          $_SESSION['role'] = $user['role'];

          if($user['role'] === 'etudiant'){
              header("Location: etudiant.php");
          } elseif($user['role'] === 'admin'){
              header("Location: admin.php");
          } else {
              header("Location: index.php");
          }
          exit;
      } else {
          $error_login = "Mot de passe incorrect";
      }
  } else {
      $error_login = "Utilisateur non trouvé";
  }
}

// ================= SIGNUP =================
if(isset($_POST['signup'])){
  $nom = trim($_POST['nom']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  if($password !== $confirm_password){
      $error_signup = "Les mots de passe ne correspondent pas";
  } else {
      $check = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", [$email]);

      if(pg_num_rows($check) > 0){
          $error_signup = "Email déjà utilisé";
      } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);

          $insert = pg_query_params($conn,
              "INSERT INTO utilisateurs (nom,email,password,role) VALUES ($1,$2,$3,'etudiant')",
              [$nom,$email,$hash]
          );

          if($insert){
              $success_signup = "Compte créé avec succès";
          } else {
              $error_signup = "Erreur inscription";
          }
      }
  }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Les Secrets d'un CV Parfait pour les Lycéens</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
/* === TON STYLE EXACT === */
:root{
--rose:#c34271;
--rose-clair:#f070a1;
--vert:#16ffbd;
--vert-fonce:#12c998;
--bg:#f8fafc;
--text:#2d3e50;
--shadow:rgba(0,0,0,0.08);
--white:#fff;
}

*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);}

.header{
background:white;
padding:20px 40px;
display:flex;
justify-content:space-between;
align-items:center;
box-shadow:0 5px 20px var(--shadow);
border-radius:15px 15px 0 0;
}

.logo a{font-size:28px;font-weight:700;color:var(--vert);text-decoration:none;}
.logo span{color:var(--rose);}
.nav a{margin:0 15px;text-decoration:none;font-weight:500;color:#555;}
.nav a.active,.nav a:hover{color:var(--rose);}

.header-auth span{
margin-left:15px;
padding:8px 15px;
border-radius:20px;
border:1px solid var(--rose);
color:var(--rose);
cursor:pointer;
}

.header-auth span:hover{background:var(--rose);color:white;}

.hero{text-align:center;padding:40px 20px;}
.hero h1{color:var(--rose);font-size:32px;margin-bottom:10px;}
.hero p{color:#666;font-size:14px;margin-bottom:20px;}

.hero img{
width:100%;
max-width:700px;
height:300px;
object-fit:cover;
border-radius:15px;
box-shadow:0 10px 25px var(--shadow);
}

.container{
max-width:900px;
margin:30px auto;
background:white;
padding:30px;
border-radius:15px;
box-shadow:0 5px 20px var(--shadow);
}

.container h2{color:var(--rose);margin:25px 0 10px;}
.container p{margin-bottom:12px;font-size:14px;line-height:1.7;}
.container ul{margin-left:20px;margin-bottom:15px;}
.container li{margin-bottom:8px;font-size:14px;}

.cta{
text-align:center;
margin-top:30px;
padding:30px;
border-radius:15px;
background:linear-gradient(135deg,var(--rose),var(--vert));
color:white;
}

.cta a{
display:inline-block;
margin-top:10px;
padding:10px 20px;
background:white;
color:var(--rose);
border-radius:20px;
text-decoration:none;
font-weight:600;
}

footer{
margin-top:40px;
padding:30px;
text-align:center;
background:white;
box-shadow:0 -5px 20px var(--shadow);
border-radius:15px 15px 0 0;
}


/* MODAL */
.modal-bg{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,0.6);
justify-content:center;
align-items:center;
z-index:100;
}

.modal{
background:white;
padding:30px;
border-radius:20px;
width:400px;
max-width:90%;
position:relative;
}

.modal h2{text-align:center;color:var(--rose);margin-bottom:20px;}

.modal input{
width:100%;
padding:10px;
margin-bottom:10px;
border-radius:10px;
border:1px solid #ddd;
}

.modal button{
width:100%;
padding:10px;
border:none;
border-radius:20px;
background:var(--rose);
color:white;
cursor:pointer;
}

.close{
position:absolute;
top:10px;
right:15px;
font-size:22px;
cursor:pointer;
}

.error{color:red;text-align:center;}
.success{color:green;text-align:center;}
.copyright{text-align:center;}
</style>
</head>

<body>

<header class="header">
<div class="logo"><a href="#">Stage<span>Tunisie</span></a></div>

<nav class="nav">
<a href="index.php">Accueil</a>
<a href="nosentreprises.php">Entreprises</a>
<a href="nosoffres.php">Offres</a>
<a href="blog.php" class="active">Blog</a>
<a href="ai.php">Coach AI</a>
</nav>

<div class="header-auth">
<span onclick="openModal('login')">Connexion</span>
<span onclick="openModal('signup')">Inscription</span>
</div>
</header>

<section class="hero">
<h1>Les Secrets d'un CV Parfait pour les Lycéens</h1>
<p>Lance ta carrière dès le lycée avec un CV gagnant</p>
<img src="carte8.avif">
</section>

<div class="container">

<!-- TON CONTENU COMPLET (inchangé) -->
<p>Rédiger un CV peut sembler une tâche ardue...</p>

<h2>1. L'Importance d'un CV Bien Rédigé</h2>
<p>Un CV est souvent le premier contact...</p>

<h2>2. La Structure d'un CV</h2>
<ul>
<li>Informations personnelles</li>
<li>Objectif professionnel</li>
<li>Formation</li>
<li>Expériences</li>
<li>Compétences</li>
<li>Centres d'intérêt</li>
</ul>

<h2>3. Mettre en Valeur Vos Compétences</h2>
<p>Les compétences sont essentielles...</p>

<h2>4. Expériences</h2>
<p>Inclure projets scolaires...</p>

<h2>5. Qualités</h2>
<p>Mentionnez motivation...</p>

</div>



<!-- LOGIN -->
<div class="modal-bg" id="modal-login">
<div class="modal">
<span class="close" onclick="closeModal('login')">×</span>
<h2>Connexion</h2>
<?php if(isset($error_login)) echo "<p class='error'>$error_login</p>"; ?>
<form method="POST">
<input type="email" name="email" placeholder="email" required>
<input type="password" name="password" placeholder="Mot de passe" required>
<button type="submit" name="login">Se connecter</button>
</form>
</div>
</div>

<!-- SIGNUP -->
<div class="modal-bg" id="modal-signup">
<div class="modal">
<span class="close" onclick="closeModal('signup')">×</span>
<h2>Inscription</h2>
<?php if(isset($error_signup)) echo "<p class='error'>$error_signup</p>"; ?>
<?php if(isset($success_signup)) echo "<p class='success'>$success_signup</p>"; ?>
<form method="POST">
<input type="text" name="nom"  placeholder="Nom Complet" required>
<input type="email" name="email"  placeholder="email" required>
<input type="password" name="password" placeholder="Mot de passe" required>
<input type="password" name="confirm_password"  placeholder="confirmer mot de passe" required>
<button type="submit" name="signup">S'inscrire</button>
</form>
</div>
</div>

<script>
function openModal(type){
document.getElementById('modal-'+type).style.display='flex';
}
function closeModal(type){
document.getElementById('modal-'+type).style.display='none';
}
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>