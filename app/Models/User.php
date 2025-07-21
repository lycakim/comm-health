<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Afsakar\FilamentOtpLogin\Models\Contracts\CanLoginDirectly;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanLoginDirectly
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => RoleEnum::class,
        ];
    }

    public function barangays()
    {
        return $this->belongsToMany(Barangay::class, 'barangay_users')
            ->using(BarangayUser::class)
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleEnum::ADMIN;
    }

    public function isMHO(): bool
    {
        return $this->role === RoleEnum::MHO;
    }

    public function isBHW(): bool
    {
        return $this->role === RoleEnum::BHW;
    }

    public function isResident(): bool
    {
        return $this->role === RoleEnum::RESIDENT;
    }

    public function isMidwife(): bool
    {
        return $this->role === RoleEnum::MIDWIFE;
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function canLoginDirectly(): bool
    {
        // return $this->role === RoleEnum::ADMIN;
        return true;
    }

    // Accessor to convert string to enum
    // public function getRoleAttribute($value): RoleEnum
    // {
    //     return RoleEnum::from($value);
    // }

    // Mutator to convert enum to string when saving
    // public function setRoleAttribute($value): void
    // {
    //     $this->attributes['role'] = $value instanceof RoleEnum ? $value->value : $value;
    // }
}