# Deployment-Anleitung

## Schnell-Deployment (ZIP-Upload)

### Für Standard-PHP-Hosting (z.B. Hostinger, SiteGround, etc.)

1. **ZIP-Datei erstellen**:
   - Komprimiere den gesamten `Sportfest_deploy` Ordner zu einer ZIP-Datei
   - Oder nutze das Terminal:
     ```bash
     cd /Applications/XAMPP/xamppfiles/htdocs
     zip -r sportfest.zip Sportfest_deploy/
     ```

2. **Upload via FTP/SFTP oder Control Panel**:
   - Logge dich in dein Hosting Control Panel ein (z.B. cPanel, Plesk)
   - Navigiere zum Webroot (meist `public_html` oder `htdocs`)
   - Lade die ZIP-Datei hoch
   - Entpacke die Datei über das Control Panel

3. **Berechtigungen setzen**:
   - Über FTP-Client oder File Manager:
     - Ordner: `755`
     - PHP-Dateien: `644`
     - `sportfest.db`: `666`
     - Root-Ordner: `777`

4. **config.php anpassen**:
   ```php
   define('BASE_URL', '');  // Für Root-Installation
   // ODER
   define('BASE_URL', '/sportfest');  // Für Unterordner
   ```

5. **Testen**:
   - Öffne deine Domain im Browser
   - Login: `admin` / `admin123`
   - **WICHTIG**: Ändere sofort das Passwort!

---

## Alternative Hosting-Optionen

### 1. Vercel (mit Vercel PHP Runtime)

**Nicht empfohlen** - Vercel ist für Node.js/Next.js optimiert

### 2. Railway.app (Empfohlen!)

**Kostenlos für kleine Projekte**

1. Erstelle ein Railway-Konto: https://railway.app
2. Installiere Railway CLI:
   ```bash
   npm i -g @railway/cli
   ```
3. Login und Deploy:
   ```bash
   cd Sportfest_deploy
   railway login
   railway init
   railway up
   ```

### 3. Heroku

**Benötigt Procfile**

Erstelle `Procfile`:
```
web: vendor/bin/heroku-php-apache2
```

Deploy:
```bash
cd Sportfest_deploy
git init
heroku create sportfest-app
git add .
git commit -m "Initial deployment"
git push heroku master
```

### 4. DigitalOcean App Platform

1. Erstelle ein GitHub Repository
2. Pushe den Code
3. Verbinde Repository mit DigitalOcean
4. Wähle PHP als Runtime
5. Deploy!

---

## MySQL-Konvertierung (Optional)

Falls dein Hoster SQLite nicht unterstützt:

1. **Installiere MySQL auf dem Server**

2. **Konvertiere die Datenbank**:
   ```bash
   # SQLite nach MySQL Export
   sqlite3 sportfest.db .dump > dump.sql
   ```

3. **Passe db.php an**:
   ```php
   // Ersetze PDO SQLite durch MySQL
   $dsn = "mysql:host=localhost;dbname=sportfest;charset=utf8mb4";
   $db = new PDO($dsn, 'username', 'password');
   ```

---

## Checkliste nach Deployment

- [ ] Login funktioniert
- [ ] Admin-Passwort geändert
- [ ] Schüler können angelegt werden
- [ ] Disziplinen können angelegt werden
- [ ] Ergebnisse können erfasst werden
- [ ] CSV-Import funktioniert
- [ ] Export funktioniert
- [ ] Datenbank-Berechtigungen korrekt
- [ ] Error Reporting in Produktion deaktiviert
- [ ] Backup-Strategie etabliert

---

## Troubleshooting

### Problem: 500 Internal Server Error
**Lösung**: Prüfe die `.htaccess` Datei und PHP-Fehler-Logs

### Problem: Datenbank nicht beschreibbar
**Lösung**:
```bash
chmod 666 sportfest.db
chmod 777 .
```

### Problem: Styles werden nicht geladen
**Lösung**: Passe `BASE_URL` in `config.php` an

### Problem: Login funktioniert nicht
**Lösung**: Prüfe Session-Berechtigungen auf dem Server
```php
// In config.php
session_save_path('/tmp');
```
