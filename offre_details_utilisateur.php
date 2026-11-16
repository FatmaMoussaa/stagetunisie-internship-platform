<?php
ob_start();
session_start();
require_once 'config.php';

// ==================== LOGIN ====================
if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = pg_query_params($conn, "SELECT * FROM utilisateurs WHERE email=$1", array($email));
    if($result && pg_num_rows($result) === 1){
        $user = pg_fetch_assoc($result);
        if(password_verify($password, $user['password'])){
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] === 'etudiant'){
                header("Location: etudiant.php"); exit;
            } elseif($user['role'] === 'admin'){
                header("Location: admin.php"); exit;
            } else {
                header("Location: index.php"); exit;
            }
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
                header("Location: offredetails.php?id=".$_GET['id']); exit;
            } else {
                $error_signup = "Erreur lors de l'inscription.";
            }
        }
    }
}








// ==================== Récupérer les infos de l'offre ====================
$id_stage = $_GET['id'] ?? 0;
$result = pg_query_params($conn, "
    SELECT o.*, e.nom AS entreprise_nom, e.logo AS entreprise_logo, e.ville AS ville_entreprise
    FROM offres_stage o
    JOIN entreprises e ON o.id_entreprise = e.id_entreprise
    WHERE o.id_stage = $1
", [$id_stage]);

if(!$result || pg_num_rows($result) === 0){
    echo "Offre non trouvée"; exit;
}
$offre = pg_fetch_assoc($result);

// ==================== Extraire les sections depuis la description ====================
function parse_sections($text) {
    $sections = [];
    $titles = ['Description', 'Missions', 'Compétences', 'Profil'];
    foreach ($titles as $t) {
        if (preg_match("/$t\s*:\s*(.*?)(?=" . implode("|", array_map(fn($x)=>preg_quote($x), $titles)) . "|$)/is", $text, $m)) {
            $sections[$t] = trim($m[1]);
        } else {
            $sections[$t] = 'Non renseigné';
        }
    }
    return $sections;
}

$sections = parse_sections($offre['description'] ?? '');
// ==================== Préparer les réponses chatbot ====================
$chatbotResponses = [
    "entreprise" => "Entreprise : " . $offre['entreprise_nom'],
    "ville"      => "Ville : " . $offre['ville_entreprise'],
    "niveau"     => "Niveau d'étude : " . $offre['niveau_etude'],
    "duree"      => "Durée : " . $offre['duree_semaine'] . " semaines",
    "missions"   => "Missions : " . ($sections['Missions'] ?? 'Non renseigné'),
    "competences"=> "Compétences requises : " . ($sections['Compétences'] ?? 'Non renseigné'),
    "profil"     => "Profil recherché : " . ($sections['Profil'] ?? 'Non renseigné')
];

$chatbotJSON = json_encode($chatbotResponses, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StageTunisie - <?= htmlspecialchars($offre['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{
  --rose:#c34271;
  --rose-clair:#f070a1;
  --vert:#16ffbd;
  --bg:#f8fafc;
  --text:#2d3e50;
  --shadow:rgba(0,0,0,0.08);
  --chat-bg:#fff0f8;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Poppins',sans-serif;
}

/* 🔥 FIX GLOBAL */
html, body{
  height:100%;
}

body{
  overflow:hidden; /* 🔥 bloque scroll page */
  background: var(--bg);
  color: var(--text);
  display:flex;
  flex-direction:column;
}

/* HEADER */
.header{
  flex-shrink:0;
  background:white;
  padding:20px 40px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  box-shadow:0 5px 20px var(--shadow);
  border-radius:15px 15px 0 0;
}

.logo a { font-size:28px; font-weight:700; color: var(--vert); text-decoration:none; }
.logo span { color: var(--rose); }

.nav a { margin:0 15px; text-decoration:none; font-weight:500; color:#555; }
.nav a.active, .nav a:hover { color: var(--rose); }

.auth-label {
  padding:8px 15px;
  border-radius:20px;
  border:1px solid var(--rose);
  color: var(--rose);
  cursor:pointer;
}
.auth-label:hover { background: var(--rose); color:white; }

/* 🔥 CONTAINER PRINCIPAL */
.chat-container{
  flex:1;
  display:flex;
  margin:20px;
  border-radius:20px;
  box-shadow:0 10px 25px var(--shadow);
  overflow:hidden;
  background: var(--chat-bg);

  min-height:0; /* 🔥 CRUCIAL */
}

/* SIDEBAR */
.chat-sidebar{
  width:350px;
  background:white;
  border-right:1px solid #eee;
  padding:20px;
  overflow-y:auto;
  display:flex;
  flex-direction:column;
  gap:20px;
}

.chat-sidebar .offre-header{
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:10px;
}

.chat-sidebar .offre-header img{width:80px; height:80px; border-radius:50%; border:2px solid var(--vert);}

.chat-sidebar .offre-header h2{color:var(--rose); font-size:18px; text-align:center;}

.chat-sidebar .offre-header{display:flex; flex-direction:column; align-items:center; gap:10px;}
.chat-sidebar h3{margin-bottom:10px; color: var(--rose); font-size:16px;}

.chat-sidebar ul{list-style:none;} .chat-sidebar li{padding:10px; cursor:pointer; border-radius:10px; transition:0.3s;} .chat-sidebar li:hover{background: var(--rose-clair); color:white;}

/* 🔥 CHAT MAIN */
.chat-main{
  flex:1;
  display:flex;
  flex-direction:column;
  overflow:hidden;

  min-height:0; /* 🔥 CRUCIAL */
}

/* 🔥 ZONE SCROLL */
.chat-messages{
  flex:1;
  overflow-y:auto; /* ✅ SCROLL ICI */
  padding:20px;

  display:flex;
  flex-direction:column;
  gap:15px;

  min-height:0; /* 🔥 CRUCIAL */
}

/* BULLES */
.bubble{
  max-width:75%;
  padding:12px 18px;
  border-radius:25px;
}

.bubble.bot{
  background:#ffe6f0;
  align-self:flex-start;
  display:flex;
  gap:10px;
}

.bubble.bot img{
  width:40px;
  height:40px;
  border-radius:50%;
}

.bubble.user{
  background:#16ffbd;
  align-self:flex-end;
}

/* INPUT FIXE */
.chat-input{
  flex-shrink:0;
  display:flex;
  padding:15px;
  background:white;
  border-top:1px solid #eee;
}

.chat-input input{
  flex:1;
  padding:12px 20px;
  border-radius:25px;
  border:1px solid #ccc;
}

.chat-input button{
  margin-left:10px;
  padding:12px 20px;
  border:none;
  border-radius:25px;
  background: var(--rose);
  color:white;
  cursor:pointer;
}



/* MODAL */
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:100; }
.modal { background:white; padding:40px; border-radius:20px; width:400px; max-width:90%; box-shadow:0 20px 50px var(--shadow); position:relative; }
.modal h2 { text-align:center; color: var(--rose); margin-bottom:25px; }
.modal input { width:100%; padding:12px 15px; border-radius:12px; border:1px solid #ddd; margin-bottom:15px; outline:none; transition:0.2s; }
.modal input:focus { border-color: var(--rose); box-shadow:0 0 10px rgba(195,66,113,0.2); }
.modal button { width:100%; padding:14px; border:none; border-radius:30px; background: linear-gradient(135deg,var(--rose),var(--rose-clair)); color:white; font-weight:600; cursor:pointer; transition:0.3s; }
.modal button:hover { transform:translateY(-2px); box-shadow:0 12px 25px rgba(0,0,0,0.15); }
.modal .close { position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px; color:#999; transition:0.3s; }
.modal .close:hover { color:#333; }
.copyright{text-align:center;}
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
    <a href="ai.php">Coach AI</a>
  </nav>
  <div class="header-auth">
    <span class="auth-label" onclick="openModal('login')">Connexion</span>
    <span class="auth-label" onclick="openModal('signup')">Inscription</span>
  </div>
</header>

<!-- MODALS -->
<div class="modal-bg" id="modal-login">
  <div class="modal">
    <span class="close" onclick="closeModal('login')">&times;</span>
    <h2>Connexion</h2>
    <?php if(isset($error_login)) echo '<div class="error-msg">'.htmlspecialchars($error_login).'</div>'; ?>
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
    if(isset($error_signup)) echo '<div class="error-msg">'.htmlspecialchars($error_signup).'</div>';
    if(isset($_SESSION['success_signup'])) { echo '<div class="success-msg">'.htmlspecialchars($_SESSION['success_signup']).'</div>'; unset($_SESSION['success_signup']); }
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

<!-- CHATBOT OFFRE -->
<div class="chat-container">
  <div class="chat-sidebar">
    <div class="offre-header">
      <?php if($offre['entreprise_logo'] && file_exists($offre['entreprise_logo'])): ?>
        <img src="<?= htmlspecialchars($offre['entreprise_logo']) ?>" alt="Logo entreprise">
      <?php endif; ?>
      <h2><?= htmlspecialchars($offre['titre']) ?></h2>
    </div>
    <h3>Questions sur l'offre</h3>
    <ul id="faqList">
      <li data-msg="entreprise">Quelle est l'entreprise ?</li>
      <li data-msg="ville">Dans quelle ville ?</li>
      <li data-msg="niveau">Niveau requis ?</li>
      <li data-msg="duree">Durée du stage ?</li>
      <li data-msg="missions">Missions ?</li>
      <li data-msg="competences">Compétences ?</li>
      <li data-msg="profil">Profil recherché ?</li>
    </ul>
  </div>
  <div class="chat-main">
    <div class="chat-messages" id="chatMessages">
      <div class="bubble bot">
        <img src="loup.webp" alt="Coach IA">
        <div>Bonjour ! Pose-moi une question sur cette offre et je te répondrai.</div>
      </div>
    </div>
    <div class="chat-input">
      <input type="text" id="chatInputBox" placeholder="Écris ta question...">
      <button id="chatSendBtn">Envoyer</button>
    </div>
  </div>
</div>

<script>
function openModal(type){document.getElementById('modal-'+type).style.display='flex';}
function closeModal(type){document.getElementById('modal-'+type).style.display='none';}
window.onclick=function(e){if(e.target.classList.contains('modal-bg')) e.target.style.display='none';}

const chatMessages = document.getElementById("chatMessages");
const inputBox = document.getElementById("chatInputBox");
const sendBtn = document.getElementById("chatSendBtn");
const faqItems = document.querySelectorAll("#faqList li");

const chatbotResponses = <?php echo $chatbotJSON; ?>;

faqItems.forEach(item=>{
  item.addEventListener("click", ()=>{
    addBubble(item.dataset.msg,"user");
    setTimeout(()=>addBubble(chatbotResponses[item.dataset.msg],"bot"),300);
  });
});

function addBubble(msg,type){
  const bubble=document.createElement("div");
  bubble.classList.add("bubble",type);
  if(type==="bot"){
    const img=document.createElement("img");
    img.src="loup.webp"; img.alt="Coach IA";
    bubble.appendChild(img);
    const div=document.createElement("div");
    div.innerText=msg;
    bubble.appendChild(div);
  } else { bubble.innerText=msg; }
  chatMessages.appendChild(bubble);
  chatMessages.scrollTop=chatMessages.scrollHeight;
}

sendBtn.addEventListener("click",()=>{
  const msg=inputBox.value.trim();
  if(!msg) return;
  addBubble(msg,"user");
  let response="Désolé, je n'ai pas compris. Essaie de cliquer sur une question ou poser-la clairement.";
  for(const key in chatbotResponses){
    if(msg.toLowerCase().includes(key)){
      response=chatbotResponses[key];
      break;
    }
  }
  setTimeout(()=>addBubble(response,"bot"),300);
  inputBox.value="";
});

inputBox.addEventListener("keypress",e=>{ if(e.key==="Enter") sendBtn.click(); });
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>