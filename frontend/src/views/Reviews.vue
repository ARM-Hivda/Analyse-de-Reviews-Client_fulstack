<template>
  <div class="reviews-page">
    <div class="page-header">
      <div class="header-content">
        <div>
          <h1>{{ pageTitle }}</h1>
          <p class="subtitle">{{ pageSubtitle }}</p>
          <div v-if="isAdmin" class="admin-badge-inline">
            <span><i class="fa-solid fa-crown"></i></span>
            <span>Mode Admin : Vous voyez toutes les reviews</span>
          </div>
        </div>
        <router-link to="/add-review" class="btn-new">
          <span class="btn-icon"><i class="fa-solid fa-plus"></i></span>
          <span>Nouvelle Review</span>
        </router-link>
      </div>
    </div>

    <div class="page-content">
      <div v-if="loading" class="loading-state">
        <div class="spinner-large"></div>
        <p>Chargement des reviews...</p>
      </div>
      
      <div v-else-if="error" class="error-state">
        <div class="error-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <p>{{ error }}</p>
      </div>
      
      <div v-else-if="reviews.length === 0" class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-file-lines"></i></div>
        <h2>Aucune review pour le moment</h2>
        <p>Commencez à analyser vos reviews clients dès maintenant</p>
        <router-link to="/add-review" class="btn-primary">
          <span>Créer ma première review</span>
        </router-link>
      </div>
      
      <div v-else class="reviews-container-wrapper">
        <div class="reviews-grid">
          <div v-for="review in reviews" :key="review.id" class="review-card">
          <div class="card-header">
            <div class="user-info">
              <div class="user-avatar">{{ getUserInitials(review.user?.name) }}</div>
              <div>
                <div class="user-name">{{ review.user?.name || 'Utilisateur' }}</div>
                <div class="review-date">{{ formatDate(review.created_at) }}</div>
              </div>
            </div>
            <div class="card-actions">
              <button @click="editReview(review)" class="action-btn edit" title="Modifier">
                <i class="fa-solid fa-pencil"></i>
              </button>
              <button @click="deleteReview(review.id)" class="action-btn delete" title="Supprimer">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
          
          <div class="card-body">
            <p class="review-content">{{ review.content }}</p>
          </div>
          
          <div class="card-footer">
            <div class="analysis-badges">
              <span :class="['badge', 'sentiment', review.sentiment]">
                <span class="badge-icon" v-html="getSentimentIcon(review.sentiment)"></span>
                <span>{{ review.sentiment }}</span>
              </span>
                  <span class="badge score">
                    <span class="badge-icon"><i class="fa-solid fa-star"></i></span>
                    <span>{{ review.score }}/100</span>
                  </span>
            </div>
            <div v-if="review.topics && review.topics.length > 0" class="topics">
              <span v-for="topic in review.topics" :key="topic" class="topic-tag">
                {{ topic }}
              </span>
            </div>
          </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="pagination-wrapper">
          <div class="pagination-info">
            <span>Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} reviews</span>
          </div>
          <div class="pagination-controls">
            <button 
              @click="goToPage(pagination.current_page - 1)" 
              :disabled="!pagination.prev_page_url"
              class="pagination-btn"
            >
              ← Précédent
            </button>
            <div class="pagination-pages">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                :class="['pagination-page', { active: page === pagination.current_page }]"
              >
                {{ page }}
              </button>
            </div>
            <button 
              @click="goToPage(pagination.current_page + 1)" 
              :disabled="!pagination.next_page_url"
              class="pagination-btn"
            >
              Suivant →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal d'édition -->
    <div v-if="editingReview" class="modal-overlay" @click="editingReview = null">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h2>Modifier la review</h2>
          <button @click="editingReview = null" class="close-btn">✕</button>
        </div>
        <textarea
          v-model="editForm.content"
          rows="6"
          placeholder="Contenu de la review..."
          class="modal-textarea"
        ></textarea>
        <div class="modal-footer">
          <button @click="editingReview = null" class="btn-secondary">Annuler</button>
          <button @click="saveEdit" :disabled="loading" class="btn-primary">
            {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';

const reviews = ref([]);
const pagination = ref(null);
const loading = ref(true);
const error = ref('');
const editingReview = ref(null);
const editForm = ref({ content: '' });
const user = ref(null);
const currentPage = ref(1);
const perPage = ref(10);

const isAdmin = computed(() => {
  return user.value?.role === 'admin';
});

const pageTitle = computed(() => {
  return isAdmin.value ? 'Toutes les Reviews' : 'Mes Reviews';
});

const pageSubtitle = computed(() => {
  if (pagination.value) {
    const total = pagination.value.total;
    if (isAdmin.value) {
      return `${total} review${total > 1 ? 's' : ''} au total (tous les utilisateurs)`;
    }
    return `${total} review${total > 1 ? 's' : ''} au total`;
  }
  return 'Chargement...';
});

const visiblePages = computed(() => {
  if (!pagination.value) return [];
  const current = pagination.value.current_page;
  const last = pagination.value.last_page;
  const pages = [];
  
  // Afficher max 5 pages autour de la page actuelle
  let start = Math.max(1, current - 2);
  let end = Math.min(last, current + 2);
  
  // Ajuster si on est près du début ou de la fin
  if (end - start < 4) {
    if (start === 1) {
      end = Math.min(5, last);
    } else if (end === last) {
      start = Math.max(1, last - 4);
    }
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const loadUser = async () => {
  // Charger depuis localStorage d'abord
  const userStr = localStorage.getItem('user');
  if (userStr) {
    user.value = JSON.parse(userStr);
  }
  
  // Recharger depuis l'API pour avoir les données à jour
  try {
    const response = await api.get('/auth/me');
    user.value = response.data;
    localStorage.setItem('user', JSON.stringify(response.data));
  } catch (err) {
    console.error('Erreur lors du chargement de l\'utilisateur:', err);
  }
};

const fetchReviews = async (page = 1) => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/reviews', {
      params: {
        page: page,
        per_page: perPage.value
      }
    });
    
    // Si la réponse contient des données paginées
    if (response.data.data) {
      reviews.value = response.data.data;
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        per_page: response.data.per_page,
        total: response.data.total,
        from: response.data.from,
        to: response.data.to,
        prev_page_url: response.data.prev_page_url,
        next_page_url: response.data.next_page_url
      };
    } else {
      // Fallback si pas de pagination
      reviews.value = response.data;
      pagination.value = null;
    }
    currentPage.value = page;
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du chargement des reviews';
  } finally {
    loading.value = false;
  }
};

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value?.last_page) {
    fetchReviews(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

onMounted(async () => {
  await loadUser();
  await fetchReviews();
});

const deleteReview = async (id) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette review ?')) {
    return;
  }
  try {
    await api.delete(`/reviews/${id}`);
    // Recharger la page actuelle après suppression
    await fetchReviews(currentPage.value);
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la suppression';
  }
};

const editReview = (review) => {
  editingReview.value = review;
  editForm.value.content = review.content;
};

const saveEdit = async () => {
  if (!editForm.value.content || editForm.value.content.length < 10) {
    alert('Le contenu doit contenir au moins 10 caractères');
    return;
  }
  loading.value = true;
  try {
    const response = await api.put(`/reviews/${editingReview.value.id}`, {
      content: editForm.value.content,
    });
    
    // Mettre à jour la review dans la liste avec les nouvelles données IA
    const index = reviews.value.findIndex(r => r.id === editingReview.value.id);
    if (index !== -1) {
      // Remplacer complètement la review avec les nouvelles données (sentiment, score, topics mis à jour)
      reviews.value[index] = response.data;
      console.log('Review mise à jour avec nouvelle analyse IA:', response.data);
    }
    
    editingReview.value = null;
    
    // Afficher un message de succès
    const successMsg = `Review mise à jour ! Nouvelle analyse : ${response.data.sentiment} (${response.data.score}/100)`;
    console.log(successMsg);
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors de la modification';
    console.error('Erreur lors de la mise à jour:', err);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
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

</script>

<style scoped>
.reviews-page {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(to bottom, #f8fafc 0%, #fdf2f8 100%);
}

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
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
  font-size: 1rem;
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

.btn-new {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  text-decoration: none;
  border-radius: 12px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

.btn-new:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(236, 72, 153, 0.4);
}

.btn-icon {
  font-size: 1.2rem;
}

.page-content {
  max-width: 1400px;
  margin: 0 auto;
}

.loading-state, .error-state, .empty-state {
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

.empty-icon, .error-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h2 {
  font-size: 1.5rem;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #64748b;
  margin-bottom: 1.5rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  padding: 12px 24px;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  color: white;
  text-decoration: none;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.reviews-container-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
}

.reviews-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
  width: 100%;
}

/* Optimisation pour grands écrans */
@media (min-width: 1400px) {
  .reviews-grid {
    grid-template-columns: repeat(3, 1fr);
    max-width: 1400px;
  }
}

/* 2 colonnes sur écrans moyens */
@media (min-width: 1100px) and (max-width: 1399px) {
  .reviews-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* 1 colonne sur petits écrans */
@media (max-width: 1099px) {
  .reviews-grid {
    grid-template-columns: 1fr;
  }
}

.review-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid #e2e8f0;
}

.review-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f5f9;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.9rem;
}

.user-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 1rem;
}

.review-date {
  font-size: 0.85rem;
  color: #64748b;
  margin-top: 2px;
}

.card-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
}

.action-btn:hover {
  transform: scale(1.1);
}

.action-btn.edit:hover {
  background: #dbeafe;
}

.action-btn.delete:hover {
  background: #fee2e2;
}

.card-body {
  margin-bottom: 1rem;
}

.review-content {
  line-height: 1.7;
  color: #475569;
  font-size: 0.95rem;
}

.card-footer {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.analysis-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge.sentiment.positif {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.badge.sentiment.négatif {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.badge.sentiment.neutre {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
}

.badge.score {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.badge-icon {
  font-size: 1rem;
}

.topics {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.topic-tag {
  padding: 4px 10px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
}

.modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 2px solid #f1f5f9;
}

.modal-header h2 {
  font-size: 1.5rem;
  color: #1e293b;
  font-weight: 700;
}

.close-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: #f1f5f9;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1.2rem;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #e2e8f0;
  transform: rotate(90deg);
}

.modal-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
  min-height: 150px;
  margin: 1.5rem;
  transition: all 0.3s ease;
}

.modal-textarea:focus {
  outline: none;
  border-color: #ec4899;
  box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 1.5rem;
  border-top: 2px solid #f1f5f9;
  justify-content: flex-end;
}

.btn-secondary {
  padding: 10px 20px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.modal-footer .btn-primary {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
}

/* Pagination */
.pagination-wrapper {
  margin-top: 3rem;
  padding: 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  align-items: center;
}

.pagination-info {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: center;
}

.pagination-btn {
  padding: 10px 20px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #475569;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #ec4899;
  color: #ec4899;
  transform: translateY(-2px);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-pages {
  display: flex;
  gap: 0.5rem;
}

.pagination-page {
  min-width: 40px;
  height: 40px;
  padding: 0 12px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #475569;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-page:hover {
  border-color: #ec4899;
  color: #ec4899;
  transform: translateY(-2px);
}

.pagination-page.active {
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  border-color: #ec4899;
  color: white;
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

@media (max-width: 768px) {
  .reviews-grid {
    grid-template-columns: 1fr;
  }
  
  .reviews-page {
    padding: 1rem;
  }
  
  h1 {
    font-size: 2rem;
  }
  
  .pagination-controls {
    flex-direction: column;
  }
  
  .pagination-pages {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
