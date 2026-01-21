# Application de Gestion et d'Analyse de Reviews Clients

Application complète de gestion de reviews avec analyse IA (sentiment, score, topics) développée avec Laravel 12 (backend) et Vue 3 (frontend).

##  Stack Technique

### Backend
- **Laravel 12** - Framework PHP
- **Laravel Sanctum** - Authentification API
- **MySQL** - Base de données

### Frontend
- **Vue 3** - Framework JavaScript
- **Vue Router 4** - Routing
- **Axios** - Client HTTP

### IA / NLP
- Service d'analyse de sentiment personnalisé
- Détection de topics (livraison, prix, qualité, service, satisfaction)
- Calcul de score (0-100)

##  Prérequis

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL

## Installation

### Backend

1. Installer les dépendances PHP :
```bash
composer install
```

2. Copier le fichier `.env` :
```bash
cp .env.example .env
```

3. Configurer la base de données MySQL dans `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=review_analyzer
DB_USERNAME=root
DB_PASSWORD=
```

4. Générer la clé d'application :
```bash
php artisan key:generate
```

5. Exécuter les migrations :
```bash
php artisan migrate
```

6. Créer des utilisateurs de test :
```bash
php artisan db:seed
```

7. Démarrer le serveur de développement :
```bash
php artisan serve --port=8000
```

Le backend sera accessible sur `http://localhost:8001`

### Frontend

1. Aller dans le dossier frontend :
```bash
cd frontend
```

2. Installer les dépendances :
```bash
npm install
```

3. Démarrer le serveur de développement :
```bash
npm run dev
```

Le frontend sera accessible sur `http://localhost:5173`

## Comptes de test

**Admin :**
- Email: `admin@example.com`
- Password: `password`

**User :**
- Email: `user@example.com`
- Password: `password`

##  API Endpoints

### Authentification

- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion (nécessite auth)
- `GET /api/auth/me` - Utilisateur actuel (nécessite auth)

### Reviews

- `GET /api/reviews` - Liste des reviews (pagination)
- `POST /api/reviews` - Créer une review (analyse IA automatique)
- `GET /api/reviews/{id}` - Récupérer une review
- `PUT /api/reviews/{id}` - Modifier une review (réanalyse IA)
- `DELETE /api/reviews/{id}` - Supprimer une review

### Analyse IA

- `POST /api/analyze` - Analyser un texte sans créer de review (regarde exemple) 

**Body:**
```json
{
  "text": "Excellent produit, livraison rapide !"
}
```

**Response:**
```json
{
  "sentiment": "positif",
  "score": 85,
  "topics": ["livraison", "qualité"]
}
```

### Dashboard

- `GET /api/dashboard/stats` - Statistiques (nécessite auth)


##  Analyse IA

Le service analyse les reviews en détectant :
- **Sentiment** : positif, neutre, ou négatif
- **Score** : 0-100 basé sur le ratio de mots positifs/négatifs
- **Topics** : livraison, prix, qualité, service, satisfaction

##  Authentification

L'application utilise Laravel Sanctum pour l'authentification par token.

**Utilisation :**
1. Se connecter via `/api/auth/login`
2. Récupérer le token dans la réponse
3. Inclure le token dans les headers :
```
Authorization: Bearer {token}
```

##  Frontend

Le frontend Vue 3 est séparé du backend et communique uniquement via l'API.

**Pages disponibles :**
- `/login` - Connexion/Inscription
- `/` - Liste des reviews (pagination)
- `/add-review` - Créer une nouvelle review
- `/dashboard` - Statistiques et analyses

## Notes

- Les users normaux ne peuvent voir et modifier que leurs propres reviews
- Les admins peuvent voir et supprimer toutes les reviews
- L'analyse IA est automatique lors de la création/modification d'une review
- Le backend doit tourner sur le port 8000 (configuré dans le frontend)

##  Déploiement

### Backend
1. Configurer MySQL dans `.env`
2. Exécuter les migrations : `php artisan migrate`
3. Optimiser : `php artisan config:cache`, `php artisan route:cache`

### Frontend
1. Build : `npm run build`
2. Servir les fichiers dans `dist/` avec un serveur web
