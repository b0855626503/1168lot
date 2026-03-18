<?php

namespace Gametech\Reward\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Reward\DataTables\RewardListDataTable;
use Gametech\Reward\Repositories\RewardListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RewardListController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    public function __construct(
        RewardListRepository $repository,
        MemberRepository $memberRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberRepository = $memberRepository;
    }

    public function index(RewardListDataTable $rewardListDataTable)
    {
        return $rewardListDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->find($id);
        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request)
    {
        $admin = $this->user();

        // รองรับทั้งกรณีส่งแบบ JSON (data เป็น array) และ multipart (data เป็น string/array)
        $data  = (array) $request->input('data', []);

        // --- sanitize basics ---
        $data['name'] = trim((string) ($data['name'] ?? ''));

        if ($data['name'] === '') {
            return $this->sendError('กรุณากรอกชื่อรางวัล', 200);
        }

        // --- code fallback (กันหน้าเว็บไม่ส่ง/ส่งว่าง) ---
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $type = trim((string) ($data['reward_type'] ?? 'wallet_credit'));
            $type = Str::of($type)
                ->replace('wallet_', '')
                ->lower()
                ->replaceMatches('/[^a-z0-9_]/', '')
                ->toString();

            $ts   = (string) now('Asia/Bangkok')->format('YmdHis');
            $code = "RW_{$type}_{$ts}_" . Str::lower(Str::random(6));
        }
        $data['code'] = $code;

        // --- map booleans / status ---
        // front ส่ง status มาแล้ว แต่กันเคสส่ง enabled มาแทน
        if (! isset($data['status']) && isset($data['enabled'])) {
            $data['status'] = $this->toBool($data['enabled']) ? 'active' : 'inactive';
        }

        // --- normalize limit fields (ใหม่) ---
        $data = $this->normalizeLimitFields($data);

        // --- normalize reward amounts ---
        $data = $this->normalizeAmounts($data);

        // --- normalize stock fields ---
        $data = $this->normalizeStock($data);

        // --- normalize boolean flags (กันเคสส่งมาเป็น true/false หรือ 1/0 สลับ) ---
        if (array_key_exists('auto_claim', $data)) {
            $data['auto_claim'] = $this->toBool($data['auto_claim']) ? 1 : 0;
        }
        if (array_key_exists('require_staff_contact', $data)) {
            $data['require_staff_contact'] = $this->toBool($data['require_staff_contact']) ? 1 : 0;
        }
        if (array_key_exists('is_featured', $data)) {
            $data['is_featured'] = $this->toBool($data['is_featured']) ? 1 : 0;
        }
        if (array_key_exists('is_hidden', $data)) {
            $data['is_hidden'] = $this->toBool($data['is_hidden']) ? 1 : 0;
        }

        // --- business validation (กันยิง API ตรง) ---
        $v = $this->validateBusiness($data);
        if ($v !== true) {
            return $this->sendError($v, 200);
        }

        // --- image upload (multipart: image) ---
        $data = $this->handleImageUpload($request, $data);

        // --- audit ---
        $data['created_by'] = $admin->code ?? null;
        $data['updated_by'] = $admin->code ?? null;

        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request)
    {
        $admin = $this->user();
        $id    = $request->input('id');
        $data  = (array) $request->input('data', []);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        // --- sanitize basics ---
        if (array_key_exists('name', $data)) {
            $data['name'] = trim((string) $data['name']);
            if ($data['name'] === '') {
                return $this->sendError('กรุณากรอกชื่อรางวัล', 200);
            }
        }

        // --- code: ห้ามว่าง (แต่ไม่บังคับเปลี่ยน ถ้าไม่ได้ส่งมา) ---
        if (array_key_exists('code', $data)) {
            $data['code'] = trim((string) $data['code']);
            if ($data['code'] === '') {
                // ถ้า user ส่งค่าว่างมา ให้คงของเดิม ไม่ให้พัง
                unset($data['code']);
            }
        }

        // --- map booleans / status ---
        if (! isset($data['status']) && isset($data['enabled'])) {
            $data['status'] = $this->toBool($data['enabled']) ? 'active' : 'inactive';
        }

        // --- normalize limit fields (ใหม่) ---
        $data = $this->normalizeLimitFields($data);

        // --- normalize reward amounts ---
        $data = $this->normalizeAmounts($data);

        // --- normalize stock fields ---
        $data = $this->normalizeStock($data);

        // --- normalize boolean flags ---
        if (array_key_exists('auto_claim', $data)) {
            $data['auto_claim'] = $this->toBool($data['auto_claim']) ? 1 : 0;
        }
        if (array_key_exists('require_staff_contact', $data)) {
            $data['require_staff_contact'] = $this->toBool($data['require_staff_contact']) ? 1 : 0;
        }
        if (array_key_exists('is_featured', $data)) {
            $data['is_featured'] = $this->toBool($data['is_featured']) ? 1 : 0;
        }
        if (array_key_exists('is_hidden', $data)) {
            $data['is_hidden'] = $this->toBool($data['is_hidden']) ? 1 : 0;
        }

        // --- business validation ---
        $merged = array_merge($chk->toArray(), $data); // อิงค่าปัจจุบัน + ของใหม่
        $v = $this->validateBusiness($merged);
        if ($v !== true) {
            return $this->sendError($v, 200);
        }

        // --- image upload (multipart: image) ---
        $data = $this->handleImageUpload($request, $data, $chk->image ?? null);

        // --- audit ---
        $data['updated_by'] = $admin->code ?? null;

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    /**
     * Toggle / quick edit (ใช้กับปุ่มใน datatable)
     * เดิมใช้ method/status ตรง ๆ ซึ่งเสี่ยงมาก → ทำ whitelist
     */
    public function edit(Request $request)
    {
        $admin  = $this->user();
        $id     = $request->input('id');
        $value  = $request->input('status'); // ค่าที่จะ set
        $method = $request->input('method'); // ชื่อฟิลด์ที่จะแก้

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        // whitelist fields ที่อนุญาตให้ toggle/แก้เร็ว
        $allowed = [
            'status',
            'is_hidden',
            'is_featured',
            'priority',
        ];

        if (! in_array($method, $allowed, true)) {
            return $this->sendError('ไม่อนุญาตให้แก้ไขฟิลด์นี้', 200);
        }

        $data = [];

        if ($method === 'status') {
            // รองรับ active/inactive หรือ 1/0
            $v = (string) $value;
            $v = trim(strtolower($v));
            if (in_array($v, ['1', 'y', 'yes', 'true', 'active'], true)) {
                $data['status'] = 'active';
            } else {
                $data['status'] = 'inactive';
            }
        } elseif (in_array($method, ['is_hidden', 'is_featured'], true)) {
            $data[$method] = $this->toBool($value) ? 1 : 0;
        } elseif ($method === 'priority') {
            $data['priority'] = (int) $value;
        }

        $data['updated_by'] = $admin->code ?? null;

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    /* ===============================
     | Internal helpers
     =============================== */

    private function toBool($v): bool
    {
        if ($v === true || $v === 1 || $v === '1' || $v === 'Y') return true;
        $s = (string) ($v ?? '');
        $s = trim(strtolower($s));
        return in_array($s, ['true', 'yes', 'y', 'on'], true);
    }

    private function validateBusiness(array $data)
    {
        $type = (string) ($data['reward_type'] ?? '');

        if ($type === 'wallet_credit') {
            $ca = $data['credit_amount'] ?? null;
            if ($ca === null || $ca === '' || (float) $ca <= 0) {
                return 'รางวัลเครดิตต้องมีจำนวนมากกว่า 0';
            }
        }

        if ($type === 'wallet_gem') {
            $ga = $data['gem_amount'] ?? null;
            if ($ga === null || $ga === '' || (int) $ga <= 0) {
                return 'รางวัลเพชรต้องมีจำนวนมากกว่า 0';
            }
        }

        if ($type === 'external') {
            $fm = (string) ($data['fulfillment_mode'] ?? '');
            if ($fm === 'auto') {
                return 'รางวัลภายนอกไม่สามารถใช้โหมด Auto ได้';
            }
        }

        return true;
    }

    private function normalizeAmounts(array $data): array
    {
        if (! isset($data['reward_type'])) {
            return $data;
        }

        $type = (string) $data['reward_type'];

        if ($type === 'wallet_credit') {
            // credit ใช้ได้, gem ต้อง null
            $data['gem_amount'] = null;

            // normalize
            if (array_key_exists('credit_amount', $data)) {
                $ca = $data['credit_amount'];
                $data['credit_amount'] = ($ca === '' || $ca === null) ? null : $ca;
            }
        } elseif ($type === 'wallet_gem') {
            // gem ใช้ได้, credit ต้อง null
            $data['credit_amount'] = null;

            if (array_key_exists('gem_amount', $data)) {
                $ga = $data['gem_amount'];
                $data['gem_amount'] = ($ga === '' || $ga === null) ? null : (int) $ga;
            }
        } else {
            // external / custom → ไม่ใช้จำนวน
            $data['credit_amount'] = null;
            $data['gem_amount']    = null;
        }

        return $data;
    }

    private function normalizeStock(array $data): array
    {
        if (array_key_exists('stock_unlimited', $data)) {
            $unlimited = $this->toBool($data['stock_unlimited']);
            $data['stock_unlimited'] = $unlimited ? 1 : 0;

            // ถ้าไม่จำกัด ให้ stock เป็น null
            if ($unlimited) {
                $data['stock'] = null;
            } else {
                if (array_key_exists('stock', $data)) {
                    $s = $data['stock'];
                    $data['stock'] = ($s === '' || $s === null) ? 0 : (int) $s;
                }
            }
        }

        if (array_key_exists('auto_disable_when_out_of_stock', $data)) {
            $data['auto_disable_when_out_of_stock'] = $this->toBool($data['auto_disable_when_out_of_stock']) ? 1 : 0;
        }

        return $data;
    }

    private function normalizeLimitFields(array $data): array
    {
        // ถ้าไม่ได้ส่ง limit_type มา ไม่ไปยุ่ง (เพื่อ backward compatible)
        if (! array_key_exists('limit_type', $data)) {
            return $data;
        }

        $limitType = trim((string) ($data['limit_type'] ?? 'unlimited'));
        if ($limitType === '') $limitType = 'unlimited';
        $data['limit_type'] = $limitType;

        if ($limitType === 'unlimited') {
            $data['limit_per_user']   = null;
            $data['limit_period']     = null;
            $data['limit_per_period'] = null;
            $data['strict_limit']     = 0;
            return $data;
        }

        if ($limitType === 'per_reward') {
            $data['limit_period']     = null;
            $data['limit_per_period'] = null;
            $data['strict_limit']     = 0;

            $l = $data['limit_per_user'] ?? 1;
            $l = (int) $l;
            if ($l < 1) $l = 1;
            $data['limit_per_user'] = $l;

            return $data;
        }

        if ($limitType === 'per_period') {
            $data['limit_per_user'] = null;

            $period = trim((string) ($data['limit_period'] ?? 'day'));
            if (! in_array($period, ['day', 'week', 'month', 'event'], true)) {
                $period = 'day';
            }
            $data['limit_period'] = $period;

            $lp = $data['limit_per_period'] ?? 1;
            $lp = (int) $lp;
            if ($lp < 1) $lp = 1;
            $data['limit_per_period'] = $lp;

            $data['strict_limit'] = $this->toBool($data['strict_limit'] ?? false) ? 1 : 0;

            return $data;
        }

        // unknown type → ปลอดภัยไว้ก่อน
        $data['limit_type']       = 'unlimited';
        $data['limit_per_user']   = null;
        $data['limit_period']     = null;
        $data['limit_per_period'] = null;
        $data['strict_limit']     = 0;

        return $data;
    }

    /**
     * รองรับ upload รูปจาก <input type="file" name="image">
     * - เก็บที่ storage/app/public/rewards
     * - เซ็ต field image เป็น /storage/...
     * - ถ้า update และมีรูปใหม่: ลบรูปเก่า (best effort)
     */
    private function handleImageUpload(Request $request, array $data, $oldImagePath = null): array
    {
        if (! $request->hasFile('image')) {
            return $data;
        }

        $file = $request->file('image');
        if (! $file || ! $file->isValid()) {
            return $data;
        }

        $path = $file->store('rewards', 'public');

        // ลบรูปเก่าแบบ best-effort (ไม่ให้ flow พัง)
        if ($oldImagePath) {
            try {
                $oldPath = (string) $oldImagePath;

                // ถ้าเก็บเป็น /storage/xxx ให้ตัด /storage/ ออกก่อนลบใน disk public
                if (str_starts_with($oldPath, '/storage/')) {
                    $oldPath = substr($oldPath, strlen('/storage/'));
                }

                if ($oldPath !== '') {
                    Storage::disk('public')->delete($oldPath);
                }
            } catch (\Throwable $e) {
                // เงียบไว้ตามเจตนา (อย่าให้ update ล้มเพราะลบไฟล์เก่าไม่สำเร็จ)
            }
        }

        $data['image'] = '/storage/' . $path;

        return $data;
    }
}
