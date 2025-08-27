<?php

/**
 * DokuWiki Plugin dwtimeline (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  saggi <saggi@gmx.de>
 */

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

class action_plugin_dwtimeline extends ActionPlugin
{
    /**
     * Register the eventhandlers
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insertButton', []);
    }

    /**
     * Inserts the toolbar button
     */
    public function insertButton(Event $event, $param)
    {
        $event->data[] = [
            'type'  => 'picker',
            'title' => $this->getLang('tl-picker'),
            'icon'  => '../../plugins/dwtimeline/icons/timeline_picker.png',
            'list'  => [
                [
                    'type'   => 'format',
                    'title'  => $this->getLang('tl-button'),
                    'icon'   => '../../plugins/dwtimeline/icons/timeline_marker.png',
                    'open'   => $this->buildSkeleton('complete'),
                    'sample' => $this->getLang('ms-content'),
                    'close'  => '\n</milestone>\n</dwtimeline title="' . $this->getLang('tl-end') . '">',
                ],
                [
                    'type'   => 'format',
                    'title'  => $this->getLang('ms-button'),
                    'icon'   => '../../plugins/dwtimeline/icons/page_white_code.png',
                    'open'   => $this->buildSkeleton('milestone'),
                    'sample' => $this->getLang('ms-content'),
                    'close'  => '\n</milestone>',
                ],
            ]
        ];
    }

    private function buildSkeleton($skeletontype, $data = null)
    {
        switch ($skeletontype) {
            case 'complete':
                $skeleton = '<dwtimeline align="' . $this->getConf('align') . '" title="';
                $skeleton .= $this->getLang('tl-title') . '" description="' . $this->getLang('tl-desc') . '">\n';
                $skeleton .= $this->buildSkeleton('milestone');
                $skeleton .= $this->getLang('ms-content') . '\n';
                $skeleton .= '</milestone>\n';
                $skeleton .= $this->buildSkeleton('milestone', '02');
                break;
            case 'milestone':
                if (!$data) {
                    $data = $this->getLang('ms-data');
                }
                $skeleton = '<milestone title="' . $this->getLang('ms-title') . '" description="';
                $skeleton .= $this->getLang('ms-desc') . '" ';
                $skeleton .= 'data="' . $data . '" backcolor="' . $this->getLang('ms-backcolor') . '">\n';
                break;
            default:
                $skeleton = '<dwtimeline title="' . $this->getLang('tl-title') . '" description="';
                $skeleton .= $this->getLang('tl-desc') . '">\n';
                $skeleton .= $this->buildSkeleton('milestone');
                break;
        }
        return $skeleton;
    }
}
