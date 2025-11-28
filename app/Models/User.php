<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use Spatie\Activitylog\LogOptions;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Afsakar\FilamentOtpLogin\Models\Contracts\CanLoginDirectly;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements CanLoginDirectly
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity;

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
            'privacy_accepted_at' => 'datetime',
        ];
    }

    public function hasAcceptedPrivacy(): bool
    {
        return !is_null($this->privacy_accepted_at);
    }

    public function needsPrivacyAcceptance(): bool
    {
        // Admin users don't need to accept privacy policy
        if ($this->isAdmin()) {
            return false;
        }

        return !$this->hasAcceptedPrivacy();
    }

    public function barangays()
    {
        return $this->belongsToMany(Barangay::class, 'barangay_users')
            ->using(BarangayUser::class)
            ->withTimestamps();
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function assignedBarangay()
    {
        return $this->belongsToMany(Barangay::class, 'barangay_users', 'user_id', 'barangay_id')
                    ->limit(1);
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
        // Always allow direct login for admins
        // if ($this->role === RoleEnum::ADMIN) {
        //     return true;
        // }

        // Allow direct login if OTP has already been used today
        // return $this->last_otp_login_at?->isToday() ?? false;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn(string $eventName) => "User {$this->name} has been {$eventName}");
    }
}