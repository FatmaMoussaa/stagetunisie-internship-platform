<?php
session_start();
require_once 'config.php';
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
            if($user['role'] === 'etudiant') header("Location: etudiant.php");
            elseif($user['role'] === 'admin') header("Location: admin.php");
            else header("Location: index.php");
            exit;
        } else $error_login = "Mot de passe incorrect.";
    } else $error_login = "Utilisateur non trouvé.";
}
if(isset($_POST['signup'])){
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password){
        $error_signup = "Les mots de passe ne correspondent pas.";
    } else {
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
            } else $error_signup = "Erreur lors de l'inscription.";
        }
    }
}
$articles = [
    ['id'=>1,'titre'=>'Candidature de stage : CV, mail et présence en ligne pour convaincre les recruteurs','date'=>'20 mars, 2026','description'=>"Trouver un stage repose aujourd’hui sur une candidature cohérente et structurée.",'image'=>'carte1.jpg','lien'=>'conseil1.php'],
    ['id'=>2,'titre'=>'Pourquoi le tutorat est un atout sur un CV étudiant ou jeune diplômé','date'=>'21 mars, 2026','description'=>"À l’heure où les recruteurs scrutent autant les compétences que les expériences.",'image'=>'carte2.jpg','lien'=>'conseil2.php'],
    ['id'=>3,'titre'=>'Community manager ou social media manager : quel métier choisir en stage ou alternance ?','date'=>'21 mars, 2026','description'=>"Découvrez les missions et compétences clés pour devenir un community manager efficace.",'image'=>'carte3.avif','lien'=>'conseil3.php'],
    ['id'=>4,'titre'=>'L’IA a-t-elle changé le métier de développeur ?','date'=>'22 mars, 2026','description'=>"Faut-il encore apprendre à coder quand l’IA fait partie du quotidien du développeur ?",'image'=>'carte4.webp','lien'=>'conseil4.php'],
    ['id'=>5,'titre'=>'Quelles informations personnelles inclure sur un cv?','date'=>'22 mars, 2026','description'=>"Un bon CV doit contenir uniquement des renseignements qui permettent une identification rapide et un contact facile.",'image'=>'carte5.jpg','lien'=>'conseil5.php'],
    ['id'=>6,'titre'=>'Les secrets d un cv parfait','date'=>'22 mars, 2026','description'=>"Rédiger un CV peut sembler une tâche ardue, surtout pour les lycéens qui n'ont pas encore beaucoup d'expérience.",'image'=>'carte6.avif','lien'=>'conseil6.php'],
    ['id'=>7,'titre'=>'La Recherche d un stage à l étranger','date'=>'22 mars, 2026','description'=>"Il faut savoir que vous pouvez faire votre stage dans le pays de votre choix et la préparation est essentielle.",'image'=>'carte8.webp','lien'=>'conseil7.php'],
    ['id'=>8,'titre'=>'Préparer son entretien d embauche','date'=>'22 mars, 2026','description'=>"Dans le processus de recherche d'emploi, de stage ou d'alternance on se trouve souvent face à de nombreux entretiens.",'image'=>'carte9.avif','lien'=>'conseil8.php'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:100; }
.modal { background:white; padding:40px; border-radius:20px; width:400px; max-width:90%; box-shadow:0 20px 50px var(--shadow); position:relative; }
.modal h2 { text-align:center; color: var(--rose); margin-bottom:25px; }
.modal input { width:100%; padding:12px 15px; border-radius:12px; border:1px solid #ddd; margin-bottom:15px; outline:none; transition:0.2s; }
.modal input:focus { border-color: var(--rose); box-shadow:0 0 10px rgba(195,66,113,0.2); }
.modal button { width:100%; padding:14px; border:none; border-radius:30px; background: linear-gradient(135deg,var(--rose),var(--rose-clair)); color:white; font-weight:600; cursor:pointer; transition:0.3s; }
.modal button:hover { transform:translateY(-2px); box-shadow:0 12px 25px rgba(0,0,0,0.15); }
.modal .close { position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px; color:#999; transition:0.3s; }
.modal .close:hover { color:#333; }
:root{
  --rose:#c34271;
  --rose-clair:#f070a1;
  --vert:#16ffbd;
  --vert-fonce:#12c998;
  --bg:#f8fafc;
  --blanc:#fff;
  --text:#2d3e50;
  --text-gris:#5a6b7a;
  --shadow:rgba(0,0,0,0.1);
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
body{background:var(--bg);color:var(--text);}

.header { background:white; padding:20px 40px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 5px 20px var(--shadow); border-radius:15px 15px 0 0; }
.logo a { font-size:28px; font-weight:700; color: var(--vert); text-decoration:none; }
.logo span { color: var(--rose); }
.nav a { margin:0 15px; text-decoration:none; font-weight:500; color:#555; }
.nav a.active, .nav a:hover { color: var(--rose); }
.header-auth a { margin-left:15px; text-decoration:none; font-weight:500; }
.auth-label { padding:8px 15px; border-radius:20px; border:1px solid var(--rose); color: var(--rose); cursor:pointer; transition:0.3s; }
.auth-label:hover { background: var(--rose); color:white; }

.nav a{ margin:0 15px; text-decoration:none; color:#555; font-weight:500;}
.nav a.active,.nav a:hover{ color: var(--rose);}
.auth-label{padding:8px 15px;border-radius:20px;border:1px solid var(--rose);color:var(--rose);cursor:pointer;transition:0.3s;}
.auth-label:hover{background:var(--rose);color:white;}

.header-spacer{height:100px;}
.page-title{text-align:center;margin-bottom:20px;}
.page-title h1{font-size:28px;color:var(--rose);}
.page-title p{color:#555;font-size:14px;}

.cards{
  display:grid;
  grid-template-columns:repeat(auto-fill,170px);
  justify-content:center;
  gap:18px;
  margin:30px auto 60px auto;
  max-width:1100px;
}
.card{
  background:white;
  border-radius:12px;
  padding:12px;
  box-shadow:0 5px 15px var(--shadow);
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  text-align:center;
  transition:0.25s;
}
.card:hover{transform:translateY(-4px);}
.card img{width:55px;height:55px;object-fit:cover;margin-bottom:8px;border-bottom:2px solid var(--rose);padding-bottom:5px;}
.card h4{font-size:13px;color:var(--rose-clair);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;}
.card p{font-size:11px;color:#555;}
.card a.details{font-size:11px;text-decoration:none;color:#d4a017;margin-top:5px;font-weight:600;}


.card:hover{
  transform:translateY(-5px);
  box-shadow:0 10px 20px var(--shadow);
}

.card img{
  width:55px;
  height:55px;
  object-fit:cover;
  margin-bottom:10px;
  border-bottom:2px solid var(--rose);
  padding-bottom:5px;
  border-radius:8px;
}

.card h4{
  font-size:13px;
  color:var(--rose-clair);
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  width:100%;
}

.card p{
  font-size:11px;
  color:#555;
  margin:5px 0;
}

.card a.details{
  font-size:11px;
  text-decoration:none;
  color:#d4a017;
  margin-top:5px;
  font-weight:600;
  transition:0.3s;
}
.card a.details:hover{
  color:var(--rose);
}
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
    <span class="auth-label" onclick="openModal('login')">Connexion</span>
    <span class="auth-label" onclick="openModal('signup')">Inscription</span>
  </div>
</header>
<div class="header-spacer"></div>

<div class="modal-bg" id="modal-login">
  <div class="modal">
    <span class="close" onclick="closeModal('login')">&times;</span>
    <h2>Connexion</h2>
    <?php if(isset($error_login)) echo '<div class="error-msg">'.$error_login.'</div>'; ?>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Mot de passe" required>
      <button type="submit" name="login">Se connecter</button>
    </form>
  </div>
</div>
<div class="modal-bg" id="modal-signup">
  <div class="modal">
    <span class="close" onclick="closeModal('signup')">&times;</span>
    <h2>Inscription</h2>
    <?php 
    if(isset($error_signup)) echo '<div class="error-msg">'.$error_signup.'</div>';
    if(isset($_SESSION['success_signup'])) { echo '<div class="success-msg">'.$_SESSION['success_signup'].'</div>'; unset($_SESSION['success_signup']); }
    ?>
    <form method="POST">
      <input type="text" name="nom" placeholder="Nom complet" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Mot de passe" required>
      <input type="password" name="confirm_password" placeholder="Confirmer mot de passe" required>
      <button type="submit" name="signup">S'inscrire</button>
    </form>
  </div>
</div>
<div class="page-title">
  <h1>Nos conseils</h1>
  <p>Découvrez nos articles pour améliorer vos candidatures et réussir vos stages et alternances.</p>
</div>

<div class="cards">
<?php foreach($articles as $art): ?>
  <div class="card">
    <img src="<?= htmlspecialchars($art['image']) ?>" alt="<?= htmlspecialchars($art['titre']) ?>">
    <h4><?= htmlspecialchars($art['titre']) ?></h4>
    <p><?= htmlspecialchars($art['description']) ?></p>
    <a class="details" href="<?= htmlspecialchars($art['lien']) ?>">Lire plus</a>
  </div>
<?php endforeach; ?>
</div>

<script>
function openModal(type){ document.getElementById('modal-'+type).style.display='flex'; }
function closeModal(type){ document.getElementById('modal-'+type).style.display='none'; }
window.onclick=function(e){ if(e.target.classList.contains('modal-bg')) e.target.style.display='none'; }
</script>

<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>