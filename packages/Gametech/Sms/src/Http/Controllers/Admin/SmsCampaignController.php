<?php

namespace Gametech\Sms\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Sms\DataTables\SmsCampaignDataTable;
use Gametech\Sms\Jobs\SendSmsJob;
use Gametech\Sms\Repositories\SmsCampaignRepository;
use Gametech\Sms\Repositories\SmsImportBatchRepository;
use Gametech\Sms\Repositories\SmsRecipientRepository;
use Gametech\Sms\Services\Recipients\SmsRecipientBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsCampaignController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $importBatchRepository;

    protected $recipientRepository;

    protected $recipientBuilder;

    public function __construct(
        SmsCampaignRepository $repository,
        SmsImportBatchRepository $importBatchRepository,
        SmsRecipientRepository $recipientRepository,
        SmsRecipientBuilderService $recipientBuilder
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
        $this->importBatchRepository = $importBatchRepository;
        $this->recipientRepository = $recipientRepository;
        $this->recipientBuilder = $recipientBuilder;
    }

    public function index(SmsCampaignDataTable $dataTable)
    {
        return $dataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = (int) $request->input('id');

        $data = $this->repository->find($id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        // ✅ เพิ่ม stats แนบกลับไปด้วย (modal จะได้โชว์ตัวเลขได้เลย)
        $stats = $this->computeCampaignStats($data->id);

        $payload = is_object($data) ? $data->toArray() : (array) $data;

        $payload['recipients_total'] = $stats['recipients_total'];
        $payload['queued_total'] = $stats['queued_total'];
        $payload['delivered_total'] = $stats['delivered_total'];
        $payload['failed_total'] = $stats['failed_total'];
        $payload['last_import_batch_id'] = $stats['last_import_batch_id'];

        return $this->sendResponse($payload, 'ดำเนินการเสร็จสิ้น');
    }

    /**
     * ✅ route: admin.sms_campaign.stats
     * request: id (campaign id)
     */
    public function stats(Request $request)
    {
        $id = (int) $request->input('id');

        $campaign = $this->repository->find($id);

        if (! $campaign) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $stats = $this->computeCampaignStats($campaign->id);

        return $this->sendResponse($stats, 'ดำเนินการเสร็จสิ้น');
    }

    /**
     * ✅ ปรับ create:
     * - สร้าง sms_campaign ก่อนเพื่อได้ campaign_id
     * - ถ้ามี import_batch_id (อัปโหลดไฟล์มาก่อนตอน Add) → ผูก batch นั้นกับ campaign_id
     * - ส่ง campaign_id กลับไปให้ frontend (modal จะได้สลับเป็น edit)
     *
     * request:
     * - data: { ...campaign_fields }
     * - import_batch_id (optional): id ของ sms_import_batches ที่อัปโหลดไว้ก่อนหน้า
     */
    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = (array) $request->input('data', []);
        $importBatchId = $request->input('import_batch_id');

        // defaults
        $data['provider'] = $data['provider'] ?? 'vonage';
        $data['status'] = $data['status'] ?? 'draft';
        $data['audience_mode'] = $data['audience_mode'] ?? 'member_all';
        $data['respect_opt_out'] = array_key_exists('respect_opt_out', $data) ? (bool) $data['respect_opt_out'] : true;
        $data['require_consent'] = array_key_exists('require_consent', $data) ? (bool) $data['require_consent'] : false;

        $data['user_create'] = $user;
        $data['user_update'] = $user;

        try {
            $campaign = DB::transaction(function () use ($data, $importBatchId, $user) {

                // 1) สร้าง campaign ก่อน
                $campaign = $this->repository->create($data);

                // 2) ถ้ามี batch ที่อัปโหลดมาก่อน → ผูกเข้ากับ campaign id
                if (! empty($importBatchId)) {
                    $batch = $this->importBatchRepository->find((int) $importBatchId);

                    if ($batch) {
                        // สำคัญ: อย่าเดา field อื่น เพิ่มแบบมั่ว ๆ — ผูก campaign_id อย่างเดียวก่อน (นิ่งสุด)
                        $this->importBatchRepository->update([
                            'campaign_id'  => $campaign->id,
                            'user_update'  => $user,
                        ], (int) $importBatchId);
                    } else {
                        // ไม่พัง flow: แค่แจ้งเตือนใน response meta ก็พอ
                        // (ถ้าคุณอยาก strict ให้ return error ก็เปลี่ยนได้)
                    }
                }

                return $campaign;
            });
        } catch (\Throwable $e) {
            return $this->sendError('เกิดข้อผิดพลาด: '.$e->getMessage(), 200);
        }

        // ✅ ส่ง id กลับไปด้วย เพื่อให้ modal เปลี่ยนเป็น edit แล้วกด Build/Dispatch ต่อได้ทันที
        return $this->sendResponse([
            'campaign_id' => (int) $campaign->id,
        ], 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $id = $request->input('id');
        $data = (array) $request->input('data', []);

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    /**
     * Toggle/edit single field pattern
     * request: id, status, method
     */
    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        if (! $method) {
            return $this->sendError('method ไม่ถูกต้อง', 200);
        }

        $data = [];
        $data[$method] = $status;

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id') ?: $request->route('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    /**
     * Build recipients into sms_recipients for a campaign
     * request:
     * - id (campaign id)
     * - mode: member_all | upload_only | mixed
     * - import_batch_id (required if upload_only/mixed)
     */
    public function buildRecipients(Request $request)
    {
        $id = (int) $request->input('id');
        $mode = (string) $request->input('mode', 'member_all');
        $importBatchId = $request->input('import_batch_id');

        $campaign = $this->repository->find($id);

        if (! $campaign) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $result = [];

        try {
            DB::beginTransaction();

            if ($mode === 'member_all' || $mode === 'mixed') {
                $result['members'] = $this->recipientBuilder->buildFromMembers($campaign);
            }

            if ($mode === 'upload_only' || $mode === 'mixed') {
                if (! $importBatchId) {
                    DB::rollBack();

                    return $this->sendError('กรุณาเลือก import_batch_id', 200);
                }

                $batch = $this->importBatchRepository->find((int) $importBatchId);

                if (! $batch) {
                    DB::rollBack();

                    return $this->sendError('ไม่พบ import batch ดังกล่าว', 200);
                }

                $phones = (array) ($batch->meta['phones'] ?? []);

                $result['upload'] = $this->recipientBuilder->buildFromImportBatch(
                    $campaign,
                    $batch,
                    $phones,
                    (string) ($batch->country_code ?: '66')
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->sendError('เกิดข้อผิดพลาด: '.$e->getMessage(), 200);
        }

        return $this->sendResponse($result, 'ดำเนินการเสร็จสิ้น');
    }

    /**
     * Dispatch queued recipients to SendSmsJob
     * request:
     * - id (campaign id)
     * - limit (default 1000, max 5000)
     */
    public function dispatchQueued(Request $request)
    {
        $id = (int) $request->input('id');
        $limit = (int) $request->input('limit', 1000);

        if ($limit < 1) $limit = 1;
        if ($limit > 5000) $limit = 5000;

        $campaign = $this->repository->find($id);

        if (! $campaign) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $recipientIds = $this->recipientRepository
            ->query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($recipientIds as $rid) {
            SendSmsJob::dispatch((int) $rid)->onQueue('sms');
        }

        if (($campaign->status ?? 'draft') === 'draft') {
            $this->repository->update([
                'status' => 'running',
                'started_at' => $campaign->started_at ?: now(),
            ], $campaign->id);
        }

        return $this->sendResponse([
            'dispatched' => $recipientIds->count(),
        ], 'ดำเนินการเสร็จสิ้น');
    }

    /**
     * รวม logic คำนวณสถิติไว้ที่เดียว เพื่อไม่ให้ซ้ำระหว่าง loadData/stats
     */
    private function computeCampaignStats(int $campaignId): array
    {
        $base = $this->recipientRepository->query()->where('campaign_id', $campaignId);

        $recipientsTotal = (clone $base)->count();
        $queuedTotal = (clone $base)->where('status', 'queued')->count();
        $deliveredTotal = (clone $base)->whereIn('status', ['delivered'])->count();
        $failedTotal = (clone $base)->whereIn('status', ['failed'])->count();

        // ✅ หา last_import_batch_id แบบ “ถูกจังหวะ”:
        // - ถ้า build recipients แล้ว → sms_recipients จะมี import_batch_id (อิงผลจริง)
        // - ถ้ายังไม่ build แต่มีการ upload/parse แล้วผูก campaign_id ใน sms_import_batches → ต้องยัง “มองเห็น batch”
        //   เพื่อให้ปุ่ม Build จากไฟล์เปิดได้
        $lastImportBatchId = (clone $base)
            ->whereNotNull('import_batch_id')
            ->orderByDesc('id')
            ->value('import_batch_id');

        if (! $lastImportBatchId) {
            // fallback: ดูจาก import_batches ล่าสุดของ campaign (ยังไม่ build ก็เจอ)
            try {
                $lastImportBatchId = $this->importBatchRepository
                    ->query()
                    ->where('campaign_id', $campaignId)
                    ->orderByDesc('id')
                    ->value('id');
            } catch (\Throwable $e) {
                // เงียบไว้: stats ไม่ควรทำให้หน้าแตก
            }
        }

        return [
            'recipients_total' => (int) $recipientsTotal,
            'queued_total' => (int) $queuedTotal,
            'delivered_total' => (int) $deliveredTotal,
            'failed_total' => (int) $failedTotal,
            'last_import_batch_id' => $lastImportBatchId ? (int) $lastImportBatchId : null,
        ];
    }
}
