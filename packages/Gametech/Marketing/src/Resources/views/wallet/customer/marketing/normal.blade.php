<div id="page-register" class="register-container sub-page sub-footer vhm-100">
	<div id="block-register" class="register-inner-content card shadow">
		<h4 class="card-title text-center pt-3">{{ __('app.login.register') }}</h4>
		<div class="card-body pt-0 px-0">
			<div class="theme-form">
				<form method="POST" ref="form" action="{{ route('customer.session.register') }}"
					  @submit.prevent="onSubmit">
					@csrf
					@if($id)
						<div id="zone-contributor">
							<input type="hidden" id="marketing" name="marketing" value="{!! $id !!}">
						</div>
					@endif

					{{-- กล่องแจ้งเตือนสั้น ๆ (ถ้าจะใช้) --}}
					<small id="form-global-status" class="form-text text-center mb-2"></small>

					<div class="container-fluid">
						<div class="row g-3">



							{{-- ธนาคาร --}}
							<div class="col-12 col-md-12">
								<div class="text-danger small">{{ __('app.register.warning') }}</div>
								<label>{{ __('app.register.bank') }}</label>
								<select style="width:100%" class="form-control x-form-control bank-select" id="bank"
										name="bank"
										v-validate="'required'"
										:class="[errors.has('bank') ? 'is-invalid' : '']">
									<option value="">{{ __('app.register.select_bank') }}</option>
									@php
										$lang = Session::get('lang', 'th'); // ถ้าไม่มีค่าก็ใช้ th เป็นค่าเริ่มต้น
                                        if($lang != 'th'){
                                            $lang = 'en'; // ถ้าไม่ใช่ภาษาไทย ให้ใช้ภาษาอังกฤษ
                                        }
                                        $field = "name_{$lang}";
									@endphp
									@foreach($banks as $i => $bank)
										<option
												data-img="{{ url('/storage/bank_img/'.$bank->filepic) }}"
												value="{{ $bank->code }}" {{ old('bank') == $bank->code ? 'selected' : '' }}>{{ $bank->name_th .' - '. $bank->name_en }}</option>
									@endforeach
								</select>
								{{--                                    <select class="form-control x-form-control" id="bank" name="bank"--}}
								{{--                                            v-validate="'required'"--}}
								{{--                                            :class="[errors.has('bank') ? 'is-invalid' : '']">--}}
								{{--                                        <option value="">{{ __('app.register.select_bank') }}</option>--}}
								{{--                                        @foreach($banks as $i => $bank)--}}
								{{--                                            <option value="{{ $bank->code }}" {{ old('bank') == $bank->code ? 'selected' : '' }}>--}}
								{{--                                                {{ $bank->name_th }}--}}
								{{--                                            </option>--}}
								{{--                                        @endforeach--}}
								{{--                                    </select>--}}
								<small class="control-error text-warning text-center" v-if="errors.has('bank')">
									@{{ errors.first('bank') }}
								</small>
							</div>

							{{-- เลขบัญชี --}}
							<div class="col-12 col-md-6">
								<label>{{ __('app.register.bank_account') }}</label>
								<div class="input-group input-group-lg">
									<span class="input-group-text"><i class="bi bi-credit-card-2-front-fill"></i></span>
									<input
											inputmode="numeric"
											oninput="this.value = this.value.replace(/[^0-9]/g, '')"
											autocomplete="off" class="form-control x-form-control" id="acc_no"
											minlength="5" maxlength="12"
											data-vv-as="&quot;{{ __('app.register.bank_account') }}&quot;"
											value="{{ old('acc_no') }}"
											v-validate="'required|min:5|numeric'"
											:class="[errors.has('acc_no') ? 'is-invalid' : '']"
											name="acc_no"
											placeholder="{{ __('app.register.bank_placeholder') }}"
											type="text">
								</div>
								<small id="account-status" class="form-text text-center"></small>
								<small class="control-error text-warning text-center" v-if="errors.has('acc_no')">
									@{{ errors.first('acc_no') }}
								</small>
							</div>

							{{-- ชื่อ-นามสกุล (รวมในฟิลด์เดียวตามเดิม) --}}
							<div class="col-12 col-md-6">
								<label>{{ __('app.register.name') }} {{ __('app.register.surname') }}</label>
								<div class="input-group input-group-lg">
									<span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
									<input autocomplete="off" class="form-control x-form-control" id="name"
										   name="name"
										   v-validate="'required'"
										   :class="[errors.has('name') ? 'is-invalid' : '']"
										   data-vv-as="&quot;name&quot;"
										   value="{{ old('name') }}"
										   placeholder="{{ __('app.register.no_position') }}" type="text">
								</div>
								<small class="control-error text-warning text-center" v-if="errors.has('name')">
									@{{ errors.first('name') }}
								</small>
							</div>

							{{-- เบอร์โทร --}}
							<div class="col-12 col-md-6">
								<label class="text-content">{{ __('app.register.tel') }}</label>
								<div class="input-group input-group-lg">
									<span class="input-group-text"><i class="bi bi-phone-fill"></i></span>
									<input
											autocomplete="off"
											data-vv-as="&quot;{{ __('app.register.tel') }}&quot;"
											class="form-control x-form-control" id="user_name1"
											name="user_name" maxlength="10" minlength="10"
											placeholder="{{ __('app.register.username_placeholder') }}"
											value="{{ old('user_name') }}"
											v-validate="'required'"
											:class="[errors.has('user_name') ? 'is-invalid' : '']"
											pattern="[0-9]*" inputmode="numeric"
											oninput="this.value = this.value.replace(/[^0-9]/g, '')"
											type="text">
								</div>
								<small id="phone-status" class="form-text text-center"></small>
								<small class="control-error text-warning text-center" v-if="errors.has('user_name')">
									@{{ errors.first('user_name') }}
								</small>
							</div>

							{{-- รหัสผ่าน --}}
							<div class="col-12 col-md-6">
								<label>{{ __('app.register.password') }}</label>
								<div class="input-group input-group-lg">
									<span class="input-group-text"><i class="bi bi-key-fill"></i></span>
									<input autocomplete="off"
										   minlength="6" maxlength="10"
										   data-vv-as="&quot;{{ __('app.register.password') }}&quot;"
										   class="form-control x-form-control input-password" id="password1"
										   v-validate="'required|min:6|max:10'"
										   value="{{ old('password') }}"
										   :class="[errors.has('password') ? 'is-invalid' : '' ]"
										   name="password" placeholder="{{ __('app.register.password') }}"
										   type="text"
										   ref="password">
								</div>
								<small class="control-error text-warning text-center" v-if="errors.has('password')">
									@{{ errors.first('password') }}
								</small>
							</div>


							{{-- refer (ซ่อนไว้เหมือนเดิม ถ้าต้องใช้เลือกให้แสดง) --}}
							<div class="col-12 col-md-6" style="display:none;">
								<label>{{ __('app.register.refer') }}</label>
								<select class="form-control x-form-control" id="refer" name="refer"
										v-validate="'required'"
										data-vv-as="&quot;{{ __('app.register.refer') }}&quot;"
										:class="[errors.has('refer') ? 'is-invalid' : '']">
									@foreach($refers as $i => $refer)
										<option value="{{ $refer->code }}">{{ $refer->name }}</option>
									@endforeach
								</select>
								<small class="control-error text-warning text-center" v-if="errors.has('refer')">
									@{{ errors.first('refer') }}
								</small>
							</div>
						</div>

						<hr>

						<div class="d-flex flex-column flex-md-row w-100 gap-2 mt-1">
							{{--                                <a href="{{ route('customer.session.index') }}" class="btn btn-secondary rounded-pill w-100">--}}
							{{--                                    <i class="bi bi-arrow-left"></i> {{ __('app.status.prev') }}--}}
							{{--                                </a>--}}
							<button type="submit" class="btn btn-success rounded-pill w-100 regisbtn">
								<i class="bi bi-person-plus-fill"></i> {{ __('app.login.register') }}
							</button>
						</div>

					</div>
				</form>

				<div class="d-inline-flex w-100 mt-3 justify-content-between">
					<div></div>
					<div>
						<a href="{{ $webconfig->linelink }}" target="_blank"
						   class="btn btn-link btn-sm text-white">{{ __('app.login.help') }}</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>





