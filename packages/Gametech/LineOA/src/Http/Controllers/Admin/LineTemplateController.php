<?php

namespace Gametech\LineOA\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\LineOA\DataTables\LineTemplateDataTable;
use Gametech\LineOA\Repositories\LineTemplateRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LineTemplateController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    public function __construct(
        LineTemplateRepository $repository,
        MemberRepository $memberRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberRepository = $memberRepository;
    }

    public function index(LineTemplateDataTable $lineTemplateDataTable)
    {
        return $lineTemplateDataTable->render($this->_config['view']);
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

    /**
     * ✅ เพิ่ม: อัปโหลดรูปสำหรับเทมเพลต (ใช้ใน modal)
     * - ไม่กระทบ create/update เดิม
     * - คืน url ที่ public เข้าถึงได้ เพื่อเอาไปฝังใน JSON message
     *
     * Expect: multipart/form-data
     * field: image
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:10240'], // 10MB
        ]);

        $file = $request->file('image');

        // เก็บใน public disk
        $dir = 'line_oa/templates/'.now()->format('Y/m/d');
        $path = $file->store($dir, 'public');

        $relativeUrl = Storage::disk('public')->url($path); // /storage/...
        $base = rtrim((string) (config('line_oa.public_base_url') ?: config('app.url')), '/');
        $publicUrl = $base.$relativeUrl;

        return $this->sendResponse([
            'storage_disk' => 'public',
            'storage_path' => $path,
            'url' => $publicUrl,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ], 'อัปโหลดรูปสำเร็จ');
    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = $request->input('data', []);

        // ====== prefix key ด้วย category. ======
        $category = $data['category'] ?? null;
        if ($category && isset($data['key'])) {
            $category = trim($category);
            $rawKey   = trim((string) $data['key']);

            if ($category !== '' && $rawKey !== '') {
                $prefix = $category.'.';
                if (strpos($rawKey, $prefix) !== 0) {
                    $rawKey = $prefix.$rawKey;
                }
                $data['key'] = $rawKey;
            }
        }

        // ====== ตรวจ message ว่าเป็น JSON template หรือ text ปกติ ======
        $rawMessage = $data['message'] ?? '';

        $decoded = json_decode($rawMessage, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // ถือว่าเป็น JSON template
            $data['message']      = $rawMessage;   // เก็บดิบ ๆ ใน column message
            $data['message_type'] = 'json';
        } else {
            // เป็นข้อความธรรมดา
            $data['message']      = $rawMessage;
            $data['message_type'] = 'text';
        }

        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id   = $request->input('id');
        $data = $request->input('data', []);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        // ====== prefix key ด้วย category. ======
        $category = $data['category'] ?? $chk->category ?? null;
        if ($category && isset($data['key'])) {
            $category = trim($category);
            $rawKey   = trim((string) $data['key']);

            if ($category !== '' && $rawKey !== '') {
                $prefix = $category.'.';
                if (strpos($rawKey, $prefix) !== 0) {
                    $rawKey = $prefix.$rawKey;
                }
                $data['key'] = $rawKey;
            }
        }

        // ====== ตรวจ message ว่าเป็น JSON template หรือ text ปกติ ======
        $rawMessage = $data['message'] ?? '';

        $decoded = json_decode($rawMessage, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data['message']      = $rawMessage;
            $data['message_type'] = 'json';
        } else {
            $data['message']      = $rawMessage;
            $data['message_type'] = 'text';
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

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
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }
}
