# aidzap.com – Projektstruktur & Setup

## Stack
- PHP 8.3 (kein Framework)
- MySQL 8.x
- Apache (Hetzner Managed Server)
- Vanilla JS (kein jQuery-Pflicht)
- CSS Custom Properties (kein Tailwind)

---

## Ordnerstruktur

```
/var/www/aidzap.com/
│
├── public/                        ← Document Root (Apache zeigt hierhin)
│   ├── index.php                  ← Einstiegspunkt / Router
│   ├── .htaccess                  ← URL-Rewriting
│   ├── assets/
│   │   ├── css/
│   │   │   ├── main.css           ← Globale Styles
│   │   │   ├── dashboard.css      ← Dashboard-Styles
│   │   │   └── auth.css           ← Login/Register-Styles
│   │   ├── js/
│   │   │   ├── main.js
│   │   │   └── dashboard.js
│   │   └── img/
│   │       └── logo.svg
│   └── ad/
│       └── serve.php              ← Ad-Auslieferungs-Endpoint (öffentlich)
│
├── app/                           ← Anwendungslogik (NICHT öffentlich)
│   ├── Core/
│   │   ├── Router.php             ← URL → Controller Mapping
│   │   ├── Database.php           ← PDO Singleton
│   │   ├── Request.php            ← $_GET/$_POST Wrapper
│   │   ├── Response.php           ← Header/Redirect Helpers
│   │   ├── Session.php            ← Session-Verwaltung
│   │   ├── Auth.php               ← Login-Check, Token-Verwaltung
│   │   └── View.php               ← Template-Renderer
│   │
│   ├── Controllers/
│   │   ├── HomeController.php     ← Landingpage
│   │   ├── AuthController.php     ← Register / Login / Logout
│   │   ├── PublisherController.php
│   │   ├── AdvertiserController.php
│   │   ├── CampaignController.php
│   │   ├── AdController.php       ← Banner-Verwaltung
│   │   ├── PaymentController.php  ← Krypto-Zahlungen
│   │   └── ApiController.php      ← Interne API-Endpunkte
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Campaign.php
│   │   ├── AdUnit.php             ← Publisher Ad-Slots
│   │   ├── AdBanner.php           ← Advertiser Banner-Creatives
│   │   ├── Impression.php
│   │   ├── Click.php
│   │   ├── Payment.php
│   │   └── Fraud.php              ← Bot-Detection Logs
│   │
│   ├── Services/
│   │   ├── CryptoPaymentService.php   ← BTC/Krypto Zahlungsabwicklung
│   │   ├── FraudDetectionService.php  ← KI-gestützte Bot-Erkennung
│   │   ├── AdServeService.php         ← Welcher Banner wird ausgeliefert?
│   │   ├── BillingService.php         ← CPD/CPM/CPA Abrechnung
│   │   └── MailService.php            ← Optionale Benachrichtigungen
│   │
│   └── Views/
│       ├── layouts/
│       │   ├── main.php           ← Haupt-Layout (Header/Footer)
│       │   ├── dashboard.php      ← Dashboard-Layout
│       │   └── auth.php           ← Auth-Layout (clean, minimal)
│       ├── home/
│       │   └── index.php          ← Landingpage
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── publisher/
│       │   ├── dashboard.php
│       │   ├── units.php          ← Ad-Units verwalten
│       │   └── earnings.php
│       ├── advertiser/
│       │   ├── dashboard.php
│       │   ├── campaigns.php
│       │   ├── create-campaign.php
│       │   └── billing.php
│       └── errors/
│           ├── 404.php
│           └── 500.php
│
├── config/
│   ├── app.php                    ← App-Konfiguration
│   ├── database.php               ← DB-Credentials
│   └── crypto.php                 ← API-Keys für Zahlungsanbieter
│
├── database/
│   ├── schema.sql                 ← Vollständiges DB-Schema
│   └── migrations/                ← Zukünftige Änderungen
│       └── 001_initial.sql
│
├── storage/
│   ├── logs/                      ← Error/Access Logs
│   │   └── .gitkeep
│   └── cache/                     ← Optionaler File-Cache
│       └── .gitkeep
│
├── tests/                         ← Unit/Integration Tests
│   └── .gitkeep
│
├── .htaccess                      ← Schützt app/ vor direktem Zugriff
├── .env.example                   ← Umgebungsvariablen Template
└── README.md
```

---

## Apache VirtualHost Konfiguration

```apache
<VirtualHost *:443>
    ServerName aidzap.com
    ServerAlias www.aidzap.com
    DocumentRoot /var/www/aidzap.com/public

    <Directory /var/www/aidzap.com/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Schützt app/, config/, database/, storage/ vor direktem Zugriff
    <Directory /var/www/aidzap.com/app>
        Require all denied
    </Directory>
    <Directory /var/www/aidzap.com/config>
        Require all denied
    </Directory>
    <Directory /var/www/aidzap.com/database>
        Require all denied
    </Directory>

    ErrorLog /var/www/aidzap.com/storage/logs/error.log
    CustomLog /var/www/aidzap.com/storage/logs/access.log combined
</VirtualHost>
```

---

## public/.htaccess (URL-Rewriting)

```apache
Options -MultiViews -Indexes
RewriteEngine On

# HTTPS erzwingen
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# www → non-www
RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
RewriteRule ^ https://%1%{REQUEST_URI} [R=301,L]

# Statische Dateien direkt ausliefern
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Alles andere → index.php (Front Controller)
RewriteRule ^ index.php [L]
```

---

## .env.example

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aidzap.com
APP_SECRET=CHANGE_THIS_TO_RANDOM_64_CHAR_STRING

DB_HOST=localhost
DB_PORT=3306
DB_NAME=aidzap
DB_USER=aidzap_user
DB_PASS=STRONG_PASSWORD_HERE

# Krypto-Zahlungen (z.B. NOWPayments API)
CRYPTO_PROVIDER=nowpayments
NOWPAYMENTS_API_KEY=
NOWPAYMENTS_IPN_SECRET=

# Bot-Detection Threshold (0.0 - 1.0)
FRAUD_SCORE_THRESHOLD=0.75

SESSION_LIFETIME=7200
SESSION_SECURE=true
```

---

## Setup-Befehle (Server)

```bash
# Verzeichnis anlegen
mkdir -p /var/www/aidzap.com/{public/assets/{css,js,img},public/ad,app/{Core,Controllers,Models,Services,Views/{layouts,home,auth,publisher,advertiser,errors}},config,database/migrations,storage/{logs,cache},tests}

# Berechtigungen
chown -R www-data:www-data /var/www/aidzap.com
chmod -R 755 /var/www/aidzap.com
chmod -R 775 /var/www/aidzap.com/storage

# .env erstellen
cp /var/www/aidzap.com/.env.example /var/www/aidzap.com/.env
```

---

## Nächste Schritte (Entwicklungsreihenfolge)

1. **Schritt 1** → `config/`, `app/Core/` (Database, Router, View, Auth)
2. **Schritt 2** → `database/schema.sql` – vollständiges Datenbankschema
3. **Schritt 3** → Landingpage (`HomeController` + View)
4. **Schritt 4** → Anonymes Auth-System (Register ohne KYC, Login)
5. **Schritt 5** → Publisher Dashboard (Ad-Units anlegen, Embed-Code)
6. **Schritt 6** → Advertiser Dashboard (Kampagnen, Banner-Upload, CPD/CPM)
7. **Schritt 7** → Ad-Serving Engine (`public/ad/serve.php`)
8. **Schritt 8** → Krypto-Zahlungen Integration
9. **Schritt 9** → Fraud Detection Service
```
