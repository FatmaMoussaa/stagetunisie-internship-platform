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
                header("Location: nosoffres.php");
                exit;
            } else {
                $error_signup = "Erreur lors de l'inscription.";
            }
        }
    }
}

// ==================== FILTRAGE OFFRES ====================
$selected_entreprise = $_GET['entreprise'] ?? '';
$selected_titre = $_GET['titre'] ?? '';

$sql = "SELECT o.id_stage, o.titre, o.niveau_etude, o.duree_semaine, o.date_publication,
        e.id_entreprise, e.nom AS entreprise, e.ville, e.logo
        FROM offres_stage o
        JOIN entreprises e ON o.id_entreprise = e.id_entreprise
        WHERE 1=1";
$params = [];
$counter = 1;

if($selected_entreprise !== ''){
    $sql .= " AND e.id_entreprise = $".$counter;
    $params[] = $selected_entreprise;
    $counter++;
}

if($selected_titre !== ''){
    $sql .= " AND o.titre = $".$counter;
    $params[] = $selected_titre;
    $counter++;
}

$sql .= " ORDER BY o.date_publication DESC";
$offres = pg_query_params($conn,$sql,$params);

// Dropdown dynamique
$entreprises_dropdown = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");
$titres_dropdown = pg_query($conn, "SELECT DISTINCT titre FROM offres_stage ORDER BY titre ASC");

// Préparer tableau JS
$offres_array = [];
while($o = pg_fetch_assoc($offres)) $offres_array[] = $o;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Offres - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
--rose:#c34271;--rose-clair:#f070a1;--vert:#16ffbd;--vert-fonce:#12c998;
--bg:#f8fafc;--text:#2d3e50;--shadow:rgba(0,0,0,0.1);--white:#fff;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);}

/* HEADER */
.header{background:white;padding:20px 40px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 5px 20px var(--shadow);border-radius:15px 15px 0 0;}
.logo a{font-size:28px;font-weight:700;color:var(--vert);text-decoration:none;}
.logo span{color:var(--rose);}
.nav a{margin:0 15px;text-decoration:none;font-weight:500;color:#555;}
.nav a.active,.nav a:hover{color:var(--rose);}
.header-auth a{margin-left:15px;text-decoration:none;font-weight:500;}
.auth-label{padding:8px 15px;border-radius:20px;border:1px solid var(--rose);color:var(--rose);cursor:pointer;transition:0.3s;}
.auth-label:hover{background:var(--rose);color:white;}

/* HERO */
.hero{padding:40px 20px;text-align:center;}
.hero h1{font-size:36px;color:var(--rose);margin-bottom:10px;}
.hero p{color:#666;margin-bottom:20px;}
.search-box{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;align-items:center;}
.search-box button{border:none;background:var(--rose);color:white;font-weight:500;cursor:pointer;transition:0.3s;width:150px;padding:12px 15px;border-radius:10px;}

/* Dropdown filtrage entreprise & titre */
.custom-dropdown{position:relative;width:200px;cursor:pointer;}
.dropdown-selected{background:white;padding:12px;border-radius:10px;border:1px solid #ddd;}
.dropdown-options{position:absolute;top:100%;left:0;width:100%;background:white;border:1px solid #ddd;border-radius:10px;display:none;max-height:180px;overflow-y:auto;z-index:10;}
.dropdown-options div{padding:8px 12px;cursor:pointer;font-size:14px;}
.dropdown-options div:hover{background:var(--rose-clair);color:white;}

/* CARDS OFFRES */
.cards{
  display:grid;
  grid-template-columns: repeat(auto-fill, 170px);
  justify-content:center;
  gap:18px;
  margin:30px auto;
  max-width:1100px;
}

.card{
  width:170px;
  height:200px;
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
.card img{width:55px;height:55px;object-fit:contain;margin-bottom:8px;border-bottom:2px solid var(--rose);padding-bottom:5px;}
.card h3{font-size:13px;color:var(--rose-clair);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;}
.card p{font-size:11px;color:#555;}
.card a.details{font-size:11px;text-decoration:none;color:#d4a017;margin-top:5px;font-weight:600;}

/* MODAL */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;z-index:100;}
.modal{background:white;padding:30px;border-radius:20px;width:400px;max-width:90%;box-shadow:0 20px 50px var(--shadow);position:relative;}
.modal h2{text-align:center;color: var(--rose);margin-bottom:20px;}
.modal input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #ddd;margin-bottom:12px;outline:none;transition:0.2s;}
.modal input:focus{border-color: var(--rose);box-shadow:0 0 10px rgba(195,66,113,0.2);}
.modal button{width:100%;padding:12px;border:none;border-radius:30px;background: linear-gradient(135deg,var(--rose),var(--rose-clair));color:white;font-weight:600;cursor:pointer;transition:0.3s;}
.modal button:hover{transform:translateY(-2px);box-shadow:0 12px 25px rgba(0,0,0,0.15);}
.modal .close{position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;color:#999;transition:0.3s;}
.modal .close:hover{color:#333;}
</style>
</head>
<body>

<header class="header">
<div class="logo"><a href="#">Stage<span>Tunisie</span></a></div>
<nav class="nav">
<a href="index.php">Accueil</a>
<a href="nosentreprises.php">Entreprises</a>
<a href="nosoffres.php" class="active">Offres</a>
<a href="blog.php">Blog</a>
<a href="ai.php">Coach AI </a>
</nav>
<div class="header-auth">
<span class="auth-label" onclick="openModal('login')">Connexion</span>
<span class="auth-label" onclick="openModal('signup')">Inscription</span>
</div>
</header>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nos offres - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
--rose:#c34271;--rose-clair:#f070a1;--vert:#16ffbd;--vert-fonce:#12c998;
--bg:#f8fafc;--text:#2d3e50;--shadow:rgba(0,0,0,0.1);--white:#fff;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);}

/* HEADER */
.header{background:white;padding:20px 40px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 5px 20px var(--shadow);border-radius:15px 15px 0 0;}
.logo a{font-size:28px;font-weight:700;color:var(--vert);text-decoration:none;}
.logo span{color:var(--rose);}
.nav a{margin:0 15px;text-decoration:none;font-weight:500;color:#555;}
.nav a.active,.nav a:hover{color:var(--rose);}
.header-auth a{margin-left:15px;text-decoration:none;font-weight:500;}
.auth-label{padding:8px 15px;border-radius:20px;border:1px solid var(--rose);color:var(--rose);cursor:pointer;transition:0.3s;}
.auth-label:hover{background:var(--rose);color:white;}

/* HERO */
.hero{padding:40px 20px;text-align:center;}
.hero h1{font-size:36px;color:var(--rose);margin-bottom:10px;}
.hero p{color:#666;margin-bottom:20px;}
.search-box{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;align-items:center;}
.search-box button{border:none;background:var(--rose);color:white;font-weight:500;cursor:pointer;transition:0.3s;width:150px;padding:12px 15px;border-radius:10px;}
.search-box button:hover{background:var(--rose-clair);}

/* Dropdown filtrage */
.custom-dropdown{position:relative;width:200px;cursor:pointer;}
.dropdown-selected{background:white;padding:12px;border-radius:10px;border:1px solid #ddd;}
.dropdown-options{position:absolute;top:100%;left:0;width:100%;background:white;border:1px solid #ddd;border-radius:10px;display:none;max-height:180px;overflow-y:auto;z-index:10;}
.dropdown-options div{padding:8px 12px;cursor:pointer;font-size:14px;}
.dropdown-options div:hover{background:var(--rose-clair);color:white;}

/* CARDS OFFRES */
.cards{
  display:grid;
  grid-template-columns: repeat(auto-fill, 170px);
  justify-content:center;
  gap:18px;
  margin:30px auto;
  max-width:1100px;
}

.card{
  width:170px;
  height:170px;
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
.card img.logo{width:55px;height:55px;object-fit:contain;margin-bottom:8px;border-bottom:2px solid var(--rose);padding-bottom:5px;}
.card h3{font-size:13px;color: var(--rose-clair);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;}
.card p{font-size:11px;color:#555;}
.card a.details{font-size:11px;text-decoration:none;color:#d4a017;margin-top:5px;font-weight:600;}

/* MODAL */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;z-index:100;}
.modal{background:white;padding:30px;border-radius:20px;width:400px;max-width:90%;box-shadow:0 20px 50px var(--shadow);position:relative;}
.modal h2{text-align:center;color: var(--rose);margin-bottom:20px;}
.modal input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #ddd;margin-bottom:12px;outline:none;transition:0.2s;}
.modal input:focus{border-color: var(--rose);box-shadow:0 0 10px rgba(195,66,113,0.2);}
.modal button{width:100%;padding:12px;border:none;border-radius:30px;background: linear-gradient(135deg,var(--rose),var(--rose-clair));color:white;font-weight:600;cursor:pointer;transition:0.3s;}
.modal button:hover{transform:translateY(-2px);box-shadow:0 12px 25px rgba(0,0,0,0.15);}
.modal .close{position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;color:#999;transition:0.3s;}
.modal .close:hover{color:#333;}
.copyright{text-align:center;}
</style>
</head>
<body>


<section class="hero">
<h1>Nos offres</h1>
<p>Filtrez les offres par nom de stage ou nom d’entreprise.</p>
<form method="GET" class="search-box">
  <!-- Dropdown entreprise -->
  <div class="custom-dropdown" id="entrepriseDropdown">
    <?php
      $selectedEntrepriseNom = '';
      if($selected_entreprise){
          $res = pg_query_params($conn, "SELECT nom FROM entreprises WHERE id_entreprise=$1", array($selected_entreprise));
          if(pg_num_rows($res)) $selectedEntrepriseNom = pg_fetch_result($res,0,0);
      }
    ?>
    <div class="dropdown-selected"><?= $selectedEntrepriseNom ?: 'Nom entreprise' ?></div>
    <div class="dropdown-options">
      <?php while($e = pg_fetch_assoc($entreprises_dropdown)): ?>
        <div data-value="<?= htmlspecialchars($e['id_entreprise']) ?>"><?= htmlspecialchars($e['nom']) ?></div>
      <?php endwhile; ?>
    </div>
    <input type="hidden" name="entreprise" id="entrepriseInput" value="<?= htmlspecialchars($selected_entreprise) ?>">
  </div>

  <!-- Dropdown titre offre -->
  <div class="custom-dropdown" id="titreDropdown">
    <div class="dropdown-selected"><?= $selected_titre ?: 'Titre du stage' ?></div>
    <div class="dropdown-options">
      <?php while($t = pg_fetch_assoc($titres_dropdown)): ?>
        <div data-value="<?= htmlspecialchars($t['titre']) ?>"><?= htmlspecialchars($t['titre']) ?></div>
      <?php endwhile; ?>
    </div>
    <input type="hidden" name="titre" id="titreInput" value="<?= htmlspecialchars($selected_titre) ?>">
  </div>

  <button type="submit">Filtrer</button>
  <button type="button" onclick="window.location.href='nosoffres.php'">Réinitialiser</button>
</form>
</section>

<h2 class="section-title"></h2>
<div class="cards" id="offresContainer"></div>

<!-- MODAL LOGIN -->
<div class="modal-bg" id="modal-login">
  <div class="modal">
    <span class="close" onclick="closeModal('login')">&times;</span>
    <h2>Connexion</h2>
    <?php if(isset($error_login)): ?>
      <p style="color:red; font-size:13px; text-align:center;"><?= $error_login ?></p>
    <?php endif; ?>
<form method="POST">
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Mot de passe" required>
  <button type="submit" name="login">Se connecter</button>
</form>
  </div>
</div>

<!-- MODAL INSCRIPTION -->
<div class="modal-bg" id="modal-signup">
  <div class="modal">
    <span class="close" onclick="closeModal('signup')">&times;</span>
    <h2>Inscription</h2>
    <?php if(isset($error_signup)): ?>
      <p style="color:red; font-size:13px; text-align:center;"><?= $error_signup ?></p>
    <?php endif; ?>
<form method="POST">
  <input type="text" name="nom" placeholder="Nom complet" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Mot de passe" required>
  <input type="password" name="confirm_password" placeholder="Confirmer mot de passe" required>
  <button type="submit" name="signup">S'inscrire</button>
</form>
  </div>
</div>

<script>
let offres = <?= json_encode($offres_array) ?>;
const offresContainer = document.getElementById('offresContainer');
offres.forEach(o=>{
  let html=`<div class="card">
    ${o.logo?'<img class="logo" src="'+o.logo+'">':''}
    <h3>${o.titre}</h3>
    <p><strong>Entreprise:</strong> ${o.entreprise} (${o.ville})</p>
  
    
    <a class="details" href="offre_details_utilisateur.php?id=${o.id_stage}">Voir détails</a>
  </div>`;
  offresContainer.innerHTML+=html;
});

// Dropdown entreprise
const dropdownEntreprise = document.getElementById('entrepriseDropdown');
const selectedEntreprise = dropdownEntreprise.querySelector('.dropdown-selected');
const optionsEntreprise = dropdownEntreprise.querySelector('.dropdown-options');
const inputHiddenEntreprise = document.getElementById('entrepriseInput');
selectedEntreprise.addEventListener('click', ()=>{
  optionsEntreprise.style.display = optionsEntreprise.style.display==='block'?'none':'block';
});
optionsEntreprise.querySelectorAll('div').forEach(div=>{
  div.addEventListener('click', ()=>{
    selectedEntreprise.innerText = div.innerText;
    optionsEntreprise.style.display='none';
    inputHiddenEntreprise.value = div.getAttribute('data-value');
  });
});

// Dropdown titre
const dropdownTitre = document.getElementById('titreDropdown');
const selectedTitre = dropdownTitre.querySelector('.dropdown-selected');
const optionsTitre = dropdownTitre.querySelector('.dropdown-options');
const inputHiddenTitre = document.getElementById('titreInput');
selectedTitre.addEventListener('click', ()=>{
  optionsTitre.style.display = optionsTitre.style.display==='block'?'none':'block';
});
optionsTitre.querySelectorAll('div').forEach(div=>{
  div.addEventListener('click', ()=>{
    selectedTitre.innerText = div.innerText;
    optionsTitre.style.display='none';
    inputHiddenTitre.value = div.getAttribute('data-value');
  });
});

// Fermer dropdown si clic en dehors
window.addEventListener('click', function(e){
  if(!dropdownEntreprise.contains(e.target)) optionsEntreprise.style.display='none';
  if(!dropdownTitre.contains(e.target)) optionsTitre.style.display='none';
});

// Modal
function openModal(type){document.getElementById('modal-'+type).style.display='flex';}
function closeModal(type){document.getElementById('modal-'+type).style.display='none';}
window.onclick=function(e){if(e.target.classList.contains('modal-bg')) e.target.style.display='none';}
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>