<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */
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
        return 180;
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
        $this->Lexer->addEntryPattern('<milestone\b.*?>',$mode,'plugin_dwtimeline_milestone');/* (?=.*?</milestone>) */
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
                        $renderer->doc .= '<div class="container '.$direction.'">'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
                        if ($indata['title']) {
                            if ($indata['link']) {
                               $renderer->doc .= '<h2>'.$this->render_text('[['.$indata['link'].'|'.$indata['title'].']]').'</h2>'. DOKU_LF;
                            } else {
                                $renderer->doc .= '<h2>'.$indata['title'].'</h2>'. DOKU_LF;
                            }
                        }
                        if ($indata['description']) {$renderer->doc .= '<h3>'.$indata['description'].'</h3>'. DOKU_LF;}
                    break;
                    
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $renderer->_xmlEntities($indata);
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

