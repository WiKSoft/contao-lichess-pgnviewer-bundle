/*
 * Backend: Die Vorschau im Feld "Figurensatz" (lpv_pieceSet) zeigt die
 * Feldfarben aus "Farbe helle/dunkle Felder" sofort beim Ändern, nicht erst
 * nach dem Speichern (siehe PieceSets::getBackendOptions(), public/backend.css).
 *
 * Contaos Farbwähler setzt den Wert per Skript, ohne ein input-/change-Ereignis
 * auszulösen. Deshalb wird außer bei Eingaben auch nach jedem Klick geprüft
 * (z. B. "Speichern" im Farbwähler oder Klick daneben).
 */
(function () {
    'use strict';

    var defaults = { light: 'f0d9b5', dark: 'b58863' };

    function color(name) {
        var input = document.getElementById('ctrl_lpv_square' + (name === 'light' ? 'Light' : 'Dark') + 'ColorHex');
        var value = input ? input.value.trim().replace(/^#/, '') : '';

        return '#' + (/^[0-9a-f]{6}$/i.test(value) ? value : defaults[name]);
    }

    function update() {
        var previews = document.querySelectorAll('.lpv-pieceset__preview');

        if (!previews.length) {
            return;
        }

        var light = color('light');
        var dark = color('dark');

        previews.forEach(function (preview) {
            preview.style.setProperty('--lpv-sq-light', light);
            preview.style.setProperty('--lpv-sq-dark', dark);
        });
    }

    function isColorField(target) {
        return target && /^ctrl_lpv_square(Light|Dark)ColorHex$/.test(target.id || '');
    }

    document.addEventListener('input', function (event) {
        if (isColorField(event.target)) {
            update();
        }
    });

    document.addEventListener('change', function (event) {
        if (isColorField(event.target)) {
            update();
        }
    });

    // Farbwähler: Wert wird beim Klick auf "Speichern" bzw. beim Schließen gesetzt
    document.addEventListener('click', function () {
        if (document.getElementById('ctrl_lpv_squareLightColorHex')) {
            window.setTimeout(update, 0);
        }
    });
})();
