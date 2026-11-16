<?php
session_start();
require_once 'config.php';

// Vérifie rôle étudiant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
    exit;
}

$etudiant_nom = $_SESSION['nom'] ?? 'Étudiant';
$id_user = $_SESSION['user_id'];

// Récupérer infos utilisateur
$res = pg_query_params($conn, "SELECT nom, email FROM utilisateurs WHERE id_user=$1", array($id_user));
$user = pg_fetch_assoc($res);
if (!$user) die("Utilisateur introuvable.");

// Traitement mise à jour profil
if (isset($_POST['update_profil'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $update = pg_query_params($conn,
        "UPDATE utilisateurs SET nom=$1, email=$2 WHERE id_user=$3",
        array($nom, $email, $id_user)
    );
    if ($update) {
        $success_profil = "Profil mis à jour avec succès.";
        $_SESSION['nom'] = $nom;
        $user['nom'] = $nom;
        $user['email'] = $email;
    } else {
        $error_profil = "Erreur lors de la mise à jour.";
    }
}

// Récupérer entreprises pour avis
$entreprises_result = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");
$entreprises = [];
while($row = pg_fetch_assoc($entreprises_result)){
    $entreprises[] = $row;
}

// Messages Avis
$success_avis = $_SESSION['success_avis'] ?? '';
$error_avis = $_SESSION['error_avis'] ?? '';
unset($_SESSION['success_avis'], $_SESSION['error_avis']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Étudiant - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --rose: #c34271; --rose-clair: #f070a1; --vert: #16ffbd; --vert-fonce: #12c998;
    --bg: #f8fafc; --blanc:#fff; --shadow: rgba(0,0,0,0.1); --text:#2d3e50;
}
/* RESET */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { background: var(--bg); color: var(--text);display: flex;
  flex-direction: column;
  min-height: 100vh; }

/* HEADER */
.header { display:flex; justify-content:space-between; align-items:center; padding:20px 40px; background: var(--blanc); box-shadow:0 5px 20px var(--shadow); position:sticky; top:0; z-index:1000; border-bottom-left-radius:15px; border-bottom-right-radius:15px; }
.header .logo { font-size:28px; font-weight:700; color: var(--vert); }
.header .logo span { color: var(--rose); }
.header .user-info { display:flex; align-items:center; gap:15px; }
.header .user-info img { width:40px; height:40px; border-radius:50%; border:2px solid var(--rose); object-fit:cover; }
.header .logout a { padding:8px 18px; border-radius:30px; font-weight:600; text-decoration:none; color: var(--rose); background:#fff; transition:0.3s; }
.header .logout a:hover { background: var(--rose); color:#fff; }

/* DASHBOARD */
.dashboard-container { display:flex; min-height: calc(100vh - 80px); }
.main-content { flex:1; padding:30px 40px; }
.welcome { margin-bottom:25px; font-size:20px; font-weight:600; color: var(--rose); }
.cards-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:15px; margin-bottom:30px; }
.card-small { background:var(--blanc); padding:20px; border-radius:15px; box-shadow:0 8px 20px var(--shadow); display:flex; flex-direction:column; justify-content:space-between; transition:0.3s; }
.card-small:hover { transform:translateY(-3px); box-shadow:0 12px 25px var(--shadow); }
.card-title { font-weight:600; color:var(--rose-clair); margin-bottom:5px; font-size:14px; }
.card-sub { font-size:12px; color:#555; margin-bottom:10px; }
.card-link { display:inline-block; margin-top:auto; padding:8px 18px; border-radius:25px; font-weight:600; text-decoration:none; color:#fff; background: linear-gradient(135deg, #f070a1,#c34271); text-align:center; transition:0.4s; }
.card-link:hover { background: linear-gradient(135deg,#16ffbd,#c34271); transform:translateY(-3px) scale(1.05); }

/* MODAL */
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:2000; }
.modal-content { background:#fff; padding:30px; border-radius:20px; width:95%; max-width:480px; position:relative; box-shadow:0 15px 40px rgba(0,0,0,0.15); }
.close-modal { position:absolute; top:12px; right:15px; font-size:20px; cursor:pointer; color:#888; }
.close-modal:hover { color:var(--rose); }

/* FORMULAIRE */
.form-group { display:flex; flex-direction:column; margin-bottom:18px; }
.form-group label { font-size:13px; font-weight:500; margin-bottom:6px; color:#555; }
.form-control, .form-control textarea { width:100%; padding:12px; border-radius:12px; border:1px solid #ddd; font-size:14px; }
textarea.form-control { resize:none; }
.btn-submit { width:100%; padding:12px; border:none; border-radius:30px; background: linear-gradient(135deg,#16ffbd,#c34271); color:#fff; font-weight:600; cursor:pointer; margin-top:10px; }
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(18,201,152,0.4); }

.success-msg { background:#d4f7dc; color:#12c998; padding:12px 20px; border-radius:25px; text-align:center; margin-bottom:15px; font-weight:500; }
.error-msg { background:#fcdcdc; color:#c34242; padding:12px 20px; border-radius:25px; text-align:center; margin-bottom:15px; font-weight:500; }

/* DROPDOWN */
.custom-dropdown { position: relative; width: 100%; font-size: 14px; margin-bottom: 18px;}
.dropdown-selected { padding: 12px; border-radius: 12px; background: #f9fafc; border: 1px solid #ddd; cursor: pointer; transition: 0.3s; }
.dropdown-selected:hover { border-color: var(--rose); }
.dropdown-options { position: absolute; width: 100%; background: white; border-radius: 12px; margin-top: 5px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: none; max-height: 200px; overflow-y: auto; z-index: 1000; }
.dropdown-item { padding: 12px; cursor: pointer; transition: 0.2s; }
.dropdown-item:hover { background: linear-gradient(135deg, #f070a1, #c34271); color: white;}
.custom-dropdown.active .dropdown-options { display: block; }

/* NOTE étoiles */
.rating { display: flex; gap: 8px; font-size: 22px; cursor: pointer; }
.rating span { color: #ddd; transition: 0.2s; }
.rating span.active { color: #f070a1; }
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
@media(max-width:900px){ .dashboard-container{flex-direction:column; } }
.copyright{text-align:center;margin-top: auto;}
</style>
</head>
<body>

<header class="header">
    <div class="logo">Stage<span>Tunisie</span></div>
    <div class="user-info">
        <img src="inconnu.png" alt="Avatar Étudiant">
        <span><?= htmlspecialchars($etudiant_nom); ?></span>
        <div class="logout"><a href="etudiant.php">Dashboard</a></div>
        <div class="logout"><a href="logout.php">Déconnexion</a></div>
    </div>
</header>

<div class="dashboard-container">
    <div class="main-content">
        <div class="welcome">Bienvenue, <?= htmlspecialchars($etudiant_nom); ?> !</div>
    
        <div class="cards-row">
            <div class="card-small">
                <p class="card-title">Offres disponibles</p>
                <span class="card-sub">Consultez toutes les offres de stage</span>
                <a href="etudiant_offres.php" class="card-link">Voir →</a>
            </div>
            <div class="card-small">
                <p class="card-title">Mes candidatures</p>
                <span class="card-sub">Suivez vos candidatures</span>
                <a href="etudiant_condidatures.php" class="card-link">Voir →</a>
            </div>
            <div class="card-small">
                <p class="card-title">Profil</p>
                <span class="card-sub">Modifier vos informations</span>
                <a href="javascript:void(0)" class="card-link" onclick="document.getElementById('profilModal').style.display='flex'">Voir →</a>
            </div>
            <div class="card-small">
                <p class="card-title">Donner un avis</p>
                <span class="card-sub">Évaluez une entreprise et laissez un commentaire</span>
                <a href="javascript:void(0)" class="card-link" onclick="document.getElementById('avisModal').style.display='flex'">Voir →</a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PROFIL -->
<div id="profilModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="document.getElementById('profilModal').style.display='none'">&times;</span>
        <h2 style="text-align:center;color:var(--rose);margin-bottom:15px;">Mon profil</h2>

        <?php if(isset($success_profil)) echo "<p class='success-msg'>$success_profil</p>"; ?>
        <?php if(isset($error_profil)) echo "<p class='error-msg'>$error_profil</p>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($user['nom']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>
            <button type="submit" name="update_profil" class="btn-submit">Mettre à jour</button>
        </form>
    </div>
</div>

<!-- MODAL AVIS -->
<div id="avisModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="document.getElementById('avisModal').style.display='none'">&times;</span>
        <h2 style="text-align:center;color:var(--rose);margin-bottom:15px;">Donner un avis</h2>

        <?php if($error_avis): ?>
            <p class="error-msg"><?= htmlspecialchars($error_avis) ?></p>
        <?php endif; ?>
        <?php if($success_avis): ?>
            <p class="success-msg"><?= htmlspecialchars($success_avis) ?></p>
        <?php endif; ?>

        <form method="POST" action="ajouter_avis.php">
            <div class="form-group">
                <label>Entreprise</label>
                <div class="custom-dropdown">
                    <div class="dropdown-selected">Sélectionner une entreprise</div>
                    <div class="dropdown-options">
                        <?php foreach($entreprises as $e): ?>
                            <div class="dropdown-item" data-value="<?= $e['id_entreprise'] ?>"><?= htmlspecialchars($e['nom']) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="id_entreprise" required>
                </div>
            </div>

            <div class="form-group">
                <label>Note</label>
                <div class="rating">
                    <span data-value="1">★</span>
                    <span data-value="2">★</span>
                    <span data-value="3">★</span>
                    <span data-value="4">★</span>
                    <span data-value="5">★</span>
                </div>
                <input type="hidden" name="note" required>
            </div>

            <div class="form-group">
                <label>Commentaire</label>
                <textarea name="commentaire" class="form-control" rows="4" placeholder="Votre avis..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">Envoyer</button>
        </form>
    </div>
</div>

<script>
// DROPDOWN
const dropdown = document.querySelector(".custom-dropdown");
if(dropdown){
    const selected = dropdown.querySelector(".dropdown-selected");
    const options = dropdown.querySelectorAll(".dropdown-item");
    const hiddenInput = dropdown.querySelector("input");
    selected.addEventListener("click", ()=>{ dropdown.classList.toggle("active"); });
    options.forEach(option=>{
        option.addEventListener("click", ()=>{
            selected.textContent = option.textContent;
            hiddenInput.value = option.dataset.value;
            dropdown.classList.remove("active");
        });
    });
    document.addEventListener("click", e=>{ if(!dropdown.contains(e.target)) dropdown.classList.remove("active"); });
}

// NOTE étoiles
const stars = document.querySelectorAll(".rating span");
const noteInput = document.querySelector("input[name='note']");
stars.forEach(star=>{
    star.addEventListener("click", ()=>{
        let value = star.dataset.value;
        noteInput.value = value;
        stars.forEach(s=>s.classList.remove("active"));
        for(let i=0;i<value;i++) stars[i].classList.add("active");
    });
});

// ESC pour fermer modals
document.addEventListener("keydown", e=>{
    if(e.key==="Escape"){
        document.getElementById('profilModal').style.display='none';
        document.getElementById('avisModal').style.display='none';
    }
});
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
<?php
// Afficher modal si erreur ou succès
if(isset($success_profil) || isset($error_profil)){
    echo "<script>document.getElementById('profilModal').style.display='flex';</script>";
}
if($success_avis || $error_avis){
    echo "<script>document.getElementById('avisModal').style.display='flex';</script>";
}
?>

</body>
</html>