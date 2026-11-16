<?php
session_start();
require_once 'config.php';
require('fpdf.php');

if (!isset($_SESSION['role']) || $_SESSION['role']!=='admin') {
    header("Location: login.php");
    exit;
}

// Récupération des filtres depuis URL
$entreprise_id = $_GET['entreprise'] ?? null;
$offre_id = $_GET['poste'] ?? null;
$statut_filter = $_GET['statut'] ?? null;
$admin_mail = $_SESSION['email'] ?? 'fatmamoussa1698@gmail.com';

// Nom de l'entreprise pour le PDF
$entreprise_nom = 'Toutes les entreprises';
if(!empty($entreprise_id)){
    $res_e = pg_query_params($conn, "SELECT nom FROM entreprises WHERE id_entreprise=$1", [$entreprise_id]);
    if($row_e = pg_fetch_assoc($res_e)){
        $entreprise_nom = $row_e['nom'];
    }
}

// Requête filtrée
$sql = "
SELECT c.id_candidature, u.nom AS etudiant, u.email AS etudiant_email, o.titre AS poste, e.nom AS entreprise,
       c.cv_url, c.lettre_motivation, c.statut, c.date_postulation
FROM candidatures c
JOIN utilisateurs u ON c.id_user = u.id_user
JOIN offres_stage o ON c.id_stage = o.id_stage
JOIN entreprises e ON o.id_entreprise = e.id_entreprise
";

$params = [];
$conditions = [];
if(!empty($entreprise_id)){ $conditions[] = "e.id_entreprise=$".(count($params)+1); $params[] = $entreprise_id; }
if(!empty($offre_id)){ $conditions[] = "o.id_stage=$".(count($params)+1); $params[] = $offre_id; }
if(!empty($statut_filter)){ $conditions[] = "c.statut=$".(count($params)+1); $params[] = $statut_filter; }

if($conditions){ $sql .= " WHERE ".implode(" AND ", $conditions); }
$sql .= " ORDER BY e.nom ASC, o.titre ASC, c.date_postulation DESC";

$res = pg_query_params($conn, $sql, $params);

// ----------------
// Création PDF
// ----------------
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Rapport des candidatures',0,1,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'Liste des candidatures',0,1,'C');
$pdf->Ln(10);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,"Entreprise: $entreprise_nom",0,0,'L');
$pdf->Cell(0,6,"Mail Admin: $admin_mail",0,1,'R');
$pdf->AddPage();

// Tableau
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(50,10,'Etudiant',1,0,'C',true);
$pdf->Cell(70,10,'Poste',1,0,'C',true);
$pdf->Cell(30,10,'CV',1,0,'C',true);
$pdf->Cell(35,10,'Lettre',1,1,'C',true);

$pdf->SetFont('Arial','',11);
$base_url = 'http://localhost/MonStage/uploads/';

while($row = pg_fetch_assoc($res)){
    $pdf->Cell(50,10,substr($row['etudiant'],0,25),1,0,'C');
    $pdf->Cell(70,10,substr($row['poste'],0,25),1,0,'C');

    // CV clickable
    if(!empty($row['cv_url'])){
        $cv_url = $base_url . basename($row['cv_url']);
        $pdf->SetTextColor(0,0,255);
        $pdf->Cell(30,10,'Voir CV',1,0,'C',false,$cv_url);
        $pdf->SetTextColor(0,0,0);
    }else{
        $pdf->Cell(30,10,'-',1,0,'C');
    }

    // Lettre clickable
    if(!empty($row['lettre_motivation'])){
        $lettre_url = $base_url . basename($row['lettre_motivation']);
        $pdf->SetTextColor(0,0,255);
        $pdf->Cell(35,10,'Voir Lettre',1,1,'C',false,$lettre_url);
        $pdf->SetTextColor(0,0,0);
    }else{
        $pdf->Cell(35,10,'-',1,1,'C');
    }
}

// Affichage PDF directement
$pdf->Output('I','Candidatures_Filtres.pdf');