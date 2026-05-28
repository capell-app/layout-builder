<div class="layout-builder-inspector">
    <div class="layout-builder-inspector-header">
        <div>
            <h2>{{ __('capell-layout-builder::heading.inspector') }}</h2>
            <p>{{ $this->selectedInspectorDescription }}</p>
        </div>
    </div>

    @if ($selectedContainerKey === null)
        <div class="layout-builder-inspector-empty">
            @svg('heroicon-o-cursor-arrow-rays', 'h-8 w-8')
            <p>
                {{ __('capell-layout-builder::message.select_layout_item') }}
            </p>
        </div>
    @elseif ($selectedBlockIndex === null)
        <div class="layout-builder-inspector-section">
            <h3>
                {{ __('capell-layout-builder::heading.container_settings') }}
            </h3>
            <dl class="layout-builder-inspector-meta">
                <div>
                    <dt>{{ __('capell-admin::form.key') }}</dt>
                    <dd>{{ $selectedContainerKey }}</dd>
                </div>
            </dl>

            <div class="layout-builder-inspector-actions">
                {{ ($this->editContainerAction)(['containerKey' => $selectedContainerKey]) }}
                {{ ($this->duplicateContainerAction)(['containerKey' => $selectedContainerKey]) }}
                {{ ($this->removeContainerAction)(['containerKey' => $selectedContainerKey]) }}
            </div>
        </div>
    @else
        @php
            $selectedBlock = $this->selectedBlock;
        @endphp

        @if ($selectedBlock)
            <div class="layout-builder-inspector-section">
                <h3>{{ $selectedBlock->name }}</h3>
                <p>{{ $selectedBlock->type?->name }}</p>

                <div class="layout-builder-inspector-actions">
                    {{ ($this->editBlockAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex]) }}
                    {{ ($this->editLayoutBlockAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex]) }}
                    {{ ($this->togglePageAssetsAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex]) }}
                    {{ ($this->duplicateBlockAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex]) }}
                    {{ ($this->removeBlockAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex]) }}
                </div>
            </div>

            @if ($this->getBlockAssetTypes($selectedBlock) !== [])
                <div class="layout-builder-inspector-section">
                    <h3>{{ __('capell-layout-builder::heading.assets') }}</h3>
                    <div class="layout-builder-inspector-actions">
                        @foreach ($this->getBlockAssetTypes($selectedBlock) as $assetType)
                            {{ ($this->selectAssetAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex, 'type' => $assetType, 'types' => $this->getBlockAssetTypes($selectedBlock)]) }}
                            {{ ($this->addAssetAction)(['containerKey' => $selectedContainerKey, 'blockIndex' => $selectedBlockIndex, 'type' => $assetType, 'types' => $this->getBlockAssetTypes($selectedBlock)]) }}
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="layout-builder-inspector-empty">
                @svg('heroicon-o-exclamation-triangle', 'h-8 w-8')
                <p>
                    {{ __('capell-admin::message.unknown_block', ['block' => __('capell-admin::generic.unknown')]) }}
                </p>
            </div>
        @endif
    @endif
</div>
