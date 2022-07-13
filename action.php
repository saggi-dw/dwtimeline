<?php
/**
 * DokuWiki Plugin dwtimeline (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_dwtimeline extends DokuWiki_Action_Plugin {

    /**
     * Register the eventhandlers
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
    }

    /**
     * Inserts the toolbar button
     */
    public function insert_button(Doku_Event $event, $param) {
        $event->data[] = array (
            'type' => 'format',
            'title' => $this->getLang('tl-button'),
            'icon' => '../../plugins/dwtimeline/icons/timeline_marker.png',
            'open' => $this->buildSkeleton(),
            'sample' => $this->getLang('ms-content'),
            'close' => '\n</milestone>\n</dwtimeline title="'.$this->getLang('tl-end').'">',
        );
    }
    
    private function buildSkeleton() {
        $skeleton = '';
        $skeleton .= '<dwtimeline title="'.$this->getLang('tl-title').'" description="'.$this->getLang('tl-desc').'">\n';
        $skeleton .= '<milestone title="'.$this->getLang('ms-title').'" description="'.$this->getLang('ms-desc').'" ';
        $skeleton .= 'data="'.$this->getLang('ms-data').'">\n';
        return $skeleton;
    }
            
    
}

