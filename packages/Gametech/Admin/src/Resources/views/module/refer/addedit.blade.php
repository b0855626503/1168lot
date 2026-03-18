<b-modal ref="addedit" id="addedit" centered size="sm" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        <b-form-group
            id="input-group-1"
            label="หัวข้อ:"
            label-for="name"
            description="ระบุหัวข้อ">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder="หัวข้อ"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>
@push('scripts')

    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,
                    formmethod: 'add',
                    formaddedit: {
                        name: ''
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
                    this.formaddedit.name = '';
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
                    this.formaddedit.name = response.data.data.name;
                },
            },
        });

    </script>
@endpush

