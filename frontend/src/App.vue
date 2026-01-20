<template>
  <div id="app" :class="{ 'admin-layout': isAdmin }">
    <!-- Sidebar -->
    <aside v-if="showSidebar" class="sidebar" :class="{ 'admin-sidebar': isAdmin }">
      <div class="sidebar-header">
        <div class="logo">
          <div class="logo-icon"><i class="fa-solid fa-chart-bar"></i></div>
          <span class="logo-text">Review Analyzer</span>
        </div>
      </div>
      
      <nav class="sidebar-nav">
        <router-link to="/" class="nav-item">
          <span class="nav-icon"><i class="fa-solid fa-file-lines"></i></span>
          <span class="nav-text">{{ isAdmin ? 'All Reviews' : 'Mes Reviews' }}</span>
        </router-link>
        <router-link to="/add-review" class="nav-item">
          <span class="nav-icon"><i class="fa-solid fa-plus"></i></span>
          <span class="nav-text">Nouvelle Review</span>
        </router-link>
        <router-link to="/dashboard" class="nav-item">
          <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
          <span class="nav-text">Dashboard</span>
        </router-link>
        <div v-if="isAdmin" class="admin-section">
          <div class="admin-badge">ADMIN</div>
        </div>
      </nav>
      
      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">{{ userInitials }}</div>
          <div class="user-details">
            <div class="user-name">{{ user?.name }}</div>
            <div class="user-role">{{ user?.role === 'admin' ? 'Administrateur' : 'Utilisateur' }}</div>
          </div>
        </div>
        <button @click="handleLogout" class="logout-btn">
          <span><i class="fa-solid fa-right-from-bracket"></i></span>
          <span>Déconnexion</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main :class="{ 'with-sidebar': showSidebar }">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from './api/axios';

const router = useRouter();
const route = useRoute();
const user = ref(null);

const isAuthenticated = computed(() => {
  return !!localStorage.getItem('token');
});

const showSidebar = computed(() => {
  return isAuthenticated.value && route.path !== '/login';
});

const isAdmin = computed(() => {
  return user.value?.role === 'admin';
});

const userInitials = computed(() => {
  if (!user.value?.name) return 'U';
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
});

const loadUser = () => {
  const userStr = localStorage.getItem('user');
  if (userStr) {
    user.value = JSON.parse(userStr);
  }
};

const handleLogout = async () => {
  try {
    await api.post('/auth/logout');
  } catch (err) {
    console.error('Erreur lors de la déconnexion:', err);
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    user.value = null;
    router.push('/login');
  }
};

onMounted(() => {
  loadUser();
});
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --primary: #ec4899;
  --primary-dark: #db2777;
  --primary-light: #f472b6;
  --secondary: #a855f7;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --dark: #1e293b;
  --light: #f8fafc;
  --gray: #64748b;
  --border: #e2e8f0;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  background: #f8fafc;
  min-height: 100vh;
  color: #1e293b;
}

#app {
  min-height: 100vh;
  display: flex;
}

/* Sidebar */
.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  color: white;
  display: flex;
  flex-direction: column;
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
  position: fixed;
  height: 100vh;
  left: 0;
  top: 0;
  z-index: 1000;
}

.admin-sidebar {
  background: linear-gradient(180deg, #ec4899 0%, #a855f7 100%);
}

.sidebar-header {
  padding: 2rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  font-size: 2rem;
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.sidebar-nav {
  flex: 1;
  padding: 1.5rem 0;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem 1.5rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
  margin: 0.25rem 0;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border-left-color: var(--primary-light);
}

.nav-item.router-link-active {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-left-color: white;
  font-weight: 600;
}

.nav-icon {
  font-size: 1.25rem;
  width: 24px;
  text-align: center;
}

.nav-text {
  font-size: 0.95rem;
}

.admin-section {
  margin-top: 2rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-badge {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #1e293b;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-align: center;
  letter-spacing: 1px;
}

.sidebar-footer {
  padding: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
}

.user-details {
  flex: 1;
}

.user-name {
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 2px;
}

.user-role {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  text-transform: capitalize;
}

.logout-btn {
  width: 100%;
  padding: 0.75rem;
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: white;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: rgba(239, 68, 68, 0.3);
  border-color: rgba(239, 68, 68, 0.5);
  transform: translateY(-2px);
}

/* Main Content */
main {
  flex: 1;
  min-height: 100vh;
  background: var(--light);
}

main.with-sidebar {
  margin-left: 280px;
}

/* Admin Layout */
.admin-layout main {
  background: linear-gradient(135deg, #fdf2f8 0%, #f3e8ff 100%);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }
  
  .sidebar.open {
    transform: translateX(0);
  }
  
  main.with-sidebar {
    margin-left: 0;
  }
}
</style>
