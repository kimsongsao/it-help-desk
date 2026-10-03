<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UpdateAvatar
{
    /**
     * The width and height of stored profile photos, in pixels.
     */
    private const int Size = 256;

    /**
     * Re-encode the uploaded photo as a square WebP and make it the user's avatar.
     *
     * Re-encoding drops metadata and anything embedded in the original file.
     */
    public function handle(User $user, UploadedFile $photo): void
    {
        $source = imagecreatefromstring((string) file_get_contents($photo->getRealPath()))
            ?: throw new RuntimeException('The photo could not be read.');

        $side = min(imagesx($source), imagesy($source));
        $avatar = imagecreatetruecolor(self::Size, self::Size);
        imagecopyresampled(
            $avatar, $source, 0, 0,
            intdiv(imagesx($source) - $side, 2), intdiv(imagesy($source) - $side, 2),
            self::Size, self::Size, $side, $side,
        );

        ob_start();
        imagewebp($avatar, null, 85);
        $contents = (string) ob_get_clean();

        $path = 'avatars/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $contents);

        $this->remove($user);
        $user->forceFill(['avatar_path' => $path])->save();
    }

    /**
     * Delete the user's current avatar file, if any.
     */
    public function remove(User $user): void
    {
        if ($user->avatar_path !== null) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }
    }
}
