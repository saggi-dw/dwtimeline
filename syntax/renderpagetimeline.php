<?php

/**
 * DokuWiki Plugin dwtimeline (Syntax Component: renderpage timeline)
 * Renders <dwtimeline page=ns:page /> by reusing the plugin's own markup.
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi
 */

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\File\PageResolver;

class syntax_plugin_dwtimeline_renderpagetimeline extends SyntaxPlugin
{
    public function getType()
    {
        return 'substition';
    }

    public function getPType()
    {
        return 'block';
    }

    public function getSort()
    {
        return 400;
    }

    /**
     * Recognize: <dwtimeline page=ns:page />
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(
            '<dwtimeline\s+page\s*=\s*(?:"[^"]*"|[^\s/>]+)\s*/>',
            $mode,
            'plugin_dwtimeline_renderpagetimeline'
        );
    }

    /**
     * Extract and resolve the target page id.
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        if (preg_match('/page\s*=\s*"([^"]*)"/i', $match, $m)) {
            $raw = trim($m[1]);
        } elseif (preg_match('/page\s*=\s*([^\s\/>]+)/i', $match, $m)) {
            $raw = trim($m[1]);
        } else {
            $raw = '';
        }

        $id = cleanID((string)$raw);

        global $ID;
        $resolver = new PageResolver($ID);
        $id       = $resolver->resolveId($id); // may resolve to a non-existing page (by design)

        return [
            'id'  => $id,
            'pos' => $pos,
        ];
    }

    /**
     * Render metadata (references) and XHTML output.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        // --- METADATA: persist backlink/reference only here ---
        if ($mode === 'metadata') {
            global $ID;
            $target = $data['id'] ?? '';
            if ($target && $target !== $ID && page_exists($target)) {
                if (!isset($renderer->meta['relation']['references'])) {
                    $renderer->meta['relation']['references'] = [];
                }
                $renderer->meta['relation']['references'][] = $target;
            }
            return true;
        }

        if ($mode !== 'xhtml') {
            return false;
        }

        global $ID;

        $target = $data['id'] ?? '';
        if ($target === '') {
            $renderer->doc .= $this->err('rp_missing_id');
            return true;
        }

        // Permission first (avoid existence leaks)
        if (auth_quickaclcheck($target) < AUTH_READ) {
            $renderer->doc .= $this->err('rp_no_acl', [$target]);
            return true;
        }

        // Existence check
        if (!page_exists($target)) {
            $renderer->doc .= $this->err('rp_not_found', [$target]);
            return true;
        }

        // Self-include guard
        if ($target === $ID) {
            $renderer->doc .= $this->err('rp_same', [$target]);
            return true;
        }

        // Cache dependency on the source page (so changes there invalidate this page)
        $renderer->info['depends']['pages'][] = $target;

        // Read source wikitext
        $wikitext = rawWiki($target);
        if ($wikitext === null) {
            $renderer->doc .= $this->err('rp_not_found', [$target]);
            return true;
        }

        // Parse instructions (headers provide [text, level, pos])
        $instr = p_get_instructions($wikitext);

        // Collect headers
        $headers = [];
        foreach ($instr as $idx => $ins) {
            if ($ins[0] !== 'header') {
                continue;
            }
            $text      = $ins[1][0] ?? '';
            $level     = (int)($ins[1][1] ?? 0);
            $pos       = (int)($ins[1][2] ?? -1); // may be -1 on older DW versions
            $headers[] = ['idx' => $idx, 'text' => $text, 'level' => $level, 'pos' => $pos];
        }

        // Determine timeline title: prefer first H1, fallback to first header, then prettyId
        $titleHdr = null;
        foreach ($headers as $h) {
            if ($h['level'] === 1) {
                $titleHdr = $h;
                break;
            }
        }
        if ($titleHdr === null && !empty($headers)) {
            $titleHdr = $headers[0];
        }

        $title      = $titleHdr ? trim($titleHdr['text']) : $this->prettyId($target);
        $titleLevel = $titleHdr ? (int)$titleHdr['level'] : 1;
        $milLevel   = $titleLevel + 1;

        // Build synthetic <dwtimeline> markup using existing plugin tags
        $align     = (string)$this->getConf('align');
        $synthetic = $this->buildSyntheticTimeline($wikitext, $headers, $title, $milLevel, $align);

        // Render the synthetic markup through DokuWiki (uses your existing syntax classes)
        $info = [];
        $html = p_render('xhtml', p_get_instructions($synthetic), $info);

        // Output + small source link
        $renderer->doc .= $html;

        $info2         = [];
        $renderer->doc .= p_render('xhtml', p_get_instructions('[[' . $target . ']]'), $info2);

        return true;
    }

    /**
     * Localized error helper with ARIA for screen readers.
     */
    private function err(string $langKey, array $sprintfArgs = []): string
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
     * Pretty-print a page id for human-readable display.
     */
    private function prettyId(string $id): string
    {
        $parts = explode(':', $id);
        $parts = array_map(function ($p) {
            $p = str_replace('_', ' ', $p);
            return mb_convert_case($p, MB_CASE_TITLE, 'UTF-8');
        }, $parts);
        return implode(' › ', $parts);
    }

    /**
     * Build the synthetic DokuWiki markup for the timeline using the plugin's own tags.
     *
     * @param string $wikitext Raw wikitext of the source page
     * @param array  $headers  List of headers: ['text'=>string,'level'=>int,'pos'=>int]
     * @param string $title    Timeline title (from H1 or fallback)
     * @param int    $milLevel Milestone level (titleLevel + 1, typically H2)
     * @param string $align    Alignment taken from plugin config
     * @return string           Complete <dwtimeline ...> ... </dwtimeline> markup
     */
    private function buildSyntheticTimeline(
        string $wikitext,
        array $headers,
        string $title,
        int $milLevel,
        string $align
    ): string {
        $len       = strlen($wikitext); // byte-based; header positions are byte offsets
        $synthetic = '<dwtimeline align="' . hsc($align) . '" title="' . hsc($title) . '">' . DOKU_LF;

        $count = count($headers);
        for ($i = 0; $i < $count; $i++) {
            $h = $headers[$i];
            if (($h['level'] ?? null) !== $milLevel) {
                continue;
            }

            // If positions are not available, we cannot safely cut body text; emit empty content
            if (!isset($h['pos']) || $h['pos'] < 0) {
                $synthetic .= '<milestone title="' . hsc(trim((string)$h['text'])) . '" data="' . $i . '">' . DOKU_LF
                    . '</milestone>' . DOKU_LF;
                continue;
            }

            // Start right after the milestone header line
            $start = $this->lineEndAt($wikitext, (int)$h['pos'], $len);

            // End at the line start of the next header with level <= $milLevel (or EOF)
            $end = $len;
            for ($j = $i + 1; $j < $count; $j++) {
                $hn = $headers[$j];
                if (($hn['level'] ?? PHP_INT_MAX) <= $milLevel) {
                    $nextPos = (int)($hn['pos'] ?? -1);
                    // If next header position is missing, fall back to EOF
                    if ($nextPos >= 0) {
                        $end = $this->lineStartAt($wikitext, $nextPos);
                    }
                    break;
                }
            }

            $section = $this->cutSection($wikitext, $start, $end);

            // Emit milestone with title attribute and body content
            $synthetic .= '<milestone title="' . hsc(trim((string)$h['text'])) . '" data="' . $i . '">' . DOKU_LF
                . $section . DOKU_LF
                . '</milestone>' . DOKU_LF;
        }

        $synthetic .= '</dwtimeline>' . DOKU_LF;
        return $synthetic;
    }

    /**
     * Return the index (byte offset) directly after the end of the line containing $pos.
     */
    private function lineEndAt(string $text, int $pos, int $len): int
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
    private function lineStartAt(string $text, int $pos): int
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
     * Note: $start/$end are byte offsets (keep substr, not mb_substr).
     */
    private function cutSection(string $text, int $start, int $end): string
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
