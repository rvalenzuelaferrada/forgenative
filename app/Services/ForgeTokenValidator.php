<?php

namespace App\Services;

class ForgeTokenValidator
{
    public function __construct(private ForgeClientFactory $clients) {}

    /**
     * @return array{id: int|null, name: string|null, email: string|null}
     */
    public function validate(string $token): array
    {
        $user = $this->clients->make($token)->user();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
