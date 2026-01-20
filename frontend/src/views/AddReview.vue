<template>
  <div class="add-review-page">
    <div class="page-header">
      <div>
        <h1>Nouvelle Review</h1>
        <p class="subtitle">Créez une nouvelle review et analysez-la avec l'IA</p>
      </div>
    </div>

    <div class="page-content">
      <div class="form-card">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label>
              <span class="label-icon"><i class="fa-solid fa-pen"></i></span>
              Contenu de la review
            </label>
            <div class="textarea-wrapper">
              <textarea
                v-model="form.content"
                rows="10"
                required
                minlength="10"
                placeholder="Décrivez votre expérience en détail... (minimum 10 caractères)"
                class="review-textarea"
              ></textarea>
              <div class="char-counter" :class="{ 'warning': form.content.length < 10 }">
                {{ form.content.length }} / 10 caractères minimum
              </div>
            </div>
          </div>

          <div v-if="analysis" class="analysis-card">
            <div class="analysis-header">
              <h3><i class="fa-solid fa-robot"></i> Analyse IA</h3>
            </div>
            <div class="analysis-grid">
              <div class="analysis-box">
                <div class="analysis-label">Sentiment</div>
                <span :class="['badge', 'sentiment', analysis.sentiment, 'large']">
                  <span class="badge-icon" v-html="getSentimentIcon(analysis.sentiment)"></span>
                  <span>{{ analysis.sentiment }}</span>
                </span>
              </div>
              <div class="analysis-box">
                <div class="analysis-label">Score</div>
                <div class="score-display">
                  <span class="score-value">{{ analysis.score }}</span>
                  <span class="score-max">/100</span>
                </div>
              </div>
              <div v-if="analysis.topics && analysis.topics.length > 0" class="analysis-box full-width">
                <div class="analysis-label">Topics détectés</div>
                <div class="topics-list">
                  <span v-for="topic in analysis.topics" :key="topic" class="topic-chip">
                    {{ topic }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button 
              type="button" 
              @click="analyzeText" 
              :disabled="!form.content || form.content.length < 10 || analyzing"
              class="btn-analyze"
            >
              <span v-if="!analyzing"><i class="fa-solid fa-robot"></i> Analyser avec IA</span>
              <span v-else class="spinner-small"></span>
            </button>
            <button 
              type="submit" 
              :disabled="loading || !form.content || form.content.length < 10"
              class="btn-submit"
            >
              <span v-if="!loading">💾 Enregistrer la review</span>
              <span v-else class="spinner-small"></span>
            </button>
            <router-link to="/" class="btn-cancel">Annuler</router-link>
          </div>

          <div v-if="error" class="alert error">
            <span><i class="fa-solid fa-triangle-exclamation"></i></span>
            <span>{{ error }}</span>
          </div>
          <div v-if="success" class="alert success">
            <span>✅</span>
            <span>{{ success }}</span>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';

const router = useRouter();
const form = ref({ content: '' });
const analysis = ref(null);
const loading = ref(false);
const analyzing = ref(false);
const error = ref('');
const success = ref('');

const getSentimentIcon = (sentiment) => {
  const icons = {
    'positif': '<i class="fa-solid fa-face-smile"></i>',
    'négatif': '<i class="fa-solid fa-face-frown"></i>',
    'neutre': '<i class="fa-solid fa-face-meh"></i>'
  };
  return icons[sentiment] || '<i class="fa-solid fa-face-meh"></i>';
};

const analyzeText = async () => {
  if (!form.value.content || form.value.content.length < 10) {
    return;
  }
  analyzing.value = true;
  error.value = '';
  try {
    const response = await api.post('/analyze', { text: form.value.content });
    analysis.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de l\'analyse';
  } finally {
    analyzing.value = false;
  }
};

const handleSubmit = async () => {
  if (!form.value.content || form.value.content.length < 10) {
    error.value = 'Le contenu doit contenir au moins 10 caractères';
    return;
  }
  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    await api.post('/reviews', form.value);
    success.value = 'Review créée avec succès !';
    setTimeout(() => {
      router.push('/');
    }, 1500);
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la création de la review';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.add-review-page {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(to bottom, #f8fafc 0%, #fdf2f8 100%);
}

.page-header {
  max-width: 900px;
  margin: 0 auto 2rem;
}

h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: #64748b;
  font-size: 1.1rem;
}

.page-content {
  max-width: 900px;
  margin: 0 auto;
}

.form-card {
  background: white;
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.form-group {
  margin-bottom: 2rem;
}

label {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  color: #1e293b;
  font-weight: 600;
  font-size: 1.1rem;
}

.label-icon {
  font-size: 1.3rem;
}

.textarea-wrapper {
  position: relative;
}

.review-textarea {
  width: 100%;
  padding: 1.25rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
  transition: all 0.3s ease;
  background: #f8fafc;
  line-height: 1.7;
}

.review-textarea:focus {
  outline: none;
  border-color: #ec4899;
  background: white;
  box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.1);
}

.char-counter {
  position: absolute;
  bottom: 12px;
  right: 16px;
  font-size: 0.85rem;
  color: #64748b;
  background: white;
  padding: 4px 8px;
  border-radius: 6px;
}

.char-counter.warning {
  color: #ef4444;
  font-weight: 600;
}

.analysis-card {
  background: linear-gradient(135deg, #fdf2f8 0%, #f3e8ff 100%);
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 2px solid #fbcfe8;
}

.analysis-header h3 {
  font-size: 1.3rem;
  color: #1e293b;
  margin-bottom: 1rem;
  font-weight: 700;
}

.analysis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.analysis-box {
  background: white;
  padding: 1.25rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.analysis-box.full-width {
  grid-column: 1 / -1;
}

.analysis-label {
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 8px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge.large {
  padding: 8px 16px;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.score-display {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.score-value {
  font-size: 2rem;
  font-weight: 700;
  color: #f59e0b;
}

.score-max {
  font-size: 1.2rem;
  color: #64748b;
}

.topics-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.topic-chip {
  padding: 6px 14px;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: capitalize;
}

.form-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 2rem;
}

.btn-analyze, .btn-submit {
  flex: 1;
  min-width: 180px;
  padding: 14px 24px;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-analyze {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.btn-analyze:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

.btn-submit {
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
}

.btn-analyze:disabled, .btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-cancel {
  padding: 14px 24px;
  background: #f1f5f9;
  color: #475569;
  text-decoration: none;
  border-radius: 12px;
  font-weight: 600;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
}

.btn-cancel:hover {
  background: #e2e8f0;
}

.spinner-small {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.alert {
  margin-top: 1.5rem;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}

.alert.error {
  background: #fef2f2;
  color: #dc2626;
  border: 2px solid #fecaca;
}

.alert.success {
  background: #f0fdf4;
  color: #16a34a;
  border: 2px solid #bbf7d0;
}

.alert span:first-child {
  font-size: 1.3rem;
}

@media (max-width: 768px) {
  .add-review-page {
    padding: 1rem;
  }
  
  .form-card {
    padding: 1.5rem;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn-analyze, .btn-submit {
    width: 100%;
  }
}
</style>
