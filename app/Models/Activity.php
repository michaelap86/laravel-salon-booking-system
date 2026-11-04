<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Activity extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'subject', 'details', 'url', 'ip', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Record something that happened, attributed to the signed-in user if any. */
    public static function log(string $action, ?string $subject = null, ?string $details = null, ?string $url = null, ?Request $request = null): self
    {
        $request ??= request();

        return static::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject' => $subject,
            'details' => $details,
            'url' => $url,
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    public static function labels(): array
    {
        return [
            'booking' => 'New booking', 'cancel' => 'Cancelled', 'status' => 'Status change', 'reschedule' => 'Rescheduled',
            'login' => 'Signed in', 'logout' => 'Signed out', 'service' => 'Service change', 'staff' => 'Staff change',
            'client' => 'Client change', 'review' => 'Review', 'settings' => 'Settings', 'user' => 'User change',
            'message' => 'Message', 'export' => 'Export',
        ];
    }
}
