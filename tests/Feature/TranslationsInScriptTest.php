<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Neura\Kit\Components\Atoms\SpotlightManager;
use Neura\Kit\Tests\TestCase;

/**
 * A translation echoed into an Alpine expression goes through `@js()`, never between quotes:
 * Blade turns `'` into `&#039;`, the browser turns it back before Alpine reads the attribute,
 * and "Demander à l'IA" between single quotes ends the string at "l" — a syntax error on every
 * page that renders the component.
 */
class TranslationsInScriptTest extends TestCase
{
    public function test_the_spotlight_label_keeps_an_apostrophe()
    {
        $this->translate(['askAi' => "Demander à l'IA", 'search' => "Chercher dans l'app"]);

        $label = $this->attribute(Livewire::test(SpotlightManager::class)->html(), ':aria-label');

        $this->assertContains("Demander à l'IA", $this->strings($label));
        $this->assertContains("Chercher dans l'app", $this->strings($label));
    }

    public function test_the_flow_titles_keep_an_apostrophe()
    {
        $this->translate(['Output' => "Sortie d'étape", 'Activate' => "Activer l'étape", 'Deactivate' => "Désactiver l'étape"]);

        $html = Blade::render('<x-neura::flow />');

        $this->assertContains("Sortie d'étape", $this->strings($this->attribute($html, ':title', 'out.label')));
        $toggle = $this->strings($this->attribute($html, ':title', 'isDisabled(node)'));
        $this->assertContains("Activer l'étape", $toggle);
        $this->assertContains("Désactiver l'étape", $toggle);
    }

    public function test_the_emoji_picker_placeholder_keeps_an_apostrophe()
    {
        $this->translate(['No selection' => "Pas d'emoji"]);

        $text = $this->attribute(Blade::render('<x-neura::emoji-picker />'), 'x-text', 'value ||');

        $this->assertContains("Pas d'emoji", $this->strings($text));
    }

    public function test_the_calendar_view_labels_keep_an_apostrophe()
    {
        $this->translate(['Month' => "Vue d'un mois", 'Week' => "Vue d'une semaine", 'Day' => "Vue d'un jour", 'Agenda' => "Liste d'événements"]);

        $data = $this->attribute(Blade::render('<x-neura::event-calendar />'), 'x-data');
        preg_match('/^\s*viewLabel\(v\).*$/m', $data, $method);

        $this->assertNotEmpty($method, 'The calendar names its views in viewLabel(v).');
        $labels = $this->strings($method[0]);
        foreach (["Vue d'un mois", "Vue d'une semaine", "Vue d'un jour", "Liste d'événements"] as $label) {
            $this->assertContains($label, $labels);
        }
    }

    public function test_no_view_echoes_a_translation_between_script_quotes()
    {
        $offenders = [];

        foreach (File::allFiles(__DIR__.'/../../resources/views') as $file) {
            foreach (file($file->getPathname()) as $index => $line) {
                if (preg_match('/[\'`]\s*\{(\{|!!)\s*(__|neura_trans|trans|trans_choice)\(/', $line)) {
                    $offenders[] = $file->getRelativePathname().':'.($index + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'Use @js(__(…)) for a translation inside a script string.');
    }

    /**
     * Use these lines for the French strings, on top of the kit's own.
     */
    private function translate(array $lines): void
    {
        app()->setLocale('fr');
        app('translator')->load('*', '*', 'fr');
        app('translator')->addLines(collect($lines)->mapWithKeys(fn ($line, $key) => ["*.{$key}" => $line])->all(), 'fr');
    }

    /**
     * The value of the first `$name` attribute starting with `$start`, as Alpine reads it (the
     * browser has decoded its entities).
     */
    private function attribute(string $html, string $name, string $start = ''): string
    {
        $found = preg_match('/\s'.preg_quote($name, '/').'="('.preg_quote($start, '/').'[^"]*)"/', $html, $match);

        $this->assertSame(1, $found, "No {$name}=\"{$start}…\" attribute.");

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * The strings of a JavaScript expression, as the script reads them. A string left open (an
     * apostrophe in a label closes the quotes early) fails the test.
     */
    private function strings(string $expression): array
    {
        $strings = [];
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $quote = $expression[$i];

            if (! in_array($quote, ["'", '"', '`'], true)) {
                continue;
            }

            $string = '';

            for ($i++; $i < $length && $expression[$i] !== $quote; $i++) {
                if ($expression[$i] !== '\\' || $i + 1 === $length) {
                    $string .= $expression[$i];

                    continue;
                }

                $escaped = $expression[++$i];

                if ($escaped === 'u' && preg_match('/\G[0-9a-fA-F]{4}/', $expression, $hex, 0, $i + 1)) {
                    $string .= mb_chr(hexdec($hex[0]), 'UTF-8');
                    $i += 4;
                } else {
                    $string .= ['n' => "\n", 't' => "\t", 'r' => "\r"][$escaped] ?? $escaped;
                }
            }

            $this->assertLessThan($length, $i, "A string is left open in: {$expression}");

            $strings[] = $string;
        }

        return $strings;
    }
}
