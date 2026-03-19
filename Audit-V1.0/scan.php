<?php
declare(strict_types=1);

$allowedTargets = [
    "localhost",
    "127.0.0.1",
    "scanme.local"
];

function isAllowedTarget(string $target, array $allowedTargets): bool
{
    return in_array($target, $allowedTargets, true);
}

function sanitizeTarget(string $target): string
{
    $target = trim($target);

    // caractères autorisés très limités
    if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $target)) {
        return '';
    }

    return $target;
}

function toolExists(string $tool): bool
{
    $escaped = escapeshellarg($tool);

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = "where $escaped";
    } else {
        $cmd = "command -v $escaped";
    }

    $output = [];
    $code = 1;
    exec($cmd . " 2>&1", $output, $code);

    return $code === 0;
}

function runCommand(string $tool, string $target): array
{
    $output = [];
    $exitCode = 1;
    $command = '';

    if ($tool === 'nmap') {
        // Scan de démonstration simple
        $command = 'nmap -F ' . escapeshellarg($target) . ' 2>&1';
    } elseif ($tool === 'nikto') {
        // Cible locale ou lab uniquement
        $command = 'nikto -h ' . escapeshellarg($target) . ' 2>&1';
    } else {
        return [
            'command' => '',
            'output' => "Outil non autorisé.",
            'exitCode' => 1
        ];
    }

    exec($command, $output, $exitCode);

    return [
        'command' => $command,
        'output' => implode("\n", $output),
        'exitCode' => $exitCode
    ];
}

$target = $_POST['target'] ?? '';
$tool = $_POST['tool'] ?? '';

$target = sanitizeTarget($target);
$tool = trim($tool);

$error = null;
$result = null;

if ($target === '') {
    $error = "Cible invalide.";
} elseif (!in_array($tool, ['nmap', 'nikto'], true)) {
    $error = "Outil invalide.";
} elseif (!isAllowedTarget($target, $allowedTargets)) {
    $error = "Cette cible n'est pas autorisée dans cette démonstration.";
} elseif (!toolExists($tool)) {
    $error = "L'outil '$tool' n'est pas installé ou pas dans le PATH.";
} else {
    $result = runCommand($tool, $target);

    $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $filename = $logDir . DIRECTORY_SEPARATOR . 'scan_' . date('Ymd_His') . '.txt';

    $logContent  = "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
    $logContent .= "Cible: " . $target . PHP_EOL;
    $logContent .= "Outil: " . $tool . PHP_EOL;
    $logContent .= "Commande: " . $result['command'] . PHP_EOL;
    $logContent .= "Code retour: " . $result['exitCode'] . PHP_EOL;
    $logContent .= str_repeat('-', 60) . PHP_EOL;
    $logContent .= $result['output'] . PHP_EOL;

    file_put_contents($filename, $logContent);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat du scan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Résultat</h1>

        <div class="card">
            <p><strong>Cible :</strong> <?= htmlspecialchars($target, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Outil :</strong> <?= htmlspecialchars($tool, ENT_QUOTES, 'UTF-8') ?></p>

            <?php if ($error !== null): ?>
                <div class="error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <p><strong>Commande exécutée :</strong></p>
                <pre><?= htmlspecialchars($result['command'], ENT_QUOTES, 'UTF-8') ?></pre>

                <p><strong>Code retour :</strong> <?= (int)$result['exitCode'] ?></p>

                <p><strong>Sortie :</strong></p>
                <pre><?= htmlspecialchars($result['output'], ENT_QUOTES, 'UTF-8') ?></pre>
            <?php endif; ?>

            <p>
                <a href="index.php">← Retour</a>
            </p>
        </div>
    </div>
</body>
</html>