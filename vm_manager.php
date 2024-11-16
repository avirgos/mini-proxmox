<?php
session_start();

// vérification si l'utilisateur est authentifié
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // redirection vers la page de connexion si non authentifié
    header('Location: index.php');
    exit;
}

// gestion de la déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // détruire la session
    session_unset();
    session_destroy();

    // redirection vers la page de connexion
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire de VM</title>
</head>
<body>
    <h1>Bienvenue sur le gestionnaire de VM</h1>
    <p>Connecté en tant que : <strong><?php echo htmlspecialchars($remoteHost); ?></strong></p>

    <form method="post">
        <input type="hidden" name="logout" value="1">
        <button type="submit">Se déconnecter</button>
    </form>

    <h2>Liste des VMs</h2>
</body>
</html>
