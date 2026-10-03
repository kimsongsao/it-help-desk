<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view the ticket list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::TicketsViewAll)
            || $ticket->requester_id === $user->id
            || $ticket->assignee_id === $user->id;
    }

    /**
     * Determine whether the user can submit tickets.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::TicketsCreate);
    }

    /**
     * Determine whether the user can change the ticket's status, priority and category.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::TicketsManage) && $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can change who the ticket is assigned to.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::TicketsAssign) && $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can reply to the ticket.
     */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can add internal notes to the ticket.
     */
    public function commentInternal(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::TicketsCommentInternal) && $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can escalate the ticket.
     */
    public function escalate(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::TicketsEscalate)
            && $ticket->status->isActive()
            && $this->view($user, $ticket);
    }

    /**
     * Determine whether the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(Permission::TicketsDelete);
    }
}
