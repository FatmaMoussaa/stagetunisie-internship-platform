<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$etudiant_nom = $_SESSION['nom'] ?? 'Étudiant';
$avatar = $_SESSION['avatar'] ?? 'inconnu.png';

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

$check = pg_query_params($conn,
    "SELECT 1 FROM candidatures WHERE id_user=$1 AND id_stage=$2",
    [$id_user, $id_stage]
);
$deja_postule = pg_num_rows($check) > 0;
if (isset($_POST['postuler']) && !$deja_postule) {
    $cv_path = '';
    $lettre_path = '';

    if (!empty($_FILES['cv']['name'])) {
        $cv_path = 'uploads/' . time().'_'.basename($_FILES['cv']['name']);
        move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path);
    }

    if (!empty($_FILES['lettre']['name'])) {
        $lettre_path = 'uploads/' . time().'_'.basename($_FILES['lettre']['name']);
        move_uploaded_file($_FILES['lettre']['tmp_name'], $lettre_path);
    }

    $insert = pg_query_params($conn,
        "INSERT INTO candidatures 
        (date_postulation, statut, id_user, id_stage, cv_url, lettre_motivation)
        VALUES (CURRENT_DATE, 'en_attente', $1, $2, $3, $4)",
        [$id_user, $id_stage, $cv_path, $lettre_path]
    );

    if ($insert) {
        $success = "Vous avez postulé avec succès !";
        $deja_postule = true;
    } else {
        $error = "Erreur lors de la candidature.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($offre['titre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>













.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:100; }
.modal { background:white; padding:40px; border-radius:20px; width:400px; max-width:90%; box-shadow:0 20px 50px rgba(0,0,0,0.1); position:relative; }
.modal h2 { text-align:center; color: var(--rose); margin-bottom:25px; }
.modal input { width:100%; padding:12px 15px; border-radius:12px; border:1px solid #ddd; margin-bottom:15px; outline:none; transition:0.2s; }
.modal input:focus { border-color: var(--rose); box-shadow:0 0 10px rgba(195,66,113,0.2); }
.modal button { width:100%; padding:14px; border:none; border-radius:30px; background: var(--rose); color:white; font-weight:600; cursor:pointer; transition:0.3s; }
.modal button:hover { transform:translateY(-2px); box-shadow:0 12px 25px rgba(0,0,0,0.15); }
.modal .close { position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px; color:#999; transition:0.3s; }
.modal .close:hover { color:#333; }
:root{
    --rose:#c34271; --vert:#16ffbd; --vert-fonce:#12c998;
    --bg:#f8fafc; --blanc:#fff; --shadow:rgba(0,0,0,0.08); --text:#2d3e50;--rose-clair: #f070a1;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);color:var(--text);line-height:1.6;}
a{text-decoration:none;}
.header{
    display:flex;justify-content:space-between;align-items:center;
    padding:20px 40px;background:var(--blanc);
    box-shadow:0 5px 15px var(--shadow);
    border-radius:0 0 20px 20px;
}
.header .logo{font-size:28px;font-weight:700;color:var(--vert);}
.header .logo span{color:var(--rose);}
.user-info{display:flex;align-items:center;gap:15px;}
.user-info img{width:45px;height:45px;border-radius:50%;border:2px solid var(--rose);transition:0.3s;}
.user-info img:hover{transform:scale(1.1);}
.logout a{padding:8px 18px;background:#fff;color:var(--rose);border-radius:30px;font-weight:600;transition:0.3s;}
.logout a:hover{background:var(--rose-clair);color:#fff;}

/* Container */
.container{max-width:850px;margin:60px auto;padding:0 15px;display:flex;flex-direction:column;gap:25px;}

.chat-container{flex:1; display:flex; margin:20px; border-radius:20px; box-shadow:0 10px 25px var(--shadow); overflow:hidden; background: var(--chat-bg);}
.chat-sidebar{width:350px; background:white; border-right:1px solid #eee; padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:20px;}
.chat-sidebar .offre-header{display:flex; flex-direction:column; align-items:center; gap:10px;}
.chat-sidebar .offre-header img{width:80px; height:80px; border-radius:50%; border:2px solid var(--vert);}
.chat-sidebar .offre-header h2{color:var(--rose); font-size:18px; text-align:center;}
.chat-sidebar h3{margin-bottom:10px; color: var(--rose); font-size:16px;}
.chat-sidebar ul{list-style:none;}
.chat-sidebar li{padding:10px; cursor:pointer; border-radius:10px; transition:0.3s;}
.chat-sidebar li:hover{background: var(--rose-clair);color:white;}

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
.auth-label{ padding:12px 20px; border:none; border-radius:25px; background: var(--rose); color:white; cursor:pointer; transition:0.3s;margin-left:20px;}

/* Formulaire postuler */
.form-postuler{display:flex;flex-direction:column;gap:12px;padding:15px;background:var(--blanc);border-radius:18px;box-shadow:0 6px 20px var(--shadow);}
.form-postuler input[type="file"]{
    padding:10px;border-radius:12px;border:1px solid #ccc;background:#fafafa;cursor:pointer;transition:0.3s;
}
.form-postuler input[type="file"]:hover{border-color:var(--vert);}
.btn{padding:12px;border:none;border-radius:25px;background:linear-gradient(135deg,var(--vert),var(--rose));color:white;font-weight:600;cursor:pointer;transition:all 0.3s;}
.btn:hover{transform:translateY(-2px) scale(1.02);box-shadow:0 6px 16px rgba(18,201,152,0.3);}

.msg {
    padding: 10px 15px;
    border-radius: 12px;
    margin-bottom: 15px;
    font-size: 14px;
    text-align: center;
}

.msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.msg.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.msg.info    { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
.copyright{text-align:center;}
</style>
</head>
<body>

<header class="header">
    <div class="logo">Stage<span>Tunisie</span></div>
    <div class="user-info">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
        <span><?= htmlspecialchars($etudiant_nom) ?></span>
        <div class="logout"><a href="etudiant.php">Dashboard</a></div>
        <div class="logout"><a href="logout.php">Déconnexion</a></div>
    </div>
</header>



    
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
        

        
        <!-- Bouton pour ouvrir le modal -->
<?php if(isset($_SESSION['user_id'])): ?>
    <button class="auth-label" onclick="openModal('postuler')">Postuler</button>
<?php else: ?>
    <button class="auth-label" onclick="openModal('login')">Se connecter pour postuler</button>
<?php endif; ?>

<div class="modal-bg" id="modal-postuler">
  <div class="modal">
    <span class="close" onclick="closeModal('postuler')">&times;</span>
    <h2>Postuler à l'offre</h2>

    <!-- Messages -->
    <?php if(isset($success)): ?>
        <div class="msg success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if(isset($deja_postule) && $deja_postule): ?>
        <div class="msg info">Vous avez déjà postulé à cette offre</div>
    <?php else: ?>
        <form class="form-postuler" method="POST" enctype="multipart/form-data">
            <input type="file" name="cv" required>
            <input type="file" name="lettre" required>
            <button class="btn" type="submit" name="postuler">Envoyer ma candidature</button>
        </form>
    <?php endif; ?>
  </div>
</div>
       

</div>
<script>
<?php if(isset($success) || isset($error) || (isset($deja_postule) && $deja_postule)): ?>
    document.addEventListener("DOMContentLoaded", function(){
        document.getElementById('modal-postuler').style.display = 'flex';
    });
<?php endif; ?>
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>