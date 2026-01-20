<template>
  <div class="login-container">
    <div class="login-wrapper">
      <!-- Côté gauche avec branding -->
      <div class="login-left">
        <div class="brand-content">
          <div class="brand-icon-wrapper">
            <div class="brand-icon"><i class="fa-solid fa-chart-bar"></i></div>
          </div>
          <h1 class="brand-title">Review Analyzer</h1>
          <p class="brand-subtitle">Analysez et gérez vos reviews clients avec l'intelligence artificielle</p>
          <div class="brand-features">
            <div class="feature-item">
              <span class="feature-icon"><i class="fa-solid fa-robot"></i></span>
              <span>Analyse IA automatique</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon"><i class="fa-solid fa-chart-line"></i></span>
              <span>Statistiques détaillées</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon"><i class="fa-solid fa-bolt"></i></span>
              <span>Gestion simplifiée</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Côté droit avec formulaire -->
      <div class="login-right">
        <div class="login-card">
          <div class="card-header">
            <h2>{{ showRegister ? 'Créer un compte' : 'Connexion' }}</h2>
            <p>{{ showRegister ? 'Rejoignez-nous dès maintenant' : 'Bienvenue, connectez-vous à votre compte' }}</p>
          </div>

          <form v-if="!showRegister" @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
              <label>Email</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="votre@email.com"
                  class="form-input"
                />
              </div>
            </div>
            <div class="form-group">
              <label>Mot de passe</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <input
                  v-model="form.password"
                  type="password"
                  required
                  placeholder="••••••••"
                  class="form-input"
                />
              </div>
            </div>
            <button type="submit" :disabled="loading" class="btn-primary">
              <span v-if="!loading">Se connecter</span>
              <span v-else class="spinner"></span>
            </button>
            <p v-if="error" class="error-message">{{ error }}</p>
            <p class="register-link">
              Pas de compte ? <a href="#" @click.prevent="showRegister = true">S'inscrire</a>
            </p>
          </form>

          <form v-else @submit.prevent="handleRegister" class="login-form">
            <div class="form-group">
              <label>Nom complet</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                <input
                  v-model="registerForm.name"
                  type="text"
                  required
                  placeholder="Votre nom"
                  class="form-input"
                />
              </div>
            </div>
            <div class="form-group">
              <label>Email</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                <input
                  v-model="registerForm.email"
                  type="email"
                  required
                  placeholder="votre@email.com"
                  class="form-input"
                />
              </div>
            </div>
            <div class="form-group">
              <label>Mot de passe</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                <input
                  v-model="registerForm.password"
                  type="password"
                  required
                  minlength="8"
                  placeholder="Minimum 8 caractères"
                  class="form-input"
                />
              </div>
            </div>
            <button type="submit" :disabled="loading" class="btn-primary">
              <span v-if="!loading">Créer mon compte</span>
              <span v-else class="spinner"></span>
            </button>
            <p v-if="error" class="error-message">{{ error }}</p>
            <p class="register-link">
              Déjà un compte ? <a href="#" @click.prevent="showRegister = false">Se connecter</a>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';

const router = useRouter();
const loading = ref(false);
const error = ref('');
const showRegister = ref(false);

const form = ref({
  email: '',
  password: '',
});

const registerForm = ref({
  name: '',
  email: '',
  password: '',
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await api.post('/auth/login', form.value);
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    router.push('/');
  } catch (err) {
    console.error('Erreur de connexion:', err);
    if (err.response) {
      error.value = err.response.data?.message || err.response.data?.error || 'Erreur de connexion';
    } else if (err.request) {
      error.value = 'Impossible de contacter le serveur. Vérifiez que le backend est démarré sur http://localhost:8001';
    } else {
      error.value = 'Erreur: ' + err.message;
    }
  } finally {
    loading.value = false;
  }
};

const handleRegister = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await api.post('/auth/register', registerForm.value);
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    router.push('/');
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de l\'inscription';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.login-wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  max-width: 1200px;
  width: 100%;
  background: white;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.login-left {
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  padding: 4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  position: relative;
  overflow: hidden;
}

.login-left::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  animation: pulse 8s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.1); opacity: 0.8; }
}

.brand-content {
  position: relative;
  z-index: 1;
  text-align: center;
}

.brand-icon-wrapper {
  margin-bottom: 2rem;
  display: flex;
  justify-content: center;
}

.brand-icon {
  font-size: 5rem;
  background: rgba(255, 255, 255, 0.2);
  width: 120px;
  height: 120px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.brand-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.brand-subtitle {
  font-size: 1.1rem;
  margin-bottom: 3rem;
  opacity: 0.95;
  line-height: 1.6;
}

.brand-features {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  text-align: left;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-size: 1rem;
  opacity: 0.95;
}

.feature-icon {
  font-size: 1.5rem;
  background: rgba(255, 255, 255, 0.2);
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.login-right {
  padding: 4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
}

.login-card {
  width: 100%;
  max-width: 400px;
}

.card-header {
  text-align: center;
  margin-bottom: 2rem;
}

.card-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.card-header p {
  color: #64748b;
  font-size: 0.95rem;
}

.login-form {
  width: 100%;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #1e293b;
  font-weight: 600;
  font-size: 0.9rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 16px;
  font-size: 1.2rem;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 14px 16px 14px 48px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f8fafc;
  color: #1e293b;
}

.form-input:focus {
  outline: none;
  border-color: #ec4899;
  background: white;
  box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.1);
}

.btn-primary {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  margin-top: 1rem;
  padding: 12px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 8px;
  font-size: 0.9rem;
  text-align: center;
  border: 1px solid #fecaca;
}

.register-link {
  text-align: center;
  margin-top: 1.5rem;
  color: #64748b;
  font-size: 0.9rem;
}

.register-link a {
  color: #ec4899;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
}

.register-link a:hover {
  color: #db2777;
  text-decoration: underline;
}

@media (max-width: 968px) {
  .login-wrapper {
    grid-template-columns: 1fr;
  }
  
  .login-left {
    padding: 3rem 2rem;
  }
  
  .brand-title {
    font-size: 2rem;
  }
  
  .brand-icon {
    font-size: 4rem;
    width: 100px;
    height: 100px;
  }
}

@media (max-width: 640px) {
  .login-container {
    padding: 1rem;
  }
  
  .login-left,
  .login-right {
    padding: 2rem 1.5rem;
  }
  
  .brand-features {
    gap: 1rem;
  }
  
  .feature-item {
    font-size: 0.9rem;
  }
}
</style>
