<?php

declare(strict_types=1);

?>

<div class="{{ $this->class }}">
    @if ($successMessage)
        <div
            class="mb-4 text-green-500"
            aria-live="polite"
        >
            {{ $successMessage }}
        </div>
    @else
        <form wire:submit.prevent="submit">
            <input
                type="hidden"
                name="_token"
                value=""
            />
            <div class="relative">
                <input
                    type="email"
                    wire:model="email"
                    placeholder="Email address"
                    class="focus:ring-primary placeholder:text-gray-300! w-full rounded-md bg-gray-800 py-2 pl-4 pr-10 text-base text-gray-300 shadow-sm transition-all duration-150 ease-in-out placeholder:opacity-80 focus:text-white focus:outline-none focus:ring-2 dark:text-gray-200 dark:placeholder:text-gray-400 dark:focus:text-white"
                    aria-label="Email address"
                />

                <button
                    type="submit"
                    class="hover:bg-primary focus:bg-primary absolute right-0 top-0 flex h-full w-10 cursor-pointer items-center justify-center rounded-r-md border-l border-gray-700 bg-gray-600/75 text-gray-200 transition-colors duration-150 hover:text-white focus:text-white focus:outline-none dark:border-gray-700"
                    wire:loading.attr="disabled"
                    aria-disabled="true"
                >
                    <x-heroicon-o-paper-airplane
                        class="h-5 w-5"
                        wire:loading.remove.delay
                    />

                    <x-heroicon-o-arrow-path
                        wire:loading.delay
                        class="text-primary h-5 w-5 animate-spin"
                    />
                </button>
            </div>

            @error('email')
                <span
                    class="mt-1 text-xs text-red-500"
                    aria-live="polite"
                >
                    {{ $message }}
                </span>
            @enderror
        </form>
    @endif
</div>

<?php
