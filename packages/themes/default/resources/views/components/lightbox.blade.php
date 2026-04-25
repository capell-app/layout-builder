<?php

declare(strict_types=1);

?>

<div
    x-data="lightbox"
    @lightbox.window="lightbox(event)"
    @keyup.escape.window="close()"
    @keyup.left.window="loadPrevious()"
    @keyup.right.window="loadNext()"
>
    <div
        class="fixed left-0 top-0 z-[9999] flex h-screen w-screen items-center justify-center bg-[#000000c9] md:p-5 lg:p-10"
        x-show="currentUrl"
        x-cloak
        @click="if($event.target == $el){ close() }"
    >
        <div
            class="relative mx-auto min-h-[10vh] min-w-[50%] max-w-[95%] rounded bg-white p-1 md:max-w-[85%] md:p-3"
        >
            <button
                class="hover:text-primary focus:text-primary fixed right-6 top-6 z-[8888] flex h-10 w-10 items-center justify-center rounded-full border-2 border-white bg-black text-white md:absolute md:-right-6 md:-top-6"
                @click="close()"
            >
                @svg('heroicon-s-x-mark', 'h-6 w-6 stroke-current')
            </button>

            <div
                class="absolute inset-0 flex items-center justify-center"
                role="status"
            >
                <svg
                    class="text-secondary h-20 w-20 fill-current"
                    version="1.1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    viewBox="0 0 100 100"
                >
                    <path
                        d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50"
                    >
                        <animateTransform
                            type="rotate"
                            attributeName="transform"
                            attributeType="XML"
                            dur="1s"
                            from="0 50 50"
                            to="360 50 50"
                            repeatCount="indefinite"
                        ></animateTransform>
                    </path>
                </svg>
                <span class="sr-only">
                    {{ __('capell-frontend::generic.loading') }}
                </span>
            </div>

            <div class="relative">
                <img
                    class="relative max-h-[90vh] w-full max-w-full object-cover"
                    x-show="currentType !== 'video'"
                    :src="currentUrl"
                    :alt="currentTitle"
                />

                <video
                    class="relative aspect-video max-h-[90vh] w-full max-w-full object-cover object-top"
                    playsinline
                    autoplay
                    controls
                    x-show="currentType === 'video'"
                    x-ref="lightbox-video"
                    preload="none"
                    :src="currentUrl"
                    :alt="currentTitle"
                ></video>

                <div
                    class="flex items-center bg-white/80 pt-3"
                    x-show="total() > 1"
                >
                    <button
                        class="text-primary hover:bg-primary active:bg-primary rounded hover:text-white active:text-white"
                        type="button"
                        @click.prevent="loadPrevious"
                    >
                        @svg('heroicon-o-chevron-left', 'h-10 w-10 stroke-current')
                    </button>
                    <span
                        class="grow text-center text-sm leading-tight tracking-wide text-gray-800"
                        x-text="currentTitle"
                    ></span>
                    <button
                        class="text-primary hover:bg-primary active:bg-primary ml-auto rounded hover:text-white active:text-white"
                        type="button"
                        @click.prevent="loadNext"
                    >
                        @svg('heroicon-o-chevron-right', 'h-10 w-10 stroke-current')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
