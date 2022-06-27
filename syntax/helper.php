<?php
/**
 * DokuWiki Plugin dwtimeline (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

class helper_plugin_dwtimeline_syntax extends DokuWiki_Plugin {
    
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
     * @param type $match the clened option String: 'opt1="value1" opt2="value2"'
     * @param type $boxtitle special identifier for the title-tag
     * @return type
     */
    public static function getTitleMatches($match,$boxtitle)
    {
        $data = [];
        $titles=[];
        preg_match_all('/(?<title>\w+?\b=".*?")/',$match,$titles);
        foreach ($titles['title'] as $title) {
            $opttitle = explode('=',$title,2);
            switch(trim($opttitle[0]))
            {
                case $boxtitle:
                    $data[$boxtitle] = hsc(trim($opttitle[1],' "'));
                    break;
                case 'description':
                    $data['description'] = hsc(trim($opttitle[1],' "'));
                    break;
                case 'link':
                    $data['link'] = hsc(trim($opttitle[1],' "'));
                    break;                
            }
        }
        return $data;
    }
    
    /**
     * Like function getTitleMatches, but returns in a given array
     * @param type $match
     * @param type $cnt
     * @param type $data
     * @return type
     */    
    public static function getEntryTitleMatches($match,$cnt,$data)
    {
        $titles=[];
        preg_match_all('/(?<title>\w+?\b=".*?")/',$match,$titles);
        foreach ($titles['title'] as $title) {
            $opttitle = explode('=',$title,2);
            switch(trim($opttitle[0]))
            {
                case 'title':
                    $data['entries'][$cnt]['title'] = hsc(trim($opttitle[1],' "'));
                    break;
                case 'description':
                    $data['entries'][$cnt]['description'] = hsc(trim($opttitle[1],' "'));
                    break;
                case 'link':
                    $data['entries'][$cnt]['link'] = helper_plugin_dwtimeline_syntax::getLink(trim($opttitle[1],' "'));
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
           return substr($link,0,strpos($link,'|'));
       }        
    }
    
    public static function array_keys_recursive($myArray, $MAXDEPTH = INF, $depth = 0, $arrayKeys = array()){
           if($depth < $MAXDEPTH){
                $depth++;
                $keys = array_keys($myArray);
                foreach($keys as $key){
                    if(is_array($myArray[$key])){
                        $arrayKeys[$key] = array_keys_recursive($myArray[$key], $MAXDEPTH, $depth);
                    }
                }
            }

            return $arrayKeys;
    }    
}
