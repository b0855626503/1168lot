<?php

namespace Gametech\Marketing\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use Gametech\Marketing\DataTables\MarketingTeamDataTable;
use Gametech\Marketing\Repositories\MarketingTeamRepository;
use Gametech\Marketing\Repositories\RegistrationLinkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketingTeamController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $registrationLinkRepository;

    public function __construct(
        MarketingTeamRepository $repository,
        RegistrationLinkRepository $registrationLinkRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->registrationLinkRepository = $registrationLinkRepository;

    }

    public function index(MarketingTeamDataTable $marketingTeamDataTable)
    {
        return $marketingTeamDataTable->render($this->_config['view']);
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

        $data = json_decode($request['data'], true);

        $data['enable'] = true;

        $team = $this->repository->create($data);
        if ($team) {
            $link = $this->addRegisterLink($team);

        }

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function addRegisterLink($team)
    {
        return $this->registrationLinkRepository->create([
            'code' => Str::random(20),
            'team_id' => $team->id,
            'campaign_id' => null,
        ]);
    }

    public function edit(Request $request)
    {

        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {

        $data = json_decode($request['data'], true);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

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

    public function loadBank()
    {
        $banks = [
            'value' => '',
            'text' => 'ธนาคาร',
        ];

        $responses = collect(app('Gametech\Payment\Repositories\BankRepository')->where('show_regis', 'Y')->where('enable', 'Y')->get()->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object) $items;

            return [
                'value' => $item->code,
                'text' => $item->name_th,
            ];

        })->prepend($banks);

        $result['banks'] = $responses;

        return $this->sendResponseNew($result, 'complete');
    }
}
