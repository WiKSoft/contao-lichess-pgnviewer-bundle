<?php

declare(strict_types=1);

namespace Wiksoft\ContaoLichessPgnviewerBundle\Pgn;

/**
 * Zerlegt einen PGN-Text, der eine oder mehrere Partien enthalten kann, in
 * einzelne Partien. Jede Partie wird als eigenständiger PGN-Text (inkl.
 * ihrer eigenen Tag-Sektion) zurückgegeben, so wie ihn der lichess-pgn-viewer
 * als "pgn"-Option einer einzelnen Viewer-Instanz erwartet - der Viewer
 * selbst kann pro Instanz immer nur eine Partie darstellen (siehe
 * https://github.com/lichess-org/pgn-viewer, config.ts: "pgn: string").
 *
 * Für die im Frontend angezeigte Auswahlliste (bei mehreren Partien) werden
 * zusätzlich die Tag-Pairs (Event, White, Black, Date, ...) geparst und pro
 * Partie zurückgegeben; das Contao-Template entscheidet, wie diese Angaben
 * als Auswahltext dargestellt und sortiert werden.
 */
final class PgnSplitter
{
    /**
     * @return list<array{pgn: string, headers: array<string, string>}>
     */
    public static function split(string $pgn): array
    {
        $pgn = trim(str_replace(["\r\n", "\r"], "\n", $pgn));

        if ('' === $pgn) {
            return [];
        }

        // Kein Tag-Pair im Text: gesamten Inhalt als einzelne Partie ohne
        // Kopfdaten behandeln (z. B. reine Zugfolge im Textfeld).
        if (!preg_match('/^\s*\[Event\s+"/m', $pgn)) {
            return [
                ['pgn' => $pgn, 'headers' => []],
            ];
        }

        $chunks = preg_split('/(?=^\s*\[Event\s+")/m', $pgn) ?: [];

        $games = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);

            if ('' === $chunk) {
                continue;
            }

            $games[] = [
                'pgn' => $chunk,
                'headers' => self::parseHeaders($chunk),
            ];
        }

        return $games;
    }

    /**
     * @return array<string, string>
     */
    private static function parseHeaders(string $pgn): array
    {
        $headers = [];

        if (!preg_match_all('/^\s*\[(\w+)\s+"((?:\\\\.|[^"\\\\])*)"\]\s*$/m', $pgn, $matches, PREG_SET_ORDER)) {
            return $headers;
        }

        foreach ($matches as $match) {
            $headers[$match[1]] = str_replace(['\\"', '\\\\'], ['"', '\\'], $match[2]);
        }

        return $headers;
    }
}
