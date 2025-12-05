# Configuration Production

Ce fichier explique comment utiliser la configuration production avec HTTPS automatique.

## 📋 Vue d'ensemble

Le fichier `docker-compose.prod.yml` fournit une configuration production complète avec :

- ✅ **HTTPS automatique** avec Let's Encrypt (Traefik)
- ✅ **Reverse proxy** pour gérer plusieurs domaines
- ✅ **Security headers** (HSTS, X-Frame-Options, etc.)
- ✅ **Redirection automatique** HTTP → HTTPS
- ✅ **Génération automatique** des clés JWT
- ✅ **Volumes persistants** pour les données

---

## ⚠️ Important

**Ce fichier est un EXEMPLE de configuration production.**

Il n'est **PAS** utilisé par défaut. Le fichier `docker-compose.yml` (sans `.prod`) est utilisé pour le développement local et les tests CI/CD.

---

## 🚀 Utilisation en production

### **Prérequis**

1. Un serveur Linux (Ubuntu, Debian, etc.)
2. Un nom de domaine configuré (ex: `monapp.com`)
3. DNS pointant vers votre serveur :
    - `A record`: `monapp.com` → `IP_DU_SERVEUR`
    - `A record`: `api.monapp.com` → `IP_DU_SERVEUR`

### **Configuration**

1. **Copier le fichier d'environnement exemple :**
   ```bash
   cp .env.prod.example .env.prod
   ```

2. **Éditer `.env.prod` avec vos valeurs :**
   ```bash
   # Base de données
   MYSQL_ROOT_PASSWORD=mot_de_passe_root_securise_ici
   MYSQL_USER=mob_user
   MYSQL_PASSWORD=mot_de_passe_mysql_securise_ici

   # Symfony
   APP_SECRET=generez_un_secret_unique_ici_32_caracteres_minimum

   # JWT (optionnel, sera généré automatiquement si vide)
   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
   JWT_PASSPHRASE=

   # Domaines
   DOMAIN=monapp.com
   API_DOMAIN=api.monapp.com

   # Email pour Let's Encrypt
   LETSENCRYPT_EMAIL=votre-email@example.com
   ```

3. **Modifier `docker-compose.prod.yml` :**

   Remplacez toutes les occurrences de `votre-domaine.com` par votre vrai domaine :

   ```yaml
   # Ligne 28 : Email Let's Encrypt
   - "--certificatesresolvers.letsencrypt.acme.email=votre@email.com"
   
   # Lignes 110, 114 : Domaine API
   - "traefik.http.routers.api-http.rule=Host(`api.monapp.com`)"
   - "traefik.http.routers.api.rule=Host(`api.monapp.com`)"
   
   # Lignes 136 : Variable d'environnement frontend
   VITE_API_BASE_URL: https://api.monapp.com/api/v1
   
   # Lignes 143, 147 : Domaine frontend
   - "traefik.http.routers.frontend-http.rule=Host(`monapp.com`)"
   - "traefik.http.routers.frontend.rule=Host(`monapp.com`)"
   ```

### **Déploiement**

```bash
# 1. Cloner le projet sur le serveur
git clone https://github.com/baherdev/MOB-defi-fullstack.git
cd MOB-defi-fullstack

# 2. Configurer les variables d'environnement
cp .env.prod.example .env.prod
nano .env.prod  # Éditer avec vos valeurs

# 3. Modifier docker-compose.prod.yml avec vos domaines
nano docker-compose.prod.yml

# 4. Lancer avec la configuration production
docker compose -f docker-compose.prod.yml up -d --build

# 5. Vérifier que tout fonctionne
docker compose -f docker-compose.prod.yml logs -f
```

### **Premiers utilisateurs**

Pour créer les premiers utilisateurs en production :

```bash
# Entrer dans le conteneur backend
docker exec -it mob-backend bash

# Créer un utilisateur admin
php bin/console app:create-user admin@monapp.com password123 ROLE_ADMIN

# Sortir du conteneur
exit
```

---

## 🔒 Sécurité en production

### **Dashboard Traefik**

Le dashboard Traefik est accessible sur le port 8080. **Il faut le protéger !**

**Option 1 : Désactiver complètement** (recommandé)
```yaml
# Dans docker-compose.prod.yml, commenter ces lignes :
# - "--api.dashboard=true"
# - "--api.insecure=false"
# Et retirer le port 8080
```

**Option 2 : Protéger par mot de passe**
```bash
# Générer un mot de passe
htpasswd -nb admin votre_mot_de_passe
# Copier le résultat et l'ajouter comme middleware Traefik
```

### **Firewall**

Configurez un firewall (UFW, iptables) pour autoriser uniquement :
- Port 80 (HTTP)
- Port 443 (HTTPS)
- Port 22 (SSH)

```bash
# Exemple avec UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### **Mots de passe forts**

- ✅ Utilisez des mots de passe générés aléatoirement (32+ caractères)
- ✅ Ne commitez **JAMAIS** le fichier `.env.prod`
- ✅ Changez `APP_SECRET` en production

### **Sauvegardes**

Sauvegardez régulièrement les volumes Docker :

```bash
# Sauvegarder la base de données
docker exec mob-mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD mob_routing > backup.sql

# Sauvegarder les volumes
docker run --rm -v mob_db_data:/data -v $(pwd):/backup alpine tar czf /backup/db_data_backup.tar.gz /data
```

---

## 📊 Monitoring

### **Logs**

```bash
# Voir tous les logs
docker compose -f docker-compose.prod.yml logs -f

# Logs d'un service spécifique
docker compose -f docker-compose.prod.yml logs -f backend
docker compose -f docker-compose.prod.yml logs -f traefik
```

### **Santé des conteneurs**

```bash
docker compose -f docker-compose.prod.yml ps
```

### **Espace disque**

```bash
# Nettoyer les images inutilisées
docker system prune -a

# Voir l'utilisation
docker system df
```

---

## 🔄 Mises à jour

```bash
# 1. Récupérer les dernières modifications
git pull

# 2. Reconstruire et redémarrer
docker compose -f docker-compose.prod.yml up -d --build

# 3. Appliquer les migrations
docker exec mob-backend php bin/console doctrine:migrations:migrate --no-interaction
```

---

## ⚡ Performance

### **Cache**

En production, Symfony met en cache automatiquement. Pour vider le cache :

```bash
docker exec mob-backend php bin/console cache:clear --env=prod
```

### **Optimisation Composer**

Les dépendances sont déjà optimisées dans le Dockerfile avec :
```dockerfile
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative
```

---

## 🆘 Dépannage

### **Les certificats Let's Encrypt ne se génèrent pas**

- Vérifiez que les DNS pointent bien vers votre serveur
- Vérifiez que les ports 80 et 443 sont ouverts
- Attendez quelques minutes (propagation DNS)
- Consultez les logs Traefik : `docker compose -f docker-compose.prod.yml logs traefik`

### **Erreur 502 Bad Gateway**

- Le backend n'a pas démarré correctement
- Vérifiez : `docker compose -f docker-compose.prod.yml logs backend`

### **JWT Token errors**

- Les clés JWT ne sont pas générées
- Vérifiez : `docker exec mob-backend ls -la config/jwt/`
- Régénérez : `docker exec mob-backend php bin/console lexik:jwt:generate-keypair`

---

## 📚 Ressources

- [Documentation Traefik](https://doc.traefik.io/traefik/)
- [Let's Encrypt](https://letsencrypt.org/)
- [Symfony Deployment](https://symfony.com/doc/current/deployment.html)
- [Docker Compose Production](https://docs.docker.com/compose/production/)

---

## ✅ Checklist de déploiement

Avant de mettre en production :

- [ ] Nom de domaine configuré et DNS propagé
- [ ] Fichier `.env.prod` créé avec des valeurs sécurisées
- [ ] Tous les `votre-domaine.com` remplacés dans `docker-compose.prod.yml`
- [ ] Email Let's Encrypt configuré
- [ ] Firewall configuré (ports 80, 443, 22)
- [ ] Dashboard Traefik désactivé ou protégé
- [ ] Sauvegarde automatique configurée
- [ ] Monitoring configuré
- [ ] Tests effectués en pré-production

---

**Pour le développement local, utilisez simplement `docker-compose.yml` !**
