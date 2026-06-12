<?php

namespace App\Models;

use Database\Factories\ForgeCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForgeCredential extends Model
{
    /** @use HasFactory<ForgeCredentialFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'encrypted_token',
        'token_fingerprint',
        'forge_user_id',
        'forge_email',
        'last_verified_at',
    ];

    protected $hidden = [
        'encrypted_token',
        'token_fingerprint',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_verified_at' => 'datetime',
        ];
    }
}
