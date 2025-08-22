<?php

namespace dokuwiki\plugin\dwtimeline\test;

use DokuWikiTest;

/**
 * Tests for <dwtimeline page=... />
 *
 * @group plugin_dwtimeline
 * @group plugins
 */
class RenderPageTimelineTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['dwtimeline'];

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_renders_title_and_milestones()
    {
        // Prepare a source page with H1 + H2 + content
        $srcId = 'playground:source';
        $wikitext = <<<TXT
====== Project Alpha ======
===== Kickoff =====
Intro text.

===== Build =====
Build details.

==== Substep A ====
More details.

===== Launch =====
Final text.
TXT;
        saveWikiText($srcId, $wikitext, 'setup');

        // Render a page that uses renderpage
        $pageId = 'playground:target';
        $targetText = '<dwtimeline page='.$srcId.' />';
        $html = p_render('xhtml', p_get_instructions($targetText), $info);

        // Title must appear
        $this->assertStringContainsString('Project Alpha', $html, 'Timeline title missing');

        // Milestone titles must appear
        $this->assertStringContainsString('Kickoff', $html);
        $this->assertStringContainsString('Build', $html);
        $this->assertStringContainsString('Launch', $html);

        // Body content of first milestone should be included
        $this->assertStringContainsString('Intro text.', $html);

        // Body content of "Build" should include its H3 section text
        $this->assertStringContainsString('Build details.', $html);
        $this->assertStringContainsString('More details.', $html);
    }

    public function test_non_existing_page()
    {
        $srcId = 'no:such:page';
        $html  = p_render('xhtml', p_get_instructions('<dwtimeline page='.$srcId.' />'), $info);
        $this->assertStringContainsString('Page not found:', strip_tags($html), 'Missing not-found message key');
    }

//    public function test_self_include_guard()
//    {
//        $pageId = 'playground:self';
//        $wikitext = "====== Self test ======
//
//" . '<dwtimeline page='.$pageId.' />';
//        saveWikiText($pageId, $wikitext, 'setup');
//        $html = p_render('xhtml', p_get_instructions('<dwtimeline page='.$pageId.' />'), $info);
//        $this->assertStringContainsString('Source and destination are equal:', strip_tags($html), 'Missing same-page guard');
//    }
}