<?php

namespace Challengr;

use Illuminate\Database\Eloquent\Model;

/**
 * Challengr\Challenge
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property float|null $distance_miles
 * @property string|null $duration
 * @property string $starts_at
 * @property string $ends_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Challengr\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\Challengr\User[] $users_joined
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereDistanceMiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Challenge whereUserId($value)
 * @mixin \Eloquent
 */
class Challenge extends Model
{
    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function users_joined()
    {
        return $this->hasManyThrough(User::class, ChallengeUser::class, 'challenge_id', 'user_id');
    }
}
