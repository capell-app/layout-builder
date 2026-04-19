<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Analytics;

use Illuminate\Http\Request;
use Illuminate\Session\Store;

/**
 * Captures UTM query parameters from an inbound request and persists them to
 * the session so forms and conversions can attribute the visit accurately.
 */
class UtmCollector
{
    private const KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
    ];

    private const SESSION_KEY = 'capell.utm';

    public function __construct(
        private readonly Store $session,
    ) {}

    /**
     * Pull any UTM params present on the request into the session. Existing
     * values are overwritten only when a fresh parameter is present.
     *
     * @return array<string, string>
     */
    public function capture(Request $request): array
    {
        $existing = $this->all();
        foreach (self::KEYS as $key) {
            $value = $request->query($key);
            if (is_string($value) && $value !== '') {
                $existing[$key] = $value;
            }
        }

        $this->session->put(self::SESSION_KEY, $existing);

        return $existing;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $stored = $this->session->get(self::SESSION_KEY, []);

        return is_array($stored) ? $stored : [];
    }

    public function get(string $key): ?string
    {
        $value = $this->all()[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Emit a JS snippet that exposes UTM params on the window object.
     */
    public function toJavaScript(string $windowKey = 'CAPELL_UTM'): string
    {
        $payload = json_encode($this->all(), JSON_THROW_ON_ERROR);

        return sprintf('window.%s = %s;', $windowKey, $payload);
    }
}
