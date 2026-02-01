<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'ticket_status_id' => null, // Will be set by tests
            'priority_id' => null, // Will be set by tests
            'name' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'start_date' => fake()->optional()->date(),
            'due_date' => fake()->optional()->date(),
            'epic_id' => null,
            'created_by' => null,
        ];
    }
}
