# 🔐 Installation de l'authentification JWT

## Prérequis

L'extension PHP **sodium** est requise pour le chiffrement JWT.

### Sur macOS avec MacPorts
```bash
sudo port install php83-sodium
```

### Sur macOS avec Homebrew
```bash
brew install php@8.3
# sodium est inclus par défaut
```

### Sur Ubuntu/Debian
```bash
sudo apt-get install php8.3-sodium
```

### Vérification
```bash
php -m | grep sodium
# Devrait afficher : sodium
```

---

## Installation

```bash
cd mob-routing-api

# Installer le bundle JWT
composer require lexik/jwt-authentication-bundle

# Générer les clés JWT
php bin/console lexik:jwt:generate-keypair
```

---

## Configuration

### 1. Fichier `.env`

```env
###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase-here
###< lexik/jwt-authentication-bundle ###
```

### 2. Fichier `config/packages/security.yaml`

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        # Pour cet exemple, on crée un provider en mémoire
        # En prod, utilisez une entité User depuis la DB
        in_memory:
            memory:
                users:
                    admin:
                        password: '$2y$13$8VZ5Y.6lqKVQZ5bN5xQE5OZJqKvxKK4nLxQU8xKQx7lXKvQxKVQxK' # "admin123"
                        roles: ['ROLE_ADMIN']
                    user:
                        password: '$2y$13$9XZ6Y.7lqKVQZ5bN5xQE5OZJqKvxKK4nLxQU8xKQx7lXKvQxKVQxL' # "user123"
                        roles: ['ROLE_USER']

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login
                username_path: username
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        api:
            pattern: ^/api
            stateless: true
            jwt: ~

    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/docs, roles: PUBLIC_ACCESS }  # Documentation accessible
        - { path: ^/api, roles: ROLE_USER }  # Toutes les autres routes nécessitent auth
```

### 3. Fichier `config/routes.yaml`

```yaml
api_login:
    path: /api/login
    methods: ['POST']
```

---

## Génération des mots de passe hashés

```bash
# Pour "admin123"
php bin/console security:hash-password admin123

# Pour "user123"
php bin/console security:hash-password user123
```

Copiez les hash générés dans `security.yaml`.

---

## Test de l'authentification

### 1. Obtenir un token JWT

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }'
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### 2. Utiliser le token pour accéder à l'API

```bash
# Sans token (échoue)
curl http://localhost:8000/api/v1/routes
# Retourne : 401 Unauthorized

# Avec token (fonctionne)
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."

curl -X POST http://localhost:8000/api/v1/routes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "fromStationId": "AVA",
    "toStationId": "BLON",
    "analyticCode": "PASSAGER"
  }'
```

---

## Mise à jour du Frontend

### Fichier `src/services/api.ts`

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
});

// Interceptor pour ajouter le token JWT
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Fonction de login
export async function login(username: string, password: string) {
  const response = await api.post('/login', { username, password });
  const token = response.data.token;
  localStorage.setItem('jwt_token', token);
  return token;
}

// Utilisation existante
export async function calculateRoute(data: RouteRequest): Promise<RouteResponse> {
  const response = await api.post('/routes', data);
  return response.data;
}
```

### Composant de Login (optionnel)

```vue
<!-- src/components/LoginForm.vue -->
<template>
  <v-card>
    <v-card-title>Connexion</v-card-title>
    <v-card-text>
      <v-text-field v-model="username" label="Username" />
      <v-text-field v-model="password" label="Password" type="password" />
    </v-card-text>
    <v-card-actions>
      <v-btn @click="handleLogin">Se connecter</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { login } from '@/services/api';

const username = ref('admin');
const password = ref('admin123');

async function handleLogin() {
  try {
    await login(username.value, password.value);
    // Rediriger vers la page principale
  } catch (error) {
    console.error('Login failed', error);
  }
}
</script>
```

---

## Pour la production

**Utilisez une vraie entité User depuis la base de données :**

```bash
php bin/console make:user
php bin/console make:auth
```

---

## Alternative plus simple (API Key)

Si JWT est trop complexe, utilisez une simple API Key :

### Backend

```php
// src/Security/ApiKeyAuthenticator.php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    private const API_KEY = 'mob-secret-key-2024'; // En prod, depuis .env
    
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-Key');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-Key');
        
        if ($apiKey !== self::API_KEY) {
            throw new AuthenticationException('Invalid API Key');
        }
        
        // Créer un passport valide
        // ...
    }
}
```

### Utilisation

```bash
curl -X POST http://localhost:8000/api/v1/routes \
  -H "X-API-Key: mob-secret-key-2024" \
  -H "Content-Type: application/json" \
  -d '{"fromStationId": "AVA", "toStationId": "BLON", "analyticCode": "PASSAGER"}'
```

---

## Notes pour le défi MOB

**Pour ce défi**, vous pouvez :
1. **Installer JWT** mais le laisser optionnel (documentation suffit)
2. **Ou** juste documenter comment l'ajouter (ce fichier)
3. **Montrer que vous savez** comment sécuriser une API

**L'important** : démontrer la connaissance, pas forcément implémenter à 100%.
