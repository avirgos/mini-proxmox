<?php
session_start();

// vérification si l'utilisateur est authentifié
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// gestion de la déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$successMessage = ""; // messages de succès
$errorMessage = ""; // messages d'erreur
$infoMessage = ""; // messages d'information

// Exécution du programme avec l'option "c" [Démarrer]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_vm'])) {
    $vmName = escapeshellarg($_POST['vm_name']);
    $remoteHost = escapeshellarg($_SESSION['remote_host']);

    // Commande complète avec le nom de la VM
    $command = "./hypervisor $remoteHost c $vmName";
    
    $descriptorSpec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"],  // stderr
    ];

    // lancement du processus concernant le démarrage de la VM
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

	// démarrage réussi
	if ($exitCode === 0) {
	    // utilisation d'une expression régulière pour extraire la portion JSON
    	    if (preg_match('/\{.*\}/s', $output, $matches)) {
		$jsonOutput = $matches[0];
		$result = json_decode($jsonOutput, true);
		
		$infoMessage = "{$result['message']}";
	    }
    	} else {
            $errorMessage = "Échec de l'exécution de la commande de démarrage pour la VM $vmName.";
	}
    }
}

// Exécution du programme avec l'option "d"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stop_vm'])) {
    $vmName = escapeshellarg($_POST['vm_name']);
    $remoteHost = escapeshellarg($_SESSION['remote_host']);

    // Commande complète avec le nom de la VM
    $command = "./hypervisor $remoteHost d $vmName";
    
    $descriptorSpec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"],  // stderr
    ];

    // lancement du processus concernant l'arrêt de la VM
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

	// démarrage réussi
	if ($exitCode === 0) {
	    // utilisation d'une expression régulière pour extraire la portion JSON
    	    if (preg_match('/\{.*\}/s', $output, $matches)) {
		$jsonOutput = $matches[0];
		$result = json_decode($jsonOutput, true);

		$infoMessage = "{$result['message']}";
	    }
    	} else {
            $errorMessage = "Échec de l'exécution de la commande d'arrêt pour la VM $vmName.";
	}
    }
}

// Exécution du programme avec l'option "s" [Sauvegarder]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vm'])) {
    $vmName = escapeshellarg($_POST['vm_name']);
    $remoteHost = escapeshellarg($_SESSION['remote_host']);
    
    // Commande complète avec le nom de la VM
    $command = "./hypervisor $remoteHost s $vmName";
    
    $descriptorSpec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"],  // stderr
    ];

    // lancement du processus concernant la sauvegarde de la VM
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

	// sauvegarde réussie
        if (strpos($output, "VM sauvegardée") !== false) {
	    // utilisation d'une expression régulière pour extraire la portion JSON
    	    if (preg_match('/\{.*\}/s', $output, $matches)) {
        	$jsonOutput = $matches[0];	
		$result = json_decode($jsonOutput, true);

		$successMessage = "{$result['message']}";
	    }
    	} else {
            $errorMessage = "Échec de l'exécution de la commande de sauvegarde pour la VM $vmName.";
	}
    }
}

// Exécution du programme avec l'option "r" [Restorer]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_vm'])) {
    $vmName = escapeshellarg($_POST['vm_name']);
    $remoteHost = escapeshellarg($_SESSION['remote_host']);
    
    // Commande complète avec le nom de la VM
    $command = "./hypervisor $remoteHost r $vmName";
    
    $descriptorSpec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"],  // stderr
    ];

    // lancement du processus concernant la restoration de la VM
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

	// sauvegarde réussie
        if (strpos($output, "est restorée") !== false) {
	    // utilisation d'une expression régulière pour extraire la portion JSON
    	    if (preg_match('/\{.*\}/s', $output, $matches)) {
        	$jsonOutput = $matches[0];	
		$result = json_decode($jsonOutput, true);

		$successMessage = "{$result['message']}";
	    }
    	} else {
            $errorMessage = "Échec de l'exécution de la commande de restoration pour la VM $vmName.";
	}
    }
}

// Lister les VMs
function getActiveAndInactiveDomains($remoteHost) {
    $command = "./hypervisor $remoteHost l";
    $output = shell_exec($command);

    // utilisation d'une expression régulière pour extraire la portion JSON
    if (preg_match('/\{.*\}/s', $output, $matches)) {
        $jsonOutput = $matches[0];

        $domains = json_decode($jsonOutput, true);
        
        return $domains;
    } else {
        $errorMessage = "Aucune donnée JSON valide trouvée dans la sortie.\n";
        return null;
    }
}

// Fonction de tri alphabétique
function sortDomainsAlphabetically(&$domains, $key = 'name') {
    usort($domains, function ($a, $b) use ($key) {
        return strcasecmp($a[$key], $b[$key]);
    });
}

$domains = getActiveAndInactiveDomains($_SESSION['remote_host']);
sortDomainsAlphabetically($domains['active_domains']);
sortDomainsAlphabetically($domains['inactive_domains']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire de VM</title>
    <style>
        .info {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
	}
        .info-message {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
    </style>
</head>
<body>
    <h1>Bienvenue sur le gestionnaire de VM</h1>
    <p>Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['remote_host']); ?></strong></p>

    <form method="post">
        <input type="hidden" name="logout" value="1">
        <button type="submit">Se déconnecter</button>
    </form>

    <h2>Liste des VMs</h2>
    <?php if (!empty($successMessage)): ?>
        <div class="info success">
            <p><?php echo htmlspecialchars($successMessage); ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="info error">
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
        </div>
   <?php endif; ?>
   <?php if (!empty($infoMessage)): ?>
        <div class="info info-message">
            <p><?php echo htmlspecialchars($infoMessage); ?></p>
        </div>
   <?php endif; ?>
    <table border="1">
        <thead>
            <tr>
                <th>Nom de la VM</th>
                <th>Statut de la VM</th>
                <th>ID</th>
                <th>Etat</th>
                <th>Mémoire maximale (Gbits)</th>
                <th>Mémoire actuelle (Gbits)</th>
                <th>Nombre de vCPUs</th>
                <th>Temps CPU</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Vérification des domaines actifs
            if (isset($domains['active_domains']) && is_array($domains['active_domains'])):
                foreach ($domains['active_domains'] as $domain): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($domain["name"]); ?></td>
                        <td>Active</td>
                        <td><?php echo htmlspecialchars($domain["id"]); ?></td>
                        <td><?php echo htmlspecialchars($domain["state"]); ?></td>
                        <td><?php echo htmlspecialchars($domain["maxMem"] / 1024 / 1024); ?> Gbits</td>
                        <td><?php echo htmlspecialchars($domain["memory"] / 1024 / 1024); ?> Gbits</td>
                        <td><?php echo htmlspecialchars($domain["nrVirtCpu"]); ?></td>
                        <td><?php echo htmlspecialchars($domain["cpuTime"]); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="vm_name" value="<?php echo htmlspecialchars($domain["name"]); ?>">
                                <button type="submit" name="stop_vm">Stopper</button>
			    </form>
			    <form method="post" style="display:inline;">
                                <input type="hidden" name="vm_name" value="<?php echo htmlspecialchars($domain["name"]); ?>">
                                <button type="submit" name="save_vm">Sauvegarder</button>
                            </form> 
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>

            <?php
            // Vérification des domaines inactifs
            if (isset($domains['inactive_domains']) && is_array($domains['inactive_domains'])):
                foreach ($domains['inactive_domains'] as $domain): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($domain["name"]); ?></td>
                        <td>Inactive</td>
                        <td colspan="6">N/A</td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="vm_name" value="<?php echo htmlspecialchars($domain["name"]); ?>">
                                <button type="submit" name="start_vm">Démarrer</button>
			    </form>
			    <form method="post" style="display:inline;">
                                <input type="hidden" name="vm_name" value="<?php echo htmlspecialchars($domain["name"]); ?>">
                                <button type="submit" name="restore_vm">Restorer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </tbody>
    </table>
</body>
</html>
