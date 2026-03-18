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
        @include('LaravelLoggerUser::scripts.confirm-modal', ['formTrigger' => '#confirmRestore'])

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
    {{ trans('LaravelLoggerUser::laravel-logger.dashboardCleared.title') }}
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

        @if(config('LaravelLoggerUser.enablePackageFlashMessageBlade'))
            @include('LaravelLoggerUser::partials.form-status')
        @endif

        <div class="row">
            <div class="col-sm-12">
                <div class="{{ $containerClass }} {{ $bootstrapCardClasses }}">
                    <div class="{{ $containerHeaderClass }}">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>
                                {!! trans('LaravelLoggerUser::laravel-logger.dashboardCleared.title') !!}
                                <sup class="label">
                                    {{ $totalActivities }} {!! trans('LaravelLoggerUser::laravel-logger.dashboardCleared.subtitle') !!}
                                </sup>
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
                                        <a href="{{route('log-user')}}" class="dropdown-item">
                                            <span class="text-primary">
                                                <i class="fa fa-fw fa-mail-reply" aria-hidden="true"></i>
                                                {!! trans('LaravelLoggerUser::laravel-logger.dashboard.menu.back') !!}
                                            </span>
                                        </a>
                                        @if($totalActivities)
                                            @include('LaravelLoggerUser::forms.delete-activity-log')
                                            @include('LaravelLoggerUser::forms.restore-activity-log')
                                        @endif
                                    </div>
                                @else
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{route('log-user')}}">
                                                <span class="text-primary">
                                                    <i class="fa fa-fw fa-mail-reply" aria-hidden="true"></i>
                                                    {!! trans('LaravelLoggerUser::laravel-logger.dashboard.menu.back') !!}
                                                </span>
                                            </a>
                                        </li>
                                        @if($totalActivities)
                                            <li>
                                                @include('LaravelLoggerUser::forms.delete-activity-log')
                                            </li>
                                            <li>
                                                @include('LaravelLoggerUser::forms.restore-activity-log')
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        @include('LaravelLoggerUser::logger.partials.activity-table', ['activities' => $activities, 'hoverable' => true])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('LaravelLoggerUser::modals.confirm-modal', ['formTrigger' => 'confirmDelete', 'modalClass' => 'danger', 'actionBtnIcon' => 'fa-trash-o'])
    @include('LaravelLoggerUser::modals.confirm-modal', ['formTrigger' => 'confirmRestore', 'modalClass' => 'success', 'actionBtnIcon' => 'fa-check'])

@endsection
