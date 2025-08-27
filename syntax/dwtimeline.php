<?php

/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\File\PageResolver;

class syntax_plugin_dwtimeline_dwtimeline extends SyntaxPlugin
{
    /**
     * Global direction memory
     * @var
     */
    protected static $direction;
    protected static $align;

    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'stack';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 400;
    }

    /**
     * Change the current content of $direction String (left,right)
     * @param string $direction
     * @return string
     */
    public function changeDirection(string $direction): string
    {
        if ($direction === 'tl-right') {
            $direction = 'tl-left';
        } else {
            $direction = 'tl-right';
        }
        return $direction;
    }

    public function getDirection()
    {
        if (!self::$direction) {
            self::$direction = 'tl-' . $this->getConf('direction');
        }
        return self::$direction;
    }

    /**
     * Handle the match
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return [];
    }

    /**
     * Create output
     *
     * @param string        $mode     string     output format being rendered
     * @param Doku_Renderer $renderer the current renderer object
     * @param array         $data     data created by handler()
     * @return  bool                 rendered correctly?
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        return false;
    }

    /**
     * Match entity options like: <dwtimeline opt1="value1" opt2='value2'>
     * Returns normalized data array used by the renderer.
     */
    public function getTitleMatches(string $match): array
    {
        // defaults
        $data = [
            'align' => self::$align, // standard alignment
            'data'  => '',
            'style' => ' style="',
        ];

        $opts = $this->parseOptions($match);

        foreach ($opts as $option => $rawValue) {
            switch ($option) {
                case 'link':
                    $data['link'] = $this->getLink($rawValue);
                    break;

                case 'data':
                    $datapoint    = substr($rawValue, 0, 4);
                    $data['data'] = ' data-point="' . hsc($datapoint) . '" ';
                    if (strlen($datapoint) > 2) {
                        $data['style'] .= '--4sizewidth: 50px; --4sizeright: -29px; --4sizesmallleft40: 60px; ';
                        $data['style'] .= '--4sizesmallleft50: 70px; --4sizesmallleft4: -10px; ';
                        $data['style'] .= '--4sizewidthhorz: 50px; --4sizerighthorz: -29px; ';
                    }
                    break;

                case 'align':
                    $data['align'] = $this->checkValues($rawValue, ['horz', 'vert'], self::$align);
                    break;

                case 'backcolor':
                    if ($c = $this->isValidColor($rawValue)) {
                        $data['style'] .= 'background-color:' . $c . '; ';
                    }
                    break;

                case 'style':
                    // do not accept custom styles at the moment
                    break;

                default:
                    // generic attributes (e.g., title)
                    $data[$option] = hsc($rawValue); // HTML-escape for output later
                    break;
            }
        }

        // close style if something was added
        $data['style'] = ($data['style'] === ' style="') ? '' : $data['style'] . '"';

        return $data;
    }

    /**
     * Parse HTML-like attributes from a string.
     * Supports: key="val", key='val', key=val (unquoted), with \" and \\ in "..."
     * Note: PREG_UNMATCHED_AS_NULL requires PHP 7.2+.
     */
    private function parseOptions(string $s): array
    {
        $out = [];
        $i   = 0;
        $len = strlen($s);

        $pattern = '/\G\s*(?P<name>[a-zA-Z][\w-]*)\s*'
            . '(?:=\s*(?:"(?P<dq>(?:[^"\\\\]|\\\\.)*)"'
            . '|\'(?P<sq>(?:[^\'\\\\]|\\\\.)*)\''
            . '|\[\[(?P<br>.+?)\]\]'
            . '|(?P<uq>[^\s"\'=<>`]+)))?'
            . '/A';

        while ($i < $len) {
            if (!preg_match($pattern, $s, $m, PREG_UNMATCHED_AS_NULL, $i)) {
                break;
            }
            $i += strlen($m[0]);

            $name = strtolower($m['name']);
            $raw  = $m['dq'] ?? $m['sq'] ?? ($m['br'] !== null ? '[[' . $m['br'] . ']]' : null) ?? $m['uq'] ?? '';
            if ($m['dq'] !== null || $m['sq'] !== null) {
                $raw = stripcslashes($raw); // \" und \\ in quoted Werten ent-escapen
            }
            $out[$name] = $raw;
        }
        return $out;
    }

    /**
     * Return the first link target found in the given wiki text.
     * Supports internal links [[id|label]], external links (bare or bracketed),
     * interwiki, mailto and Windows share. Returns a normalized target:
     * - internal: absolute page id, incl. optional "#section"
     * - external: absolute URL (http/https/ftp)
     * - email:    mailto:<addr>
     * - share:    \\server\share\path
     * Returns '' if none found.
     */
    public function getLink(string $wikitext): string
    {
        $ins = p_get_instructions($wikitext);
        if (!$ins) {
            return '';
        }

        global $ID;
        $resolver = new PageResolver($ID);

        foreach ($ins as $node) {
            $type = $node[0];
            // INTERNAL WIKI LINK [[ns:page#section|label]]
            if ($type === 'internallink') {
                $raw = $node[1][0] ?? '';
                if ($raw === '') {
                    continue;
                }

                $anchor = '';
                if (strpos($raw, '#') !== false) {
                    [$rawId, $sec] = explode('#', $raw, 2);
                    $raw    = trim($rawId);
                    $anchor = '#' . trim($sec);
                } else {
                    $raw = trim($raw);
                }

                $abs = $resolver->resolveId(cleanID($raw));
                return $abs . $anchor;
            }

            // EXTERNAL LINK (bare URL or [[http(s)/ftp://...|label]])
            if ($type === 'externallink') {
                // payload can be scalar or array depending on DW version
                $url = is_array($node[1]) ? (string)($node[1][0] ?? '') : (string)$node[1];
                return trim($url);
            }

            // INTERWIKI [[wp>Foo]] etc. – return the canonical "prefix>page"
            if ($type === 'interwikilink') {
                $raw = $node[1][0] ?? '';
                if ($raw === '') {
                    continue;
                }
                return $raw;
            }

            // EMAIL
            if ($type === 'emaillink') {
                $addr = is_array($node[1]) ? (string)($node[1][0] ?? '') : (string)$node[1];
                return 'mailto:' . trim($addr);
            }

            // WINDOWS SHARE
            if ($type === 'windowssharelink') {
                $path = is_array($node[1]) ? (string)($node[1][0] ?? '') : (string)$node[1];
                return trim($path);
            }
        }

        // Fallback: detect bare URL or email if no instruction was emitted
        if (preg_match('/\b(?:https?|ftp):\/\/\S+/i', $wikitext, $m)) {
            return rtrim($m[0], '.,);');
        }
        if (preg_match('/^[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}$/', trim($wikitext), $m)) {
            return 'mailto:' . $m[0];
        }

        return '';
    }

    public function checkValues($toCheck, $allowed, $standard)
    {
        if (in_array($toCheck, $allowed, true)) {
            return $toCheck;
        } else {
            return $standard;
        }
    }

    /**
     * Validate color value $color
     * this is cut price validation - only to ensure the basic format is correct and there is nothing harmful
     * three basic formats  "colorname", "#fff[fff]", "rgb(255[%],255[%],255[%])"
     */
    public function isValidColor($color)
    {
        $color      = trim($color);
        $colornames = [
            'AliceBlue',
            'AntiqueWhite',
            'Aqua',
            'Aquamarine',
            'Azure',
            'Beige',
            'Bisque',
            'Black',
            'BlanchedAlmond',
            'Blue',
            'BlueViolet',
            'Brown',
            'BurlyWood',
            'CadetBlue',
            'Chartreuse',
            'Chocolate',
            'Coral',
            'CornflowerBlue',
            'Cornsilk',
            'Crimson',
            'Cyan',
            'DarkBlue',
            'DarkCyan',
            'DarkGoldenRod',
            'DarkGray',
            'DarkGrey',
            'DarkGreen',
            'DarkKhaki',
            'DarkMagenta',
            'DarkOliveGreen',
            'DarkOrange',
            'DarkOrchid',
            'DarkRed',
            'DarkSalmon',
            'DarkSeaGreen',
            'DarkSlateBlue',
            'DarkSlateGray',
            'DarkSlateGrey',
            'DarkTurquoise',
            'DarkViolet',
            'DeepPink',
            'DeepSkyBlue',
            'DimGray',
            'DimGrey',
            'DodgerBlue',
            'FireBrick',
            'FloralWhite',
            'ForestGreen',
            'Fuchsia',
            'Gainsboro',
            'GhostWhite',
            'Gold',
            'GoldenRod',
            'Gray',
            'Grey',
            'Green',
            'GreenYellow',
            'HoneyDew',
            'HotPink',
            'IndianRed',
            'Indigo',
            'Ivory',
            'Khaki',
            'Lavender',
            'LavenderBlush',
            'LawnGreen',
            'LemonChiffon',
            'LightBlue',
            'LightCoral',
            'LightCyan',
            'LightGoldenRodYellow',
            'LightGray',
            'LightGrey',
            'LightGreen',
            'LightPink',
            'LightSalmon',
            'LightSeaGreen',
            'LightSkyBlue',
            'LightSlateGray',
            'LightSlateGrey',
            'LightSteelBlue',
            'LightYellow',
            'Lime',
            'LimeGreen',
            'Linen',
            'Magenta',
            'Maroon',
            'MediumAquaMarine',
            'MediumBlue',
            'MediumOrchid',
            'MediumPurple',
            'MediumSeaGreen',
            'MediumSlateBlue',
            'MediumSpringGreen',
            'MediumTurquoise',
            'MediumVioletRed',
            'MidnightBlue',
            'MintCream',
            'MistyRose',
            'Moccasin',
            'NavajoWhite',
            'Navy',
            'OldLace',
            'Olive',
            'OliveDrab',
            'Orange',
            'OrangeRed',
            'Orchid',
            'PaleGoldenRod',
            'PaleGreen',
            'PaleTurquoise',
            'PaleVioletRed',
            'PapayaWhip',
            'PeachPuff',
            'Peru',
            'Pink',
            'Plum',
            'PowderBlue',
            'Purple',
            'RebeccaPurple',
            'Red',
            'RosyBrown',
            'RoyalBlue',
            'SaddleBrown',
            'Salmon',
            'SandyBrown',
            'SeaGreen',
            'SeaShell',
            'Sienna',
            'Silver',
            'SkyBlue',
            'SlateBlue',
            'SlateGray',
            'SlateGrey',
            'Snow',
            'SpringGreen',
            'SteelBlue',
            'Tan',
            'Teal',
            'Thistle',
            'Tomato',
            'Turquoise',
            'Violet',
            'Wheat',
            'White',
            'WhiteSmoke',
            'Yellow',
            'YellowGreen'
        ];

        if (in_array(strtolower($color), array_map('strtolower', $colornames))) {
            return $color;
        }

        $pattern = '/^\s*(
            (\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}))|        #colorvalue
            (rgb\(([0-9]{1,3}%?,){2}[0-9]{1,3}%?\))     #rgb triplet
            )\s*$/x';

        if (preg_match($pattern, $color)) {
            return trim($color);
        }

        return false;
    }

    /**
     * Localized error helper with ARIA for screen readers.
     */
    public function err(string $langKey, array $sprintfArgs = []): string
    {
        $txt = $this->getLang($langKey) ?? $langKey;
        if ($sprintfArgs) {
            $sprintfArgs = array_map('hsc', $sprintfArgs);
            $txt         = vsprintf($txt, $sprintfArgs);
        } else {
            $txt = hsc($txt);
        }

        return '<div class="plugin_dwtimeline_error" role="status" aria-live="polite">'
            . $txt
            . '</div>';
    }

    /**
     * Return a human-friendly page title for $id.
     * 1) metadata title
     * 2) first heading (if available)
     * 3) pretty formatted ID with namespaces (e.g. "Ns › Sub › Page")
     */
    public function prettyId(string $id): string
    {
        // 1) meta title, if exist
        $metaTitle = p_get_metadata($id, 'title');
        if (is_string($metaTitle) && $metaTitle !== '') {
            return $metaTitle;
        }

        // 2) First header
        if (function_exists('p_get_first_heading')) {
            $h = p_get_first_heading($id);
            if (is_string($h) && $h !== '') {
                return $h;
            }
        }

        // 3) fallback: path to page
        $parts = explode(':', $id);
        foreach ($parts as &$p) {
            $p = str_replace('_', ' ', $p);
            $p = mb_convert_case($p, MB_CASE_TITLE, 'UTF-8');
        }
        return implode(' › ', $parts);
    }

    /**
     * Quote a value for wiki-style plugin attributes.
     * Prefers "..." if possible, then '...'. If both quote types occur,
     * wrap with " and escape inner \" and \\ (the parser will unescape them).
     */
    public function quoteAttrForWiki(string $val): string
    {
        if (strpos($val, '"') === false) {
            return '"' . $val . '"';
        }
        if (strpos($val, "'") === false) {
            return "'" . $val . "'";
        }

        // contains both ' and " -> escape for double-quoted
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $val);
        return '"' . $escaped . '"';
    }

    /**
     * Return the index (byte offset) directly after the end of the line containing $pos.
     */
    public function lineEndAt(string $text, int $pos, int $len): int
    {
        if ($pos < 0) {
            return 0;
        }
        $nl = strpos($text, "\n", $pos);
        return ($nl === false) ? $len : ($nl + 1);
    }

    /**
     * Return the start index (byte offset) of the line containing $pos.
     */
    public function lineStartAt(string $text, int $pos): int
    {
        if ($pos <= 0) {
            return 0;
        }
        $before = substr($text, 0, $pos);
        $nl     = strrpos($before, "\n");
        return ($nl === false) ? 0 : ($nl + 1);
    }

    /**
     * Cut a section [start, end) from $text and rtrim it on the right side.
     */
    public function cutSection(string $text, int $start, int $end): string
    {
        if ($start < 0) {
            $start = 0;
        }
        if ($end < $start) {
            $end = $start;
        }
        return rtrim(substr($text, $start, $end - $start));
    }
}
