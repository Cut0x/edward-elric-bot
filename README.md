# Edward Elric Bot

Projet de collection de cartes Fullmetal Alchemist avec site web PHP, base MySQL et deux bots Discord.

## Structure

```text
bots/
  edward/      Bot principal. Gère les commandes slash et les rolls Discord.
  alphonse/    Bot d'activité. Publie les évènements du site sur le serveur support.
public/        Site web PHP.
cards/         Images des cartes validées.
icons/         Icônes utilisées par le site.
badges/        Badges de profil.
migrations/    Migrations SQL incrémentales.
database.sql   Schéma complet pour une installation neuve.
```

Le dossier `bot/` ne contient plus que des wrappers de compatibilité vers `bots/edward`.

## Prérequis

- Node.js 20 ou plus récent
- PHP 8.1 ou plus récent
- MySQL ou MariaDB
- Une application Discord pour Edward
- Une application Discord séparée pour Alphonse si vous voulez publier l'activité du site

## Installation

1. Installer les dépendances Node :

```bash
npm install
```

2. Copier la configuration :

```bash
cp exemple.env .env
```

3. Remplir `.env`.

Edward utilise les commandes slash. Alphonse utilise uniquement les salons configurés du serveur support.

4. Créer la base :

```bash
mysql -u root < database.sql
```

Pour une base existante, exécuter les migrations dans l'ordre :

```bash
mysql -u root edwardbot < migrations/2026_04_23_001_roll_xp_and_profile.sql
mysql -u root edwardbot < migrations/2026_04_23_002_public_collections_roles.sql
mysql -u root edwardbot < migrations/2026_04_23_003_community_forum.sql
mysql -u root edwardbot < migrations/2026_04_23_004_alphonse_activity_events.sql
mysql -u root edwardbot < migrations/2026_04_23_005_nested_forum_replies.sql
mysql -u root edwardbot < migrations/2026_04_23_006_reports_bans_proposal_actions.sql
```

## Lancer les bots

Edward :

```bash
npm run start:edward
```

Alphonse :

```bash
npm run start:alphonse
```

Les deux :

```bash
npm run start:bots
```

## Commandes slash et badge Discord

Pour que le profil du bot Edward affiche le badge Discord "Supports Commands" / "Prend en charge les commandes", des commandes slash globales doivent être enregistrées sur l'application Discord d'Edward.

Déploiement dans un serveur de test :

```bash
npm run deploy:edward
```

Déploiement global, requis pour le badge :

```bash
npm run deploy:edward:global
```

Discord peut mettre du temps à afficher les commandes globales et le badge.

## Alphonse

Alphonse lit la table `activity_events` et publie les évènements non livrés dans Discord.

Evènements actuellement envoyés :

- `legendary_roll` : un joueur roll une carte légendaire
- `first_card` : un joueur obtient sa première carte
- `collection_complete` : un joueur possède toutes les cartes
- `community_post` : une nouvelle proposition est publiée sur le forum communautaire
- `community_reply` : une réponse est publiée sur une proposition
- `report` : un post ou une réponse est signalé

Chaque type peut avoir son propre salon via `.env`. Si un salon spécifique n'est pas configuré, Alphonse utilise `ALPHONSE_CHANNEL_ACTIVITY_ID`.

Les reports sont envoyés dans `ALPHONSE_CHANNEL_REPORTS_ID` avec des boutons de modération Discord. Un administrateur du serveur peut supprimer le contenu, supprimer et bannir l'utilisateur du site et du bot, ou ignorer le report.

Les propositions communautaires envoyées dans `ALPHONSE_CHANNEL_COMMUNITY_POST_ID` affichent l'image jointe quand elle existe. Le bouton "Ajouter en brouillon" crée une carte inactive, clôture la proposition, puis renvoie vers l'édition admin.

## Site web

Le site se trouve dans `public/`.

Pages principales :

- `index.php`
- `cards.php`
- `card.php`
- `collection.php`
- `user.php`
- `users.php`
- `roll.php`
- `community.php`
- `proposal.php`
- `admin/`

## Contributions

1. Créer une branche dédiée.
2. Garder les changements ciblés.
3. Ajouter ou modifier les migrations SQL si le schéma change.
4. Vérifier les fichiers PHP modifiés avec `php -l`.
5. Vérifier les fichiers JavaScript modifiés avec `node --check`.
6. Ne pas commiter `.env`, tokens Discord, dumps SQL privés ou images temporaires.

## Conventions

- Les images de cartes doivent respecter un ratio 2:3, idéalement 1000 x 1500 px.
- Les nouvelles pages PHP doivent inclure `config.php`, `db.php`, `auth.php` et `functions.php` si elles utilisent la session, la base ou les helpers.
- Les commandes Discord d'Edward doivent utiliser Components V2.
- Les évènements destinés à Alphonse doivent passer par la table `activity_events`.
