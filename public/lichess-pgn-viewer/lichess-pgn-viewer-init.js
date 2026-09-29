/**
 * Initialisiert den lichess.org PGN-Viewer (lichess-pgn-viewer.min.js,
 * unveränderter Original-Code aus @lichess-org/pgn-viewer) für alle
 * Content-Elemente "lichessPgnviewer" auf der Seite.
 *
 * Dieses Script ist eigener Code von wiksoft/contao-lichess-pgnviewer-bundle
 * und Teil KEINE Änderung an lichess-pgn-viewer.min.js selbst.
 *
 * Als ES-Modul eingebunden (<script type="module">) läuft dieser Code erst,
 * nachdem das HTML-Dokument geparst wurde, und pro eindeutiger Script-URL
 * nur genau einmal - auch wenn das Content-Element mehrfach auf einer Seite
 * vorkommt (jede Instanz bindet dieses Modul erneut ein, der Browser führt
 * den Modul-Code trotzdem nur einmal aus). Ein einziger Durchlauf reicht
 * also aus, um alle Viewer der Seite zu finden und zu initialisieren.
 */
import LichessPgnViewer from './lichess-pgn-viewer.min.js';

function readOptions(root) {
    const id = root.dataset.lpvOptions;
    const el = id ? document.getElementById(id) : null;

    if (!el) {
        return {};
    }

    try {
        return JSON.parse(el.textContent);
    } catch (error) {
        return {};
    }
}

function initViewer(root) {
    if (root.dataset.lpvInitialised === '1') {
        return;
    }
    root.dataset.lpvInitialised = '1';

    // "initialVariation" (siehe buildViewerOptions() im Controller) kennt
    // der lichess-pgn-viewer nicht - die Option wird hier herausgelöst und
    // nach dem Aufbau des Viewers selbst ausgewertet, siehe goToVariation().
    const { initialVariation, ...baseOptions } = readOptions(root);
    const wrapper = root.closest('.lpv-wrapper');
    const select = wrapper ? wrapper.querySelector('[data-lpv-select]') : null;

    // Die zusätzlichen Partiedaten (Veranstaltung, Ort, Runde, ECO, Elo,
    // Kommentator, Quelle - siehe Block "gameInfo" im Twig-Template, bzw.
    // bei Custom-Templates wie ce_lichessPgnviewer_turnier auch außerhalb
    // davon, z. B. die Quellenangabe unterhalb des Bretts) zeigt der
    // lichess-pgn-viewer selbst nicht an. Sie stecken als data-* Attribute
    // auf der ausgewählten <option> (bzw. auf "root" selbst, wenn es nur
    // eine Partie gibt) und werden hier bei jedem Partiewechsel in JEDES
    // [data-lpv-info]-Element im gesamten .lpv-wrapper übertragen - bewusst
    // nicht nur innerhalb des "-info"-<dl>, damit Custom-Templates solche
    // Felder auch außerhalb der regulären Partiedaten-Liste platzieren
    // können. Ziel ist das <dd> der Zeile, falls vorhanden (reguläre
    // .lpv-info__row-Zeilen), sonst die Zeile selbst (z. B. ein einzelnes
    // <p data-lpv-info="...">).
    const updateInfo = (source) => {
        if (!wrapper || !source) {
            return;
        }
        wrapper.querySelectorAll('[data-lpv-info]').forEach((row) => {
            const value = source.dataset[row.dataset.lpvInfo];
            const target = row.querySelector('dd') || row;
            if (value) {
                target.textContent = value;
                row.hidden = false;
            } else {
                row.hidden = true;
            }
        });
    };

    // LichessPgnViewer() aktualisiert das übergebene Element nicht an Ort
    // und Stelle, sondern ersetzt es intern durch ein neu aufgebautes
    // Element (ein snabbdom-Patch gegen ein Element ohne vnode übernimmt
    // den Knoten nur, wenn dessen Tag/ID/Klassen-Selektor zum neuen Rendern
    // passt - was hier nie der Fall ist). Würde man dasselbe "root"-Element
    // ein zweites Mal übergeben, versuchte die Bibliothek ein Element zu
    // ersetzen, das gar nicht mehr im Dokument hängt (es wurde schon beim
    // ersten Rendern ausgetauscht), und der neue Inhalt erschiene
    // stillschweigend nicht. Damit das erneute Rendern mit einer anderen
    // Partie sicher funktioniert, bleibt "root" unser eigener, stabiler und
    // nie ersetzter Container, und für die Bibliothek wird bei jedem
    // Rendern ein neues Kind-Element angelegt.
    const render = (pgn) => {
        root.innerHTML = '';
        const mount = document.createElement('div');
        root.appendChild(mount);
        const ctrl = LichessPgnViewer(mount, Object.assign({}, baseOptions, { pgn: pgn || '' }));

        if (makePathsUnique(ctrl)) {
            ctrl.redraw();
        }

        if (initialVariation) {
            goToVariation(ctrl);
        }

        if (baseOptions.initialPly) {
            scrollToCurrentMove();
        }
    };

    // Der lichess-pgn-viewer adressiert jeden Knoten im Zugbaum über einen
    // Pfad aus 2-Zeichen-IDs, die allein aus dem Zug berechnet werden
    // (Start-/Zielfeld). Beginnen zwei Äste am selben Knoten mit demselben
    // Zug - eine Nebenvariante mit dem Hauptlinienzug oder zwei
    // Nebenvarianten mit dem gleichen Zug -, erhalten sie denselben Pfad.
    // Die Bibliothek findet dann immer nur den ersten Ast: ein Klick in den
    // zweiten springt in den ersten, beide werden als aktuell markiert und
    // "vor" läuft im falschen Ast weiter. Die IDs dienen nur als Schlüssel
    // und werden nirgends in Züge zurückgerechnet - doppelte Geschwister-
    // IDs werden daher hier durch freie Zeichen aus dem Unicode-Bereich für
    // private Nutzung ersetzt und die Pfade des ganzen Teilbaums neu
    // gesetzt. Der erste Ast (Hauptlinie bzw. erste Variante) behält seine
    // ID, die Pfade der Hauptlinie ändern sich also nie. Rückgabe: true,
    // wenn etwas umbenannt wurde (dann muss neu gezeichnet werden).
    const makePathsUnique = (ctrl) => {
        const root = ctrl && ctrl.game && ctrl.game.moves;
        if (!root || !root.children) {
            return false;
        }

        const Path = ctrl.path.constructor;
        let counter = 0;
        let changed = false;

        const walk = (node, prefix) => {
            const used = new Set();
            node.children.forEach((child) => {
                let id = child.data.path.last();
                if (used.has(id)) {
                    do {
                        id = String.fromCharCode(0xe000 + Math.floor(counter / 64), 0xe000 + (counter % 64));
                        counter++;
                    } while (used.has(id));
                    changed = true;
                }
                used.add(id);
                if (child.data.path.path !== prefix + id) {
                    child.data.path = new Path(prefix + id);
                }
                walk(child, prefix + id);
            });
        };

        walk(root, '');

        return changed;
    };

    // Start in einer Nebenvariante: Variante Nummer "index" ersetzt den
    // Halbzug "initialPly" der Hauptlinie (1 = erste Variante, 2 = zweite
    // ...). Deren Knoten hängen im Zugbaum als weitere Kinder neben dem
    // Hauptlinienzug (children[0]) am vorherigen Knoten. Von dort geht es
    // "depth - 1" Halbzüge entlang der Variante weiter (children[0] ist
    // jeweils ihre eigene Fortsetzung), höchstens bis zu ihrem Ende. Gibt
    // es die Variante nicht, bleibt der Viewer beim Hauptlinien-Halbzug.
    const goToVariation = (ctrl) => {
        const ply = baseOptions.initialPly;
        const game = ctrl && ctrl.game;
        if (!game || typeof ply !== 'number' || ply < 1 || !game.mainline[ply - 1]) {
            return;
        }

        const parent = ply === 1 ? game.moves : game.nodeAt(game.mainline[ply - 2].path);
        let node = parent && parent.children[initialVariation.index];
        if (!node) {
            return;
        }

        for (let i = 1; i < initialVariation.depth && node.children[0]; i++) {
            node = node.children[0];
        }

        ctrl.toPath(node.data.path, false);
    };

    // Startet der Viewer nicht in der Grundstellung (initialPly = Halbzug-
    // Nummer oder 'last'), versucht der lichess-pgn-viewer zwar selbst, die
    // Zugliste zum aktuellen Zug (.current) zu scrollen - aber nur einmal
    // direkt beim Einfügen ins DOM. Zu diesem Zeitpunkt hat die Zugliste
    // ihre endgültige Höhe noch nicht (sie wächst erst mit dem Brett bzw.
    // dem CSS-Grid mit), der Scroll geht daher ins Leere und die Liste
    // bleibt oben stehen. Hier wird deshalb bei jeder Größenänderung der
    // Zugliste erneut zentriert, bis der Nutzer selbst eingreift oder das
    // Layout sich gesetzt hat. Zusätzlich nach dem Laden der Webfonts und
    // der ganzen Seite: dabei ändert sich nicht die Größe der Zugliste,
    // wohl aber der Zeilenumbruch langer Kommentare - der aktuelle Zug
    // rutscht dann innerhalb der Liste, ohne dass ResizeObserver feuert.
    const scrollToCurrentMove = () => {
        const moves = root.querySelector('.lpv__moves');
        if (!moves || typeof ResizeObserver === 'undefined') {
            return;
        }

        const center = () => {
            const current = moves.querySelector('.current');
            if (!current) {
                return;
            }
            const offset = current.getBoundingClientRect().top - moves.getBoundingClientRect().top + moves.scrollTop;
            moves.scrollTop = offset - moves.clientHeight / 2 + current.offsetHeight;
        };

        let active = true;
        const recenter = () => {
            if (active) {
                center();
            }
        };

        const observer = new ResizeObserver(recenter);
        const stop = () => {
            active = false;
            observer.disconnect();
            window.removeEventListener('load', recenter);
            ['pointerdown', 'wheel', 'keydown', 'touchstart'].forEach((type) => root.removeEventListener(type, stop));
        };

        observer.observe(moves);
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(recenter);
        }
        if (document.readyState !== 'complete') {
            window.addEventListener('load', recenter);
        }
        ['pointerdown', 'wheel', 'keydown', 'touchstart'].forEach((type) => root.addEventListener(type, stop, { passive: true }));
        window.setTimeout(stop, 10000);
    };

    if (select) {
        const selectedOption = select.options[select.selectedIndex];
        render(selectedOption ? selectedOption.dataset.pgn : '');
        updateInfo(selectedOption);

        select.addEventListener('change', () => {
            const option = select.options[select.selectedIndex];
            render(option ? option.dataset.pgn : '');
            updateInfo(option);
        });
    } else if (root.dataset.lpvPgn) {
        render(root.dataset.lpvPgn);
        updateInfo(root);
    }
}

document.querySelectorAll('[data-lpv-root]').forEach(initViewer);
