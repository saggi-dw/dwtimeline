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
                $match = trim(substr($match, 11,-1));//
                $data = helper_plugin_dwtimeline_syntax::getTitleMatches($match, 'title');
                return array($state,$data);

            case DOKU_LEXER_UNMATCHED :  
                $milestones=[];
                preg_match_all('/(?<entry><milestone\b.*?>)(?<content>.*?)(?<exit><\/milestone>)/s',$match,$milestones, PREG_SET_ORDER);
                $cnt = -1;
                foreach ($milestones as $milestone) {
                    $cnt++;
                    $entry = substr($milestone['entry'],10,-1);
                    $data = helper_plugin_dwtimeline_syntax::getEntryTitleMatches($entry, $cnt, $data);
                    $data['entries'][$cnt]['content'] = $milestone['content'];
                }
                return array($state,$data);
                
            case DOKU_LEXER_EXIT :
                $match = trim(substr($match, 12,-1));//
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
                    $renderer->doc .= '<div class="timeline">';
                    if ($indata['title'] or $indata['description']) {
                        $renderer->doc .= '<div class="container top">';
                        $renderer->doc .= '<div class="content">';
                        if ($indata['title']) {$renderer->doc .= '<h2>'.$indata['title'].'</h2>';}
                        if ($indata['description']) {$renderer->doc .= $indata['description'];}
                         $renderer->doc .= '</div>';
                        $renderer->doc .= '</div>';
                    }
                    break;
                case DOKU_LEXER_UNMATCHED :
                    foreach($indata['entries'] as $entry)
                    {
                        $renderer->doc .= '<div class="container '.$direction.'">';
                        $renderer->doc .= '<div class="content">';
                        if ($entry['title']) {
                            if ($entry['link']) {
                               $renderer->doc .= '<h2>'.$this->render_text('[['.$entry['link'].'|'.$entry['title'].']]').'</h2>';
                            } else {
                                $renderer->doc .= '<h2>'.$this->render_text($entry['title']).'</h2>';
                            }                           
                        }
                        if ($entry['description']) {$renderer->doc .= '<h3>'.$entry['description'].'</h3>';}                
                        if ($entry['content']) {$renderer->doc .= $this->render_text($entry['content']);}
                        $renderer->doc .= '</div>';
                        $renderer->doc .= '</div>';
                        
                        $direction = helper_plugin_dwtimeline_syntax::getDirection($direction);

                    }
                    break;
                case DOKU_LEXER_EXIT :
                    if ($indata['title'] or $indata['description']) {
                        $renderer->doc .= '<div class="container bottom">';
                        $renderer->doc .= '<div class="content">';
                        if ($indata['title']) {$renderer->doc .= '<h2>'.$indata['title'].'</h2>';}
                        if ($indata['description']) {$renderer->doc .= $indata['description'];}
                        $renderer->doc .= '</div>';
                        $renderer->doc .= '</div>';
                    }

                    $renderer->doc .= '</div>';        
                   
                    break;
            }
            return true;        
        }
        return false;
    }
    
}

