<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

test('guests can switch the interface to khmer', function () {
    $this->from(route('login'))
        ->post(route('locale.update'), ['locale' => 'km'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('locale', 'km');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('lang="km"', false)
        ->assertSee('សូមស្វាគមន៍ការត្រឡប់មកវិញ');
});

test('the chosen language is saved on the signed in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('locale.update'), ['locale' => 'km']);

    expect($user->refresh()->locale)->toBe('km');

    $this->flushSession();
    $this->actingAs($user)->get(route('dashboard'))->assertSee('ទំព័រដើម');
});

test('unsupported languages are rejected', function () {
    $this->post(route('locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
});

test('the browser language is used when nothing was chosen', function () {
    $this->get(route('login'), ['Accept-Language' => 'km-KH,km;q=0.9'])->assertSee('សូមស្វាគមន៍ការត្រឡប់មកវិញ');
    $this->get(route('login'), ['Accept-Language' => 'fr-FR'])->assertSee('Welcome back');
});

test('validation messages are shown in khmer', function () {
    $user = User::factory()->create(['locale' => 'km']);

    $this->actingAs($user)
        ->post(route('tickets.store'), [])
        ->assertSessionHasErrors(['subject' => 'សូមបំពេញ ចំណងជើង។']);
});

test('the api answers in the language of the user', function () {
    Sanctum::actingAs(User::factory()->create(['locale' => 'km']));

    $this->postJson(route('api.v1.tickets.store'), [])
        ->assertJsonValidationErrors(['subject' => 'សូមបំពេញ ចំណងជើង។']);
});

test('notifications are written in the language of the recipient', function () {
    $assignee = User::factory()->agent()->create(['locale' => 'km']);
    $ticket = Ticket::factory()->create();

    $assignee->notify(new TicketAssigned($ticket));

    expect($assignee->notifications()->sole()->data['message'])
        ->toBe("{$ticket->reference} ត្រូវបានប្រគល់ឱ្យអ្នកដោយស្វ័យប្រវត្តិ។");
});

test('the activity log is translated when it is displayed', function () {
    $agent = User::factory()->agent()->create(['locale' => 'km']);
    $ticket = Ticket::factory()->create(['category_id' => Category::factory()->create()->id]);

    $this->actingAs($agent)->patch(route('tickets.update', $ticket), ['status' => 'in_progress']);

    expect($ticket->activities()->latest('id')->first()->description)->toBe('Status changed from :from to :to');

    $this->actingAs($agent)->get(route('tickets.show', $ticket))
        ->assertSee('បានប្តូរស្ថានភាពពី បើក ទៅ កំពុងដំណើរការ');
});

test('every khmer translation key is used with the same placeholders as english', function () {
    $translations = json_decode(file_get_contents(lang_path('km.json')), true);

    foreach ($translations as $english => $khmer) {
        preg_match_all('/:[a-z_]+/i', $english, $expected);
        preg_match_all('/:[a-z_]+/i', $khmer, $actual);

        expect(collect($actual[0])->sort()->values()->all())
            ->toBe(collect($expected[0])->sort()->values()->all(), "Placeholders differ for \"{$english}\"");
    }
});

test('every interface string has a khmer translation', function () {
    $translations = json_decode(file_get_contents(lang_path('km.json')), true);
    $files = collect([
        ...File::allFiles(resource_path('views')),
        ...File::allFiles(app_path()),
    ]);

    $missing = $files
        ->flatMap(function (SplFileInfo $file): array {
            preg_match_all('/__\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/', file_get_contents($file->getPathname()), $matches);

            return array_map(
                fn (string $single, string $double): string => stripslashes($single !== '' ? $single : $double),
                $matches[1],
                $matches[2],
            );
        })
        ->unique()
        ->reject(fn (string $key): bool => str_contains($key, '.') && ! str_contains($key, ' '))
        ->reject(fn (string $key): bool => array_key_exists($key, $translations))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});
