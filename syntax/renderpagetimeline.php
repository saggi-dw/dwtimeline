<?php

/**
 * DokuWiki Plugin dwtimeline (Syntax Component: renderpage timeline)
 * Renders <dwtimeline page=ns:page /> by reusing the plugin's own markup.
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi
 */

use dokuwiki\File\PageResolver;

class syntax_plugin_dwtimeline_renderpagetimeline extends syntax_plugin_dwtimeline_dwtimeline
{
    public function getPType()
    {
        return 'block';
    }

    /**
     * Recognize: <dwtimeline page=ns:page />
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(
            '<dwtimeline\s+page\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s/>]+)\s*/>',
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

        $id = cleanID($raw);

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
        if ($titleHdr === null && $headers !== []) {
            $titleHdr = $headers[0];
        }

        $title      = $titleHdr ? trim($titleHdr['text']) : $this->prettyId($target);
        $titleLevel = $titleHdr ? $titleHdr['level'] : 1;
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
     * Build the synthetic DokuWiki markup for the timeline using the plugin's own tags.
     *
     * @param string $wikitext Raw wikitext of the source page
     * @param array  $headers  List of headers: ['text'=>string,'level'=>int,'pos'=>int]
     * @param string $title    Timeline title (from H1 or fallback)
     * @param int    $milLevel Milestone level (titleLevel + 1, typically H2)
     * @param string $align    Alignment taken from plugin config
     * @return string           Complete <dwtimeline ...> ... </dwtimeline> markup
     */
    public function buildSyntheticTimeline(
        string $wikitext,
        array $headers,
        string $title,
        int $milLevel,
        string $align
    ): string {
        $len       = strlen($wikitext); // byte-based; header positions are byte offsets
        $synthetic = '<dwtimeline align="' . $align . '" title=' . $this->quoteAttrForWiki($title) . '>' . DOKU_LF;

        $count = count($headers);
        for ($i = 0; $i < $count; $i++) {
            $h = $headers[$i];
            if (($h['level'] ?? null) !== $milLevel) {
                continue;
            }

            // If positions are not available, we cannot safely cut body text; emit empty content
            if (!isset($h['pos']) || $h['pos'] < 0) {
                $synthetic .= '<milestone title=';
                $synthetic .= $this->quoteAttrForWiki((string)$h['text']);
                $synthetic .= ' data="' . $i . '">' . DOKU_LF;
                $synthetic .= '</milestone>' . DOKU_LF;
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
            $synthetic .= '<milestone title=';
            $synthetic .= $this->quoteAttrForWiki((string)$h['text']);
            $synthetic .= ' data="' . $i . '">' . DOKU_LF;
            $synthetic .= $section . DOKU_LF;
            $synthetic .= '</milestone>' . DOKU_LF;
        }

        $synthetic .= '</dwtimeline>' . DOKU_LF;
        return $synthetic;
    }
}
