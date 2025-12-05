# 🔒 Configuration de Sécurité

## ⚠️ Configuration actuelle (Développement)

L'application actuelle est configurée pour un **environnement de développement** :
- HTTP (pas de HTTPS)
- Pas d'authentification
- API publique

**⚠️ Cette configuration N'EST PAS adaptée pour la production.**

---

## 🔐 Recommandations pour la Production

### 1. HTTPS/TLS

#### Option A : Avec Traefik + Let's Encrypt

```yaml
# docker-compose.prod.yml
services:
  traefik:
    image: traefik:v2.10
    command:
      - "--providers.docker=true"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
      - "--certificatesresolvers.letsencrypt.acme.email=votre@email.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web"
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
      - ./letsencrypt:/letsencrypt

  frontend:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.frontend.rule=Host(`votre-domaine.com`)"
      - "traefik.http.routers.frontend.entrypoints=websecure"
      - "traefik.http.routers.frontend.tls.certresolver=letsencrypt"
```

#### Option B : Avec Nginx + Certbot

```bash
# Installation
apt-get install certbot python3-certbot-nginx

# Obtention du certificat
certbot --nginx -d votre-domaine.com -d api.votre-domaine.com

# Auto-renouvellement
certbot renew --dry-run
```

---

### 2. Authentification API

#### JWT avec LexikJWTAuthenticationBundle

```bash
# Installation
composer require lexik/jwt-authentication-bundle
```

```yaml
# config/packages/security.yaml
security:
  firewalls:
    api:
      pattern: ^/api
      stateless: true
      jwt: ~

  access_control:
    - { path: ^/api/login, roles: PUBLIC_ACCESS }
    - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

#### Ou API Key simple

```yaml
# config/packages/security.yaml
security:
  firewalls:
    api:
      pattern: ^/api
      stateless: true
      custom_authenticators:
        - App\Security\ApiKeyAuthenticator
```

---

### 3. Rate Limiting

#### Avec Symfony Rate Limiter

```bash
composer require symfony/rate-limiter
```

```php
// src/EventListener/RateLimitListener.php
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitListener
{
    public function __construct(
        private RateLimiterFactory $apiLimiter
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $limiter = $this->apiLimiter->create($request->getClientIp());
        
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }
    }
}
```

#### Ou avec Nginx

```nginx
# nginx.conf
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;

location /api {
    limit_req zone=api_limit burst=20 nodelay;
    proxy_pass http://backend;
}
```

---

### 4. Headers de Sécurité (déjà partiellement configuré)

```nginx
# nginx.conf (à améliorer)
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

---

### 5. Secrets et Variables d'environnement

#### Développement

```yaml
# docker-compose.yml
environment:
  APP_SECRET: ${APP_SECRET}
  DATABASE_PASSWORD: ${DATABASE_PASSWORD}
```

```bash
# .env (à ne pas commiter)
APP_SECRET=your-secret-key-here
DATABASE_PASSWORD=strong-password-here
```

#### Production avec Docker Swarm

```yaml
# docker-compose.prod.yml
services:
  backend:
    environment:
      APP_SECRET_FILE: /run/secrets/app_secret
      DATABASE_PASSWORD_FILE: /run/secrets/db_password
    secrets:
      - app_secret
      - db_password

secrets:
  app_secret:
    external: true
  db_password:
    external: true
```

```bash
# Créer les secrets
echo "your-secret" | docker secret create app_secret -
echo "db-password" | docker secret create db_password -
```

---

### 6. CORS Configuration

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
  defaults:
    origin_regex: true
    allow_origin: ['https://votre-domaine.com']  # Pas '*'
    allow_methods: ['GET', 'POST', 'OPTIONS']
    allow_headers: ['Content-Type', 'Authorization']
    expose_headers: ['Link']
    max_age: 3600
  paths:
    '^/api/':
      allow_origin: ['https://votre-domaine.com']
```

---

### 7. Protection contre les injections SQL

✅ **Déjà fait** : Doctrine ORM avec requêtes préparées

```php
// ✅ BON (protégé)
$repository->findBy(['shortName' => $stationId]);

// ❌ MAUVAIS (vulnérable)
$em->createQuery("SELECT s FROM Station s WHERE s.shortName = '$stationId'");
```

---

### 8. Validation des données

✅ **Déjà fait** : API Platform avec validation Symfony

```php
use Symfony\Component\Validator\Constraints as Assert;

class RouteRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    public string $fromStationId;
}
```

---

### 9. Audit de sécurité

```bash
# Scanner les dépendances PHP
composer audit

# Scanner les dépendances npm
npm audit

# Scanner les images Docker (déjà dans CI/CD)
trivy image defi-fullstack-backend
```

---

### 10. Logs de sécurité

```yaml
# config/packages/monolog.yaml
monolog:
  channels: ['security']
  handlers:
    security:
      type: stream
      path: '%kernel.logs_dir%/security.log'
      level: warning
      channels: ['security']
```

---

## 📋 Checklist Sécurité Production

- [ ] HTTPS activé avec certificats valides
- [ ] Authentification API (JWT ou API Key)
- [ ] Rate limiting configuré
- [ ] CORS restrictif (pas de *)
- [ ] Headers de sécurité complets
- [ ] Secrets dans fichiers séparés (pas en clair)
- [ ] Firewall configuré (ports 80, 443 seulement)
- [ ] Backups automatiques de la DB
- [ ] Logs de sécurité activés
- [ ] Monitoring et alertes configurés
- [ ] Scan de vulnérabilités automatique
- [ ] APP_DEBUG=0 en production
- [ ] Mots de passe forts partout

---

## 🎓 Pour ce défi technique

**Note importante :** Ce défi est un **proof of concept** démontrant :
- Architecture fullstack moderne
- Qualité du code
- Tests automatisés
- CI/CD

**En production réelle**, tous les points de sécurité ci-dessus devraient être implémentés.

**Pour le défi MOB**, la configuration actuelle démontre :
- ✅ Connaissance des best practices (headers, secrets)
- ✅ Architecture sécurisable
- ✅ Scan de sécurité dans le CI/CD
- ⚠️ HTTPS/Auth seraient à ajouter pour une vraie production

---

**Documentation créée pour montrer la conscience des enjeux de sécurité et les solutions possibles.**
