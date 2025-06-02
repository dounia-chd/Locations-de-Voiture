DrivUP est une application web de gestion de location de voitures développée avec le framework Symfony . Elle permet aux utilisateurs de visualiser les véhicules disponibles, réserver des locations avec ou sans inscription, gérer leur panier et accéder à un espace personnel. L’application intègre une réduction automatique si la flotte dépasse 10 véhicules.

Elle propose trois espaces distincts :

Client entreprise : tableau de bord avec statistiques de location, factures et historique.

Loueur : gestion des véhicules (ajout, modification, suppression), suivi des locations et factures par client.

Administrateur : gestion globale des utilisateurs, véhicules, emplacements et suivi des performances via un tableau de bord centralisé.

## Installation

### Pré-requis

- PHP >= 7.4
- Composer
- Symfony CLI
- Serveur MySQL

Cloner le repository

```
git clone --single-branch -b master https://github.com/Danny-7/Car-Rental-Management-System.git
```

Installer les dépendances nécessaires du projet

```
composer install
```

Configurer la connexion à la base de données dans le fichier `.env`

```
DATABASE_URL='mysql://<user><password>:@127.0.0.1:3306/<database>'
```

Remplacer :

- `<user>` par votre nom utilisateur
- `<password>` par votre mot de passe
- `<database>` par le nom de votre base de données

Créer la base de données

```
php bin/console d:d:c --if-not-exists
```

Importer le fichier `db.sql` dans votre base de données sur le serveur MySQL

Lancer l'application web

```
symfony server:start
```
## Pour se connecter
Côté Loueur: easyrent@easyrent.com
             Azerty123
Côté Client: hubert.pichet@gmail.com
            Azerty123




