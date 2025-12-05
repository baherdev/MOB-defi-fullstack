# 🚀 Guide de Déploiement - MOB Routing Application

## Prérequis

- Docker >= 20.10
- Docker Compose >= 2.0
- Git
- Ports disponibles : 3000 (frontend), 8000 (backend), 3306 (database)

---

## 🐳 Déploiement rapide (Développement)

### Installation

```bash
# 1. Cloner le repository
git clone https://github.com/baherdev/defi-fullstack.git
cd defi-fullstack

# 2. Démarrer tous les services
docker compose up -d

# 3. Attendre que les services démarrent (30 secondes)
# Les migrations et fixtures se chargent automatiquement

# 4. Vérifier que tout tourne
docker compose ps
```

### Accès

- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8000/api/v1
- **Documentation API** : http://localhost:8000/api/docs
- **Database** : localhost:3306

---

## 📦 Structure des conteneurs

```
┌─────────────────────────────────────────────┐
│           MOB Routing Application           │
├─────────────────────────────────────────────┤
│                                             │
│  Frontend (Vue.js)          :3000          │
│       ↓                                     │
│  Backend Nginx              :8000          │
│       ↓                                     │
│  Backend PHP-FPM            :9000          │
│       ↓                                     │
│  MySQL Database             :3306          │
│                                             │
└─────────────────────────────────────────────┘
```

**Services démarrés :**
- `mob-frontend` : Interface utilisateur Vue.js
- `mob-webserver` : Serveur Nginx pour le backend
- `mob-backend` : Application Symfony PHP-FPM
- `mob-mysql` : Base de données MySQL 8.0

---

## 🔧 Commandes utiles

### Gestion des services

```bash
# Démarrer l'application
docker compose up -d

# Arrêter l'application
docker compose down

# Redémarrer un service spécifique
docker compose restart backend

# Voir l'état des conteneurs
docker compose ps

# Arrêter et supprimer tout (y compris volumes)
docker compose down -v
```

### Logs et debugging

```bash
# Voir tous les logs
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f backend
docker compose logs -f frontend

# Logs des 100 dernières lignes
docker compose logs --tail=100 backend
```

### Rebuild après modifications

```bash
# Rebuild tous les services
docker compose up -d --build

# Rebuild un service spécifique
docker compose build --no-cache backend
docker compose up -d backend

# Rebuild le frontend après modifications du code
docker compose build --no-cache frontend
docker compose up -d frontend
```

### Accéder aux conteneurs

```bash
# Backend (PHP)
docker exec -it mob-backend sh

# Frontend
docker exec -it mob-frontend sh

# MySQL
docker exec -it mob-mysql mysql -u mob_user -pmob_password mob_routing
```

---

## 🗄️ Base de données

### Informations de connexion

- **Host** : localhost (ou `database` depuis les conteneurs)
- **Port** : 3306
- **Database** : mob_routing
- **User** : mob_user
- **Password** : mob_password
- **Root Password** : root

### Commandes Doctrine

```bash
# Migrations
docker exec -it mob-backend php bin/console doctrine:migrations:migrate

# Charger les fixtures
docker exec -it mob-backend php bin/console doctrine:fixtures:load --no-interaction

# Voir le schéma de la base
docker exec -it mob-backend php bin/console doctrine:schema:validate

# Créer une nouvelle migration
docker exec -it mob-backend php bin/console make:migration
```

### Accès direct MySQL

```bash
# Via ligne de commande
docker exec -it mob-mysql mysql -u mob_user -pmob_password mob_routing

# Exemples de requêtes
docker exec -it mob-mysql mysql -u mob_user -pmob_password mob_routing -e "SELECT COUNT(*) FROM stations;"
docker exec -it mob-mysql mysql -u mob_user -pmob_password mob_routing -e "SELECT * FROM code_analytics;"
```

### Backup et restore

```bash
# Backup
docker exec mob-mysql mysqldump -u mob_user -pmob_password mob_routing > backup.sql

# Restore
docker exec -i mob-mysql mysql -u mob_user -pmob_password mob_routing < backup.sql
```

---

## 🧪 Lancer les tests

### Backend (PHPUnit)

```bash
# Tous les tests
docker exec -it mob-backend php bin/phpunit

# Tests avec couverture
docker exec -it mob-backend php bin/phpunit --coverage-text

# Tests spécifiques
docker exec -it mob-backend php bin/phpunit tests/Unit
docker exec -it mob-backend php bin/phpunit tests/Integration
```

### Frontend (Vitest)

```bash
# Tous les tests
docker exec -it mob-frontend npm test

# Tests en mode watch
docker exec -it mob-frontend npm test -- --watch

# Tests avec couverture
docker exec -it mob-frontend npm run test:coverage
```

### Linting

```bash
# PHPStan (backend)
docker exec -it mob-backend vendor/bin/phpstan analyse src --level=6

# ESLint (frontend)
docker exec -it mob-frontend npm run lint

# Fix automatique des erreurs ESLint
docker exec -it mob-frontend npm run lint -- --fix
```

---

## ⚙️ Configuration

### Variables d'environnement Backend

Fichier : `docker-compose.yml` (section backend)

```yaml
environment:
  APP_ENV: dev                    # dev | prod | test
  APP_DEBUG: 1                    # 0 | 1
  APP_SECRET: your-secret-key     # Clé secrète Symfony
  DATABASE_URL: mysql://...       # Connexion MySQL
```

### Variables d'environnement Frontend

Fichier : `docker-compose.yml` (section frontend)

```yaml
environment:
  VITE_API_BASE_URL: http://localhost:8000/api/v1
```

Pour le développement local (sans Docker), créez `.env.local` dans `mob-routing-frontend/` :

```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

---

## 🔒 Configuration de production

### Checklist de sécurité

- [ ] Changer `APP_SECRET` dans docker-compose.yml
- [ ] Utiliser des mots de passe forts pour MySQL
- [ ] Passer `APP_ENV=prod` et `APP_DEBUG=0`
- [ ] Configurer HTTPS avec certificats SSL
- [ ] Activer les logs de production
- [ ] Configurer un firewall
- [ ] Limiter les ressources Docker (CPU, RAM)

### Configuration SSL/TLS (Production)

#### Option 1 : Avec Traefik

```yaml
# Ajouter dans docker-compose.yml
services:
  traefik:
    image: traefik:v2.10
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - ./traefik.yml:/traefik.yml
      - ./acme.json:/acme.json
```

#### Option 2 : Avec Certbot + Nginx

```bash
# Installer Certbot
apt-get install certbot python3-certbot-nginx

# Obtenir certificat
certbot --nginx -d votre-domaine.com

# Auto-renouvellement
certbot renew --dry-run
```

### Optimisations de production

#### Backend

```yaml
# docker-compose.yml
backend:
  environment:
    APP_ENV: prod
    APP_DEBUG: 0
  deploy:
    resources:
      limits:
        cpus: '1'
        memory: 512M
      reservations:
        cpus: '0.5'
        memory: 256M
```

#### Frontend

- Activer la compression gzip (déjà configuré dans nginx.conf)
- Configurer le cache des assets statiques
- Utiliser un CDN pour les fichiers statiques

#### Base de données

```yaml
mysql:
  command: --default-authentication-plugin=mysql_native_password --max_connections=200
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
```

---

## 🐛 Dépannage

### Le backend ne démarre pas

**Symptôme :** `docker compose ps` ne montre pas `mob-backend`

**Solutions :**

```bash
# Voir les logs
docker compose logs backend

# Vérifier que MySQL est prêt
docker compose ps database

# Redémarrer MySQL puis backend
docker compose restart database
sleep 10
docker compose restart backend
```

### Le frontend affiche "Cannot connect to API"

**Symptôme :** Erreur de connexion dans la console du navigateur

**Solutions :**

1. Vérifier que le backend est accessible :
```bash
curl http://localhost:8000/api/v1
```

2. Vérifier la variable d'environnement :
```bash
docker exec -it mob-frontend env | grep VITE_API
```

3. Vérifier la configuration Nginx du frontend :
```bash
docker exec -it mob-frontend cat /etc/nginx/conf.d/default.conf
```

### Erreur "Connection reset by peer"

**Cause :** Configuration Nginx manquante ou incorrecte

**Solution :**

```bash
# Vérifier que nginx.conf existe
ls -la mob-routing-frontend/nginx.conf

# Rebuild le frontend
docker compose build --no-cache frontend
docker compose up -d frontend
```

### Erreur "Station not found"

**Cause :** Les fixtures ne sont pas chargées

**Solution :**

```bash
# Charger les fixtures manuellement
docker exec -it mob-backend php bin/console doctrine:fixtures:load --no-interaction

# Vérifier les données
docker exec -it mob-mysql mysql -u mob_user -pmob_password mob_routing -e "SELECT COUNT(*) FROM stations;"
```

### Port déjà utilisé

**Symptôme :** `Error starting userland proxy: listen tcp4 0.0.0.0:3000: bind: address already in use`

**Solutions :**

```bash
# Trouver le processus utilisant le port
lsof -i :3000

# Tuer le processus
kill -9 <PID>

# Ou changer le port dans docker-compose.yml
ports:
  - "3001:80"  # Au lieu de 3000:80
```

### Problèmes de permissions

**Symptôme :** Erreurs de permissions sur `var/cache`, `var/log`

**Solution :**

```bash
# Depuis le conteneur
docker exec -it mob-backend chown -R www-data:www-data var/

# Ou depuis l'hôte
sudo chown -R $(whoami):$(whoami) mob-routing-api/var/
```

---

## 📊 Monitoring et Logs

### Voir les ressources utilisées

```bash
# Stats en temps réel
docker stats

# Utilisation disque
docker system df
```

### Logs de production

Configuration recommandée dans `docker-compose.yml` :

```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

### Health checks

Les health checks sont déjà configurés pour MySQL. Pour ajouter au backend :

```yaml
backend:
  healthcheck:
    test: ["CMD", "php", "-v"]
    interval: 30s
    timeout: 10s
    retries: 3
```

---

## 🔄 Mise à jour

### Mettre à jour l'application

```bash
# 1. Pull les dernières modifications
git pull origin main

# 2. Rebuild et redémarrer
docker compose down
docker compose up -d --build

# 3. Appliquer les migrations
docker exec -it mob-backend php bin/console doctrine:migrations:migrate --no-interaction
```

### Mettre à jour les dépendances

```bash
# Backend
cd mob-routing-api
composer update
cd ..

# Frontend
cd mob-routing-frontend
npm update
cd ..

# Rebuild
docker compose up -d --build
```

---

## 📈 Scalabilité

### Horizontal Scaling (plusieurs instances)

Pour scaler horizontalement, utilisez Docker Swarm ou Kubernetes.

**Exemple avec Docker Swarm :**

```bash
# Initialiser Swarm
docker swarm init

# Déployer avec replicas
docker stack deploy -c docker-compose.yml mob-app

# Scaler un service
docker service scale mob-app_backend=3
```

### Vertical Scaling (plus de ressources)

Modifier les limites dans `docker-compose.yml` :

```yaml
deploy:
  resources:
    limits:
      cpus: '2'
      memory: 1G
```

---

## 🎯 Checklist de déploiement

### Avant le déploiement

- [ ] Tests passent (backend + frontend)
- [ ] Linting sans erreurs
- [ ] Variables d'environnement configurées
- [ ] Secrets changés (APP_SECRET, mots de passe)
- [ ] SSL/TLS configuré (production)
- [ ] Backup de la base de données (si mise à jour)

### Après le déploiement

- [ ] Vérifier que tous les conteneurs tournent : `docker compose ps`
- [ ] Tester l'API : `curl http://localhost:8000/api/v1`
- [ ] Tester le frontend : Ouvrir http://localhost:3000
- [ ] Vérifier les logs : `docker compose logs`
- [ ] Tester un calcul d'itinéraire complet
- [ ] Vérifier les statistiques

---

## 📞 Support

Pour toute question :
- Consultez le [README.md](./README.md)
- Consultez [ARCHITECTURE.md](./ARCHITECTURE.md) pour les détails techniques
- Ouvrez une issue sur GitHub

---

## 📝 Notes importantes

### Données de test
- **44 stations** chargées par les fixtures (sur 108 disponibles)
- **5 codes analytiques** : PASSAGER, FRET, MAINTENANCE, TEST, TOURISME
- Les fixtures se chargent automatiquement au démarrage du backend

### Environnement de développement
- `APP_ENV=dev` permet de charger DoctrineFixturesBundle
- En production, utilisez `APP_ENV=prod` et chargez les données via import SQL

### Ports utilisés
- 3000 : Frontend
- 8000 : Backend API
- 3306 : MySQL

Si ces ports sont occupés, modifiez-les dans `docker-compose.yml`.

---

**L'application est maintenant prête ! Pour toute question, consultez la documentation ou les logs.** 🚀
