# Entropy Guard – Backend

Application **Symfony** connectée à une base **PostgreSQL** via Docker Compose.

## Prérequis
- Docker & Docker Compose
- PHP ≥ 8.2 installé en local
- Composer ≥ 2
- (Recommandé) Symfony CLI

---

### 1. Démarrer la base de données
docker compose up -d


### 2. Générer les clés JWT
RUN Les commandes : 
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\generate-jwt-keys.ps1

### 3. Installer les dépendances
composer install

### 4. Mettre à jour la base
php bin/console doctrine:migrations:migrate --no-interaction

### 5. Lancer le serveur symfony 
symfony serve -d


---

L’API est disponible par défaut sur :  
http://127.0.0.1:8000