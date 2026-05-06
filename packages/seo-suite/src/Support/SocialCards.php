<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support;

class SocialCards
{
    public function __construct(
        private readonly string $title,
        private readonly string $description = '',
        private readonly string $url = '',
        private readonly string $image = '',
        private readonly string $type = 'website',
        private readonly string $siteName = '',
        private readonly string $twitterCard = 'summary_large_image',
        private readonly ?string $twitterSite = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function ogTags(): array
    {
        $tags = [
            'og:title' => $this->title,
            'og:description' => $this->description,
            'og:url' => $this->url,
            'og:type' => $this->type,
        ];

        if ($this->image !== '') {
            $tags['og:image'] = $this->image;
        }

        if ($this->siteName !== '') {
            $tags['og:site_name'] = $this->siteName;
        }

        return $tags;
    }

    /**
     * @return array<string, string>
     */
    public function twitterTags(): array
    {
        $tags = [
            'twitter:card' => $this->twitterCard,
            'twitter:title' => $this->title,
            'twitter:description' => $this->description,
        ];

        if ($this->image !== '') {
            $tags['twitter:image'] = $this->image;
        }

        if ($this->twitterSite !== null) {
            $tags['twitter:site'] = $this->twitterSite;
        }

        return $tags;
    }

    public function render(): string
    {
        $lines = [];

        foreach ($this->ogTags() as $property => $content) {
            $escapedContent = htmlspecialchars($content, ENT_QUOTES | ENT_HTML5);
            $lines[] = '<meta property="' . $property . '" content="' . $escapedContent . '">';
        }

        foreach ($this->twitterTags() as $name => $content) {
            $escapedContent = htmlspecialchars($content, ENT_QUOTES | ENT_HTML5);
            $lines[] = '<meta name="' . $name . '" content="' . $escapedContent . '">';
        }

        return implode("\n", $lines);
    }
}
