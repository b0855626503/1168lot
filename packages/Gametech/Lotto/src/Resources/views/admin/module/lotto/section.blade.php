@extends('admin::layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
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
                        <div class="card-header">
                            <h3 class="card-title">สถานะหน้าใช้งาน</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                หน้านี้ถูกเชื่อม route, menu และ ACL แล้ว เพื่อให้สามารถกดเข้าใช้งานแต่ละเมนูของ Lotto ได้
                            </div>

                            <ul class="mb-0 pl-3">
                                <li>เมนูใน sidebar จะพาเข้าหน้า Lotto ของแต่ละ section ได้แล้ว</li>
                                <li>route ของ admin ถูกโหลดผ่าน <code>LottoServiceProvider</code></li>
                                <li>ส่วน CRUD / DataTable / ฟอร์มจริงของแต่ละ section ยังสามารถพัฒนาต่อจากหน้านี้ได้</li>
                            </ul>
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
@endsection
