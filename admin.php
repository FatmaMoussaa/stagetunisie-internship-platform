<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';
$admin_avatar = $_SESSION['avatar'] ?? 'adminphoto.webp';

$entreprises = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");

$success_entreprise = $error_entreprise = '';
$success_offre = $error_offre = '';
$openModalEntreprise = false;
$openModalOffre = false;

if (isset($_POST['submit_entreprise'])) {
    $openModalEntreprise = true;
    $nom = trim($_POST['nom']);
    $adresse = trim($_POST['adresse']);
    $ville = trim($_POST['ville']);
    $email = trim($_POST['email']);
    $description = trim($_POST['description']);
    $secteur = trim($_POST['secteur']);
    $date_creation = $_POST['date_creation'] ?: null;
    $logo_path = 'images/logos/default-logo.png';

    if (!$nom || !$adresse || !$ville || !$email || !$description) {
        $error_entreprise = "Tous les champs sauf logo sont obligatoires.";
    } else {
        $check = pg_query_params($conn, "SELECT 1 FROM entreprises WHERE email=$1", [$email]);
        if ($check && pg_num_rows($check) > 0) {
            $error_entreprise = "Entreprise déjà existante.";
        } else {
            if (!empty($_FILES['logo_file']['name'])) {
                $dir = __DIR__ . '/images/logos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $file = time().'_'.basename($_FILES['logo_file']['name']);
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dir.$file)) {
                    $logo_path = 'images/logos/'.$file;
                }
            }
            $insert = pg_query_params($conn,
                "INSERT INTO entreprises (nom, adresse, ville, email, description, secteur, date_creation, logo)
                 VALUES ($1,$2,$3,$4,$5,$6,$7,$8)",
                [$nom,$adresse,$ville,$email,$description,$secteur,$date_creation,$logo_path]
            );
            if ($insert) { $success_entreprise = "Entreprise ajoutée avec succès !"; $_POST = []; $openModalEntreprise = false; }
            else { $error_entreprise = "Erreur lors de l'ajout."; }
        }
    }
}

if (isset($_POST['submit_offre'])) {
    $openModalOffre = true;
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $id_entreprise = $_POST['id_entreprise'];
    $niveau_etude = $_POST['niveau_etude'];
    $duree_semaine = $_POST['duree_semaine'];

    if ($titre && $description && $id_entreprise && $niveau_etude && $duree_semaine) {
        $insert = pg_query_params($conn, 
            "INSERT INTO offres_stage (titre, description, date_publication, id_entreprise, niveau_etude, duree_semaine) 
             VALUES ($1, $2, CURRENT_DATE, $3, $4, $5)",
            [$titre, $description, $id_entreprise, $niveau_etude, $duree_semaine]
        );
        if ($insert) { $success_offre = "Offre ajoutée avec succès !"; $_POST = []; $openModalOffre = false; }
        else { $error_offre = "Erreur lors de l'ajout."; }
    } else { $error_offre = "Tous les champs sont obligatoires."; }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - StageTunisie</title>
<style>
:root{
  --rose:#c34271; --rose-clair:#f070a1; --vert:#16ffbd; --vert-fonce:#12c998;
  --bg:#f8fafc; --blanc:#ffffff; --shadow: rgba(0,0,0,0.1); --shadow-hover: rgba(0,0,0,0.15);
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
body{background:var(--bg);color:#2d3e50;display: flex;
  flex-direction: column;
  min-height: 100vh; }


.header{
  display:flex; justify-content:space-between; align-items:center;
  padding:20px 40px; background:var(--blanc);
  box-shadow:0 5px 20px var(--shadow);
  position:sticky; top:0; z-index:1000;
  border-bottom-left-radius:15px; border-bottom-right-radius:15px;
}
.header .logo{font-size:28px;font-weight:700;color:var(--vert);}
.header .logo span{color:var(--rose);}
.header .user-info{display:flex;align-items:center;gap:15px;}
.header .user-info img{width:50px;height:50px;border-radius:50%;border:2px solid var(--rose);}
.header .user-info span{font-weight:500;}
.header .logout a{padding:8px 18px;background:#fff;color:var(--rose);border-radius:30px;text-decoration:none;font-weight:600;transition:0.3s;}
.header .logout a:hover{background:var(--rose);color:#fff;}


.message{padding:10px;border-radius:10px;text-align:center;font-weight:500;margin:10px 40px;}
.success{background:#d4f7dc;color:#12c998;}
.error{background:#ffe9c6;color:#d07a00;}

.dashboard{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:30px;padding:50px;}
.card{position:relative;background:var(--blanc);padding:40px 20px;border-radius:20px;box-shadow:0 10px 25px var(--shadow);text-align:center;transition:0.4s;}
.card:hover{transform:translateY(-5px);box-shadow:0 15px 35px var(--shadow-hover);}
.card-title{font-size:22px;font-weight:700;color:var(--rose-clair);}
.card-desc{font-size:14px;color:#555;margin:15px 0;}
.card-actions{margin-top:15px;display:flex;gap:10px;justify-content:center;}
.card-actions a{padding:8px 20px;border-radius:30px;text-decoration:none;font-weight:600;}
.btn-consult{background:linear-gradient(135deg,var(--vert),var(--vert-fonce));color:#fff;}
.btn-add{background:linear-gradient(135deg,var(--rose),var(--rose-clair));color:#fff;cursor:pointer;}

.modal-overlay {display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:2000;}
.modal-box {background:#fff;border-radius:20px;padding:25px;width:500px;max-width:90%;max-height:85vh;overflow-y:auto;box-shadow:0 15px 40px rgba(0,0,0,0.2);position:relative;}
.modal-box .close-btn {position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;color:#888;}
.modal-box .close-btn:hover {color:var(--rose);}
.modal-box form {display:flex;flex-direction:column;gap:12px;}
.modal-box input, .modal-box select, .modal-box textarea {padding:10px;border-radius:10px;border:1px solid #ddd;font-size:14px;}
.modal-box textarea {resize:none;}
.modal-box button {padding:12px;border:none;border-radius:25px;background:linear-gradient(135deg,var(--rose),var(--rose-clair));color:#fff;font-weight:600;cursor:pointer;}
.copyright{text-align:center;margin-top:auto;}
</style>
</head>

<body>
<header class="header">
  <div class="logo">Stage<span>Tunisie</span></div>
  <div class="user-info">
    <img src="<?= htmlspecialchars($admin_avatar) ?>">
    <span><?= htmlspecialchars($admin_nom) ?></span>
    <div class="logout"><a href="logout.php">Déconnexion</a></div>
  </div>
</header>

<?php if($error_entreprise): ?><div class="message error"><?= $error_entreprise ?></div><?php endif; ?>
<?php if($success_entreprise): ?><div class="message success"><?= $success_entreprise ?></div><?php endif; ?>
<?php if($error_offre): ?><div class="message error"><?= $error_offre ?></div><?php endif; ?>
<?php if($success_offre): ?><div class="message success"><?= $success_offre ?></div><?php endif; ?>

<div class="dashboard">

  <div class="card">
    <div class="card-title">Entreprises</div>
    <div class="card-desc">Gérez les entreprises</div>
    <div class="card-actions">
      <a href="admin_liste_entreprise.php" class="btn-consult">Consulter</a>
      <a class="btn-add" onclick="openModalEntreprise()">Ajouter</a>
    </div>
  </div>
  <div class="card">
    <div class="card-title">Offres</div>
    <div class="card-desc">Gérez les offres</div>
    <div class="card-actions">
      <a href="admin_offres.php" class="btn-consult">Consulter</a>
      <a class="btn-add" onclick="openModalOffre()">Ajouter</a>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Utilisateurs</div>
    <div class="card-desc">Gérez les utilisateurs</div>
    <div class="card-actions">
      <a href="admin_utilisateurs.php" class="btn-consult">Consulter</a>
      
    </div>
  </div>
  <div class="card">
    <div class="card-title">Candidatures</div>
    <div class="card-desc">Gérez les candidatures</div>
    <div class="card-actions">
      <a href="admin_condidatures.php" class="btn-consult">Consulter</a>
     
    </div>
  </div>
</div>
<div id="modal-overlay-entreprise" class="modal-overlay">
  <div class="modal-box">
    <span class="close-btn" onclick="closeModalEntreprise()">×</span>
    <h2 style="text-align:center;color:#c34271;">Ajouter une entreprise</h2>
    <form method="POST" enctype="multipart/form-data">
      <input name="nom" placeholder="Nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
      <input name="adresse" placeholder="Adresse" required value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
      <input name="ville" placeholder="Ville" required value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>">
      <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <textarea name="description" placeholder="Description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      <input name="secteur" placeholder="Secteur" value="<?= htmlspecialchars($_POST['secteur'] ?? '') ?>">
      <input type="date" name="date_creation" value="<?= htmlspecialchars($_POST['date_creation'] ?? '') ?>">
      <input type="file" name="logo_file">
      <button name="submit_entreprise">Ajouter</button>
    </form>
  </div>
</div>
<div id="modal-overlay-offre" class="modal-overlay">
  <div class="modal-box">
    <span class="close-btn" onclick="closeModalOffre()">×</span>
    <h2 style="text-align:center;color:#c34271;">Ajouter une offre</h2>
    <form method="POST">
      <input name="titre" placeholder="Titre" required value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">
      <textarea name="description" placeholder="Description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      <select name="id_entreprise" required>
        <option value="">Sélectionner une entreprise</option>
        <?php while($e = pg_fetch_assoc($entreprises)) : ?>
          <option value="<?= $e['id_entreprise'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
        <?php endwhile; ?>
      </select>
      <select name="niveau_etude" required>
        <option value="">Sélectionner un niveau</option>
        <option value="Bac+1">Bac+1</option>
        <option value="Bac+2">Bac+2</option>
        <option value="Licence">Licence</option>
        <option value="Master">Master</option>
        <option value="Doctorat">Doctorat</option>
      </select>
      <input type="number" name="duree_semaine" min="1" placeholder="Durée (semaines)" value="<?= htmlspecialchars($_POST['duree_semaine'] ?? '') ?>" required>
      <button name="submit_offre">Ajouter</button>
    </form>
  </div>
</div>

<script>
function openModalEntreprise(){document.getElementById('modal-overlay-entreprise').style.display='flex';}
function closeModalEntreprise(){document.getElementById('modal-overlay-entreprise').style.display='none';}

function openModalOffre(){document.getElementById('modal-overlay-offre').style.display='flex';}
function closeModalOffre(){document.getElementById('modal-overlay-offre').style.display='none';}

<?php if($openModalEntreprise): ?>openModalEntreprise();<?php endif; ?>
<?php if($openModalOffre): ?>openModalOffre();<?php endif; ?>
</script>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>