<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected $table = 'User';

    public $timestamps = false;

    protected $fillable = ['firebase_uid', 'name', 'email', 'account_status_id', 'profile_picture', 'chef_application_id', 'chef_id', 'is_chef', 'is_developer', 'is_email_verified', 'date_created'];

    protected $hidden = ['password'];

    protected function casts(): array {
        return ['is_chef' => 'boolean', 'is_developer' => 'boolean', 'is_email_verified' => 'boolean', 'date_created' => 'immutable_datetime'];
    }

    public function chef(): BelongsTo {
        return $this->belongsTo(Chef::class, 'chef_id');
    }

    public function chefApplication(): HasOne {
        return $this->hasOne(ChefApplication::class, 'user_id');
    }

    public function favoriteRecipes(): BelongsToMany {
        return $this->belongsToMany(Recipe::class, 'UserRecipeFavorite', 'user_id', 'recipe_id');
    }

    public function isEnabled(): bool {
        return (int)$this->account_status_id === 1;
    }
}
