<b-container class="bv-example-row">
    <b-form @submit.prevent="onSubmit" v-if="show" id="frmaddedit" ref="frmaddedit" method="POST"
            action="{{ route('admin.'.$menu->currentRoute.'.update').'/1' }}">
        @csrf
        <b-form-row>
            <b-col>
                <b-card title="ข้อความประกาศ">
                    <b-card-text>
                        <b-form-group
                            id="input-group-content"
                            label="ข้อความ :"
                            label-for="content"
                            description="">
                            <b-form-textarea
                                id="content"
                                name="content"
                                v-model="formaddedit.content"
                                placeholder=""
                                rows="3"
                                max-rows="6"
                            ></b-form-textarea>
                        </b-form-group>

                    </b-card-text>
                </b-card>
            </b-col>
        </b-form-row>


        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-container>

@push('scripts')
    <script type="text/javascript">
        (() => {
            window.app = new Vue({
                el: '#app',
                data() {
                    return {
                        show: false,
                        formmethod: 'edit',
                        code: 1,
                        formaddedit: {
                            content: ''
                        }
                    };
                },
                mounted() {
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.loadData();
                    })
                },
                methods: {
                    editModal(code) {
                        this.code = null;
                        this.formaddedit.content = '';
                        this.formmethod = 'edit';

                        this.show = false;
                        this.$nextTick(() => {
                            this.show = true;
                            this.code = code;
                            this.loadData();
                            this.$refs.addedit.show();

                        })
                    },
                    addModal() {
                        this.code = null;
                        this.formaddedit.name = '';
                        this.formmethod = 'add';

                        this.show = false;
                        this.$nextTick(() => {
                            this.show = true;
                            this.$refs.addedit.show();

                        })
                    },
                    async loadData() {
                        const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                        this.formaddedit.content = response.data.data.content;
                        console.log(this.formaddedit.content);
                    },
                    addEditSubmit(event) {
                        event.preventDefault();
                        this.toggleButtonDisable(true);
                        if (this.formmethod === 'add') {
                            var url = "{{ url($menu->currentRoute.'/create') }}";
                        } else if (this.formmethod === 'edit') {
                            var url = "{{ url($menu->currentRoute.'/update') }}";
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

                            })
                            .catch(exception => {
                                console.log('error');
                            });

                    },
                },
            });
        })()
    </script>
@endpush
