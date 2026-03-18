{!! Form::open(array('route' => 'log-user.restore-activity', 'method' => 'POST', 'class' => 'mb-0')) !!}
    {!! Form::button('<i class="fa fa-fw fa-history" aria-hidden="true"></i>' . trans('LaravelLoggerUser::laravel-logger.dashboardCleared.menu.restoreAll'), array('type' => 'button', 'class' => 'text-success dropdown-item', 'data-toggle' => 'modal', 'data-target' => '#confirmRestore', 'data-title' => trans('LaravelLoggerUser::laravel-logger.modals.restoreLog.title'),'data-message' => trans('LaravelLoggerUser::laravel-logger.modals.restoreLog.message'))) !!}
{!! Form::close() !!}
