<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository
{
    /**
     * Create a new ticket
     *
     * @param array $data
     * @return Ticket
     */
    public function create(array $data): Ticket
    {
        // If ticket_status_id is not provided, find or create default "backlog" status
        if (!isset($data['ticket_status_id']) && isset($data['project_id'])) {
            $backlogStatus = TicketStatus::where('project_id', $data['project_id'])
                ->where('name', 'backlog')
                ->first();

            if ($backlogStatus) {
                $data['ticket_status_id'] = $backlogStatus->id;
            }
        }

        // If priority_id is not provided, find default "medium" priority
        if (!isset($data['priority_id'])) {
            $mediumPriority = TicketPriority::where('name', 'medium')->first();

            if ($mediumPriority) {
                $data['priority_id'] = $mediumPriority->id;
            }
        }

        return Ticket::create($data);
    }

    /**
     * Find tickets by project ID
     *
     * @param int $projectId
     * @return Collection
     */
    public function findByProject(int $projectId): Collection
    {
        return Ticket::where('project_id', $projectId)->get();
    }
}
