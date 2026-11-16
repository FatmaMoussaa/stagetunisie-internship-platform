<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['user_id'])) {
    die("Erreur critique : session utilisateur non définie.");
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';
$id_user_session = $_SESSION['user_id']; 
$success = $error = '';
if (isset($_GET['role'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $role = $_GET['role'];
    if (in_array($role, ['admin', 'etudiant'])) {
        $res = pg_query_params($conn, "UPDATE utilisateurs SET role=$1 WHERE id_user=$2", [$role, $id]);
        $success = $res ? "Rôle mis à jour." : "Erreur lors du changement de rôle.";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id != $id_user_session) {
        
        pg_query_params($conn, "DELETE FROM candidatures WHERE id_user=$1", [$id]);

        $res = pg_query_params($conn, "DELETE FROM utilisateurs WHERE id_user=$1", [$id]);
        $success = $res ? "Utilisateur supprimé." : "Erreur lors de la suppression.";
    } else {
        $error = "Impossible de supprimer votre propre compte.";
    }
}
$search = "";
$params = [];
$query = "SELECT * FROM utilisateurs";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $query .= " WHERE nom ILIKE $1 OR email ILIKE $1";
    $params[] = "%$search%";
}

$query .= " ORDER BY id_user DESC";
$res = pg_query_params($conn, $query, $params);
function getAvis($conn, $id_user) {
    $res = pg_query_params($conn, "
        SELECT a.note, a.commentaire, e.nom AS entreprise 
        FROM avis a
        JOIN entreprises e ON e.id_entreprise = a.id_entreprise
        WHERE a.id_user=$1
        ORDER BY a.id_avis DESC
    ", [$id_user]);

    $avis = [];
    while ($row = pg_fetch_assoc($res)) {
        $avis[] = $row;
    }
    return $avis;
}
?>
        
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion utilisateurs - StageTunisie</title>
<style>
:root{
  --rose:#c34271; --rose-clair:#f070a1; --vert:#16ffbd; --vert-fonce:#12c998;
  --bg:#f8fafc; --blanc:#ffffff; --shadow: rgba(0,0,0,0.1); --shadow-hover: rgba(0,0,0,0.15);
}
body{margin:0;font-family:Poppins,sans-serif;background:var(--bg);}
.header{display:flex; justify-content:space-between; align-items:center;
  padding:20px 40px; background:var(--blanc); box-shadow:0 5px 20px var(--shadow);
  position:sticky; top:0; z-index:1000; border-bottom-left-radius:15px; border-bottom-right-radius:15px;}
.header .logo{font-size:28px;font-weight:700;color:var(--vert);}
.header .logo span{color:var(--rose);}
.header .user-info{display:flex;align-items:center;gap:15px;}
.header .user-info img{width:50px;height:50px;border-radius:50%;border:2px solid var(--rose);}
.header .user-info span{font-weight:500;}
.header .logout a{padding:8px 18px;background:#fff;color:var(--rose);border-radius:30px;text-decoration:none;font-weight:600;transition:0.3s;}
.header .logout a:hover{background:var(--rose);color:#fff;}
.message{margin:20px 40px;padding:12px;border-radius:12px;font-weight:500;}
.success{background:#d4f7dc;color:var(--vert-fonce);}
.error{background:#fcdcdc;color:var(--rose);}
.search-form{display:flex;gap:10px;margin:20px 40px;flex-wrap:wrap;}
.search-form input{flex:1;padding:10px;border-radius:10px;border:2px solid;}
.search-form button, .search-form a{padding:10px 20px;border-radius:10px;border:none;background:var(--rose);color:#fff;cursor:pointer;text-decoration:none;}
.search-form button:hover,.search-form a:hover{background:var(--vert-fonce);}
.cards-container{
    display:grid;
    grid-template-columns:repeat(auto-fill,220px);
    justify-content:center;
    gap:30px;
    margin:30px auto;
    max-width:1200px;
}
.card{
    width:220px;
    background:var(--blanc);
    border-radius:12px;
    padding:12px;
    box-shadow:0 5px 15px var(--shadow);
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    transition:0.25s;
}
.card:hover{transform:translateY(-4px);}
.card img.logo{width:55px;height:55px;border-radius:50%;margin-bottom:8px;border:2px solid var(--rose);padding:2px;}
.card h3{font-size:14px;color:var(--rose-clair);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;}
.card p{font-size:12px;color:#555;margin:2px 0;}
.card .badge{font-size:11px;font-weight:600;color:var(--vert-fonce);margin:4px 0;}
.card a.details{font-size:11px;text-decoration:none;color:#d4a017;margin-top:4px;font-weight:600;}
.card .avis{margin-top:10px;width:100%;text-align:left;}
.card .avis p{margin:2px 0;font-size:11px;}
.card .stars{color:#f5b50a;font-size:12px;}
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

<?php if($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="search-form" method="GET">
    <input type="text" name="search" placeholder="Nom ou email" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Rechercher</button>
    <a href="admin_utilisateurs.php" class="btn-reset">Réinitialiser</a>
</form>

<div class="cards-container">
<?php while($row = pg_fetch_assoc($res)): ?>
    <div class="card">
        <?php 
        $logo = ($row['role'] == 'admin') ? 'adminphoto.webp' : 'inconnu.png';
        ?>
        <img class="logo" src="<?= $logo ?>" alt="Avatar">
        <h3><?= htmlspecialchars($row['nom']); ?></h3>
        <p><?= htmlspecialchars($row['email']); ?></p>
        <span class="badge"><?= htmlspecialchars($row['role']); ?></span>
        <a class="details" href="?role=<?= $row['role']=='etudiant'?'admin':'etudiant'; ?>&id=<?= $row['id_user']; ?>">
            <?= $row['role']=='etudiant'?'Promouvoir Admin':'Rendre Étudiant'; ?>
        </a>
        <a class="details" href="?delete=<?= $row['id_user']; ?>" onclick="return confirm('Supprimer cet utilisateur ?')">
            Supprimer
        </a>
        <div class="avis">
        <?php 
        $avis_user = getAvis($conn, $row['id_user']);
        if($avis_user):
            echo "<p><strong>Avis :</strong></p>";
            foreach($avis_user as $a){
                $stars = str_repeat('★', $a['note']) . str_repeat('☆', 5-$a['note']);
                echo "<p class='stars'>$stars</p>";
                echo "<p>".$a['commentaire']."<br><em>".$a['entreprise']."</em></p>";
            }
        endif;
        ?>
        </div>

    </div>
<?php endwhile; ?>
</div>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>