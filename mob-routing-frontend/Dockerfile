# Dependencies stage
FROM node:20-alpine AS deps

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Test stage (accessible pour les tests)
FROM deps AS test

WORKDIR /app

# Copy source code
COPY . .

# Les tests peuvent être lancés ici avec: docker compose run frontend-test npm test

# Build stage
FROM deps AS builder

WORKDIR /app

# Copy source code
COPY . .

# Build for production (skip TypeScript checking for Docker build)
RUN npx vite build

# Production stage with Nginx
FROM nginx:alpine AS production

# Copy built files
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy custom Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
