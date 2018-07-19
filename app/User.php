<?php

namespace Challengr;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * Challengr\User
 *
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\User whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\Challengr\Challenge[] $challenges_created
 * @property-read \Illuminate\Database\Eloquent\Collection|\Challengr\Challenge[] $challenges_joined
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function challenges_joined()
    {
        return $this->hasManyThrough(Challenge::class, ChallengeUser::class, 'user_id', 'challenge_id');
    }

    public function challenges_created()
    {
        return $this->hasMany(Challenge::class);
    }
}
