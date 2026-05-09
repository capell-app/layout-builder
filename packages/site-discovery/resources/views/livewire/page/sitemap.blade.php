<?php
use Capell\Frontend\Facades\Frontend;

$site = Frontend::site();
$language = Frontend::language();

?>

<x-capell::layout class="page-sitemap">
    <div class="vsitemap mb-20 overflow-x-auto">
        <ul>
            @if ($this->results)
                @foreach ($this->results as $sitemapPage)
                    @include('capell::sitemap.sitemap-page', ['sitemapPage' => $sitemapPage, 'level' => 1])
                @endforeach
            @endif
        </ul>
    </div>

    @once
        <style>
            /***** Vertical Sitemap from https://github.com/kanyarut/VisualSitemap/blob/master/sitemap.css *****/
            .vsitemap {
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
                top: 0.9em;
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

            .vsitemap a {
                line-height: 1.5em;
                display: block;
                text-decoration: none;
                padding: 0.2em 0.5em;
                border-radius: 3px;
                width: var(--item-width);
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
    @endonce
</x-capell::layout>
