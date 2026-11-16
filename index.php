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
            } else {
                $error_signup = "Erreur lors de l'inscription.";
            }
        }
    }
}
$ville = $_GET['ville'] ?? '';
$niveau_etude = $_GET['niveau_etude'] ?? '';
$duree_min = $_GET['duree_min'] ?? '';
$duree_max = $_GET['duree_max'] ?? '';

$sql = "SELECT o.id_stage,o.titre,o.niveau_etude,o.duree_semaine,o.date_publication,
        e.id_entreprise,e.nom AS entreprise,e.ville,e.logo
        FROM offres_stage o
        JOIN entreprises e ON o.id_entreprise=e.id_entreprise
        WHERE 1=1";
$params = [];
$counter = 1;
if($ville!==''){ $sql.=" AND e.ville ILIKE $".$counter; $params[]="%$ville%"; $counter++; }
if($niveau_etude!==''){ $sql.=" AND o.niveau_etude=$".$counter; $params[]=$niveau_etude; $counter++; }
if($duree_min!==''){ $sql.=" AND o.duree_semaine >= $".$counter; $params[]=$duree_min; $counter++; }
if($duree_max!==''){ $sql.=" AND o.duree_semaine <= $".$counter; $params[]=$duree_max; $counter++; }
$sql.=" ORDER BY o.date_publication DESC";
$offres = pg_query_params($conn,$sql,$params);

$popular_query = "SELECT e.id_entreprise,e.nom,e.ville,e.logo,COUNT(o.id_stage) AS nb_offres
                  FROM entreprises e
                  LEFT JOIN offres_stage o ON e.id_entreprise=o.id_entreprise
                  GROUP BY e.id_entreprise
                  ORDER BY nb_offres DESC
                  LIMIT 5";
$entreprises_populaires = pg_query($conn,$popular_query);


$offres_array = [];
while($o = pg_fetch_assoc($offres)) $offres_array[] = $o;
$entreprises_array = [];
while($e = pg_fetch_assoc($entreprises_populaires)) $entreprises_array[] = $e;
$total_etudiants = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM utilisateurs WHERE role='etudiant'"), 0, 0);
$total_entreprises = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM entreprises"), 0, 0);

$total_offres = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM offres_stage"), 0, 0);

$etudiants_places = pg_fetch_result(pg_query($conn, "SELECT COUNT(DISTINCT id_user) FROM candidatures WHERE statut='accepte'"), 0, 0);

$taux_placement = $total_etudiants > 0 ? round(($etudiants_places/$total_etudiants)*100) : 0;
?>
<?php
$entreprise_du_mois_query = "SELECT * FROM entreprises ORDER BY RANDOM() LIMIT 1";
$entreprise_du_mois = pg_fetch_assoc(pg_query($conn, $entreprise_du_mois_query));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
--rose:#c34271;--rose-clair:#f070a1;--vert:#16ffbd;--vert-fonce:#12c998;
--bg:#f8fafc;--text:#2d3e50;--shadow:rgba(0,0,0,0.1);--white:#fff;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);}


.header{background:white;padding:20px 40px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 5px 20px var(--shadow);border-radius:15px 15px 0 0;}
.logo a{font-size:28px;font-weight:700;color:var(--vert);text-decoration:none;}
.logo span{color:var(--rose);}
.nav a{margin:0 15px;text-decoration:none;font-weight:500;color:#555;}
.nav a.active,.nav a:hover{color:var(--rose);}
.header-auth a{margin-left:15px;text-decoration:none;font-weight:500;}
.auth-label{padding:8px 15px;border-radius:20px;border:1px solid var(--rose);color:var(--rose);cursor:pointer;transition:0.3s;}
.auth-label:hover{background:var(--rose);color:white;}


.hero{padding:40px 20px;text-align:center;}
.hero h1{font-size:36px;color:var(--rose);margin-bottom:10px;}
.hero p{color:#666;margin-bottom:20px;}
.search-box{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;align-items:center;}
.search-box input, .search-box button, .dropdown-selected{padding:12px 15px;border-radius:10px;border:1px solid #ddd;font-size:14px;height:42px;}
.search-box input{width:150px;}
.search-box button{border:none;background:var(--rose);color:white;font-weight:500;cursor:pointer;transition:0.3s;}
.search-box button:hover{background:var(--rose-clair);}


.custom-dropdown{position:relative;width:160px;cursor:pointer;}
.dropdown-selected{background:white;}
.dropdown-options{position:absolute;top:100%;left:0;width:100%;background:white;border:1px solid #ddd;border-radius:10px;display:none;max-height:180px;overflow-y:auto;z-index:10;}
.dropdown-options div{padding:8px 12px;cursor:pointer;font-size:14px;}
.dropdown-options div:hover{background:var(--rose-clair);color:white;}

.cards{
  display:grid;

 
  grid-template-columns: repeat(auto-fill, 170px);

  justify-content:center;
  gap:18px;

  margin:30px auto;
  max-width:1100px;
}


.card,
.company{
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

.card:hover,
.company:hover{
  transform:translateY(-4px);
}


.card img.logo,
.company img{
  width:55px;
  height:55px;
  object-fit:contain;

  margin-bottom:8px;

  border-bottom:2px solid var(--rose);
  padding-bottom:5px;
}


.card h3,
.company h4{
  font-size:13px;
  color: var(--rose-clair);

  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;

  width:100%;
}


.card p,
.company p{
  font-size:11px;
  color:#555;
}


.nb-offres{
  font-size:11px;
  font-weight:600;
  color: var(--vert-fonce);
}


.card a.details,
.company a.details{
  font-size:11px;
  text-decoration:none;
  color:#d4a017; 
  margin-top:5px;
  font-weight:600;
}



.load-arrow{
  display:flex;
  justify-content:center;
  align-items:center;

  font-size:26px;
  color: var(--rose);

  cursor:pointer;

  margin:10px auto;

  width:42px;
  height:42px;

  border-radius:50%;
  background:white;

  box-shadow:0 4px 12px rgba(0,0,0,0.1);

  transition:0.3s;
}

.load-arrow:hover{
  color: var(--rose-clair);
  transform:scale(1.1);
}


.section-title{
  text-align:center;
  font-size:22px;
  color: var(--rose);
  margin-top:30px;
}


@media(max-width:500px){
  .cards{
    grid-template-columns: repeat(auto-fill, 140px);
  }

  .card,
  .company{
    width:140px;
    height:140px;
  }
}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;z-index:100;}
.modal{background:white;padding:30px;border-radius:20px;width:400px;max-width:90%;box-shadow:0 20px 50px var(--shadow);position:relative;}
.modal h2{text-align:center;color: var(--rose);margin-bottom:20px;}
.modal input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #ddd;margin-bottom:12px;outline:none;transition:0.2s;}
.modal input:focus{border-color: var(--rose);box-shadow:0 0 10px rgba(195,66,113,0.2);}
.modal button{width:100%;padding:12px;border:none;border-radius:30px;background: linear-gradient(135deg,var(--rose),var(--rose-clair));color:white;font-weight:600;cursor:pointer;transition:0.3s;}
.modal button:hover{transform:translateY(-2px);box-shadow:0 12px 25px rgba(0,0,0,0.15);}
.modal .close{position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;color:#999;transition:0.3s;}
.modal .close:hover{color:#333;}

.how-it-helps{
  max-width:800px;
  margin:30px auto 60px auto;
  display:flex;
  flex-direction:column;
  gap:12px;
}

.help-item{
  background:white;
  border-radius:12px;
  box-shadow:0 5px 15px rgba(0,0,0,0.1);
  overflow:hidden;
}

.help-question{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:15px 20px;
  cursor:pointer;
  font-size:16px;
  font-weight:600;
  color:var(--rose);
  transition:0.3s;
}

.help-question:hover{
  background:var(--rose-clair);
  color:white;
}

.icon-toggle{
  font-size:18px;
  transition:transform 0.3s;
}

.help-answer{
  display:none;
  padding:12px 20px;
  font-size:14px;
  color:#555;
  border-top:1px solid #eee;
  background:var(--bg);
}


.spotlight{
  max-width:1100px;
  margin:50px auto;
  padding:0 20px;
}

.spotlight-card{
  display:flex;
  align-items:center;
  background: linear-gradient(135deg, #f8f9fa, #ffe6f0);
  border-radius:20px;
  padding:25px 30px;
  box-shadow:0 20px 50px rgba(0,0,0,0.1);
  gap:25px;
  opacity:0;
  transform: translateY(30px);
  transition: all 0.8s ease;
}

.spotlight-card.visible{
  opacity:1;
  transform: translateY(0);
}

.spotlight-card .logo img{
  width:130px;
  height:130px;
  object-fit:contain;
  border-radius:50%;
  border:4px solid white;
  box-shadow:0 5px 20px rgba(0,0,0,0.1);
  background:white;
  padding:10px;
}

.spotlight-card .info{
  flex:1;
}

.spotlight-card .info h3{
  font-size:22px;
  color:var(--rose);
  margin-bottom:12px;
}

.spotlight-card .info p{
  font-size:15px;
  color:#555;
  line-height:1.5;
}

.spotlight-card .action .btn{
  display:inline-block;
  padding:12px 22px;
  background:linear-gradient(135deg, var(--rose), var(--rose-clair));
  color:white;
  border-radius:30px;
  text-decoration:none;
  font-weight:600;
  transition:0.3s;
}

.spotlight-card .action .btn:hover{
  transform:translateY(-3px) scale(1.05);
  box-shadow:0 12px 25px rgba(0,0,0,0.2);
}

.section-title{
  text-align:center;
  font-size:24px;
  color:var(--rose);
  margin-bottom:25px;
}
@media(max-width:900px){
  .spotlight-card{
    flex-direction:column;
    text-align:center;
  }
  .spotlight-card .action{
    margin-top:15px;
  }
}

</style>
</head>
<body>

<header class="header">
<div class="logo"><a href="#">Stage<span>Tunisie</span></a></div>
<nav class="nav">
<a href="index.php" class="active">Accueil</a>
<a href="nosentreprises.php">Entreprises</a>
<a href="nosoffres.php">Offres</a>
<a href="blog.php">Blog</a>
<a href="ai.php">Coach AI </a>
</nav>
<div class="header-auth">
<span class="auth-label" onclick="openModal('login')">Connexion</span>
<span class="auth-label" onclick="openModal('signup')">Inscription</span>
</div>
</header>

<section class="hero">
<h1>Trouvez votre stage idéal</h1>
<p>Ne dites pas qu’il n’y a pas de stages. Dites plutôt que vous n’êtes pas encore venu chez nous.</p>
<form method="GET" class="search-box">
<input type="text" name="ville" placeholder="Ville" value="<?= htmlspecialchars($ville) ?>">
<div class="custom-dropdown" id="niveauDropdown">
<div class="dropdown-selected"><?= $niveau_etude?:'Niveau d’étude' ?></div>
<div class="dropdown-options">
<div data-value="">Tous</div>
<div data-value="Bac+1">Bac+1</div>
<div data-value="Bac+2">Bac+2</div>
<div data-value="Licence">Licence</div>
<div data-value="Master">Master</div>
<div data-value="Doctorat">Doctorat</div>
</div>
</div>
<input type="hidden" name="niveau_etude" id="niveauInput" value="<?= htmlspecialchars($niveau_etude) ?>">
<input type="number" name="duree_min" placeholder="Durée min" value="<?= htmlspecialchars($duree_min) ?>" style="width:150px;">
<input type="number" name="duree_max" placeholder="Durée max" value="<?= htmlspecialchars($duree_max) ?>" style="width:150px;">
<button type="submit">Rechercher</button>
<button type="button" onclick="window.location.href='index.php'">Réinitialiser</button>
</form>
</section>

<h2 class="section-title">Offres Récentes</h2>
<div class="cards" id="offresContainer"></div>
<div class="load-arrow" id="loadMoreOffres">&#8595;</div>

<h2 class="section-title">Entreprises populaires</h2>
<div class="cards" id="entreprisesContainer"></div>
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
<div class="modal-bg" id="modal-signup">
  <div class="modal">
    <span class="close" onclick="closeModal('signup')">&times;</span>
    <h2>Inscription</h2>
    <?php if(isset($error_signup)): ?>
      <p style="color:red; font-size:13px; text-align:center;"><?= $error_signup ?></p>
    <?php endif; ?>
    <?php if(isset($success_signup)): ?>
      <p style="color:green; font-size:13px; text-align:center;"><?= $success_signup ?></p>
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
<h2 class="section-title">Pourquoi choisir StageTunisie ?</h2>

<div class="how-it-helps">

  <div class="help-item">
    <div class="help-question" onclick="toggleHelp(this)">
      <span> Grande base d’offres de stage</span>
      <span class="icon-toggle">+</span>
    </div>
    <div class="help-answer">
      Nous centralisons des centaines d’offres de stage actualisées chaque semaine pour toutes les
      villes et tous les niveaux d’étude — BTS, Licence, Master, etc.
    </div>
  </div>

  <div class="help-item">
    <div class="help-question" onclick="toggleHelp(this)">
      <span> Interface simple pour postuler</span>
      <span class="icon-toggle">+</span>
    </div>
    <div class="help-answer">
      Une interface fluide et intuitive vous permet de trier, filtrer et postuler rapidement sans
      perdre de temps.
    </div>
  </div>

  <div class="help-item">
    <div class="help-question" onclick="toggleHelp(this)">
      <span>Conseils pour CV & entretien</span>
      <span class="icon-toggle">+</span>
    </div>
    <div class="help-answer">
      Accédez à des conseils pratiques pour rédiger votre CV et réussir vos entretiens de stage et
      alternance.
    </div>
  </div>

  <div class="help-item">
    <div class="help-question" onclick="toggleHelp(this)">
      <span> Suivi personnalisé via dashboard</span>
      <span class="icon-toggle">+</span>
    </div>
    <div class="help-answer">
      Suivez vos candidatures, offres préférées et conseils personnalisés directement depuis votre
      tableau de bord.
    </div>
  </div>

</div>
<h2 class="section-title">Nos chiffres clés</h2>
<section class="stats-circle">
  <div class="circle-stat" data-value="<?= $total_entreprises ?>" data-max="1000">
    <svg viewBox="0 0 100 100">
      <circle class="bg" cx="50" cy="50" r="45"></circle>
      <circle class="progress" cx="50" cy="50" r="45"></circle>
    </svg>
    <div class="number">0</div>
    <div class="label">Entreprises inscrites</div>
  </div>

  <div class="circle-stat" data-value="<?= $total_offres ?>" data-max="3000">
    <svg viewBox="0 0 100 100">
      <circle class="bg" cx="50" cy="50" r="45"></circle>
      <circle class="progress" cx="50" cy="50" r="45"></circle>
    </svg>
    <div class="number">0</div>
    <div class="label">Offres disponibles</div>
  </div>

  <div class="circle-stat" data-value="<?= $taux_placement ?>" data-max="100">
    <svg viewBox="0 0 100 100">
      <circle class="bg" cx="50" cy="50" r="45"></circle>
      <circle class="progress" cx="50" cy="50" r="45"></circle>
    </svg>
    <div class="number">0</div>
    <div class="label">Taux de placement (%)</div>
  </div>
</section>
<section class="spotlight">
  <h2 class="section-title">Entreprise du Jour</h2>
  <?php if($entreprise_du_mois): ?>
  <div class="spotlight-card">
    <div class="logo">
      <img src="<?= htmlspecialchars($entreprise_du_mois['logo']) ?>" alt="<?= htmlspecialchars($entreprise_du_mois['nom']) ?>">
    </div>
    <div class="info">
      <h3><?= htmlspecialchars($entreprise_du_mois['nom']) ?></h3>
      <p><?= htmlspecialchars(substr($entreprise_du_mois['description'],0,200)) ?>...</p>
    </div>
    <div class="action">
      <a href="entreprise_detail_utilisateur.php?id=<?= $entreprise_du_mois['id_entreprise'] ?>" class="btn">Détails</a>
    </div>
  </div>
  <?php endif; ?>
</section>
<script>
const circles = document.querySelectorAll('.circle-stat');
circles.forEach(c => {
  const number = c.querySelector('.number');
  const progress = c.querySelector('.progress');
  const value = +c.dataset.value; 
  const max = +c.dataset.max; 
  const radius = 45;
  const circumference = 2 * Math.PI * radius;
  const percent = Math.min(value/max*100,100); 
  const offset = circumference - (percent/100)*circumference;
  setTimeout(()=>{ progress.style.strokeDashoffset = offset; },100);
  let count = 0;
  const step = Math.ceil(value / 100);
  const animateNumber = () => {
    if(count < value){
      count += step;
      if(count > value) count = value;
      number.innerText = count;
      requestAnimationFrame(animateNumber);
    } else number.innerText = value;
  };
  animateNumber();
});
</script>

<style>
.stats-circle{
  display:flex;
  justify-content:center;
  gap:50px;
  flex-wrap:wrap;
  margin:60px auto;
  max-width:900px;
}
.circle-stat{
  position:relative;
  width:150px;
  height:150px;
  text-align:center;
}
.circle-stat svg{
  transform:rotate(-90deg);
  width:100%;
  height:100%;
}
.circle-stat circle{
  fill:none;
  stroke-width:10;
  stroke-linecap:round;
}
.circle-stat circle.bg{
  stroke:#eee;
}
.circle-stat circle.progress{
  stroke: url(#gradient);
  stroke-dasharray: 283; 
  stroke-dashoffset: 283;
  transition: stroke-dashoffset 1.5s ease-out;
}
.circle-stat .number{
  position:absolute;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%);
  font-size:24px;
  font-weight:700;
  color: var(--rose);
}
.circle-stat .label{
  margin-top:10px;
  font-size:14px;
  color:#555;
}
.copyright{text-align:center;}
</style>
<svg style="display:none">
  <defs>
    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="var(--rose)"/>
      <stop offset="100%" stop-color="var(--rose-clair)"/>
    </linearGradient>
  </defs>
</svg>


<script>
let offres=<?= json_encode($offres_array) ?>;
let entreprises=<?= json_encode($entreprises_array) ?>;
let offresIndex=0, step=5;
function renderOffres(){
  const container=document.getElementById('offresContainer');let html='';
  for(let i=offresIndex;i<Math.min(offresIndex+step,offres.length);i++){
    let o=offres[i];
    html+=`<div class="card">
      ${o.logo?'<img class="logo" src="'+o.logo+'">':''}
      <h3>${o.titre}</h3>
      <p><strong>Entreprise:</strong> ${o.entreprise} (${o.ville})</p>
      
      
      <a class="details" href="offre_details_utilisateur.php?id=${o.id_stage}">Voir détails</a>
    </div>`;
  }
  container.innerHTML+=html;
  offresIndex+=step;
  console.log(offres.length);
}
document.getElementById('loadMoreOffres').addEventListener('click',renderOffres);
renderOffres();
const entreprisesContainer=document.getElementById('entreprisesContainer');
entreprises.forEach(e=>{
  let html=`<div class="company">
    ${e.logo?'<img src="'+e.logo+'">':''}
    <h4>${e.nom}</h4>
    <p>${e.ville}</p>
    <p><strong>${e.nb_offres} offres</strong></p>
    <a class="details" href="entreprise_detail_utilisateur.php?id=${e.id_entreprise}">Voir détails</a>
  </div>`;
  entreprisesContainer.innerHTML+=html;
});
const dropdown=document.getElementById('niveauDropdown');
const selected=dropdown.querySelector('.dropdown-selected');
const options=dropdown.querySelector('.dropdown-options');
const inputHidden=document.getElementById('niveauInput');
selected.addEventListener('click',()=>{options.style.display = options.style.display==='block'?'none':'block';});
options.querySelectorAll('div').forEach(div=>{
  div.addEventListener('click',()=>{
    selected.innerText=div.innerText;
    options.style.display='none';
    inputHidden.value=div.getAttribute('data-value');
  });
});
function openModal(type){document.getElementById('modal-'+type).style.display='flex';}
function closeModal(type){document.getElementById('modal-'+type).style.display='none';}
window.onclick=function(e){if(e.target.classList.contains('modal-bg')) e.target.style.display='none';}
</script>
<script>
function toggleHelp(el){
  const answer = el.nextElementSibling;
  const icon = el.querySelector(".icon-toggle");
  
  if(answer.style.display === "block"){
    answer.style.display = "none";
    icon.innerText = "+";
  } else {
    answer.style.display = "block";
    icon.innerText = "−";
  }
}

const spotlightCard = document.querySelector('.spotlight-card');
function revealSpotlight(){
  const top = spotlightCard.getBoundingClientRect().top;
  const height = window.innerHeight;
  if(top < height - 100){
    spotlightCard.classList.add('visible');
  }
}
window.addEventListener('scroll', revealSpotlight);
revealSpotlight();
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>