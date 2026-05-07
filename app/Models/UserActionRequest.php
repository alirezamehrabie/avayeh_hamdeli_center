<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActionRequest extends Model
{
    use HasFactory;

    public const ACTION_DELETE = 'delete_user';
    public const ACTION_DOWNGRADE = 'downgrade_admin';
    public const ACTION_PROMOTE = 'promote_to_admin';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'action_type',
        'target_user_id',
        'requested_by_user_id',
        'approved_by_user_id',
        'status',
    ];
}
