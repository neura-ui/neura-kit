<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Review in the editor: comments on the text, suggested changes, and the
 * Editing / Suggesting / Viewing modes — signed by the author, in EN / FR.
 */
class EditorReviewTest extends TestCase
{
    public function test_the_panel_the_comment_button_and_the_modes_are_there()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString('class="nk-review"', $html);
        $this->assertStringContainsString('class="nk-comment-fab"', $html);
        $this->assertStringContainsString("'has-review': reviewOpen", $html);
        foreach (['startComment()', 'saveDraft()', 'cancelDraft()', 'reply(item.key)', 'resolveThread(item.key, true)', 'deleteThread(item.key)', 'settleSuggestion(item.key, true)', 'settleSuggestion(item.key, false)', 'settleAll(true)', 'settleAll(false)', 'toggleReview()'] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
        foreach (['editing', 'suggesting', 'viewing'] as $mode) {
            $this->assertStringContainsString("setEditMode('{$mode}')", $html, $mode);
        }
        $this->assertStringContainsString('Ctrl+Alt+M', $html);
        $this->assertStringContainsString('Ctrl+Alt+Shift+X', $html);
    }

    public function test_the_author_signs_comments_and_suggestions()
    {
        $named = html_entity_decode(Blade::render('<x-neura::editor author="Zoé Martin" />'));
        $this->assertStringContainsString('\\u0022author\\u0022:{\\u0022name\\u0022:\\u0022Zoé Martin\\u0022,\\u0022avatar\\u0022:null}', $named);

        $array = html_entity_decode(Blade::render('<x-neura::editor :author="[\'name\' => \'Marc\', \'avatar\' => \'/m.png\']" />'));
        $this->assertStringContainsString('\\u0022name\\u0022:\\u0022Marc\\u0022', $array);

        // Nobody signed in and no author given: the script says "You".
        $anonymous = html_entity_decode(Blade::render('<x-neura::editor />'));
        $this->assertStringContainsString('\\u0022author\\u0022:null', $anonymous);
        $this->assertStringContainsString('\\u0022you\\u0022:\\u0022You\\u0022', $anonymous);
    }

    public function test_review_speaks_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['Commentaires et suggestions', 'Ajouter un commentaire', 'Afficher les résolus', 'Tout accepter', 'Tout refuser', 'Modification', 'Suggestion', 'Lecture', 'Les modifications deviennent des suggestions', 'Résoudre', 'Répondre…', 'Remplacer'] as $phrase) {
            $this->assertStringContainsString($phrase, $html, $phrase);
        }
        $this->assertStringContainsString('\\u0022you\\u0022:\\u0022Vous\\u0022', $html);
    }

    public function test_a_disabled_editor_only_reads()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor disabled />'));

        $this->assertStringContainsString('\\u0022editable\\u0022:false', $html);
        // No toolbar, so no mode switch; the script keeps it in Viewing whatever is asked.
        $this->assertStringNotContainsString('nk-mode-button', $html);
    }
}
