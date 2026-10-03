# wiksoft/contao-lichess-pgnviewer-bundle

Mit dieser Erweiterung lassen sich Schachpartien im PGN-Format auf der Website
nachspielen. Das Brett stammt vom offiziellen
[lichess.org PGN-Viewer](https://github.com/lichess-org/pgn-viewer). Der Viewer
läuft vollständig im Browser, Contao liefert ihm nur die PGN-Daten und die
Einstellungen.

## Funktionen

### Inhaltselement „Schachpartie (lichess PGN-Viewer)"

- Die Partien können aus drei Quellen kommen:
  - aus einer **Datei** (`.pgn` aus der Dateiverwaltung)
  - aus einem **Textfeld** (PGN direkt im Element eingeben)
  - aus der **internen Datenbank** (dbChess, siehe unten)
- Enthält die Quelle mehrere Partien, werden sie auf dem Server getrennt. Im
  Frontend erscheint dann ein Auswahlfeld, und der Wechsel zwischen den Partien
  geschieht ohne Neuladen der Seite.
- Im Element lässt sich vieles einstellen:
  - Spieler, Uhren, Zugliste und Steuerung
  - Position der Zugliste: rechts oder links neben dem Brett, darunter oder
    ausgeblendet
  - Notation der Hauptlinie in Spalten oder als Fließtext
  - Brettausrichtung und Startposition: Grundstellung, letzter Zug, ein
    bestimmter Halbzug oder ein Zug in einer Nebenvariante
  - Pfeile, Koordinaten und Hervorhebungen
  - Animation
  - Menüpunkte (PGN herunterladen, gegen den Computer üben, Analysebrett, Link
    zu lichess.org)
  - Gesamtbreite, Brettbreite und Farben der Brettfelder
  - Figurensatz (neun mitgelieferte Sätze mit Vorschau im Backend, siehe
    [Figurensätze](#figurensätze))
- Die Darstellung ist responsiv: Neben dem Brett wird zuerst das Brett
  schmaler (die „Brettbreite" ist eine Höchstbreite), die Zugliste behält
  mindestens 232px. Ist der Viewer schmaler als 432px, rutscht die Zugliste
  unter das Brett.
- Beim Start mit einem Halbzug scrollt die Zugliste zum aktuellen Zug.
- Nullzüge (`--`, `Z0`), mit denen Anmerkungen Drohungen zeigen, kann der
  lichess-Viewer nicht ausführen. Die Linie ab dem Nullzug wird deshalb als
  Kommentartext angezeigt, statt verloren zu gehen. „PGN herunterladen" liefert
  weiterhin die Original-PGN.
- Züge in Kommentaren werden als `<span class="lpv-comment-move">`
  ausgezeichnet, z. B. um sie wie die Zugliste in einer Figurinen-Schrift
  darzustellen (siehe [Anpassen per CSS](#anpassen-per-css)).
- Die Darstellung läuft über Twig-Vorlagen mit überschreibbaren Blöcken
  (`gameInfo`, `gameSelect`, `viewer`, `remark`). Eigene Vorlagen nach dem
  Muster `ce_lichessPgnviewer_*` können im Element ausgewählt werden.

### Frontend-Modul „lichessPgnviewer Reader"

> **Hinweis:** Der Reader funktioniert nur zusammen mit dbChess
> (`wiksoft/contao-dbchess-bundle`). Er liest die Partien ausschließlich aus
> `tl_dbChess_games`. Ist dbChess nicht installiert, gibt das Modul im Frontend
> nichts aus.

- Funktioniert wie der Nachrichtenleser in Contao: Es zeigt genau eine Partie
  aus der Datenbank, die über den Partie-Alias in der URL bestimmt wird.
- Es setzt den Seitentitel („Weiß – Schwarz Ergebnis (Datum)") und die
  Meta-Beschreibung (Event, Ort).
- Die Viewer-Einstellungen sind dieselben wie beim Inhaltselement.
- Optional kann das Modul auf bestimmte Sammlungen beschränkt werden.

## Zusammenspiel mit dbChess

dbChess (`wiksoft/contao-dbchess-bundle`) ist im Viewer nur als optionale
Ergänzung (`suggest`) eingetragen, nicht als feste Abhängigkeit. Ob dbChess
installiert ist, prüft der Viewer über `DbChessAvailability`. Nur dann
erscheinen im Backend die Datenbank-Quellen und die Sammlungsauswahl des
Readers. Ohne dbChess funktioniert das Inhaltselement weiterhin mit Datei oder
Textfeld, der Reader dagegen gibt dann nichts aus.

**1. dbChess als Partiequelle für das Inhaltselement**

- **„Interne Datenbank – Liste"**: Du wählst eine oder mehrere Sammlungen,
  optional mit SQL-Filter und Sortierung.
  - Eine Partie, deren Alias in der URL steht, wird angezeigt, sonst die erste.
  - Die Standard-Vorlage bietet ein Auswahlfeld mit allen passenden Partien,
    das per Link auf die jeweilige Partie wechselt.
  - Für eigene Vorlagen stehen Links zur vorherigen und nächsten Partie sowie
    eine Rundennavigation bereit (siehe [Eigene Vorlagen](#eigene-vorlagen)).
- **„Interne Datenbank – Einzelauswahl"**: Du wählst bestimmte Partien aus
  einer Sammlung von Hand aus.
- Aus jedem Datensatz in `tl_dbChess_games` wird ein vollständiges PGN mit
  Kopfzeilen gebaut. Die **Bemerkung** (`remark`) der Partie wird unter dem
  Brett angezeigt.

**2. Link von der Liste zum Viewer**

- Das dbChess-Inhaltselement **„dbChess_list"** und die Detailliste des
  **„dbChess_index"**-Moduls haben jeweils eine **Weiterleitungsseite**. Jede
  Partie wird dorthin als `…/zielseite/<partie-alias>` verlinkt.
- Auf der Zielseite liest der **Reader** oder das **Inhaltselement mit
  Datenbank-Quelle** diesen Alias aus und zeigt die Partie an. Weil der Reader
  ohne Alias nichts ausgibt, können Liste und Reader auch auf derselben Seite
  stehen.

**3. Verknüpfte Partien (`sid`)**

- In dbChess kann dieselbe Partie mehrfach vorkommen, etwa mit Kommentaren
  verschiedener Autoren. Die Funktion „Partien verknüpfen" fasst diese
  Fassungen über `sid` zusammen.
- Der Viewer lädt dann alle verknüpften Fassungen mit, und über ein
  Auswahlfeld lässt sich zwischen ihnen umschalten. In Navigationen erscheint
  eine verknüpfte Partie trotzdem nur einmal.

**Typischer Aufbau:**

1. Partien im dbChess-Backend per PGN importieren und verknüpfen.
2. Eine Übersichtsseite mit „dbChess_list" oder „dbChess_index" anlegen und
   eine Weiterleitungsseite einstellen.
3. Auf der Weiterleitungsseite den lichess-Reader oder das Viewer-Element mit
   Quelle „Interne Datenbank" einbinden.

## Eigene Vorlagen

Die mitgelieferten Vorlagen nutzen nicht alle Daten, die der PHP-Code
bereitstellt. Die folgenden Variablen sind für eigene Vorlagen vorbereitet.
Sie sind immer gesetzt, bei fehlenden Daten mit `null` bzw. `''`. Eine eigene
Vorlage kann deshalb ohne weitere Prüfung auf sie zugreifen.

Am einfachsten erweitert man die Standard-Vorlage und überschreibt nur einen
Block. Die neue Vorlage wird unter `templates/` abgelegt, z. B. als
`templates/ce_lichessPgnviewer_meine.html.twig`, und dann im Element unter
„Eigene Vorlage" ausgewählt:

```twig
{% extends '@Contao/ce_lichessPgnviewer.html.twig' %}

{% block gameInfo %}
    {# eigene Ausgabe, siehe Beispiele unten #}
    {{ parent() }}
{% endblock %}
```

### Link zur übergeordneten Seite

Gilt für das Inhaltselement (alle Quellen) und für den Reader.

| Variable | Inhalt |
|---|---|
| `upHref` | URL der Elternseite (`null`, wenn es keine gibt oder sie eine Weiterleitung ist) |
| `upTitle` | Titel der Elternseite |

```twig
{% if upHref %}
    <a href="{{ upHref }}">zurück zu {{ upTitle }}</a>
{% endif %}
```

### Vorherige und nächste Partie

Gilt nur für das Inhaltselement mit Quelle „Interne Datenbank – Liste". Die
Links führen zur vorherigen bzw. nächsten Partie in der sortierten Liste.
Verknüpfte Fassungen (`sid`) werden dabei nur einmal gezählt. Die Links
passen zum Beispiel für Simultan- oder Trainingspartien ohne sinnvolle
Rundenangabe.

| Variable | Inhalt |
|---|---|
| `prevGameHref`, `nextGameHref` | URL der Nachbarpartie (`null` am Anfang bzw. Ende der Liste) |
| `prevGameWhite`, `prevGameBlack` | Spieler der vorherigen Partie |
| `nextGameWhite`, `nextGameBlack` | Spieler der nächsten Partie |

```twig
<nav class="lpv-gamenav">
    {% if prevGameHref %}<a href="{{ prevGameHref }}">‹ {{ prevGameWhite }} – {{ prevGameBlack }}</a>{% endif %}
    {% if nextGameHref %}<a href="{{ nextGameHref }}">{{ nextGameWhite }} – {{ nextGameBlack }} ›</a>{% endif %}
</nav>
```

### Rundennavigation

Gilt nur für das Inhaltselement mit Quelle „Interne Datenbank – Liste". Der
Name der eigenen Vorlage spielt keine Rolle. Die aktuelle Runde ist die Runde
der gerade angezeigten Partie. Die Runden werden numerisch sortiert, in der
Richtung der eingestellten Sortierung (auf- oder absteigend). Die Links führen
jeweils zur ersten Partie der Nachbarrunde. Damit die Navigation gut
funktioniert, sollten die Partien nach Runde sortiert sein.

| Variable | Inhalt |
|---|---|
| `currentRound` | aktuelle Runde (`''`, wenn es keine Rundenangaben gibt) |
| `prevRound`, `nextRound` | Nachbarrunde (`null` am Anfang bzw. Ende) |
| `prevRoundHref`, `nextRoundHref` | URL der ersten Partie der Nachbarrunde |

```twig
{# z. B. templates/ce_lichessPgnviewer_turnier.html.twig #}
{% extends '@Contao/ce_lichessPgnviewer.html.twig' %}

{% block gameInfo %}
    {% if currentRound %}
        <nav class="lpv-roundnav">
            {% if prevRoundHref %}<a href="{{ prevRoundHref }}">‹ Runde {{ prevRound }}</a>{% endif %}
            <span>Runde {{ currentRound }}</span>
            {% if nextRoundHref %}<a href="{{ nextRoundHref }}">Runde {{ nextRound }} ›</a>{% endif %}
        </nav>
    {% endif %}
    {{ parent() }}
{% endblock %}
```

## Vorgaben am Startpunkt der Website

Die meisten Darstellungs-Einstellungen lassen sich zentral am **Startpunkt
der Website** vorgeben (*Seiten*, Startpunkt bearbeiten, Abschnitt
*lichess PGN-Viewer: Vorgaben*). Inhaltselemente und Reader-Module erben sie.

Für jede Einstellung gilt beim Ausgeben:

1. der eigene Wert des Elements bzw. Moduls, falls einer gewählt ist,
2. sonst die Vorgabe am Startpunkt der Website,
3. sonst der eingebaute Standard der Erweiterung.

Im Element steht dafür bei jeder Einstellung die Option **„Standard (…)“**;
in Klammern steht, was gerade gilt. Bei Text- und Farbfeldern zeigt das leere
Feld den geerbten Wert als Platzhalter. Ja/Nein-Einstellungen sind deshalb
Auswahllisten mit „Standard / Ja / Nein“.

Ändert man eine Vorgabe am Startpunkt, ändern sich alle Elemente, die auf
„Standard“ stehen, ohne dass man sie einzeln speichern muss.

**Für alle Elemente erzwingen:** Am Startpunkt lassen sich einzelne
Einstellungen sperren. Sie gelten dann für alle Elemente und Module der
Website, auch für solche mit eigenem Wert; im Element sind die Felder
schreibgeschützt. Der eigene Wert bleibt gespeichert und gilt wieder, sobald
die Sperre aufgehoben wird.

Vorgeben lassen sich: Spieler, Uhren, Zugliste (Position und Notation),
Steuerung, Mitscrollen, Tastatur, Partiedaten, Pfeile, Koordinaten,
Hervorhebungen, Animation, Touch-Scrollen, Feldfarben, Figurensatz,
Menüpunkte, Breite, Brettbreite und die Vorlage des Inhaltselements. Nur im
Element einstellbar bleiben alles, was von der einzelnen Partie abhängt:
Quelle, Partieauswahl, Startposition, Brettausrichtung, Dateiname für den
PGN-Download und CSS-Klasse.

Gibt es mehrere Startpunkte (mehrere Websites in einer Installation), gilt
für jedes Element der Startpunkt seiner Seite. Beim Reader-Modul ist der
Startpunkt der Seite maßgeblich, auf der es ausgegeben wird.

Die Vorgaben stehen in der Datenbank (`tl_page`) und wandern deshalb mit
jedem Datenbank-Export mit.

## Figurensätze

Im Inhaltselement und im Reader-Modul wählt das Feld „Figurensatz"
(Abschnitt *Design-Einstellungen*) das Aussehen der Figuren. Jede Option zeigt
eine kleine Vorschau mit Urheber und Lizenz, auf Feldern in den Farben aus
„Farbe helle/dunkle Felder“. Mitgeliefert werden diese Sätze
von [lichess.org](https://github.com/lichess-org/lila/tree/master/public/piece):

| Satz | Urheber | Lizenz |
|---|---|---|
| `cburnett` (Standard) | Colin M. L. Burnett | GPL v2+ |
| `merida` | Armando Hernandez Marroquin | GPL v2+ |
| `mpchess` | Maxime Chupin | GPL v3+ |
| `chessnut` | Alexis Luengas | Apache 2.0 |
| `fantasy` | Maurizio Monge | MIT |
| `celtic` | Maurizio Monge | MIT |
| `rhosgfx` | RhosGFX | CC0 1.0 |
| `kiwen-suwi` | neverRare | CC BY 4.0 |
| `totoy` | Kosal Sen | CC BY 4.0 |

Alle Lizenzen erlauben die Nutzung auf jeder Website, auch einer
kommerziellen. Außer bei `rhosgfx` (CC0) müssen Urheber und Lizenz aber
genannt werden, bei `kiwen-suwi` und `totoy` (CC BY 4.0) sichtbar für die
Besucher, z. B. im Impressum oder Quellenverzeichnis. Einzelheiten und
Lizenztexte stehen in
[public/pieces/LICENSES.md](public/pieces/LICENSES.md).

So funktioniert es: Das Template setzt die Klasse `lpv-pieces--<satz>` auf
den `.lpv-wrapper` und bindet `public/pieces/<satz>.css` ein. Diese Datei
überschreibt die Hintergrundbilder der Figuren, die der lichess-Viewer als
`<piece class="knight white">` usw. ausgibt. Für den Standard `cburnett` wird
keine Datei geladen, seine Figuren stecken schon in `lichess-pgn-viewer.css`.

Die Figuren in der Zugliste und in Kommentaren (♘, ♗) sind Schriftzeichen
und ändern sich mit dem Figurensatz nicht.

### Eigene Figurensätze per CSS

Ein weiterer Satz lässt sich ohne Änderung an der Erweiterung nachrüsten:

1. Die zwölf Figuren als SVG (oder PNG/WebP) im Ordner `files/` ablegen,
   z. B. `files/pieces/meinsatz/wK.svg` … `bP.svg`. Benennung wie bei lichess:
   `w`/`b` für Weiß/Schwarz, `K Q R B N P` für König, Dame, Turm, Läufer,
   Springer, Bauer. Quadratische Bilder ohne Rand sehen am besten aus.

2. In einem Stylesheet des Themes die zwölf Figuren überschreiben. Ohne
   eigene Klasse gilt der Satz für alle Viewer der Website:

   ```css
   .lpv-wrapper .cg-wrap piece.king.white   { background-image: url("/files/pieces/meinsatz/wK.svg"); }
   .lpv-wrapper .cg-wrap piece.queen.white  { background-image: url("/files/pieces/meinsatz/wQ.svg"); }
   .lpv-wrapper .cg-wrap piece.rook.white   { background-image: url("/files/pieces/meinsatz/wR.svg"); }
   .lpv-wrapper .cg-wrap piece.bishop.white { background-image: url("/files/pieces/meinsatz/wB.svg"); }
   .lpv-wrapper .cg-wrap piece.knight.white { background-image: url("/files/pieces/meinsatz/wN.svg"); }
   .lpv-wrapper .cg-wrap piece.pawn.white   { background-image: url("/files/pieces/meinsatz/wP.svg"); }
   .lpv-wrapper .cg-wrap piece.king.black   { background-image: url("/files/pieces/meinsatz/bK.svg"); }
   .lpv-wrapper .cg-wrap piece.queen.black  { background-image: url("/files/pieces/meinsatz/bQ.svg"); }
   .lpv-wrapper .cg-wrap piece.rook.black   { background-image: url("/files/pieces/meinsatz/bR.svg"); }
   .lpv-wrapper .cg-wrap piece.bishop.black { background-image: url("/files/pieces/meinsatz/bB.svg"); }
   .lpv-wrapper .cg-wrap piece.knight.black { background-image: url("/files/pieces/meinsatz/bN.svg"); }
   .lpv-wrapper .cg-wrap piece.pawn.black   { background-image: url("/files/pieces/meinsatz/bP.svg"); }
   ```

3. Soll der Satz nur bei einzelnen Elementen erscheinen, statt
   `.lpv-wrapper` eine eigene Klasse voranstellen und sie im Element als
   *CSS-Klasse* (Experten-Einstellungen) eintragen, z. B.
   `.figuren-meinsatz .cg-wrap piece.king.white { … }`. Im Feld
   „Figurensatz" bleibt dann `cburnett` stehen.

Der Selektor braucht mindestens eine Klasse vor `.cg-wrap`, sonst gewinnt die
Regel aus `lichess-pgn-viewer.css` (`.cg-wrap piece.king.white`). Bei fremden
Figurensätzen gelten deren Lizenzbedingungen, viele lichess-Sätze stehen zum
Beispiel unter CC BY-NC-SA (nur nicht-kommerziell), siehe
[COPYING.md von lichess](https://github.com/lichess-org/lila/blob/master/COPYING.md).

## Anpassen per CSS

Im eigenen Theme lassen sich unter anderem folgende Punkte anpassen:

| Selektor / Variable | Wirkung |
|---|---|
| `.lpv-wrapper { --lpv-moves-height: 18em; }` | Höhe der Zugliste, wenn sie unter dem Brett steht (Standard 14em) |
| `.lpv-wrapper .lpv__moves comment .lpv-comment-move` | Züge innerhalb von Kommentaren, z. B. `font-family` einer Figurinen-Schrift |
| `.lpv-wrapper--moves-left`, `.lpv-wrapper--moves-flow` | Wrapper-Klassen für „Zugliste links" bzw. „Notation als Fließtext" |
| `.lpv-pieces--<satz>` | Wrapper-Klasse des gewählten Figurensatzes (siehe [Figurensätze](#figurensätze)) |
| `--c-lpv-*` | Farbvariablen des lichess-Viewers (siehe `lichess-pgn-viewer.css`) |

## Aufbau

| Pfad | Zweck |
|---|---|
| `src/ContentElement/ContentLichessPgnviewer.php` | Inhaltselement: sammelt die Partien je nach Quelle und baut die Viewer-Optionen |
| `src/Module/ModuleLichessPgnviewerReader.php` | Frontend-Modul „Reader": zeigt eine Partie anhand des Alias in der URL |
| `src/Pgn/PgnSplitter.php` | Zerlegt einen PGN-Text mit mehreren Partien in einzelne Partien samt Kopfzeilen |
| `src/Settings/ViewerSettings.php` | Vorgaben am Startpunkt: vererbbare Felder, Auflösung (Element → Startpunkt → Standard), Sperren, Backend-Hinweise |
| `src/Board/PieceSets.php` | Liste der Figurensätze mit Urheber und Lizenz, Backend-Optionen mit Vorschau, Stylesheet-Pfad |
| `src/Pgn/DbChessAvailability.php` | Prüft per `class_exists()`, ob `wiksoft/contao-dbchess-bundle` installiert ist |
| `src/EventListener/LoadDataContainerListener.php` | Bindet `backend.css` nur beim Laden der DCA von `tl_content`/`tl_module` ein |
| `src/Migration/ViewerDefaultsMigration.php` | Datenbank-Migration (1.3.0): stellt Ja/Nein-Felder auf Standard/Ja/Nein um und Werte, die dem bisherigen Standard entsprechen, auf „Standard“ |
| `src/Migration/ShowMovesAutoMigration.php` | Datenbank-Migration: stellt die entfallene Zuglisten-Option „Automatisch" auf „Rechts" um |
| `src/DependencyInjection/`, `config/services.yaml` | Registrierung der Dienste (Migrationen) |
| `src/ContaoManager/Plugin.php` | Contao-Manager-Plugin für die Bundle-Registrierung |
| `contao/config/config.php` | Registriert Inhaltselement, Frontend-Modul, CSS und Hook |
| `contao/dca/tl_page.php` | Vorgaben und Sperren am Startpunkt der Website |
| `contao/dca/tl_content.php`, `contao/dca/tl_module.php` | Paletten, Felder und Options-Callbacks für Inhaltselement und Modul |
| `contao/templates/ce_lichessPgnviewer.html.twig` | Standard-Vorlage des Inhaltselements inkl. Partieauswahl |
| `contao/templates/mod_lichessPgnviewerReader.html.twig` | Standard-Vorlage des Reader-Moduls |
| `contao/languages/de/*.php`, `contao/languages/en/*.php` | Sprachdateien (Deutsch, Englisch) für Backend und Frontend |
| `public/lichess-pgn-viewer/` | Unveränderte Originaldateien des lichess-pgn-viewer (JS/CSS) plus eigenes Init-Script |
| `public/lichess-pgn-viewer/NOTICE.md` | Herkunft, Lizenz und Quellcode-Link der mitgelieferten lichess-Dateien |
| `public/lichess-pgn-viewer/lichess-pgn-viewer-init.js` | Eigenes Init-Script (siehe unten) |
| `public/lpv.css` | Layout von Wrapper und Auswahlliste, responsive Spalten, Zuglisten-Position und Fließtext-Notation |
| `public/backend.css`, `public/backend.js` | Styles und Skript für die Eingabemasken im Backend, u. a. die Vorschau der Figurensätze |
| `public/pieces/` | Figurensätze (SVG), je Satz eine `<satz>.css`, Lizenzen in `LICENSES.md` und `licenses/` |

## Änderungen am lichess-pgn-viewer-Code

Eingebunden sind ausschließlich die unveränderten Originaldateien
`lichess-pgn-viewer.min.js` und `lichess-pgn-viewer.css` (Version 2.6.4).
Die gesamte Contao-spezifische Logik liegt in einem eigenen Init-Script
(`public/lichess-pgn-viewer/lichess-pgn-viewer-init.js`). Es

- initialisiert den Viewer und erzeugt ihn bei einer Auswahl im Auswahlfeld neu,
- wandelt Nullzüge vor der Übergabe in Kommentartext um,
- vergibt eindeutige Pfade, wenn Varianten mit demselben Zug beginnen wie die
  Hauptlinie oder eine andere Variante (sonst springt die Navigation in den
  falschen Ast),
- steuert eine Startposition in einer Nebenvariante an,
- scrollt die Zugliste zum aktuellen Zug und
- zeichnet Züge in Kommentaren aus.

Änderungen am lichess-Code selbst sind nicht vorgesehen.

## Anforderungen

- PHP ^8.1
- Contao ^5.3
- optional: `wiksoft/contao-dbchess-bundle` (für die Quelle „Interne Datenbank"
  und das Reader-Modul)

## Installation

1. Das Paket installieren, entweder im **Contao Manager** (nach
   `wiksoft/contao-lichess-pgnviewer-bundle` suchen und installieren) oder auf
   der **Kommandozeile**:
   ```bash
   composer require wiksoft/contao-lichess-pgnviewer-bundle
   ```

2. Anschließend die Datenbank aktualisieren, entweder im **Contao Manager**
   unter *Systemwartung → Datenbank aktualisieren* oder auf der
   **Kommandozeile**:
   ```bash
   vendor/bin/contao-console contao:migrate
   ```

3. Damit PGN-Dateien in die Dateiverwaltung hochgeladen, als Quelle „Datei“
   geladen und über das Menü des Viewers heruntergeladen werden können, muss
   der Dateityp `pgn` in den Backend-Einstellungen erlaubt sein. Unter
   *System → Einstellungen* `pgn` in beide Felder eintragen:
   - **Erlaubte Upload-Dateitypen** (Abschnitt *Datei-Uploads*) – für das
     Hochladen bzw. Importieren von `.pgn`-Dateien
   - **Erlaubte Download-Dateitypen** (Abschnitt *Dateien und Bilder*) – für
     das Herunterladen bzw. Exportieren von `.pgn`-Dateien

   Der Eintrag wird kommagetrennt an die vorhandene Liste angehängt,
   z. B. `…,zip,pgn`.

## Lizenz

GPL-3.0-or-later, siehe [LICENSE](LICENSE).

Die Erweiterung liefert den
[lichess-pgn-viewer](https://github.com/lichess-org/pgn-viewer) (Version 2.6.4,
© Lichess Team und Mitwirkende) mit aus, der unter GPL-3.0-or-later steht. Die
Erweiterung steht deshalb als Ganzes ebenfalls unter dieser Lizenz. Herkunft
und Quellcode der mitgelieferten Dateien sind in
[public/lichess-pgn-viewer/NOTICE.md](public/lichess-pgn-viewer/NOTICE.md)
angegeben.

Die Figurensätze in `public/pieces/` stammen von lichess.org und stehen unter
eigenen Lizenzen (GPL v2+, GPL v3+, Apache 2.0, MIT, CC0 1.0, CC BY 4.0), siehe
[public/pieces/LICENSES.md](public/pieces/LICENSES.md).
