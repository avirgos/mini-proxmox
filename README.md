# mini-proxmox

## Prérequis

- Paquets `php` requis

Debian :

```sh
sudo apt-get update
sudo apt-get install php -y
```

RHEL/CentOS :

```sh
sudo dnf update
sudo dnf install php -y
```

- Disposer d'une clé SSH

Générez votre clé SSH :

```sh
ssh-keygen -t ed25519
```

Ajout de la clé publique sur votre périphérique :

```sh
ssh-copy-id -i ~/.ssh/id_ed25519.pub <utilisateur>@<machine>
```

## Utilisation

```bash
./deploy.sh
```
