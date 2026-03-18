<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-game_code"
                        label="เกม:"
                        label-for="game_code"
                        description="">

                        <b-form-select
                            id="game_code"
                            v-model="formaddedit.game_code"
                            :options="option.game_code"
                            size="sm"
                            first="true"
                            required
                            v-on:change="loadData($event, formaddedit.game_code , formaddedit.freecredit)"
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-freecredit"
                        label="ประเภท:"
                        label-for="freecredit"
                        description="">

                        <b-form-select
                            id="freecredit"
                            v-model="formaddedit.freecredit"
                            :options="option.freecredit"
                            size="sm"
                            required
                            v-on:change="loadData($event, formaddedit.game_code , formaddedit.freecredit)"
                        ></b-form-select>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-prefix"
                        label="Prefix:"
                        label-for="prefix"
                        description="ระบุ ตัวอักษร ภาษาอังกฤษผสมตัวเลข 5-6 ตัวอักษร">
                        <b-form-input
                            id="prefix"
                            v-model="formaddedit.prefix"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            :maxlength="max"
                            required
                            v-validate="{ required: true, min: 5 , max:6 }"
                            v-uppercase
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-batch_start"
                        label="จำนวนเริ่ม:"
                        label-for="batch_start"
                        description="">
                        <b-form-input
                            id="batch_start"
                            v-model="formaddedit.batch_start"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            required
                            readonly
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-batch_stop"
                        label="ถึงจำนวน:"
                        label-for="batch_stop"
                        description="">
                        <b-form-input
                            id="batch_stop"
                            v-model="formaddedit.batch_stop"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            required
                            readonly
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>
@push('scripts')
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    trigger: 0,
                    max: 6,
                    formmethod: 'edit',
                    formaddedit: {
                        game_code: '',
                        game_id: '',
                        prefix: '',
                        freecredit: 'N',
                        batch_start: 1,
                        batch_stop: 30000
                    },
                    option: {
                        freecredit: [{text: 'ปกติ', value: 'N'}, {text: 'ฟรีเครดิต', value: 'Y'}],
                        game_code: [],
                    },
                    imgpath: '/storage/game_img/'
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
                        name: '',
                        game_type: '',
                        user_demo: '',
                        user_demofree: '',
                        sort: 0,
                        link_ios: '',
                        link_android: '',
                        link_web: '',
                        batch_game: 'N',
                        auto_open: 'N',
                        filepic: ''
                    }
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
                    this.formaddedit = {
                        game_code: '',
                        game_id: '',
                        prefix: '',
                        freecredit: 'N',
                        batch_start: 1,
                        batch_stop: 30000
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.loadGame();
                        this.$refs.addedit.show();

                    })
                },
                async loadGame() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadgame') }}");
                    this.option.game_code = response.data.games;
                    this.formaddedit.game_code = response.data.games[0].value;
                    this.loadData(response.data.games[0].value, response.data.games[0].value, this.formaddedit.freecredit);
                },
                async loadData(newObjectState, game, free) {
                    // console.log(newObjectState + " --- " + game + '---' +free);
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {
                        game_code: game,
                        freecredit: free
                    });
                    if (response.data.success) {
                        this.formaddedit = {
                            game_code: response.data.data.game_code,
                            freecredit: response.data.data.freecredit,
                            prefix: response.data.data.prefix,
                            batch_start: response.data.data.batch_start,
                            batch_stop: response.data.data.batch_stop
                        };
                    } else {
                        this.formaddedit = {
                            game_code: game,
                            freecredit: free,
                            prefix: '',
                            batch_start: 1,
                            batch_stop: 30000
                        };
                    }


                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";

                    let formData = new FormData();
                    const json = JSON.stringify({
                        game_code: this.formaddedit.game_code,
                        prefix: this.formaddedit.prefix,
                        freecredit: this.formaddedit.freecredit,
                        batch_start: this.formaddedit.batch_start,
                        batch_stop: this.formaddedit.batch_stop
                    });

                    formData.append('data', json);
                    // formData.append('filepic', $('input[name="filepic[image_0]"]')[1].files[0]);


                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
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
                        .catch(errors => console.log(errors));

                }
            },
        });

    </script>
@endpush


