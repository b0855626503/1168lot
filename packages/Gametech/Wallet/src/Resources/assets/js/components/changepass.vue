<template>
    <div class="p-1">
        <div class="headsecion">
            <i class="fas fa-user-lock"></i>  {{ __('app.home.changepass') }}
        </div>
        <div class="ctpersonal">
            <form class="mt-4" id="frmchangepass" @submit.prevent="validateBeforeSubmit" ref="form" method="post">
                <div class="boxpsl">
                    <span> {{ __('app.home.old_password') }}</span>
                    <input type="password" :class="[errors.has('old_password') ? 'is-invalid' : '']"
                           data-vv-as="&quot;รหัสผ่านเดิม&quot;"
                           id="old_password" name="old_password" v-validate="'required|min:6'"
                           v-model="old_password">
                    <span class="control-error" v-if="errors.has('old_password')">{{ errors.first('old_password') }}</span>
                </div>
                <div class="boxpsl">
                    <span> {{ __('app.home.new_password') }}</span>
                    <input type="password" :class="[errors.has('password') ? 'is-invalid' : '']"
                           data-vv-as="&quot;รหัสผ่านใหม่&quot;" ref="password"
                           id="password" name="password" v-validate="'required|min:6'" v-model="password">
                    <span class="control-error" v-if="errors.has('password')">{{ errors.first('password') }}</span>
                </div>
                <div class="boxpsl">
                    <span> {{ __('app.home.new_password_confirm') }}</span>
                    <input type="password"
                           :class="[errors.has('password_confirmation') ? 'is-invalid' : '']"
                           id="password_confirmation" name="password_confirmation"
                           data-vv-as="&quot;ยืนยันรหัสผ่านใหม่&quot;" data-vv-name="password_confirmation"
                           v-validate="'required|min:6|confirmed:password'" v-model="password_confirmation">
                    <span class="control-error" v-if="errors.has('password_confirmation')">{{ errors.first('password_confirmation') }}</span>
                </div>
            <button class="btnLogin" id="btnLogin"> {{ __('app.home.changepass') }}</button>
            </form>

        </div>

    </div>
</template>
<script>
export default {

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
            console.log('validate');
            this.$validator.validateAll().then(result => {
                if (result) {
                    this.submit();
                } else {
                    eventBus.$emit('onFormError')
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
                '_token': document.head.querySelector('meta[name="csrf-token"]').content
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
}


</script>
