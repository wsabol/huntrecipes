<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable {
    use HasFactory;
    use Notifiable;

    protected $table = 'User';
    public $timestamps = false;
    protected $fillable = ['firebase_uid', 'name', 'email', 'account_status_id', 'profile_picture', 'chef_application_id', 'chef_id', 'is_chef', 'is_developer', 'is_email_verified', 'date_created'];
    protected $hidden = ['password'];
    protected $appends = ['profile_picture_lg'];

    public function getProfilePictureLgAttribute(): ?string {
        $picture = $this->profile_picture;
        $host = strtolower((string) parse_url((string) $picture, PHP_URL_HOST));
        if (parse_url((string) $picture, PHP_URL_SCHEME) === 'https'
            && ($host === 'googleusercontent.com' || str_ends_with($host, '.googleusercontent.com'))) {
            if (preg_match('/([?&])sz=\d+/', $picture)) {
                return preg_replace('/([?&])sz=\d+/', '${1}sz=608', $picture);
            }
            if (preg_match('/=s\d+|\/s\d+(?:-c)?\//', $picture)) {
                return preg_replace(['/=s\d+/', '/\/s\d+(-c)?\//'], ['=s608', '/s608$1/'], $picture);
            }

            return preg_replace('/(?=\?|$)/', '=s608-c', $picture, 1);
        }
        if (str_starts_with((string) $picture, '/storage/users/')) {
            $large = preg_replace('/\.[^.]+$/', '-lg.jpg', substr($picture, strlen('/storage/')));
            if (Storage::disk('public')->exists($large)) {
                return '/storage/' . $large;
            }
        }

        return $picture;
    }

    protected function casts(): array {
        return ['is_chef' => 'boolean', 'is_developer' => 'boolean', 'is_email_verified' => 'boolean', 'date_created' => 'immutable_datetime'];
    }

    public function chef(): BelongsTo {
        return $this->belongsTo(Chef::class, 'chef_id');
    }

    public function chefApplication(): HasOne {
        return $this->hasOne(ChefApplication::class, 'user_id')->where('is_deleted', false);
    }

    public function favoriteRecipes(): BelongsToMany {
        return $this->belongsToMany(Recipe::class, 'UserRecipeFavorite', 'user_id', 'recipe_id');
    }

    public function isEnabled(): bool {
        return (int)$this->account_status_id === 1;
    }
}
