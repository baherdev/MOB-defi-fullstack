<template>
  <v-container fluid class="pa-4">
    <v-row justify="center">
      <v-col cols="12" sm="10" md="8" lg="12">
        <v-card elevation="3" class="mx-auto">
          <v-card-title class="bg-primary text-white text-h5 pa-4">
            <v-icon start size="large">mdi-map-marker-distance</v-icon>
            Calculer un trajet
          </v-card-title>

          <v-card-text class="pt-6 px-4 px-md-6">
            <v-form ref="formRef" v-model="formValid" @submit.prevent="handleSubmit">
              <!-- Station de départ -->
              <v-select
                  v-model="form.fromStationId"
                  :items="stations"
                  item-title="displayName"
                  item-value="shortName"
                  label="Station de départ"
                  prepend-icon="mdi-train-car"
                  :rules="[rules.required]"
                  variant="outlined"
                  class="mb-3"
                  required
              ></v-select>

              <!-- Station d'arrivée -->
              <v-select
                  v-model="form.toStationId"
                  :items="stations"
                  item-title="displayName"
                  item-value="shortName"
                  label="Station d'arrivée"
                  prepend-icon="mdi-flag-checkered"
                  :rules="[rules.required, rules.different]"
                  variant="outlined"
                  class="mb-3"
                  required
              ></v-select>

              <!-- Code analytique -->
              <v-select
                  v-model="form.analyticCode"
                  :items="analyticCodes"
                  label="Type de trajet"
                  prepend-icon="mdi-tag"
                  :rules="[rules.required]"
                  variant="outlined"
                  class="mb-4"
                  required
              ></v-select>

              <!-- Bouton de soumission -->
              <v-btn
                  type="submit"
                  color="primary"
                  size="x-large"
                  block
                  :loading="loading"
                  :disabled="!formValid || loading"
                  class="text-h6"
              >
                <v-icon start>mdi-calculator</v-icon>
                Calculer le trajet
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>

        <!-- Affichage du résultat -->
        <v-card v-if="result" elevation="3" class="mt-6">
          <v-card-title class="bg-success text-white">
            <v-icon start>mdi-check-circle</v-icon>
            Trajet calculé
          </v-card-title>

          <v-card-text>
            <v-list>
              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="primary">mdi-map-marker</v-icon>
                </template>
                <v-list-item-title>Départ</v-list-item-title>
                <v-list-item-subtitle>{{ result.fromStationId }}</v-list-item-subtitle>
              </v-list-item>

              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="error">mdi-map-marker</v-icon>
                </template>
                <v-list-item-title>Arrivée</v-list-item-title>
                <v-list-item-subtitle>{{ result.toStationId }}</v-list-item-subtitle>
              </v-list-item>

              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="warning">mdi-ruler</v-icon>
                </template>
                <v-list-item-title>Distance totale</v-list-item-title>
                <v-list-item-subtitle class="text-h6 text-primary">
                  {{ result.distanceKm }} km
                </v-list-item-subtitle>
              </v-list-item>

              <v-list-item>
                <template v-slot:prepend>
                  <v-icon color="info">mdi-tag</v-icon>
                </template>
                <v-list-item-title>Type de trajet</v-list-item-title>
                <v-list-item-subtitle>{{ result.analyticCode }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>

            <v-divider class="my-4"></v-divider>

            <div class="text-subtitle-2 mb-2">
              <v-icon start>mdi-routes</v-icon>
              Chemin ({{ result.path.length }} stations)
            </div>
            <v-chip-group column>
              <v-chip
                  v-for="(station, index) in result.path"
                  :key="index"
                  :color="index === 0 ? 'primary' : index === result.path.length - 1 ? 'error' : 'default'"
                  size="small"
              >
                {{ station }}
              </v-chip>
            </v-chip-group>

            <v-divider class="my-4"></v-divider>

            <div class="text-caption text-grey">
              <v-icon start size="small">mdi-clock</v-icon>
              Créé le {{ formatDate(result.createdAt) }}
            </div>
          </v-card-text>

          <v-card-actions>
            <v-btn color="primary" variant="text" @click="reset">
              <v-icon start>mdi-refresh</v-icon>
              Nouveau calcul
            </v-btn>
          </v-card-actions>
        </v-card>

        <!-- Affichage des erreurs -->
        <v-alert
            v-if="error"
            type="error"
            closable
            class="mt-4"
            @click:close="error = null"
        >
          <v-alert-title>Erreur</v-alert-title>
          {{ error.message }}
          <ul v-if="error.details && error.details.length">
            <li v-for="(detail, index) in error.details" :key="index">{{ detail }}</li>
          </ul>
        </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { calculateRoute } from '@/services/api'
import type { RouteRequest, RouteResponse, ErrorResponse } from '@/types'

// État du formulaire
const formRef = ref()
const formValid = ref(false)
const loading = ref(false)
const result = ref<RouteResponse | null>(null)
const error = ref<ErrorResponse | null>(null)

// Données du formulaire
const form = ref<RouteRequest>({
  fromStationId: '',
  toStationId: '',
  analyticCode: 'PASSAGER',
})

// Stations disponibles (liste simplifiée, à compléter)
const stations = ref([
  { shortName: 'MX', longName: 'Montreux', displayName: 'MX - Montreux' },
  { shortName: 'CGE', longName: 'Montreux-Collège', displayName: 'CGE - Montreux-Collège' },
  { shortName: 'ZW', longName: 'Zweisimmen', displayName: 'ZW - Zweisimmen' },
  { shortName: 'GST', longName: 'Gstaad', displayName: 'GST - Gstaad' },
  { shortName: 'LENK', longName: 'Lenk im Simmental', displayName: 'LENK - Lenk im Simmental' },
  { shortName: 'VV', longName: 'Vevey', displayName: 'VV - Vevey' },
  { shortName: 'BLON', longName: 'Blonay', displayName: 'BLON - Blonay' },
  { shortName: 'AVA', longName: 'Les Avants', displayName: 'AVA - Les Avants' },
  { shortName: 'MTB', longName: 'Montbovon', displayName: 'MTB - Montbovon' },
  { shortName: 'CHOE', longName: "Château-d'Oex", displayName: "CHOE - Château-d'Oex" },
  { shortName: 'ROU', longName: 'Rougemont', displayName: 'ROU - Rougemont' },
  { shortName: 'SAAN', longName: 'Saanen', displayName: 'SAAN - Saanen' },
])

// Codes analytiques
const analyticCodes = ref([
  { title: 'Passager', value: 'PASSAGER' },
  { title: 'Fret', value: 'FRET' },
  { title: 'Maintenance', value: 'MAINTENANCE' },
  { title: 'Test', value: 'TEST' },
  { title: 'Tourisme', value: 'TOURISME' },
])

// Règles de validation
const rules = {
  required: (value: string) => !!value || 'Ce champ est requis',
  different: (value: string) =>
      value !== form.value.fromStationId || 'Les stations doivent être différentes',
}

// Soumettre le formulaire
const handleSubmit = async () => {
  if (!formValid.value) return

  loading.value = true
  error.value = null
  result.value = null

  try {
    result.value = await calculateRoute(form.value)
  } catch (err: any) {
    if (err.response?.data) {
      error.value = err.response.data
    } else {
      error.value = {
        message: err.message || 'Une erreur est survenue',
        details: []
      }
    }
  } finally {
    loading.value = false
  }
}

// Réinitialiser le formulaire
const reset = () => {
  result.value = null
  error.value = null
  form.value = {
    fromStationId: '',
    toStationId: '',
    analyticCode: 'PASSAGER',
  }
  formRef.value?.reset()
}

// Formater la date
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}
</script>
