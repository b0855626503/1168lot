<nav class=" navbar navbar-expand border-bottom nav-header nav-top">
    <div class="container">
        <div class="row w-100">
            <div class="col-3">@yield('back')</div>
            {!! core()->showImg($config->logo,'img','','','img-top') !!}
            <div class="col-1 offset-8">
                <a href="{{ route('customer.session.destroy') }}"
                   class="nav-link text-light p-2 signout-btn mx-auto hand-point" data-widget="control-sidebar">
                    <i class="fal fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>
