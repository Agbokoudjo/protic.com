<div align="center">

<img src="public/assets/images/logo.png" alt="ProTIC Editions & Services" width="280"/>

# ProTIC Editions & Services

**Site officiel de la maison d'édition béninoise**

[![Symfony](https://img.shields.io/badge/Symfony-7.4_LTS-black?logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![React](https://img.shields.io/badge/React-18-61DAFB?logo=react)](https://react.dev)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?logo=postgresql)](https://postgresql.org)
[![FrankenPHP](https://img.shields.io/badge/FrankenPHP-latest-blue)](https://frankenphp.dev)
[![Version](https://img.shields.io/badge/version-v1.0.0-FFD700)](https://github.com/Agbokoudjo/protic.com/releases)
[![License](https://img.shields.io/badge/licence-Propriétaire-red)](./LICENSE)

🌐 **[protic.com](https://protic.com)** — Abomey-Calavi, Bénin 🇧🇯

</div>

---

## 📖 À propos du projet

**ProTIC Editions & Services** est une maison d'édition béninoise fondée en **2010** par
**M. SETONWAN DENIS HOUNGNIMON**, engagée pour la promotion du livre et des auteurs africains.

Ce dépôt contient le code source complet du site officiel de ProTIC, développé par
**[INTERNATIONALES WEB APPS & SERVICES](https://github.com/Agbokoudjo)** —
Franck AGBOKOUDJO, Cotonou, Bénin.

> *"Notre mission est de rendre la publication accessible à chaque auteur béninois
> et africain, quelle que soit son expérience ou sa localisation."*
> — M. SETONWAN DENIS HOUNGNIMON, Directeur & Fondateur

---

## ✨ Fonctionnalités

### Pages publiques
| Page | Description |
|---|---|
| **Accueil** | Hero Three.js, catalogue des dernières parutions (React), stats animées, genres littéraires, témoignages, CTA |
| **Catalogue** | Grille/liste de livres avec filtres (genre, auteur, année), recherche, pagination classique, modal commande |
| **Contact** | Formulaire de soumission de manuscrit (Symfony Form + VichUploader), upload fichier, emails automatiques |
| **À propos** | Histoire, valeurs, timeline 2010→2026, équipe, FAQ dynamique (React + API Platform) |

### Fonctionnalités techniques
- 🎨 **Design glassmorphism** — palette bleu profond `#0d1f4e` + or `#FFD700`
- ⚡ **React injecté dynamiquement** via `IntersectionObserver` (performance optimale)
- 🔍 **SEO complet** — Schema.org, Open Graph, balises canoniques, mots-clés longue traîne
- 📱 **Responsive** — Bootstrap 5.3.8, offcanvas mobile, grilles adaptatives
- 🌀 **Animations** — AOS.js + Three.js (particules hero) + CSS animations
- 📬 **Système email double** — SystemMailer + SupportMailer (2 DSN SMTP séparés)
- 🔒 **Sécurité** — CSRF, validation Symfony, SoftDelete, rate limiting
- 🗃️ **Admin** — Sonata Admin (interface d'administration complète)

---

## 🛠️ Stack technique

### Backend
| Technologie | Version | Usage |
|---|---|---|
| PHP | 8.3 | Langage serveur |
| Symfony | 7.4 LTS | Framework principal |
| FrankenPHP | latest stable | Serveur HTTP (remplace Nginx + PHP-FPM) |
| API Platform | 4.3 | API REST (livres, FAQ, soumissions) |
| Doctrine ORM | 3.x | ORM PostgreSQL |
| Sonata Admin | 5.x | Interface d'administration |
| VichUploaderBundle | latest | Upload fichiers manuscrits |
| GedmoDoctrineExtensions | latest | SoftDelete, Timestampable |
| libphonenumber | latest | Validation numéros de téléphone |
| Symfony Mailer | 7.x | Envoi emails (2 transports DSN) |
| Symfony Messenger | 7.x | File de messages asynchrones |

### Frontend
| Technologie | Version | Usage |
|---|---|---|
| React | 18 | Composants dynamiques (BooksGrid, FAQ, Footer) |
| Vite | 6 | Bundler assets |
| vite-plugin-symfony | latest | Intégration Vite ↔ Symfony |
| Bootstrap | 5.3.8 | Framework CSS responsive |
| Three.js | r128 | Particules 3D hero |
| AOS.js | next | Animations au scroll |
| Bootstrap Icons | 1.11.3 | Icônes vectorielles |

### Infrastructure
| Technologie | Version | Usage |
|---|---|---|
| PostgreSQL | 17+ | Base de données principale |
| Redis | 7.x | Sessions + cache + file de messages |
| Caddy | intégré FrankenPHP | Reverse proxy + SSL Let's Encrypt |

### Packages npm propriétaires (`@wlindabla`)
| Package | Version | Usage |
|---|---|---|
| `@wlindabla/http_client` | 1.1.0 | Client HTTP sécurisé (fetch wrapper) |
| `@wlindabla/form_validator` | 2.4.0 | Validation formulaires + URL params |

---

## 📁 Structure du projet

```
protic.com/
├── assets/                     # Sources frontend (Vite)
│   ├── images/                 # Images statiques
│   ├── js/                     # Points d'entrée JS par page
│   │   ├── home.js
│   │   ├── catalogue.js
│   │   ├── contact.js
│   │   └── about.js
│   ├── react/                  # Composants React
│   │   ├── controllers/
│   │   │   └── BooksGrid.jsx   # Grille livres (home + catalogue)
│   │   ├── FaqComponent.jsx    # FAQ dynamique
│   │   ├── FooterComponent.jsx # Footer React
│   │   ├── BookSummaryModal.jsx
│   │   ├── AuthorBioModal.jsx
│   │   └── ContactFormModal.jsx
│   └── styles/                 # CSS par page
│       ├── protic-layout.css   # Variables + header/nav/footer
│       ├── home.css
│       ├── catalogue.css
│       ├── contact.css
│       └── about.css
│
├── config/                     # Configuration Symfony
│   └── packages/
│       ├── mailer.yaml         # 2 transports SMTP
│       ├── pentatrion_vite.yaml
│       └── ...
│
├── public/                     # Racine web
│   ├── build/                  # Assets compilés par Vite (gitignore)
│   ├── uploads/                # Fichiers uploadés (gitignore)
│   └── index.php
│
├── src/
│   ├── Controller/
│   │   ├── HomeController.php
│   │   ├── AboutController.php
│   │   ├── ContactController.php
│   │   ├── Api/
│   │   │   └── BookApiController.php
│   │   └── ...
│   ├── Entity/
│   │   ├── Book.php
│   │   ├── Author.php
│   │   ├── Category.php
│   │   ├── Faq.php
│   │   ├── GlobalSetting.php
│   │   └── ManuscriptSubmission.php
│   ├── Form/
│   │   └── ManuscriptSubmissionType.php
│   ├── Repository/
│   ├── Service/
│   │   └── Mailing/
│   │       ├── MailerFactory.php
│   │       ├── SystemMailer.php
│   │       └── SupportMailer.php
│   └── Twig/
│       └── Components/
│           └── ContactInfo.php  # Twig Component coordonnées
│
├── templates/
│   ├── base.html.twig
│   ├── partials/
│   │   ├── _header.html.twig
│   │   ├── _nav.html.twig
│   │   └── _footer.html.twig
│   ├── home/
│   ├── catalogue/
│   ├── contact/
│   ├── about/
│   ├── emails/
│   │   ├── notification_submission.html.twig
│   │   └── confirmation_author.html.twig
│   └── components/
│       └── ContactInfo.html.twig
│
├── Caddyfile                   # Configuration FrankenPHP
├── vite.config.js
├── package.json
└── composer.json
```

---

## 🚀 Installation locale (développement)

### Prérequis
- PHP 8.3+
- Composer 2.x
- Node.js 22 LTS + Yarn 4
- PostgreSQL 17+
- Redis 7+
- FrankenPHP (optionnel en dev, Symfony CLI suffit)

### 1. Cloner le dépôt

```bash
git clone git@github.com:Agbokoudjo/protic.com.git
cd protic.com
```

### 2. Variables d'environnement

```bash
cp .env .env.local
# Éditer .env.local avec tes valeurs locales
nano .env.local
```

Variables minimales pour le dev :
```bash
APP_ENV=dev
APP_SECRET=une_chaine_aleatoire_32_caracteres
DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/protic_db?serverVersion=17"
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
SYSTEM_MAILER_DSN="smtp://user:pass@sandbox.smtp.mailtrap.io:2525"
SUPPORT_MAILER_DSN="smtp://user:pass@sandbox.smtp.mailtrap.io:2525"
APP_MAIL_FROM_SYSTEM="system@protic.com"
APP_MAIL_FROM_SYSTEM_NAME="ProTIC Editions — Système"
APP_MAIL_FROM_SUPPORT="direction@protic.com"
APP_MAIL_FROM_SUPPORT_NAME="Direction ProTIC Editions & Services"
```

### 3. Installer les dépendances

```bash
# PHP
composer install

# JavaScript
yarn install
```

### 4. Base de données

```bash
# Créer la BDD
php bin/console doctrine:database:create

# Migrations
php bin/console doctrine:migrations:migrate

# Fixtures (données de test)
php bin/console doctrine:fixtures:load --append
```

### 5. Lancer le serveur de développement

```bash
# Terminal 1 — Symfony
symfony server:start
# ou avec FrankenPHP :
# frankenphp run --config Caddyfile

# Terminal 2 — Vite (HMR React)
yarn dev
```

Ouvrir : **http://localhost:8000**

---

## 🏭 Déploiement production

### Prérequis VPS
- Ubuntu 24.04 LTS
- 2 vCPU / 8 Go RAM / 100 Go NVMe
- FrankenPHP installé
- PostgreSQL 17
- Redis 7

### Déployer

```bash
# Sur le VPS
cd /var/www/protic

# Pull dernière version
git pull origin main

# Build assets
yarn install --frozen-lockfile && yarn build

# Dépendances PHP prod
composer install --no-dev --optimize-autoloader

# Migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Cache
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup

# Restart FrankenPHP
sudo systemctl restart protic
```

---

## 📬 Configuration emails

Le projet utilise **deux DSN SMTP séparés** :

| Service | Variable | Usage |
|---|---|---|
| `SystemMailer` | `SYSTEM_MAILER_DSN` | Confirmations auteurs, notifications auto |
| `SupportMailer` | `SUPPORT_MAILER_DSN` | Alertes internes équipe ProTIC |

En production, configurer avec les comptes Gmail `system@protic.com`
et `direction@protic.com` (mots de passe d'application Google requis).

---

## 🌍 Informations légales ProTIC

| Registre | Numéro |
|---|---|
| RCCM | RB/ABC/21 A 32987 |
| IFU | 0202112604781 |
| CNSS | 21312177 |
| Compte UBA | 506070006684 |

**Adresse :** Campus d'Abomey-Calavi, K61-62 Rectorat annexe, Bénin  
**Téléphone :** +229 95 86 99 51  
**Email :** proticeditions@gmail.com  
**Membre :** APPEL-Bénin (Association Professionnelle des Éditeurs de Livres)

---

## 👨‍💻 Développeur

**Franck AGBOKOUDJO**  
Fondateur — INTERNATIONALES WEB APPS & SERVICES  
📍 Cotonou, Bénin  
📞 +229 01 67 25 18 86  
✉️ internationaleswebservices@gmail.com  
🔗 [LinkedIn](https://www.linkedin.com/in/internationales-web-apps-services-120520193/)  
🐙 [GitHub](https://github.com/Agbokoudjo)

---

## 📄 Licence

Ce projet est propriétaire. Tous droits réservés.  
© 2026 ProTIC Editions & Services — Développé par INTERNATIONALES WEB APPS & SERVICES.

Toute reproduction, distribution ou utilisation non autorisée est strictement interdite.