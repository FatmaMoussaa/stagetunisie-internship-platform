<?php
ob_start();
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StageTunisie - Mon Coach IA</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{
  --rose: #c34271;
  --rose-clair: #f070a1;
  --vert: #16ffbd;
  --bg: #f8fafc;
  --text: #2d3e50;
  --shadow: rgba(0,0,0,0.08);
  --chat-bg: #fff0f8;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background: var(--bg); color: var(--text); display:flex; flex-direction:column; height:100vh;}
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
.chat-container{flex:1; display:flex; margin:20px; border-radius:20px; box-shadow:0 10px 25px var(--shadow); overflow:hidden; background: var(--chat-bg);}
.chat-sidebar{width:600px; background:white; border-right:1px solid #eee; padding:20px; overflow-y:auto;}
.chat-sidebar h3{margin-bottom:15px; color: var(--rose); font-size:16px;}
.chat-sidebar ul{list-style:none;}
.chat-sidebar li{padding:10px; cursor:pointer; border-radius:10px; transition:0.3s;}
.chat-sidebar li:hover{background: var(--rose-clair); color:white;}
.chat-main{flex:1; display:flex; flex-direction:column; justify-content:space-between;}
.chat-messages{padding:20px; flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:15px; scroll-behavior:smooth;}
.bubble{max-width:75%; padding:12px 18px; border-radius:25px; box-shadow:0 4px 12px rgba(0,0,0,0.08); position:relative;}
.bubble.bot{background: #ffe6f0; align-self:flex-start; display:flex; gap:10px; align-items:flex-start;}
.bubble.bot img{width:40px; height:40px; border-radius:50%; object-fit:cover;}
.bubble.user{background: #16ffbd; color:#000; align-self:flex-end;}
.chat-input{display:flex; padding:15px; border-top:1px solid #eee; background:white;}
.chat-input input{flex:1; padding:12px 20px; border-radius:25px; border:1px solid #ccc;}
.chat-input button{margin-left:10px; padding:12px 20px; border:none; border-radius:25px; background: var(--rose); color:white; cursor:pointer; transition:0.3s;}
.chat-input button:hover{ background: var(--rose-clair);}
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
    <a href="nosoffres.php">Offres</a>
    <a href="blog.php">Blog</a>
    <a href="ai.php" class="active">Coach AI </a>
  </nav>
  <div class="header-auth">
    <span class="auth-label" onclick="openModal('login')">Connexion</span>
    <span class="auth-label" onclick="openModal('signup')">Inscription</span>
  </div>
</header>

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
<div class="chat-container">
  <div class="chat-sidebar">
    <h3>Questions fréquentes</h3>
    <ul id="faqList">
      <li data-msg="Comment postuler à une offre de stage ?">Comment postuler à une offre de stage ?</li>
      <li data-msg="Comment créer un CV sans expérience ?">Comment créer un CV sans expérience ?</li>
      <li data-msg="Puis-je postuler si je suis encore étudiant ?">Puis-je postuler si je suis encore étudiant ?</li>
      <li data-msg="Quels sont les meilleurs mois pour trouver un stage ?">Quels sont les meilleurs mois pour trouver un stage ?</li>
      <li data-msg="Qu'est-ce qu'une convention de stage ?">Qu'est-ce qu'une convention de stage ?</li>
      <li data-msg="Que faire si l'entreprise refuse de signer la convention ?">Que faire si l'entreprise refuse de signer la convention ?</li>
      <li data-msg="Quelle est la durée idéale pour un stage PFE ?">Quelle est la durée idéale pour un stage PFE ?</li>
      <li data-msg="Pas de missions pendant mon stage ?">Pas de missions pendant mon stage ?</li>
      <li data-msg="Comment demander à être embauché après le stage ?">Comment demander à être embauché après le stage ?</li>
      <li data-msg="Comment transformer mon stage en expérience sur le CV ?">Comment transformer mon stage en expérience sur le CV ?</li>
    </ul>
  </div>

  <div class="chat-main">
    <div class="chat-messages" id="chatMessages">
      <div class="bubble bot">
        <img src="loup.webp" alt="Coach IA">
        <div>Salut ! Moi c’est ton conseiller IA StageTunisie. Dis-moi, tu sais déjà quel diplôme tu aimerais préparer ou un métier que tu veux faire ? (Réponds oui ou non)</div>
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

const faqResponses = {
  "Comment postuler à une offre de stage ?":"Consulte les offres, clique sur 'Voir détails', connecte-toi et postule via ton dashboard.",
  "Comment créer un CV sans expérience ?":"Mets en avant projets académiques, compétences techniques et soft skills. Sois clair et précis.",
  "Puis-je postuler si je suis encore étudiant ?":"Oui, indique tes disponibilités dans ta lettre et ton dashboard.",
  "Quels sont les meilleurs mois pour trouver un stage ?":"Pour stages d’été commence mars/avril, pour PFE septembre à janvier.",
  "Qu'est-ce qu'une convention de stage ?":"Document légal à obtenir via université et signé par l'entreprise avant le début du stage.",
  "Que faire si l'entreprise refuse de signer la convention ?":"Ne commence jamais sans convention. Explique que c’est obligatoire pour ta protection.",
  "Quelle est la durée idéale pour un stage PFE ?":"Généralement 4 à 6 mois pour un projet concret.",
  "Pas de missions pendant mon stage ?":"Propose tes idées et montre ta proactivité, discute avec ton maître de stage.",
  "Comment demander à être embauché après le stage ?":"15 jours avant fin, demande un entretien de débriefing via ton dashboard.",
  "Comment transformer mon stage en expérience sur le CV ?":"Détaille tes missions, outils utilisés et résultats obtenus."
};
const keywordsResponses = [
  {keywords:["postuler","candidature","offre"], response:faqResponses["Comment postuler à une offre de stage ?"]},
  {keywords:["cv","expérience","projet"], response:faqResponses["Comment créer un CV sans expérience ?"]},
  {keywords:["étudiant","encore","cours"], response:faqResponses["Puis-je postuler si je suis encore étudiant ?"]},
  {keywords:["mois","meilleurs","stage"], response:faqResponses["Quels sont les meilleurs mois pour trouver un stage ?"]},
  {keywords:["convention","document","légal"], response:faqResponses["Qu'est-ce qu'une convention de stage ?"]},
];

function addBubble(msg,type){
  const bubble = document.createElement("div");
  bubble.classList.add("bubble",type);
  if(type==="bot"){
    const img = document.createElement("img");
    img.src = "loup.webp";
    img.alt = "Coach IA";
    bubble.appendChild(img);
    const div = document.createElement("div");
    div.innerText = msg;
    bubble.appendChild(div);
  } else {
    bubble.innerText = msg;
  }
  chatMessages.appendChild(bubble);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

let chatStep = 0;
let lastUserResponse = "";
faqItems.forEach(item=>{
  item.addEventListener("click", ()=>{
    addBubble(item.dataset.msg, "user");
    setTimeout(()=>addBubble(faqResponses[item.dataset.msg],"bot"),300);
  });
});
sendBtn.addEventListener("click", ()=>{
  const msg = inputBox.value.trim();
  if(!msg) return;
  addBubble(msg,"user");

  let botReply = "";

  if(chatStep === 0){
    lastUserResponse = msg.toLowerCase();
    if(lastUserResponse.includes("oui")){
      botReply = "Super ! Dis-moi maintenant quel diplôme ou métier t'intéresse ?";
      chatStep = 1;
    } else if(lastUserResponse.includes("non")){
      botReply = "Pas de souci ! Voici quelques idées de diplômes et métiers que tu peux envisager : BTS, Licences, Masters, Métiers du numérique, Commerce, Marketing, etc. Consulte les questions fréquentes à gauche pour en savoir plus.";
      chatStep = 2;
    } else {
      botReply = "Réponds simplement par oui ou non s'il te plaît.";
    }
  } else if(chatStep === 1){
    botReply = "Parfait, bon choix ! Maintenant tu peux consulter les questions fréquentes à gauche pour en savoir plus.";
    chatStep = 2;
  } else {
    const txt = msg.toLowerCase();
    let found = false;
    for(const kr of keywordsResponses){
      for(const k of kr.keywords){
        if(txt.includes(k)){
          botReply = kr.response;
          found = true;
          break;
        }
      }
      if(found) break;
    }
    if(!found) botReply = "Merci pour ta question ! Consulte ton dashboard ou les offres pour plus de détails.";
  }

  setTimeout(()=>addBubble(botReply,"bot"),300);
  inputBox.value="";
});

inputBox.addEventListener("keypress", e=>{
  if(e.key==="Enter") sendBtn.click();
});
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>