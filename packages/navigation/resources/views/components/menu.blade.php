<nav {{ $attributes }}>
    @include('capell-navigation::components.menu-items', [
        'items' => $menu->items,
    ])
</nav>
