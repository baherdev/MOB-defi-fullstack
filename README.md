# 🚂 MOB Routing Application

Application fullstack de calcul d'itinéraires ferroviaires pour le MOB (Montreux-Oberland-Bernois).

![CI/CD](https://github.com/VOTRE-USERNAME/VOTRE-REPO/workflows/CI%2FCD%20Pipeline/badge.svg)

---

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Technologies](#technologies)
- [Fonctionnalités](#fonctionnalités)
- [Installation rapide](#installation-rapide)
- [Architecture](#architecture)
- [Tests](#tests)
- [Documentation](#documentation)
- [Déploiement](#déploiement)

---

## 🎯 Vue d'ensemble

Cette application permet de :
- **Calculer des itinéraires** entre deux gares du réseau MOB en utilisant l'algorithme de Dijkstra
- **Visualiser les statistiques** de distance agrégées par code analytique
- **Gérer plusieurs réseaux** ferroviaires (MOB, MVR-ce)

L'application respecte la spécification OpenAPI fournie et implémente une architecture moderne avec Docker, tests automatisés et CI/CD.

---

## 🛠️ Technologies

### Backend
- **PHP 8.4** avec **Symfony 7.1**
- **API Platform** pour l'API REST
- **Doctrine ORM** avec migrations
- **MySQL 8.0**
- **PHPUnit** pour les tests
- **PHPStan** pour l'analyse statique

### Frontend
- **Vue.js 3** avec **Composition API**
- **TypeScript 5**
- **Vuetify 3** pour l'UI
- **Vite** pour le build
- **Vitest** pour les tests

### Infrastructure
- **Docker** & **Docker Compose**
- **GitHub Actions** pour le CI/CD
- **Nginx** comme serveur web et reverse proxy

---

## ✨ Fonctionnalités

### API REST (Backend)

#### `POST /api/v1/routes`
Calcule l'itinéraire optimal entre deux gares.

**Requête :**
```json
{
  "fromStationId": "AVA",
  "toStationId": "BLON",
  "analyticCode": "PASSAGER"
}
```

**Réponse :**
```json
{
  "id": "1",
  "fromStationId": "AVA",
  "toStationId": "BLON",
  "analyticCode": "PASSAGER",
  "distanceKm": 6.65,
  "path": ["AVA", "SDY", "CABY", "CHAN", "BLON"],
  "createdAt": "2025-12-02T20:12:41+00:00"
}
```

#### `GET /api/v1/stats/distances`
Récupère les statistiques de distance agrégées.

**Paramètres optionnels :**
- `from` : Date de début (ISO 8601)
- `to` : Date de fin (ISO 8601)
- `groupBy` : Groupement (none, day, month, year)

**Réponse :**
```json
{
  "from": null,
  "to": null,
  "groupBy": "none",
  "items": [
    {
      "analyticCode": "PASSAGER",
      "totalDistanceKm": 41.06
    }
  ]
}
```

### Interface Web (Frontend)

- **Calculateur d'itinéraires** : Formulaire interactif avec sélection de gares
- **Visualisation des statistiques** : Graphiques et tableaux des distances parcourues
- **Interface responsive** : Compatible mobile et desktop

---

## 🚀 Installation rapide

### Prérequis

- Docker >= 20.10
- Docker Compose >= 2.0
- Ports disponibles : 3000, 8000, 3306

### Démarrage en 3 commandes

```bash
# 1. Cloner le repository
git clone https://github.com/VOTRE-USERNAME/defi-fullstack.git
cd defi-fullstack

# 2. Démarrer l'application
docker compose up -d

# 3. Attendre 30 secondes (le temps que MySQL démarre)
# L'application est prête !
```

### Accès

- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8000/api/v1
- **Documentation API** : http://localhost:8000/api/docs

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────┐
│         MOB Routing Application             │
├─────────────────────────────────────────────┤
│                                             │
│  Frontend (Vue.js + Vuetify)   :3000       │
│           ↓                                 │
│  Nginx (Reverse Proxy)         :8000       │
│           ↓                                 │
│  Backend (Symfony + API Platform)          │
│           ↓                                 │
│  MySQL Database                :3306       │
│                                             │
└─────────────────────────────────────────────┘
```

### Structure du projet

```
defi-fullstack/
├── .github/
│   └── workflows/
│       └── ci.yml                 # Pipeline CI/CD
├── mob-routing-api/               # Backend Symfony
│   ├── src/
│   │   ├── Controller/            # Contrôleurs API
│   │   ├── Entity/                # Entités Doctrine
│   │   ├── Repository/            # Repositories
│   │   ├── Service/               # Services métier (Dijkstra)
│   │   └── DataFixtures/          # Fixtures de test
│   ├── tests/                     # Tests PHPUnit
│   ├── Dockerfile
│   └── composer.json
├── mob-routing-frontend/          # Frontend Vue.js
│   ├── src/
│   │   ├── components/            # Composants Vue
│   │   ├── services/              # Services API
│   │   └── types/                 # Types TypeScript
│   ├── __tests__/                 # Tests Vitest
│   ├── Dockerfile
│   ├── nginx.conf
│   └── package.json
├── docker-compose.yml             # Orchestration
├── DEPLOYMENT.md                  # Guide de déploiement
└── README.md                      # Ce fichier
```

---

## 🧪 Tests

### Couverture globale : **86%** (19/22 tests)

#### Backend (PHPUnit)
- **9/12 tests** passent (75%)
- **6/6 tests d'intégration** passent (100%)
- 3 tests unitaires nécessitent amélioration (mocks Doctrine)

```bash
# Lancer les tests backend
docker exec -it mob-backend php bin/phpunit

# Avec couverture
docker exec -it mob-backend php bin/phpunit --coverage-html coverage
```

#### Frontend (Vitest)
- **10/10 tests** passent (100%)
- Tests des composants et services

```bash
# Lancer les tests frontend
docker exec -it mob-frontend npm test

# Mode watch
docker exec -it mob-frontend npm test -- --watch
```

#### Linting

```bash
# PHPStan (backend)
docker exec -it mob-backend vendor/bin/phpstan analyse src --level=6

# ESLint (frontend)
docker exec -it mob-frontend npm run lint
```

---

## 📖 Documentation

- **[DEPLOYMENT.md](./DEPLOYMENT.md)** : Guide de déploiement détaillé
- **[OpenAPI Spec](./mob-routing-api/openapi.yml)** : Spécification de l'API
- **Documentation API interactive** : http://localhost:8000/api/docs (quand l'app tourne)

---

## 🚢 Déploiement

### Développement

```bash
docker compose up -d
```

### Production

Voir [DEPLOYMENT.md](./DEPLOYMENT.md) pour les instructions détaillées incluant :
- Configuration des variables d'environnement
- Configuration SSL/TLS
- Optimisations de performance
- Monitoring et logs

---

## 🔐 Sécurité

- HTTPS recommandé en production
- Secrets gérés via variables d'environnement
- Headers de sécurité configurés (CSP, HSTS, etc.)
- Scan de vulnérabilités automatique dans le CI/CD

---

## 📊 CI/CD

Le pipeline GitHub Actions exécute automatiquement :

1. ✅ Tests backend (PHPUnit)
2. ✅ Tests frontend (Vitest)
3. ✅ Linting (PHPStan + ESLint)
4. ✅ Build Docker
5. ✅ Tests d'intégration
6. ✅ Scan de sécurité (Trivy)

---

## 📦 Déploiement

### Développement local
```bash
docker compose up -d
```

### Production
Voir [PRODUCTION.md](PRODUCTION.md) pour le guide complet de déploiement en production avec HTTPS automatique.

---

## 📝 Notes importantes

### Données de test
- Les fixtures chargent **44 stations** (sur 108 disponibles dans `stations.json`)
- Cela permet de démontrer les fonctionnalités sans surcharger la base de test
- En production, toutes les stations seraient chargées

### Codes analytiques disponibles
- `PASSAGER` : Transport de passagers
- `FRET` : Transport de marchandises
- `MAINTENANCE` : Opérations de maintenance
- `TEST` : Tests techniques
- `TOURISME` : Trains touristiques

---

## 📚 Documentation

- **[README](README.md)** - Vous êtes ici
- **[Architecture](docs/ARCHITECTURE.md)** - Architecture technique du système
- **[Design](docs/DESIGN.md)** - Conception des entités et algorithme de Dijkstra
- **[Deployment](docs/DEPLOYMENT.md)** - Guide de déploiement
- **[Production](docs/PRODUCTION.md)** - Configuration production avec HTTPS
- **[JWT Setup](docs/JWT-SETUP.md)** - Configuration de l'authentification
- **[Security](docs/SECURITY.md)** - Bonnes pratiques de sécurité
- **[Git History](docs/GIT-HISTORY.md)** - Explication de l'historique Git reconstruit

---
## 🤝 Contribution

Ce projet a été développé dans le cadre du défi technique MOB pour démontrer :
- Architecture fullstack moderne
- Qualité du code avec tests automatisés
- DevOps avec Docker et CI/CD
- Documentation complète

---

## 📄 Licence

Ce projet est développé dans un cadre éducatif/technique.

---

## 👤 Auteur

**Baher** - Full Stack Developer
- Expertise : PHP/Symfony, Vue.js, Docker, CI/CD
- Certifications : PSM, PSPO, PRINCE2, ITIL V4

---

## 🙏 Remerciements

- MOB (Montreux-Oberland-Bernois) pour le défi technique
- La communauté Symfony et Vue.js

---

**Pour toute question, consultez [DEPLOYMENT.md](./DEPLOYMENT.md) ou ouvrez une issue.**
