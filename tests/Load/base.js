import http from "k6/http";
import {check, fail, sleep} from "k6";
import {Trend} from "k6/metrics";
// noinspection JSFileReferences
import {Normal} from "https://gist.githubusercontent.com/iansltx/bf3f980eeedf29dfc53c71d5c62d9a15/raw/b67db941aa3effe5c75dede3be2b6054a77e7e4e/distributions.js";
// Browserified "distributions" npm module, see https://github.com/AndreasMadsen/distributions

// baseURL e.g. http://my-load-test-system.local/
const [baseURL, clientId, clientSecret] = open('./config.txt').split("\n"),
    // emails should start on the second line of the document, one per line, e.g.
    // mysql -B -e "SELECT email FROM users order by md5(email)" > emails.csv
    emails = open("./emails.csv").split("\n").slice(1),

    /** TEST PARAMETERS **/
    pCorrectCredentials = 0.8,
    pRetryAfterFailedCreds = 0.5,
    pAbandonAfterHomeLoad = 0.15,
    pAddChallenge = 0.05,
    pAddAnotherActivity = 0.05,
    pIncludeChallengeDuration = 0.5,
    pIncludeChallengeMileage = 0.5,
    // start with larger units than minutes/seconds and single miles for more accurate
    // approximation of what challenges look like
    challengeMinHalfHours = 1,
    challengeMaxHalfHours = 80,
    challengeMinTenMiles = 1,
    challengeMaxTenMiles = 20,

    challengeThinkTime = new Normal(30, 10),
    activityThinkTime = new Normal(30, 10),
    secondActivityThinkTime = new Normal(10, 3),
    activitySpeed = new Normal(15, 3),

    challengeListResponseTime = new Trend("challenge_list_response_time"),
    activityListResponseTime = new Trend("activity_list_response_time"),
    userProfileResponseTime = new Trend("user_profile_response_time"),

    activityMinSeconds = 180,
    activityMaxSeconds = 10800;
    /** END OF TEST PARAMETERS **/

function fromDist(dist)
{
    return Math.floor(dist.inv(Math.random()));
}

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min)) + min;
}

function getDistance(seconds) {
    return activitySpeed.inv(Math.random()) * (seconds / 3600);
}

function secondsToTime(seconds) {
    let hours = Math.floor(seconds / 3600), minutes = Math.floor((seconds % 3600) / 60), secs = seconds % 60;
    return (hours >= 10 ? hours : ("0" + hours)) + ':' +
        (minutes >= 10 ? minutes : ("0" + minutes)) + ':' +
        (secs >= 10 ? secs : ("0" + secs));
}

function randomDate(withinLastDays = 30) {
    const date = new Date(Date.now() - getRandomInt(0, 60 * 60 * 24 * withinLastDays * 1000));
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const day = date.getDate();

    return year + '-' + (month >= 10 ? month : ("0" + month)) + '-' + (day >= 10 ? day : ("0" + day));
}

export default function () {
    let isIncorrectLogin = Math.random() > pCorrectCredentials,
        email = emails[getRandomInt(0, emails.length)];

    let resLogin = http.post(baseURL + "oauth/token", {
        "client_id": clientId,
        "client_secret": clientSecret,
        "grant_type": "password",
        "username": email,
        "password": isIncorrectLogin ? "seekrit" : "secret",
    }, {
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        }
    });

    if (isIncorrectLogin) {
        check(resLogin, {"invalid login caught": (res) => res.status === 401})
        || fail("no 401 on invalid login");

        if (Math.random() > pRetryAfterFailedCreds) {
            return; // abandon on incorrect login
        }

        // log in the correct way this time
        resLogin = http.post(baseURL + "oauth/token", {
            "client_id": clientId,
            "client_secret": clientSecret,
            "grant_type": "password",
            "username": email,
            "password": "secret",
        }, {
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            }
        });
    }

    check(resLogin, {
        "login succeeded": (res) => res.status === 200
            && typeof res.json().access_token !== "undefined",
    }) || fail("failed to log in");

    let params = {
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "Authorization": "Bearer " + resLogin.json().access_token
        }
    }, makeGet = function (path) {
        return {method: "GET", url: baseURL + path, params: params};
    };

    let homeScreenResponses = http.batch({
        "me": makeGet("api/me"),
        "challenges": makeGet("api/me/challenges"),
        "activities": makeGet("api/me/activities")
    });

    check(homeScreenResponses["me"],
        {"User profile loaded": (res) => res.json().email === email})
        || fail("user profile email did not match");
    check(homeScreenResponses["challenges"],
        {"Challenges list loaded": (res) => res.status === 200})
        || fail("challenges list GET failed");
    check(homeScreenResponses["activities"],
        {"Activities list loaded": (res) => res.status === 200})
        || fail("activities list GET failed");

    activityListResponseTime.add(homeScreenResponses["activities"].timings.duration);
    challengeListResponseTime.add(homeScreenResponses["challenges"].timings.duration);
    userProfileResponseTime.add(homeScreenResponses["me"].timings.duration);

    let pNextAction = Math.random();
    if (pNextAction > (1 - pAbandonAfterHomeLoad)) {
        return; // abandon here
    } else if (pNextAction > (1 - pAbandonAfterHomeLoad - pAddChallenge)) {
        sleep(fromDist(challengeThinkTime)); // think time before creating challenge
        let startMonth = getRandomInt(7, 9), endMonth = startMonth + getRandomInt(1, 2),
            challengeRes = http.post(baseURL + "api/challenges", JSON.stringify({
                "name": "Test Challenge",
                "starts_at": "2019-0" + startMonth + "-01 00:00:00",
                "ends_at": "2019-" + (endMonth >= 10 ? endMonth : ("0" + endMonth)) + "-01 00:00:00",
                "duration": Math.random() > pIncludeChallengeDuration ? null :
                    secondsToTime(getRandomInt(challengeMinHalfHours, challengeMaxHalfHours) * 1800),
                "distance_miles": Math.random() > pIncludeChallengeMileage ? null :
                    getRandomInt(challengeMinTenMiles, challengeMaxTenMiles) * 10
            }), params);

        check(challengeRes, {"challenge was created": (res) => res.status === 201 && res.json().id})
            || fail("challenge create failed");

        let challengeListRes = http.get(baseURL + "api/me/challenges", params);
        check(challengeListRes, {
            "challenge is in user challenge list": (res) => {
                let json = res.json();
                for (let i = 0; i < json.created.length; i++) {
                    if (json.created[i].id === challengeRes.json().id) {
                        return true;
                    }
                }
                return false;
            }
        }) || fail("challenge was not in user challenge list");

        return;
    }

    sleep(fromDist(activityThinkTime));

    let activitySeconds = getRandomInt(activityMinSeconds, activityMaxSeconds),
        resActivity = http.post(baseURL + "api/me/activities", JSON.stringify({
            "name": "My Activity",
            "duration": secondsToTime(activitySeconds),
            "distance_miles": getDistance(activitySeconds),
            "started_at": randomDate()
        }), params);

    check(resActivity, {"activity was created": (res) => res.status === 201 && res.json().id})
        || fail("activity create failed");

    if (pAddAnotherActivity > Math.random()) {
        sleep(fromDist(secondActivityThinkTime));

        let activitySeconds = getRandomInt(activityMinSeconds, activityMaxSeconds),
            resActivity = http.post(baseURL + "api/me/activities", JSON.stringify({
                "name": "My Second Activity",
                "duration": secondsToTime(activitySeconds),
                "distance_miles": getDistance(activitySeconds),
                "started_at": randomDate()
            }), params);

        check(resActivity, {"activity was created": (res) => res.status === 201 && res.json().id})
            || fail("activity create failed");
    }
}
