<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The editor's references: "@" chips for people and dates, tables of contents
 * and footnotes — reachable from the Insert menu and the "/" menu, in EN / FR.
 */
class EditorReferencesTest extends TestCase
{
    public function test_people_to_mention_reach_the_script()
    {
        $html = html_entity_decode(Blade::render(
            '<x-neura::editor :mentions="$people" mentions-url="/api/people" />',
            ['people' => [
                ['id' => 7, 'name' => 'Zoé Martin', 'email' => 'zoe@example.com'],
                (object) ['id' => 8, 'name' => 'Marc Akkari'],
                ['id' => 9, 'name' => ''],
            ]]
        ));

        $this->assertStringContainsString('\\u0022id\\u0022:7,\\u0022name\\u0022:\\u0022Zoé Martin\\u0022,\\u0022email\\u0022:\\u0022zoe@example.com\\u0022', $html);
        $this->assertStringContainsString('\\u0022name\\u0022:\\u0022Marc Akkari\\u0022', $html);
        // Someone without a name is left out.
        $this->assertSame(2, substr_count($html, '\\u0022avatar\\u0022'));
        $this->assertStringContainsString('\\u0022mentionsUrl\\u0022:\\u0022\\\\\\/api\\\\\\/people\\u0022', $html);
    }

    public function test_the_search_endpoint_can_come_from_the_config()
    {
        config(['neura-kit.editor.mentions_url' => '/team/search']);
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString('\\u0022mentionsUrl\\u0022:\\u0022\\\\\\/team\\\\\\/search\\u0022', $html);
    }

    public function test_insert_menu_and_panels()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (["insertToc('pages')", "insertToc('links')", 'insertFootnote()', 'startMention()', "setTocStyle('links')", 'deleteToc()', 'removeChip()', 'setChipDate($event.target.value)'] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
        $this->assertStringContainsString('Ctrl+Alt+F', $html);
        $this->assertStringContainsString('class="nk-chip-card"', $html);
        $this->assertStringContainsString('class="nk-toc-bar"', $html);
        // The "@" menu shows people with their initials and email.
        $this->assertStringContainsString('class="nk-slash-avatar"', $html);
        $this->assertStringContainsString('x-text="item.sub"', $html);
    }

    public function test_references_speak_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['Table des matières', 'Avec numéros de page', 'Avec liens bleus', 'Note de bas de page', 'Personne ou date', 'Changer la date', 'Envoyer un e-mail'] as $phrase) {
            $this->assertStringContainsString($phrase, $html, $phrase);
        }

        // Words the script shows travel with its phrases.
        foreach (['Personnes', 'Demain', 'Hier'] as $phrase) {
            $this->assertStringContainsString('\\u0022'.$phrase.'\\u0022', $html, $phrase);
        }
    }
}
