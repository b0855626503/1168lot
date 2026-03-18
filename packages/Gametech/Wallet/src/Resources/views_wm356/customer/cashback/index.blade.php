@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    <div class="p-1">
        <div class="headsecion">
            <img src="/images/icon/return.png"> แคชแบ็ก
        </div>
        <div class="ctpersonal trans">

            <h1 class="headtwocb">CASHBACK ที่ได้รับ</h1>
            <h5 class="cashbacknb">
                {{ $profile->cashback }} บาท
            </h5>
            <button type="button" class="btnLogin my-3" onclick="openPopup('CASHBACK','Cashback')">
                <span>รับ CASHBACK</span>
            </button>
            <hr class="cashbackhr">
            <div class="modalspanbox mt-3 text-left">

                ✅ การคืนยอดเสียจะ มอบหลัง 02.00 น. ของทกวัน<br>
            </div>
        </div>

    </div>
@endsection


@push('scripts')
    <script>
        function openPopup(id, msg) {
            Swal.fire({
                title: 'ยืนยันการโยก ' + msg + ' เข้ากระเป๋า ใช่หรือไม่',
                html: "กด ยืนยัน เพื่อนรับยอด",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post(`{{ route('customer.transfer.bonus.confirm') }}`, {
                        id: id
                    }).then(response => {
                        if (response.data.success) {
                            Swal.fire(
                                'ดำเนินการสำเร็จ',
                                response.data.message,
                                'success'
                            );
                            setTimeout(() => {
                                window.location.href = window.location;
                            }, 5000);
                        } else {
                            Swal.fire(
                                'พบข้อผิดพลาด',
                                response.data.message,
                                'error'
                            );
                        }

                    }).catch(err => [err]);
                }
            })
        }
    </script>
@endpush


