<?php
$allowedTargets = [
    "localhost",
    "127.0.0.1",
    "scanme.local"
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ScanLab - Projet universitaire</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>ScanLab</h1>
        <p class="subtitle">Interface locale de démonstration pour scans autorisés</p>

        <div class="card">
            <h2>Lancer un scan</h2>

            <form method="POST" action="scan.php">
                <label for="target">Cible</label>
                <input
                    type="text"
                    id="target"
                    name="target"
                    placeholder="localhost"
                    required
                >

                <label for="tool">Outil</label>
                <select id="tool" name="tool" required>
                    <option value="nmap">Nmap</option>
                    <option value="nikto">Nikto</option>
                </select>

                <button type="submit">Exécuter</button>
            </form>

            <div class="info">
                <h3>Cibles autorisées</h3>
                <ul>
                    <?php foreach ($allowedTargets as $t): ?>
                        <li><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    Pour une démo sûre, l'application n'accepte que les cibles de cette liste.
                </p>
            </div>
        </div>
    </div>
</body>
</html>