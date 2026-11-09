<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

/**
 * The editor's images and tables: a picked image gets a frame, handles and its
 * own toolbar; tables can merge cells, fill them, take a border and resize
 * their columns — from the toolbar's table menu and the right-click menu alike.
 */
class EditorObjectsTest extends TestCase
{
    public function test_a_picked_image_has_a_frame_and_a_toolbar()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        $this->assertStringContainsString('class="nk-image-layer" x-show="imageOpen"', $html);
        $this->assertSame(4, substr_count($html, 'class="nk-image-handle"'));
        $this->assertStringContainsString("resizeImage(\$event, 'left')", $html);
        $this->assertStringContainsString("resizeImage(\$event, 'right')", $html);

        foreach (["setImageWrap('inline')", "setImageWrap('left')", "setImageWrap('right')", 'setImageWidth(50)', 'setImageWidth(null)', 'toggleImageCaption()', 'openAltText()', 'replaceImage()', 'deleteImage()', 'applyAltText()'] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
    }

    public function test_a_picked_image_can_be_cropped_turned_bordered_and_moved()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach (['startCrop()', 'rotateImage(-1)', 'rotateImage(1)', 'resetImage()', 'applyCrop()', 'closeCrop()', "setImageBorder('thick')", 'setImageBorder(null)', "setImageBorderColor('#cc0000')"] as $action) {
            $this->assertStringContainsString($action, $html, $action);
        }
        // Eight handles round the part kept, and the part itself slides.
        $this->assertSame(8, substr_count($html, 'class="nk-crop-handle"'));
        $this->assertStringContainsString("dragCrop(\$event, 'move')", $html);
        // While it is dragged, a line shows where it would land and the toolbar steps aside.
        $this->assertStringContainsString('class="nk-image-drop"', $html);
        $this->assertStringContainsString('x-show="!imageDragging"', $html);
    }

    public function test_both_table_menus_carry_the_same_actions()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        // Once in the toolbar's table menu, once in the right-click menu, each closing its own menu.
        foreach (['"mergeCells()', '"unmergeCells()', 'distributeColumns()', "setTableBorder('thick')", "setTableBorderColor('#3c78d8')", "setCellFill('#d9ead3')", 'setCellFill(null)', 'clearPickedCells()'] as $action) {
            $this->assertSame(2, substr_count($html, $action), $action);
        }
        $this->assertStringContainsString('mergeCells(); open = false', $html);
        $this->assertStringContainsString('mergeCells(); tableMenuOpen = false', $html);
        $this->assertStringContainsString('x-bind:disabled="!canMerge()"', $html);
        $this->assertStringContainsString('tableInfo().column + 1', $html);
    }

    public function test_panels_that_hide_keep_their_position_out_of_the_style_attribute()
    {
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        // A string style binding would overwrite the `display: none` that x-show sets.
        $this->assertStringContainsString('x-bind:style="{ left: `${tableMenuX}px`, top: `${tableMenuY}px` }"', $html);
        $this->assertStringContainsString('x-bind:style="{ left: `${slashX}px`, top: `${slashY}px` }"', $html);
        $this->assertStringContainsString('x-bind:style="{ left: `${imageBar.left}px`, top: `${imageBar.top + 44}px` }"', $html);
    }

    public function test_images_and_tables_speak_french()
    {
        app()->setLocale('fr');
        $html = html_entity_decode(Blade::render('<x-neura::editor />'));

        foreach ([
            'Fusionner les cellules', 'Annuler la fusion', 'Couleur de cellule', 'Bordures', 'Épaisse',
            'Répartir les colonnes', 'Vider les cellules', 'Aligné sur le texte', 'Habillage, image à gauche',
            'Recadrer l\'image', 'Faire pivoter à gauche', 'Faire pivoter à droite', 'Rétablir l\'image', 'Terminé',
            'Légende', 'Texte alternatif', "Remplacer l'image", "Supprimer l'image", "Options de l'image",
        ] as $phrase) {
            $this->assertStringContainsString($phrase, $html, $phrase);
        }

        // The caption hint is set by the script, so it travels with the phrases.
        $this->assertStringContainsString('captionHint\\u0022:\\u0022Ajouter une légende', $html);
    }
}
