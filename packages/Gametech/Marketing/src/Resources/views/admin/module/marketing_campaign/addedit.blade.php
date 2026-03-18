<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">


            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="ชื่อแคมเปญ / กิจกรรม:"
                            label-for="name"
                            description="ระบุชื่อแคมเปญ / กิจกรรม">
                        <b-form-input
                                id="name"
                                v-model="formaddedit.name"
                                type="text"
                                size="sm"
                                placeholder="ชื่อแคมเปญ / กิจกรรม"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>



            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="Username ของทีมงาน:"
                            label-for="admin_username"
                            description="ระบุ ID ของทีมงานที่จะให้ดูได้ โดย ทีมงาน ต้อง มีสิทธิ์ ที่ชื่อว่า marketing ด้วยนะค">
                        <b-form-input
                                id="admin_username"
                                v-model="formaddedit.admin_username"
                                type="text"
                                size="sm"
                                placeholder="ไอดี ทีมงาน 1 คน"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

                <b-col>
                    <b-form-group
                            id="input-group-team_id"
                            label="ชื่อทีมที่เกี่ยวข้อง / ดูแล:"
                            label-for="team_id"
                            description="ระบุทีม ที่เกี่ยวข้อง / รับผิดชอบ ไม่บังคับ">
                        <b-form-select
                                id="team_id"
                                v-model="formaddedit.team_id"
                                :options="teams"
                                :disabled="formmethod === 'edit'"
                                size="sm"
                        ></b-form-select>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-form-group
                        id="input-group-description"
                        label="รายละเอียดเพิ่มเติม:"
                        label-for="description"
                        description="เอาไว้เป็นข้อมูล เฉยๆ ใส่ไม่ใส่ก็แล้วแต่">

                    <summernote id="description" v-model="formaddedit.description" ref="editor"></summernote>

                </b-form-group>
            </b-form-row>

            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs4.min.css"
          integrity="sha512-rDHV59PgRefDUbMm2lSjvf0ZhXZy3wgROFyao0JxZPGho3oOuWejq/ELx0FOZJpgaE5QovVtRN65Y3rrb7JhdQ=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs4.min.js"
            integrity="sha512-/DlF8zrT3XyUWEK7bmU1v7Q0kMXctQfqNwyzCNBB/mdUFxz87bq3X4TqadyuQBJW39g29t1tLNbHYLpXLs1zVA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/lang/summernote-en-US.min.js"
            integrity="sha512-IAPKzC0kpHVJzPKcRDuv3dzcFbU85cgFqbwAf+w5TM45/UOYiek4dZT2Xrz3Lxpn/VaBWikCWesWV/hub2uBQA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('vendor/summernote/plugin/attribute/summernote-image-attributes.js') }}"></script>
    <script>
        function ViewModal(id) {
            window.app.ViewModal(id);
        }
    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    trigger: 0,
                    formmethod: 'edit',
                    fileupload: '',
                    formaddedit: {
                        name: '',
                        admin_username : '',
                        team_id: '',
                        description: '',
                    },
                    teams: [{value: null, text: '== ระบุทีมที่ดูแล /เกี่ยวข้อง =='}],

                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                this.loadTeam();
            },
            methods: {
                ViewModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        admin_username : '',
                        team_id: '',
                        description: '',
                    }
                    this.formmethod = 'edit';
                    this.fileupload = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();

                    })
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        admin_username : '',
                        team_id: '',
                        description: '',
                    }
                    this.formmethod = 'edit';
                    this.fileupload = '';
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
                        name: '',
                        admin_username : '',
                        team_id: null,
                        description: '',
                    }
                    this.formmethod = 'add';
                    this.fileupload = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                async loadTeam() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadTeam') }}");
                    this.teams = response.data.teams;
                },
                async loadData() {

                    try {
                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});

                        const response = await responses;

                        this.formaddedit = {
                            name: response.data.data.name,
                            admin_username: response.data.data.admin_username,
                            team_id: response.data.data.team_id,
                            description: response.data.data.description,

                        };


                    } catch (error) {
                        console.log(error)
                    }
                },

                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;
                    }

                    let formData = new FormData();
                    const json = JSON.stringify({
                        name: this.formaddedit.name,
                        team_id: this.formaddedit.team_id,
                        admin_username: this.formaddedit.admin_username,
                        description: this.formaddedit.description
                    });

                    formData.append('data', json);


                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
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

