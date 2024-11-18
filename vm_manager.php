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

// Exécution du programme avec l'option "c"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_vm'])) {
    $vmName = escapeshellarg($_POST['vm_name']);
    $remoteHost = escapeshellarg($_SESSION['remote_host']);

    // Commande complète avec le nom de la VM
    $command = "./hypervisor $remoteHost c $vmName";
    $output = shell_exec($command);

    // Vérifier et afficher la sortie du programme
    if ($output) {
        echo "<p>Commande exécutée : $output</p>";
    } else {
        echo "<p>Échec de l'exécution de la commande pour la VM $vmName.</p>";
    }
    // Redirection pour éviter le rechargement de POST
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Exécution du programme avec l'option "d"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stop_vm'])) {
    $vmName = escapeshellarg($_POST['vm_name']);
    $remoteHost = escapeshellarg($_SESSION['remote_host']);

    // Commande complète avec le nom de la VM
    $command = "./hypervisor $remoteHost d $vmName";
    $output = shell_exec($command);

    // Vérifier et afficher la sortie du programme
    if ($output) {
        echo "<p>Commande exécutée : $output</p>";
    } else {
        echo "<p>Échec de l'exécution de la commande pour la VM $vmName.</p>";
    }
    // Redirection pour éviter le rechargement de POST
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
        echo "Aucune donnée JSON valide trouvée dans la sortie.\n";
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

// Trier les domaines actifs
if (isset($domains['active_domains']) && is_array($domains['active_domains'])) {
    sortDomainsAlphabetically($domains['active_domains']);
}

// Trier les domaines inactifs
if (isset($domains['inactive_domains']) && is_array($domains['inactive_domains'])) {
    sortDomainsAlphabetically($domains['inactive_domains']);
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

    <form method="post">
        <input type="hidden" name="logout" value="1">
        <button type="submit">Se déconnecter</button>
    </form>

    <h2>Liste des VMs</h2>
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
                                <button type="submit" name="start_vm">Démarrer</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="vm_name" value="<?php echo htmlspecialchars($domain["name"]); ?>">
                                <button type="submit" name="stop_vm">Stopper</button>
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
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </tbody>
    </table>
</body>
</html>
