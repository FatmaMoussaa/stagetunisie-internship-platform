<?php
session_start();
require_once 'config.php';
// ==================== LOGIN ====================
if(isset($_POST['login'])){
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $result = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", array($email));
  if(pg_num_rows($result) === 1){
      $user = pg_fetch_assoc($result);
      if(password_verify($password, $user['password'])){
          $_SESSION['user_id'] = $user['id_user'];
          $_SESSION['nom'] = $user['nom'];
          $_SESSION['role'] = $user['role'];

          // REDIRECTION SELON ROLE
          if($user['role'] === 'etudiant'){
              header("Location: etudiant.php");
          } elseif($user['role'] === 'admin'){
              header("Location: admin.php");
          } else {
              header("Location: index.php");
          }
          exit;
      } else {
          $error_login = "Mot de passe incorrect.";
      }
  } else {
      $error_login = "Utilisateur non trouvé.";
  }
}

// ==================== INSCRIPTION ====================
if(isset($_POST['signup'])){
  $nom = trim($_POST['nom']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  if($password !== $confirm_password){
      $error_signup = "Les mots de passe ne correspondent pas.";
  } else {
      // Vérifier si email existe
      $check = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", array($email));
      if(pg_num_rows($check) > 0){
          $error_signup = "Cet email est déjà utilisé.";
      } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $insert = pg_query_params($conn, 
              "INSERT INTO utilisateurs (nom,email,password,role) VALUES ($1,$2,$3,'etudiant')",
              array($nom,$email,$hash)
          );

          if($insert){
              $_SESSION['success_signup'] = "Compte créé avec succès, veuillez vous connecter.";
              header("Location: index.php");
              exit;
          } else {
              $error_signup = "Erreur lors de l'inscription.";
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
<title>Pourquoi le tutorat est un atout - StageTunisie</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root{
--rose:#c34271;
--rose-clair:#f070a1;
--vert:#16ffbd;
--vert-fonce:#12c998;
--bg:#f8fafc;
--text:#2d3e50;
--shadow:rgba(0,0,0,0.08);
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);}

/* HEADER */
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
.auth-label{
padding:8px 15px;
border-radius:20px;
border:1px solid var(--rose);
color:var(--rose);
cursor:pointer;
}
.auth-label:hover{background:var(--rose);color:white;}

/* HERO */
.hero{
text-align:center;
padding:50px 20px 20px;
}
.hero h1{
font-size:30px;
color:var(--rose);
margin-bottom:10px;
}
.hero p{
color:#666;
font-size:14px;
}

.image-container{
display:flex;
justify-content:center;
margin:25px 0;
}
.image-container img{
width:650px;
height:360px;
border-radius:12px;
object-fit:cover;
box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

/* ARTICLE */
.article{
max-width:850px;
margin:30px auto;
background:white;
padding:35px;
border-radius:15px;
box-shadow:0 5px 20px var(--shadow);
}
.article h2{
color:var(--rose);
margin:25px 0 10px;
font-size:20px;
}
.article h3{
color:var(--rose-clair);
margin:15px 0 8px;
font-size:16px;
}
.article p{
font-size:14px;
color:#555;
margin-bottom:12px;
line-height:1.6;
}

/* CTA */
.cta{
margin:50px auto;
max-width:850px;
background:linear-gradient(135deg,var(--rose),var(--rose-clair));
padding:30px;
text-align:center;
border-radius:15px;
color:white;
}
.cta a{
display:inline-block;
margin-top:15px;
background:white;
color:var(--rose);
padding:10px 20px;
border-radius:25px;
text-decoration:none;
font-weight:600;
}

/* MODAL */
.modal-bg{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,0.6);
justify-content:center;
align-items:center;
z-index:999;
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
border-radius:10px;
border:1px solid #ddd;
margin-bottom:10px;
}

.modal button{
width:100%;
padding:12px;
border:none;
border-radius:30px;
background:linear-gradient(135deg,var(--rose),var(--rose-clair));
color:white;
font-weight:600;
cursor:pointer;
}

.close{
position:absolute;
top:10px;
right:15px;
font-size:22px;
font-weight:bold;
color:#888;
cursor:pointer;
}
.close:hover{color:black;}
.copyright{text-align:center;}
</style>
</head>

<body>

<!-- HEADER -->
<header class="header">
<div class="logo"><a href="#">Stage<span>Tunisie</span></a></div>

<nav class="nav">
<a href="index.php">Accueil</a>
<a href="nosentreprises.php">Entreprises</a>
<a href="nosoffres.php">Offres</a>
<a href="blog.php" class="active">Blog</a>
<a href="ai.php">Coach AI</a>
</nav>

<div>
<span class="auth-label" onclick="openModal('login')">Connexion</span>
<span class="auth-label" onclick="openModal('signup')">Inscription</span>
</div>
</header>

<!-- HERO -->
<section class="hero">
<h1>Pourquoi le tutorat est un atout</h1>
<p>Un levier puissant pour valoriser votre profil étudiant.</p>

<div class="image-container">
<img src="carte3.jpg">
</div>
</section>

<!-- CONTENU COMPLET -->
<div class="article">

<p>À l’heure où les recruteurs scrutent autant les compétences que les expériences concrètes, les parcours linéaires ne suffisent plus toujours à se démarquer...</p>

<h2>Une expérience professionnelle qui a du sens</h2>
<p>Donner des cours particuliers suppose de s’engager auprès d’un élève...</p>

<h2>Des compétences transversales</h2>
<p>Le tutorat développe des soft skills essentielles...</p>

<h2>Une activité flexible</h2>
<p>Compatible avec les études et projets professionnels...</p>

<h2>Renforcer sa méthode</h2>
<p>Expliquer aide à mieux comprendre soi-même...</p>

<h2>Plateformes structurées</h2>
<p>Exemple : Ikando accompagne des milliers d’élèves...</p>

<h2>Résultats mesurables</h2>
<p>Progression, confiance, efficacité...</p>

<h2>Insertion professionnelle</h2>
<p>Gestion de projet, méthode, organisation...</p>

<h2>Conclusion</h2>
<p>Le tutorat est une vraie valeur ajoutée sur un CV.</p>

</div>



<!-- MODALS -->
<div class="modal-bg" id="modal-login">
<div class="modal">
<span class="close" onclick="closeModal('login')">&times;</span>
<h2>Connexion</h2>
<form method="POST">
<input type="email" name="email" required>
<input type="password" name="password" required>
<button name="login">Se connecter</button>
</form>
</div>
</div>

<div class="modal-bg" id="modal-signup">
<div class="modal">
<span class="close" onclick="closeModal('signup')">&times;</span>
<h2>Inscription</h2>
<form method="POST">
<input type="text" name="nom" required>
<input type="email" name="email" required>
<input type="password" name="password" required>
<input type="password" name="confirm_password" required>
<button name="signup">S'inscrire</button>
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
window.onclick=function(e){
if(e.target.classList.contains('modal-bg')){
e.target.style.display='none';
}
}
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>