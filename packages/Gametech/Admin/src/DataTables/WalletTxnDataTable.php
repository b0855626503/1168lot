<?php

namespace Gametech\Admin\DataTables;

use Gametech\Admin\Transformers\WalletTxnTransformer;
use Gametech\Lotto\Models\WalletTransaction;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Services\DataTable;

class WalletTxnDataTable extends DataTable
{
    public function dataTable($query): DataTableAbstract
    {
        return (new EloquentDataTable($query))
            ->setTransformer(new WalletTxnTransformer);
    }

    public function query(WalletTransaction $model)
    {
        $user = request()->input('user_name');
        $direction = request()->input('direction');
        $startdate = request()->input('startDate');
        $enddate = request()->input('endDate');

        if (empty($startdate)) {
            $startdate = now()->subDays(6)->startOfDay()->format('Y-m-d H:i:s');
        }
        if (empty($enddate)) {
            $enddate = now()->endOfDay()->format('Y-m-d H:i:s');
        }

        return $model->newQuery()
            ->with('member')
            ->select('wallet_transactions.*')
            ->when($startdate, function ($query, $startdate) use ($enddate) {
                $query->whereBetween('wallet_transactions.created_at', [$startdate, $enddate]);
            })
            ->when($direction, function ($query, $direction) {
                $query->where('wallet_transactions.direction', $direction);
            })
            ->when($user, function ($query, $user) {
                $query->whereIn('wallet_transactions.member_id', function ($q) use ($user) {
                    $q->from('members')->select('members.code')->where('members.user_name', $user);
                });
            })
            ->orderBy('wallet_transactions.created_at', 'desc');
    }

    public function html(): Builder
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->ajaxWithForm('', '#frmsearch')
            ->parameters([
                'dom' => 'Bfrtip',
                'processing' => true,
                'serverSide' => true,
                'responsive' => false,
                'stateSave' => true,
                'scrollX' => true,
                'paging' => true,
                'searching' => false,
                'deferRender' => true,
                'retrieve' => true,
                'ordering' => true,
                'autoWidth' => false,
                'pageLength' => 50,
                'order' => [[0, 'desc']],
                'lengthMenu' => [
                    [50, 100, 200, 500, 1000],
                    ['50 rows', '100 rows', '200 rows', '500 rows', '1000 rows'],
                ],
                'buttons' => [
                    'pageLength',
                ],
                'columnDefs' => [
                    ['targets' => '_all', 'className' => 'text-center text-nowrap'],
                ],
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'name' => 'wallet_transactions.id', 'title' => '#', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'user_name', 'name' => 'member.user_name', 'title' => 'User ID', 'orderable' => false, 'searchable' => true, 'className' => 'text-center text-nowrap'],
            ['data' => 'direction', 'name' => 'wallet_transactions.direction', 'title' => 'Direction', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'amount', 'name' => 'wallet_transactions.amount', 'title' => 'Amount', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_before', 'name' => 'wallet_transactions.balance_before', 'title' => 'Balance Before', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'balance_after', 'name' => 'wallet_transactions.balance_after', 'title' => 'Balance After', 'orderable' => false, 'searchable' => false, 'className' => 'text-right text-nowrap'],
            ['data' => 'ref_type', 'name' => 'wallet_transactions.ref_type', 'title' => 'Ref Type', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'ref_code', 'name' => 'wallet_transactions.ref_code', 'title' => 'Ref Code', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'status', 'name' => 'wallet_transactions.status', 'title' => 'Status', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-nowrap'],
            ['data' => 'description', 'name' => 'wallet_transactions.description', 'title' => 'Description', 'orderable' => false, 'searchable' => false, 'className' => 'text-left text-nowrap'],
            ['data' => 'created_at', 'name' => 'wallet_transactions.created_at', 'title' => 'Date', 'orderable' => true, 'searchable' => false, 'className' => 'text-center text-nowrap'],
        ];
    }

    protected function filename(): string
    {
        return 'wallet_txn_'.time();
    }
}
