<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remote_host']) && isset($_POST['password'])) {
        $remoteHost = $_POST['remote_host'];
        $password = $_POST['password'];

        // Validation des entrées utilisateur
        if (!preg_match('/^[^@]+@[^@]+$/', $remoteHost)) {
            echo "Erreur : Veuillez entrer un hôte distant valide (ex : utilisateur@machine).";
            exit;
        }

        // Échapper les entrées pour éviter les injections
        $escapedHost = escapeshellarg($remoteHost);
        $escapedPassword = escapeshellarg($password);

        // Commande pour vérifier la connexion
        $command = "sshpass -p $escapedPassword ./hypervisor $escapedHost SSH";

        $descriptorSpec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"],  // stderr
        ];
        $process = proc_open($command, $descriptorSpec, $pipes);

        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            if (strpos($output, 'Connection successful') !== false) {
                // Si la connexion réussit, stocker l'utilisateur en session
                $_SESSION['authenticated'] = true;
                $_SESSION['remote_host'] = $remoteHost;

                // Redirection vers vm-manager.php
                header('Location: vm_manager.php');
                exit;
            } else {
                // Afficher un message d'erreur en cas d'échec
                echo "Échec de la connexion : " . htmlspecialchars($error);
            }
        } else {
            echo "Erreur : Impossible d'exécuter la commande.";
        }
    } else {
        echo "Erreur : Veuillez fournir toutes les informations nécessaires.";
    }
} else {
    // Afficher le formulaire de connexion
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

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required><br><br>

            <input type="submit" value="Connecter">
        </form>
    </body>
    </html>
    <?php
}
?>
