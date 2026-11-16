<?php
session_start();
require_once 'config.php';

// Vérifie que c'est bien un étudiant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
    exit;
}

// Récupère l'id de l'étudiant connecté et son nom
$id_user = $_SESSION['user_id'] ?? null;
$etudiant_nom = $_SESSION['nom'] ?? 'Étudiant';

// 🔴 TRAITEMENT ANNULATION
if (isset($_GET['annuler'])) {
    $id_candidature = $_GET['annuler'];

    // Supprimer seulement si elle appartient à l'étudiant ET en attente
    pg_query_params(
        $conn,
        "DELETE FROM candidatures 
         WHERE id_candidature = $1 AND id_user = $2 AND statut = 'en_attente'",
        array($id_candidature, $id_user)
    );

    // Refresh pour éviter répétition
    header("Location: etudiant_condidatures.php");
    exit;
}

// Récupère toutes les candidatures avec infos entreprise
$sql = "
SELECT c.id_candidature, o.titre AS poste, e.nom AS entreprise, e.logo AS logo, o.niveau_etude, e.ville, c.statut, c.date_postulation
FROM candidatures c
JOIN offres_stage o ON c.id_stage = o.id_stage
JOIN entreprises e ON o.id_entreprise = e.id_entreprise
WHERE c.id_user = $1
ORDER BY c.date_postulation DESC
";
$res = pg_query_params($conn, $sql, array($id_user));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes candidatures - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --rose: #c34271;
    --rose-clair: #f070a1;
    --vert-fonce: #12c998;
    --bg: #f8fafc;
    --blanc: #ffffff;
    --shadow: rgba(0,0,0,0.1);
    --text: #2d3e50;
    --vert:#16ffbd;
}

/* Global */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
body { background: var(--bg); color: var(--text); padding-bottom:40px;display: flex;
  flex-direction: column;
  min-height: 100vh; }

/* HEADER */
.header {
    display:flex; justify-content:space-between; align-items:center;
    padding:20px 40px; background: var(--blanc);
    box-shadow:0 5px 20px var(--shadow);
    position:sticky; top:0; z-index:1000;
    border-bottom-left-radius:15px; border-bottom-right-radius:15px;
}
.header .logo { font-size:28px; font-weight:700; color: var(--vert); }
.header .logo span { color: var(--rose); font-weight:700; }
.header .user-info { display:flex; align-items:center; gap:15px; }
.header .user-info img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--rose); }
.header .user-info span { font-weight:500; }
.header .logout a { padding:8px 18px; background:#fff; color:var(--rose); border-radius:30px; font-weight:600; text-decoration:none; transition:0.3s; }
.header .logout a:hover { background:var(--rose); color:#fff; }

/* CARDS comme offres */
.cards-container {
    display:grid;
    grid-template-columns: repeat(auto-fill, 170px);
    justify-content:center;
    gap:18px;
    margin:30px auto;
    max-width:1100px;
}
.card {
    width:170px;
    height:170px;
    background: var(--blanc);
    border-radius:12px;
    padding:12px;
    box-shadow:0 5px 15px var(--shadow);
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
    transition:0.25s;
}
.card:hover { transform: translateY(-4px); }

/* Logo */
.card img.logo {
    width:55px;
    height:55px;
    object-fit:contain;
    margin-bottom:8px;
    border-bottom:2px solid var(--rose);
    padding-bottom:5px;
}

/* Texte */
.card h3 { font-size:13px; color: var(--rose-clair); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width:100%; }
.card p { font-size:11px; color:#555; margin:2px 0; }
.nb-offres { font-size:11px; font-weight:600; color: var(--vert-fonce); }

/* Badge statut */
.badge { display:inline-block; padding:4px 8px; border-radius:10px; font-size:10px; font-weight:600; margin-top:5px; }
.en_attente { background:#ffe9c6; color:#d07a00; }
.accepte { background:#d4f7dc; color:#16a34a; }
.refuse { background:#fcdcdc; color:#c34242; }

/* Bouton annuler */
.actions { margin-top:5px; }
.btn-annuler {
    display:inline-block;
    padding:4px 8px;
    border-radius:8px;
    background: var(--rose);
    color:#fff;
    font-size:10px;
    text-decoration:none;
    transition:0.3s;
}
.btn-annuler:hover { background:#f070a1; }

/* Responsive */
@media(max-width:500px){
    .cards-container { grid-template-columns: repeat(auto-fill, 140px); }
    .card { width:140px; height:140px; }
}
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

<div class="cards-container">
<?php while($row = pg_fetch_assoc($res)): ?>
    <div class="card">
        <?php $logo = $row['logo'] && file_exists($row['logo']) ? $row['logo'] : 'images/default-logo.png'; ?>
        <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($row['entreprise']) ?>">
        <h3><?= htmlspecialchars($row['poste']); ?></h3>
        <p><?= htmlspecialchars($row['entreprise']); ?></p>
        <p class="nb-offres"><?= htmlspecialchars($row['ville']) ?> - <?= htmlspecialchars($row['niveau_etude']) ?></p>

        <span class="badge <?= 
            $row['statut']=='en_attente'?'en_attente':($row['statut']=='accepte'?'accepte':'refuse'); ?>">
            <?= htmlspecialchars($row['statut']); ?>
        </span>

        <?php if($row['statut']=='en_attente'): ?>
        <div class="actions">
            <a class="btn-annuler" href="?annuler=<?= $row['id_candidature']; ?>" 
               onclick="return confirm('Annuler cette candidature ?');">Annuler</a>
        </div>
        <?php endif; ?>
    </div>
<?php endwhile; ?>
</div>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>