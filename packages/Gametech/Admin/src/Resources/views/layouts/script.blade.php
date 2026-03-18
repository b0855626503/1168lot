<script type="text/javascript">

    function editdata(id, status, method) {
        window.app.editdata(id, status, method);
    }

    function editModal(id) {
        window.app.editModal(id);
    }

    function addModal() {
        window.app.addModal();
    }

    function delModal(id) {
        window.app.delModal(id);
    }


    (() => {
        $(document).on('click', '[data-widget="pushmenu"]', event => {
            window.LaravelDataTables["dataTableBuilder"].columns.adjust().draw(false);
        })

        Vue.mixin({
            data() {
                return {
                    code: null,
                    method: null,
                    formmethod: 'add'
                };
            },
            methods: {
                delModal(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ลบข้อมูลหรือไม่.', {
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
                                this.$http.post("{{ url($menu->currentRoute.'/delete') }}", {
                                    id: code
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
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })
                },
                editdata(code, status, method) {

                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ปรับข้อมูลหรือไม่.', {
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
                                this.$http.post("{{ url($menu->currentRoute.'/edit') }}", {
                                    id: code,
                                    status: status,
                                    method: method
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
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })

                },
                addEditSubmit(event) {
                    event.preventDefault();
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
                            window.LaravelDataTables["dataTableBuilder"].draw(false);
                        })
                        .catch(exception => {
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });

                }
            },
        });
    })()


</script>

