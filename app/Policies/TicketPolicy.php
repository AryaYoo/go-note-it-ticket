<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Boleh melihat daftar tiket.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Boleh melihat detail tiket tertentu.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->company_id === (int) $ticket->company_id;
    }

    /**
     * Boleh membuat tiket baru.
     */
    public function create(User $user): bool
    {
        return ! $user->isSuperAdmin() && ! empty($user->company_id);
    }

    /**
     * Boleh mengubah tiket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->company_id === (int) $ticket->company_id;
    }

    /**
     * Boleh menghapus tiket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->company_id === (int) $ticket->company_id 
            && ($user->isManager() || (int) $user->id === (int) $ticket->user_id);
    }
}
