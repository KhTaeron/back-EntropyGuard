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


### 2. Installer les dépendances
composer install

### 3. Mettre à jour la base
php bin/console doctrine:migrations:migrate --no-interaction

### 4. Lancer le serveur symfony 
symfony serve -d

### 5. Arrêter la base
docker compose down

---

L’API est disponible par défaut sur :  
http://127.0.0.1:8000