# Guide d'installation 

## 1. Prérequis

Installez sur votre machine :
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (inclut Docker Compose)
- [Git](https://git-scm.com/)
- [Git Bash](https://gitforwindows.org/) (Windows uniquement)

---

## 2. Cloner et configurer

```bash
git clone https://github.com/AminNeji/MiniProjet2A-EventReservation-MohamedAminNeji.git
cd MiniProjet2A-EventReservation-MohamedAminNeji
cp .env.local.example .env.local
```

---

## 3. Générer les clés JWT 

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
# (saisir une passphrase — mettez-la dans .env.local -> JWT_PASSPHRASE)
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
chmod 600 config/jwt/private.pem config/jwt/public.pem
```

Puis mettre à jour `.env.local` :
```
JWT_PASSPHRASE=la_passphrase_que_vous_avez_saisie
```

---

## 4. Lancer Docker

```bash
docker-compose up -d --build
```

Attendez ~30 secondes que la base de données soit prête.

---

## 5. Installer les dépendances et migrer

```bash
docker exec event_php composer install
docker exec event_php php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 6. Créer l'administrateur

Ouvrez dans le navigateur :
```
http://localhost:8082/admin/setup
```
Cela crée l'admin avec : **username: admin / password: Admin@1234**

## 7. Tester l'application

| URL | Description |
|-----|-------------|
| http://localhost:8082 | Accueil |
| http://localhost:8082/events/ | Événements (5 exemples pré-chargés) |
| http://localhost:8082/register | Créer un compte utilisateur |
| http://localhost:8082/login | Connexion utilisateur |
| http://localhost:8082/admin/login | Connexion admin |

---

## 8. Lancer les tests

```bash
docker exec event_php php bin/phpunit
```

---

## Commandes utiles

```bash
# Voir les logs
docker logs event_php
docker logs event_nginx

# Console Symfony
docker exec event_php php bin/console cache:clear
docker exec event_php php bin/console debug:router

# Base de données
docker exec -it event_db psql -U app -d app
```
