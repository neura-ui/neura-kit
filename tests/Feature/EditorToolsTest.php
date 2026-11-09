<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The editor's tools: the menus sharing one sliding panel, the kinds of break,
 * special characters and emoji, voice typing, and the shortcuts help.
 */
class EditorToolsTest extends TestCase
{
    public function test_the_menus_share_one_panel()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString('x-data="nkMenubar"', $html);
        $this->assertSame(1, substr_count($html, 'nk-menubar-frame'));
        $this->assertSame(6, substr_count($html, 'data-menubar-trigger'));
        $this->assertSame(6, substr_count($html, 'data-menubar-list='));
        // Passing over a title while a menu is open opens that one; the keys move along.
        $this->assertStringContainsString('x-on:mouseenter="menuHover(5)"', $html);
        $this->assertStringContainsString('x-on:keydown="menuKey($event)"', $html);
        // Items still call the editor's own commands, then shut the panel.
        $this->assertStringContainsString("toggle('bold'); menuClose()", $html);
        $this->assertStringContainsString('openFind(true); menuClose()', $html);
    }

    public function test_insert_offers_every_kind_of_break()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (["insertBreak('page')", "insertBreak('line')", "insertBreak('odd')", "insertBreak('even')"] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
    }

    public function test_special_characters_voice_typing_and_shortcuts_are_there()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString('class="nk-chars" x-show="charsOpen"', $html);
        $this->assertStringContainsString('insertCharacter(entry.char)', $html);
        $this->assertStringContainsString('openCharacters()', $html);
        $this->assertStringContainsString('class="nk-voice" x-show="voiceOpen"', $html);
        $this->assertStringContainsString('toggleVoice()', $html);
        $this->assertStringContainsString('voiceHint()', $html);
        $this->assertStringContainsString('shortcutsOpen', $html);
        $this->assertStringContainsString('Ctrl+/', $html);
    }

    public function test_the_tools_speak_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['Saut de ligne', 'Page impaire suivante', 'Page paire suivante', 'Caractères spéciaux…', 'Saisie vocale', 'Raccourcis clavier'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
    }

    public function test_layout_columns_numbering_and_a_first_page_of_its_own()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor :columns="2" different-first-page first-header="Cover" />'));

        foreach (['setColumns(1)', 'setColumns(2)', 'setColumns(3)', "insertBreak('column')", "setNumbering('paren')", "setNumbering('outline')", "setNumbering('upper-roman')", 'toggleFirstPage()'] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
        // The column break is offered only to text in columns; the page setup sets them too.
        $this->assertStringContainsString('x-show="columns > 1"', $html);
        $this->assertStringContainsString('x-model.number="columns"', $html);
        // The props reach the script.
        // (The config reaches the page as JSON with its quotes escaped.)
        $this->assertStringContainsString('\\u0022columns\\u0022:2', $html);
        $this->assertStringContainsString('\\u0022differentFirstPage\\u0022:true', $html);
        $this->assertStringContainsString('\\u0022firstHeader\\u0022:\\u0022Cover\\u0022', $html);
    }

    public function test_columns_are_kept_between_one_and_three()
    {
        $this->assertStringContainsString('\\u0022columns\\u0022:3', html_entity_decode(Blade::render('<x-neura::editor :columns="9" />')));
        $this->assertStringContainsString('\\u0022columns\\u0022:1', html_entity_decode(Blade::render('<x-neura::editor />')));
    }

    public function test_comments_can_be_edited_deleted_and_mention_people()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['startEdit(item.key, index)', 'deleteComment(item.key, index)', 'saveEdit()', 'cancelEdit()', 'isMine(comment)', 'commentRuns(comment)', 'pickCommentPerson(person)'] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
        // An "@" list under each box: a new comment, a reply, a comment being changed.
        $this->assertSame(3, substr_count($html, 'class="nk-comment-people"'));
        foreach (["commentKeys(\$event, 'draft')", "commentKeys(\$event, 'reply', item.key)", "commentKeys(\$event, 'edit')"] as $keys) {
            $this->assertStringContainsString($keys, $html, $keys);
        }
    }

    public function test_formatting_and_paragraph_suggestions_have_cards()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString("['format', 'style', 'split', 'join'].includes(item.suggestion.kind)", $html);
        $this->assertStringContainsString('suggestionLabel(item.suggestion)', $html);
        $this->assertStringContainsString('\\u0022suggestSplit\\u0022:\\u0022New paragraph\\u0022', $html);
    }

    public function test_layout_comments_and_suggestions_speak_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['Deux colonnes', 'Saut de colonne', 'Style de numérotation', 'Première page différente', '(modifié)', 'Répondre… @ pour mentionner', 'Nouveau paragraphe', 'Fusionner les paragraphes', 'Mise en forme : {changes}'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
    }
}
