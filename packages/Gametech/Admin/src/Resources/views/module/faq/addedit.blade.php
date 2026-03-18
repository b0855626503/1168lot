<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        <b-form-group
            id="input-group-1"
            label="คำถาม:"
            label-for="question"
            description="ระบุคำถาม">
            <b-form-input
                id="question"
                v-model="formaddedit.question"
                type="text"
                size="sm"
                placeholder="คำถาม"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <summernote id="answer" v-model="formaddedit.answer" ref="editor" name="answer"></summernote>

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
                        question: '',
                        answer: '',
                    }
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        question: '',
                        answer: '',
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
                        question: '',
                        answer: '',
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
                    this.formaddedit.question = response.data.data.question;
                    this.formaddedit.answer = response.data.data.answer;
                },
                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formaddedit.answer == '') {
                        $('.editor').summernote('focus');
                        return false;
                    }

                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}";
                    }
                    this.$http.post(url, {id: this.code, data: this.formaddedit})
                        .then(response => {
                            this.$refs.addedit.hide();

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

