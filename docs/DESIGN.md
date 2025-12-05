# Conception du Modèle de Données

Ce document explique la démarche de conception des entités du système de calcul d'itinéraires ferroviaires.

---

## 🎯 Objectif

Créer un système capable de calculer le **chemin le plus court** entre deux stations du réseau ferroviaire MOB (Montreux-Oberland-Bernois) en utilisant l'algorithme de Dijkstra.

---

## 📚 Approche Théorique

### **Algorithme de Dijkstra**

L'algorithme de Dijkstra permet de trouver le chemin le plus court dans un graphe pondéré. Dans notre contexte :

- **Nœuds (vertices)** : Les stations ferroviaires
- **Arêtes (edges)** : Les segments de voie entre deux stations
- **Poids** : La distance en kilomètres entre les stations

### **Représentation en Graphe**

```
[AVA] --6.65km-- [SDY] --3.2km-- [CABY]
  |                                  |
  +------------ 12.5km --------------+
```

Chaque station est un **point** dans le graphe, et chaque segment ferroviaire est une **connexion pondérée** entre deux points.

---

## 🏗️ Conception des Entités (Version Initiale)

### **1. Station**
Représente un arrêt ferroviaire.

```
Station:
├── id (UUID)
├── id_station (code unique, ex: "AVA", "BLON")
├── short_name_label (nom court, ex: "Avançon")
├── long_name_label (nom complet, ex: "Avançon-Gare")
```

**Décision de conception :** Utiliser un code court (`id_station`) pour identifier rapidement les stations dans les requêtes API.

---

### **2. NetworkSegment (Segment)**
Représente une connexion directe entre deux stations.

```
NetworkSegment:
├── id (UUID)
├── parent_station (Station de départ)
├── child_station (Station d'arrivée)
├── distance (Distance en km)
```

**Décision de conception :**
- Représentation **bidirectionnelle** : Chaque liaison physique génère 2 segments (A→B et B→A)
- Permet à l'algorithme de Dijkstra de traverser le graphe dans les deux sens

**Exemple :**
```
AVA → SDY : 6.65 km (segment 1)
SDY → AVA : 6.65 km (segment 2)
```

---

### **3. Train**
Représente un train circulant sur le réseau.

```
Train:
├── id (UUID)
├── train_label (Numéro/nom du train)
├── id_code_analytics (Type de trafic)
```

**Décision de conception :** Séparer l'entité Train pour permettre le suivi et l'analyse des différents types de services ferroviaires.

---

### **4. CodeAnalytics**
Catégorise les types de trafic ferroviaire.

```
CodeAnalytics:
├── id (UUID)
├── code_analytics_label (Type: PASSAGER, FRET, MAINTENANCE, TEST, TOURISME)
```

**Décision de conception :** Permettre l'analyse statistique par type de trafic (voyageurs vs marchandises vs maintenance).

---

### **5. Trajet**
Représente un voyage calculé entre deux stations.

```
Trajet:
├── id (UUID)
├── id_train (Train associé)
├── station_dep (Station de départ)
├── station_arriv (Station d'arrivée)
├── distance_totale (Distance calculée)
├── chemin (Liste ordonnée des stations)
├── createdAt (Horodatage)
```

**Décision de conception :** Stocker les trajets calculés pour :
- Historique des calculs
- Analyse statistique des itinéraires les plus demandés
- Cache potentiel pour des calculs répétés

---

### **6. Network**
Lien entre un trajet et les segments empruntés.

```
Network:
├── id (UUID)
├── id_trajet (Trajet associé)
├── id_segment (Segment emprunté)
```

**Décision de conception :** Table de liaison pour reconstituer le détail d'un trajet complet avec tous les segments traversés.

---

## 🔄 Évolution du Modèle

### **Simplifications apportées**

Au cours du développement, certaines entités ont été simplifiées :

1. **TrajetSegment** : Fusion de Network dans une entité plus claire
2. **Suppression de relations complexes** : Simplification pour se concentrer sur le calcul d'itinéraire
3. **Optimisation des index** : Ajout d'index sur les clés étrangères pour améliorer les performances de Dijkstra

---

## 📊 Schéma de Base de Données Final

```
┌─────────────┐         ┌──────────────────┐
│   Station   │◄───────►│ NetworkSegment   │
│             │         │                  │
│ - id        │         │ - fromStation    │
│ - shortName │         │ - toStation      │
│ - longName  │         │ - distance       │
└─────────────┘         └──────────────────┘
       ▲
       │
       │
┌─────────────┐
│   Trajet    │
│             │
│ - fromSta   │
│ - toSta     │
│ - distance  │
│ - path      │
└─────────────┘
```

---

## 🧠 Implémentation de Dijkstra

### **Algorithme Appliqué**

```php
function findShortestPath(fromStation, toStation):
    1. Initialiser distances = {tous: ∞, départ: 0}
    2. Créer file de priorité
    3. Tant que file non vide:
        a. Extraire station avec distance minimale
        b. Pour chaque voisin via NetworkSegment:
            - Calculer distance_alternative
            - Si distance_alternative < distance_actuelle:
                * Mettre à jour distance
                * Enregistrer prédécesseur
    4. Reconstruire chemin depuis destination
    5. Retourner {distance, path}
```

### **Complexité**

- **Temps** : O((V + E) log V) avec file de priorité
- **Espace** : O(V) pour stocker distances et prédécesseurs

Où :
- V = nombre de stations (~44 pour MOB)
- E = nombre de segments (~88 bidirectionnels)

---

## 🎯 Cas d'Usage Réels

### **Exemple 1 : Montreux → Gstaad**

```
Input:
  from: "MON"
  to: "GSTA"
  analytics: "PASSAGER"

Calcul:
  MON → TVY → CLAR → MONT → CHÂT → ROSS → GSTA
  Distance totale: 42.8 km

Output:
  {
    "distance": 42.8,
    "path": ["MON", "TVY", "CLAR", "MONT", "CHÂT", "ROSS", "GSTA"]
  }
```

### **Exemple 2 : Itinéraire avec correspondance**

Pour des réseaux plus complexes avec correspondances, le modèle peut être étendu avec :
- **Station de correspondance** (attribut dans Station)
- **Temps de transfert** (poids supplémentaire dans Segment)

---

## 📈 Évolutions Futures Possibles

### **1. Multi-critères**
Actuellement : distance uniquement  
Futur : distance + temps + nombre de correspondances

### **2. Contraintes horaires**
Ajouter les horaires réels des trains et calculer l'itinéraire optimal selon l'heure de départ souhaitée.

### **3. Capacité et disponibilité**
Intégrer la capacité des trains et la disponibilité en temps réel.

### **4. Tarification**
Calculer le coût du trajet en fonction des zones tarifaires traversées.

---

## 🔗 Références

- [Algorithme de Dijkstra - Wikipedia](https://fr.wikipedia.org/wiki/Algorithme_de_Dijkstra)
- [Graph Theory in Railway Networks](https://en.wikipedia.org/wiki/Graph_theory)
- Données réelles : [Réseau MOB](https://www.mob.ch)

---

## 📝 Notes de Développement

### **Décisions Techniques**

1. **Bidirectionnalité** : Créer 2 segments (A→B et B→A) plutôt qu'un flag `bidirectional`
    - ✅ Simplifie l'algorithme de Dijkstra
    - ✅ Permet des distances asymétriques futures (montée vs descente)

2. **UUID vs Auto-increment** : Utiliser des UUID pour les IDs
    - ✅ Permet la distribution et la synchronisation
    - ✅ Évite les collisions dans un système distribué

3. **Stockage du chemin** : Array JSON dans Trajet
    - ✅ Rapide à lire
    - ✅ Facile à afficher
    - ⚠️ Non normalisé (acceptable pour les besoins actuels)

---

**Cette conception permet de résoudre efficacement le problème de calcul d'itinéraire tout en restant extensible pour des fonctionnalités futures.**
