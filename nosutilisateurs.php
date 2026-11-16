<?php
require_once 'config.php'; // connexion PostgreSQL

// Récupération de tous les utilisateurs
$utilisateurs = pg_query($conn, "SELECT * FROM utilisateurs ORDER BY nom ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nos Utilisateurs - StageTunisie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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

/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
body { background: var(--bg); color: var(--text); }

/* HEADER */
.header {
    display:flex;  align-items:center;
    padding:20px 40px; background: var(--blanc);
    box-shadow:0 5px 20px var(--shadow); position:sticky; top:0; z-index:1000;
    border-bottom-left-radius:15px; border-bottom-right-radius:15px;
}
.header .logo { font-size:28px; font-weight:700; color: var(--vert); }
.header .logo span { color: var(--rose); font-weight:300; }

/* CARDS CONTAINER */
.cards-container {
    display:grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
    gap:20px; padding:40px;
}

/* CARD UTILISATEUR */
.card {
    background: var(--blanc);
    border-radius:20px; padding:20px;
    box-shadow:0 10px 25px var(--shadow);
    transition:0.3s;
    text-align:center;
}
.card:hover { transform: translateY(-5px); box-shadow:0 15px 35px var(--shadow); }
.card img {
    width:80px; height:80px; border-radius:50%; object-fit:cover;
    margin-bottom:15px; border:2px solid var(--rose-clair);
}
.card h3 { font-size:18px; margin-bottom:6px; color: var(--rose-clair); }
.card p { font-size:14px; margin-bottom:6px; color:#555; }
.badge { display:inline-block; padding:5px 10px; border-radius:10px; font-size:12px; font-weight:600; margin-top:5px; }
.admin { background:#d4f7dc; color: #16a34a; }
.etudiant { background:#ffe9c6; color:#d07a00; }

/* Back link */
.back-link { display:inline-block; margin:20px 40px; color: var(--vert-fonce); font-weight:500; text-decoration:none; transition:0.3s; }
.back-link:hover { color: var(--rose); }

/* Responsive */
@media(max-width:900px){
    .cards-container { grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); padding:20px; }
    .header { flex-direction:column; gap:10px; }
}
</style>
</head>
<body>

<div class="header">
    <div class="logo">Stage<span>Tunisie</span></div>
</div>

<div class="cards-container">
<?php while($u = pg_fetch_assoc($utilisateurs)): ?>
  <div class="card">
  <img src="<?php 
    echo htmlspecialchars(
        $u['role'] === 'etudiant' ? 'inconnu.png' : 'adminphoto.webp'
    ); 
?>" alt="Avatar de <?php echo htmlspecialchars($u['nom']); ?>">
      <h3><?php echo htmlspecialchars($u['nom']); ?></h3>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($u['email']); ?></p>
      <span class="badge <?php echo htmlspecialchars($u['role']); ?>"><?php echo htmlspecialchars($u['role']); ?></span>
  </div>
<?php endwhile; ?>
</div>

<a class="back-link" href="index.php">← Retour à l'accueil</a>

</body>
</html>