<?php

namespace Challengr\Query;

class ChallengeStats
{
    public static function getForChallenge($id)
    {
        $ret = [];
        foreach (\DB::select(' select sum(activities.duration) duration, sum(activities.distance_miles) distance_miles,
                activities.user_id from activities join challenge_user on challenge_user.user_id = activities.user_id
                join challenges on challenges.id = challenge_user.challenge_id
                where activities.started_at between challenges.starts_at and challenges.ends_at && challenges.id = ?
                group by activities.user_id', [$id]) as $row) {
            $ret[$row->user_id] = ['distance_miles' => $row->distance_miles, 'duration' => $row->duration];
        }
        return $ret;
    }
}
