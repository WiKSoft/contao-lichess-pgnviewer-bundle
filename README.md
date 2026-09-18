# wiksoft/contao-lichess-pgnviewer-bundle

Content-Element **"Schachpartie (lichess PGN-Viewer)"** für Contao 5: macht eine
oder mehrere Schachpartien im Frontend nachspielbar, dargestellt mit dem
offiziellen [lichess.org PGN-Viewer](https://github.com/lichess-org/pgn-viewer)
(`@lichess-org/pgn-viewer`, vendored/unverändert unter `public/lichess-pgn-viewer/`).

## Funktionsweise

- **PGN-Quelle** (im Content-Element wählbar):
  - **Datei**: eine `.pgn`-Datei aus der Dateiverwaltung
  - **Textfeld**: PGN-Text direkt im Content-Element
  - **Interne Datenbank**: Partien aus `wiksoft/dbchess-bundle` (Sammlungen
    auswählbar, optionaler eigener SQL-Filter) - **diese Option erscheint im
    Backend nur, wenn `wiksoft/dbchess-bundle` installiert ist** (siehe
    `src/Pgn/DbChessAvailability.php`; das Bundle ist nur ein Composer
    `suggest`, keine feste Abhängigkeit).
- Enthält die Quelle **mehrere Partien**, werden sie serverseitig anhand der
  `[Event "..."]`-Tags aufgeteilt (`src/Pgn/PgnSplitter.php`) und komplett an
  das Template übergeben. Im Frontend erscheint dann automatisch ein
  Dropdown zur Partieauswahl; der Wechsel passiert rein clientseitig per
  JavaScript (kein Neuladen der Seite).
- **Anzeige und Sortierung der Dropdown-Einträge** sind bewusst im
  Twig-Template gelöst (`contao/templates/ce_lichessPgnviewer.html.twig`,
  Block `gameSelect`), nicht im PHP-Code - so lassen sie sich über eine
  eigene Vorlage (Feld "Eigene Vorlage" im Content-Element) anpassen, ohne
  die PHP-Klasse zu ändern.
- Im Content-Element lassen sich (fast) alle Konfigurationsoptionen des
  lichess-pgn-viewer einstellen (Anzeige von Spielern/Uhren/Zugliste/
  Steuerung, Brettausrichtung, Startposition, Pfeile zeichnen, Koordinaten,
  Hervorhebungen, Animationsdauer, Menüpunkte "PGN herunterladen"/"Gegen
  Computer üben"/"Analysebrett", lichess.org-Verknüpfung). Nicht angeboten
  werden Chessground-Optionen für interaktive Zugeingabe (`movable`,
  `draggable`, `premovable` etc.) sowie alternative Farbschemata/Figurensätze,
  da der lichess-pgn-viewer als reiner Wiedergabe-Viewer diese Chessground-
  Optionen intern fest auf "nur ansehen" setzt bzw. nur den mitgelieferten
  Figurensatz (cburnett) vendored ist.

## Änderungen am lichess-pgn-viewer-Code

Es werden ausschließlich die unveränderten Original-Dateien
`lichess-pgn-viewer.min.js` und `lichess-pgn-viewer.css` (Version 2.6.4)
vendored. Sämtliche Contao-spezifische Logik liegt in einem eigenen,
schlanken Glue-Script (`public/lichess-pgn-viewer/lichess-pgn-viewer-init.js`),
das den Viewer initialisiert und bei einer Dropdown-Auswahl neu erzeugt.
Änderungen am lichess-Code selbst sind **nicht** vorgesehen und sollten nur
nach Rücksprache erfolgen.

## Installation

In der Projekt-`composer.json` als Path-Repository eintragen (analog zu
`wiksoft/dbchess-bundle` und `wiksoft/pgn4web-bundle`) und
`composer update wiksoft/contao-lichess-pgnviewer-bundle` sowie
`vendor/bin/contao-console contao:migrate` ausführen, damit die neuen
`tl_content`-Felder in der Datenbank angelegt werden.
