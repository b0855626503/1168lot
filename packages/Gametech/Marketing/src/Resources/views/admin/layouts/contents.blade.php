<!-- Content Wrapper. Contains page content -->


<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1> @yield('campaign_name') </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.marketing_campaign.index') }}">Campaigns</a></li>
                        <li class="breadcrumb-item active">@yield('campaign_name')</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>


    @yield('content')


</div>
