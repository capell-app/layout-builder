<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Carbon\CarbonImmutable;

/**
 * Evaluates whether the current moment falls inside one of the configured
 * release windows. Windows are read from `capell.publishing-studio.release_windows`
 * and evaluated in the configured timezone. When the feature is disabled
 * the guard always dashboard-dashboard_reports open.
 *
 * Each window is an array:
 *   [
 *     'days' => ['mon', 'tue', ...],
 *     'start' => '09:00',
 *     'end' => '17:00',
 *   ]
 *
 * A window with `start` > `end` is treated as crossing midnight.
 */
final readonly class ReleaseWindowGuard
{
    /**
     * Exposed for tests and diagnostics.
     *
     * @return array<int, string>
     */
    public static function validDayKeys(): array
    {
        return ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    }

    public function isOpen(?CarbonImmutable $now = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        $moment = $this->normalise($now);

        foreach ($this->windows() as $window) {
            if ($this->matches($window, $moment)) {
                return true;
            }
        }

        return false;
    }

    public function nextOpensAt(?CarbonImmutable $now = null): ?CarbonImmutable
    {
        if (! $this->enabled()) {
            return null;
        }

        $windows = $this->windows();
        if ($windows === []) {
            return null;
        }

        $moment = $this->normalise($now);
        $candidates = [];

        foreach (range(0, 7) as $offsetDays) {
            $candidateDay = $moment->addDays($offsetDays);

            foreach ($windows as $window) {
                $startAt = $this->windowStartOn($window, $candidateDay);

                if (! $startAt instanceof CarbonImmutable) {
                    continue;
                }

                if ($startAt->greaterThan($moment)) {
                    $candidates[] = $startAt;
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (CarbonImmutable $left, CarbonImmutable $right): int => $left <=> $right);

        return $candidates[0];
    }

    private function enabled(): bool
    {
        $enabled = config('capell.publishing-studio.release_windows.enabled', false);

        return is_bool($enabled) && $enabled;
    }

    /**
     * @return array<int, array{days: array<int, string>, start: string, end: string}>
     */
    private function windows(): array
    {
        /** @var array<int, array{days: array<int, string>, start: string, end: string}> $windows */
        $windows = config('capell.publishing-studio.release_windows.windows', []);

        return $windows;
    }

    private function timezone(): string
    {
        $timezone = config('capell.publishing-studio.release_windows.timezone', 'UTC');

        return is_string($timezone) ? $timezone : 'UTC';
    }

    private function normalise(?CarbonImmutable $now): CarbonImmutable
    {
        $moment = $now ?? CarbonImmutable::now();

        return $moment->setTimezone($this->timezone());
    }

    /**
     * @param  array{days: array<int, string>, start: string, end: string}  $window
     */
    private function matches(array $window, CarbonImmutable $moment): bool
    {
        [$startHour, $startMinute] = $this->parseTime($window['start']);
        [$endHour, $endMinute] = $this->parseTime($window['end']);

        $dayKey = strtolower($moment->format('D'));
        $dayKey = substr($dayKey, 0, 3);

        $allowedDays = array_map(static fn (string $day): string => strtolower(substr($day, 0, 3)), $window['days']);
        $crossesMidnight = ($startHour * 60 + $startMinute) > ($endHour * 60 + $endMinute);
        $currentMinutes = $moment->hour * 60 + $moment->minute;
        $startMinutes = $startHour * 60 + $startMinute;
        $endMinutes = $endHour * 60 + $endMinute;

        if (! $crossesMidnight) {
            return in_array($dayKey, $allowedDays, true)
                && $currentMinutes >= $startMinutes
                && $currentMinutes < $endMinutes;
        }

        if (in_array($dayKey, $allowedDays, true) && $currentMinutes >= $startMinutes) {
            return true;
        }

        $previousDayKey = strtolower(substr($moment->subDay()->format('D'), 0, 3));

        return in_array($previousDayKey, $allowedDays, true) && $currentMinutes < $endMinutes;
    }

    /**
     * @param  array{days: array<int, string>, start: string, end: string}  $window
     */
    private function windowStartOn(array $window, CarbonImmutable $candidateDay): ?CarbonImmutable
    {
        $dayKey = strtolower(substr($candidateDay->format('D'), 0, 3));
        $allowedDays = array_map(static fn (string $day): string => strtolower(substr($day, 0, 3)), $window['days']);

        if (! in_array($dayKey, $allowedDays, true)) {
            return null;
        }

        [$startHour, $startMinute] = $this->parseTime($window['start']);

        return $candidateDay->setTime($startHour, $startMinute);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseTime(string $value): array
    {
        $parts = explode(':', $value);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);

        return [max(0, min(23, $hour)), max(0, min(59, $minute))];
    }
}
