<?php
session_start();

function validateRemoteHost($remoteHost) {
    return preg_match('/^[^@]+@[^@]+$/', $remoteHost);
}

function executeCommand($command) {
    $descriptorSpec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        return [false, "Erreur : Impossible d'exécuter la commande."];
    }

    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [$exitCode === 0, $output, $error];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remote_host'])) {
        $remoteHost = $_POST['remote_host'];
        
        if (!validateRemoteHost($remoteHost)) {
            echo "Erreur : Veuillez entrer un hôte distant valide (<em>Ex. : utilisateur@machine</em>).";
            exit;
        }

        $escapedHost = escapeshellarg($remoteHost);
        $command = "./hypervisor $escapedHost SSH";

        list($success, $output, $error) = executeCommand($command);

        if ($success && strpos($output, 'Connection successful') !== false) {
            $_SESSION['authenticated'] = true;
            $_SESSION['remote_host'] = $remoteHost;
            header('Location: vm_manager.php');
            exit;
        } else {
            echo "Échec de la connexion : " . htmlspecialchars($error);
        }
    } else {
        echo "Erreur : Veuillez fournir l'hôte distant.";
    }
} else {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion à l'hyperviseur</title>
</head>
<body>
    <h1>Connexion à l'hyperviseur</h1>
    <form action="index.php" method="post">
        <label for="remote_host">Hôte distant (<em>Ex. : utilisateur@machine</em>) :</label>
        <input type="text" id="remote_host" name="remote_host" placeholder="utilisateur@machine" required><br><br>
        <input type="submit" value="Se connecter">
    </form>
</body>
</html>
<?php
}
?>
