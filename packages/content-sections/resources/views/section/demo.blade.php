<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title }} section</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-50 text-slate-950 antialiased">
        <main class="mx-auto min-h-screen max-w-6xl px-6 py-12">
            <p
                class="mb-3 text-sm font-medium uppercase tracking-wide text-slate-500"
            >
                section preview
            </p>
            <div
                class="rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200"
            >
                <x-dynamic-component
                    :component="$definition->component"
                    :asset="$asset"
                    :link-text="$linkText"
                    :meta="$meta"
                    :summary="$summary"
                    :title="$title"
                    :url="$url"
                />
            </div>
        </main>
    </body>
</html>
