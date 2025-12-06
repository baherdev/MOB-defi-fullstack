# Sécurité

Ce document décrit les mesures de sécurité mises en place et les recommandations pour un déploiement en production.

---

## ⚠️ Avertissement Important

**La configuration de production fournie dans ce projet (Traefik + HTTPS) est un EXEMPLE et n'a PAS été testée en environnement réel.**

- ✅ La configuration de **développement** a été testée et fonctionne
- ⚠️ La configuration de **production** est fournie comme **référence** mais nécessite :
    - Tests approfondis avant mise en production
    - Adaptation à votre infrastructure spécifique
    - Audit de sécurité par un professionnel
    - Configuration des secrets et credentials appropriés

**Nous ne garantissons pas la sécurité de la configuration production fournie. Utilisez-la à vos risques et périls.**

---

## 🔐 Configuration Actuelle (Développement)

L'application implémente les mesures de sécurité suivantes en environnement de développement :

### ✅ Authentification JWT

**Implémentation complète avec LexikJWTAuthenticationBundle**

- **Clés RSA** : Génération automatique de paires de clés publique/privée
- **Algorithme** : RS256 (RSA avec SHA-256)
- **Protection des endpoints** : Tous les endpoints `/api/v1/*` requièrent un token JWT valide
- **Login** : `POST /api/login` avec email/password
- **Token dans header** : `Authorization: Bearer {token}`
- **Durée de validité** : 3600 secondes (1 heure) - configurable
- **Refresh** : À implémenter si nécessaire (JWTRefreshTokenBundle)

**Configuration :**
```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

**Endpoints protégés :**
- ✅ `POST /api/v1/routes` - Calcul d'itinéraire
- ✅ `GET /api/v1/stats/distances` - Statistiques
- ❌ `POST /api/login` - Public (nécessaire pour obtenir le token)

### ✅ Validation des Entrées

**Backend (Symfony Validator)**
- Validation des codes de station (format et existence)
- Validation des codes analytiques
- Validation des types de données (DTO avec contraintes)
- Protection contre les injections SQL (Doctrine ORM)

**Frontend (Vue.js)**
- Validation des formulaires côté client
- Vérification de la présence du token avant requêtes API
- Gestion des erreurs 401 (redirection vers login)

### ✅ CORS (Cross-Origin Resource Sharing)

**Configuration pour développement :**
```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['http://localhost:3000']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
        allow_headers: ['*']
        expose_headers: ['Link']
        max_age: 3600
```

⚠️ **En production** : Restreindre `allow_origin` à votre domaine spécifique.

### ✅ Protection CSRF

- **Pas nécessaire** pour une API stateless avec JWT
- Les tokens JWT remplacent la protection CSRF traditionnelle

### ✅ Hashage des Mots de Passe

**Symfony PasswordHasher**
```php
// Utilise bcrypt ou argon2i automatiquement
$hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
```

**Configuration :**
- Algorithme : `auto` (bcrypt par défaut)
- Cost : 13 en production, 4 en test (pour la rapidité)

### ⚠️ Limitations de l'Environnement de Développement

**Ces configurations NE SONT PAS adaptées pour la production :**

- ❌ **HTTP uniquement** (pas de HTTPS)
- ❌ **CORS permissif** (autorise localhost)
- ❌ **Debug mode activé** (`APP_DEBUG=1`)
- ❌ **Logs verbeux** (stack traces visibles)
- ❌ **Secrets en clair** dans `.env` (non chiffré)
- ❌ **Base de données locale** sans backup
- ❌ **Pas de rate limiting** sur les endpoints
- ❌ **Pas de monitoring** de sécurité

---

## 🔒 Recommandations pour la Production

### 1. HTTPS Obligatoire ⚠️

**Critique : Sans HTTPS, les tokens JWT sont transmis en clair !**

```yaml
# docker-compose.prod.yml utilise Traefik + Let's Encrypt
# ATTENTION : Configuration non testée, à adapter
traefik:
  command:
    - "--certificatesresolvers.letsencrypt.acme.email=votre@email.com"
    - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
```

**Actions requises :**
- ✅ Configurer un nom de domaine
- ✅ Pointer les DNS vers votre serveur
- ✅ Tester le renouvellement automatique des certificats
- ✅ Forcer HTTPS (redirection HTTP → HTTPS)
- ✅ Configurer HSTS

### 2. Variables d'Environnement Sécurisées

**NE JAMAIS commiter les secrets !**

```bash
# .env.prod (à créer sur le serveur, NE PAS commiter)
APP_SECRET=générez_un_secret_vraiment_aléatoire_32_caractères_minimum
MYSQL_ROOT_PASSWORD=mot_de_passe_très_complexe_et_aléatoire
JWT_PASSPHRASE=phrase_de_passe_pour_clés_jwt
```

**Générer des secrets sécurisés :**
```bash
# Secret Symfony
php -r "echo bin2hex(random_bytes(32));"

# Mot de passe MySQL
openssl rand -base64 32

# Passphrase JWT
openssl rand -base64 48
```

### 3. Rate Limiting

**Protéger contre les attaques par force brute**

```yaml
# À implémenter : symfony/rate-limiter
framework:
    rate_limiter:
        login:
            policy: 'sliding_window'
            limit: 5
            interval: '15 minutes'
```

**Endpoints critiques à protéger :**
- `/api/login` : Max 5 tentatives / 15 min
- `/api/v1/routes` : Max 100 requêtes / heure / IP
- `/api/v1/stats/*` : Max 50 requêtes / heure / IP

### 4. Security Headers

**Configuration Traefik (dans docker-compose.prod.yml) :**

```yaml
# ATTENTION : Configuration non testée
traefik.http.middlewares.security-headers.headers:
  - customResponseHeaders.X-Frame-Options=DENY
  - customResponseHeaders.X-Content-Type-Options=nosniff
  - customResponseHeaders.X-XSS-Protection=1; mode=block
  - customResponseHeaders.Strict-Transport-Security=max-age=31536000; includeSubDomains
  - customResponseHeaders.Referrer-Policy=no-referrer-when-downgrade
  - customResponseHeaders.Permissions-Policy=geolocation=(), microphone=(), camera=()
```

### 5. Base de Données

**Sécuriser MySQL :**

```bash
# Créer un utilisateur dédié avec privilèges minimaux
CREATE USER 'mob_app'@'%' IDENTIFIED BY 'mot_de_passe_complexe';
GRANT SELECT, INSERT, UPDATE, DELETE ON mob_routing.* TO 'mob_app'@'%';
FLUSH PRIVILEGES;

# Désactiver l'utilisateur root distant
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
FLUSH PRIVILEGES;
```

**Sauvegardes automatiques :**
```bash
# Cron quotidien
0 2 * * * docker exec mob-mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD mob_routing > /backup/mob_$(date +\%Y\%m\%d).sql
```

### 6. Monitoring et Logs

**Implémenter :**
- ✅ Logs centralisés (ELK, Graylog, ou Loki)
- ✅ Alertes sur erreurs critiques
- ✅ Monitoring des tentatives de connexion échouées
- ✅ Alertes sur usage anormal (spike de requêtes)

**Outils recommandés :**
- **Sentry** : Monitoring d'erreurs
- **Prometheus + Grafana** : Métriques
- **Fail2Ban** : Blocage automatique d'IPs malveillantes

### 7. Mises à Jour de Sécurité

**Automatiser les scans :**
```yaml
# .github/workflows/security.yml
- name: Security Audit
  run: |
    composer audit
    npm audit
    docker scan mob-backend
```

**Tenir à jour :**
- Dépendances PHP (Composer)
- Dépendances NPM
- Images Docker de base
- Symfony / Vue.js

### 8. Firewall

**Configurer UFW (Ubuntu) :**
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw enable
```

### 9. Clés JWT en Production

**NE PAS utiliser les clés de dev !**

```bash
# Sur le serveur de production
docker exec mob-backend php bin/console lexik:jwt:generate-keypair --overwrite

# Vérifier les permissions
docker exec mob-backend ls -la config/jwt/
# private.pem : 600 (lecture seule par propriétaire)
# public.pem : 644 (lecture par tous)
```

**Rotation des clés :**
- Régénérer tous les 6 mois
- Invalider tous les tokens existants
- Prévenir les utilisateurs

---

## 🎯 Checklist de Sécurité Production

Avant de déployer en production, vérifiez :

### Configuration
- [ ] HTTPS activé avec certificat valide (Let's Encrypt ou autre)
- [ ] Redirection HTTP → HTTPS forcée
- [ ] HSTS configuré (min 1 an)
- [ ] Variables d'environnement sécurisées (pas de valeurs par défaut)
- [ ] Secrets générés aléatoirement (APP_SECRET, passwords, JWT_PASSPHRASE)
- [ ] `APP_DEBUG=0` en production
- [ ] `APP_ENV=prod` en production

### Authentification & Autorisation
- [x] JWT implémenté et testé
- [ ] Rate limiting sur `/api/login`
- [ ] Clés JWT générées spécifiquement pour la production
- [ ] Token TTL approprié (pas trop long)
- [ ] Refresh token si nécessaire
- [ ] Validation stricte des permissions

### Base de Données
- [ ] Utilisateur MySQL avec privilèges minimaux (pas root)
- [ ] Mot de passe fort et aléatoire
- [ ] Connexion depuis l'application uniquement (pas d'accès externe)
- [ ] Sauvegardes automatiques configurées
- [ ] Sauvegardes testées (restauration)

### Réseau
- [ ] Firewall configuré (ports 22, 80, 443 uniquement)
- [ ] CORS restreint au domaine de production
- [ ] Rate limiting global sur l'API
- [ ] Protection DDoS (Cloudflare ou équivalent)

### Monitoring
- [ ] Logs centralisés configurés
- [ ] Alertes sur erreurs critiques
- [ ] Monitoring des ressources (CPU, RAM, disque)
- [ ] Monitoring de disponibilité (uptime)
- [ ] Alertes sur tentatives d'intrusion

### Maintenance
- [ ] Process de mise à jour défini
- [ ] Scan de vulnérabilités automatique
- [ ] Plan de réponse aux incidents
- [ ] Documentation pour l'équipe ops

---

## 🚨 Vulnérabilités Connues et Acceptées (Dev)

En environnement de développement, les "vulnérabilités" suivantes sont acceptées :

1. **HTTP sans HTTPS** : OK pour dev local
2. **CORS permissif** : Nécessaire pour localhost:3000
3. **Debug mode** : Facilite le développement
4. **Secrets dans .env** : OK si `.env` est dans `.gitignore`
5. **Pas de rate limiting** : Simplifie les tests

**Ces vulnérabilités DOIVENT être corrigées en production.**

---

## 📚 Ressources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Symfony Security Best Practices](https://symfony.com/doc/current/security.html)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)
- [Docker Security](https://docs.docker.com/engine/security/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)

---

## 🆘 En Cas d'Incident de Sécurité

1. **Isoler** : Couper l'accès au système compromis
2. **Analyser** : Vérifier les logs pour comprendre l'attaque
3. **Corriger** : Appliquer le correctif de sécurité
4. **Régénérer** : Changer tous les secrets (JWT, passwords, API keys)
5. **Notifier** : Informer les utilisateurs si données compromises
6. **Documenter** : Post-mortem pour éviter la récidive

---

**La sécurité est un processus continu, pas un état final. Restez vigilant !** 🔒
