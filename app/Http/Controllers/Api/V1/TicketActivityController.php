<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketActivityResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TicketActivityController extends Controller
{
    /**
     * List the activity log of a ticket. Only available to support staff.
     */
    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        Gate::authorize('view', $ticket);
        abort_unless($request->user()->isStaff(), 403);

        return TicketActivityResource::collection($ticket->activities()->with('user')->latest()->latest('id')->get());
    }
}
