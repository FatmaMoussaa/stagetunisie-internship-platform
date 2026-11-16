<?php
session_start();
require_once 'config.php';

// Vérifie si admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = $success = '';


if (isset($_POST['submit'])) {
    $nom = trim($_POST['nom']);
    $adresse = trim($_POST['adresse']);
    $ville = trim($_POST['ville']);
    $email = trim($_POST['email']);
    $description = trim($_POST['description'] ?? '');
    $secteur = trim($_POST['secteur'] ?? '');
    $date_creation = trim($_POST['date_creation'] ?? null);
    $logo_path = 'images/logos/default-logo.png'; // par défaut

    // Champs obligatoires
    if (empty($nom) || empty($adresse) || empty($ville) || empty($email) || empty($description)) {
        $error = "Tous les champs sauf logo sont obligatoires.";
    } else {
        // Vérifie email unique
        $check = pg_query_params($conn, "SELECT 1 FROM entreprises WHERE email=$1", [$email]);
        if ($check && pg_num_rows($check) > 0) {
            $error = "Cette entreprise existe déjà.";
        } else {
            // Upload logo depuis PC
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
                $upload_dir = __DIR__ . '/images/logos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $filename = time() . '_' . basename($_FILES['logo_file']['name']);
                $target_file = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_file)) {
                    $logo_path = 'images/logos/' . $filename;
                } else {
                    $error = "Erreur lors de l'upload du logo.";
                }
            }
            // Logo depuis URL
            elseif (!empty($_POST['logo_url']) && filter_var($_POST['logo_url'], FILTER_VALIDATE_URL)) {
                $logo_path = trim($_POST['logo_url']);
            }

            // Insert dans la DB
            if (!$error) {
                $insert = pg_query_params($conn,
                    "INSERT INTO entreprises (nom, adresse, ville, email, description, secteur, date_creation, logo)
                     VALUES ($1,$2,$3,$4,$5,$6,$7,$8)",
                    [$nom, $adresse, $ville, $email, $description, $secteur, $date_creation, $logo_path]
                );

                if ($insert) $success = "Entreprise ajoutée avec succès !";
                else $error = "Erreur lors de l'ajout dans la DB.";
            }
        }
    }
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajouter entreprise - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --rose:#c34271; --rose-clair:#f070a1; --vert:#16ffbd; --vert-fonce:#12c998;
    --bg:#f8fafc; --blanc:#ffffff; --shadow:rgba(0,0,0,0.1); --text:#2d3e50;
}
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { background: var(--bg); color: var(--text); }

/* Header */
.header { display:flex; justify-content:space-between; align-items:center; padding:20px 40px; background:#fff; box-shadow:0 5px 20px var(--shadow); position:sticky; top:0; z-index:1000; border-bottom-left-radius:15px; border-bottom-right-radius:15px; }


/* TITRE STAGETUNISIE EXACTEMENT COMME INDEX */
.logo  {
    font-size: 28px;       /* taille du texte */
    font-weight: 700;      /* gras du texte */
    color: var(--vert);    /* vert principal */
    text-decoration: none; /* pas de soulignement */
}

.logo span {
    color: var(--rose);    /* partie “Tunisie” en rose */
    font-weight: 700;      /* poids plus léger */
}






/* CHAMPS AJOUTÉS : Description, Secteur, Date de création */
.input-group textarea,
.input-group input[type="date"] {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
    transition: 0.3s;
    font-family: 'Inter', sans-serif;
    resize: vertical; /* seulement pour textarea */
}

.input-group textarea:focus,
.input-group input[type="date"]:focus {
    border-color: var(--rose);
    box-shadow: 0 0 8px rgba(195,66,113,0.2);
    outline: none;
}

/* Placeholder léger */
.input-group input::placeholder,
.input-group textarea::placeholder {
    color: #aaa;
    font-size: 13px;
}


.header .user-info { display:flex; align-items:center; gap:15px; }
.header .user-info img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--rose); }
.header .user-info span { font-weight:500; }
.header .logout a { padding:8px 18px; background:#fff; color:var(--rose); border-radius:30px; font-weight:600; text-decoration:none; transition:0.3s; }
.header .logout a:hover { background:var(--rose); color:#fff; }

/* Wrapper & mini carte */
.wrapper { display:flex; flex-direction:column; align-items:center; padding:40px 20px; }
.form-section { width:100%; max-width:500px; background: var(--blanc); padding:30px 25px; border-radius:20px; box-shadow:0 10px 30px var(--shadow); }
.form-section h2 { text-align:center; color: var(--rose); margin-bottom:20px; }

/* Messages */
.message { padding:10px; border-radius:10px; text-align:center; margin-bottom:15px; font-weight:500; }
.error { background:#ffe9c6; color:#d07a00; }
.success { background:#d4f7dc; color:var(--vert-fonce); }

/* Logo preview */
.logo-preview { display:block; width:70px; height:70px; border-radius:50%; object-fit:cover; margin:0 auto 15px auto; border:1px solid #ddd; }

/* Form fields */
.input-group { margin-bottom:15px; }
.input-group label { display:block; margin-bottom:5px; font-size:14px; font-weight:500; }
.input-group input[type="text"], .input-group input[type="email"], .input-group input[type="file"] { width:100%; padding:12px; border-radius:12px; border:1px solid #ddd; transition:0.3s; font-size:14px; }
.input-group input:focus { border-color: var(--rose); box-shadow:0 0 8px rgba(195,66,113,0.2); outline:none; }

/* Bouton */
.btn { width:100%; padding:12px; border:none; border-radius:30px; background: linear-gradient(135deg, var(--rose), var(--rose-clair)); color:white; font-weight:600; cursor:pointer; transition:0.3s; }
.btn:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,0.15); }

/* Lien retour */
.back-link { display:inline-block; margin-top:15px; text-decoration:none; color: var(--vert-fonce); font-weight:500; }
.back-link:hover { color: var(--rose); }

/* Responsive */
@media(max-width:600px){
    .form-section { padding:20px; }
    .logo-preview { width:60px; height:60px; }
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
        <h2>Ajouter une entreprise</h2>

      

        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="input-group">
                <label>Adresse</label>
                <input type="text" name="adresse" required>
            </div>
            <div class="input-group">
                <label>Ville</label>
                <input type="text" name="ville" required>
            </div>
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
    <label>Description</label>
    <textarea name="description" rows="4" required></textarea>
</div>

<div class="input-group">
    <label>Secteur</label>
    <input type="text" name="secteur" placeholder="Ex: IT, Banque, Industrie...">
</div>

<div class="input-group">
    <label>Date de création</label>
    <input type="date" name="date_creation">
</div>

            <div class="input-group" > <label>Logo depuis PC</label> <input type="file" name="logo_file" accept="image/*"> </div>



            <div class="input-group">
                <label>Logo depuis URL (optionnel)</label>
                <input type="text" name="logo_url" placeholder="https://exemple.com/logo.png">
            </div>

            <button class="btn" type="submit" name="submit">Ajouter l’entreprise</button>
        </form>

       
    </div>
</div>

</body>
</html>