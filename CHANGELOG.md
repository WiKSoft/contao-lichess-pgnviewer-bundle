# Changelog

Alle nennenswerten Änderungen an `wiksoft/contao-lichess-pgnviewer-bundle`.
Die Versionsnummern folgen [Semantic Versioning](https://semver.org/lang/de/).

## [1.2.0] – 2026-10-01

### Neu

- Das Feld „PGN-Text" prüft beim Speichern die Länge (höchstens 65.535 Byte,
  Grenze der Datenbankspalte) und zeigt bei Überschreitung eine Meldung am
  Feld.

### Geändert

- Der Partie-Alias in der URL wird immer über `auto_item` gelesen und Links
  auf Partien werden immer ohne `/items/` erzeugt. Die Contao-4-Einstellung
  `useAutoItem` (z. B. `contao.localconfig.useAutoItem` in der
  `config/config.yaml`) wird nicht mehr benötigt. Alte Links mit `/items/`
  funktionieren weiterhin.

### Dokumentation

- README: Hinweis auf den Dateityp `pgn` in den erlaubten Upload- und
  Download-Dateitypen.

## [1.1.0] – 2026-09-30

### Neu

- **Start in einer Nebenvariante:** Bei der Startposition „Bestimmter Halbzug"
  gibt es die Felder „Variante" und „Tiefe in der Variante". Variante N
  ersetzt den angegebenen Halbzug der Hauptlinie, der Viewer startet nach der
  eingestellten Zahl von Halbzügen in dieser Variante.
- **Notation der Hauptlinie** wahlweise in Spalten (wie bisher) oder als
  Fließtext.
- **Nullzüge** (`--`, `Z0`), mit denen Anmerkungen Drohungen zeigen, werden ab
  dem Nullzug als Kommentartext angezeigt, statt samt der restlichen Linie
  verloren zu gehen. „PGN herunterladen" liefert weiterhin die Original-PGN.
- **Züge in Kommentaren** werden als `<span class="lpv-comment-move">`
  ausgezeichnet und lassen sich per CSS gestalten, z. B. in einer
  Figurinen-Schrift.
- **Höhe der Zugliste unter dem Brett** über die CSS-Variable
  `--lpv-moves-height` einstellbar (Standard jetzt 14em statt 6em).
- Beim Start mit einem Halbzug scrollt die Zugliste zum aktuellen Zug.

### Geändert

- **Zugliste rechts/links ist responsiv:** Fehlt Platz, wird zuerst das Brett
  schmaler, die Zugliste behält mindestens 232px. „Brettbreite" ist damit eine
  Höchstbreite, zusätzlich begrenzt durch die Fensterhöhe. Ist der Viewer
  schmaler als 432px, rutscht die Zugliste unter das Brett. Maßgeblich ist die
  Breite des Viewers, nicht die des Browserfensters.
- **Option „Automatisch" bei „Zugliste anzeigen" entfernt**, da „Rechts"
  und „Links" jetzt dasselbe Verhalten haben. Neuer Standard ist „Rechts". Die
  Migration `ShowMovesAutoMigration` stellt vorhandene Einstellungen beim
  Datenbank-Update automatisch um.

### Behoben

- Beginnt eine Nebenvariante mit demselben Zug wie die Hauptlinie oder wie
  eine andere Variante, markierte ein Klick beide Züge, und „vor" lief im
  falschen Ast weiter.
- Die CSS-ID aus den Experteneinstellungen wurde escaped ausgegeben
  (`id=&quot;…&quot;`) und wirkte deshalb nicht, z. B. als Sprungmarke.

### Hinweis zum Update

Nach dem Update die Datenbank aktualisieren (Contao Manager →
*Datenbank aktualisieren* oder `vendor/bin/contao-console contao:migrate`).
Dabei werden die neuen Felder angelegt und die Migration ausgeführt.

## [1.0.1] – 2026-09-28

### Sicherheit

- Der „Eigene Filter" der Quelle „Interne Datenbank – Liste"
  (`lpv_dbChess_filter`) ist ein freier SQL-Ausdruck. Er kann jetzt nur noch
  von Administratoren bearbeitet werden. Bisher konnte jeder Redakteur mit
  Zugriff auf das Feld beliebiges SQL ausführen. **Update wird empfohlen.**

### Behoben

- Bei „Zugliste links neben dem Brett" erscheinen Menü und PGN-Ansicht jetzt
  über dem Brett statt über der Zugliste.

## [1.0.0] – 2026-09-28

Erste veröffentlichte Version: Inhaltselement „Schachpartie (lichess
PGN-Viewer)" mit den Quellen Datei, Textfeld und dbChess-Datenbank (Liste und
Einzelauswahl), Frontend-Modul „lichessPgnviewer Reader", Partieauswahl,
verknüpfte Partien, Rundennavigation, Zugliste links oder rechts, feste
Brettbreite und Farben der Brettfelder, Sprachdateien Deutsch und Englisch.

[1.2.0]: https://github.com/WiKSoft/contao-lichess-pgnviewer-bundle/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/WiKSoft/contao-lichess-pgnviewer-bundle/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/WiKSoft/contao-lichess-pgnviewer-bundle/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/WiKSoft/contao-lichess-pgnviewer-bundle/releases/tag/v1.0.0
