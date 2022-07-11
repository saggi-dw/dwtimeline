<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_dwtimeline_milestone extends \dokuwiki\Extension\SyntaxPlugin
{
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
        return 320;
    }
    
    /**
     * @return array Things that may be inside the syntax
     */
    function getAllowedTypes() {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * Set the EntryPattern
     * @param type $mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<milestone\b.*?>',$mode,'plugin_dwtimeline_milestone');
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
     * @param type $match
     * @param type $state
     * @param type $pos
     * @param Doku_Handler $handler
     * @return type
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $data = [];
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $match = trim(substr($match, 10,-1));// returns match between <milestone(10) and >(-1)
                $data = helper_plugin_dwtimeline::getTitleMatches($match, 'title');
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
     * Render Function
     * @param type $mode
     * @param Doku_Renderer $renderer
     * @param type $data
     * @return boolean
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml') {

            global $direction;
            if (!$direction) {$direction=$this->getConf('direction');}
            list($state,$indata) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                        $renderer->doc .= '<div class="container-'.$indata['align'].' '.$direction.'"'.$indata['data'].$indata['backcolor'].'>'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
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
                    $direction = helper_plugin_dwtimeline::getDirection($direction);
                    break;
            }
            return true;        
        }
        return false;
    }
    
}

