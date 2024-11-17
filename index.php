<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remote_host'])) {
        $remoteHost = $_POST['remote_host'];

        // vérification du $remoteHost saisi par l'utilisateur
        if (!preg_match('/^[^@]+@[^@]+$/', $remoteHost)) {
            echo "Erreur : Veuillez entrer un hôte distant valide (<em>Ex. : utilisateur@machine</em>).";
            exit;
        }

        $escapedHost = escapeshellarg($remoteHost);

	// commande pour établir la connexion SSH vers l'hyperviseur
	$command = "./hypervisor $escapedHost SSH";
		
	// configuration des descripteurs de processus pour récupérer la sortie
        $descriptorSpec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"],  // stderr
        ];

        // lancement du processus concernant la connexion SSH vers l'hyperviseur
        $process = proc_open($command, $descriptorSpec, $pipes);

        if (is_resource($process)) {
	    // stdin	
	    $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

	    // stderr
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // fermeture du processus
            $exitCode = proc_close($process);

	    // connexion réussie
            if (strpos($output, 'Connection successful') !== false) {
                $_SESSION['authenticated'] = true;
                $_SESSION['remote_host'] = $remoteHost;

                // rediriger vers la page vm_manager.php
                header('Location: vm_manager.php');
                exit;
            } else {
                echo "Échec de la connexion : " . htmlspecialchars($error);
            }
        } else {
            echo "Erreur : Impossible d'exécuter la commande.";
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
