<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Settings;

use Contao\ArticleModel;
use Contao\Database;
use Contao\DataContainer;
use Contao\PageModel;
use Contao\StringUtil;
use Wiksoft\ContaoLichessPgnviewerBundle\Board\PieceSets;

/**
 * Vorgaben für den Viewer am Startpunkt der Website (tl_page, Typ "root"
 * bzw. "rootfallback").
 *
 * Für jede Einstellung aus FIELDS gilt beim Ausgeben eines Elements oder
 * Moduls:
 *
 *  1. eigener Wert des Elements/Moduls, falls gesetzt (nicht leer),
 *  2. sonst der Wert am Startpunkt der Website,
 *  3. sonst der eingebaute Standard (BUILTIN).
 *
 * Ist die Einstellung am Startpunkt gesperrt (Feld lpv_locked), gilt Stufe 2
 * bzw. 3 auch dann, wenn das Element einen eigenen Wert hat. Der eigene Wert
 * bleibt gespeichert und wirkt wieder, sobald die Sperre aufgehoben wird.
 *
 * Gespeichert wird überall gleich: '' = Standard (erben), Ja/Nein-Felder als
 * 'y'/'n'. resolve() liefert die Werte in der bisherigen Form ('1'/'' für
 * Ja/Nein), damit der übrige Code unverändert bleibt.
 */
final class ViewerSettings
{
    private const KIND_BOOL = 'bool';
    private const KIND_SELECT = 'select';
    private const KIND_TEXT = 'text';

    /**
     * Feld => [Art, eingebauter Standard]. Reihenfolge = Reihenfolge am
     * Startpunkt.
     */
    public const FIELDS = [
        'lpv_showPlayers' => [self::KIND_SELECT, 'a'],
        'lpv_showClocks' => [self::KIND_BOOL, '1'],
        'lpv_showMoves' => [self::KIND_SELECT, 'r'],
        'lpv_movesLayout' => [self::KIND_SELECT, 'c'],
        'lpv_showControls' => [self::KIND_BOOL, '1'],
        'lpv_scrollToMove' => [self::KIND_BOOL, '1'],
        'lpv_keyboardToMove' => [self::KIND_BOOL, '1'],
        'lpv_showGameInfo' => [self::KIND_BOOL, '1'],
        'lpv_drawArrows' => [self::KIND_BOOL, '1'],
        'lpv_coordinates' => [self::KIND_BOOL, '1'],
        'lpv_highlightLastMove' => [self::KIND_BOOL, '1'],
        'lpv_highlightCheck' => [self::KIND_BOOL, '1'],
        'lpv_animationDuration' => [self::KIND_TEXT, '250'],
        'lpv_blockTouchScroll' => [self::KIND_BOOL, ''],
        'lpv_squareLightColorHex' => [self::KIND_TEXT, ''],
        'lpv_squareDarkColorHex' => [self::KIND_TEXT, ''],
        'lpv_pieceSet' => [self::KIND_SELECT, PieceSets::DEFAULT],
        'lpv_menuGetPgn' => [self::KIND_BOOL, '1'],
        'lpv_menuPractice' => [self::KIND_BOOL, '1'],
        'lpv_menuAnalysisBoard' => [self::KIND_BOOL, '1'],
        'lpv_lichessLink' => [self::KIND_BOOL, '1'],
        'lpv_width' => [self::KIND_TEXT, ''],
        'lpv_boardWidth' => [self::KIND_TEXT, ''],
        'lpv_template' => [self::KIND_SELECT, 'ce_lichessPgnviewer'],
    ];

    /**
     * Anzeigewert des eingebauten Standards, wo BUILTIN leer ist (die Farben
     * und Breiten kommen dann aus public/lpv.css).
     */
    private const BUILTIN_DISPLAY = [
        'lpv_squareLightColorHex' => 'f0d9b5',
        'lpv_squareDarkColorHex' => 'b58863',
        'lpv_width' => '100%',
        'lpv_boardWidth' => 'auto',
    ];

    /**
     * Felder, die nur das Inhaltselement erbt. Das Reader-Modul hat eigene
     * Vorlagen (mod_lichessPgnviewerReader*).
     */
    private const CONTENT_ONLY = ['lpv_template'];

    /**
     * @var array<int, array<string, mixed>|null>
     */
    private static array $rootCache = [];

    /**
     * @return list<string>
     */
    public static function getFields(string $table): array
    {
        $fields = array_keys(self::FIELDS);

        if ('tl_content' !== $table && 'tl_page' !== $table) {
            $fields = array_values(array_diff($fields, self::CONTENT_ONLY));
        }

        return $fields;
    }

    /**
     * Wirksame Werte für ein Element/Modul in der bisherigen Form ('1'/''
     * für Ja/Nein).
     *
     * @param array<string, mixed>      $own  Datensatz des Elements/Moduls
     * @param array<string, mixed>|null $root Startpunkt der Website
     *
     * @return array<string, string>
     */
    public static function resolve(string $table, array $own, array|null $root): array
    {
        $locked = self::getLocked($root);
        $values = [];

        foreach (self::getFields($table) as $field) {
            [$kind, $builtin] = self::FIELDS[$field];

            $ownValue = (string) ($own[$field] ?? '');
            $rootValue = (string) ($root[$field] ?? '');

            if (\in_array($field, $locked, true) || '' === $ownValue) {
                $value = '' !== $rootValue ? $rootValue : $builtin;
            } else {
                $value = $ownValue;
            }

            if (self::KIND_BOOL === $kind) {
                $value = match ($value) {
                    'y', '1' => '1',
                    'n' => '',
                    default => $builtin,
                };
            }

            $values[$field] = $value;
        }

        return $values;
    }

    /**
     * Startpunkt der Seite, die gerade im Frontend ausgegeben wird.
     *
     * @return array<string, mixed>|null
     */
    public static function getRootForCurrentPage(): array|null
    {
        $page = $GLOBALS['objPage'] ?? null;

        if (!$page instanceof PageModel) {
            return null;
        }

        return self::getRootRow((int) ($page->rootId ?: $page->loadDetails()->rootId));
    }

    /**
     * @return list<string>
     */
    public static function getLocked(array|null $root): array
    {
        if (!$root) {
            return [];
        }

        return array_values(array_intersect(
            StringUtil::deserialize($root['lpv_locked'] ?? null, true),
            array_keys(self::FIELDS),
        ));
    }

    /**
     * Macht die Felder in tl_content bzw. tl_module vererbbar: Ja/Nein wird
     * zur Auswahl Standard/Ja/Nein, Auswahllisten bekommen "Standard" als
     * leere Option, Standardwerte entfallen (leer = erben).
     */
    public static function makeInheritable(string $table): void
    {
        foreach (self::getFields($table) as $field) {
            if (!isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                continue;
            }

            $config = &$GLOBALS['TL_DCA'][$table]['fields'][$field];
            [$kind] = self::FIELDS[$field];

            unset($config['default']);

            // Beschriftung, falls prepareBackendFields() nicht läuft (z. B.
            // "Mehrere bearbeiten"); dort wird sie durch den Wert ergänzt
            $config['eval']['blankOptionLabel'] = $GLOBALS['TL_LANG']['MSC']['lpv_inheritShort'] ?? 'Default';

            switch ($kind) {
                case self::KIND_BOOL:
                    $config['inputType'] = 'select';
                    $config['options'] = ['y', 'n'];
                    $config['reference'] = &$GLOBALS['TL_LANG']['MSC']['lpv_yesNo'];
                    unset($config['eval']['submitOnChange']);
                    $config['eval']['includeBlankOption'] = true;
                    $config['eval']['tl_class'] = trim(str_replace('m12', '', $config['eval']['tl_class'] ?? 'w50'));
                    $config['sql'] = "char(1) NOT NULL default ''";
                    break;

                case self::KIND_SELECT:
                    if ('radio' !== ($config['inputType'] ?? '')) {
                        $config['eval']['includeBlankOption'] = true;
                    }

                    $length = 'lpv_template' === $field ? 64 : ('lpv_pieceSet' === $field ? 32 : 1);
                    $config['sql'] = "varchar($length) NOT NULL default ''";
                    break;

                case self::KIND_TEXT:
                    if ('lpv_animationDuration' === $field) {
                        $config['sql'] = "varchar(5) NOT NULL default ''";
                    }
                    break;
            }

            unset($config);
        }
    }

    /**
     * onload_callback für tl_content/tl_module: zeigt bei jedem Feld, was
     * "Standard" gerade bedeutet (Wert vom Startpunkt oder eingebauter
     * Standard), und sperrt Felder, die am Startpunkt fest vorgegeben sind.
     */
    public static function prepareBackendFields(DataContainer $dc): void
    {
        $table = $dc->table;
        $record = $dc->id ? $dc->getCurrentRecord() : null;
        $root = self::getRootForRecord($table, $record);
        $locked = self::getLocked($root);

        foreach (self::getFields($table) as $field) {
            if (!isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
                continue;
            }

            $config = &$GLOBALS['TL_DCA'][$table]['fields'][$field];
            $rootValue = (string) ($root[$field] ?? '');
            $display = self::formatValue($table, $field, '' !== $rootValue ? $rootValue : null);
            $isLocked = \in_array($field, $locked, true);

            [$kind] = self::FIELDS[$field];

            if (self::KIND_TEXT === $kind) {
                // Leeres Feld zeigt den geerbten Wert als Platzhalter
                $config['eval']['placeholder'] = $display;
            } else {
                $config['eval']['blankOptionLabel'] = sprintf($GLOBALS['TL_LANG']['MSC']['lpv_inherit'] ?? 'Standard (%s)', $display);
            }

            if ($isLocked) {
                $config['eval']['disabled'] = true;
                $config['eval']['readonly'] = true;
                $label = (array) ($config['label'] ?? []);
                $label[1] = sprintf($GLOBALS['TL_LANG']['MSC']['lpv_locked'] ?? 'Fixed at the website root: %s', $display);
                $config['label'] = $label;
            }

            unset($config);
        }
    }

    /**
     * options_callback für lpv_pieceSet (Element, Modul, Startpunkt): die
     * Sätze mit Vorschau, davor "Standard" mit der Vorschau des geerbten
     * Satzes. Die Vorschau zeigt die wirksamen Feldfarben.
     *
     * @return array<string, string>
     */
    public static function getPieceSetOptions(DataContainer|null $dc = null): array
    {
        $table = $dc?->table ?? 'tl_content';
        $record = $dc?->id ? $dc->getCurrentRecord() : null;

        if ('tl_page' === $table) {
            // Am Startpunkt selbst: "Standard" = eingebauter Standard
            $root = null;
            $label = sprintf($GLOBALS['TL_LANG']['MSC']['lpv_inheritBuiltin'] ?? 'Extension default (%s)', PieceSets::DEFAULT);
        } else {
            $root = self::getRootForRecord($table, $record);
            $label = sprintf($GLOBALS['TL_LANG']['MSC']['lpv_inherit'] ?? 'Default (%s)', PieceSets::normalize($root['lpv_pieceSet'] ?? ''));
        }

        $color = static fn (string $field): string|null => ($record[$field] ?? '') ?: (($root[$field] ?? '') ?: null);

        return PieceSets::getBackendOptions(
            $color('lpv_squareLightColorHex'),
            $color('lpv_squareDarkColorHex'),
            $label,
            PieceSets::normalize($root['lpv_pieceSet'] ?? ''),
            // Am Startpunkt ist "Standard" = cburnett, nicht noch einmal anbieten
            'tl_page' === $table,
        );
    }

    /**
     * Startpunkt zum Datensatz eines Elements/Moduls im Backend. Elemente in
     * Artikeln: Startpunkt ihrer Seite. Sonst (Modul, Nachrichten usw.):
     * der einzige Startpunkt der Installation, wenn es genau einen gibt.
     *
     * @param array<string, mixed>|null $record
     *
     * @return array<string, mixed>|null
     */
    public static function getRootForRecord(string $table, array|null $record): array|null
    {
        if ('tl_content' === $table && $record && 'tl_article' === ($record['ptable'] ?? 'tl_article')) {
            $article = ArticleModel::findById((int) $record['pid']);
            $page = $article ? PageModel::findWithDetails((int) $article->pid) : null;

            if ($page) {
                return self::getRootRow((int) $page->rootId);
            }
        }

        $ids = Database::getInstance()
            ->execute("SELECT id FROM tl_page WHERE type IN ('root', 'rootfallback')")
            ->fetchEach('id');

        return 1 === \count($ids) ? self::getRootRow((int) $ids[0]) : null;
    }

    /**
     * Lesbarer Wert für Hinweise im Backend. $value = null: eingebauter
     * Standard.
     */
    public static function formatValue(string $table, string $field, string|null $value): string
    {
        [$kind, $builtin] = self::FIELDS[$field];
        $value ??= self::BUILTIN_DISPLAY[$field] ?? $builtin;

        if (self::KIND_BOOL === $kind) {
            $key = \in_array($value, ['y', '1'], true) ? 'y' : 'n';

            return $GLOBALS['TL_LANG']['MSC']['lpv_yesNo'][$key] ?? $key;
        }

        $reference = $GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'] ?? null;

        if (\is_array($reference) && isset($reference[$value])) {
            return \is_array($reference[$value]) ? (string) $reference[$value][0] : (string) $reference[$value];
        }

        return $value;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function getRootRow(int $rootId): array|null
    {
        if ($rootId < 1) {
            return null;
        }

        if (!\array_key_exists($rootId, self::$rootCache)) {
            self::$rootCache[$rootId] = PageModel::findById($rootId)?->row();
        }

        return self::$rootCache[$rootId];
    }
}
