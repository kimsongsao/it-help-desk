<?php

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * @return array<string, mixed>
 */
function ticketPayload(array $overrides = []): array
{
    return [
        'subject' => 'Printer prints blank pages',
        'description' => 'Since this morning.',
        'priority' => 'medium',
        'category_id' => Category::factory()->create()->id,
        ...$overrides,
    ];
}

test('tickets can be submitted with attachments', function () {
    Storage::fake('local');
    $requester = User::factory()->create();

    $this->actingAs($requester)->post(route('tickets.store'), ticketPayload([
        'attachments' => [
            UploadedFile::fake()->image('screenshot.png'),
            UploadedFile::fake()->create('error-log.pdf', 200, 'application/pdf'),
        ],
    ]))->assertSessionHasNoErrors();

    $ticket = Ticket::sole();
    $attachments = $ticket->ticketAttachments()->orderBy('id')->get();

    expect($attachments->pluck('original_name')->all())->toBe(['screenshot.png', 'error-log.pdf'])
        ->and($attachments->first()->uploader->is($requester))->toBeTrue()
        ->and($attachments->first()->is_voice_note)->toBeFalse();

    $attachments->each(fn (TicketAttachment $attachment) => Storage::disk('local')->assertExists($attachment->path));

    $this->actingAs($requester)->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee(route('attachments.show', $attachments->first()))
        ->assertSee('error-log.pdf');
});

test('a voice note can replace the written description', function () {
    Storage::fake('local');

    $this->actingAs(User::factory()->create())->post(route('tickets.store'), ticketPayload([
        'description' => '',
        'voice_note' => UploadedFile::fake()->create('voice-note.webm', 300, 'audio/webm'),
    ]))->assertSessionHasNoErrors();

    $ticket = Ticket::sole();
    $voiceNote = $ticket->attachments()->sole();

    expect($ticket->description)->toBe('')
        ->and($voiceNote->is_voice_note)->toBeTrue()
        ->and($voiceNote->isAudio())->toBeTrue();
});

test('a ticket needs a description or a voice note', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tickets.store'), ticketPayload(['description' => '']))
        ->assertSessionHasErrors(['description' => 'The description field is required when voice note is not present.']);

    expect(Ticket::count())->toBe(0);
});

test('attachments are validated', function (array $upload, string $errorKey) {
    Storage::fake('local');

    $this->actingAs(User::factory()->create())
        ->post(route('tickets.store'), ticketPayload($upload))
        ->assertSessionHasErrors($errorKey);

    expect(Ticket::count())->toBe(0);
})->with([
    'executable files' => [fn () => ['attachments' => [UploadedFile::fake()->create('setup.exe', 10)]], 'attachments.0'],
    'files over 10 MB' => [fn () => ['attachments' => [UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf')]], 'attachments.0'],
    'more than five files' => [fn () => ['attachments' => array_map(fn ($index) => UploadedFile::fake()->image("{$index}.png"), range(1, 6))], 'attachments'],
    'voice notes that are not audio' => [fn () => ['voice_note' => UploadedFile::fake()->create('note.pdf', 10, 'application/pdf')], 'voice_note'],
]);

test('replies can carry attachments without a message', function () {
    Storage::fake('local');
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)->post(route('tickets.comments.store', $ticket), [
        'attachments' => [UploadedFile::fake()->image('new-error.jpg')],
    ])->assertSessionHasNoErrors();

    $comment = $ticket->comments()->sole();
    expect($comment->attachments()->sole()->original_name)->toBe('new-error.jpg')
        ->and($comment->attachments()->sole()->ticket_id)->toBe($ticket->id);
});

test('an empty reply is rejected', function () {
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($requester)
        ->post(route('tickets.comments.store', $ticket), ['body' => ''])
        ->assertSessionHasErrors('body');
});

test('attachments are only served to users who can see the ticket', function () {
    Storage::fake('local');
    $requester = User::factory()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();
    $path = UploadedFile::fake()->image('photo.png')->store("attachments/{$ticket->id}", 'local');
    $attachment = TicketAttachment::factory()->for($ticket)->create([
        'path' => $path,
        'original_name' => 'photo.png',
        'mime_type' => 'image/png',
    ]);

    $this->get(route('attachments.show', $attachment))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('attachments.show', $attachment))->assertForbidden();

    $this->actingAs($requester)->get(route('attachments.show', $attachment))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs($requester)->get(route('attachments.show', [$attachment, 'download' => 1]))
        ->assertDownload('photo.png');
});

test('attachments on internal notes are hidden from the requester', function () {
    Storage::fake('local');
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), [
        'body' => 'Vendor quote',
        'is_internal' => '1',
        'attachments' => [UploadedFile::fake()->create('quote.pdf', 20, 'application/pdf')],
    ]);
    $attachment = TicketAttachment::sole();

    $this->actingAs($requester)->get(route('attachments.show', $attachment))->assertForbidden();
    $this->actingAs($agent)->get(route('attachments.show', $attachment))->assertOk();
});

test('deleting a ticket removes its stored files', function () {
    Storage::fake('local');
    $requester = User::factory()->create();

    $this->actingAs($requester)->post(route('tickets.store'), ticketPayload([
        'attachments' => [UploadedFile::fake()->image('screen.png')],
    ]));
    $ticket = Ticket::sole();
    $path = $ticket->attachments()->sole()->path;

    $this->actingAs(User::factory()->admin()->create())->delete(route('tickets.destroy', $ticket));

    Storage::disk('local')->assertMissing($path);
    expect(TicketAttachment::count())->toBe(0);
});

test('attachments can be uploaded and downloaded through the api', function () {
    Storage::fake('local');
    Sanctum::actingAs(User::factory()->create());

    $response = $this->post(route('api.v1.tickets.store'), ticketPayload([
        'attachments' => [UploadedFile::fake()->create('report.pdf', 50, 'application/pdf')],
    ]), ['Accept' => 'application/json']);

    $response->assertCreated()->assertJsonPath('data.attachments.0.name', 'report.pdf');

    $this->get($response->json('data.attachments.0.url'))->assertOk();
});
