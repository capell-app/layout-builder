<input
    type="checkbox"
    id="toolbar-toggle-checkbox"
    style="display: none"
    checked
/>
<label
    for="toolbar-toggle-checkbox"
    id="toolbar-toggle-label"
    title="{{ __('capell-frontend-toolbar::toolbar.toggle_toolbar') }}"
>
    <span class="toolbar-toggle-label-show">
        @svg('heroicon-o-wrench-screwdriver', 'toolbar-link-icon')
    </span>
    <span class="toolbar-toggle-label-hide">
        @svg('heroicon-o-x-mark', 'toolbar-link-icon')
    </span>
</label>
<div id="toolbar" class="toolbar" wire:ignore>
    @if ($editUrl)
        <a
            href="{{ $editUrl }}"
            title="{{ __('capell-frontend-toolbar::toolbar.last_updated', ['date' => $pageUrl->pageable->updated_at->toDayDateTimeString()]) }}"
            class="toolbar-link"
            target="_blank"
        >
            @svg('heroicon-o-pencil', 'toolbar-link-icon')
            <span class="toolbar-link-label">
                {{ __('capell-frontend-toolbar::toolbar.edit_page') }}
            </span>
        </a>
    @endif

    @if ($htmlCache['cacheTime'])
        <form action="{{ $htmlCache['deleteUrl'] }}" method="post">
            @csrf
            <button
                type="submit"
                name="refresh"
                title="{{ __('capell-frontend-toolbar::toolbar.cached_on', ['date' => $htmlCache['cacheTime']->toDayDateTimeString()]) }}"
                class="toolbar-link"
            >
                @svg('heroicon-c-arrow-path', 'toolbar-link-icon')
                <span class="toolbar-link-label">
                    {{ __('capell-frontend-toolbar::toolbar.refresh') }}
                </span>
            </button>
        </form>
    @endif
</div>

<style>
    #toolbar-toggle-checkbox:checked ~ #toolbar {
        display: none !important;
    }
    #toolbar-toggle-checkbox:not(:checked) ~ #toolbar {
        display: flex;
    }
    #toolbar-toggle-checkbox:checked
        ~ #toolbar-toggle-label
        .toolbar-toggle-label-show {
        display: inline;
    }
    #toolbar-toggle-checkbox:checked
        ~ #toolbar-toggle-label
        .toolbar-toggle-label-hide {
        display: none;
    }
    #toolbar-toggle-checkbox:not(:checked)
        ~ #toolbar-toggle-label
        .toolbar-toggle-label-show {
        display: none;
    }
    #toolbar-toggle-checkbox:not(:checked)
        ~ #toolbar-toggle-label
        .toolbar-toggle-label-hide {
        display: inline;
    }
    #toolbar-toggle-label {
        cursor: pointer;
        align-items: center;
        order: 3;
        padding: 8px;
    }
    #capell-frontend-toolbar {
        align-items: center;
        background: rgba(0, 0, 0, 0.9);
        box-shadow: -1px -1px 0 #ffffff42;
        bottom: 0;
        border-top-left-radius: 8px;
        color: #fff;
        display: flex;
        font-size: 12px;
        height: 2rem;
        justify-content: space-between;
        line-height: 1;
        position: fixed;
        right: 0;
        white-space: nowrap;
        max-width: 100%;
        z-index: 99999;
    }
    .toolbar {
        order: 2;
    }
    .toolbar-link {
        align-items: center;
        background: none;
        box-shadow: none;
        color: inherit;
        cursor: pointer;
        display: inline-flex;
        gap: 4px;
        overflow: hidden;
        padding: 8px 4px 8px 8px;
        text-align: right;
        text-decoration: none;
        text-overflow: ellipsis;
        vertical-align: middle;
        white-space: nowrap;
        border: none;
        font-size: inherit;
        line-height: 1;
    }
    .toolbar-link:hover,
    .toolbar-link:focus {
        color: gold;
    }
    .toolbar-link-label {
        line-height: 1;
        vertical-align: middle;
    }
    .toolbar-link-icon {
        width: 14px;
        height: 14px;
        display: inline-block;
        vertical-align: middle;
    }
</style>
