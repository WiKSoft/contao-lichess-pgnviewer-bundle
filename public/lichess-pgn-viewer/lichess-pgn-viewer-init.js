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

    const baseOptions = readOptions(root);
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

    // LichessPgnViewer() does not update the element it is given in place -
    // internally it replaces it with a freshly built element (snabbdom
    // patch against a non-vnode element only reuses the node when its
    // tag/id/class selector matches the new render, which it never does
    // here). Passing the same "root" element into it a second time would
    // therefore try to replace an element that is no longer attached to the
    // document (it was already swapped out on the first render), and the
    // new content silently fails to appear. To make re-rendering with a
    // different game safe, "root" is kept as our own stable, never-replaced
    // container, and a brand new child element is created for the library
    // on every render.
    const render = (pgn) => {
        root.innerHTML = '';
        const mount = document.createElement('div');
        root.appendChild(mount);
        LichessPgnViewer(mount, Object.assign({}, baseOptions, { pgn: pgn || '' }));
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
