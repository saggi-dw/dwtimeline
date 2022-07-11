<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class syntax_plugin_dwtimeline_timeline extends \dokuwiki\Extension\SyntaxPlugin
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
        $this->Lexer->addEntryPattern('<dwtimeline\b.*?>',$mode,'plugin_dwtimeline_timeline');
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
                global $align;
                $align = $this->getConf('align');    
                $match = trim(substr($match, 11,-1));// returns match between <dwtimeline(11) and >(-1)
                $data = helper_plugin_dwtimeline::getTitleMatches($match);
                return array($state,$data);
            case DOKU_LEXER_UNMATCHED :  
                return array ($state,$match);
            case DOKU_LEXER_EXIT :
                $match = trim(substr($match, 12,-1));//returns match between </dwtimeline(12) and >(-1)
                $data = helper_plugin_dwtimeline::getTitleMatches($match);
                return array($state,$data);
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
                    if ($indata['align'] === 'horz'){$renderer->doc .= '<div class="timeline-'.$indata['align'].'-line"></div>'. DOKU_LF;};
                    $renderer->doc .= '<div class="timeline-'.$indata['align'].'">'. DOKU_LF;
                    if (isset($indata['title']) or isset($indata['description'])) {
                        $renderer->doc .= '<div class="container-'.$indata['align'].' top">'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
                        if (isset($indata['title'])) {$renderer->doc .= '<div class="tltitle">'.$indata['title'].'</div>'. DOKU_LF;}
                        if (isset($indata['description'])) {$renderer->doc .= '<p>'.$indata['description'].'</p>'. DOKU_LF;}
                        $renderer->doc .= '</div>'. DOKU_LF;
                        $renderer->doc .= '</div>'. DOKU_LF;
                    }
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $renderer->cdata($indata);
                    break;
                case DOKU_LEXER_EXIT :
                    if (isset($indata['title']) or isset($indata['description'])) {
                        $renderer->doc .= '<div class="container-'.$indata['align'].' bottom">'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
                        if (isset($indata['title'])) {$renderer->doc .= '<div class="tltitle">'.$indata['title'].'</div>'. DOKU_LF;}
                        if (isset($indata['description'])) {$renderer->doc .= '<p>'.$indata['description'].'</p>'. DOKU_LF;}
                        $renderer->doc .= '</div>'. DOKU_LF;
                        $renderer->doc .= '</div>'. DOKU_LF;
                    }
                    $renderer->doc .= '</div>'. DOKU_LF;
                    $direction=$this->getConf('direction');//Reset direction
                    break;
            }
            return true;        
        }
        return false;
    }

}

