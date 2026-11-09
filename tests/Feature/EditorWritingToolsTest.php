<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The editor's words follow the app locale (EN / FR), and it carries its writing tools:
 * find and replace, the "/" menu, checklists, text case, paint format and word count.
 */
class EditorWritingToolsTest extends TestCase
{
    public function test_the_editor_speaks_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach ([
            'Fichier', 'Édition', 'Affichage', 'Insertion', 'Outils',
            'Rechercher et remplacer', 'Liste de contrôle', 'Casse du texte', 'MAJUSCULES',
            'Reproduire la mise en forme', 'Nombre de mots', 'Respecter la casse', 'Tout remplacer',
            'Document sans titre', 'Commencez à écrire…', 'Double-cliquez pour ajouter un en-tête',
            'Insérer un tableau', 'Mise en page', 'Marge gauche',
        ] as $phrase) {
            $this->assertStringContainsString($phrase, $html, $phrase);
        }

        // The script gets its own words too: style names and "/" commands.
        $this->assertStringContainsString('Texte normal', $html);
        $this->assertStringContainsString('{current} sur {total}', $html);
        $this->assertStringContainsString('\\u0022locale\\u0022:\\u0022fr\\u0022', $html);
    }

    public function test_english_by_default()
    {
        app()->setLocale('en');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['Find and replace', 'Paint format', 'Word count', 'Checklist', 'Text case', 'Untitled document', 'Start writing…'] as $phrase) {
            $this->assertStringContainsString($phrase, $html, $phrase);
        }
    }

    public function test_the_writing_tools_are_in_the_markup()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor document-title="Brief" />'));

        $this->assertStringContainsString('class="nk-find nk-menu-motion"', $html);
        $this->assertStringContainsString('x-ref="findInput"', $html);
        $this->assertStringContainsString('class="nk-slash nk-editor-menu"', $html);
        $this->assertStringContainsString('runSlash(item.id)', $html);
        $this->assertStringContainsString('toggleChecklist()', $html);
        $this->assertStringContainsString("setCase('title')", $html);
        $this->assertStringContainsString('togglePainter(true)', $html);
        $this->assertStringContainsString('openWordCount()', $html);
        $this->assertStringContainsString('Brief', $html);
    }
}
