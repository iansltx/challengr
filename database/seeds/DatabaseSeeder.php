<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // As fair warning, we're using mt_rand here rather than pulling stuff out of a probability distribution.
    // Realistically, user activity would likely follow normal or NegExp distributions. Future work: switch to
    // a distribution for these numbes, with e.g. emonkak/php-random
    const RANDOM_SEED = 1337; // I want the same distribution of "random" values each time

    /**
     * Seed the application's database.
     *
     * @return void
     * @throws Exception
     */
    public function run()
    {
        // add a bunch of users
        $userIds = [];

        $faker = Faker\Factory::create();
        $faker->seed(static::RANDOM_SEED);

        $userTable = DB::table('users');
        $userIdCount = 10000;

        error_log("Adding users");

        for ($i = 0; $i < $userIdCount; $i++) {
            $userIds[] = $userTable->insertGetId([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
                'remember_token' => Str::random(10),
            ]);
        }

        mt_srand(static::RANDOM_SEED);

        error_log("Adding activities");

        // add a bunch of activities
        $activityTable = DB::table('activities');
        for ($i = 0; $i < 150000; $i++) {
            $activityTable->insert([
                'name' => $faker->slug(3),
                'duration' => \Challengr\Util::secondsToTime($durationInSeconds = mt_rand(175, 7300)),
                'distance_miles' => $this->getRandomMiles($durationInSeconds),
                'started_at' => $this->getRandomDate(time() - 60 * 60 * 24 * 30, time()),
                'user_id' => $userIds[mt_rand(0, $userIdCount - 1)],
            ]);
        }

        // add not-so-many challenges
        $challengeTable = DB::table('challenges');
        $challengeUserTable = Db::table('challenge_user');

        error_log("Adding challenges + challenge-user association");

        for ($i = 0; $i < 300; $i++) {
            $challengeId = $challengeTable->insertGetId([
                'name' => $faker->slug(2),
                'duration' => $duration = (mt_rand(0, 10) >= 5 ? null :
                    \Challengr\Util::secondsToTime($durationInSeconds = mt_rand(1, 100) * 1800)),
                'distance_miles' => ($duration || mt_rand(0, 10) >= 3) ? null : mt_rand(1, 150) * 2.5,
                'starts_at' => ($startAt = $this->getRandomDate(time() - 60 * 60 * 24 * 33, time()))
                    ->format('Y-m-d H:i:s'),
                'ends_at' => $startAt->add(new \DateInterval('P' . mt_rand(1, 60) . 'D'))->format('Y-m-d H:i:s'),
                'user_id' => ($userId = $userIds[mt_rand(0, $userIdCount - 1)])
            ]);

            // add users to challenges
            $numberOfUsersToAdd = mt_rand(0, 1000);
            $userIdsInChallengeMap = [$userId => true];
            for ($j = 0; $j < $numberOfUsersToAdd; $j++) {
                $userId = $userIds[mt_rand(0, $userIdCount - 1)];
                if (isset($userIdsInChallengeMap[$userId])) {
                    continue;
                }
                $userIdsInChallengeMap[$userId] = true;

                $challengeUserTable->insert([
                    'challenge_id' => $challengeId,
                    'user_id' => $userId
                ]);
            }
        }
    }

    private function getRandomMiles(int $durationInSeconds)
    {
        $averageSpeed = mt_rand(00, 200) / 10;
        return round(($durationInSeconds / 3600) * $averageSpeed, 3);
    }

    /**
     * @param int $minTs
     * @param int $maxTs
     * @return \DateTimeImmutable
     * @throws Exception
     */
    public function getRandomDate(int $minTs, int $maxTs)
    {
        return (new \DateTimeImmutable())->setTimestamp(mt_rand($minTs, $maxTs))->setTime(0, 0);
    }
}
