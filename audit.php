<?php
if (empty($_POST['url'])) die('URL manquante.');

$url = trim($_POST['url']);
if (!filter_var($url, FILTER_VALIDATE_URL)) die('URL invalide.');

$parsed = parse_url($url);
$scheme = $parsed['scheme'] ?? 'http';
$domain = $parsed['host'] ?? '';

if (!in_array($scheme, ['http', 'https'])) die('Seuls http/https autorisés.');

$ip = gethostbyname($domain);
if ($ip === $domain) die('Impossible de résoudre le domaine.');

// Résultats
$results = [
    'url' => $url,
    'domain' => $domain,
    'ip' => $ip,
    'timestamp' => date('Y-m-d H:i:s')
];

// Outils (mode simple - 6 max)
$commands = [
    'whois'   => "whois $domain 2>&1",
    'dig'     => "dig +short $domain 2>&1",
    'whatweb' => "whatweb --no-errors $url 2>&1",
    'nmap'    => "nmap -sV -Pn --open $ip 2>&1",
    'nikto'   => "nikto -h $url -Tuning x -Format txt 2>&1",
    'testssl' => "testssl.sh --fast --color 0 $url 2>&1"
];

foreach ($commands as $tool => $cmd) {
    $output = shell_exec($cmd);
    $results[$tool] = $output ?: 'Erreur lors de l\'exécution.';
}

// Stockage SQLite
try {
    $db = new PDO('sqlite:audits.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS audits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        url TEXT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        results TEXT NOT NULL
    )");

    $stmt = $db->prepare("INSERT INTO audits (url, results) VALUES (?, ?)");
    $stmt->execute([$url, json_encode($results, JSON_UNESCAPED_UNICODE)]);

    $id = $db->lastInsertId();
} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

header("Location: report.php?id=$id");
exit;