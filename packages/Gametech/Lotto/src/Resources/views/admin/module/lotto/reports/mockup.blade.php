@extends('admin::layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <section class="content text-sm">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $title }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">{{ $description }}</p>

                    <div class="row">
                        @foreach((array) ($filters ?? []) as $filter)
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="mb-1">{{ $filter }}</label>
                                <input type="text" class="form-control form-control-sm" placeholder="Mockup filter">
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-primary btn-sm">ค้นหา</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm">รีเซ็ต</button>
                        <button type="button" class="btn btn-outline-success btn-sm">Export</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                @foreach((array) ($columns ?? []) as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td colspan="{{ max(1, count((array) ($columns ?? []))) }}" class="text-center text-muted">
                                    Mockup report table for section <code>{{ $section ?? '-' }}</code>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

