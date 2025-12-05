# 🏗️ Architecture de l'application MOB Routing

## Vue d'ensemble

Cette application est conçue selon une architecture **fullstack moderne** avec séparation claire entre frontend, backend et base de données, orchestrée par Docker.

---

## 📐 Diagramme d'architecture

```
┌──────────────────────────────────────────────────────────┐
│                        Internet                          │
└────────────────────────┬─────────────────────────────────┘
                         │
                         │ HTTPS (Production)
                         │
┌────────────────────────▼─────────────────────────────────┐
│              Reverse Proxy (Nginx/Traefik)               │
│                      Port 80/443                          │
└────────┬──────────────────────────────────┬──────────────┘
         │                                  │
         │ HTTP :3000                       │ HTTP :8000
         │                                  │
┌────────▼────────────┐          ┌─────────▼──────────────┐
│   Frontend (SPA)    │          │   Backend Nginx        │
│   Vue.js + Vuetify  │          │   (Reverse Proxy)      │
│   Nginx Alpine      │          └──────────┬─────────────┘
└─────────────────────┘                     │
         │                                  │ FastCGI :9000
         │ HTTP Request                     │
         │ /api/* → Backend                 │
         └──────────────────────────────────┤
                                   ┌────────▼─────────────┐
                                   │  Backend PHP-FPM     │
                                   │  Symfony 7.1         │
                                   │  API Platform        │
                                   └────────┬─────────────┘
                                            │
                                            │ TCP :3306
                                            │
                                   ┌────────▼─────────────┐
                                   │   MySQL 8.0          │
                                   │   Database           │
                                   └──────────────────────┘
```

---

## 🔧 Composants

### 1. Frontend (Vue.js)

**Technologie :** Vue.js 3 + TypeScript + Vuetify 3

**Responsabilités :**
- Interface utilisateur responsive
- Validation côté client
- Communication avec l'API backend
- Gestion de l'état local

**Structure :**
```
src/
├── components/
│   ├── RouteCalculator.vue    # Formulaire de calcul
│   └── StatsView.vue          # Visualisation des stats
├── services/
│   └── api.ts                 # Client API
└── types/
    └── index.ts               # Types TypeScript
```

**Déploiement :**
- Build statique avec Vite
- Servi par Nginx Alpine
- Configuration SPA (Vue Router)

---

### 2. Backend (Symfony)

**Technologie :** PHP 8.4 + Symfony 7.1 + API Platform

**Responsabilités :**
- API REST conforme OpenAPI
- Logique métier (algorithme de Dijkstra)
- Validation des données
- Persistance en base de données

**Architecture en couches :**

```
Controller Layer (API Endpoints)
         ↓
Service Layer (Business Logic)
         ↓
Repository Layer (Data Access)
         ↓
Entity Layer (Domain Model)
```

**Composants clés :**

#### Entities
- `Station` : Gares du réseau
- `Network` : Réseaux ferroviaires (MOB, MVR-ce)
- `NetworkSegment` : Segments entre gares
- `CodeAnalytics` : Codes d'analyse
- `Journey` : Trajets calculés
- `JourneySegment` : Segments d'un trajet

#### Services
- `RoutingService` : Implémentation de l'algorithme de Dijkstra
- Gestion du graphe de stations
- Calcul du plus court chemin

#### Controllers
- `RouteController` : Calcul d'itinéraires
- `StatsController` : Statistiques agrégées

---

### 3. Base de données

**Technologie :** MySQL 8.0

**Schéma :**

```sql
stations
├── id (PK)
├── short_name (UNIQUE)
└── long_name

networks
├── id (PK)
└── name

network_segments
├── id (PK)
├── network_id (FK)
├── parent_station_id (FK)
├── child_station_id (FK)
└── distance_km

code_analytics
├── id (PK)
└── label

trajets (journeys)
├── id (PK)
├── from_station_id (FK)
├── to_station_id (FK)
├── analytic_code_id (FK)
├── distance_km
└── created_at

trajet_segments
├── id (PK)
├── trajet_id (FK)
├── segment_id (FK)
└── sequence_order
```

**Migrations :** Gérées par Doctrine Migrations

---

## 🔄 Flux de données

### Calcul d'itinéraire

```
1. User Input (Frontend)
   ↓
2. HTTP POST /api/v1/routes
   ↓
3. RouteController::calculate()
   ↓
4. Validation des données
   ↓
5. RoutingService::findShortestPath()
   ├── Construction du graphe depuis DB
   ├── Algorithme de Dijkstra
   └── Calcul du chemin optimal
   ↓
6. Persistance du Journey
   ↓
7. JSON Response
   ↓
8. Frontend Display
```

### Récupération des statistiques

```
1. User Request (Frontend)
   ↓
2. HTTP GET /api/v1/stats/distances
   ↓
3. StatsController::getDistances()
   ↓
4. Query agrégée (GROUP BY)
   ↓
5. JSON Response avec totaux
   ↓
6. Frontend Visualization
```

---

## 🎯 Algorithme de Dijkstra

### Principe

L'algorithme de Dijkstra trouve le **plus court chemin** dans un graphe pondéré.

### Implémentation

```php
function findShortestPath($fromStation, $toStation) {
    // 1. Construire le graphe depuis les NetworkSegments
    $graph = buildGraph();
    
    // 2. Initialiser les distances (∞ sauf source = 0)
    $distances = initialize();
    
    // 3. File de priorité (min-heap)
    $priorityQueue = new PriorityQueue();
    
    // 4. Tant que la file n'est pas vide
    while (!$priorityQueue->isEmpty()) {
        $current = $priorityQueue->extract();
        
        // Si on atteint la destination, on s'arrête
        if ($current === $toStation) break;
        
        // Pour chaque voisin
        foreach ($graph->neighbors($current) as $neighbor) {
            $newDistance = $distances[$current] + $graph->weight($current, $neighbor);
            
            if ($newDistance < $distances[$neighbor]) {
                $distances[$neighbor] = $newDistance;
                $previous[$neighbor] = $current;
                $priorityQueue->insert($neighbor, $newDistance);
            }
        }
    }
    
    // 5. Reconstruire le chemin
    return reconstructPath($previous, $fromStation, $toStation);
}
```

### Complexité

- **Temps :** O((V + E) log V) avec un tas binaire
- **Espace :** O(V) où V = nombre de stations

---

## 🐳 Infrastructure Docker

### Multi-stage Build

**Avantages :**
- Images légères (séparation build/runtime)
- Cache des layers pour builds rapides
- Sécurité (pas d'outils de build en production)

**Backend Dockerfile :**
```dockerfile
# Stage 1: Builder
FROM php:8.4-fpm-alpine AS builder
RUN composer install
COPY . .

# Stage 2: Production
FROM php:8.4-fpm-alpine
COPY --from=builder /app /app
CMD ["php-fpm"]
```

**Frontend Dockerfile :**
```dockerfile
# Stage 1: Builder
FROM node:20-alpine AS builder
RUN npm ci && npm run build

# Stage 2: Production
FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
```

---

## 🔒 Sécurité

### Mesures implémentées

1. **Backend**
    - Validation stricte des entrées
    - Parameterized queries (Doctrine ORM)
    - CORS configuré
    - Headers de sécurité

2. **Frontend**
    - CSP (Content Security Policy)
    - XSS Protection
    - HTTPS Only (production)

3. **Infrastructure**
    - Images Docker officielles
    - Scan de vulnérabilités (Trivy)
    - Secrets via variables d'environnement
    - Pas de credentials en dur

---

## 📈 Scalabilité

### Actuellement (Single Server)

```
1 instance frontend + 1 instance backend + 1 DB
```

### Evolution possible

```
Load Balancer
      ↓
┌─────┴─────┐
│  Frontend │ x N (stateless)
└───────────┘
      ↓
┌─────┴─────┐
│  Backend  │ x N (stateless)
└───────────┘
      ↓
┌─────┴─────┐
│ DB Master │ → DB Replica(s)
└───────────┘
```

**Améliorations possibles :**
- Cache Redis pour les itinéraires fréquents
- Message Queue pour les statistiques
- CDN pour les assets statiques
- DB Read Replicas

---

## 🧪 Stratégie de tests

### Pyramide de tests

```
      /\
     /E2\      End-to-End (Docker Compose)
    /────\
   /Integr\    Integration (API + DB)
  /────────\
 /   Unit   \  Unit (Services, Algorithms)
/____________\
```

**Distribution :**
- **Unit Tests :** 40% (logique métier isolée)
- **Integration Tests :** 40% (API + DB)
- **E2E Tests :** 20% (Docker Compose complet)

---

## 📊 Monitoring (Production)

### Recommandations

**Logs :**
- Centralisés (ELK Stack / Loki)
- Rotation automatique
- Niveaux : ERROR, WARN, INFO, DEBUG

**Métriques :**
- Temps de réponse API
- Taux d'erreur
- Utilisation CPU/RAM
- Connexions DB

**Alerting :**
- Service down
- Erreurs 5xx > seuil
- DB connexions saturées

---

## 🔄 CI/CD Pipeline

### Workflow

```
git push
   ↓
GitHub Actions Triggered
   ↓
┌──────────────────┐
│  Parallel Jobs   │
├──────────────────┤
│ • Backend Tests  │
│ • Frontend Tests │
│ • Linting        │
│ • Security Scan  │
└────────┬─────────┘
         ↓
   All Jobs Pass?
         ↓
  Docker Build
         ↓
  Integration Test
         ↓
  Deploy (optionnel)
```

---

## 📝 Décisions d'architecture (ADR)

### ADR-001 : Choix de Symfony pour le backend

**Contexte :** Besoin d'un framework PHP moderne avec support API REST

**Décision :** Symfony 7.1 + API Platform

**Raisons :**
- Maturité et stabilité
- API Platform pour génération OpenAPI automatique
- Doctrine ORM pour la persistance
- Large communauté et documentation

### ADR-002 : Algorithme de Dijkstra

**Contexte :** Calcul du plus court chemin dans un graphe

**Décision :** Implémentation de Dijkstra avec file de priorité

**Raisons :**
- Optimal pour graphes pondérés positifs
- Complexité acceptable O((V+E) log V)
- Facile à tester et maintenir
- Standard de l'industrie

### ADR-003 : Docker multi-stage builds

**Contexte :** Optimisation des images Docker

**Décision :** Build en 2 stages (builder + production)

**Raisons :**
- Images finales légères (~100MB vs ~500MB)
- Sécurité (pas d'outils de build en prod)
- Temps de déploiement réduit

---

## 🎓 Patterns utilisés

- **Repository Pattern** : Abstraction de la couche données
- **Service Layer** : Logique métier séparée des controllers
- **DTO (Data Transfer Objects)** : Via API Platform
- **Dependency Injection** : Container Symfony
- **Factory Pattern** : Construction d'entités complexes

---

**Cette architecture permet une maintenance facile, une scalabilité future et une excellente testabilité.**
