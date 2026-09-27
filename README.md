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
  - Brettausrichtung und Startzug
  - Pfeile, Koordinaten und Hervorhebungen
  - Animation
  - Menüpunkte (PGN herunterladen, gegen den Computer üben, Analysebrett, Link
    zu lichess.org)
  - Breite und Farben der Brettfelder
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

## Aufbau

| Pfad | Zweck |
|---|---|
| `src/ContentElement/ContentLichessPgnviewer.php` | Inhaltselement: sammelt die Partien je nach Quelle und baut die Viewer-Optionen |
| `src/Module/ModuleLichessPgnviewerReader.php` | Frontend-Modul „Reader": zeigt eine Partie anhand des Alias in der URL |
| `src/Pgn/PgnSplitter.php` | Zerlegt einen PGN-Text mit mehreren Partien in einzelne Partien samt Kopfzeilen |
| `src/Pgn/DbChessAvailability.php` | Prüft per `class_exists()`, ob `wiksoft/contao-dbchess-bundle` installiert ist |
| `src/EventListener/LoadDataContainerListener.php` | Bindet `backend.css` nur beim Laden der DCA von `tl_content`/`tl_module` ein |
| `src/ContaoManager/Plugin.php` | Contao-Manager-Plugin für die Bundle-Registrierung |
| `contao/config/config.php` | Registriert Inhaltselement, Frontend-Modul, CSS und Hook |
| `contao/dca/tl_content.php`, `contao/dca/tl_module.php` | Paletten, Felder und Options-Callbacks für Inhaltselement und Modul |
| `contao/templates/ce_lichessPgnviewer.html.twig` | Standard-Vorlage des Inhaltselements inkl. Partieauswahl |
| `contao/templates/mod_lichessPgnviewerReader.html.twig` | Standard-Vorlage des Reader-Moduls |
| `contao/languages/de/*.php` | Backend-Sprachdateien |
| `public/lichess-pgn-viewer/` | Unveränderte Originaldateien des lichess-pgn-viewer (JS/CSS) plus eigenes Init-Script |
| `public/lichess-pgn-viewer/NOTICE.md` | Herkunft, Lizenz und Quellcode-Link der mitgelieferten lichess-Dateien |
| `public/lpv.css` | Layout von Wrapper und Auswahlliste (nicht die Viewer-Styles selbst) |
| `public/backend.css` | Styles für die Eingabemasken im Backend |

## Änderungen am lichess-pgn-viewer-Code

Eingebunden sind ausschließlich die unveränderten Originaldateien
`lichess-pgn-viewer.min.js` und `lichess-pgn-viewer.css` (Version 2.6.4).
Die gesamte Contao-spezifische Logik liegt in einem eigenen, schlanken
Init-Script (`public/lichess-pgn-viewer/lichess-pgn-viewer-init.js`), das den
Viewer initialisiert und bei einer Auswahl im Auswahlfeld neu erzeugt.
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

## Lizenz

GPL-3.0-or-later, siehe [LICENSE](LICENSE).

Die Erweiterung liefert den
[lichess-pgn-viewer](https://github.com/lichess-org/pgn-viewer) (Version 2.6.4,
© Lichess Team und Mitwirkende) mit aus, der unter GPL-3.0-or-later steht. Die
Erweiterung steht deshalb als Ganzes ebenfalls unter dieser Lizenz. Herkunft
und Quellcode der mitgelieferten Dateien sind in
[public/lichess-pgn-viewer/NOTICE.md](public/lichess-pgn-viewer/NOTICE.md)
angegeben.
