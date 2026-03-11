# 📚 API REST Laravel — Gestion Étudiants & Cours

> Projet pédagogique — IPD Licence STI  


---

## Table des matières

1. [Présentation du projet](#1-présentation-du-projet)
2. [Concepts fondamentaux expliqués](#2-concepts-fondamentaux-expliqués)
   - [Qu'est-ce qu'une API REST ?](#21-quest-ce-quune-api-rest-)
   - [Pourquoi Laravel ?](#22-pourquoi-laravel-)
   - [Laravel Sanctum — Authentification par token](#23-laravel-sanctum--authentification-par-token)
   - [Le Rate Limiting](#24-le-rate-limiting-limitation-de-débit)
   - [Les API Resources](#25-les-api-resources)
   - [Les Form Requests — Validation](#26-les-form-requests--validation)
   - [La relation Many-to-Many](#27-la-relation-many-to-many)
   - [Le versioning d'API](#28-le-versioning-dapi)
   - [Les statuts HTTP](#29-les-statuts-http)
3. [Architecture du projet](#3-architecture-du-projet)
4. [Installation et configuration](#4-installation-et-configuration)
5. [Lancer l'API](#5-lancer-lapi)
6. [Endpoints disponibles](#6-endpoints-disponibles)
7. [Exemples d'appels API](#7-exemples-dappels-api)
8. [Tester avec Postman](#8-tester-avec-postman)
9. [Tests automatisés Laravel](#9-tests-automatisés-laravel)
10. [Références officielles](#10-références-officielles)

---

## 1. Présentation du projet

Ce projet est une **API REST** construite avec le framework PHP **Laravel**. Elle permet de gérer :

- Les **étudiants** d'une école
- Les **cours** dispensés
- Les **inscriptions** des étudiants aux cours (un étudiant peut suivre plusieurs cours, un cours peut avoir plusieurs étudiants)

**Important :** Il n'y a aucune interface graphique (pas de pages web, pas de HTML). Tout se consomme via des requêtes HTTP et les réponses sont toujours en **JSON**. C'est ce qu'on appelle un backend pur, qui peut ensuite être consommé par n'importe quel frontend (React, Vue, mobile, etc.) ou par d'autres services.

---

## 2. Concepts fondamentaux expliqués

### 2.1 Qu'est-ce qu'une API REST ?

**API** signifie *Application Programming Interface* (Interface de Programmation d'Application). C'est un ensemble de règles qui permettent à deux logiciels de communiquer entre eux.

**REST** (*Representational State Transfer*) est un style d'architecture pour concevoir ces interfaces. Une API REST respecte plusieurs principes :

- **Sans état (Stateless)** : chaque requête envoyée au serveur est indépendante. Le serveur ne "se souvient" pas des requêtes précédentes. Toutes les informations nécessaires doivent être envoyées à chaque requête (notamment le token d'authentification).
- **Ressources** : tout est pensé en termes de "ressources" (un étudiant, un cours). Chaque ressource est accessible via une URL unique.
- **Méthodes HTTP** : on utilise les verbes HTTP pour exprimer l'action à effectuer :
  - `GET` → lire/consulter
  - `POST` → créer
  - `PUT/PATCH` → modifier
  - `DELETE` → supprimer
- **Format JSON** : les données sont échangées au format JSON (*JavaScript Object Notation*), lisible et universel.

**Exemple concret :**

```
GET    /api/v1/etudiants       → récupérer la liste des étudiants
POST   /api/v1/etudiants       → créer un nouvel étudiant
GET    /api/v1/etudiants/5     → récupérer l'étudiant avec l'id 5
PATCH  /api/v1/etudiants/5     → modifier l'étudiant avec l'id 5
DELETE /api/v1/etudiants/5     → supprimer l'étudiant avec l'id 5
```

---

### 2.2 Pourquoi Laravel ?

**Laravel** est un framework PHP open-source créé par Taylor Otwell en 2011. C'est aujourd'hui l'un des frameworks PHP les plus populaires au monde. Il est choisi ici pour plusieurs raisons :

- **Eloquent ORM** : un système élégant pour interagir avec la base de données sans écrire de SQL brut. On manipule des objets PHP et Laravel traduit ça en requêtes SQL.
- **Artisan** : un outil en ligne de commande intégré qui automatise les tâches répétitives (créer des fichiers, lancer des migrations, exécuter des tests...).
- **Migrations** : un système de versioning de la base de données. Chaque changement de structure est écrit dans un fichier PHP et peut être rejoué sur n'importe quelle machine.
- **Écosystème riche** : Sanctum, Telescope, Horizon... Des packages officiels couvrent la plupart des besoins courants.
- **Tests intégrés** : Laravel est livré avec PHPUnit et propose une couche de tests HTTP très expressive.

---

### 2.3 Laravel Sanctum — Authentification par token

#### Le problème à résoudre

Notre API est publiquement accessible sur Internet. Sans protection, n'importe qui pourrait lire, modifier ou supprimer les données de l'école. Il faut donc un mécanisme pour **identifier et authentifier** les utilisateurs qui font des requêtes.

#### Pourquoi pas les sessions ?

Dans une application web classique, on utilise des **sessions** : l'utilisateur se connecte, le serveur crée une session et envoie un cookie au navigateur. Ce système fonctionne bien pour les navigateurs mais pose problème pour une API car :
- Les applications mobiles ne gèrent pas bien les cookies
- Les APIs sont souvent consommées par d'autres serveurs (pas de navigateur)
- Les sessions ne s'adaptent pas bien à une architecture distribuée

#### La solution : les tokens

**Sanctum** est le package officiel de Laravel pour l'authentification par **token API**. Le fonctionnement est simple :

```
1. L'utilisateur envoie son email + mot de passe
         ↓
2. Le serveur vérifie les identifiants
         ↓
3. Le serveur génère un token unique et le retourne
         ↓
4. L'utilisateur stocke ce token
         ↓
5. Pour chaque requête suivante, l'utilisateur envoie le token
   dans le header : Authorization: Bearer <token>
         ↓
6. Le serveur vérifie le token et traite la requête
```

Le token est stocké en base de données dans la table `personal_access_tokens`. Sans token valide, le serveur répond avec un **401 Unauthorized**.

#### Pourquoi Sanctum et pas Passport ?

Laravel propose deux packages d'authentification :
- **Passport** : implémente OAuth 2.0 complet — puissant mais complexe, adapté aux APIs publiques avec des clients tiers.
- **Sanctum** : plus léger et plus simple — parfait pour des SPAs, des applications mobiles et des APIs internes comme la nôtre.

---

### 2.4 Le Rate Limiting (limitation de débit)

#### Le problème à résoudre

Même avec l'authentification, une API peut être victime de deux types d'attaques :

- **Brute force** : un attaquant essaie des milliers de combinaisons email/mot de passe pour trouver les bons identifiants
- **DDoS** (*Denial of Service*) : un attaquant envoie un nombre massif de requêtes pour surcharger le serveur

#### La solution

Le **rate limiting** consiste à **limiter le nombre de requêtes** qu'un utilisateur ou une IP peut envoyer dans un intervalle de temps donné.

Dans ce projet, on configure :
```
60 requêtes par minute par utilisateur (ou par IP pour les non-connectés)
```

Si un utilisateur dépasse cette limite, le serveur répond avec le statut HTTP **429 Too Many Requests** et le client doit attendre avant de réessayer.

---

### 2.5 Les API Resources

#### Le problème à résoudre

Quand Eloquent récupère un modèle en base de données, il retourne **tous** les champs, y compris des champs sensibles qu'on ne veut pas exposer (mot de passe, tokens internes, etc.).

#### La solution

Les **API Resources** sont des classes de transformation. Elles définissent exactement quels champs sont exposés et comment ils sont formatés.

**Sans API Resource (dangereux) :**
```json
{
  "id": 1,
  "prenom": "Amadou",
  "password": "$2y$12$abc...",
  "remember_token": "xyz...",
  "created_at": "2026-02-25T10:00:00.000000Z"
}
```

**Avec API Resource (propre et sécurisé) :**
```json
{
  "data": {
    "id": 1,
    "prenom": "Amadou",
    "nom": "Diallo",
    "email": "amadou@ecole.sn",
    "date_naissance": "2000-03-15"
  }
}
```

#### Le paramètre `?include=`

Les relations (cours d'un étudiant, étudiants d'un cours) ne sont incluses dans la réponse **que si** le paramètre `?include=` est présent dans l'URL. Cela évite de surcharger les réponses inutilement.

```
GET /api/v1/etudiants/1              → retourne l'étudiant sans ses cours
GET /api/v1/etudiants/1?include=cours → retourne l'étudiant AVEC ses cours
```

---

### 2.6 Les Form Requests — Validation

Avant d'enregistrer quoi que ce soit en base de données, on doit **valider** les données envoyées. Les Form Requests sont des classes dédiées à ça.

Si la validation échoue, Laravel retourne automatiquement **422 Unprocessable Entity** :

```json
{
  "message": "Les données envoyées sont invalides.",
  "errors": {
    "email": ["L'email est obligatoire."],
    "date_naissance": ["La date de naissance doit être une date valide."]
  }
}
```

#### La règle `sometimes`

Pour les modifications partielles (PATCH), on utilise la règle `sometimes` qui signifie : "valider ce champ **uniquement s'il est présent** dans la requête". Cela permet de modifier un seul champ sans envoyer tous les autres.

---

### 2.7 La relation Many-to-Many

#### Définition

Une relation **Many-to-Many** (plusieurs-à-plusieurs) signifie que :
- Un étudiant peut être inscrit à **plusieurs** cours
- Un cours peut contenir **plusieurs** étudiants

#### La table pivot `cours_etudiant`

Cette relation nécessite une **table pivot** qui fait le lien entre les deux entités :

| etudiant_id | cours_id |
|-------------|----------|
| 1           | 1        |
| 1           | 2        |
| 2           | 1        |
| 3           | 2        |

L'étudiant 1 est inscrit aux cours 1 et 2, l'étudiant 2 au cours 1, etc.

#### Les trois méthodes de gestion

- **`attach`** : ajoute des cours **sans supprimer** les cours existants
- **`detach`** : retire des cours spécifiques
- **`sync`** : **remplace toute la liste** — les cours non mentionnés sont automatiquement retirés

---

### 2.8 Le versioning d'API

Toutes les routes sont préfixées par `/api/v1`. Le versioning permet de faire évoluer l'API sans casser les applications existantes. Quand des changements majeurs sont nécessaires, on crée `/api/v2` en maintenant `/api/v1` opérationnelle.

---

### 2.9 Les statuts HTTP

| Code | Nom | Utilisé quand |
|------|-----|---------------|
| 200  | OK | Requête réussie (GET, PUT, PATCH) |
| 201  | Created | Ressource créée avec succès (POST) |
| 204  | No Content | Suppression réussie (DELETE) |
| 401  | Unauthorized | Token manquant ou invalide |
| 403  | Forbidden | Token valide mais droits insuffisants |
| 404  | Not Found | Ressource introuvable |
| 422  | Unprocessable Entity | Données invalides |
| 429  | Too Many Requests | Limite de requêtes dépassée |

---

## 3. Architecture du projet

```
gestion-ecole/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── AuthController.php
│   │   │           ├── EtudiantController.php
│   │   │           └── CoursController.php
│   │   ├── Middleware/
│   │   │   └── Authenticate.php
│   │   ├── Requests/
│   │   │   ├── StoreEtudiantRequest.php
│   │   │   ├── UpdateEtudiantRequest.php
│   │   │   ├── StoreCoursRequest.php
│   │   │   └── UpdateCoursRequest.php
│   │   └── Resources/
│   │       ├── EtudiantResource.php
│   │       ├── EtudiantCollection.php
│   │       ├── CoursResource.php
│   │       └── CoursCollection.php
│   └── Models/
│       ├── User.php
│       ├── Etudiant.php
│       └── Cours.php
├── database/
│   ├── factories/
│   │   ├── EtudiantFactory.php
│   │   └── CoursFactory.php
│   └── migrations/
│       ├── xxxx_create_users_table.php
│       ├── xxxx_create_etudiants_table.php
│       ├── xxxx_create_cours_table.php
│       └── xxxx_create_cours_etudiant_table.php
├── routes/
│   └── api.php
├── tests/
│   └── Feature/
│       ├── AuthTest.php
│       ├── EtudiantTest.php
│       └── CoursTest.php
├── postman_collection.json
├── postman_environment.json
└── README.md
```

---

## 4. Installation et configuration

### Prérequis

- PHP >= 8.2
- Composer
- MySQL (ou MariaDB)
- Postman (pour tester l'API)

### Étapes d'installation

**1. Cloner le projet**
```bash
git clone <url-du-repo>
cd gestion-ecole
```

**2. Installer les dépendances PHP**
```bash
composer install
```

**3. Copier le fichier d'environnement**
```bash
cp .env.example .env
```

**4. Générer la clé d'application**

La clé d'application est utilisée par Laravel pour chiffrer les données sensibles. Elle doit être unique et secrète.
```bash
php artisan key:generate
```

**5. Configurer la base de données dans `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_ecole
DB_USERNAME=root
DB_PASSWORD=
```

**6. Créer la base de données**
```sql
CREATE DATABASE gestion_ecole CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**7. Lancer les migrations**

Les migrations créent automatiquement toutes les tables nécessaires :
```bash
php artisan migrate
```

Les tables créées sont :
- `users` — les utilisateurs de l'API
- `personal_access_tokens` — les tokens Sanctum
- `etudiants` — les étudiants
- `cours` — les cours
- `cours_etudiant` — la table pivot Many-to-Many

---

## 5. Lancer l'API

```bash
php artisan serve
```

L'API est accessible à : `http://127.0.0.1:8000/api/v1`

Test rapide pour vérifier que le serveur tourne :
```
GET http://127.0.0.1:8000/api/ping
→ { "status": "ok" }
```

---

## 6. Endpoints disponibles

### Authentification (routes publiques — sans token)

| Méthode | URI | Description |
|---------|-----|-------------|
| POST | `/api/v1/auth/register` | Créer un compte utilisateur |
| POST | `/api/v1/auth/login` | Se connecter et obtenir un token |
| POST | `/api/v1/auth/logout` | Se déconnecter (token requis) |
| GET  | `/api/v1/auth/me` | Voir son profil (token requis) |

### Étudiants (routes protégées — token requis)

| Méthode | URI | Description |
|---------|-----|-------------|
| GET    | `/api/v1/etudiants` | Lister tous les étudiants (paginé) |
| POST   | `/api/v1/etudiants` | Créer un étudiant |
| GET    | `/api/v1/etudiants/{id}` | Détails d'un étudiant |
| PUT    | `/api/v1/etudiants/{id}` | Modifier un étudiant (tous les champs) |
| PATCH  | `/api/v1/etudiants/{id}` | Modifier un étudiant (champs partiels) |
| DELETE | `/api/v1/etudiants/{id}` | Supprimer un étudiant (retour 204) |

### Cours (routes protégées — token requis)

| Méthode | URI | Description |
|---------|-----|-------------|
| GET    | `/api/v1/cours` | Lister tous les cours (paginé) |
| POST   | `/api/v1/cours` | Créer un cours |
| GET    | `/api/v1/cours/{id}` | Détails d'un cours |
| PUT    | `/api/v1/cours/{id}` | Modifier un cours |
| PATCH  | `/api/v1/cours/{id}` | Modifier un cours (champs partiels) |
| DELETE | `/api/v1/cours/{id}` | Supprimer un cours (retour 204) |

### Inscriptions Many-to-Many (routes protégées — token requis)

| Méthode | URI | Description |
|---------|-----|-------------|
| POST | `/api/v1/etudiants/{id}/cours/attach` | Inscrire un étudiant à des cours |
| POST | `/api/v1/etudiants/{id}/cours/detach` | Désinscrire un étudiant de cours |
| POST | `/api/v1/etudiants/{id}/cours/sync` | Remplacer toute la liste des cours |

### Query params disponibles

| Paramètre | Applicable sur | Description |
|-----------|---------------|-------------|
| `?page=2` | Listes | Numéro de page |
| `?per_page=5` | Listes | Nombre d'éléments par page |
| `?include=cours` | GET /etudiants | Inclure les cours dans la réponse |
| `?include=etudiants` | GET /cours | Inclure les étudiants dans la réponse |
| `?q=Mariama` | GET /etudiants | Recherche sur nom, prénom ou email |
| `?professeur=sene` | GET /cours | Filtre par nom de professeur |

---

## 7. Exemples d'appels API

### S'inscrire

```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Admin Ecole",
  "email": "admin@ecole.sn",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Réponse (201) :**
```json
{
  "message": "Compte créé avec succès",
  "token": "1|abc123xyz456...",
  "token_type": "Bearer"
}
```

---

### Se connecter

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@ecole.sn",
  "password": "password123"
}
```

**Réponse (200) :**
```json
{
  "message": "Connexion réussie",
  "token": "2|def456uvw...",
  "token_type": "Bearer"
}
```

---

### Créer un étudiant

```http
POST /api/v1/etudiants
Authorization: Bearer 2|def456uvw...
Content-Type: application/json

{
  "prenom": "Mariama",
  "nom": "Sane",
  "email": "mariama@ecole.sn",
  "date_naissance": "2000-03-15"
}
```

**Réponse (201) :**
```json
{
  "data": {
    "id": 1,
    "prenom": "Mariama",
    "nom": "Sane",
    "email": "mariama@ecole.sn",
    "date_naissance": "2000-03-15"
  }
}
```

---

### Lister les étudiants avec leurs cours

```http
GET /api/v1/etudiants?include=cours&per_page=5
Authorization: Bearer 2|def456uvw...
```

**Réponse (200) :**
```json
{
  "data": [
    {
      "id": 1,
      "prenom": "Mariama",
      "nom": "Sane",
      "email": "mariama@ecole.sn",
      "date_naissance": "2000-03-15",
      "cours": [
        {
          "id": 1,
          "libelle": "Algorithmique",
          "professeur": "M. Sène",
          "volume_horaire": 40
        }
      ]
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/etudiants?page=1",
    "last": "http://127.0.0.1:8000/api/v1/etudiants?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "per_page": 5,
    "total": 1
  }
}
```

---

### Inscrire un étudiant à des cours

```http
POST /api/v1/etudiants/1/cours/attach
Authorization: Bearer 2|def456uvw...
Content-Type: application/json

{
  "cours_ids": [1, 2, 3]
}
```

**Réponse (200) :**
```json
{
  "message": "Cours ajoutés avec succès.",
  "data": { ... }
}
```

---

### Sans token — accès refusé

```http
GET /api/v1/etudiants
```

**Réponse (401) :**
```json
{
  "message": "Non authentifié. Veuillez fournir un token valide."
}
```

---

## 8. Tester avec Postman

### Qu'est-ce que Postman ?

**Postman** est un outil qui permet de tester des APIs REST sans avoir besoin d'écrire du code frontend. Il permet d'envoyer des requêtes HTTP (GET, POST, PATCH, DELETE...) et de visualiser les réponses JSON. C'est l'outil standard utilisé par les développeurs backend pour tester et documenter leurs APIs.

### Importer la collection et l'environnement

Le projet fournit deux fichiers Postman prêts à l'emploi :
- `API Gestion Ecole postman_collection.json` — toutes les requêtes organisées en dossiers
- `Gestion Ecole Local postman_environment.json` — les variables d'environnement

**Pour importer dans Postman :**
1. Ouvre Postman
2. Clique sur **Import** en haut à gauche
3. Glisse-dépose les deux fichiers `.json`
4. Sélectionne l'environnement `Gestion Ecole Local` dans le menu déroulant en haut à droite

### Les variables d'environnement

Les variables permettent de ne pas répéter les mêmes valeurs dans chaque requête. Elles sont automatiquement mises à jour par les scripts :

| Variable | Rôle | Mise à jour par |
|----------|------|----------------|
| `base_url` | URL de base de l'API | Manuel (déjà configuré) |
| `token` | Token d'authentification | Script de la requête **Login** |
| `etudiant_id` | ID de l'étudiant courant | Script de la requête **Créer un étudiant** |
| `cours_id` | ID du cours courant | Script de la requête **Créer un cours** |

### Les scripts de test automatiques

Chaque requête contient des **scripts Post-response** qui s'exécutent automatiquement après chaque réponse. Ils vérifient :
- Le **code HTTP** attendu (200, 201, 204, 401...)
- La **structure JSON** de la réponse (présence des champs obligatoires)
- La **valeur** de certains champs

Exemple de script sur la requête Login :
```javascript
// Vérifie que le statut est 200
pm.test("Status 200 OK", function () {
    pm.response.to.have.status(200);
});

// Vérifie que le token est présent
pm.test("Réponse contient un token", function () {
    const json = pm.response.json();
    pm.expect(json).to.have.property("token");
});

// Sauvegarde automatique du token pour les requêtes suivantes
const json = pm.response.json();
pm.environment.set("token", json.token);
```

### Structure de la collection

La collection est organisée en 4 dossiers :

```
API Gestion Ecole/
├── Auth/
│   ├── Register
│   ├── Login             ← sauvegarde automatique du {{token}}
│   ├── Me
│   ├── Me sans token     ← vérifie le 401
│   └── Logout
├── Cours/
│   ├── Créer un cours    ← sauvegarde automatique du {{cours_id}}
│   ├── Lister les cours
│   ├── Afficher un cours
│   ├── Modifier un cours
│   └── Supprimer un cours
├── Etudiants/
│   ├── Créer un étudiant ← sauvegarde automatique du {{etudiant_id}}
│   ├── Lister les étudiants
│   ├── Afficher un étudiant
│   ├── Modifier un étudiant
│   └── Supprimer un étudiant
└── Many-to-Many/
    ├── Attach            ← inscrit l'étudiant au cours
    ├── Etudiant avec cours ← vérifie l'inclusion des cours
    ├── Sync              ← remplace les cours
    └── Detach            ← désinscrit l'étudiant
```

### Scénario de test complet (ordre recommandé)

Pour tester tout le scénario correctement, respecte cet ordre :

```
1.  Register          → crée un compte (201)
2.  Login             → obtient un token (200) → {{token}} sauvegardé
3.  Me                → vérifie le profil (200)
4.  Me sans token     → vérifie le 401
5.  Créer un cours    → (201) → {{cours_id}} sauvegardé
6.  Lister les cours  → (200)
7.  Afficher un cours → (200)
8.  Modifier un cours → (200)
9.  Créer un étudiant → (201) → {{etudiant_id}} sauvegardé
10. Lister les étudiants → (200)
11. Afficher un étudiant → (200)
12. Modifier un étudiant → (200)
13. Attach            → inscrit l'étudiant au cours (200)
14. Etudiant avec cours → vérifie l'inscription (200)
15. Sync              → remplace les cours (200)
16. Detach            → désinscrit l'étudiant (200)
17. Supprimer un étudiant → (204)
18. Supprimer un cours → (204)
19. Logout            → déconnexion (200)
```

> **IMPORTANT** : les requêtes **Supprimer** effacent les données. Si tu veux retester les requêtes Many-to-Many après une suppression, relance d'abord **Créer un cours** et **Créer un étudiant** pour régénérer les IDs.

### Lancer toute la collection automatiquement avec le Collection Runner

Postman permet de lancer toutes les requêtes dans l'ordre automatiquement :

1. Clique sur les **trois points (...)** à côté de la collection `API Gestion Ecole`
2. Clique sur **Run collection**
3. Vérifie que toutes les requêtes sont cochées et dans le bon ordre
4. Clique **Run API Gestion Ecole**
5. Postman exécute toutes les requêtes et affiche un rapport avec le résultat de chaque test

---

## 9. Tests automatisés Laravel

### Qu'est-ce qu'un Feature Test ?

Un **Feature Test** simule de vraies requêtes HTTP vers l'API et vérifie que les réponses sont correctes. Contrairement aux tests manuels Postman, ces tests sont automatisés et peuvent être lancés en une seule commande à tout moment.

### La base de données de test

Les tests utilisent une **base de données SQLite en mémoire** (`:memory:`) configurée dans `phpunit.xml`. Cela signifie que :
- Une base de données temporaire est créée au début des tests
- Elle est automatiquement détruite à la fin
- **Aucune donnée de test ne touche la vraie base de données MySQL**

C'est pourquoi on peut lancer les tests autant de fois qu'on veut sans craindre de polluer les données de développement.

### Lancer tous les tests

```bash
php artisan test
```

### Lancer un fichier de test spécifique

```bash
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/EtudiantTest.php
php artisan test tests/Feature/CoursTest.php
```

### Lancer un test spécifique

```bash
php artisan test --filter test_peut_creer_un_etudiant
```

### Résultat attendu

```
PASS  Tests\Feature\AuthTest
✓ un utilisateur peut sinscrire
✓ un utilisateur peut se connecter
✓ acces sans token retourne 401
✓ utilisateur connecte peut voir son profil
✓ utilisateur connecte peut se deconnecter

PASS  Tests\Feature\EtudiantTest
✓ acces sans token retourne 401
✓ peut lister les etudiants
✓ peut creer un etudiant
✓ creation etudiant echoue sans email
✓ peut afficher un etudiant
✓ retourne 404 si etudiant introuvable
✓ peut modifier un etudiant
✓ peut supprimer un etudiant
✓ peut attacher des cours a un etudiant
✓ peut synchroniser les cours dun etudiant

PASS  Tests\Feature\CoursTest
✓ acces sans token retourne 401
✓ peut lister les cours
✓ peut creer un cours
✓ creation cours echoue avec volume horaire invalide
✓ peut afficher un cours
✓ peut modifier un cours
✓ peut supprimer un cours

Tests: 22 passed
```

### Les assertions utilisées

| Assertion | Ce qu'elle vérifie |
|-----------|-------------------|
| `assertStatus(201)` | Le code HTTP retourné |
| `assertJsonStructure([...])` | La présence de certains champs dans la réponse |
| `assertJsonPath('data.prenom', 'Amadou')` | La valeur exacte d'un champ |
| `assertDatabaseHas('etudiants', [...])` | La présence d'un enregistrement en base |
| `assertDatabaseMissing('etudiants', [...])` | L'absence d'un enregistrement en base |
| `assertJsonValidationErrors(['email'])` | La présence d'erreurs de validation |

---

## 10. Références officielles

| Sujet | Lien |
|-------|------|
| API Resources | https://laravel.com/docs/12.x/eloquent-resources |
| Tests HTTP | https://laravel.com/docs/12.x/testing |
| Authentification | https://laravel.com/docs/12.x/authentication |
| Middleware | https://laravel.com/docs/12.x/middleware |
| Routing & Rate Limiting | https://laravel.com/docs/12.x/routing |
| Sanctum | https://laravel.com/docs/11.x/sanctum |
| Validation | https://laravel.com/docs/12.x/validation |
| Eloquent Relations | https://laravel.com/docs/12.x/eloquent-relationships |

---


