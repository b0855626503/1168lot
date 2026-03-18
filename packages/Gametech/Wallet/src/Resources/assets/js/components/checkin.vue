<template>
    <button class="btn btn-outline-primary" @click="Checkin">Check In</button>
</template>
<script>
export default {

    methods: {

        Checkin: function () {
            this.$bvModal.msgBoxConfirm('ต้องการ ยืนยันการทำรายการนี้ หรือไม่', {
                title: 'ยืนยันการเช็คชื่อ',
                size: 'sm',
                buttonSize: 'sm',
                okVariant: 'danger',
                okTitle: 'YES',
                cancelTitle: 'NO',
                footerClass: 'p-2',
                hideHeaderClose: false,
                centered: true
            })
                .then(value => {
                    if(!value)return;
                    this.$http.post(`${this.$root.baseUrl}/member/checkin`)
                        .then(response => {

                            if (response.data.success) {
                                Swal.fire(
                                    'ดำเนินการสำเร็จ',
                                    response.data.message,
                                    'success'
                                );
                                this.$root.$refs.checkinlog.loadGameId();
                            } else {
                                Swal.fire(
                                    'พบข้อผิดพลาด',
                                    response.data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(response => {

                            $('.modal').modal('hide');
                            Swal.fire(
                                'พบข้อผิดพลาด',
                                response.data.message,
                                'error'
                            );
                        });
                })
                .catch(err => {
                    // An error occurred
                })
        }
    }
}


</script>
