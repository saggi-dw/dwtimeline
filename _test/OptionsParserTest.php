<?php

namespace dokuwiki\plugin\dwtimeline\test;

use DokuWikiTest;
use syntax_plugin_dwtimeline_dwtimeline;

class OptionsParserTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['dwtimeline'];

    public function test_quoted_and_unquoted_values()
    {
        $sx   = new syntax_plugin_dwtimeline_dwtimeline();
        $data = $sx->getTitleMatches('title="Release 2025-05-14" align=vert data=2025');

        $this->assertSame(hsc('Release 2025-05-14'), $data['title']);
        $this->assertSame('vert', $data['align']);
        $this->assertStringContainsString('data-point="2025"', $data['data']);
    }

    public function test_values_with_quotes_and_escapes()
    {
        $sx = new syntax_plugin_dwtimeline_dwtimeline();
        // test quotes
        $data = $sx->getTitleMatches('title="He said \"Hi\" & it\'s fine"');

        $this->assertSame(hsc('He said "Hi" & it\'s fine'), $data['title']);
    }

    public function test_flag_and_empty_value()
    {
        $sx   = new syntax_plugin_dwtimeline_dwtimeline();
        $data = $sx->getTitleMatches("foo style=''");

        $this->assertArrayHasKey('foo', $data);
        $this->assertSame('', $data['foo']);
        $this->assertSame('', $data['style']); // style is empty
    }

    public function test_internal_external_interwiki_email_links()
    {
        $sx = new syntax_plugin_dwtimeline_dwtimeline();

        // intern
        $this->assertSame('wiki:start', $sx->getLink('[[wiki:start|Go]]'));
        // extern (bracketed)
        $this->assertSame('https://example.com', $sx->getLink('[[https://example.com|x]]'));
        // raw URL (fallback)
        $this->assertSame('https://example.com', $sx->getLink('See https://example.com now'));
        // interwiki
        $this->assertSame('doku>interwiki', $sx->getLink('[[doku>interwiki]]'));
        // email
        $this->assertSame('mailto:test@example.com', $sx->getLink('test@example.com'));
    }

    public function test_regression_unmatched_groups_do_not_warn()
    {
        $sx   = new syntax_plugin_dwtimeline_dwtimeline();
        $data = $sx->getTitleMatches('title=foo bar');
        $this->assertSame(hsc('foo'), $data['title']);
        $this->assertArrayHasKey('bar', $data);
        $this->assertSame('', $data['bar']);
    }

    public function test_interwiki_in_milestone_title()
    {
        saveWikiText(
            'playground:src',
            '====== T ======
===== M =====',
            'setup'
        );

        $html = p_render(
            'xhtml',
            p_get_instructions('<milestone title="X" link="[[doku>interwiki]]"></milestone>'),
            $info
        );

        $this->assertStringContainsString('class="interwiki iw_doku"', $html);
    }
}
