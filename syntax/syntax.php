<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */
class syntax_plugin_dwtimeline_syntax extends \dokuwiki\Extension\SyntaxPlugin
{
    
    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'normal';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 185;
    }

    /**
     * Set the EntryPattern
     * @param type $mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<dwtimeline\b.*?>(?=.*?</dwtimeline\b.*?>)',$mode,'plugin_dwtimeline_syntax');
    }

    /** @inheritDoc */

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</dwtimeline\b.*?>', 'plugin_dwtimeline_syntax');
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
                $match = trim(substr($match, 11,-1));// returns match between <dwtimeline(11) and >(-1)
                $data = helper_plugin_dwtimeline_syntax::getTitleMatches($match, 'title');
                return array($state,$data);

            case DOKU_LEXER_UNMATCHED :  
                $milestones=[];
                preg_match_all('/(?<entry><milestone\b.*?>)(?<content>.*?)(?<exit><\/milestone>)/s',$match,$milestones, PREG_SET_ORDER);
                $cnt = -1;
                foreach ($milestones as $milestone) {
                    $cnt++;
                    $entry = substr($milestone['entry'],10,-1);//returns match between <milestone(10) and >(-1)
                    $data = helper_plugin_dwtimeline_syntax::getEntryTitleMatches($entry, $cnt, $data);
                    $data['entries'][$cnt]['content'] = $milestone['content'];
                }
                return array($state,$data);
                
            case DOKU_LEXER_EXIT :
                $match = trim(substr($match, 12,-1));//returns match between </dwtimeline(12) and >(-1)
                $data = helper_plugin_dwtimeline_syntax::getTitleMatches($match, 'title');
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

            $direction = $this->getConf('direction');
            list($state,$indata) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    //dbg('State Enter');
                    $renderer->doc .= '<div class="timeline">'. DOKU_LF;
                    if ($indata['title'] or $indata['description']) {
                        $renderer->doc .= '<div class="container top">'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
                        if ($indata['title']) {$renderer->doc .= '<h2>'.$indata['title'].'</h2>'. DOKU_LF;}
                        if ($indata['description']) {$renderer->doc .= '<p>'.$indata['description'].'</p>'. DOKU_LF;}
                         $renderer->doc .= '</div>'. DOKU_LF;
                        $renderer->doc .= '</div>'. DOKU_LF;
                    }
                    break;
                case DOKU_LEXER_UNMATCHED :
                    foreach($indata['entries'] as $entry)
                    {
                        $renderer->doc .= '<div class="container '.$direction.'">'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
                        if ($entry['title']) {
                            if ($entry['link']) {
                               $renderer->doc .= '<h2>'.$this->render_text('[['.$entry['link'].'|'.$entry['title'].']]').'</h2>'. DOKU_LF;
                            } else {
                                $renderer->doc .= '<h2>'.$this->render_text($entry['title']).'</h2>'. DOKU_LF;
                            }                           
                        }
                        if ($entry['description']) {$renderer->doc .= '<h3>'.$entry['description'].'</h3>'. DOKU_LF;}                
                        if ($entry['content']) {$renderer->doc .= $this->render_text($entry['content']);}
                        $renderer->doc .= '</div>'. DOKU_LF;
                        $renderer->doc .= '</div>'. DOKU_LF;
                        
                        $direction = helper_plugin_dwtimeline_syntax::getDirection($direction);
                    }
                    break;
                case DOKU_LEXER_EXIT :
                    if ($indata['title'] or $indata['description']) {
                        $renderer->doc .= '<div class="container bottom">'. DOKU_LF;
                        $renderer->doc .= '<div class="content">'. DOKU_LF;
                        if ($indata['title']) {$renderer->doc .= '<h2>'.$indata['title'].'</h2>'. DOKU_LF;}
                        if ($indata['description']) {$renderer->doc .= '<p>'.$indata['description'].'</p>'. DOKU_LF;}
                        $renderer->doc .= '</div>'. DOKU_LF;
                        $renderer->doc .= '</div>'. DOKU_LF;
                    }

                    $renderer->doc .= '</div>'. DOKU_LF;        
                   
                    break;
            }
            return true;        
        }
        return false;
    }
    
}

