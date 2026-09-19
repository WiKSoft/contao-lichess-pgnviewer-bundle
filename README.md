# wiksoft/contao-lichess-pgnviewer-bundle

Contao-5-Bundle, das ein Content-Element **"Schachpartie (lichess PGN-Viewer)"**
bereitstellt: Es macht eine oder mehrere Schachpartien im PGN-Format im
Frontend nachspielbar – gerendert mit dem offiziellen
[lichess.org PGN-Viewer](https://github.com/lichess-org/pgn-viewer)
(`@lichess-org/pgn-viewer`, unverändert vendored unter
`public/lichess-pgn-viewer/`). Der Viewer läuft komplett im Browser als
ES-Modul; das PHP-Element liefert ihm nur die PGN-Daten sowie die
Konfigurationsoptionen.

## Features

- **Drei wählbare PGN-Quellen** im Content-Element:
  - **Datei** – eine `.pgn`-Datei aus der Contao-Dateiverwaltung
  - **Textfeld** – PGN-Text direkt im Content-Element eingegeben
  - **Interne Datenbank** – Partien aus `wiksoft/dbchess-bundle`, entweder
    als Liste einer/mehrerer Sammlungen (mit optionalem eigenen SQL-Filter,
    Sortierung und Rundennavigation) oder als manuelle Einzelauswahl
    bestimmter Partien. Diese Quelle erscheint im Backend **nur**, wenn
    `wiksoft/dbchess-bundle` installiert ist (geprüft über
    `DbChessAvailability::isInstalled()`); das Bundle ist nur ein
    Composer-`suggest`, keine feste Abhängigkeit.
- **Mehrere Partien pro Element**: Enthält die Quelle mehrere Partien,
  werden sie serverseitig anhand der `[Event "..."]`-Tags aufgeteilt
  (`PgnSplitter`) und alle zusammen an das Template übergeben. Im Frontend
  erscheint automatisch ein Dropdown zur Partieauswahl; der Wechsel
  passiert rein clientseitig per JavaScript, ohne Seiten-Neuladung.
- **Rundennavigation** (nur Quelle "Interne Datenbank"): optional lässt
  sich eine Vor-/Zurück-Navigation zwischen den Runden einer Sammlung
  aktivieren, gesteuert über einen URL-Parameter.
- **Umfangreiche Konfiguration** der lichess-pgn-viewer-Optionen direkt im
  Content-Element: Anzeige von Spielern, Uhren, Zugliste und Steuerung,
  Brettausrichtung, Startzug, Pfeile zeichnen, Koordinaten, Hervorhebungen
  (letzter Zug/Schach), Animationsdauer, Sperren des Touch-Scrollings,
  Menüpunkte ("PGN herunterladen" inkl. Dateiname, "Gegen Computer üben",
  "Analysebrett"), Verlinkung zu lichess.org sowie Breite von Element und
  Brett.
- **Anpassbares Design**: einfache Farbanpassung der hellen/dunklen
  Brettfelder direkt im Content-Element (Hex-Farbwerte); weitergehendes
  Styling erfolgt zentral per CSS.
- **Eigene Vorlagen**: Anzeige und Sortierung der Dropdown-Einträge sind
  bewusst im Twig-Template gelöst (`ce_lichessPgnviewer.html.twig`, Block
  `gameSelect`) statt im PHP-Code, sodass sie sich über eine eigene Vorlage
  anpassen lassen, ohne die PHP-Klasse zu ändern.

**Nicht** angeboten werden Chessground-Optionen für interaktive Zugeingabe
(`movable`, `draggable`, `premovable` etc.) sowie alternative Farbschemata/
Figurensätze, da der lichess-pgn-viewer als reiner Wiedergabe-Viewer diese
Optionen intern fest auf "nur ansehen" setzt bzw. nur der mitgelieferte
Figurensatz (cburnett) vendored ist.

## Aufbau

| Pfad | Zweck |
|---|---|
| `src/ContentElement/ContentLichessPgnviewer.php` | Content-Element-Klasse, sammelt PGN-Text je nach Quelle und baut die Viewer-Optionen |
| `src/Pgn/PgnSplitter.php` | Zerlegt einen PGN-Text mit mehreren Partien in einzelne Partien inkl. geparster Header |
| `src/Pgn/DbChessAvailability.php` | Prüft per `class_exists()`, ob `wiksoft/dbchess-bundle` installiert ist |
| `src/ContaoManager/Plugin.php` | Contao-Manager-Plugin für die Bundle-Registrierung |
| `contao/dca/tl_content.php` | DCA-Erweiterung: Palette, Felder und Options-Callbacks für `tl_content` |
| `contao/templates/ce_lichessPgnviewer.html.twig` | Twig-Template inkl. Partieauswahl-Dropdown |
| `contao/languages/de/*.php` | Backend-Sprachdateien (Labels, Referenztexte) |
| `public/lichess-pgn-viewer/` | Vendorte, unveränderte Original-Dateien des lichess-pgn-viewer (JS/CSS) plus eigenes Init-Script |
| `public/lpv.css` | Layout-Styles für Wrapper/Auswahlliste (nicht die Viewer-Styles selbst) |

## Änderungen am lichess-pgn-viewer-Code

Es werden ausschließlich die unveränderten Original-Dateien
`lichess-pgn-viewer.min.js` und `lichess-pgn-viewer.css` (Version 2.6.4)
vendored. Sämtliche Contao-spezifische Logik liegt in einem eigenen,
schlanken Glue-Script (`public/lichess-pgn-viewer/lichess-pgn-viewer-init.js`),
das den Viewer initialisiert und bei einer Dropdown-Auswahl neu erzeugt.
Änderungen am lichess-Code selbst sind **nicht** vorgesehen und sollten nur
nach Rücksprache erfolgen.

## Anforderungen

- PHP ^8.1
- Contao ^5.3
- optional: `wiksoft/dbchess-bundle` (für die Quelle "Interne Datenbank")

## Installation

In der Projekt-`composer.json` als Path-Repository eintragen, dann:

```bash
composer update wiksoft/contao-lichess-pgnviewer-bundle
vendor/bin/contao-console contao:migrate
```

## Lizenz

LGPL-3.0-or-later
