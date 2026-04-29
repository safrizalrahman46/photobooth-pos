<?php

namespace App\Http\Controllers\Web;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\QueueTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class QueueBoardController extends Controller
{
    public function index(Request $request): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        $selectedBranch = null;

        if ($request->filled('branch_id')) {
            $selectedBranch = $branches->firstWhere('id', $request->integer('branch_id'));
        }

        $selectedBranch ??= $branches->first();

        $today = now()->toDateString();

        $tickets = QueueTicket::query()
            ->when($selectedBranch, fn ($query) => $query->where('branch_id', $selectedBranch->id))
            ->whereDate('queue_date', $today)
            ->orderByDesc('priority')
            ->orderBy('queue_number')
            ->get();

        $activeTicket = $tickets->first(fn (QueueTicket $ticket) => in_array($ticket->status?->value ?? $ticket->status, [
            QueueStatus::InSession->value,
            QueueStatus::Called->value,
            QueueStatus::CheckedIn->value,
        ], true));

        $nextTicket = $tickets->first(fn (QueueTicket $ticket) => ($ticket->status?->value ?? $ticket->status) === QueueStatus::Waiting->value);

        $waitingTickets = $tickets
            ->filter(fn (QueueTicket $ticket) => ($ticket->status?->value ?? $ticket->status) === QueueStatus::Waiting->value)
            ->values();

        return view('web.queue-board', [
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'queueDate' => $today,
            'activeTicket' => $activeTicket,
            'nextTicket' => $nextTicket,
            'waitingTickets' => $waitingTickets,
        ]);
    }
}
