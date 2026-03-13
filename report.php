<?php
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID invalide.');

try {
    $db = new PDO('sqlite:audits.db');
    $stmt = $db->prepare("SELECT * FROM audits WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) die('Audit non trouvé.');
    
    $results = json_decode($row['results'], true);
} catch (PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}

// Calcul rapide du risque global (comme avant)
$nikto_issues = substr_count($results['nikto'] ?? '', '+ ');
$ssl_grade = 'Inconnu';
if (preg_match('/Grade\s+(\w+)/i', $results['testssl'] ?? '', $m)) {
    $ssl_grade = strtoupper($m[1]);
}
$global_risk = 'mineur';
if ($nikto_issues > 3 || stripos($ssl_grade, 'C') !== false) $global_risk = 'important';
if ($nikto_issues > 10 || stripos($ssl_grade, 'D') !== false) $global_risk = 'majeur';
if ($nikto_issues > 20 || stripos($ssl_grade, 'F') !== false) $global_risk = 'critique';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Audit - <?= htmlspecialchars($results['url']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f9f9f9; line-height: 1.6; }
        h1, h2 { color: #333; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
        .severity { font-weight: bold; padding: 12px 20px; border-radius: 8px; display: inline-block; margin: 15px 0; font-size: 1.1em; }
        .mineur    { background: #d4edda; color: #155724; }
        .important { background: #fff3cd; color: #856404; }
        .majeur    { background: #ffeeba; color: #856404; }
        .critique  { background: #f8d7da; color: #721c24; }
        .tool { margin-bottom: 50px; border-bottom: 2px solid #eee; padding-bottom: 30px; }
        #download-btn {
            background: #28a745; color: white; padding: 15px 30px; font-size: 18px; 
            border: none; border-radius: 8px; cursor: pointer; margin: 20px 0;
        }
        #download-btn:hover { background: #218838; }
    </style>
    <!-- Bibliothèque PDF (client-side) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

    <button id="download-btn" onclick="downloadPDF()">📥 Télécharger le rapport en PDF</button>

    <div id="report-content">
        <h1>🔒 Rapport d'audit de sécurité</h1>
        <p><strong>Site audité :</strong> <?= htmlspecialchars($results['url']) ?></p>
        <p><strong>Date :</strong> <?= $row['date'] ?></p>
        <p><strong>ID audit :</strong> <?= $id ?></p>

        <h2>Synthèse globale (scoring ANSSI)</h2>
        <div class="severity <?= $global_risk ?>">
            Risque global : <?= ucfirst($global_risk) ?> 
            (<?= $nikto_issues ?> alertes Nikto – SSL grade : <?= $ssl_grade ?>)
        </div>

        <h2>Détails par outil</h2>

        <div class="tool">
            <h3>1. Whois (propriétaire du domaine)</h3>
            <p><strong>Explication simple :</strong> Qui possède le domaine et jusqu’à quand.</p>
            <pre><?= htmlspecialchars($results['whois'] ?? '') ?></pre>
        </div>

        <div class="tool">
            <h3>2. Dig (enregistrements DNS)</h3>
            <p><strong>Explication simple :</strong> Les adresses IP du site.</p>
            <pre><?= htmlspecialchars($results['dig'] ?? '') ?></pre>
        </div>

        <div class="tool">
            <h3>3. WhatWeb (technologies détectées)</h3>
            <p><strong>Explication simple :</strong> CMS, serveur, version (WordPress, etc.).</p>
            <pre><?= htmlspecialchars($results['whatweb'] ?? '') ?></pre>
        </div>

        <div class="tool">
            <h3>4. Nmap (ports ouverts)</h3>
            <p><strong>Explication simple :</strong> Quels ports et services tournent.</p>
            <pre><?= htmlspecialchars($results['nmap'] ?? '') ?></pre>
        </div>

        <div class="tool">
            <h3>5. Nikto (vulnérabilités web)</h3>
            <div class="severity <?= $nikto_issues > 5 ? 'critique' : ($nikto_issues > 0 ? 'important' : 'mineur') ?>">
                <?= $nikto_issues ?> problèmes détectés
            </div>
            <pre><?= htmlspecialchars($results['nikto'] ?? '') ?></pre>
        </div>

        <div class="tool">
            <h3>6. testssl.sh (sécurité HTTPS)</h3>
            <div class="severity <?= 
                str_contains($ssl_grade, 'A') || str_contains($ssl_grade, 'B') ? 'mineur' : 
                (str_contains($ssl_grade, 'C') ? 'important' : 'critique')
            ?>">
                Grade SSL/TLS : <?= $ssl_grade ?>
            </div>
            <pre><?= htmlspecialchars($results['testssl'] ?? '') ?></pre>
        </div>
    </div>

    <script>
    function downloadPDF() {
        const element = document.getElementById('report-content');
        const opt = {
            margin: 10,
            filename: 'Audit_Securite_<?= $id ?>_<?= date('Ymd') ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>

    <p><a href="index.php">← Faire un nouvel audit</a></p>
</body>
</html>