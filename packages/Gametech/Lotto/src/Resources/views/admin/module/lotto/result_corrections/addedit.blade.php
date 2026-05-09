<div class="modal fade" id="resultCorrectionDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="resultCorrectionDetailTitle">รายละเอียดการแก้ไขผลหวย</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="small text-muted">หักแล้ว</div>
                        <div class="h5 mb-0" id="rcSummaryDeducted">0</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">คงค้าง</div>
                        <div class="h5 mb-0 text-danger" id="rcSummaryRemaining">0</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">สมบูรณ์</div>
                        <div class="h5 mb-0 text-success" id="rcSummaryCompleted">0</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered w-100 mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>จำนวนโพย</th>
                                <th>ยอดตั้งต้น</th>
                                <th>ยอดหักที่ต้องทำ</th>
                                <th>ยอดที่หักแล้ว</th>
                                <th>ยอดคงค้าง</th>
                                <th>ยอดจ่ายเพิ่ม</th>
                                <th>คงเหลือในกระเป๋า</th>
                                <th>สถานะ</th>
                                <th class="text-center" style="min-width: 110px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="rcDetailBody">
                            <tr>
                                <td colspan="11" class="text-center text-muted">ไม่พบข้อมูล</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-danger btn-sm d-none" id="rcRetryAllRemainingBtn">
                        <i class="fa fa-refresh"></i> หักคืนยอดคงค้างทั้งหมด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
