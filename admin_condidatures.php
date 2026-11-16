<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$admin_nom = $_SESSION['nom'] ?? 'Admin';
$entreprise_id = $_GET['entreprise'] ?? '';
$offre_id = $_GET['poste'] ?? '';
$statut_filter = $_GET['statut'] ?? '';
$entreprises_res = pg_query($conn, "SELECT id_entreprise, nom FROM entreprises ORDER BY nom ASC");
$offres_res = pg_query($conn, "SELECT id_stage, titre FROM offres_stage ORDER BY titre ASC");
$sql = "SELECT c.id_candidature, u.nom AS etudiant, u.email, o.titre AS poste, e.nom AS entreprise, c.statut, c.cv_url, c.lettre_motivation, c.date_postulation, e.email AS email_entreprise
FROM candidatures c
JOIN utilisateurs u ON c.id_user = u.id_user
JOIN offres_stage o ON c.id_stage = o.id_stage
JOIN entreprises e ON o.id_entreprise = e.id_entreprise";

$params = [];
$conditions = [];

if(!empty($entreprise_id)) { $conditions[] = "e.id_entreprise = $" . (count($params)+1); $params[] = $entreprise_id; }
if(!empty($offre_id)) { $conditions[] = "o.id_stage = $" . (count($params)+1); $params[] = $offre_id; }
if(!empty($statut_filter)) { $conditions[] = "c.statut = $" . (count($params)+1); $params[] = $statut_filter; }
if($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);

$sql .= " ORDER BY c.date_postulation DESC";
$res = pg_query_params($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Candidatures - StageTunisie</title>
<style>
:root {--rose:#c34271; --vert:#16ffbd; --bg:#f8fafc; --blanc:#fff; --shadow:rgba(0,0,0,0.1);}
body{font-family:sans-serif;background:var(--bg);margin:0;}
.header{display:flex;justify-content:space-between;align-items:center;padding:20px;position:sticky;background:var(--blanc);box-shadow:0 5px 20px var(--shadow);}
.header .logo{font-size:28px;font-weight:700;color:var(--vert);}
.header .logo span{color:var(--rose);}
.header .user-info{display:flex;align-items:center;gap:15px;}
.header .user-info img{width:40px;height:40px;border-radius:50%;border:2px solid var(--rose);}
.header .user-info span{font-weight:500;}
.header .logout a{padding:8px 18px;background:#fff;color:var(--rose);border-radius:30px;font-weight:600;text-decoration:none;}
.filters{display:flex;flex-wrap:wrap;gap:15px;padding:20px 40px;background:var(--blanc);margin-bottom:20px;border-radius:15px;box-shadow:0 5px 20px var(--shadow);}
.filters select,.filters button,.filters a{padding:10px 15px;border-radius:10px;border:none;font-size:14px;font-weight:500;transition:0.3s;}
.filters select{background:#f0f0f0;}
.filters select:hover{background:#e0e0e0;}
.filters button{text-align:center;color:var(--rose);border:1px solid var(--rose);width:100px;}
.filters button:hover{background:var(--rose);color:#fff;}
.filters a{text-decoration:none;color:var(--rose);border:1px solid var(--rose);width:100px;text-align:center;}
.filters a:hover{background:var(--rose);color:#fff;}
.cards-container{display:grid;grid-template-columns:repeat(auto-fill,180px);justify-content:center;gap:30px;padding:20px;}
.card{width:180px;height:300px;background:var(--blanc);border-radius:12px;padding:12px;box-shadow:0 5px 15px var(--shadow);display:flex;flex-direction:column;align-items:center;text-align:center;transition:0.25s;}
.card:hover{transform:translateY(-4px);}
.card img.logo{width:55px;height:55px;border-radius:50%;object-fit:cover;margin-bottom:8px;border:2px solid var(--rose);padding:2px;}
.card h3{font-size:13px;color:var(--rose-clair);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;}
.card p{font-size:11px;color:#555;margin:2px 0;}
.badge{font-size:11px;font-weight:600;color:#fff;padding:3px 6px;border-radius:8px;margin-top:4px;}
.en_attente{background:#ffe9c6;color:#d07a00;}
.accepte{background:#16a34a;color:#fff;}
.actions{margin-top:6px;display:flex;flex-direction:column;gap:4px;}
.actions a{font-size:11px;padding:4px 6px;border-radius:6px;text-decoration:none;color:#fff;font-weight:600;transition:0.3s;}
.actions a.accept{background:var(--vert);}

.actions a.reject{background:var(--rose);}
.actions a.reject:hover{background:#f070a1;}
.pdf-email {background:var(--blanc);padding:20px;margin:20px 40px;border-radius:15px;box-shadow:0 5px 20px var(--shadow);}
.pdf-email h3{margin-bottom:10px;color:var(--rose);}
.pdf-email form{display:flex;flex-wrap:wrap;gap:10px;}
.pdf-email input[type=email]{flex:1;padding:10px;border-radius:8px;border:1px solid #ccc;}
.pdf-email button{padding:10px 15px;border:none;border-radius:8px;background:var(--rose);color:#fff;cursor:pointer;}
.pdf-email a{padding:10px 15px;border:none;border-radius:8px;background:var(--rose);color:#fff;cursor:pointer;text-decoration:none;}
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

<div class="filters">
<form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;">
  <select name="entreprise">
    <option value="">Toutes les entreprises</option>
    <?php pg_result_seek($entreprises_res,0); while($e=pg_fetch_assoc($entreprises_res)): ?>
        <option value="<?= $e['id_entreprise'] ?>" <?= $entreprise_id==$e['id_entreprise']?'selected':'' ?>><?= htmlspecialchars($e['nom']) ?></option>
    <?php endwhile; ?>
  </select>
  <select name="poste">
    <option value="">Tous les postes</option>
    <?php pg_result_seek($offres_res,0); while($p=pg_fetch_assoc($offres_res)): ?>
        <option value="<?= $p['id_stage'] ?>" <?= $offre_id==$p['id_stage']?'selected':'' ?>><?= htmlspecialchars($p['titre']) ?></option>
    <?php endwhile; ?>
  </select>
  <select name="statut">
    <option value="">Tous les statuts</option>
    <option value="en_attente" <?= $statut_filter=='en_attente'?'selected':'' ?>>En attente</option>
    <option value="accepte" <?= $statut_filter=='accepte'?'selected':'' ?>>Accepté</option>
  </select>
  <button type="submit">Filtrer</button>
  <a href="admin_condidatures.php">Réinitialiser</a>
</form>
</div>

<div class="cards-container">
<?php while($row=pg_fetch_assoc($res)): ?>
  <div class="card">
      <img class="logo" src="inconnu.png" alt="Avatar">
      <h3><?= htmlspecialchars($row['etudiant']); ?></h3>
      <p><?= htmlspecialchars($row['email']); ?></p>
      <p><?= htmlspecialchars($row['poste']); ?></p>
      <p><?= htmlspecialchars($row['entreprise']); ?></p>
      <span class="badge <?= $row['statut']=='en_attente'?'en_attente':'accepte' ?>"><?= htmlspecialchars($row['statut']); ?></span>
      <div class="actions">
          <?php if($row['statut']=='en_attente'): ?>
          <a class="accept" href="admin_update_statut.php?id=<?= $row['id_candidature']; ?>&action=accepte">Accepter</a>
          <a class="reject" href="admin_update_statut.php?id=<?= $row['id_candidature']; ?>&action=refuse">Refuser</a>
          <?php endif; ?>
          <?php if($row['cv_url']): ?><a class="accept" href="<?= $row['cv_url']; ?>" target="_blank">CV</a><?php endif; ?>
          <?php if($row['lettre_motivation']): ?><a class="accept" href="<?= $row['lettre_motivation']; ?>" target="_blank">Lettre</a><?php endif; ?>
      </div>
  </div>
<?php endwhile; ?>
</div>

<div class="pdf-email">
  <h3>Envoi Fiche Vers Entreprise</h3>
  <form method="POST" action="send_pdf.php">
    <input type="email" name="email" placeholder="Email de l'entreprise" required>
    <input type="hidden" name="entreprise" value="<?= htmlspecialchars($entreprise_id) ?>">
    <input type="hidden" name="poste" value="<?= htmlspecialchars($offre_id) ?>">
    <input type="hidden" name="statut" value="<?= htmlspecialchars($statut_filter) ?>">
    
    <button type="submit">Envoyer PDF des candidatures filtrées</button>
    
  <a href="generate_pdf.php?entreprise=<?= urlencode($entreprise_id) ?>&poste=<?= urlencode($offre_id) ?>&statut=<?= urlencode($statut_filter) ?>">Télécharger PDF</a>
  </form>

</div>
<p class="copyright"> © 2026 StageTunisie. Tous droits réservés. </p>
</body>
</html>