<?php

namespace Inovector\Mixpost\Models;

use App\Models\AddOnSubscription;
use App\Models\Language;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Inovector\Mixpost\Abstracts\User as UserAbstract;

class User extends UserAbstract
{
    use HasFactory;

    public function userSubscription()
    {
        return $this->hasOne(Subscription::class, 'user_id', 'id')->latest();
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function addOnSubscription()
    {
        return $this->hasOne(AddOnSubscription::class, 'user_id', 'id')->latest();
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }
}
