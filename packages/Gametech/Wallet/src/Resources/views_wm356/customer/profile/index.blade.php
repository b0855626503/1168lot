@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


@section('content')
    <div class="p-1">
        <div class="headsecion">
            <i class="fas fa-user-alt"></i> {{ __('app.profile.profile') }}
        </div>
        <div class="ctpersonal grid mt-4">
            <div class="boxpsl">
                <span>{{ __('app.profile.username') }} *</span>
                <input type="text" value="{{ $profile->user_name }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.name') }}</span>
                <input type="text" value="{{ $profile->name }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.tel') }}</span>
                <input type="text" value="{{ $profile->tel }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.credit') }}</span>
                <input type="text" value="{{ $profile->balance }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.point') }}</span>
                <input type="text" value="{{ $profile->point_deposit }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.diamond') }}</span>
                <input type="text" value="{{ $profile->diamond }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.bank') }}</span>
                <input type="text" value="{{ (!is_null($profile->bank) ? $profile->bank->shortcode : '') }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>{{ __('app.profile.account') }}</span>
                <input type="text" value="{{ $profile->acc_no }}" name="test" readonly="">
            </div>
            <div class="boxpsl">
                <span>โปรโมชั่น</span>
                <btn-changepro></btn-changepro>
            </div>
        </div>

    </div>



@endsection

@push('scripts')
    <script type="text/x-template" id="btn-changepro">
        @if($profile->promotion == 'Y')
            <button type="button" class="btn btn-sm btn-success btn-block" ref="btnchangepro"
                    v-on:click="changePro">
                <i class="far fa-check"></i> รับโปรโมชั่น
            </button>
        @else
            <button type="button" class="btn btn-sm btn-danger btn-block" ref="btnchangepro"
                    v-on:click="changePro">
                <i class="far fa-times"></i> ไม่รับโปรโมชั่น
            </button>
        @endif
    </script>

    <script type="module">


        Vue.component('example-modal', {
            template: '#example-modal',
            data: function () {
                return {
                    password: '',
                    old_password: '',
                    password_confirmation: ''
                }
            },
            mounted() {
                this.$validator.errors.clear();
            },
            methods: {
                validateBeforeSubmit() {
                    this.$validator.validate().then(valid => {

                        if (valid) {
                            this.submit();
                        }


                    });
                },
                showModalNew() {
                    console.log('tester');
                    let element = this.$refs.modal.$el
                    console.log(element);
                    $(element).modal('show')
                },
                submit() {

                    this.$http.post(`${this.$root.baseUrl}/member/profile/changepass`, {
                        old_password: this.old_password,
                        password: this.password,
                        password_confirmation: this.password_confirmation,
                        '_token': "{{ csrf_token() }}"
                    })
                        .then(response => {
                            $('.modal').modal('hide');

                            if (response.data.success) {
                                Swal.fire(
                                    'เปลี่ยนรหัสผ่าน',
                                    response.data.message,
                                    'success'
                                )
                            } else {
                                Swal.fire(
                                    'เปลี่ยนรหัสผ่าน',
                                    'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ ในขณะนี้',
                                    'error'
                                )
                            }
                            this.old_password = '';
                            this.password = '';
                            this.password_confirmation = '';
                            this.$validator.reset();
                        })
                        .catch(exception => {
                            $('.modal').modal('hide');
                            Swal.fire(
                                'เปลี่ยนรหัสผ่าน',
                                'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ เนื่องจากข้อมูล รหัสผ่านไม่ถูกต้อง',
                                'error'
                            );
                        });

                },

            }
        });

        Vue.component('btn-changepro', {
            template: '#btn-changepro',
            methods: {
                changePro: function (event) {
                    Swal.fire({
                        title: 'แก้ไขการรับโปรโมชั่น',
                        text: "คุณต้องการดำเนินการ ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.$http.post(`${this.$root.baseUrl}/member/profile/changepro`)
                                .then(response => {
                                    $('.modal').modal('hide');

                                    if (response.data.success) {
                                        Swal.fire(
                                            'การดำเนินการ',
                                            response.data.message,
                                            'success'
                                        )
                                        window.location.href = window.location;
                                    } else {
                                        Swal.fire(
                                            'การดำเนินการ',
                                            'ไม่สามารถเปลี่ยนข้อมูลการรับโปรโมชั่นได้ ในขณะนี้',
                                            'error'
                                        )
                                    }
                                })
                                .catch(exception => {
                                    $('.modal').modal('hide');
                                    Swal.fire(
                                        'การดำเนินการ',
                                        'เกิดข้อผิดพลาดบางประการ โปรดลองใหม่อีกครั้ง',
                                        'error'
                                    );
                                });
                        }
                    })
                }
            }
        });

        Vue.component('btn-reset', {
            template: '#btn-reset',
            methods: {
                gameReset: function (event) {
                    Swal.fire({
                        title: 'รีเซ็ตรหัสผ่านเกม (Wallet) ทั้งหมด',
                        text: "คุณต้องการดำเนินการ ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.$http.post(`${this.$root.baseUrl}/member/profile/resetgamepass`)
                                .then(response => {
                                    $('.modal').modal('hide');

                                    if (response.data.success) {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            response.data.message,
                                            'success'
                                        )
                                    } else {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ ในขณะนี้',
                                            'error'
                                        )
                                    }
                                    this.old_password = '';
                                    this.password = '';
                                    this.password_confirmation = '';
                                    this.$validator.reset();
                                })
                                .catch(exception => {
                                    $('.modal').modal('hide');
                                    Swal.fire(
                                        'เปลี่ยนรหัสผ่าน',
                                        'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ เนื่องจากข้อมูล รหัสผ่านไม่ถูกต้อง',
                                        'error'
                                    );
                                });
                        }
                    })
                }
            }
        });

        Vue.component('btnfree-reset', {
            template: '#btnfree-reset',
            methods: {
                gameFreeReset: function (event) {
                    Swal.fire({
                        title: 'รีเซ็ตรหัสผ่านเกม (Cashback) ทั้งหมด',
                        text: "คุณต้องการดำเนินการ ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.$http.post(`${this.$root.baseUrl}/member/profile/resetgamefreepass`)
                                .then(response => {
                                    $('.modal').modal('hide');

                                    if (response.data.success) {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            response.data.message,
                                            'success'
                                        )
                                    } else {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ ในขณะนี้',
                                            'error'
                                        )
                                    }
                                    this.old_password = '';
                                    this.password = '';
                                    this.password_confirmation = '';
                                    this.$validator.reset();
                                })
                                .catch(exception => {
                                    $('.modal').modal('hide');
                                    Swal.fire(
                                        'เปลี่ยนรหัสผ่าน',
                                        'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ เนื่องจากข้อมูล รหัสผ่านไม่ถูกต้อง',
                                        'error'
                                    );
                                });
                        }
                    })
                }
            }
        });


    </script>
@endpush





