<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Keep role, status, trial_ends_at, subscription_ends_at, and subscription_status
     * un-fillable by default mass assignment to prevent self-escalation security exploits.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'trial_ends_at'         => 'datetime',
            'subscription_ends_at'  => 'datetime',
            'password'              => 'hashed',
        ];
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user account is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if user is on active trial or active subscription.
     */
    public function hasActiveAccess(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isBlocked()) {
            return false;
        }

        if ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()) {
            return true;
        }

        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Grant or extend paid subscription duration.
     */
    public function grantSubscription(int $days = 30): void
    {
        $base = ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()) ? $this->subscription_ends_at : now();
        $this->subscription_ends_at = $base->copy()->addDays($days);
        $this->subscription_status = 'active';
        $this->save();
    }

    /**
     * Grant or extend free trial duration.
     */
    public function extendTrial(int $days = 30): void
    {
        $base = ($this->trial_ends_at && $this->trial_ends_at->isFuture()) ? $this->trial_ends_at : now();
        $this->trial_ends_at = $base->copy()->addDays($days);
        $this->subscription_status = 'trial';
        $this->save();
    }

    /**
     * Expire all trial and paid subscription access immediately.
     */
    public function expireAccess(): void
    {
        $this->trial_ends_at = now()->subMinute();
        $this->subscription_ends_at = now()->subMinute();
        $this->subscription_status = 'expired';
        $this->save();
    }
}
