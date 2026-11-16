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
<title>Candidature de stage - StageTunisie</title>

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
--white:#fff;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);}

/* HEADER IDENTIQUE */
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
transition:0.3s;
}
.auth-label:hover{background:var(--rose);color:white;}

/* HERO STYLE FIN */
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

/* IMAGE */
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

/* CONTENU FIN */
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

/* TABLE */
.article table{
width:100%;
border-collapse:collapse;
margin:15px 0;
}
.article th, .article td{
border:1px solid #eee;
padding:8px;
font-size:13px;
}
.article th{
background:#fff0f5;
color:var(--rose);
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

/* MODAL (CORRIGÉ X) */
.modal-bg{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,0.6);
justify-content:center;
align-items:center;
z-index:10000;
}
.modal{
background:white;
padding:30px;
border-radius:20px;
width:400px;
max-width:90%;
position:relative;
z-index:10001;
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

/* ❌ BOUTON FIX */
.close{
position:absolute;
top:10px;
right:15px;
font-size:22px;
font-weight:bold;
color:#888;
cursor:pointer;
z-index:2000;
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
<h1>Nos conseils</h1>
<p>Voici un guide complet pour réussir votre candidature de stage.</p>

<div class="image-container">
<img src="carte1.jpg">
</div>
</section>

<!-- CONTENU COMPLET -->
<div class="article">

<p>Trouver un stage repose aujourd’hui sur une candidature cohérente et structurée...</p>

<h2>Le rôle complémentaire du CV, du mail et du profil en ligne</h2>

<table>
<tr><th>Support</th><th>Fonction</th><th>Analyse</th></tr>
<tr><td>CV</td><td>Synthèse</td><td>Compétences</td></tr>
<tr><td>Mail</td><td>Contact</td><td>Motivation</td></tr>
<tr><td>Profil</td><td>Crédibilité</td><td>Cohérence</td></tr>
</table>

<h2>Concevoir un CV efficace</h2>
<p>Un CV doit être clair et structuré...</p>

<h3>Rubriques</h3>
<table>
<tr><th>Rubrique</th><th>Contenu</th></tr>
<tr><td>Formation</td><td>Diplômes</td></tr>
<tr><td>Compétences</td><td>Techniques</td></tr>
<tr><td>Expériences</td><td>Stages</td></tr>
</table>

<h2>Mail professionnel</h2>
<p>Le mail est votre premier contact...</p>

<h2>Présence numérique</h2>
<p>Optimisez LinkedIn et votre image...</p>

<h2>Conclusion</h2>
<p>Une candidature réussie repose sur cohérence et clarté.</p>

</div>


<!-- MODAL LOGIN -->
<div class="modal-bg" id="modal-login">
<div class="modal">
<span class="close" onclick="closeModal('login')">&times;</span>
<h2>Connexion</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Mot de passe" required>
<button type="submit" name="login">Se connecter</button>
</form>
</div>
</div>

<!-- MODAL SIGNUP -->
<div class="modal-bg" id="modal-signup">
<div class="modal">
<span class="close" onclick="closeModal('signup')">&times;</span>
<h2>Inscription</h2>
<form method="POST">
<input type="text" name="nom" placeholder="Nom complet" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Mot de passe" required>
<input type="password" name="confirm_password" placeholder="confirmer mot de passe" required>
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
window.onclick=function(e){
if(e.target.classList.contains('modal-bg')){
e.target.style.display='none';
}
}
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>