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
<title>L’IA a-t-elle changé le métier de développeur ?</title>

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
.header-auth span{
margin-left:15px;
padding:8px 15px;
border-radius:20px;
border:1px solid var(--rose);
color:var(--rose);
cursor:pointer;
transition:0.3s;
}
.header-auth span:hover{background:var(--rose);color:white;}

/* HERO */
.hero{
text-align:center;
padding:40px 20px;
}

.hero h1{
color:var(--rose);
font-size:32px;
margin-bottom:10px;
}

.hero p{
color:#666;
font-size:14px;
margin-bottom:20px;
}

.hero img{
width:100%;
max-width:700px;
height:300px;
object-fit:cover;
border-radius:15px;
box-shadow:0 10px 25px var(--shadow);
}

/* CONTENU */
.container{
max-width:900px;
margin:30px auto;
background:white;
padding:30px;
border-radius:15px;
box-shadow:0 5px 20px var(--shadow);
}

.container h2{
color:var(--rose);
margin:25px 0 10px;
}

/* HERO */
.hero{
text-align:center;
padding:40px 20px;
}

.hero h1{
color:var(--rose);
font-size:32px;
margin-bottom:10px;
}

.hero p{
color:#666;
font-size:14px;
margin-bottom:20px;
}

.hero img{
width:100%;
max-width:700px;
height:300px;
object-fit:cover;
border-radius:15px;
box-shadow:0 10px 25px var(--shadow);
}

/* CONTENU */
.container{
max-width:900px;
margin:30px auto;
background:white;
padding:30px;
border-radius:15px;
box-shadow:0 5px 20px var(--shadow);
}

.container h2{
color:var(--rose);
margin:25px 0 10px;
}

.container p{
margin-bottom:12px;
font-size:14px;
line-height:1.7;
}

.container ul{
margin-left:20px;
margin-bottom:15px;
}

.container li{
margin-bottom:8px;
font-size:14px;
}

/* CTA */
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

/* FOOTER */
footer{
margin-top:40px;
padding:30px;
text-align:center;
background:white;
box-shadow:0 -5px 20px var(--shadow);
border-radius:15px 15px 0 0;
color:#777;
font-size:14px;
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

<div class="header-auth">
<span onclick="openModal('login')">Connexion</span>
<span onclick="openModal('signup')">Inscription</span>
</div>
</header>

<!-- HERO -->
<section class="hero">
<h1>L’IA a-t-elle changé le métier de développeur ?</h1>
<p>Comprendre comment l’intelligence artificielle transforme le développement</p>
<img src="carte6.webp">
</section>

<!-- CONTENU COMPLET -->
<div class="container">

<p>Faut-il encore apprendre à coder quand l’IA écrit du code ? C’est une question que se posent de nombreux étudiants...</p>

<h2>L’IA : un coéquipier puissant</h2>
<p>Au départ, l’IA servait surtout à gagner du temps. Aujourd’hui, elle devient un véritable partenaire capable d’analyser, corriger et proposer des solutions complètes.</p>

<h2>Du développeur exécutant au développeur stratégique</h2>
<ul>
<li>Donner des instructions claires à l’IA</li>
<li>Automatiser certaines tâches</li>
<li>Vérifier et valider les résultats</li>
</ul>

<p>Le rôle du développeur évolue vers plus de réflexion, de contrôle et de stratégie.</p>

<h2>Des méthodes de travail transformées</h2>
<p>L’IA change aussi le travail en équipe et les outils utilisés :</p>
<ul>
<li>Compréhension du besoin métier</li>
<li>Architecture logicielle</li>
<li>Sécurité et performance</li>
<li>Capacité d’analyse</li>
</ul>

<h2>Une opportunité pour les étudiants</h2>
<p>Les développeurs juniors peuvent progresser plus vite en utilisant ces outils intelligemment.</p>

<h2>Le métier va-t-il disparaître ?</h2>
<p>Non. Même si une grande partie du code sera générée, l’humain reste indispensable pour décider, concevoir et valider.</p>

<p><strong>Conclusion :</strong> apprendre à coder reste essentiel, mais savoir utiliser l’IA devient une compétence clé.</p>

</div>



<!-- MODAL LOGIN -->
<div class="modal-bg" id="modal-login">
<div class="modal">
<span class="close" onclick="closeModal('login')">&times;</span>
<h2>Connexion</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Mot de passe" required>
<button name="login">Se connecter</button>
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
<input type="password" name="confirm_password" placeholder="Confirmer mot de passe" required>
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