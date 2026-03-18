@if($config->seamless == 'Y' || $config->multigame_open == 'Y')
    @include('module.game_seamless')
@else
    @include('module.game_single')
@endif
