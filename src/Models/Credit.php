<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credit extends Model
{
    use HasFactory;

    public $table = 'mixpost_credits';

    protected $fillable = [
        'user_id',
        'inputs_count',
        'outputs_count',
        'credit_usage',
        'credit_type',
    ];

    protected $casts = [
        'inputs_count' => 'integer',
        'outputs_count' => 'integer',
        'credit_usage' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
