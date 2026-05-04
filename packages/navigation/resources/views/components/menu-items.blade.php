<ul>
    @foreach ($items as $item)
        <li @class(['is-active' => $item->active])>
            @if ($item->type === NavigationItemType::Heading)
                <span>{{ $item->label }}</span>
            @elseif ($item->url !== null)
                <a
                    href="{{ $item->url }}"
                    @if ($item->target !== null) target="{{ $item->target }}" @endif
                >
                    {{ $item->label }}
                </a>
            @else
                <span>{{ $item->label }}</span>
            @endif

            @if ($item->children->isNotEmpty())
                @include('capell-navigation::components.menu-items', [
                    'items' => $item->children,
                ])
            @endif
        </li>
    @endforeach
</ul>
