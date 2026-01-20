import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Reviews from '../views/Reviews.vue';
import AddReview from '../views/AddReview.vue';
import Dashboard from '../views/Dashboard.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
  },
  {
    path: '/',
    name: 'Reviews',
    component: Reviews,
    meta: { requiresAuth: true },
  },
  {
    path: '/add-review',
    name: 'AddReview',
    component: AddReview,
    meta: { requiresAuth: true },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Garde de navigation pour vérifier l'authentification
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  
  if (to.meta.requiresAuth && !token) {
    next('/login');
  } else if (to.path === '/login' && token) {
    next('/');
  } else {
    next();
  }
});

export default router;


