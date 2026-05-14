<?php

declare(strict_types=1);

namespace Capell\DemoKit\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Simple dummy content generator moved out of config so callers can request
 * language-specific demo content programmatically. Returns a short HTML
 * paragraph made of a random number of sentences for the requested language.
 *
 * @method static string run(string $languageCode = 'en')
 */
class DummyContentGeneratorAction
{
    use AsAction;

    public function handle(string $languageCode = 'en'): string
    {
        $samples = [
            'en' => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.",
            'fr' => "Le Lorem Ipsum est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l'imprimerie depuis les années 1500.",
            'de' => 'Lorem Ipsum ist ein einfacher Demo-Text für die Print- und Schriftindustrie. Lorem Ipsum ist in der Industrie bereits der Standard Demo-Text seit 1500.',
            'it' => 'Lorem Ipsum è un testo segnaposto utilizzato nel settore della tipografia e della stampa. Lorem Ipsum è considerato il testo segnaposto standard sin dal sedicesimo secolo.',
            'es' => 'Lorem Ipsum es simplemente el texto de relleno de las imprentas y archivos de texto. Lorem Ipsum ha sido el texto de relleno estándar de las industrias desde el año 1500.',
        ];

        $base = $samples[$languageCode] ?? $samples['en'];

        // Work with plain text sentences so we can return a variable-length
        // paragraph without breaking HTML.
        $plain = trim(strip_tags($base));

        $sentences = preg_split('/(?<=[.!?])\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($sentences) || $sentences === []) {
            return '<p>' . $plain . '</p>';
        }

        // Language-specific max sentences (keeps returned content varied per language)
        $maxMap = [
            'en' => 6,
            'fr' => 5,
            'de' => 5,
            'it' => 5,
            'es' => 5,
        ];

        $max = $maxMap[$languageCode] ?? 4;
        $max = min($max, count($sentences));

        $count = mt_rand(1, max(1, $max));

        $selected = array_slice($sentences, 0, $count);

        $content = implode(' ', $selected);

        // Simple multilingual-safe approach:
        // - Extract word tokens using a Unicode-aware regex
        // - Pick one reasonable token at random and wrap its first occurrence
        // This keeps the logic simple, language-agnostic, and does not affect
        // sentence selection because wrapping happens after selection.
        if (preg_match_all('/\p{L}[\p{L}\p{Mn}\p{Pd}\'’]*/u', $content, $matches) && isset($matches[0]) && filled($matches[0])) {
            $tokens = $matches[0];

            // Prefer tokens longer than one character where possible
            $candidates = array_values(array_filter($tokens, static fn (string $w): bool => mb_strlen($w, 'UTF-8') > 1));

            $pool = $candidates !== [] ? $candidates : $tokens;

            $pickIndex = mt_rand(0, count($pool) - 1);
            $word = $pool[$pickIndex];

            $pattern = '/' . preg_quote($word, '/') . '/iu';

            $content = preg_replace_callback($pattern, static fn (array $m): string => '<strong>' . $m[0] . '</strong>', $content, 1) ?? $content;
        }

        return '<p>' . $content . '</p>';
    }
}
