<b-modal ref="addedit" id="addedit" centered size="sm" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        <b-form-group
            id="input-group-1"
            label="วันที่ใช้จ่าย:"
            label-for="date_pay"
            description="">
            <b-form-datepicker
                id="date_pay"
                v-model="formaddedit.date_pay"
                size="sm"
                placeholder=""
                autocomplete="off"
                locale="en-US"
                @context="onContext"
            ></b-form-datepicker>
        </b-form-group>

        <b-form-group
            id="input-group-name"
            label="ชื่อรายการ (หัวข้อ):"
            label-for="name"
            description="">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder=""
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-amount"
            label="จำนวนเงิน:"
            label-for="amount"
            description="">
            <b-form-input
                id="amount"
                v-model="formaddedit.amount"
                type="number"
                size="sm"
                placeholder=""
                autocomplete="off"
                required

            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.css') }}">
@endpush
@push('scripts')

    <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>

    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    formmethod: 'add',
                    formaddedit: {
                        date_pay: '',
                        amount: 0,
                        name: '',
                    }
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                onContext(ctx) {
                    // The date formatted in the locale, or the `label-no-date-selected` string
                    this.formatted = ctx.selectedFormatted
                    // The following will be an empty string until a valid date is entered
                    this.selected = ctx.selectedYMD
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        date_pay: '',
                        amount: 0,
                        name: '',
                    }

                    this.formmethod = 'edit';

                    this.show = false;
                    this.$nextTick(() => {

                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();
                        this.show = true;

                    })
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        date_pay: moment().format('YYYY-MM-DD'),
                        amount: 0,
                        name: '',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.$refs.addedit.show();
                        this.show = true;

                    })
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        date_pay: response.data.data.date_pay,
                        amount: response.data.data.amount,
                        name: response.data.data.name,
                    }
                },

                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ url($menu->currentRoute.'/create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ url($menu->currentRoute.'/update') }}/" + this.code;
                    }

                    this.$http.post(url, {id: this.code, data: this.formaddedit})
                        .then(response => {

                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            window.LaravelDataTables["dataTableBuilder"].draw(false);
                        })
                        .catch(exception => {
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });

                }


            },
        });
    </script>
@endpush

