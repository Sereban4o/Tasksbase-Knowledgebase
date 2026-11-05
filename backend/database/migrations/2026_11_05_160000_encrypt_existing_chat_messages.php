<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypt chat message bodies that were stored as plaintext before the
 * `encrypted` cast was introduced. Already-encrypted values are left as is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('messages')->orderBy('id')->chunkById(100, function ($messages) {
            foreach ($messages as $message) {
                $body = $message->body;

                if ($body === null || $body === '') {
                    continue;
                }

                try {
                    Crypt::decryptString($body);
                    // Already encrypted — nothing to do.
                } catch (\Throwable $e) {
                    DB::table('messages')->where('id', $message->id)->update([
                        'body' => Crypt::encryptString($body),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Irreversible: encrypted data cannot be safely converted back.
    }
};
