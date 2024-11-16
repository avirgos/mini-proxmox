<?php
session_start();

// Vérification si l'utilisateur est authentifié
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirection vers la page de connexion si non authentifié
    header('Location: index.php');
    exit;
}

// Gestion de la déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // Détruire la session
    session_unset();
    session_destroy();

    // Redirection vers la page de connexion
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
    <p>Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['remote_host']); ?></strong></p>

    <!-- Bouton de déconnexion -->
    <form method="post">
        <input type="hidden" name="logout" value="1">
        <button type="submit">Se déconnecter</button>
    </form>

    <!-- Contenu principal -->
    <p>Gérez vos machines virtuelles depuis cette page.</p>
</body>
</html>
