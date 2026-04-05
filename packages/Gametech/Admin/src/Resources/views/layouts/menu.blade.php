@php
    $menuItems = $menuItems ?? $menu->items;
    $isRootLevel = $isRootLevel ?? true;
@endphp

@foreach ($menuItems as $menuItem)

@if($isRootLevel && $webconfig->freecredit_open == 'N')
    @continue($menuItem['key'] == 'credit')
    @continue($menuItem['key'] == 'withdraw_free')
    @continue($menuItem['key'] == 'mop.rp_cashback')
    @continue($menuItem['key'] == 'mop.rp_member_ic')
@endif

    @php
        $visibleChildren = collect((array) ($menuItem['children'] ?? []))
            ->filter(fn ($child) => (int) ($child['status'] ?? 1) === 1)
            ->values()
            ->all();
        $hasChildren = count($visibleChildren) > 0;
    @endphp

    <li class="nav-item {{ $menu->getActives($menuItem) }} {{ (($menuItem['status'] ?? 1) == 0 ? 'hide' : '') }}">
        <a href="{{ $hasChildren ? 'javascript:void(0)' : ($menuItem['url'] ?? '#') }}"
           class="nav-link {{ $menu->getActive($menuItem) }}">
            @if($isRootLevel)
                <i class="text-sm nav-icon fas {{ $menuItem['icon-class'] ?? 'fa-circle' }}"></i>
            @else
                <i class="far fa-circle nav-icon text-danger text-sm"></i>
            @endif
            <p>
                {{ $menuItem['name'] ?? '-' }}
                @if($hasChildren)
                    @if(($menuItem['badge'] ?? 0) && $isRootLevel)
                        <span
                            class="badge {{ !empty($menuItem['badge-color']) ? $menuItem['badge-color'] : 'badge-info' }} right ml-2"
                            id="badge_{{ $menuItem['key'] ?? '' }}">0</span>
                    @endif
                    <i class="right fas fa-angle-left"></i>
                @elseif(($menuItem['badge'] ?? 0) && $isRootLevel)
                    <span
                        class="badge {{ !empty($menuItem['badge-color']) ? $menuItem['badge-color'] : 'badge-info' }} right"
                        id="badge_{{ $menuItem['key'] ?? '' }}">0</span>
                @endif
            </p>
        </a>

        @if($hasChildren)
            <ul class="nav nav-treeview">
                @include('admin::layouts.menu', ['menuItems' => $visibleChildren, 'isRootLevel' => false])
            </ul>
        @endif
    </li>
@endforeach
