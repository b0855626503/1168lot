@push('scripts')
    <script type="text/javascript">
        function addCredit(id, mcode, ucode, balance, bonus, date) {
            window.app.addCredit(id, mcode, ucode, balance, bonus, date);
        }
    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',

            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                addCredit(id, mcode, ucode, balance, bonus, date) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ทำรายการนี้หรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ route('admin.'.$menu->currentRoute.'.store') }}", {
                                    id: id,
                                    member_code: mcode,
                                    upline_code: ucode,
                                    balance_total: balance,
                                    bonus: bonus,
                                    date_cashback: date
                                })
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
                                        this.$refs.tbdata.refresh();

                                    })
                                    .catch(errors => console.log(errors));
                            }
                        })
                        .catch(errors => console.log(errors));
                }
            }
        });

    </script>
@endpush

