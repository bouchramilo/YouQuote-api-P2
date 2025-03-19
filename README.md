# YouQuote API

## Contexte du projet

**YouQuote** est une API avancée permettant la gestion des citations avec des fonctionnalités enrichies. Elle inclut l’authentification via **Laravel Sanctum**, la gestion des permissions et des rôles avec **Spatie Permissions**, ainsi que de nouvelles options pour améliorer l’expérience utilisateur. L'API est conçue pour permettre aux utilisateurs de créer, gérer, et interagir avec des citations, tout en offrant des fonctionnalités de modération pour les administrateurs.

---

## Nouvelles Fonctionnalités

### 🔐 Authentification et Permissions

- **Inscription et Connexion** : Les utilisateurs doivent s'inscrire et se connecter pour gérer leurs propres citations.
- **Rôles Utilisateur** :
  - **Admin** : Peut gérer toutes les citations (CRUD global, modération).
  - **Auteur** : Peut gérer uniquement ses propres citations.
- **Permissions** : Les permissions sont gérées avec **Spatie Permissions**, permettant un contrôle granulaire des actions.

### 📌 Ajout de Catégories et Tags

- **Catégories** : Chaque citation peut appartenir à une ou plusieurs catégories.
- **Tags** : Possibilité d’ajouter des tags pour faciliter la recherche et l'organisation des citations.

### ⭐ Système de Likes et Favoris

- **Likes** : Les utilisateurs peuvent liker des citations.
- **Favoris** : Possibilité d'ajouter des citations aux favoris pour les retrouver plus tard.

### 🗑️ Soft Deletes

- Lorsqu’une citation est supprimée par un utilisateur ou un admin, elle est "archivée" (soft delete) pour éviter la perte de données.
- Un admin peut restaurer des citations supprimées.

---

## Installation

### Cloner le projet

Pour commencer à utiliser YouQuote, clonez le dépôt GitHub sur votre machine locale :

```bash
git clone https://github.com/bouchramilo/YouQuote-api-P2.git
cd YouQuote-api-P2
```

### Installer les dépendances

Installez les dépendances PHP avec Composer :

```bash
composer install
```

### Configurer l'environnement

Copiez le fichier `.env.example` et renommez-le en `.env`. Configurez les variables d'environnement pour votre base de données et autres services :

```bash
cp .env.example .env
```

Générez une clé d'application :

```bash
php artisan key:generate
```

### Migrer la base de données

Exécutez les migrations pour créer les tables de la base de données :

```bash
php artisan migrate --seed
```

### Lancer le serveur

Démarrez le serveur de développement Laravel :

```bash
php artisan serve
```

L'API sera accessible à l'adresse `http://localhost:8000`.

---

## Routes Disponibles

### Authentification (Sanctum)

- **`POST /api/register`** : Inscription d'un nouvel utilisateur.
- **`POST /api/login`** : Connexion d'un utilisateur et récupération d'un token Sanctum.
- **`POST /api/logout`** : Déconnexion de l'utilisateur et invalidation du token Sanctum (nécessite une authentification).

### Gestion des Citations

- **`GET /api/quotes`** : Récupérer toutes les citations (nécessite une authentification).
- **`GET /api/quotes/{id}`** : Récupérer une citation spécifique par son ID (nécessite une authentification).
- **`POST /api/quotes`** : Créer une nouvelle citation (nécessite une authentification et le rôle `auteur`).
- **`PUT /api/quotes/{id}`** : Mettre à jour une citation existante (nécessite une authentification et le rôle `auteur` ou `admin`).
- **`DELETE /api/quotes/{id}`** : Supprimer une citation (soft delete, nécessite une authentification et le rôle `auteur` ou `admin`).

### Gestion des Catégories

- **`GET /api/categories`** : Récupérer toutes les catégories (nécessite une authentification).
- **`POST /api/categories`** : Créer une nouvelle catégorie (nécessite une authentification et le rôle `admin`).
- **`PUT /api/categories/{id}`** : Mettre à jour une catégorie existante (nécessite une authentification et le rôle `admin`).
- **`DELETE /api/categories/{id}`** : Supprimer une catégorie (nécessite une authentification et le rôle `admin`).

### Gestion des Tags

- **`GET /api/tags`** : Récupérer tous les tags (nécessite une authentification).
- **`POST /api/tags`** : Créer un nouveau tag (nécessite une authentification et le rôle `admin`).
- **`PUT /api/tags/{id}`** : Mettre à jour un tag existant (nécessite une authentification et le rôle `admin`).
- **`DELETE /api/tags/{id}`** : Supprimer un tag (nécessite une authentification et le rôle `admin`).

### Gestion des Likes

- **`GET /api/likes`** : Récupérer tous les likes (nécessite une authentification).
- **`POST /api/likes`** : Ajouter un like à une citation (nécessite une authentification).
- **`DELETE /api/likes/{id}`** : Retirer un like d'une citation (nécessite une authentification).

### Gestion des Favoris

- **`GET /api/favories`** : Récupérer toutes les citations favorites (nécessite une authentification).
- **`POST /api/favories`** : Ajouter une citation aux favoris (nécessite une authentification).
- **`DELETE /api/favories/{id}`** : Retirer une citation des favoris (nécessite une authentification).

### Gestion des Suppressions (Soft Delete)

- **`GET /api/suppression`** : Récupérer toutes les citations supprimées (nécessite une authentification et le rôle `admin`).
- **`GET /api/suppression/{id}`** : Récupérer une citation supprimée spécifique par son ID (nécessite une authentification et le rôle `admin`).
- **`POST /api/suppression/{id}`** : Restaurer une citation supprimée (nécessite une authentification et le rôle `admin`).
- **`DELETE /api/suppression/{id}`** : Supprimer définitivement une citation (nécessite une authentification et le rôle `admin`).

### Recherche

- **`POST /api/quotes/category`** : Rechercher des citations par catégorie (nécessite une authentification).
- **`POST /api/quotes/tag`** : Rechercher des citations par tag (nécessite une authentification).

### Validation des Citations (Admin)

- **`POST /api/quotes/valider/{id}`** : Valider une citation (nécessite une authentification et le rôle `admin`).

---

## Rôles et Permissions (Spatie)

### Rôles

- **Admin** : Accès complet à toutes les fonctionnalités, y compris la modération et la gestion des utilisateurs.
- **Auteur** : Peut créer, modifier et supprimer ses propres citations, mais ne peut pas modérer les citations des autres utilisateurs.

### Permissions

Les permissions sont attribuées aux rôles pour contrôler l'accès aux différentes fonctionnalités. Par exemple :
- `create quote` : Autorisation de créer une citation.
- `edit quote` : Autorisation de modifier une citation.
- `delete quote` : Autorisation de supprimer une citation.
- `validate quote` : Autorisation de valider une citation (réservé aux admins).

---
