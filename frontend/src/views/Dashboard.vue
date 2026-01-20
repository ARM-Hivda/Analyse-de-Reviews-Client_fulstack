<template>
  <div class="dashboard-page">
    <div class="page-header">
      <div>
        <h1><i class="fa-solid fa-chart-bar"></i> Tableau de Bord</h1>
        <p class="subtitle">
          {{ isAdmin ? 'Vue d\'ensemble de toutes les reviews (tous les utilisateurs)' : 'Vue d\'ensemble de vos analyses de reviews' }}
        </p>
        <div v-if="isAdmin" class="admin-badge-inline">
          <span><i class="fa-solid fa-crown"></i></span>
          <span>Mode Admin : Vous voyez toutes les statistiques</span>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <div v-if="loading" class="loading-state">
        <div class="spinner-large"></div>
        <p>Chargement des statistiques...</p>
      </div>
      
      <div v-else-if="error" class="error-state">
        <div class="error-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <p>{{ error }}</p>
      </div>
      
      <div v-else-if="stats" class="stats-wrapper">
        <!-- Statistiques principales -->
        <div class="stats-grid">
          <div class="stat-card primary">
            <div class="stat-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div class="stat-info">
              <div class="stat-label">Total Reviews</div>
              <div class="stat-value">{{ stats.total_reviews }}</div>
              <div class="stat-change">Toutes les reviews</div>
            </div>
          </div>
          
          <div class="stat-card score">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="stat-info">
              <div class="stat-label">Score Moyen</div>
              <div class="stat-value">{{ stats.average_score }}/100</div>
              <div class="stat-change" :class="getScoreClass(stats.average_score)">
                {{ getScoreLabel(stats.average_score) }}
              </div>
            </div>
          </div>
          
          <div class="stat-card positive">
            <div class="stat-icon"><i class="fa-solid fa-face-smile"></i></div>
            <div class="stat-info">
              <div class="stat-label">Positif</div>
              <div class="stat-value">{{ stats.positive_percentage }}%</div>
              <div class="stat-bar">
                <div class="stat-bar-fill" :style="{ width: stats.positive_percentage + '%' }"></div>
              </div>
            </div>
          </div>
          
          <div class="stat-card negative">
            <div class="stat-icon"><i class="fa-solid fa-face-frown"></i></div>
            <div class="stat-info">
              <div class="stat-label">Négatif</div>
              <div class="stat-value">{{ stats.negative_percentage }}%</div>
              <div class="stat-bar">
                <div class="stat-bar-fill" :style="{ width: stats.negative_percentage + '%' }"></div>
              </div>
            </div>
          </div>
          
          <div class="stat-card neutral">
            <div class="stat-icon"><i class="fa-solid fa-face-meh"></i></div>
            <div class="stat-info">
              <div class="stat-label">Neutre</div>
              <div class="stat-value">{{ stats.neutral_percentage }}%</div>
              <div class="stat-bar">
                <div class="stat-bar-fill" :style="{ width: stats.neutral_percentage + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grid principal avec 3 colonnes -->
        <div class="main-grid">
          <!-- Colonne 1: Top Topics -->
          <div class="section-card topics-card">
            <div class="section-header">
              <div class="section-title-wrapper">
                <span class="section-icon"><i class="fa-solid fa-tags"></i></span>
                <h2>Topics Populaires</h2>
              </div>
            </div>
            <div v-if="stats.top_topics && stats.top_topics.length > 0" class="topics-container">
              <div
                v-for="(topic, index) in stats.top_topics"
                :key="topic.topic"
                class="topic-row"
              >
                <div class="topic-rank-badge">{{ index + 1 }}</div>
                <div class="topic-info">
                  <div class="topic-name">{{ topic.topic }}</div>
                  <div class="topic-progress">
                    <div class="topic-progress-bar" :style="{ width: Math.min((topic.count / stats.total_reviews * 100), 100) + '%' }"></div>
                  </div>
                </div>
                <div class="topic-count">{{ topic.count }}</div>
              </div>
            </div>
            <div v-else class="empty-message">
              <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
              <p>Aucun topic détecté pour le moment.</p>
              <small>Les topics apparaîtront automatiquement lors de l'analyse des reviews</small>
            </div>
          </div>

          <!-- Colonne 2: Dernières Reviews -->
          <div class="section-card reviews-card">
            <div class="section-header">
              <div class="section-title-wrapper">
                <span class="section-icon"><i class="fa-solid fa-clock"></i></span>
                <h2>Dernières Reviews</h2>
              </div>
              <router-link to="/" class="view-all-link">Voir tout →</router-link>
            </div>
            <div v-if="stats.latest_reviews && stats.latest_reviews.length > 0" class="reviews-container">
              <div
                v-for="review in stats.latest_reviews"
                :key="review.id"
                class="review-card-mini"
              >
                <div class="review-header-mini">
                  <div class="user-avatar-mini">{{ getUserInitials(review.user?.name) }}</div>
                  <div class="review-meta-mini">
                    <div class="review-user-name">{{ review.user?.name || 'Utilisateur' }}</div>
                    <div class="review-date-mini">{{ formatDate(review.created_at) }}</div>
                  </div>
                </div>
                <p class="review-content-mini">{{ truncate(review.content, 100) }}</p>
                <div class="review-badges-mini">
                  <span :class="['badge-mini', 'sentiment', review.sentiment]">
                    <span v-html="getSentimentIcon(review.sentiment)"></span> {{ review.sentiment }}
                  </span>
                  <span class="badge-mini score"><i class="fa-solid fa-star"></i> {{ review.score }}/100</span>
                </div>
              </div>
            </div>
            <div v-else class="empty-message">
              <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
              <p>Aucune review pour le moment.</p>
              <router-link to="/add-review" class="btn-create-review">Créer une review</router-link>
            </div>
          </div>

          <!-- Colonne 3: Graphique de répartition -->
          <div class="section-card chart-card">
            <div class="section-header">
              <div class="section-title-wrapper">
                <span class="section-icon"><i class="fa-solid fa-chart-line"></i></span>
                <h2>Répartition</h2>
              </div>
            </div>
            <div class="chart-container">
              <div class="sentiment-chart">
                <div class="chart-item positive-chart">
                  <div class="chart-bar" :style="{ height: stats.positive_percentage + '%' }">
                    <span class="chart-value">{{ stats.positive_percentage }}%</span>
                  </div>
                  <div class="chart-label">Positif</div>
                </div>
                <div class="chart-item neutral-chart">
                  <div class="chart-bar" :style="{ height: stats.neutral_percentage + '%' }">
                    <span class="chart-value">{{ stats.neutral_percentage }}%</span>
                  </div>
                  <div class="chart-label">Neutre</div>
                </div>
                <div class="chart-item negative-chart">
                  <div class="chart-bar" :style="{ height: stats.negative_percentage + '%' }">
                    <span class="chart-value">{{ stats.negative_percentage }}%</span>
                  </div>
                  <div class="chart-label">Négatif</div>
                </div>
              </div>
            </div>
            <div class="chart-summary">
              <div class="summary-item">
                <span class="summary-label">Total</span>
                <span class="summary-value">{{ stats.total_reviews }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Moyenne</span>
                <span class="summary-value">{{ stats.average_score }}/100</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';

const stats = ref(null);
const loading = ref(true);
const error = ref('');
const user = ref(null);

const isAdmin = computed(() => {
  return user.value?.role === 'admin';
});

const loadUser = async () => {
  const userStr = localStorage.getItem('user');
  if (userStr) {
    user.value = JSON.parse(userStr);
  }
  
  try {
    const response = await api.get('/auth/me');
    user.value = response.data;
    localStorage.setItem('user', JSON.stringify(response.data));
  } catch (err) {
    console.error('Erreur lors du chargement de l\'utilisateur:', err);
  }
};

const fetchStats = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/dashboard/stats');
    console.log('Dashboard stats:', response.data);
    stats.value = response.data;
  } catch (err) {
    console.error('Erreur dashboard:', err);
    error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques';
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const truncate = (text, maxLength) => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

const getUserInitials = (name) => {
  if (!name) return 'U';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const getSentimentIcon = (sentiment) => {
  const icons = {
    'positif': '<i class="fa-solid fa-face-smile"></i>',
    'négatif': '<i class="fa-solid fa-face-frown"></i>',
    'neutre': '<i class="fa-solid fa-face-meh"></i>'
  };
  return icons[sentiment] || '<i class="fa-solid fa-face-meh"></i>';
};

const getScoreClass = (score) => {
  if (score >= 70) return 'score-excellent';
  if (score >= 50) return 'score-good';
  return 'score-poor';
};

const getScoreLabel = (score) => {
  if (score >= 70) return 'Excellent';
  if (score >= 50) return 'Correct';
  return 'À améliorer';
};

onMounted(async () => {
  await loadUser();
  await fetchStats();
});
</script>

<style scoped>
.dashboard-page {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(to bottom, #f8fafc 0%, #fdf2f8 100%);
}

.page-header {
  max-width: 1600px;
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

.admin-badge-inline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.dashboard-content {
  max-width: 1600px;
  margin: 0 auto;
}

.loading-state, .error-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.spinner-large {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top-color: #ec4899;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.stats-wrapper {
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-card.primary {
  border-color: #ec4899;
  background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
}

.stat-card.score {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.stat-card.positive {
  border-color: #10b981;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.stat-card.negative {
  border-color: #ef4444;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.stat-card.neutral {
  border-color: #64748b;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.stat-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.stat-change {
  font-size: 0.75rem;
  color: #64748b;
}

.stat-change.score-excellent {
  color: #10b981;
  font-weight: 600;
}

.stat-change.score-good {
  color: #f59e0b;
  font-weight: 600;
}

.stat-change.score-poor {
  color: #ef4444;
  font-weight: 600;
}

.stat-bar {
  margin-top: 8px;
  height: 6px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
  overflow: hidden;
}

.stat-bar-fill {
  height: 100%;
  background: currentColor;
  border-radius: 3px;
  transition: width 0.5s ease;
}

/* Grid principal avec 3 colonnes */
.main-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.section-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
}

.section-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-icon {
  font-size: 1.5rem;
}

.section-header h2 {
  font-size: 1.3rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.view-all-link {
  color: #ec4899;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 600;
  transition: all 0.2s;
}

.view-all-link:hover {
  color: #db2777;
  transform: translateX(4px);
}

/* Topics */
.topics-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  flex: 1;
}

.topic-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.topic-row:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.topic-rank-badge {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.topic-info {
  flex: 1;
  min-width: 0;
}

.topic-name {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 6px;
  text-transform: capitalize;
  font-size: 1rem;
}

.topic-progress {
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
}

.topic-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #ec4899 0%, #a855f7 100%);
  border-radius: 2px;
  transition: width 0.5s ease;
}

.topic-count {
  font-weight: 700;
  color: #ec4899;
  font-size: 1.1rem;
  flex-shrink: 0;
}

/* Reviews */
.reviews-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  flex: 1;
  max-height: 600px;
  overflow-y: auto;
}

.review-card-mini {
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid #ec4899;
  transition: all 0.2s ease;
}

.review-card-mini:hover {
  background: #f1f5f9;
  transform: translateX(4px);
}

.review-header-mini {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.user-avatar-mini {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.review-meta-mini {
  flex: 1;
  min-width: 0;
}

.review-user-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
}

.review-date-mini {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 2px;
}

.review-content-mini {
  color: #475569;
  font-size: 0.9rem;
  line-height: 1.6;
  margin-bottom: 8px;
}

.review-badges-mini {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.badge-mini {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-mini.sentiment.positif {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.badge-mini.sentiment.négatif {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.badge-mini.sentiment.neutre {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
}

.badge-mini.score {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

/* Chart */
.chart-container {
  flex: 1;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 2rem 0;
  min-height: 200px;
}

.sentiment-chart {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  gap: 1rem;
  width: 100%;
  height: 100%;
}

.chart-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.chart-bar {
  width: 100%;
  min-height: 40px;
  border-radius: 8px 8px 0 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 8px;
  transition: height 0.5s ease;
  position: relative;
}

.chart-item.positive-chart .chart-bar {
  background: linear-gradient(180deg, #10b981 0%, #059669 100%);
}

.chart-item.neutral-chart .chart-bar {
  background: linear-gradient(180deg, #64748b 0%, #475569 100%);
}

.chart-item.negative-chart .chart-bar {
  background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
}

.chart-value {
  color: white;
  font-weight: 700;
  font-size: 0.9rem;
}

.chart-label {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 600;
  text-transform: capitalize;
}

.chart-summary {
  display: flex;
  justify-content: space-around;
  padding-top: 1rem;
  border-top: 2px solid #f1f5f9;
  margin-top: 1rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.summary-label {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-value {
  font-size: 1.2rem;
  font-weight: 700;
  color: #1e293b;
}

.empty-message {
  text-align: center;
  padding: 3rem 2rem;
  color: #64748b;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-message p {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.empty-message small {
  font-size: 0.85rem;
  color: #94a3b8;
}

.btn-create-review {
  margin-top: 1rem;
  padding: 10px 20px;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s;
}

.btn-create-review:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

@media (max-width: 1400px) {
  .main-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-card {
    grid-column: 1 / -1;
  }
}

@media (max-width: 768px) {
  .dashboard-page {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .main-grid {
    grid-template-columns: 1fr;
  }
  
  h1 {
    font-size: 2rem;
  }
  
  .chart-container {
    min-height: 150px;
  }
}
</style>
