<?php

namespace Gametech\Lotto\Transformers;

use Gametech\Lotto\Contracts\LottoDraw;
use League\Fractal\TransformerAbstract;

class LottoDrawTransformer extends TransformerAbstract
{
    public function transform(LottoDraw $model): array
    {
        $statusBadge = $this->getStatusBadge((string) $model->status, (int) $model->id);
        $top3 = '-';
        $top2 = '-';
        $bottom2 = '-';

        if (is_array($model->result_number) && ! empty($model->result_number)) {
            $top3 = (string) ($model->result_number['top_3'] ?? '-');
            $top2 = (string) ($model->result_number['top_2'] ?? '-');
            $bottom2 = (string) ($model->result_number['bottom_2'] ?? ($model->result_number['last_2_digits'] ?? '-'));
        }
        
        return [
            'id'             => (int) $model->id,
            'market_name'    => $this->renderMarketName($model),
            'draw_date'      => $model->draw_date ? $model->draw_date->format('d/m/Y') : '-',
            'open_at'        => $model->open_at ? $model->open_at->format('d/m/Y H:i') : '-',
            'close_at'       => $model->close_at ? $model->close_at->format('d/m/Y H:i') : '-',
            'status'         => $statusBadge,
            'blocked_numbers_count' => $this->renderCountLink('showDrawBlockedNumbers', (int) $model->id, (int) ($model->blocked_numbers_count ?? 0)),
            'tickets_count' => $this->renderCountLink('showDrawTicketList', (int) $model->id, (int) ($model->tickets_count ?? 0)),
            'top_3'          => $top3,
            'top_2'          => $top2,
            'bottom_2'       => $bottom2,
            'action'         => view('admin::module.lotto.draws.datatables_actions', [
                'id' => $model->id,
                'status' => (string) $model->status,
            ])->render(),
        ];
    }

    private function getStatusBadge(string $status, int $id): string
    {
        $canOpen = bouncer()->hasPermission('lotto_draws.open');
        $canClose = bouncer()->hasPermission('lotto_draws.close');

        if ($status === 'open' && $canClose) {
            return '<button type="button" class="draw-status-toggle-btn draw-status-toggle-open" onclick="toggleDrawStatus(' . $id . ', \'close\')">เปิดรับ</button>';
        }

        if ($status === 'closed' && $canOpen) {
            return '<button type="button" class="draw-status-toggle-btn draw-status-toggle-closed" onclick="toggleDrawStatus(' . $id . ', \'open\')">ปิดรับ</button>';
        }

        $labels = [
            'draft'    => '<span class="draw-status-static draw-status-static-draft"><i class="fas fa-edit mr-1"></i>ร่าง</span>',
            'open'     => '<span class="draw-status-static draw-status-static-open"><i class="fas fa-play-circle mr-1"></i>เปิดรับ</span>',
            'closed'   => '<span class="draw-status-static draw-status-static-closed"><i class="fas fa-stop-circle mr-1"></i>ปิดรับ</span>',
            'resulted' => '<span class="draw-status-static draw-status-static-resulted"><i class="fas fa-check-circle mr-1"></i>ประกาศผล</span>',
        ];

        return $labels[$status] ?? '<span class="draw-status-static draw-status-static-default">' . e($status) . '</span>';
    }

    private function renderCountLink(string $handlerName, int $drawId, int $count): string
    {
        return '<a href="javascript:void(0);" onclick="' . $handlerName . '(' . $drawId . ')" class="font-weight-bold">' . $count . '</a>';
    }

    private function renderMarketName(LottoDraw $model): string
    {
        $name = (string) ($model->market->name ?? '-');
        $logo = (string) ($model->market->logo ?? '');
        $icon = (string) ($model->market->icon ?? '');
        $src = trim($logo) !== '' ? $logo : $icon;

        if (trim($src) === '') {
            return e($name);
        }

        return '<span style="display:inline-flex;align-items:center;gap:8px;">'
            . '<img src="' . e($src) . '" alt="" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid #e5e7eb;">'
            . '<span>' . e($name) . '</span>'
            . '</span>';
    }
}
