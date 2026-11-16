<?php
session_start();
require_once 'config.php';

// Vérifie admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';

// Messages
$success = '';
$error = '';

// 🔴 SUPPRESSION via POST (comme pour entreprises)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {

    $id_del = (int)$_POST['delete'];

    $res = pg_query_params($conn,
        "DELETE FROM offres_stage WHERE id_stage=$1",
        [$id_del]
    );

    if ($res) {
        $success = "Offre supprimée avec succès.";
    } else {
        $error = "Erreur lors de la suppression.";
    }
}

// 🔴 AJOUT / MODIFICATION (uniquement si ce n'est pas une suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {

    $id_stage = $_POST['id_stage'] ?? 0;
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id_entreprise = (int)($_POST['id_entreprise'] ?? 0);
    $niveau_etude = $_POST['niveau_etude'] ?? '';
    $duree_semaine = (int)($_POST['duree_semaine'] ?? 0);

    if (!$titre || !$description || !$id_entreprise || !$niveau_etude || !$duree_semaine) {
        $error = "Tous les champs sont obligatoires.";
    } else {

        if ($id_stage > 0) {
            // Modification
            $res = pg_query_params($conn,
                "UPDATE offres_stage 
                 SET titre=$1, description=$2, id_entreprise=$3, niveau_etude=$4, duree_semaine=$5 
                 WHERE id_stage=$6",
                [$titre, $description, $id_entreprise, $niveau_etude, $duree_semaine, $id_stage]
            );

            $success = $res ? "Offre modifiée avec succès." : "Erreur lors de la modification.";

        } else {
            // Ajout
            $res = pg_query_params($conn,
                "INSERT INTO offres_stage 
                (titre, description, id_entreprise, niveau_etude, duree_semaine, date_publication) 
                VALUES ($1,$2,$3,$4,$5,NOW())",
                [$titre, $description, $id_entreprise, $niveau_etude, $duree_semaine]
            );

            $success = $res ? "Offre ajoutée avec succès." : "Erreur lors de l'ajout.";
        }
    }
}

// 🔴 Récupération données
$entreprises = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");

$offres = pg_query($conn, "
    SELECT o.id_stage, o.titre, o.description, o.date_publication, o.niveau_etude, o.duree_semaine,
           e.nom AS entreprise, o.id_entreprise
    FROM offres_stage o
    JOIN entreprises e ON o.id_entreprise=e.id_entreprise
    ORDER BY o.date_publication DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Offres</title>

<style>
:root{
  --rose:#c34271; --rose-clair:#f070a1; --vert:#16ffbd; --vert-fonce:#12c998;
  --bg:#f8fafc; --blanc:#ffffff; --shadow: rgba(0,0,0,0.1); --shadow-hover: rgba(0,0,0,0.15);
}

/* BODY + HEADER */
body{margin:0;font-family:Poppins,sans-serif;background:var(--bg);}
/* Header */
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

.cards-container{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    gap:20px;
    padding:20px;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

.fiche{
    border-bottom:1px solid #eee;
    margin-bottom:10px;
    padding-bottom:10px;
}

.card h3{color:var(--rose);}

.card form{
    display:flex;
    flex-direction:column;
    gap:6px;
    margin-top:10px;
}

.card input,
.card textarea,
.card select{
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
}

.cards-container{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;padding:20px;}
.card{background:white;border-radius:15px;padding:15px;box-shadow:0 5px 15px var(--shadow);}
.card h3{color:var(--rose);}
.card form{display:flex;flex-direction:column;gap:6px;margin-top:10px;}
.card input,.card textarea{padding:8px;border-radius:8px;border:1px solid #ccc;}
.card button{padding:8px;border:none;border-radius:8px;cursor:pointer;background:var(--vert);color:white;font-weight:600;}
.card button.delete{background:var(--rose);}
.message{padding:10px;margin:10px;border-radius:8px;}
.success{background:#d4f7dc;color:#12c998;}
.error{background:#fcdcdc;color:#c34242;}
.copyright{text-align:center;}
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

<?php if($success): ?>
<div class="message success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="cards-container">

<!-- AJOUT -->
<div class="card">
<h3>Ajouter une offre</h3>

<form method="POST">
    <input type="hidden" name="id_stage" value="0">

    <input name="titre" placeholder="Titre" required>
    <textarea name="description" placeholder="Description" required></textarea>

    <select name="id_entreprise" required>
        <option value="">Entreprise</option>
        <?php pg_result_seek($entreprises,0); while($e = pg_fetch_assoc($entreprises)): ?>
            <option value="<?= $e['id_entreprise'] ?>">
                <?= htmlspecialchars($e['nom']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select name="niveau_etude" required>
        <option value="">Niveau</option>
        <option>Bac+1</option>
        <option>Bac+2</option>
        <option>Licence</option>
        <option>Master</option>
        <option>Doctorat</option>
    </select>

    <input type="number" name="duree_semaine" placeholder="Durée" required>

    <button type="submit">Ajouter</button>
</form>
</div>

<!-- LISTE -->
<?php while($o = pg_fetch_assoc($offres)): ?>
<div class="card">

<!-- FICHE -->
<div class="fiche">
    <h3><?= htmlspecialchars($o['titre']) ?></h3>
    <p><?= nl2br(htmlspecialchars($o['description'])) ?></p>
    <p><b>Entreprise:</b> <?= htmlspecialchars($o['entreprise']) ?></p>
    <p><b>Niveau:</b> <?= htmlspecialchars($o['niveau_etude']) ?></p>
    <p><b>Durée:</b> <?= $o['duree_semaine'] ?> semaines</p>
</div>

<!-- MODIF -->
<form method="POST">
    <input type="hidden" name="id_stage" value="<?= $o['id_stage'] ?>">

    <input name="titre" value="<?= htmlspecialchars($o['titre']) ?>">
    <textarea name="description"><?= htmlspecialchars($o['description']) ?></textarea>

    <select name="id_entreprise">
        <?php pg_result_seek($entreprises,0); while($e = pg_fetch_assoc($entreprises)): ?>
            <option value="<?= $e['id_entreprise'] ?>" <?= $e['id_entreprise']==$o['id_entreprise']?'selected':'' ?>>
                <?= htmlspecialchars($e['nom']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <input name="niveau_etude" value="<?= $o['niveau_etude'] ?>">
    <input type="number" name="duree_semaine" value="<?= $o['duree_semaine'] ?>">

    <button type="submit">Modifier</button>
</form>


<form method="POST">
    <input type="hidden" name="delete" value="<?= $o['id_stage'] ?>">
    <button class="delete" onclick="return confirm('Supprimer cette offre ?')">
        Supprimer
    </button>
</form>
</div>
<?php endwhile; ?>

</div>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>