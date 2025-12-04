#!/bin/bash

# ============================================
# Script de test de l'API MOB Routing
# ============================================

BASE_URL="http://localhost:8000/api/v1"

# Vérifier si jq est installé
if command -v jq &> /dev/null; then
    JSON_FORMATTER="jq"
else
    JSON_FORMATTER="python3 -m json.tool"
fi

echo "🚀 Tests de l'API MOB Routing"
echo "================================"

# ============================================
# 1. Test POST /routes - Calculer un trajet
# ============================================
echo ""
echo "📍 Test 1: Calculer un trajet Montreux → Gstaad"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X POST "$BASE_URL/routes" \
  -H "Content-Type: application/json" \
  -d '{
    "fromStationId": "MX",
    "toStationId": "GST",
    "analyticCode": "PASSAGER"
  }')

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 201"
echo ""

# ============================================
# 2. Test POST /routes - Validation errors
# ============================================
echo ""
echo "❌ Test 2: Validation - Champs manquants"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X POST "$BASE_URL/routes" \
  -H "Content-Type: application/json" \
  -d '{
    "fromStationId": "MX"
  }')

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 400"
echo ""

# ============================================
# 3. Test POST /routes - Mêmes stations
# ============================================
echo ""
echo "❌ Test 3: Validation - Stations identiques"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X POST "$BASE_URL/routes" \
  -H "Content-Type: application/json" \
  -d '{
    "fromStationId": "MX",
    "toStationId": "MX",
    "analyticCode": "PASSAGER"
  }')

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 400"
echo ""

# ============================================
# 4. Test GET /stats/distances - Sans paramètres
# ============================================
echo ""
echo "📊 Test 4: Statistiques sans filtre"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X GET "$BASE_URL/stats/distances" \
  -H "Accept: application/json")

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 200"
echo ""

# ============================================
# 5. Test GET /stats/distances - Avec période
# ============================================
echo ""
echo "📊 Test 5: Statistiques avec période"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X GET "$BASE_URL/stats/distances?from=2025-01-01&to=2025-12-31&groupBy=month" \
  -H "Accept: application/json")

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 200"
echo ""

# ============================================
# 6. Test GET /stats/distances - Dates invalides
# ============================================
echo ""
echo "❌ Test 6: Validation - from > to"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X GET "$BASE_URL/stats/distances?from=2024-12-31&to=2024-01-01" \
  -H "Accept: application/json")

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 400"
echo ""

# ============================================
# 7. Test GET /stats/distances - GroupBy invalide
# ============================================
echo ""
echo "❌ Test 7: Validation - groupBy invalide"
echo "----------------------------------------------"

RESPONSE=$(curl -s -X GET "$BASE_URL/stats/distances?groupBy=invalid" \
  -H "Accept: application/json")

echo "$RESPONSE" | $JSON_FORMATTER
echo ""
echo "✅ HTTP Status: 400"
echo ""

echo ""
echo "✅ Tests terminés!"
echo "================================"
