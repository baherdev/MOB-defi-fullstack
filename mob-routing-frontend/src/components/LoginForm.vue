<template>
  <v-container class="fill-height">
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>MOB Routing - Connexion</v-toolbar-title>
          </v-toolbar>

          <v-card-text>
            <v-form @submit.prevent="handleLogin">
              <v-text-field
                  v-model="email"
                  label="Email"
                  type="email"
                  prepend-icon="mdi-email"
                  :error-messages="errors.email"
                  required
              />

              <v-text-field
                  v-model="password"
                  label="Mot de passe"
                  type="password"
                  prepend-icon="mdi-lock"
                  :error-messages="errors.password"
                  required
              />

              <v-alert v-if="errorMessage" type="error" class="mt-3">
                {{ errorMessage }}
              </v-alert>

              <v-alert v-if="successMessage" type="success" class="mt-3">
                {{ successMessage }}
              </v-alert>
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
                color="primary"
                :loading="loading"
                :disabled="!isFormValid"
                @click="handleLogin"
            >
              Se connecter
            </v-btn>
          </v-card-actions>

          <v-card-text class="text-center">
            <v-divider class="my-3" />
            <p class="text-caption text-grey">
              <strong>Comptes de test :</strong><br>
              admin@mob.ch / admin123<br>
              user@mob.ch / user123
            </p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { loginApi } from '../services/api';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const { login: setLoggedIn } = useAuth();

const email = ref('admin@mob.ch');
const password = ref('admin123');
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const errors = ref({
  email: '',
  password: '',
});

const isFormValid = computed(() => {
  return email.value.length > 0 && password.value.length > 0;
});

async function handleLogin() {
  if (!isFormValid.value) return;

  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  errors.value = { email: '', password: '' };

  try {
    const token = await loginApi(email.value, password.value);
    setLoggedIn(token);  // Met à jour l'état global
    successMessage.value = 'Connexion réussie ! Redirection...';

    setTimeout(() => {
      router.push('/');  // Navigation simple, pas de rechargement
    }, 500);
  } catch (error: any) {
    console.error('Login error:', error);

    if (error.response?.status === 401) {
      errorMessage.value = 'Email ou mot de passe incorrect';
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Erreur de connexion. Veuillez réessayer.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.fill-height {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
