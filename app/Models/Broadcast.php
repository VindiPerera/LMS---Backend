<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['admin_id', 'title', 'body', 'audience_filters', 'recipient_estimate', 'status'])]
class Broadcast extends Model
{
    protected function casts(): array
    {
        return [
            'audience_filters' => 'array',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
