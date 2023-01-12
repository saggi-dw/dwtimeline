<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

use dokuwiki\plugin\dwtimeline\support\support;

class syntax_plugin_dwtimeline_milestone extends \dokuwiki\Extension\SyntaxPlugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'plugin_dwtimeline_milestone';
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

    function accepts($mode) {
        if ($mode == "plugin_dwtimeline_timeline") return true;
        return parent::accepts($mode);
    }

    /**
     * @return array Things that may be inside the syntax
     */
    function getAllowedTypes() {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * Set the EntryPattern
     * @param string $mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<milestone\b.*?>(?=.*?</milestone>)',$mode,'plugin_dwtimeline_milestone');
    }

    /**
     * Set the ExitPattern
     */
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</milestone>', 'plugin_dwtimeline_milestone');
    }

    /**
     * Handle the match
     * @param string $match The match of the syntax
     * @param int $state The state of the handler
     * @param int $pos The position in the document
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $data = [];
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $match = trim(substr($match, 10,-1));// returns match between <milestone(10) and >(-1)
                $data = support::getTitleMatches($match, 'title');
                global $align;
                $data['align'] = $align;
                return array($state,$data);
            case DOKU_LEXER_UNMATCHED :
                return array($state,$match);
            case DOKU_LEXER_EXIT :
                return array($state,'');
        }
        return array();
    }

    /**
     * Create output
     *
     * @param string $mode string     output format being rendered
     * @param Doku_Renderer $renderer the current renderer object
     * @param array $data data created by handler()
     * @return  boolean                 rendered correctly?
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml') {
            global $direction;
            if (!$direction) {$direction='tl-'.$this->getConf('direction');}
            list($state,$indata) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= '<div class="container-'.$indata['align'].' '.$direction.'"'.$indata['data'].$indata['style'].'>'. DOKU_LF;
                    $renderer->doc .= '<div class="tlcontent">'. DOKU_LF;
                    if (isset($indata['title'])) {
                        if (isset($indata['link'])) {
                            $renderer->doc .= '<div class="mstitle">'.$this->render_text('[['.$indata['link'].'|'.$indata['title'].']]').'</div>'. DOKU_LF;
                        } else {
                            $renderer->doc .= '<div class="mstitle">'.$indata['title'].'</div>'. DOKU_LF;
                        }
                    }
                    if (isset($indata['description'])) {$renderer->doc .= '<div class="msdesc">'.$indata['description'].'</div>'. DOKU_LF;}
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $renderer->cdata($indata);
                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</div>'. DOKU_LF;
                    $renderer->doc .= '</div>'. DOKU_LF;
                    $direction = support::getDirection($direction);
                    break;
            }
            return true;
        }
        return false;
    }

}

