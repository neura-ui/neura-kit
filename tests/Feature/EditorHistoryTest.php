<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * Version history: recorded as the document is written, kept in the browser or
 * by the app, shown with what each version changed, restored, named — EN / FR.
 */
class EditorHistoryTest extends TestCase
{
    public function test_the_history_screen_and_its_entries_are_there()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString('class="nk-history" x-show="historyOpen"', $html);
        $this->assertStringContainsString('x-ref="historyDoc"', $html);
        foreach (['openHistory()', 'nameCurrentVersion()', 'restoreVersion()', 'toggleHistoryDiff()', 'selectVersion(version.id)', 'renameVersion(version.id)', 'saveVersionName(version.id)', 'closeHistory()'] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
        $this->assertStringContainsString('Ctrl+Alt+Shift+H', $html);
    }

    public function test_versions_are_kept_under_the_field_and_in_the_browser_by_default()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor name="brief" />'));

        $this->assertStringContainsString('\\u0022history\\u0022:{\\u0022enabled\\u0022:true,\\u0022key\\u0022:\\u0022brief\\u0022,\\u0022url\\u0022:null,\\u0022interval\\u0022:300,\\u0022idle\\u0022:20,\\u0022limit\\u0022:100}', $html);
    }

    public function test_the_app_can_keep_them_and_name_the_document()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor history-key="plan-42" versions-url="/plans/42/versions" />'));
        $this->assertStringContainsString('\\u0022key\\u0022:\\u0022plan-42\\u0022', $html);
        $this->assertStringContainsString('\\u0022url\\u0022:\\u0022\\\\\\/plans\\\\\\/42\\\\\\/versions\\u0022', $html);

        config(['neura-kit.editor.history.url' => '/versions', 'neura-kit.editor.history.limit' => 30]);
        $configured = html_entity_decode(Blade::render('<x-neura::editor />'));
        $this->assertStringContainsString('\\u0022url\\u0022:\\u0022\\\\\\/versions\\u0022', $configured);
        $this->assertStringContainsString('\\u0022limit\\u0022:30', $configured);

        $off = html_entity_decode(Blade::render('<x-neura::editor :history="false" />'));
        $this->assertStringContainsString('\\u0022history\\u0022:{\\u0022enabled\\u0022:false', $off);
    }

    public function test_history_speaks_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['Historique des versions', "Voir l'historique des versions", 'Nommer la version actuelle', 'Afficher les modifications', 'Restaurer cette version', 'Versions nommées', 'Actuelle', 'texte ajouté dans cette version', 'Dernière version'] as $phrase) {
            $this->assertStringContainsString($phrase, $html, $phrase);
        }
        $this->assertStringContainsString('est redevenue la version actuelle', $html);
    }
}
