<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Audit Sécurité Simple - SAE4</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f9f9f9; }
        h1 { color: #333; }
        form { margin-top: 30px; }
        input[type="text"] { width: 500px; padding: 10px; font-size: 16px; }
        button { padding: 12px 30px; font-size: 16px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Plateforme d'audit de sécurité simple (mode simple)</h1>
    <p>Entre l'URL du site (ex: https://example.com). Audit black-box, white-hat, non intrusif.</p>
    
    <form method="post" action="audit.php">
        <label for="url">URL du site web :</label><br>
        <input type="text" id="url" name="url" placeholder="https://example.com" required>
        <br><br>
        <button type="submit">Lancer l'audit (mode simple)</button>
    </form>
</body>
</html>