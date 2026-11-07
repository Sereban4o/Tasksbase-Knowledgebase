<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Encrypts new values, but tolerates legacy plaintext stored in the database.
 *
 * Some rows were written before encryption was introduced (or by raw SQL /
 * tests). Reading them with the plain "encrypted" cast throws a
 * DecryptException and breaks API responses (e.g. the chat list). This cast
 * returns plaintext as-is and never re-encrypts already encrypted values.
 */
class EncryptedOrPlain implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // Do not double-encrypt values that are already encrypted.
        try {
            Crypt::decryptString($value);

            return $value;
        } catch (Throwable) {
            return Crypt::encryptString($value);
        }
    }
}
