<?php

namespace App\Http\Controllers\Web;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\QueueTicket;
use App\Models\WalkInRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QueueBoardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->has('reset')) {
            $request->session()->forget('queue_track_code');
            return redirect()->route('queue.board');
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'code' => ['required', 'string', 'max:40'],
            ]);

            $code = trim($request->code);

            $transaction = \App\Models\Transaction::where('transaction_code', $code)
                ->with(['queueTicket.branch', 'items', 'payments'])
                ->first();

            if (!$transaction) {
                return back()->withErrors([
                    'code' => 'Nomor transaksi tidak ditemukan.',
                ])->withInput();
            }

            $ticket = $transaction->queueTicket;

            if (!$ticket) {
                return back()->withErrors([
                    'code' => 'Antrean untuk transaksi ini belum aktif atau sudah dibatalkan.',
                ])->withInput();
            }

            $request->session()->put('queue_track_code', $code);

            return redirect()->route('queue.board');
        }

        $code = $request->session()->get('queue_track_code');
        $queueData = null;

        if ($code) {
            $queueData = $this->resolveQueueData($code);

            if (!$queueData) {
                $request->session()->forget('queue_track_code');
            }
        }

        return view('web.queue-board', [
            'queueData' => $queueData,
        ]);
    }

    private function resolveQueueData(string $code): ?array
    {
        $transaction = \App\Models\Transaction::where('transaction_code', $code)
            ->with(['queueTicket.branch', 'items.transaction', 'payments'])
            ->first();

        if (!$transaction || !$transaction->queueTicket) {
            return null;
        }

        $ticket = $transaction->queueTicket;
        $today = now(config('app.queue_timezone', 'Asia/Jakarta'))->toDateString();

        $position = QueueTicket::where('branch_id', $ticket->branch_id)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueStatus::Waiting->value,
                QueueStatus::Called->value,
                QueueStatus::CheckedIn->value,
                QueueStatus::InSession->value,
            ])
            ->where('queue_number', '<=', $ticket->queue_number)
            ->count();

        $totalActive = QueueTicket::where('branch_id', $ticket->branch_id)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueStatus::Waiting->value,
                QueueStatus::Called->value,
                QueueStatus::CheckedIn->value,
                QueueStatus::InSession->value,
            ])
            ->count();

        $averageMinutes = (int) config('app.queue_average_duration', 20);
        $estimatedMinutes = max(0, ($position - 1) * $averageMinutes);

        $statusLabels = [
            QueueStatus::Waiting->value => 'Menunggu',
            QueueStatus::Called->value => 'Dipanggil',
            QueueStatus::CheckedIn->value => 'Hadir',
            QueueStatus::InSession->value => 'Sedang Foto',
            QueueStatus::Finished->value => 'Selesai',
            QueueStatus::Skipped->value => 'Dilewati',
            QueueStatus::Cancelled->value => 'Batal',
        ];

        $rawStatus = $ticket->status?->value ?? $ticket->status;

        $sortedItems = $transaction->items
            ->sortBy(fn ($item) => in_array($item->item_type, ['package', 'booking']) ? 0 : 1)
            ->values();

        return [
            'queue_number' => $ticket->queue_number,
            'queue_code' => $ticket->queue_code,
            'transaction_code' => $transaction->transaction_code,
            'status' => $rawStatus,
            'status_label' => $statusLabels[$rawStatus] ?? '-',
            'position' => $position,
            'total_active' => $totalActive,
            'estimated_minutes' => $estimatedMinutes,
            'customer_name' => $ticket->customer_name,
            'branch_name' => $ticket->branch?->name ?? '-',
            'created_at' => $ticket->created_at?->format('d M Y, H:i'),
            'items' => $sortedItems->map(fn ($item) => [
                'type' => $item->item_type,
                'name' => $item->item_name,
                'qty' => $item->qty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->toArray(),
            'total_amount' => (float) ($transaction->total_amount ?? 0),
            'paid_amount' => (float) ($transaction->paid_amount ?? 0),
            'payment_status' => $transaction->status?->value ?? $transaction->status ?? '-',
            'payment_method' => $transaction->payments->first()?->method?->value ?? '-',
        ];
    }
}
