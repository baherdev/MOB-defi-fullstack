<template>
  <v-app>
    <v-app-bar color="primary" dark app>
      <div class="d-flex align-center">
        <img 
          src="./assets/logo-mob.svg" 
          alt="MOB Logo" 
          height="40"
          class="mr-3"
        />
        <v-toolbar-title>MOB Routing</v-toolbar-title>
      </div>

      <v-spacer />

      <template v-if="isAuthenticated">
        <v-btn icon @click="navigateTo('/')">
          <v-icon>mdi-train</v-icon>
        </v-btn>

        <v-btn icon @click="navigateTo('/stats')">
          <v-icon>mdi-chart-bar</v-icon>
        </v-btn>

        <v-btn icon @click="handleLogout">
          <v-icon>mdi-logout</v-icon>
        </v-btn>
      </template>
    </v-app-bar>

    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuth } from './composables/useAuth';

const router = useRouter();
const { isAuthenticated, logout } = useAuth();

function handleLogout() {
  if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
    logout();
  }
}

function navigateTo(path: string) {
  router.push(path);
}
</script>

<style scoped>
.mr-3 {
  margin-right: 12px;
}
</style>
