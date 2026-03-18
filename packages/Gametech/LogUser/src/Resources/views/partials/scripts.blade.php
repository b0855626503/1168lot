
@if(config('LaravelLoggerUser.enablejQueryCDN'))
    <script type="text/javascript" src="{{ config('LaravelLoggerUser.JQueryCDN') }}"></script>
@endif

@if(config('LaravelLoggerUser.enableBootstrapJsCDN'))
    <script type="text/javascript" src="{{ config('LaravelLoggerUser.bootstrapJsCDN') }}"></script>
@endif

@if(config('LaravelLoggerUser.enablePopperJsCDN'))
    <script type="text/javascript" src="{{ config('LaravelLoggerUser.popperJsCDN') }}"></script>
@endif

@if(config('LaravelLoggerUser.loggerDatatables'))
    @if (count($activities) > 10)
        @include('LaravelLoggerUser::scripts.datatables')
    @endif
@endif

@include('LaravelLoggerUser::scripts.add-title-attribute')
