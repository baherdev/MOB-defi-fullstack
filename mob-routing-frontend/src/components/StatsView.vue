<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-card elevation="3">
          <v-card-title class="bg-primary text-white">
            <v-icon start>mdi-chart-bar</v-icon>
            Statistiques de distances
          </v-card-title>

          <v-card-text class="pt-6">
            <v-row>
              <!-- Filtres -->
              <v-col cols="12" md="4">
                <v-text-field
                    v-model="filters.from"
                    type="date"
                    label="Date de début"
                    prepend-icon="mdi-calendar-start"
                    clearable
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                    v-model="filters.to"
                    type="date"
                    label="Date de fin"
                    prepend-icon="mdi-calendar-end"
                    clearable
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-select
                    v-model="filters.groupBy"
                    :items="groupByOptions"
                    label="Grouper par"
                    prepend-icon="mdi-group"
                ></v-select>
              </v-col>

              <v-col cols="12">
                <v-btn
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                    @click="fetchStats"
                >
                  <v-icon start>mdi-refresh</v-icon>
                  Actualiser
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Affichage des statistiques -->
      <v-col v-if="stats" cols="12">
        <v-card elevation="3">
          <v-card-title>
            <v-icon start>mdi-table</v-icon>
            Résultats
          </v-card-title>

          <v-card-text>
            <v-alert v-if="stats.items.length === 0" type="info">
              Aucune donnée disponible pour cette période
            </v-alert>

            <v-table v-else>
              <thead>
              <tr>
                <th>Code Analytique</th>
                <th>Distance Totale (km)</th>
                <th v-if="filters.groupBy !== 'none'">Période</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(item, index) in stats.items" :key="index">
                <td>
                  <v-chip :color="getCodeColor(item.analyticCode)" size="small">
                    {{ item.analyticCode }}
                  </v-chip>
                </td>
                <td class="text-h6 text-primary">{{ item.totalDistanceKm }} km</td>
                <td v-if="filters.groupBy !== 'none'">{{ item.group }}</td>
              </tr>
              </tbody>
              <tfoot>
              <tr>
                <td><strong>TOTAL</strong></td>
                <td colspan="2" class="text-h5 text-success">
                  <strong>{{ totalDistance }} km</strong>
                </td>
              </tr>
              </tfoot>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Graphique -->
      <v-col v-if="stats && stats.items.length > 0" cols="12">
        <v-card elevation="3">
          <v-card-title>
            <v-icon start>mdi-chart-bar</v-icon>
            Graphique
          </v-card-title>

          <v-card-text>
            <canvas ref="chartCanvas"></canvas>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Erreur -->
      <v-col v-if="error" cols="12">
        <v-alert type="error" closable @click:close="error = null">
          <v-alert-title>Erreur</v-alert-title>
          {{ error.message }}
        </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { getDistanceStats } from '../services/api'
import type { AnalyticDistanceList, ErrorResponse } from '../types'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

// État
const loading = ref(false)
const stats = ref<AnalyticDistanceList | null>(null)
const error = ref<ErrorResponse | null>(null)
const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

// Filtres
const filters = ref({
  from: '',
  to: '',
  groupBy: 'none' as 'day' | 'month' | 'year' | 'none',
})

const groupByOptions = [
  { title: 'Aucun', value: 'none' },
  { title: 'Par jour', value: 'day' },
  { title: 'Par mois', value: 'month' },
  { title: 'Par année', value: 'year' },
]

// Calculer la distance totale
const totalDistance = computed(() => {
  if (!stats.value) return 0
  return stats.value.items.reduce((sum, item) => sum + item.totalDistanceKm, 0).toFixed(2)
})

// Couleurs par code analytique
const getCodeColor = (code: string) => {
  const colors: Record<string, string> = {
    PASSAGER: 'primary',
    FRET: 'warning',
    MAINTENANCE: 'error',
    TEST: 'info',
    TOURISME: 'success',
  }
  return colors[code] || 'default'
}

// Récupérer les statistiques
const fetchStats = async () => {
  loading.value = true
  error.value = null

  try {
    const params: any = {
      groupBy: filters.value.groupBy
    }

    if (filters.value.from) {
      params.from = filters.value.from
    }

    if (filters.value.to) {
      params.to = filters.value.to
    }

    stats.value = await getDistanceStats(params)
    await nextTick()
    updateChart()
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

// Mettre à jour le graphique
const updateChart = () => {
  if (!chartCanvas.value || !stats.value) return

  // Détruire l'ancien graphique
  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return

  const labels = stats.value.items.map(item => item.analyticCode)
  const data = stats.value.items.map(item => item.totalDistanceKm)

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Distance (km)',
          data,
          backgroundColor: [
            'rgba(25, 118, 210, 0.8)',   // Primary
            'rgba(255, 193, 7, 0.8)',    // Warning
            'rgba(244, 67, 54, 0.8)',    // Error
            'rgba(33, 150, 243, 0.8)',   // Info
            'rgba(76, 175, 80, 0.8)',    // Success
          ],
          borderColor: [
            'rgba(25, 118, 210, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(244, 67, 54, 1)',
            'rgba(33, 150, 243, 1)',
            'rgba(76, 175, 80, 1)',
          ],
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false,
        },
        title: {
          display: true,
          text: 'Distances par code analytique',
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Distance (km)',
          },
        },
      },
    },
  })
}

// Charger les stats au montage
onMounted(() => {
  fetchStats()
})

// Recharger quand les filtres changent
watch(filters, () => {
  fetchStats()
}, { deep: true })
</script>
