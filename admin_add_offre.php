<?php
session_start();
require_once 'config.php';

// Vérifie admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Entreprises pour le select
$entreprises = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");

$success = '';
$error = '';

if (isset($_POST['submit'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $id_entreprise = $_POST['id_entreprise'];
    $niveau_etude = $_POST['niveau_etude'];
    $duree_semaine = $_POST['duree_semaine'];
    
    if ($titre && $description && $id_entreprise && $niveau_etude && $duree_semaine) {
        $insert = pg_query_params($conn, 
            "INSERT INTO offres_stage (titre, description, date_publication, id_entreprise, niveau_etude, duree_semaine) 
             VALUES ($1, $2, CURRENT_DATE, $3, $4, $5)",
            array($titre, $description, $id_entreprise, $niveau_etude, $duree_semaine)
        );
    
        if ($insert) {
            $success = "Offre ajoutée avec succès.";
        } else {
            $error = "Erreur lors de l'ajout.";
        }
    } else {
        $error = "Tous les champs sont obligatoires.";
    }
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter une offre - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --rose: #c34271; --rose-clair:#f070a1;
    --vert:#16ffbd; --vert-fonce:#12c998;
    --bg:#f8fafc; --blanc:#ffffff;
    --shadow: rgba(0,0,0,0.1); --text:#2d3e50;
}
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
body { background: var(--bg); color: var(--text); }

/* HEADER */
.header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:20px 40px;
    background: var(--blanc);
    box-shadow:0 5px 20px var(--shadow);
    position:sticky;
    top:0;
    z-index:1000;
    border-bottom-left-radius:15px;
    border-bottom-right-radius:15px;
}
.header .logo { font-size:28px; font-weight:700; color:var(--vert); }
.header .logo span { color:var(--rose); font-weight:700; }
.header .user-info { display:flex; align-items:center; gap:15px; }
.header .user-info img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--rose); }
.header .user-info span { font-weight:500; }
.header .logout a {
    padding:8px 18px; background:#fff; color:var(--rose);
    border-radius:30px; font-weight:600; text-decoration:none; transition:0.3s;
}
.header .logout a:hover { background:var(--rose); color:#fff; }

/* CARTE CENTRÉE MINI */
.wrapper { display:flex; justify-content:center; padding:40px; }
.form-section {
    width:400px;
    background: var(--blanc);
    padding:35px;
    border-radius:20px;
    box-shadow:0 10px 30px var(--shadow);
}
.form-section h2 { text-align:center; color: var(--rose); margin-bottom:25px; }
.input-group { margin-bottom:15px; }
.input-group label { display:block; margin-bottom:5px; font-size:14px; }
.input-group input, .input-group select, .input-group textarea {
    width:100%; padding:12px; border-radius:12px; border:1px solid #ddd; transition:0.3s; font-family:'Inter',sans-serif;
}
.input-group input:focus, .input-group select:focus, .input-group textarea:focus {
    border-color: var(--rose);
    box-shadow:0 0 8px rgba(195,66,113,0.2);
    outline:none;
}
textarea { resize:none; }

.btn {
    width:100%; padding:12px; border:none; border-radius:30px;
    background: linear-gradient(135deg, var(--rose), var(--rose-clair));
    color:white; font-weight:600; cursor:pointer; transition:0.3s;
}
.btn:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,0.15); }

.message { padding:10px; border-radius:10px; text-align:center; margin-bottom:15px; }
.error { background:#ffe9c6; color:#d07a00; }
.success { background:#d4f7dc; color:var(--vert-fonce); }

@media(max-width:500px){
    .form-section { width:90%; padding:25px; }
    .header { flex-direction:column; align-items:flex-start; gap:10px; }
}
</style>
</head>
<body>

<div class="header">
    <div class="logo">Stage<span>Tunisie</span></div>
    <div class="user-info">
        <img src="adminphoto.webp" alt="Avatar Admin">
        <span><?= htmlspecialchars($admin_nom) ?></span>
        <div class="logout"><a href="admin.php">Dashboard</a></div>
        <div class="logout"><a href="logout.php">Déconnexion</a></div>
    </div>
</div>

<div class="wrapper">
    <div class="form-section">
        <h2>Ajouter une offre</h2>

        <?php if($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Titre</label>
                <input type="text" name="titre" required>
            </div>

            <div class="input-group">
                <label>Description</label>
                <textarea name="description" rows="5" required></textarea>
            </div>

            <div class="input-group">
                <label>Entreprise</label>
                <select name="id_entreprise" required>
                    <option value="">Sélectionner une entreprise</option>
                    <?php while($e = pg_fetch_assoc($entreprises)) : ?>
                        <option value="<?= $e['id_entreprise']; ?>"><?= htmlspecialchars($e['nom']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-group">
                <label>Niveau d'étude</label>
                <select name="niveau_etude" required>
                    <option value="">Sélectionner un niveau</option>
                    <option value="Bac+1">Bac+1</option>
                    <option value="Bac+2">Bac+2</option>
                    <option value="Licence">Licence</option>
                    <option value="Master">Master</option>
                    <option value="Doctorat">Doctorat</option>
                </select>
            </div>

            <div class="input-group">
                <label>Durée du stage (en semaines)</label>
                <input type="number" name="duree_semaine" min="1" required>
            </div>

            <button class="btn" type="submit" name="submit">Ajouter l'offre</button>
        </form>
    </div>
</div>

</body>
</html>