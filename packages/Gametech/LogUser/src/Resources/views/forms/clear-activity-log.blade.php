{!! Form::open(array('route' => 'log-user.clear-activity')) !!}
    {!! Form::hidden('_method', 'DELETE') !!}
    {!! Form::button('<i class="fa fa-fw fa-trash" aria-hidden="true"></i>' . trans('LaravelLoggerUser::laravel-logger.dashboard.menu.clear'), array('type' => 'button', 'data-toggle' => 'modal', 'data-target' => '#confirmDelete', 'data-title' => trans('LaravelLoggerUser::laravel-logger.modals.clearLog.title'),'data-message' => trans('LaravelLoggerUser::laravel-logger.modals.clearLog.message'), 'class' => 'dropdown-item')) !!}
{!! Form::close() !!}
