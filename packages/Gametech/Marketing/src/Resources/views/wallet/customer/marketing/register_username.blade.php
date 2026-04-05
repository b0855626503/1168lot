{{-- extend layout --}}
@extends('wallet::layouts.marketing')

{{-- page title --}}
@section('title','')

@push('styles')
	
	<style>
        .x-register-tab-container2 {
            max-width: 800px;
            position: relative;
            border: none;
            border-radius: 20px;
            width: 100%;
            max-width: 1100px;
            padding: 30px;
            border: 2px solid #ffe083;
            background: rgba(10, 14, 37, .5882352941);
            border-radius: 10px;
            margin: 0 auto 20px;
            margin-top: 20px;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .input-password {
            width: 100%;
            padding: 10px 40px 10px 10px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .toggle-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            width: 20px;
            height: 20px;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            transition: background-color 9999s ease-in-out 0s;
            -webkit-text-fill-color: white !important;
            box-shadow: 0 0 0px 1000px #000 inset !important;
        }
	</style>

@endpush

@section('content')
	<div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
	     class="lazyload x-bg-position-center x-bg-index lazyload">
		
		<div class="x-index-content-main-container -anon">
			
			
			@include('wallet::customer.marketing.normal_username')
		
		
		</div>
	
	
	</div>
	
	
	<div class="x-modal modal -v2 -with-half-size" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true"
	     data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
		<div
				class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -dialog-in-tab -register-index-dialog"
				role="document">
			<div class="modal-content -modal-content">
				<button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
					<i class="fas fa-times"></i>
				</button>
				
				<div class="x-modal-account-security-tabs js-modal-account-security-tabs -v3">
					
					
					<button type="button" class="-btn -login js-modal-account-security-tab-button -active"
					        data-modal-target="#loginModal">
						{{ __('app.login.login') }}
					</button>
				</div>
				
				<div class="modal-body -modal-body">
					<div class="x-register-tab-container -login js-tab-pane-checker-v3">
						
						
						<div class="tab-content">
							<div class="tab-pane active" id="tab-content-loginPhoneNumber"
							     data-completed-dismiss-modal="">
								<div class="x-modal-body-base -v3 -phone-number x-form-register-v3">
									<div class="row -register-container-wrapper">
										<div class="col">
											<div class="x-title-register-modal-v3">
												<span class="-title">{{ __('app.login.username') }}</span>
												<span
														class="-sub-title">{{ __('app.login.username_login') }}</span>
											</div>
										</div>
										
										<div class="col">
											<div class="-fake-inner-body">
												{{--                                                    <form method="post" data-register-v3-form="v3/check-for-login"--}}
												{{--                                                          data-register-step="loginPhoneNumber">--}}
												<form method="POST"
												      action="{{ route('customer.session.create') }}"
												      @submit.prevent="onSubmit">
													@csrf
													<div
															class="-animatable-container -password-body">
														<input
																type="text"
																required
																autocomplete="off"
																id="user_name"
																name="user_name"
																inputmode="text"
																placeholder=""
																class="form-control x-form-control"
																style="text-transform: lowercase;"
														/>
													</div>
													<div class="-x-input-icon flex-column">
														<input type="password" id="password" name="password"
														       required
														       class="form-control x-form-control"
														       placeholder="XXXXXXXX"/>
													</div>
													
													
													<div class="text-center">
														<button
																class="btn -submit btn-primary mt-lg-3 mt-0">
															{{ __('app.login.submit') }}
														</button>
													</div>
												</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="x-modal modal -v2 x-theme-switcher-v2" id="themeSwitcherModal" tabindex="-1" role="dialog"
	     aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
		<div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable modal-dialog-centered"
		     role="document">
			<div class="modal-content -modal-content">
				<button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
					<i class="fas fa-times"></i>
				</button>
				<div class="modal-body -modal-body">
					<div class="-theme-switcher-container">
						<div class="-inner-header-section">
							<a class="-link-wrapper" href="{{ route('customer.home.index') }}">
								<picture>
									<source type="image/webp"
									        data-srcset="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
									<source type="image/png?v=2"
									        data-srcset="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
									<img
											alt="logo image"
											class="img-fluid lazyload -logo lazyload"
											width="180"
											height="42"
											data-src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"
											src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"
									/>
								</picture>
							</a>
						</div>
						
						<div class="-inner-top-body-section">
							<div class="col-6 -wrapper-box">
								<a
										class="btn -btn-item -top-btn -register-button lazyload x-bg-position-center"
										href="{{ route('customer.session.store') }}"
										data-bgset="/assets/wm356/images/btn-register-login-bg.png?v=2"
										style="background-image: url('/assets/wm356/images/btn-register-login-bg.png?v=2');"
								>
									<picture>
										<source type="image/webp"
										        data-srcset="/assets/wm356/images/ic-modal-menu-register.webp?v=2"/>
										<source type="image/png?v=2"
										        data-srcset="/assets/wm356/images/ic-modal-menu-register.png?v=2"/>
										<img
												alt="รูปไอคอนสมัครสมาชิก"
												class="img-fluid -icon-image lazyload"
												width="50"
												height="50"
												data-src="/assets/wm356/images/ic-modal-menu-register.png?v=2"
												src="/assets/wm356/images/ic-modal-menu-register.png?v=2"
										/>
									</picture>
									
									<div class="-typo-wrapper">
										<div class="-typo">สมัครเลย</div>
									</div>
								</a>
							</div>
							<div class="col-6 -wrapper-box">
								<button
										type="button"
										class="btn -btn-item -top-btn -login-btn lazyload x-bg-position-center"
										data-toggle="modal"
										data-dismiss="modal"
										data-target="#loginModal"
										data-bgset="/assets/wm356/images/btn-register-login-bg.png?v=2"
										style="background-image: url('/assets/wm356/images/btn-register-login-bg.png?v=2');"
								>
									<picture>
										<source type="image/webp"
										        data-srcset="/assets/wm356/images/ic-modal-menu-login.webp?v=2"
										        srcset="/assets/wm356/images/ic-modal-menu-login.webp?v=2">
										<source type="image/png?v=2"
										        data-srcset="/assets/wm356/images/ic-modal-menu-login.png?v=2"
										        srcset="/assets/wm356/images/ic-modal-menu-login.png?v=2">
										<img alt="รูปไอคอนเข้าสู่ระบบ" class="img-fluid -icon-image lazyloaded"
										     width="50" height="50"
										     data-src="/assets/wm356/images/ic-modal-menu-login.png?v=2"
										     src="/assets/wm356/images/ic-modal-menu-login.png?v=2">
									</picture>
									
									<div class="-typo-wrapper">
										<div class="-typo">เข้าสู่ระบบ</div>
									</div>
								</button>
							</div>
						</div>
						
						<div class="-inner-center-body-section">
							<div class="col-6 -wrapper-box">
								<a
										href="{{ route('customer.promotion.show') }}"
										class="btn -btn-item -promotion-button -menu-center -horizontal lazyload x-bg-position-center"
										data-bgset="/assets/wm356/images/btn-register-login-bg.png"
								>
									<picture>
										<source type="image/webp"
										        data-srcset="/assets/wm356/images/ic-modal-menu-promotion.webp?v=2"/>
										<source type="image/png?v=2"
										        data-srcset="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"/>
										<img
												alt="รูปไอคอนโปรโมชั่น"
												class="img-fluid -icon-image lazyload"
												width="65"
												height="53"
												data-src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"
												src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"
										/>
									</picture>
									
									<div class="-typo-wrapper">
										<div class="-typo">โปรโมชั่น</div>
									</div>
								</a>
							</div>
							<div class="col-6 -wrapper-box">
								<a
										href="https://lin.ee/BpAUj1s"
										class="btn -btn-item -line-button -menu-center -horizontal lazyload x-bg-position-center"
										target="_blank"
										rel="noopener nofollow"
										data-bgset="/assets/wm356/images/btn-register-login-bg.png"
								>
									<picture>
										<source type="image/webp"
										        data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.webp?v=2"/>
										<source type="image/png"
										        data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"/>
										<img
												alt="รูปไอคอนดูหนัง"
												class="img-fluid -icon-image lazyload"
												width="65"
												height="53"
												data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
												src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
										/>
									</picture>
									
									<div class="-typo-wrapper">
										<div class="-typo">ไลน์</div>
									</div>
								</a>
							</div>
						</div>
						
						<div class="-inner-bottom-body-section"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<link rel="preload" href="{{ asset('vendor/inputmask/jquery.inputmask.js') }}" as="script">
	<script src="{{ asset('vendor/inputmask/jquery.inputmask.js') }}"></script>
	<script>
        function markmobile() {
            $('#tel').inputmask({
                alias: 'tel',
                mask: "(999)-999-9999",
                removeMaskOnSubmit: true,
                autoUnmask: true,
                clearIncomplete: true,
                clearMaskOnLostFocus: true
            });
        }

        function togglePassword() {
            const passwordInput = document.getElementById("password1");
            const toggleIcon = document.getElementById("toggle-icon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }

        function isNumeric(str) {
            return /^\d+$/.test(str); // true ถ้าเป็นตัวเลขล้วน
        }

        function validateFinalStepWithAlert() {
            let errors = [];

            const username = $('#user_name1').val().trim();
            const tel = $('#tel').val().trim();
            const bankaccount = $('#acc_no').val().trim();
            const firstname = $('#firstname').val().trim();
            const lastname = $('#lastname').val().trim();
            const password = $('#password1').val().trim();
            const refer = $('#refer').val();

            if (!firstname) errors.push('{{ __('app.input.fill', ['field' => __('app.input.firstname')]) }}');
            if (!lastname) errors.push('{{ __('app.input.fill', ['field' => __('app.input.lastname')]) }}');
            if (!password) errors.push('{{ __('app.input.fill', ['field' => __('app.input.password')]) }}');
            if (!refer) errors.push('{{ __('app.input.select', ['field' => __('app.input.refer')]) }}');
            if (!username) errors.push('{{ __('app.input.fill', ['field' => __('app.register.username')]) }}');
            if (!tel) errors.push('{{ __('app.input.fill', ['field' => __('app.register.tel')]) }}');
            if (!bankaccount) errors.push('{{ __('app.input.fill', ['field' => __('app.register.bank_account')]) }}');

            if (errors.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __('app.status.nodata') }}',
                    html: '<ul style="text-align:left;">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>',
                    confirmButtonText: '{{ __('app.status.ok') }}'
                });

                return false;
            }
            console.log('เชคผ่านแล้ว');
            return true;
        }

        function trans(key, replace = {}) {
            var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

            for (var placeholder in replace) {
                translation = translation.replace(`:${placeholder}`, replace[placeholder]);
            }
            return translation;
        }


        $(function () {
            markmobile();

            $('#bank').on('change', function () {
                if ($('#bank option:selected').val() === '18') {
                    // $('#acc_no_tw').prop('required',true);
                    $('.acc_no_tw').css('display', 'block');
                    $('.tw').css('display', 'block');
                } else {
                    // $('#acc_no_tw').prop('required',false);
                    $('.acc_no_tw').css('display', 'none');
                    $('.tw').css('display', 'none');
                }
            });

            let userTimer;
            $('#user_name1').on('input', function () {
                clearTimeout(userTimer);
                const user = document.getElementById('user_name1').value.trim();
                const status = document.getElementById('user-status');
                userTimer = setTimeout(function () {
                    if (user.length >= 5) {

                        // window.app.checkPhone(phone);

                        axios.post("{{ route('customer.check.user') }}", {username: user})
                            .then(res => {
                                const status = document.getElementById('user-status');
                                if (res.data.exists) {
                                    status.innerText = 'ID นี้มีในระบบแล้ว';
                                    status.style.color = 'red';
                                } else {
                                    status.innerText = 'สามารถใช้ได้';
                                    status.style.color = 'green';
                                }
                            })
                            .catch(() => {
                                status.innerText = 'เกิดข้อผิดพลาด';
                                status.style.color = 'gray';
                            });

                    } else {
                        status.innerText = '';

                    }
                }, 500);
            });


            let phoneTimer;
            $('#tel').on('input', function () {
                clearTimeout(phoneTimer);
                const phone = document.getElementById('tel').value.trim();
                const status = document.getElementById('phone-status');

                if (!isNumeric(phone)) {
                    status.innerText = trans('app.register.numberonly');
                    status.style.color = 'red';
                    return;
                }

                phoneTimer = setTimeout(function () {
                    if (phone.length === 10) {

                        // window.app.checkPhone(phone);

                        axios.post("{{ route('customer.check.phone') }}", {tel: phone})
                            .then(res => {
                                const status = document.getElementById('phone-status');
                                if (res.data.exists) {
                                    status.innerText = res.data.message;
                                    status.style.color = 'red';
                                } else {
                                    status.innerText = res.data.message;
                                    status.style.color = 'green';
                                }
                            })
                            .catch(() => {
                                status.innerText = 'เกิดข้อผิดพลาด';
                                status.style.color = 'gray';
                            });

                    } else {
                        status.innerText = '';

                    }
                }, 500);
            });

            // เช็คธนาคาร + บัญชีเมื่อเปลี่ยน
            let bankTimer;
            $('#bank, #acc_no').on('input change', function () {
                clearTimeout(bankTimer);

                const bank = document.getElementById('bank').value;
                const account = document.getElementById('acc_no').value.trim();
                const status = document.getElementById('account-status');

                if (!isNumeric(account)) {
                    status.innerText = trans('app.register.numberonly');
                    status.style.color = 'red';
                    return;
                }

                if (bank === 18) return

                if (bank && account.length >= 10) {
                    bankTimer = setTimeout(function () {

                        axios.post("{{ route('customer.check.bank') }}", {bank: bank, acc_no: account})
                            .then(res => {

                                if (res.data.valid) {
                                    status.innerText = res.data.message;
                                    status.style.color = 'red';
                                } else {
                                    $('#firstname').val(res.data.firstname);
                                    $('#lastname').val(res.data.lastname);
                                    status.innerText = res.data.message;
                                    status.style.color = 'green';
                                }
                            })
                            .catch(() => {
                                status.innerText = 'เกิดข้อผิดพลาด';
                                status.style.color = 'gray';
                            });

                    }, 500);
                } else {
                    status.innerText = '';
                }
            });
			
			{{--let isFormReady = false;--}}
			{{--$(document).on('click', '#btnregister', function (e) {--}}
			{{--    e.preventDefault();--}}
			
			
			{{--    if (!validateFinalStepWithAlert()) {--}}
			{{--        return;--}}
			{{--    }--}}
			
			
			{{--    isFormReady = true;--}}
			{{--    // $submitBtn.prop('disabled', false);--}}
			
			{{--    const $form = $('#registerForm');--}}
			{{--    const formData = new FormData($form[0]);--}}
			
			{{--    const $submitBtn = $form.find('button[type="submit"], button[type="button"]');--}}
			{{--    // $submitBtn.prop('disabled', true);--}}
			{{--    console.log('start ajax');--}}
			{{--    console.log('formData:', formData);--}}
			{{--    for (var pair of formData.entries()) {--}}
			{{--        console.log(pair[0] + ', ' + pair[1]);--}}
			{{--    }--}}
			{{--    if (!$form.length) {--}}
			{{--        console.error('❌ ไม่พบฟอร์ม registerForm');--}}
			{{--        return;--}}
			{{--    }--}}
			{{--    console.log('✅ พบฟอร์มแล้ว');--}}
			
			
			{{--    axios.post('{{ route('customer.session.register') }}', formData, {--}}
			{{--        headers: {--}}
			{{--            'Content-Type': 'multipart/form-data',--}}
			{{--            'Accept': 'application/json'--}}
			{{--        }--}}
			{{--    })--}}
			{{--        .then(function (res) {--}}
			
			{{--            console.log(res);--}}
			{{--            if (res.data.success) {--}}
			
			{{--                Swal.fire({--}}
			{{--                    title: '{{ __('app.status.result') }}',--}}
			{{--                    html: '{{ __('app.register.success') }}',--}}
			{{--                    icon: 'success',--}}
			{{--                    showCancelButton: true,--}}
			{{--                    confirmButtonText: '{{ __('app.login.login') }}', // เพิ่มในภาษา--}}
			{{--                    cancelButtonText: '{{ __('app.status.cancel') }}',    // เพิ่มในภาษา--}}
			{{--                    reverseButtons: true,--}}
			{{--                }).then((result) => {--}}
			{{--                    if (result.isConfirmed) {--}}
			{{--                        // 🔁 กดยืนยัน → ส่งใหม่พร้อม force = true--}}
			{{--                        window.location.href = '{{ route('customer.home.index') }}';--}}
			{{--                    } else {--}}
			{{--                        $('#registerForm')[0].reset();--}}
			{{--                        $submitBtn.prop('disabled', false);--}}
			{{--                    }--}}
			{{--                });--}}
			
			{{--            } else {--}}
			{{--                Swal.fire({--}}
			{{--                    icon: 'warning',--}}
			{{--                    text: res.data.message,--}}
			{{--                    confirmButtonText: '{{ __('app.status.ok') }}'--}}
			{{--                });--}}
			
			{{--            }--}}
			{{--        })--}}
			{{--        .catch(function (error) {--}}
			{{--            console.error(error);--}}
			{{--            Swal.fire({--}}
			{{--                icon: 'error',--}}
			{{--                text: '{{ __('app.status.error') }}',--}}
			{{--                confirmButtonText: '{{ __('app.status.ok') }}'--}}
			{{--            });--}}
			{{--        });--}}
			{{--	--}}
			{{--	--}}
			{{--	--}}{{--$.ajax({--}}
			{{--	--}}{{--    url: '{{ route('customer.session.register') }}',--}}
			{{--	--}}{{--    method: 'POST',--}}
			{{--	--}}{{--    data: formData,--}}
			{{--	--}}{{--    processData: false,--}}
			{{--	--}}{{--    contentType: false,--}}
			{{--	--}}{{--    dataType: 'json',--}}
			{{--	--}}{{--    headers: {--}}
			{{--	--}}{{--        'X-CSRF-TOKEN': formData.get('_token'),--}}
			{{--	--}}{{--        'Accept': 'application/json' // สำคัญ เพื่อให้ Laravel จัดการ error แบบ JSON--}}
			{{--	--}}{{--    },--}}
			{{--	--}}{{--    success: function (res) {--}}
			{{--	--}}{{--        // ✅ กรณีสมัครสำเร็จ--}}
			{{--	--}}{{--        if (res.success) {--}}
			{{--	--}}
			{{--	--}}{{--            Swal.fire({--}}
			{{--	--}}{{--                title: '{{ __('app.status.result') }}',--}}
			{{--	--}}{{--                text: '{{ __('app.status.success') }}',--}}
			{{--	--}}{{--                icon: 'warning',--}}
			{{--	--}}{{--                showCancelButton: true,--}}
			{{--	--}}{{--                confirmButtonText: '{{ __('app.login.login') }}', // เพิ่มในภาษา--}}
			{{--	--}}{{--                cancelButtonText: '{{ __('app.status.cancel') }}',    // เพิ่มในภาษา--}}
			{{--	--}}{{--                reverseButtons: true,--}}
			{{--	--}}{{--            }).then((result) => {--}}
			{{--	--}}{{--                if (result.isConfirmed) {--}}
			{{--	--}}{{--                    // 🔁 กดยืนยัน → ส่งใหม่พร้อม force = true--}}
			{{--	--}}{{--                    window.location.href = '{{ route('customer.home.index') }}';--}}
			{{--	--}}{{--                } else {--}}
			{{--	--}}{{--                    $('#registerForm')[0].reset();--}}
			{{--	--}}{{--                    $submitBtn.prop('disabled', false);--}}
			{{--	--}}{{--                }--}}
			{{--	--}}{{--            });--}}
			{{--	--}}
			{{--	--}}{{--        } else {--}}
			{{--	--}}{{--            Swal.fire({--}}
			{{--	--}}{{--                icon: 'warning',--}}
			{{--	--}}{{--                text: res.message,--}}
			{{--	--}}{{--                confirmButtonText: '{{ __('app.status.ok') }}'--}}
			{{--	--}}{{--            });--}}
			{{--	--}}
			{{--	--}}{{--        }--}}
			{{--	--}}{{--    },--}}
			{{--	--}}{{--    error: function (xhr) {--}}
			{{--	--}}{{--        // ❌ แจ้งเตือนเมื่อเกิด error จาก server หรือ network--}}
			{{--	--}}{{--        console.error(xhr);--}}
			{{--	--}}{{--        Swal.fire({--}}
			{{--	--}}{{--            icon: 'error',--}}
			{{--	--}}{{--            text: '{{ __('app.status.error') }}',--}}
			{{--	--}}{{--            confirmButtonText: '{{ __('app.status.ok') }}'--}}
			{{--	--}}{{--        });--}}
			{{--	--}}{{--    },--}}
			{{--	--}}{{--    complete: function () {--}}
			{{--	--}}{{--        $submitBtn.prop('disabled', false); // เปิดปุ่มอีกครั้ง--}}
			{{--	--}}{{--    }--}}
			{{--	--}}{{--});--}}
			{{--});--}}
        });
	</script>

@endpush

