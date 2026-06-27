<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of comments for a ticket.
     */
    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        // Authorize ticket access first
        $this->authorizeTicketAccess($request->user(), $ticket);

        // Only agents/admins can see internal comments
        $comments = $ticket->comments()
            ->when($request->user()->isCustomer(), function ($query) {
                return $query->where('is_internal', false);
            })
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'data' => $comments,
            'meta' => [
                'total' => $comments->count(),
            ],
        ]);
    }

    /**
     * Store a newly created comment.
     */
    public function store(StoreCommentRequest $request, Ticket $ticket): JsonResponse
    {
        // Authorize ticket access first
        $this->authorizeTicketAccess($request->user(), $ticket);

        // Only agents/admins can create internal comments
        if ($request->input('is_internal') && $request->user()->isCustomer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment = Comment::create(array_merge($request->validated(), [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
        ]));

        return response()->json($comment->load('user'), 201);
    }

    /**
     * Display the specified comment.
     */
    public function show(Request $request, Comment $comment): JsonResponse
    {
        $this->authorizeCommentAccess($request->user(), $comment);

        // Customers cannot see internal comments
        if ($request->user()->isCustomer() && $comment->is_internal) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($comment->load(['user', 'ticket']));
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorizeCommentAccess($request->user(), $comment);

        // Only comment author can update
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only agents/admins can modify internal flag
        if ($request->has('is_internal') && $request->user()->isCustomer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->update($request->only(['body', 'is_internal']));

        return response()->json($comment->load('user'));
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->authorizeCommentAccess($request->user(), $comment);

        // Only comment author or agents/admins can delete
        if ($comment->user_id !== $request->user()->id && $request->user()->isCustomer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }

    /**
     * Authorize ticket access.
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
    }

    /**
     * Authorize comment access.
     */
    private function authorizeCommentAccess($user, Comment $comment): void
    {
        // Multi-tenancy: Check if comment belongs to user's organization
        if ($comment->organization_id !== $user->organization_id) {
            abort(404, 'Comment not found');
        }

        // Customers can only access comments on their own tickets
        if ($user->isCustomer() && $comment->ticket->requester_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }
}