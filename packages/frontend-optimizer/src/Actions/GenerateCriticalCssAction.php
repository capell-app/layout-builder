<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Actions;

use Capell\FrontendOptimizer\Contracts\CriticalCssGenerator;
use Capell\FrontendOptimizer\Enums\OptimizationStatus;
use Capell\FrontendOptimizer\Models\FrontendOptimizationRun;
use Capell\FrontendOptimizer\Models\FrontendRenderProfile;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class GenerateCriticalCssAction
{
    use AsAction;

    public function __construct(private readonly CriticalCssGenerator $generator) {}

    public function handle(FrontendRenderProfile $profile, string $url): string
    {
        $run = $profile->runs()->create([
            'started_at' => now(),
            'status' => OptimizationStatus::Running->value,
        ]);

        try {
            $criticalCssPath = $this->generator->generate($profile, $url);

            $profile->forceFill([
                'critical_css_path' => $criticalCssPath,
                'generated_at' => now(),
                'status' => OptimizationStatus::Generated->value,
            ])->save();

            $this->finishRun($run, OptimizationStatus::Generated);

            return $criticalCssPath;
        } catch (Throwable $exception) {
            $profile->forceFill([
                'status' => OptimizationStatus::Failed->value,
            ])->save();

            $this->finishRun($run, OptimizationStatus::Failed, $exception->getMessage());

            throw $exception;
        }
    }

    private function finishRun(FrontendOptimizationRun $run, OptimizationStatus $status, ?string $message = null): void
    {
        $run->forceFill([
            'finished_at' => now(),
            'message' => $message,
            'status' => $status->value,
        ])->save();
    }
}
