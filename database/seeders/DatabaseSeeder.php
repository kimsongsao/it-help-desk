<?php

namespace Database\Seeders;

use App\Actions\Organizations\CreateOrganization;
use App\Actions\Tickets\AddTicketComment;
use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\UpdateTicket;
use App\Enums\HelpDeskTemplate;
use App\Enums\Plan;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\User;
use App\Services\Sla\SlaBreachMonitor;
use App\Support\Tenancy;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class DatabaseSeeder extends Seeder
{
    public function __construct(
        private CreateOrganization $createOrganization,
        private CreateTicket $createTicket,
        private UpdateTicket $updateTicket,
        private AddTicketComment $addComment,
        private Tenancy $tenancy,
    ) {}

    /**
     * Seed a platform owner and two demo companies of different sizes.
     */
    public function run(SlaBreachMonitor $slaMonitor): void
    {
        $platformAdmin = $this->createOrganization->handle([
            'company_name' => 'Kdigitized Solutions',
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => 'password',
        ], Plan::Enterprise);
        $platformAdmin->forceFill(['is_platform_admin' => true])->save();

        $this->seedEnterpriseCompany();
        $this->seedSmallBusiness();

        $slaMonitor->run();
    }

    /**
     * An enterprise IT department with supervisors, agents, SLAs and workflows.
     */
    private function seedEnterpriseCompany(): void
    {
        $admin = $this->createOrganization->handle([
            'company_name' => 'Acme Corporation',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'department' => 'IT',
        ], Plan::Enterprise, template: HelpDeskTemplate::ItSupport);

        $this->tenancy->runAs($admin->organization, function () use ($admin): void {
            $admin->organization->update(['allowed_email_domains' => ['acme.test'], 'allow_self_registration' => false]);

            User::factory()->supervisor()->create(['name' => 'Sam Supervisor', 'email' => 'supervisor@example.com']);

            $agents = User::factory()->agent()->count(2)->sequence(
                ['name' => 'Agent Smith', 'email' => 'agent@example.com'],
                ['name' => 'Agent Jones', 'email' => 'agent2@example.com'],
            )->create();

            $requesters = User::factory()->count(5)->create()
                ->push(User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']));

            $this->call([SlaPolicySeeder::class, WorkflowSeeder::class]);

            $this->seedTickets($requesters, $agents, $admin, [
                'Laptop will not boot', 'VPN keeps disconnecting', 'Need access to the finance share',
                'Outlook crashes on start', 'Printer on 3rd floor is jammed', 'Wi-Fi is down in meeting room B',
                'Request new monitor', 'Password reset for ERP', 'Teams calls drop audio',
            ]);
        });
    }

    /**
     * A small café using the free plan for customer service.
     */
    private function seedSmallBusiness(): void
    {
        $owner = $this->createOrganization->handle([
            'company_name' => 'Sunrise Café',
            'name' => 'Dara Owner',
            'email' => 'cafe@example.com',
            'password' => 'password',
        ], Plan::Free, template: HelpDeskTemplate::CustomerService);

        $this->tenancy->runAs($owner->organization, function () use ($owner): void {
            $owner->organization->update(['allow_self_registration' => true]);

            $agents = User::factory()->agent()->count(1)->create(['name' => 'Sophea Staff', 'email' => 'staff@example.com']);
            $customers = User::factory()->count(3)->create(['department' => null]);

            $this->seedTickets($customers, $agents, $owner, [
                'Where is my catering order?', 'Charged twice for my coffee subscription',
                'Do you have oat milk?', 'Refund for cancelled booking', 'Great service today!',
            ]);
        });
    }

    /**
     * Create a few weeks of realistic ticket history.
     *
     * @param  Collection<int, User>  $requesters
     * @param  Collection<int, User>  $agents
     * @param  list<string>  $subjects
     */
    private function seedTickets(Collection $requesters, Collection $agents, User $manager, array $subjects): void
    {
        $categories = Category::all();

        foreach ($requesters as $requester) {
            foreach (range(1, 3) as $index) {
                $openedAt = now()->subMinutes(fake()->numberBetween(30, 20 * 24 * 60));

                $ticket = $this->at($openedAt, fn () => $this->createTicket->handle($requester, [
                    'subject' => fake()->randomElement($subjects),
                    'description' => fake()->paragraphs(2, true),
                    'priority' => fake()->randomElement(TicketPriority::cases())->value,
                    'category_id' => $categories->random()->id,
                ]));

                $agent = $ticket->assignee ?? $agents->random();
                $respondedAt = $openedAt->copy()->addMinutes(fake()->numberBetween(5, 6 * 60));

                if (fake()->boolean(75) && $respondedAt->isPast()) {
                    $this->at($respondedAt, function () use ($ticket, $agent, $manager): void {
                        $this->updateTicket->handle($ticket, ['assignee_id' => $agent->id, 'status' => TicketStatus::InProgress->value], $manager);
                        $this->addComment->handle($ticket, $agent, 'Thanks for reaching out, I am looking into it now.');
                    });

                    $resolvedAt = $respondedAt->copy()->addMinutes(fake()->numberBetween(30, 4 * 24 * 60));

                    if (fake()->boolean(60) && $resolvedAt->isPast()) {
                        $this->at($resolvedAt, fn () => $this->updateTicket->handle($ticket, ['status' => TicketStatus::Resolved->value], $agent));
                    }
                }
            }
        }
    }

    /**
     * Run the callback as if the current time were the given moment.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function at(CarbonInterface $moment, callable $callback): mixed
    {
        Date::setTestNow($moment);

        try {
            return $callback();
        } finally {
            Date::setTestNow();
        }
    }
}
