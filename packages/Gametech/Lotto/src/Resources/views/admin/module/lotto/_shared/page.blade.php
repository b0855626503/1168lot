<section class="content text-sm">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $title }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">{{ $description }}</p>
                        <p class="text-muted mb-0">
                            Route: <code>{{ $routeName }}</code>
                            @if(!empty($section))
                                · Section: <code>{{ $section }}</code>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        @includeIf('admin::module.lotto.'.$section.'.create')
                        @include('admin::module.lotto.'.$section.'.table')
                        @includeIf('admin::module.lotto.'.$section.'.addedit')
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">เมนู Lotto ทั้งหมด</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($links as $link)
                                <a href="{{ $link['url'] }}"
                                   class="list-group-item list-group-item-action {{ request()->routeIs($link['route']) ? 'active' : '' }}">
                                    {{ $link['name'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
