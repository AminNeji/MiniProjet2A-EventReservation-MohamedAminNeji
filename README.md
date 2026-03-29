# 🎟️ EventReservation — Application Web de Gestion de Réservations d'Événements

> Mini Projet · ING A2 · ISSAT Sousse · Année universitaire 2025-2026

![Symfony](https://img.shields.io/badge/Symfony-6.4-black?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-blue?logo=postgresql)
![Docker](https://img.shields.io/badge/Docker-ready-blue?logo=docker)
![JWT](https://img.shields.io/badge/Auth-JWT%20%2B%20Passkeys-gold)

---

## 📋 Description

Application web complète permettant :
- **Utilisateurs** : Consulter des événements, réserver des places en ligne
- **Administrateurs** : Gérer les événements (CRUD) et consulter les réservations
- **Sécurité renforcée** avec JWT (JSON Web Tokens) et Passkeys (WebAuthn/FIDO2)

---

## 🏗️ Technologies utilisées

| Technologie | Version | Rôle |
|---|---|---|
| PHP | 8.2 | Backend |
| Symfony | 6.4 LTS | Framework MVC |
| PostgreSQL | 15 | Base de données |
| LexikJWT | 2.x | Authentification JWT |
| WebAuthn | 4.x | Passkeys / FIDO2 |
| Docker + Docker Compose | latest | Conteneurisation |
| Twig | 3.x | Moteur de templates |
| Nginx | alpine | Serveur web |


## 🚀 Installation & Démarrage

### Prérequis
- Docker Desktop installé et lancé
- Git

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/AminNeji/MiniProjet2A-EventReservation-MohamedAminNeji.git
cd MiniProjet2A-EventReservation-MohamedAminNeji

# 2. Copier le fichier d'environnement
cp .env.local.example .env.local
# Éditez .env.local si besoin

# 3. Générer les clés JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 (skip if not working )
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout ( skip if not working ) ( its because i already did it in the project )
chmod 600 config/jwt/private.pem config/jwt/public.pem

# 4. Lancer Docker
docker-compose up -d --build

# 5. Installer les dépendances
docker exec event_php composer install

# 6. Créer la base et lancer les migrations
docker exec event_php php bin/console doctrine:migrations:migrate --no-interaction

# 7. Créer l'admin (une seule fois)
# Ouvrez : http://localhost:8082/admin/setup
# Identifiants : admin / Admin@1234

# 8. Ouvrir l'application
# http://localhost:8082
```

---

## 📁 Structure du projet

```
├── config/
│   ├── packages/         # Configuration Symfony
│   └── jwt/              # Clés RSA JWT 
├── docker/nginx/         # Config Nginx
├── migrations/           # Migrations Doctrine
├── public/
│   ├── css/              # Feuilles de style
│   ├── js/               # Scripts (auth.js, passkeys)
│   └── uploads/events/   # Images événements
├── src/
│   ├── Controller/       # Contrôleurs (Admin, Event, Auth, Security)
│   ├── Entity/           # Entités Doctrine (User, Admin, Event, Reservation, WebauthnCredential)
│   ├── Repository/       # Repositories
│   └── Service/          # Services métier
├── templates/
│   ├── admin/            # Templates admin
│   ├── event/            # Templates événements
│   ├── reservation/      # Templates réservations
│   └── security/         # Templates authentification
├── tests/                # Tests PHPUnit
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## 🔐 Authentification

### JWT (API REST)
- `POST /api/auth/register` — Inscription utilisateur
- `POST /api/auth/login` — Connexion → retourne un JWT
- `GET /api/auth/me` — Profil (token Bearer requis)

### Passkeys (WebAuthn)
- `POST /api/auth/passkey/register/options` — Options d'enregistrement
- `POST /api/auth/passkey/register/verify` — Vérification enregistrement
- `POST /api/auth/passkey/login/options` — Options de connexion
- `POST /api/auth/passkey/login/verify` — Vérification connexion

### Session (Web classique)
- `GET/POST /login` — Connexion utilisateur (formulaire)
- `GET/POST /register` — Inscription utilisateur
- `GET/POST /admin/login` — Connexion administrateur

---

## 🌐 Routes principales

| Route | Accès | Description |
|---|---|---|
| `/` | Public | Page d'accueil |
| `/events/` | Public | Liste des événements |
| `/events/{id}` | Public | Détail d'un événement |
| `/events/{id}/reserve` | Public | Formulaire de réservation |
| `/admin/` | ROLE_ADMIN | Dashboard admin |
| `/admin/events` | ROLE_ADMIN | Gestion des événements |
| `/admin/events/new` | ROLE_ADMIN | Créer un événement |
| `/admin/events/{id}/edit` | ROLE_ADMIN | Modifier un événement |
| `/admin/events/{id}/reservations` | ROLE_ADMIN | Réservations d'un événement |
| `/admin/reservations` | ROLE_ADMIN | Toutes les réservations |

---

## 🧪 Tests

```bash
# Lancer tous les tests
docker exec event_php php bin/phpunit

# Tests spécifiques
docker exec event_php php bin/phpunit --filter AuthApiControllerTest
docker exec event_php php bin/phpunit --filter EventControllerTest
```

---

## 🐳 Commandes Docker utiles

```bash
# Voir les logs
docker-compose logs -f

# Accéder au conteneur PHP
docker exec -it event_php bash

# Vider le cache Symfony
docker exec event_php php bin/console cache:clear

# Créer une nouvelle migration
docker exec event_php php bin/console doctrine:migrations:diff

# Appliquer les migrations
docker exec event_php php bin/console doctrine:migrations:migrate
```

