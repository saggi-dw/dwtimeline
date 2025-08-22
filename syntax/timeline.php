<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

class syntax_plugin_dwtimeline_timeline extends syntax_plugin_dwtimeline_dwtimeline
{

    /**
     * @return array Things that may be inside the syntax
     */
    function getAllowedTypes()
    {
        return array('plugin_dwtimeline_milestone');
    }

    /**
     * Set the EntryPattern
     * @param string $mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern(
            '<dwtimeline\b.*?>(?=.*?</dwtimeline\b.*?>)',
            $mode,
            'plugin_dwtimeline_timeline'
        );
    }

    /**
     * Set the ExitPattern
     */
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</dwtimeline\b.*?>', 'plugin_dwtimeline_timeline');
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
        switch ($state) {
            case DOKU_LEXER_ENTER :
                parent::$align = $this->getConf('align');
                $match         = trim(substr($match, 11, -1));// returns match between <dwtimeline(11) and >(-1)
                $data          = $this->getTitleMatches($match);
                parent::$align = $data['align'];
                return [$state, $data];
            case DOKU_LEXER_UNMATCHED :
                return [$state, $match];
            case DOKU_LEXER_EXIT :
                $match = trim(substr($match, 12, -1));//returns match between </dwtimeline(12) and >(-1)
                $data  = $this->getTitleMatches($match);
                return [$state, $data];
        }
        return [];
    }

    /**
     * Create output
     *
     * @param string        $mode     string     output format being rendered
     * @param Doku_Renderer $renderer the current renderer object
     * @param array         $data     data created by handler()
     * @return  boolean                 rendered correctly?
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml') {
            if (!parent::$direction) {
                parent::$direction = $this->GetDirection();
            }
            list($state, $indata) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= '<div class="dwtimeline">' . DOKU_LF;
                    if ($indata['align'] === 'horz') {
                        $renderer->doc .= '<div class="timeline-' . $indata['align'] . '-line"></div>' . DOKU_LF;
                    }
                    $renderer->doc .= '<div class="timeline-' . $indata['align'] . '">' . DOKU_LF;
                    if (isset($indata['title']) or isset($indata['description'])) {
                        $renderer->doc .= '<div class="container-' . $indata['align'] . ' tl-top">' . DOKU_LF;
                        $renderer->doc .= '<div class="tlcontent">' . DOKU_LF;
                        if (isset($indata['title'])) {
                            $renderer->doc .= '<div class="tltitle">' . $indata['title'] . '</div>' . DOKU_LF;
                        }
                        if (isset($indata['description'])) {
                            $renderer->doc .= '<p>' . DOKU_LF . $indata['description'] . DOKU_LF . '</p>' . DOKU_LF;
                        }
                        $renderer->doc .= '</div>' . DOKU_LF;
                        $renderer->doc .= '</div>' . DOKU_LF;
                    }
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->cdata($indata);
                    break;
                case DOKU_LEXER_EXIT :
                    if (isset($indata['title']) or isset($indata['description'])) {
                        $renderer->doc .= '<div class="container-' . $indata['align'] . ' tl-bottom">' . DOKU_LF;
                        $renderer->doc .= '<div class="tlcontent">' . DOKU_LF;
                        if (isset($indata['title'])) {
                            $renderer->doc .= '<div class="tltitle">' . $indata['title'] . '</div>' . DOKU_LF;
                        }
                        if (isset($indata['description'])) {
                            $renderer->doc .= '<p>' . $indata['description'] . '</p>' . DOKU_LF;
                        }
                        $renderer->doc .= '</div>' . DOKU_LF;
                        $renderer->doc .= '</div>' . DOKU_LF;
                        $renderer->doc .= '</div>' . DOKU_LF;
                    }
                    $renderer->doc     .= '</div>' . DOKU_LF;
                    parent::$direction = 'tl-' . $this->getConf('direction');//Reset direction
                    break;
            }
            return true;
        }
        return false;
    }

}

