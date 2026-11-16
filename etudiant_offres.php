<?php
session_start();
require_once 'config.php';

// Vérifie étudiant connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
    exit;
}

$etudiant_nom = $_SESSION['nom'] ?? 'Étudiant';

// Récupérer toutes les entreprises pour le filtre
$entreprises_result = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");
$entreprises = [];
while($row = pg_fetch_assoc($entreprises_result)) $entreprises[] = $row;

// Récupérer tous les titres de stage distincts
$titres_result = pg_query($conn, "SELECT DISTINCT titre FROM offres_stage ORDER BY titre ASC");
$titres = [];
while($row = pg_fetch_assoc($titres_result)) $titres[] = $row['titre'];

// Filtrage
$params = [];
$where = [];
if (!empty($_GET['entreprise'])) {
    $where[] = "o.id_entreprise=$1";
    $params[] = $_GET['entreprise'];
}
if (!empty($_GET['titre'])) {
    $where[] = "o.titre=$" . (count($params)+1);
    $params[] = $_GET['titre'];
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT o.id_stage, o.titre, o.description, o.niveau_etude, o.duree_semaine,
                 e.nom AS entreprise_nom, e.logo AS entreprise_logo, e.ville AS entreprise_ville
          FROM offres_stage o
          JOIN entreprises e ON o.id_entreprise=e.id_entreprise
          $where_sql
          ORDER BY o.titre ASC";

$result = pg_query_params($conn, $query, $params);
if (!$result) die("Erreur SQL : " . pg_last_error($conn));

$offres = [];
while($row = pg_fetch_assoc($result)) $offres[] = $row;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Offres de stage - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --rose: #c34271;
    --rose-clair: #f070a1;
    --vert: #16ffbd;
    --vert-fonce: #12c998;
    --bg: #f8fafc;
    --blanc: #ffffff;
    --shadow: rgba(0,0,0,0.1);
    --text: #2d3e50;
}

/* Global */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
body { background: var(--bg); color: var(--text); }

/* Header */
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

/* Filtres */
.filters {
    display:flex; gap:15px; padding:20px 40px;
    background:var(--blanc); box-shadow:0 5px 15px var(--shadow);
    margin:20px 40px; border-radius:15px; flex-wrap:wrap; align-items:center;
}

/* Dropdown */
.dropdown {
    position: relative; flex:1; min-width:180px;
}
.dropdown-selected {
    padding:10px 15px; border-radius:12px; border:1px solid #ccc; cursor:pointer; background:#fff; transition:0.3s;
}
.dropdown-selected:hover { border-color: var(--rose); }
.dropdown-options {
    display:none; position:absolute; top:110%; left:0; width:100%;
    background:#fff; border:1px solid #ccc; border-radius:12px; max-height:200px; overflow-y:auto; box-shadow:0 10px 25px rgba(0,0,0,0.1); z-index:100;
}
.dropdown.active .dropdown-options { display:block; }
.dropdown-item { padding:10px 12px; cursor:pointer; transition:0.2s; }
.dropdown-item:hover { background: linear-gradient(135deg, #f070a1, #c34271); color:#fff; }

/* Boutons filtres */
.filters button, .filters a {
    display:inline-block;
    text-align:center;
    padding:10px 20px;
    min-width:150px;
    border-radius:25px;
    font-weight:600;
    color:#fff;
    background: linear-gradient(135deg, #f070a1, #c34271);
    cursor:pointer;
    transition:0.3s;
    text-decoration:none;
    border:none;
}
.filters button:hover, .filters a:hover { opacity:0.85; }

/* Cartes Offres */
.cards-container {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
    gap:20px;
    padding:20px 40px;
}

/* Carte offre */
.card {
    background: white;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 5px 15px var(--shadow);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    transition: 0.25s;
}
.card:hover {
    transform: translateY(-4px);
}
.card img.logo {
    width: 55px;
    height: 55px;
    object-fit: contain;
    margin-bottom: 8px;
    border-bottom: 2px solid var(--rose);
    padding-bottom: 5px;
}
.card h3 {
    font-size: 13px;
    color: var(--rose-clair);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}
.card p {
    font-size: 11px;
    color: #555;
}
.card .nb-offres {
    font-size: 11px;
    font-weight: 600;
    color: var(--vert-fonce);
}
.card a.details {
    font-size: 11px;
    text-decoration: none;
    color: #d4a017;
    margin-top: 5px;
    font-weight: 600;
}

/* Responsive */
@media(max-width:500px){
    .cards-container {
        grid-template-columns: repeat(auto-fill, 140px);
    }
    .card {
        width: 140px;
        height: auto;
    }
}
.copyright{text-align:center;margin-top: auto;}
.foot{margin-top: auto;}
</style>
</head>
<body>

<div class="header">
    <div class="logo">Stage<span>Tunisie</span></div>
    <div class="user-info">
        <img src="inconnu.png" alt="Avatar Étudiant">
        <span><?= htmlspecialchars($etudiant_nom); ?></span>
        <div class="logout"><a href="etudiant.php">Dashboard</a></div>
        <div class="logout"><a href="logout.php">Déconnexion</a></div>
    </div>
</div>

<form method="GET" class="filters">
<div class="dropdown">
    <div class="dropdown-selected"> 
        <?php
        if (!empty($_GET['entreprise'])) {
            $filtered = array_values(array_filter($entreprises, fn($e)=>$e['id_entreprise']==$_GET['entreprise']));
            echo htmlspecialchars($filtered[0]['nom'] ?? "Toutes les entreprises");
        } else {
            echo "Toutes les entreprises";
        }
        ?>
    </div>
    <div class="dropdown-options">
        <div class="dropdown-item" data-value="">Toutes les entreprises</div>
        <?php foreach($entreprises as $e): ?>
            <div class="dropdown-item" data-value="<?= htmlspecialchars($e['id_entreprise']) ?>"><?= htmlspecialchars($e['nom']) ?></div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="entreprise" value="<?= htmlspecialchars($_GET['entreprise'] ?? '') ?>">
</div>

<div class="dropdown">
    <div class="dropdown-selected"> 
        <?= !empty($_GET['titre']) ? htmlspecialchars($_GET['titre']) : "Tous les titres" ?>
    </div>
    <div class="dropdown-options">
        <div class="dropdown-item" data-value="">Tous les titres</div>
        <?php foreach($titres as $t): ?>
            <div class="dropdown-item" data-value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="titre" value="<?= htmlspecialchars($_GET['titre'] ?? '') ?>">
</div>

<button type="submit">Filtrer</button>
<a href="etudiant_offres.php">Réinitialiser</a>
</form>

<div class="cards-container">
<?php if(!$offres): ?>
    <p style="padding:20px 40px;">Aucune offre trouvée.</p>
<?php else: ?>
    <?php foreach($offres as $o): ?>
        <div class="card">
            <?php $logo = $o['entreprise_logo'] && file_exists($o['entreprise_logo']) ? $o['entreprise_logo'] : 'images/default-logo.png'; ?>
            <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($o['entreprise_nom']) ?>">
            <h3><?= htmlspecialchars($o['titre']) ?></h3>
            <p><?= htmlspecialchars($o['entreprise_nom']) ?></p>
            <p class="nb-offres"><?= htmlspecialchars($o['entreprise_ville']) ?> - <?= htmlspecialchars($o['niveau_etude']) ?></p>
            <a href="etudiant_offre_detail.php?id=<?= $o['id_stage'] ?>" class="details">Voir détails</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
// Dropdown interaction
document.querySelectorAll('.dropdown').forEach(drop=>{
    const selected = drop.querySelector('.dropdown-selected');
    const options = drop.querySelectorAll('.dropdown-item');
    const hidden = drop.querySelector('input[type=hidden]');
    selected.addEventListener('click', ()=> drop.classList.toggle('active'));
    options.forEach(opt=>{
        opt.addEventListener('click', ()=>{
            hidden.value = opt.dataset.value;
            selected.textContent = opt.textContent;
            drop.classList.remove('active');
        });
    });
    document.addEventListener('click', e=> { if(!drop.contains(e.target)) drop.classList.remove('active'); });
});
</script>
<footer class="foot">
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p></footer>
</body>
</html>