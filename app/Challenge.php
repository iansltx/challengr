<?php

namespace Challengr;

use Challengr\Query\ChallengeStats;
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
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'distance_miles', 'duration', 'starts_at', 'ends_at'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function users_joined()
    {
        return $this->belongsToMany(User::class);
    }

    public function toArray()
    {
        return parent::toArray() + ['leaderboard' => $this->getLeaderboard()];
    }

    public function getLeaderboard()
    {
        return $this->duration ? $this->getDurationLeaderboard() : $this->getDistanceLeaderboard();
    }

    public function getDurationLeaderboard()
    {
        $stats = ChallengeStats::getForChallenge($this->id);

        $arr = $this->users_joined->map(function(User $user) use ($stats) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'duration_seconds' => $stats[$user->id]['duration'] ?? 0,
                'distance_miles' => $stats[$user->id]['distance_miles'] ?? 0,
                'duration' => Util::secondsToTime($stats[$user->id]['duration'] ?? 0),
            ];
        })->toArray();
        usort($arr, function ($a, $b) {
            return $b['duration_seconds'] <=> $a['duration_seconds'];
        });
        return $arr;
    }

    public function getDistanceLeaderboard()
    {
        $stats = ChallengeStats::getForChallenge($this->id);

        $arr = $this->users_joined->map(function(User $user) use ($stats) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'duration_seconds' => $stats[$user->id]['duration'] ?? 0,
                'distance_miles' => $stats[$user->id]['distance_miles'] ?? 0,
                'duration' => Util::secondsToTime($stats[$user->id]['duration'] ?? 0),
            ];
        })->toArray();
        usort($arr, function ($a, $b) {
            return $b['distance_miles'] <=> $a['distance_miles'];
        });
        return $arr;
    }
}
