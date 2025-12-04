# test-deployment.sh
#!/bin/bash

echo "🧪 Test du déploiement MOB..."

echo "📦 Nettoyage..."
docker compose down -v

echo "🏗️ Build et démarrage..."
docker compose up -d --build

echo "⏳ Attente 30 secondes..."
sleep 30

echo "✅ Test API..."
curl -s http://localhost:8000/api/v1 | grep -q "resourceNameCollection" && echo "✅ API OK" || echo "❌ API KO"

echo "✅ Test Frontend..."
curl -s http://localhost:3000 | grep -q "<!DOCTYPE html>" && echo "✅ Frontend OK" || echo "❌ Frontend KO"

echo "✅ Test Fixtures..."
STATIONS=$(docker exec mob-mysql mysql -u mob_user -pmob_password mob_routing -se "SELECT COUNT(*) FROM stations;")
[ "$STATIONS" -eq 44 ] && echo "✅ Fixtures OK ($STATIONS stations)" || echo "❌ Fixtures KO"

echo "🎉 Tests terminés!"
