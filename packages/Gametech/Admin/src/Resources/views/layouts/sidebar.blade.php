<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="javascript:void(0)" class="brand-link {{ ($config->admin_brand_color?$config->admin_brand_color:'navbar-gray-dark') }}">
        {!! core()->showImg($config->logo,'img','','','brand-image img-circle elevation-3') !!}
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
{{--        <div class="user-panel mt-3 pb-3 mb-3 d-flex">--}}
{{--            <div class="image">--}}
{{--                {!! core()->showImg('logo.png','game_img','','','img-circle elevation-2') !!}--}}

{{--            </div>--}}
{{--            <div class="info">--}}
{{--                <a href="javascript:void(0)" class="d-block">{{ auth()->guard('admin')->user()->name }}</a>--}}
{{--            </div>--}}
{{--        </div>--}}


        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-collapse-hide-child nav-child-indent nav-legacy nav-compact" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                @include('admin::layouts.menu')
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

