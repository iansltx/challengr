<?php

namespace Challengr;

use Illuminate\Database\Eloquent\Model;

/**
 * Challengr\Activity
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property float $distance_miles
 * @property string $duration
 * @property string $started_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereDistanceMiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Challengr\Activity whereUserId($value)
 * @mixin \Eloquent
 */
class Activity extends Model
{
    //
}
