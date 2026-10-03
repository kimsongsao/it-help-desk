<?php

namespace App\Enums;

use App\Models\Organization;

/**
 * Starting points for a new company's help desk. Each template only seeds categories, which can be edited later.
 */
enum HelpDeskTemplate: string
{
    case General = 'general';
    case ItSupport = 'it_support';
    case CustomerService = 'customer_service';
    case HumanResources = 'human_resources';
    case Facilities = 'facilities';

    /**
     * Get the translated name of the template.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::General => 'General help desk',
            self::ItSupport => 'IT support',
            self::CustomerService => 'Customer service',
            self::HumanResources => 'HR service desk',
            self::Facilities => 'Facilities & maintenance',
        }, locale: $locale);
    }

    /**
     * Get the Tabler icon that represents the template.
     */
    public function icon(): string
    {
        return match ($this) {
            self::General => 'ti-lifebuoy',
            self::ItSupport => 'ti-device-laptop',
            self::CustomerService => 'ti-headset',
            self::HumanResources => 'ti-users',
            self::Facilities => 'ti-building',
        };
    }

    /**
     * Get the categories the template starts with, as name => [description, icon].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function categories(): array
    {
        return match ($this) {
            self::General => [
                'General question' => ['Questions that do not fit anywhere else.', 'ti-help-circle'],
                'Technical problem' => ['Something is broken or not working as expected.', 'ti-tool'],
                'Account & Access' => ['Logins, passwords and permissions.', 'ti-key'],
                'Billing & payments' => ['Invoices, refunds and payment issues.', 'ti-receipt'],
                'Feedback & suggestions' => ['Ideas and comments to help us improve.', 'ti-message-heart'],
                'Other' => ['Anything that does not fit elsewhere.', 'ti-dots'],
            ],
            self::ItSupport => [
                'Hardware' => ['Laptops, desktops, monitors and peripherals.', 'ti-device-laptop'],
                'Software' => ['Application installs, licenses and bugs.', 'ti-apps'],
                'Network' => ['Wi-Fi, VPN and connectivity issues.', 'ti-wifi'],
                'Account & Access' => ['Password resets, permissions and new accounts.', 'ti-key'],
                'Email' => ['Mailbox, calendar and distribution lists.', 'ti-mail'],
                'Printer' => ['Printing and scanning problems.', 'ti-printer'],
                'Other' => ['Anything that does not fit elsewhere.', 'ti-help-circle'],
            ],
            self::CustomerService => [
                'Order status' => ['Where is my order, delivery and tracking.', 'ti-truck-delivery'],
                'Returns & refunds' => ['Returning items and getting money back.', 'ti-receipt-refund'],
                'Billing & payments' => ['Invoices, charges and payment methods.', 'ti-credit-card'],
                'Product question' => ['How a product works, sizes and availability.', 'ti-package'],
                'Complaint' => ['Something went wrong and we want to fix it.', 'ti-mood-sad'],
                'Other' => ['Anything that does not fit elsewhere.', 'ti-help-circle'],
            ],
            self::HumanResources => [
                'Leave & time off' => ['Annual leave, sick leave and holidays.', 'ti-beach'],
                'Payroll & benefits' => ['Salary, payslips, insurance and benefits.', 'ti-cash'],
                'Onboarding & offboarding' => ['New joiners and people leaving.', 'ti-user-plus'],
                'Policies & documents' => ['Contracts, letters and company policies.', 'ti-file-text'],
                'Training' => ['Courses, certifications and learning.', 'ti-school'],
                'Other' => ['Anything that does not fit elsewhere.', 'ti-help-circle'],
            ],
            self::Facilities => [
                'Repairs & maintenance' => ['Broken fixtures, doors, plumbing and electrics.', 'ti-tool'],
                'Cleaning' => ['Spills, waste and cleaning requests.', 'ti-wash'],
                'Air conditioning' => ['Too hot, too cold or noisy units.', 'ti-air-conditioning'],
                'Security & access cards' => ['Badges, keys and building access.', 'ti-id-badge'],
                'Meeting rooms & space' => ['Room setup, furniture and desk moves.', 'ti-armchair'],
                'Other' => ['Anything that does not fit elsewhere.', 'ti-help-circle'],
            ],
        };
    }

    /**
     * Create the template's categories for the organization.
     */
    public function createCategoriesFor(Organization $organization): void
    {
        foreach ($this->categories() as $name => [$description, $icon]) {
            $organization->categories()
                ->withoutGlobalScope('organization')
                ->updateOrCreate(['name' => $name], ['description' => $description, 'icon' => $icon]);
        }
    }
}
