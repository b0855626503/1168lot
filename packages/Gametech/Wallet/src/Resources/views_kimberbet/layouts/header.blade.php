<nav id="main-nav" class="navbar navbar-expand-sm navbar-light">
	<div class="container" style="max-height: 100%;">
		<div class="d-inline-flex align-items-center ham-menu">
			<button class="navbar-toggler p-0" type="button" data-bs-toggle="collapse"
					data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
					aria-expanded="false" aria-label="Toggle navigation" style="height: 35px; width: 35px;">
				<span class="bi bi-list bi-2x text-light"></span>
			</button>
		</div>

		<a href="{{ route('customer.session.index') }}"
		   class="navbar-brand m-0 d-flex align-items-center not-login-stay-left">
			<img id="main-logo" src="{{ url(core()->imgurl($webconfig->logo,'img')) }}">
		</a>

		<div id="auth-wrapper" class="group-button-user p-1 rounded-pill login-b">
			<div class="d-inline-flex">
				<a href="{{ route('customer.session.store') }}"
				   class="nav-link register-btn btn btn-custom-secondary rounded-pill d-flex align-items-center pt-1 pb-1 text-white justify-content-center homeregis"
				   aria-label="register">
          <span class="fw-bold text-highlight d-flex align-items-center">
            <i class="bi bi-person-plus-fill me-1 text-white"></i>
            @{{ trans('app.login.register') }}
          </span>
				</a>

				<a href="{{ route('customer.session.index') }}"
				   class="nav-link login-btn btn btn-custom-primary rounded-pill gradient d-flex align-items-center pt-1 pb-1 ms-2 justify-content-center homelogin"
				   aria-label="login">
          <span class="fw-bold text-highlight d-flex align-items-center">
            <i class="bi bi-box-arrow-in-right me-1"></i>
            @{{ trans('app.login.login') }}
          </span>
				</a>
			</div>
		</div>

		<div class="collapse navbar-collapse navbar-content-index" id="navbarSupportedContent">
			<div class="navbar-nav ms-auto align-items-center">

				<li class="nav-item header-group-menu pt-3">
					<span>@{{ trans('app.login.pages') }}</span>
				</li>

				<li class="nav-item bg-box-1 nc-home btn-home">
					<a href="{{ route('customer.session.index') }}"
					   class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative">
						<span class="text-highlight">@{{ trans('app.login.home') }}</span>
					</a>
				</li>

				<li class="nav-item bg-box-1 line__ti_p_390ypsoj btn-contact">
					<a href="{{ $webconfig->linelink }}"
					   class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative"
					   target="_blank" rel="noopener">
						<span class="text-highlight">@{{ trans('app.login.contact') }}</span>
					</a>
				</li>

				{{-- Desktop language dropdown --}}
				<li class="nav-item bg-box-1 btn-language dropdown d-none d-md-block">
					<a class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative dropdown-toggle"
					   href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						<span class="text-highlight">@{{ trans('app.login.language') }}</span>
					</a>
					<ul class="dropdown-menu" aria-labelledby="navbarDropdown">
						@foreach($languages as $code => $lang)
							<li>
								<a class="dropdown-item" href="javascript:void(0)"
								   onclick="AppLocale.switchTo('{{ $code }}')">
									<img src="/images/flag/{{ $code }}.png" width="32" height="32" class="img img-fluid img-sm">
									{{ $lang['name'] }}
								</a>
							</li>
						@endforeach
					</ul>
				</li>

				{{-- Mobile language list --}}
				<li class="nav-item header-group-menu pt-3">
					<span>@{{ trans('app.login.language') }}</span>
				</li>

				@foreach($languages as $code => $lang)
					<li class="nav-item bg-box-1 nc-home btn-language d-block d-md-none">
						<a href="javascript:void(0)"
						   class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative"
						   onclick="AppLocale.switchTo('{{ $code }}')">
              <span class="text-highlight">
                <img src="/images/flag/{{ $code }}.png" width="32" height="32" class="img img-fluid img-sm">
                {{ $lang['name'] }}
              </span>
						</a>
					</li>
				@endforeach

			</div>
		</div>
	</div>
</nav>
