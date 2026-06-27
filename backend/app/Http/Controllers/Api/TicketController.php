<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets (tenant-scoped).
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with(['requester', 'assignee', 'comments'])
            ->when($request->user()->isCustomer(), function ($query) use ($request) {
                return $query->where('requester_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'data' => $tickets,
            'meta' => [
                'total' => $tickets->count(),
            ],
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create(array_merge($request->validated(), [
            'requester_id' => $request->user()->id,
            'status' => 'open',
            'organization_id' => $request->user()->organization_id,
        ]));

        return response()->json($ticket->load(['requester', 'assignee']), 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicketAccess($request->user(), $ticket);

        return response()->json($ticket->load(['requester', 'assignee', 'comments.user']));
    }

    /**
     * Update the specified ticket.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicketAccess($request->user(), $ticket);

        // Only agents/admins can modify certain fields
        $validated = $request->validated();

        if ($request->user()->isCustomer()) {
            // Customers can only update description and tags
            $validated = array_intersect_key($validated, array_flip(['description', 'tags']));
        }

        $ticket->update($validated);

        return response()->json($ticket->load(['requester', 'assignee']));
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicketAccess($request->user(), $ticket);

        // Only agents/admins can delete tickets
        if ($request->user()->isCustomer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->delete();

        return response()->json(null, 204);
    }

    /**
     * Authorize ticket access based on user role and ownership.
     */
    private function authorizeTicketAccess($user, Ticket $ticket): void
    {
        // Multi-tenancy: Check if ticket belongs to user's organization
        if ($ticket->organization_id !== $user->organization_id) {
            abort(404, 'Ticket not found');
        }

        // Customers can only access their own tickets
        if ($user->isCustomer() && $ticket->requester_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Agents and admins can access all organization tickets
        if (!$user->isCustomer() && $ticket->organization_id !== $user->organization_id) {
            abort(404, 'Ticket not found');
        }
    }
}