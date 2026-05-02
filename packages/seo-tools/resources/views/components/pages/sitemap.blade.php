<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2">
        <label for="site_id" class="sr-only">
            {{ __('capell-admin::form.site') }}
        </label>
        <x-filament::input.wrapper>
            <x-filament::input.select id="site_id" wire:model.live="site_id">
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">
                        {{ $site->name }}
                        ({{ $site->translations->pluck('language.code')->implode(', ') }})
                    </option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        @if ($site_languages)
            <label for="language_id" class="sr-only">
                {{ __('capell-admin::form.language') }}
            </label>
            <x-filament::input.wrapper>
                <x-filament::input.select
                    id="language_id"
                    wire:model.live="language_id"
                >
                    @foreach ($site_languages as $language)
                        <option value="{{ $language->id }}">
                            {{ $language->name }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        @endif
    </div>

    @if ($sitemap)
        <div class="vsitemap mb-20 overflow-x-auto">
            <ul>
                @foreach ($sitemap as $sitemapPage)
                    @include('capell::components.pages.sitemap.page', compact('sitemapPage'))
                @endforeach
            </ul>
        </div>
    @endif

    <style>
        /***** Vertical Sitemap from https://github.com/kanyarut/VisualSitemap/blob/master/sitemap.css *****/
        .vsitemap {
            --color-primary: #0088ce;
            --color-primary-tint: #fff;
            --color-secondary: #005399;
            --color-secondary-tint: #fff;
            --color-line: #ccc;
            --item-width: 200px;
            --item-gap: 20px;
            text-align: left;
        }

        .vsitemap * {
            box-sizing: border-box;
        }

        .vsitemap ul {
            margin: 0 var(--item-gap);
            list-style: none;
            padding: 0;
        }

        .vsitemap ul > li {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            margin: 0 0 calc(var(--item-gap) / 2);
            position: relative;
        }

        .vsitemap ul > li:before {
            content: '';
            position: absolute;
            top: 1em;
            height: 1px;
            left: calc(-1 * var(--item-gap) / 2);
            width: calc(var(--item-gap) / 2);
            background: var(--color-line);
        }

        /* Draw Line */
        .vsitemap ul > li:first-child:before {
            width: var(--item-gap);
            left: calc(-1 * var(--item-gap));
        }

        .vsitemap ul li:after {
            content: '';
            position: absolute;
            top: calc(-1 * var(--item-gap));
            left: calc((var(--item-gap) / 2) - var(--item-gap));
            bottom: 0;
            width: 1px;
            background: var(--color-line);
        }

        .vsitemap ul li:first-child:after {
            top: 1em;
        }

        .vsitemap ul li:last-child:after {
            bottom: auto;
            height: calc(var(--item-gap) + 1em);
        }

        .vsitemap ul li:only-child:after {
            display: none;
        }

        /* Box Item */
        .vsitemap small {
            line-height: 1.5em;
            position: relative;
            font-size: 0.8em;
        }

        .vsitemap a.sitemap-icon {
            flex: 0;
        }

        .vsitemap a.sitemap-link {
            padding: 0.5em;
            border-radius: 3px;
            width: var(--item-width);
            transition: background-color 0.4s;
        }

        .vsitemap a.sitemap-link,
        .vsitemap a.sitemap-link:visited {
            background: var(--color-primary);
            color: var(--color-primary-tint);
        }

        .vsitemap a.sitemap-link:hover,
        .vsitemap a.sitemap-link:active {
            background: var(--color-secondary);
            color: var(--color-secondary-tint);
        }

        /* Responsive */
        /* tablet */
        @media only screen and (max-width: 768px) {
            .vsitemap > ul > li > ul > li ul li {
                flex-direction: column;
            }

            .vsitemap > ul > li > ul > li ul li ul {
                margin-top: calc(var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li > ul li:after {
                left: calc(-1 * var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li > ul > li li:first-child:before {
                width: calc(var(--item-gap) / 2);
                left: calc(-1 * var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li > ul > li li:first-child:after {
                top: calc(-1 * var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li > ul > li li:only-child:after {
                display: block;
                height: calc(var(--item-gap) / 2 + 1em);
            }
        }

        /* mobile */
        @media only screen and (max-width: 576px) {
            .vsitemap > ul > li ul li {
                flex-direction: column;
            }

            .vsitemap > ul > li ul li ul {
                margin-top: calc(var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul li:after {
                left: calc(-1 * var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li li:first-child:before {
                width: calc(var(--item-gap) / 2);
                left: calc(-1 * var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li li:first-child:after {
                top: calc(-1 * var(--item-gap) / 2);
            }

            .vsitemap > ul > li > ul > li li:only-child:after {
                display: block;
                height: calc(var(--item-gap) / 2 + 1em);
            }
        }

        /***** End Vertical Sitemap *****/
    </style>
</x-filament-panels::page>
