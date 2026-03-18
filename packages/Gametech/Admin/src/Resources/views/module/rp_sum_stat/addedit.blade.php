<b-modal ref="addedit" id="addedit" centered scrollable size="md" :title="caption" :no-stacking="false"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form-row>
            <b-col>
                <b-table striped hover small outlined show-empty v-bind:items="myTable" :fields="fields"
                         :busy="isBusy" ref="tbdata" v-if="showtable">
                    <template #table-busy>
                        <div class="text-center text-danger my-2">
                            <b-spinner class="align-middle"></b-spinner>
                            <strong>Loading...</strong>
                        </div>
                    </template>

                </b-table>
            </b-col>
        </b-form-row>
    </b-container>

</b-modal>
@push('scripts')
    <script type="text/javascript">
        function ShowModel(id, method) {
            window.app.ShowModel(id, method);
        }
    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    showtable: false,
                    fields: [],
                    items: [],
                    isBusy: false,
                    caption: ''
                };
            },
            created() {
                // this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                ShowModel(id, method) {
                    this.showtable = false;
                    this.$nextTick(() => {
                        this.showtable = true;
                        this.code = id;
                        this.method = method;
                        this.$refs.addedit.show();

                    })
                },
                async myTable() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {
                        id: this.code,
                        method: this.method
                    });
                    this.caption = response.data.caption;
                    this.fields = [
                        {key: 'no', label: '#', class: 'text-center'},
                        {key: 'firstname', label: 'ชื่อ', class: 'text-left'},
                        {key: 'lastname', label: 'นามสกุล', class: 'text-left'},
                        {key: 'user_name', label: 'User ID', class: 'text-left'},
                        {key: 'tel', label: 'เบอร์โทร', class: 'text-center'},

                    ];


                    return response.data.list;

                },
            }
        });

    </script>
@endpush

