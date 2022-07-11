<?php
/**
 * DokuWiki Plugin dwtimeline (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_dwtimeline extends DokuWiki_Plugin {
    
    /**
     * Global direction memory
     * @var type
     */
    protected $direction;
    protected $align;
    
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
        global $align;
        $data['align'] = $align; // Set Standard Alignment
        $data['backcolor'] = '';
        $data['data'] = '';
        preg_match_all('/(?<title>\w+?\b=".*?")/',$match,$titles);
        foreach ($titles['title'] as $title) {
            $opttitle = explode('=',$title,2);
            switch(trim($opttitle[0]))
            {
                case 'link':
                    $data['link'] = self::getLink(trim($opttitle[1],' "'));
                    break;
                case 'data':
                    $data[$opttitle[0]] = ' data-point="'.hsc(substr(trim($opttitle[1],' "'),0,2)).'" ';
                    break;                
                case 'align':
                    $data[$opttitle[0]] = self::checkValues(hsc(trim($opttitle[1],' "')),array("horz", "vert") , $align);
                    break;
                case 'backcolor':
                    if(!self::isValidColor(hsc(trim($opttitle[1],' "')))) { break;}
                    $data[$opttitle[0]] = ' style="background-color:'.self::isValidColor(hsc(trim($opttitle[1],' "'))).';" ';
                    break;                
                default :
                    $data[$opttitle[0]] = hsc(trim($opttitle[1],' "'));
                    break;
            }
        }
        $align = $data['align'];
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
    
    public static function checkValues($toCheck,$allowed,$standard) {
        if (in_array($toCheck, $allowed, true)) {
            return $toCheck;
        } else {
            return $standard;
        }        
    }

    /**
     * Validate color value $color
     * this is cut price validation - only to ensure the basic format is correct and there is nothing harmful
     * three basic formats  "colorname", "#fff[fff]", "rgb(255[%],255[%],255[%])"
     */
    Public static function isValidColor($color) {
        $color = trim($color);
        $COLOR_NAMES = ["AliceBlue","AntiqueWhite","Aqua","Aquamarine","Azure","Beige","Bisque","Black",
            "BlanchedAlmond","Blue","BlueViolet","Brown","BurlyWood","CadetBlue","Chartreuse","Chocolate",
            "Coral","CornflowerBlue","Cornsilk","Crimson","Cyan","DarkBlue","DarkCyan","DarkGoldenRod",
            "DarkGray","DarkGrey","DarkGreen","DarkKhaki","DarkMagenta","DarkOliveGreen","DarkOrange",
            "DarkOrchid","DarkRed","DarkSalmon","DarkSeaGreen","DarkSlateBlue","DarkSlateGray","DarkSlateGrey",
            "DarkTurquoise","DarkViolet","DeepPink","DeepSkyBlue","DimGray","DimGrey","DodgerBlue","FireBrick",
            "FloralWhite","ForestGreen","Fuchsia","Gainsboro","GhostWhite","Gold","GoldenRod","Gray","Grey",
            "Green","GreenYellow","HoneyDew","HotPink","IndianRed","Indigo","Ivory","Khaki","Lavender",
            "LavenderBlush","LawnGreen","LemonChiffon","LightBlue","LightCoral","LightCyan","LightGoldenRodYellow",
            "LightGray","LightGrey","LightGreen","LightPink","LightSalmon","LightSeaGreen","LightSkyBlue",
            "LightSlateGray","LightSlateGrey","LightSteelBlue","LightYellow","Lime","LimeGreen","Linen","Magenta",
            "Maroon","MediumAquaMarine","MediumBlue","MediumOrchid","MediumPurple","MediumSeaGreen","MediumSlateBlue",
            "MediumSpringGreen","MediumTurquoise","MediumVioletRed","MidnightBlue","MintCream","MistyRose","Moccasin",
            "NavajoWhite","Navy","OldLace","Olive","OliveDrab","Orange","OrangeRed","Orchid","PaleGoldenRod",
            "PaleGreen","PaleTurquoise","PaleVioletRed","PapayaWhip","PeachPuff","Peru","Pink","Plum","PowderBlue",
            "Purple","RebeccaPurple","Red","RosyBrown","RoyalBlue","SaddleBrown","Salmon","SandyBrown","SeaGreen",
            "SeaShell","Sienna","Silver","SkyBlue","SlateBlue","SlateGray","SlateGrey","Snow","SpringGreen","SteelBlue",
            "Tan","Teal","Thistle","Tomato","Turquoise","Violet","Wheat","White","WhiteSmoke","Yellow","YellowGreen"];

        if (in_array($color, $COLOR_NAMES)) {
            return trim($color);
        }
        
        $pattern = "/^\s*(
            (\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}))|        #colorvalue
            (rgb\(([0-9]{1,3}%?,){2}[0-9]{1,3}%?\))     #rgb triplet
            )\s*$/x";
 
        if (preg_match($pattern, $color)) {
            return trim($color);
        }
        
        return false;
    }
   
}
