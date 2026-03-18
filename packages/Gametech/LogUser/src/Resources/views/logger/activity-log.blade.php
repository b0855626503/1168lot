@extends(config('LaravelLoggerUser.loggerBladeExtended'))

@if(config('LaravelLoggerUser.bladePlacement') == 'yield')
@section(config('LaravelLoggerUser.bladePlacementCss'))
@elseif (config('LaravelLoggerUser.bladePlacement') == 'stack')
@push(config('LaravelLoggerUser.bladePlacementCss'))
@endif

@include('LaravelLoggerUser::partials.styles')

@if(config('LaravelLoggerUser.bladePlacement') == 'yield')
@endsection
@elseif (config('LaravelLoggerUser.bladePlacement') == 'stack')
@endpush
@endif

@if(config('LaravelLoggerUser.bladePlacement') == 'yield')
@section(config('LaravelLoggerUser.bladePlacementJs'))
@elseif (config('LaravelLoggerUser.bladePlacement') == 'stack')
@push(config('LaravelLoggerUser.bladePlacementJs'))
@endif

@include('LaravelLoggerUser::partials.scripts', ['activities' => $activities])
@include('LaravelLoggerUser::scripts.confirm-modal', ['formTrigger' => '#confirmDelete'])

@if(config('LaravelLoggerUser.enableDrillDown'))
@include('LaravelLoggerUser::scripts.clickable-row')
@include('LaravelLoggerUser::scripts.tooltip')
@endif

@if(config('LaravelLoggerUser.bladePlacement') == 'yield')
@endsection
@elseif (config('LaravelLoggerUser.bladePlacement') == 'stack')
@endpush
@endif

@section('template_title')
    {{ trans('LaravelLoggerUser::laravel-logger.dashboard.title') }}
@endsection

@php
    switch (config('LaravelLoggerUser.bootstapVersion')) {
        case '4':
        $containerClass = 'card';
        $containerHeaderClass = 'card-header';
        $containerBodyClass = 'card-body';
        break;
        case '3':
        default:
        $containerClass = 'panel panel-default';
        $containerHeaderClass = 'panel-heading';
        $containerBodyClass = 'panel-body';
    }
    $bootstrapCardClasses = (is_null(config('LaravelLoggerUser.bootstrapCardClasses')) ? '' : config('LaravelLoggerUser.bootstrapCardClasses'));
@endphp

@section('content')

    <div class="container-fluid">
       @if(config('LaravelLoggerUser.enableSearch'))
       @include('LaravelLoggerUser::partials.form-search')
       @endif
       @if(config('LaravelLoggerUser.enablePackageFlashMessageBlade'))
       @include('LaravelLoggerUser::partials.form-status')
       @endif

        <div class="row">
            <div class="col-sm-12">
                <div class="{{ $containerClass }} {{ $bootstrapCardClasses }}">
                    <div class="{{ $containerHeaderClass }}">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            @if(config('LaravelLoggerUser.enableSubMenu'))

                            <span>
                                {!! trans('LaravelLoggerUser::laravel-logger.dashboard.title') !!}
                                <small>
                                    <sup class="label label-default">
                                        {{ $totalActivities }} {!! trans('LaravelLoggerUser::laravel-logger.dashboard.subtitle') !!}
                                    </sup>
                                </small>
                            </span>

                            <div class="btn-group pull-right btn-group-xs">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v fa-fw" aria-hidden="true"></i>
                                    <span class="sr-only">
                                        {!! trans('LaravelLoggerUser::laravel-logger.dashboard.menu.alt') !!}
                                    </span>
                                </button>
                                @if(config('LaravelLoggerUser.bootstapVersion') == '4')
                                <div class="dropdown-menu dropdown-menu-right">
                                    @include('LaravelLoggerUser::forms.clear-activity-log')
                                    <a href="{{route('log-user.cleared')}}" class="dropdown-item">
                                        <i class="fa fa-fw fa-history" aria-hidden="true"></i>
                                        {!! trans('LaravelLoggerUser::laravel-logger.dashboard.menu.show') !!}
                                    </a>
                                </div>
                                @else
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li class="dropdown-item">
                                        @include('LaravelLoggerUser::forms.clear-activity-log')
                                    </li>
                                    <li class="dropdown-item">
                                        <a href="{{route('log-user.cleared')}}">
                                            <i class="fa fa-fw fa-history" aria-hidden="true"></i>
                                            {!! trans('LaravelLoggerUser::laravel-logger.dashboard.menu.show') !!}
                                        </a>
                                    </li>
                                </ul>
                                @endif
                            </div>

                            @else
                            {!! trans('LaravelLoggerUser::laravel-logger.dashboard.title') !!}
                            <span class="pull-right label label-default">
                                {{ $totalActivities }}
                                <span class="hidden-sms">
                                    {!! trans('LaravelLoggerUser::laravel-logger.dashboard.subtitle') !!}
                                </span>
                            </span>
                            @endif

                        </div>
                    </div>
                    <div class="{{ $containerBodyClass }}">
                        @include('LaravelLoggerUser::logger.partials.activity-table', ['activities' => $activities, 'hoverable' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>

@include('LaravelLoggerUser::modals.confirm-modal', ['formTrigger' => 'confirmDelete', 'modalClass' => 'danger', 'actionBtnIcon' => 'fa-trash-o'])

@endsection
