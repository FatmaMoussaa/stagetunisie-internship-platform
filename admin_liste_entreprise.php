<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';

$success_add = '';
$success_delete = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(isset($_POST['delete'])){
        $id_del = (int)$_POST['delete'];
        $res = pg_query_params($conn,"DELETE FROM entreprises WHERE id_entreprise=$1",[$id_del]);
        $success_delete = $res ? "Entreprise supprimée avec succès." : "Erreur lors de la suppression.";
    } 
    elseif(isset($_POST['submit'])){
        $id = (int)($_POST['id_entreprise'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $secteur = trim($_POST['secteur'] ?? '');
        $date_creation = $_POST['date_creation'] ?? '';


        if(!$nom || !$adresse || !$ville || !$email || !$date_creation){
            $error = "Les champs Nom, Adresse, Ville et Email et date  sont obligatoires.";
        } else {

            $logo_name = null;
            if(!empty($_FILES['logo']['name'])){
                $allowed = ['jpg','jpeg','png','webp'];
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if(in_array($ext, $allowed)){
                    $logo_name = 'uploads/'.time().'_'.basename($_FILES['logo']['name']);
                    move_uploaded_file($_FILES['logo']['tmp_name'], $logo_name);
                } else {
                    $error = "Format de logo non autorisé (jpg, png, webp).";
                }
            }

            if(!$error){
                if($id > 0){
                    $query = "UPDATE entreprises SET nom=$1, adresse=$2, ville=$3, email=$4, description=$5, secteur=$6, date_creation=$7";
                    $params = [$nom,$adresse,$ville,$email,$description,$secteur,$date_creation];
                    if($logo_name){
                        $query .= ", logo=$8 WHERE id_entreprise=$9";
                        $params[] = $logo_name;
                        $params[] = $id;
                    } else {
                        $query .= " WHERE id_entreprise=$8";
                        $params[] = $id;
                    }
                    $res = pg_query_params($conn,$query,$params);
                    $success_add = $res ? "Entreprise modifiée avec succès." : "Erreur lors de la modification.";
                } else {
                    $res = pg_query_params($conn,
                        "INSERT INTO entreprises (nom, adresse, ville, email, description, secteur, date_creation, logo) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)",
                        [$nom,$adresse,$ville,$email,$description,$secteur,$date_creation,$logo_name ?? 'images/logos/default-logo.png']
                    );
                    $success_add = $res ? "Entreprise ajoutée avec succès." : "Erreur lors de l'ajout.";
                }
            }
        }
    }
}
$entreprises = pg_query($conn, "SELECT * FROM entreprises ORDER BY nom ASC");


?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - Entreprises</title>
<style>
:root {--rose:#c34271; --vert:#16ffbd; --bg:#f8fafc; --blanc:#fff; --shadow:rgba(0,0,0,0.1);}
body{font-family:sans-serif;background:var(--bg);margin:0;}
.header{display:flex;justify-content:space-between;align-items:center;padding:20px;background:var(--blanc);box-shadow:0 5px 20px var(--shadow);}
.header .logo{font-size:28px;font-weight:700;color:var(--vert);}
.header .logo span{color:var(--rose);}
.header .user-info{display:flex;align-items:center;gap:15px;}
.header .user-info img{width:40px;height:40px;border-radius:50%;border:2px solid var(--rose);}
.header .user-info span{font-weight:500;}
.header .logout a{padding:8px 18px;background:#fff;color:var(--rose);border-radius:30px;font-weight:600;text-decoration:none;}
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

<?php if($success_add): ?><div class="message success"><?= htmlspecialchars($success_add) ?></div><?php endif; ?>
<?php if($success_delete): ?><div class="message success"><?= htmlspecialchars($success_delete) ?></div><?php endif; ?>
<?php if($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="cards-container">

<div class="card">
    <h3>Ajouter entreprise</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_entreprise" value="0">
        <input name="nom" placeholder="Nom" required>
        <input name="adresse" placeholder="Adresse" required>
        <input name="ville" placeholder="Ville" required>
        <input name="email" placeholder="Email" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input name="secteur" placeholder="Secteur">
        <input type="date" name="date_creation">
        <input type="file" name="logo">
        <button type="submit" name="submit">Ajouter</button>
    </form>
</div>
<?php while($e = pg_fetch_assoc($entreprises)): ?>
<div class="card">
    <h3><?= htmlspecialchars($e['nom']) ?></h3>
    <p><strong>Adresse:</strong> <?= htmlspecialchars($e['adresse']) ?></p>
    <p><strong>Ville:</strong> <?= htmlspecialchars($e['ville']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($e['email']) ?></p>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_entreprise" value="<?= $e['id_entreprise'] ?>">
        <input name="nom" value="<?= htmlspecialchars($e['nom']) ?>" required>
        <input name="adresse" value="<?= htmlspecialchars($e['adresse']) ?>" required>
        <input name="ville" value="<?= htmlspecialchars($e['ville']) ?>" required>
        <input name="email" value="<?= htmlspecialchars($e['email']) ?>" required>
        <textarea name="description"><?= htmlspecialchars($e['description']) ?></textarea>
        <input name="secteur" value="<?= htmlspecialchars($e['secteur']) ?>">
        <input type="date" name="date_creation" value="<?= $e['date_creation'] ?>">
        <input type="file" name="logo">
        <button type="submit" name="submit">Modifier</button>
    </form>

    <form method="POST">
        <input type="hidden" name="delete" value="<?= $e['id_entreprise'] ?>">
        <button class="delete" onclick="return confirm('Supprimer cette entreprise ?')">Supprimer</button>
    </form>
</div>
<?php endwhile; ?>

</div>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>