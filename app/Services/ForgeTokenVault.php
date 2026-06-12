<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Native\Desktop\System;
use RuntimeException;

class ForgeTokenVault
{
    public function encrypt(string $token): string
    {
        if ($this->isRunningNatively()) {
            $system = app(System::class);

            if (! $system->canEncrypt()) {
                throw new RuntimeException('Secure operating system storage is unavailable.');
            }

            $encryptedToken = $system->encrypt($token);

            if ($encryptedToken === null) {
                throw new RuntimeException('The token could not be encrypted.');
            }

            return 'native:'.$encryptedToken;
        }

        return 'laravel:'.Crypt::encryptString($token);
    }

    public function decrypt(string $encryptedToken): string
    {
        if (str_starts_with($encryptedToken, 'native:')) {
            $token = app(System::class)->decrypt(substr($encryptedToken, 7));

            if ($token === null) {
                throw new RuntimeException('The token could not be decrypted.');
            }

            return $token;
        }

        if (str_starts_with($encryptedToken, 'laravel:')) {
            return Crypt::decryptString(substr($encryptedToken, 8));
        }

        throw new RuntimeException('Unknown token encryption format.');
    }

    private function isRunningNatively(): bool
    {
        return filter_var(config('nativephp.running', false), FILTER_VALIDATE_BOOL);
    }
}
