# Sportfest Manager - Deployment

## Ordnerstruktur

```
Sportfest_deploy/
├── static/                 # Statische Assets
│   ├── css/
│   │   └── style.css      # Haupt-Stylesheet
│   ├── js/                # JavaScript-Dateien (falls vorhanden)
│   └── logo_pirol.png     # Logo
├── views/                  # Template-Dateien
│   ├── header.php         # Header-Template
│   └── footer.php         # Footer-Template
├── api.php                # API-Endpunkte
├── auth.php               # Authentifizierungs-Funktionen
├── config.php             # Konfiguration
├── db.php                 # Datenbank-Verbindung
├── disciplines.php        # Disziplinen-Verwaltung
├── enter_results.php      # Ergebnis-Erfassung
├── index.php              # Dashboard
├── login.php              # Login-Seite
├── logout.php             # Logout
├── settings.php           # Einstellungen
├── students.php           # Schüler-Verwaltung
├── view_results.php       # Ergebnis-Anzeige
├── sportfest.db           # SQLite-Datenbank
├── .htaccess              # Apache-Konfiguration
└── README.md              # Diese Datei
```

## Installation auf einem PHP-Server

### Voraussetzungen
- PHP 7.4 oder höher
- PDO SQLite-Erweiterung aktiviert
- Apache oder Nginx Webserver

### Schritte

1. **Upload**: Lade alle Dateien auf deinen Webserver hoch (z.B. via FTP)

2. **Berechtigungen setzen**:
   ```bash
   chmod 755 Sportfest_deploy/
   chmod 644 *.php
   chmod 666 sportfest.db
   chmod 777 .
   ```

3. **config.php anpassen** (falls nötig):
   - Öffne `config.php`
   - Passe `BASE_URL` an deinen Server-Pfad an
   - Beispiel: `define('BASE_URL', '/sportfest');`

4. **Datenbankberechtigungen**:
   - Stelle sicher, dass der Webserver-Benutzer (z.B. `www-data`) Schreibrechte auf `sportfest.db` hat

5. **Zugriff**:
   - Öffne die URL in deinem Browser
   - Standard-Login: `admin` / `admin123`

## Wichtige Hinweise

### Sicherheit
- **Ändere sofort das Admin-Passwort** nach der Installation!
- Die Datenbank `sportfest.db` sollte NICHT öffentlich zugänglich sein
- Setze in der Produktion `error_reporting(0)` in der `config.php`

### BASE_URL Konfiguration

Wenn deine App in einem Unterordner liegt:
```php
// config.php
define('BASE_URL', '/unterordner');  // MIT führendem Slash, OHNE abschließenden Slash
```

Wenn deine App im Root-Verzeichnis liegt:
```php
// config.php
define('BASE_URL', '');  // Leer lassen
```

### Datenbank-Backup
Sichere regelmäßig die `sportfest.db` Datei:
```bash
cp sportfest.db sportfest_backup_$(date +%Y%m%d).db
```

## Support & Credits

**Sportfest Manager v1.0.0**
Verwaltung von Sportfesten fürs Vicco

Konvertiert von Python/Flask zu PHP
