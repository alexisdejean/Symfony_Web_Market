# GlassNGo

Application de commerce en ligne consacrée aux lunettes et accessoires optiques. Le projet utilise Symfony pour gérer le catalogue, les comptes clients, les paniers et les outils d’administration.

## Fonctionnalités

- Consultation du catalogue, filtres par caractéristiques et pagination.
- Création de compte, connexion et paramètres de profil.
- Panier avec contrôle des quantités par rapport au stock disponible.
- Formulaire de contact et espace d’administration.
- Gestion des produits et des comptes réservée aux administrateurs.
- Téléversement d’images JPEG, PNG et WebP pour les produits.

## Prérequis

- PHP 8.2 ou supérieur, avec PDO MySQL et Fileinfo activés.
- Composer 2.
- MySQL ou MariaDB.

Les dépendances Symfony sont installées par Composer. Aucun outil JavaScript n’est nécessaire pour le démarrage décrit ci-dessous.

## Installation locale

Depuis le dossier du projet :

```powershell
composer install
```

Créer ou compléter `.env.local` à la racine. Ce fichier est ignoré par Git et doit contenir les paramètres propres à la machine :

```dotenv
APP_ENV=dev
APP_SECRET=remplacer-par-un-secret-aleatoire
DATABASE_URL="mysql://UTILISATEUR:MOT_DE_PASSE@127.0.0.1:3306/glassngo?serverVersion=mariadb-10.4.32&charset=utf8mb4"
MAILER_DSN=null://null
EMAIL_VERIFICATION_ENABLED=false
```

Remplacer les valeurs d’exemple par celles du serveur installé. `serverVersion` doit correspondre à la version réelle de MySQL ou MariaDB. Générer un secret avec :

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Créer la base si elle n’existe pas, puis appliquer les migrations :

```powershell
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Si la base existe déjà, passer la première commande. L’utilisateur MySQL configuré dans `DATABASE_URL` doit accéder à toutes les tables utilisées par l’application. Pour appliquer des migrations, il doit également avoir les privilèges de modification de schéma ; en production, un compte réservé aux migrations et un compte applicatif aux droits réduits sont préférables.

Démarrer le serveur PHP local :

```powershell
php -S 127.0.0.1:8000 -t public
```

Ouvrir ensuite <http://127.0.0.1:8000>.

## Accès et rôles

Les pages principales sont `/service` (catalogue), `/register` (inscription), `/login` (connexion), `/contact` et `/admin` (administration). Les fonctions d’administration nécessitent un compte ayant le rôle administrateur. Le projet ne fournit pas de procédure publique de création du premier administrateur : attribuer ce rôle uniquement par une procédure contrôlée et ne jamais exposer un formulaire public de promotion.

## Vérification des adresses email

La vérification est **désactivée par défaut** afin que l’inscription fonctionne sans serveur de messagerie. Dans ce mode, les nouveaux comptes peuvent se connecter immédiatement.

Pour l’activer lors d’un déploiement, définir les deux variables dans l’environnement du serveur :

```dotenv
EMAIL_VERIFICATION_ENABLED=true
MAILER_DSN=smtp://UTILISATEUR:MOT_DE_PASSE@smtp.example.com:587?encryption=tls&auth_mode=login
```

Avec cette option, les nouveaux comptes doivent confirmer leur adresse avant de se connecter. Les identifiants SMTP sont des secrets : les conserver dans l’environnement de déploiement ou un gestionnaire de secrets, jamais dans Git. Vérifier également l’adresse expéditrice `contact@glassngo.com` dans `RegistrationController` et la remplacer si nécessaire.

## Contrôles utiles

```powershell
php bin/console lint:container
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console doctrine:schema:validate --skip-sync
```

La dernière commande vérifie le mapping Doctrine, mais l’option `--skip-sync` ne compare pas le schéma réel de la base.

## Sécurité et déploiement

- Ne pas versionner `.env.local`, les mots de passe, les secrets ou les identifiants SMTP.
- Utiliser HTTPS en production ; les cookies de session sont configurés avec `HttpOnly`, `SameSite=Lax` et `Secure` automatique.
- Les opérations qui modifient des données utilisent des méthodes POST et des jetons CSRF.
- Les images téléversées sont limitées aux formats et tailles validés par l’application. Les fichiers sont stockés sous `public/uploads/products` ; les règles `.htaccess` ne s’appliquent que si le serveur web est Apache et autorise ces fichiers.
- Après une modification de configuration de production, reconstruire le cache :

```powershell
php bin/console cache:clear --env=prod
```
