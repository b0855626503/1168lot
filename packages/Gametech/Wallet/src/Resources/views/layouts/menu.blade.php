@foreach ($menu->items as $menuItem)
    <li class="nav-item {{ $menu->getActives($menuItem) }}">
        <a href="{{ count($menuItem['children']) ? 'javascript:void(0)' : $menuItem['url'] }}"
           class="nav-link {{ $menu->getActive($menuItem) }}">
            <i class="text-sm nav-icon fas {{ $menuItem['icon-class'] }}"></i>
            <p>
                {{ $menuItem['name'] }}
                @if(count($menuItem['children']))
                    <i class="right fas fa-angle-left"></i>
                @elseif($menuItem['badge'])
                    <span
                        class="badge {{ $menuItem['badge-color'] ? $menuItem['badge-color'] : 'badge-info' }} right">0</span>
                @endif
            </p>
        </a>
        @if(count($menuItem['children']))
            <ul class="nav nav-treeview">
                @foreach ($menuItem['children'] as $item)
                    <li class="nav-item">
                        <a href="{{ $item['url'] }}" class="nav-link {{ $menu->getActive($item) }}">
                            <i class="far fa-circle nav-icon text-danger text-sm"></i>
                            <p>{{ $item['name'] }}</p>
                        </a>
                    </li>
                @endforeach

            </ul>
        @endif
    </li>
@endforeach
