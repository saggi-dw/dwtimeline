<?php
/**
 * DokuWiki Plugin dwtimeline (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

class helper_plugin_dwtimeline extends DokuWiki_Plugin {
    
    /**
     * Global direction memory
     * @var type
     */
    protected $direction;
    
    /**
     * Change the current content of $direction String (left,right)
     * @param type $direction 
     * @return string
     */
    public static function getDirection($direction) 
    {
        if($direction === 'right'){
            $direction = 'left';
            }
        else {
                $direction = 'right';
            }
        return $direction;
    }
    
    /**
     * Match the options of a entity e.g. <dwtimeline opt1="value1" opt2="value2">
     * @param type $match the cleaned option String: 'opt1="value1" opt2="value2"'
     * @param type $boxtitle special identifier for the title-tag
     * @return type
     */
    public static function getTitleMatches($match)
    {
        $data = [];
        $titles=[];
        preg_match_all('/(?<title>\w+?\b=".*?")/',$match,$titles);
        foreach ($titles['title'] as $title) {
            $opttitle = explode('=',$title,2);
            switch(trim($opttitle[0]))
            {
                case 'link':
                    $data['link'] = helper_plugin_dwtimeline::getLink(trim($opttitle[1],' "'));
                    break;
                default :
                    $data[$opttitle[0]] = hsc(trim($opttitle[1],' "'));
                    break;
            }
        }
        return $data;
    }
    
    /**
     * Check and get the link from given DokuWiki Link
     * @param type $linkToCheck
     * @return type
     */
    public static function getLink($linkToCheck) {
       $pattern = '/\[\[(?<link>.+?)\]\]/';
       $links = [];
       preg_match_all($pattern, $linkToCheck,$links);
       foreach ($links['link'] as $link) {
           return hsc(substr($link,0,strpos($link,'|')));
       }        
    }
}
