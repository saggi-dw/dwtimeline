<?php
/**
 * DokuWiki Plugin dwtimeline (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

use dokuwiki\Extension\SyntaxPlugin;

class syntax_plugin_dwtimeline_dwtimeline extends SyntaxPlugin
{
    /**
     * Global direction memory
     * @var
     */
    protected static $direction;
    protected static $align;

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
        return 400;
    }

    /**
     * Change the current content of $direction String (left,right)
     * @param string $direction
     * @return string
     */
    public function ChangeDirection(string $direction): string
    {
        if ($direction === 'tl-right') {
            $direction = 'tl-left';
        } else {
            $direction = 'tl-right';
        }
        return $direction;
    }

    public function GetDirection()
    {
        if (!self::$direction) {
            self::$direction = 'tl-' . $this->getConf('direction');
        }
        return self::$direction;
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
        return array();
    }

    /**
     * Create output
     *
     * @param string        $mode     string     output format being rendered
     * @param Doku_Renderer $renderer the current renderer object
     * @param array         $data     data created by handler()
     * @return  bool                 rendered correctly?
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        return false;
    }

    /**
     * Match the options of a entity e.g. <dwtimeline opt1="value1" opt2="value2">
     * @param string $match the cleaned option String: 'opt1="value1" opt2="value2"'
     * @return array
     */
    public function getTitleMatches(string $match): array
    {
        $data[]        = [];
        $titles[]      = [];
        $data['align'] = self::$align; // Set Standard Alignment
        $data['data']  = '';
        $data['style'] = ' style="';
        preg_match_all('/(?<title>\w+?\b=".*?")/', $match, $titles);
        foreach ($titles['title'] as $title) {
            $optionvalue = sexplode('=', $title, 2, '');
            $option      = strtolower(trim($optionvalue[0]));
            $value       = hsc(trim($optionvalue[1], ' "'));
            switch ($option) {
                case 'link':
                    $data['link'] = $this->getLink($value);
                    break;
                case 'data':
                    $datapoint     = substr($value, 0, 4);
                    $data[$option] = ' data-point="' . $datapoint . '" ';
                    // Check if more than 2 signs present, if so set style for elliptic timeline marker
                    if (strlen($datapoint) > 2) {
                        $data['style'] .= '--4sizewidth: 50px; --4sizeright: -29px; --4sizesmallleft40: 60px; ';
                        $data['style'] .= '--4sizesmallleft50: 70px; --4sizesmallleft4: -10px; --4sizewidthhorz: 50px; --4sizerighthorz: -29px; ';
                    }
                    break;
                case 'align':
                    $data[$option] = $this->checkValues($value, array('horz', 'vert'), self::$align);
                    break;
                case 'backcolor':
                    $backcolor = $this->isValidColor($value);
                    if (!$backcolor) {
                        break;
                    }
                    $data['style'] .= 'background-color:' . $backcolor . '; ';
                    break;
                case 'style':
                    // do not accept custom styles at the moment
                    break;
                default:
                    $data[$option] = $value;
                    break;
            }
        }
        // Clear $data['style'] if no special style needed
        if ($data['style'] == ' style="') {
            $data['style'] = '';
        } else {
            $data['style'] .= '"';
        }
        return $data;
    }

    /**
     * Check and get the link from given DokuWiki Link
     * @param string $linkToCheck
     * @return string
     */
    public function getLink(string $linkToCheck): string
    {
        $pattern = '/\[\[(?<link>.+?)\]\]/';
        $links   = [];
        preg_match_all($pattern, $linkToCheck, $links);
        foreach ($links['link'] as $link) {
            return hsc(substr($link, 0, strpos($link, '|')));
        }
        return '';
    }

    public function checkValues($toCheck, $allowed, $standard)
    {
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
    public function isValidColor($color)
    {
        $color      = trim($color);
        $colornames = [
            'AliceBlue',
            'AntiqueWhite',
            'Aqua',
            'Aquamarine',
            'Azure',
            'Beige',
            'Bisque',
            'Black',
            'BlanchedAlmond',
            'Blue',
            'BlueViolet',
            'Brown',
            'BurlyWood',
            'CadetBlue',
            'Chartreuse',
            'Chocolate',
            'Coral',
            'CornflowerBlue',
            'Cornsilk',
            'Crimson',
            'Cyan',
            'DarkBlue',
            'DarkCyan',
            'DarkGoldenRod',
            'DarkGray',
            'DarkGrey',
            'DarkGreen',
            'DarkKhaki',
            'DarkMagenta',
            'DarkOliveGreen',
            'DarkOrange',
            'DarkOrchid',
            'DarkRed',
            'DarkSalmon',
            'DarkSeaGreen',
            'DarkSlateBlue',
            'DarkSlateGray',
            'DarkSlateGrey',
            'DarkTurquoise',
            'DarkViolet',
            'DeepPink',
            'DeepSkyBlue',
            'DimGray',
            'DimGrey',
            'DodgerBlue',
            'FireBrick',
            'FloralWhite',
            'ForestGreen',
            'Fuchsia',
            'Gainsboro',
            'GhostWhite',
            'Gold',
            'GoldenRod',
            'Gray',
            'Grey',
            'Green',
            'GreenYellow',
            'HoneyDew',
            'HotPink',
            'IndianRed',
            'Indigo',
            'Ivory',
            'Khaki',
            'Lavender',
            'LavenderBlush',
            'LawnGreen',
            'LemonChiffon',
            'LightBlue',
            'LightCoral',
            'LightCyan',
            'LightGoldenRodYellow',
            'LightGray',
            'LightGrey',
            'LightGreen',
            'LightPink',
            'LightSalmon',
            'LightSeaGreen',
            'LightSkyBlue',
            'LightSlateGray',
            'LightSlateGrey',
            'LightSteelBlue',
            'LightYellow',
            'Lime',
            'LimeGreen',
            'Linen',
            'Magenta',
            'Maroon',
            'MediumAquaMarine',
            'MediumBlue',
            'MediumOrchid',
            'MediumPurple',
            'MediumSeaGreen',
            'MediumSlateBlue',
            'MediumSpringGreen',
            'MediumTurquoise',
            'MediumVioletRed',
            'MidnightBlue',
            'MintCream',
            'MistyRose',
            'Moccasin',
            'NavajoWhite',
            'Navy',
            'OldLace',
            'Olive',
            'OliveDrab',
            'Orange',
            'OrangeRed',
            'Orchid',
            'PaleGoldenRod',
            'PaleGreen',
            'PaleTurquoise',
            'PaleVioletRed',
            'PapayaWhip',
            'PeachPuff',
            'Peru',
            'Pink',
            'Plum',
            'PowderBlue',
            'Purple',
            'RebeccaPurple',
            'Red',
            'RosyBrown',
            'RoyalBlue',
            'SaddleBrown',
            'Salmon',
            'SandyBrown',
            'SeaGreen',
            'SeaShell',
            'Sienna',
            'Silver',
            'SkyBlue',
            'SlateBlue',
            'SlateGray',
            'SlateGrey',
            'Snow',
            'SpringGreen',
            'SteelBlue',
            'Tan',
            'Teal',
            'Thistle',
            'Tomato',
            'Turquoise',
            'Violet',
            'Wheat',
            'White',
            'WhiteSmoke',
            'Yellow',
            'YellowGreen'
        ];

        if (in_array(strtolower($color), array_map('strtolower', $colornames))) {
            return $color;
        }

        $pattern = '/^\s*(
            (\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}))|        #colorvalue
            (rgb\(([0-9]{1,3}%?,){2}[0-9]{1,3}%?\))     #rgb triplet
            )\s*$/x';

        if (preg_match($pattern, $color)) {
            return trim($color);
        }

        return false;
    }

}

