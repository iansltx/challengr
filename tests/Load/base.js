import http from "k6/http";
import { check, fail, sleep } from "k6";
import { Trend } from "k6/metrics";

const creds = open('./config.txt').split("\n"),
  baseURL = creds[0],
  clientId = creds[1],
  clientSecret = creds[2],
  // emails should start on the second line of the document, one per line;
  // mysql -B -e "SELECT email FROM users"
  emails = open("./emails.csv").split("\n").slice(1),

  /** TEST PARAMETERS **/
  pCorrectCredentials = 0.8,
  pRetryAfterFailedCreds = 0.5,
  pAbandonAfterHomeLoad = 0.15,
  pAddChallenge = 0.05,
  pAddAnotherActivity = 0.05,
  pIncludeChallengeDuration = 0.5,
  pIncludeChallengeMileage = 0.5,
  challengeMinHalfHours = 1,
  challengeMaxHalfHours = 80,
  challengeMinTenMiles = 1,
  challengeMaxTenMiles = 20,

  challengeThinkTime = {min: 0, max: 0},
  activityThinkTime = {min: 0, max: 0},
  secondActivityThinkTime = {min: 0, max: 0},

  challengeListResponseTime = new Trend("challenge_list_response_time"),
  activityListResponseTime = new Trend("activity_list_response_time"),
  userProfileResponseTime = new Trend("user_profile_response_time"),

  activityMinSeconds = 180,
  activityMaxSeconds = 10800,
  activityDateBounds = {
    year: {min: 2018, max: 2018},
    month: {min: 7, max: 7},
    day: {min: 1, max: 20}
  };
/** END OF TEST PARAMETERS **/

function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min)) + min;
}

function getDistance(seconds) {
  return getRandomInt(9, 22) * (seconds / 3600);
}

function secondsToTime(seconds) {
  let hours = Math.floor(seconds / 3600), minutes = Math.floor((seconds % 3600) / 60), secs = seconds % 60;
  return (hours >= 10 ? hours : ("0" + hours)) + ':' +
    (minutes >= 10 ? minutes : ("0" + minutes)) + ':' +
    (secs >= 10 ? secs : ("0" + secs));
}

function ymd(year, month, day) {
  return year + '-' + (month >= 10 ? month : ("0" + month)) + '-' + (day >= 10 ? day : ("0" + day));
}

export default function() {
  let isIncorrectLogin = Math.random() > pCorrectCredentials, email = emails[getRandomInt(0, emails.length)];

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
    check(resLogin, { "invalid login caught": (res) => res.status === 401 }) || fail("no 401 on invalid login");

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
    "login succeeded": (res) => res.status === 200 && typeof res.json().access_token !== "undefined",
  }) || fail("failed to log in");

  let params = { headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
      "Authorization": "Bearer " + resLogin.json().access_token
    }}, makeGet = function(path) {
    return { method: "GET",  url: baseURL + path,  params: params };
  };

  let homeScreenResponses = http.batch({
    "me": makeGet("api/me"),
    "challenges": makeGet("api/me/challenges"),
    "activities": makeGet("api/me/activities")
  });

  check(homeScreenResponses["me"], (res) => res.json().email === email) || fail("user profile email did not match");
  check(homeScreenResponses["challenges"], (res) => res.status === 200) || fail("challenges list GET failed");
  check(homeScreenResponses["activities"], (res) => res.status === 200) || fail("activities list GET failed");

  activityListResponseTime.add(homeScreenResponses["activities"].timings.duration);
  challengeListResponseTime.add(homeScreenResponses["challenges"].timings.duration);
  userProfileResponseTime.add(homeScreenResponses["me"].timings.duration);

  let pNextAction = Math.random();
  if (pNextAction > (1 - pAbandonAfterHomeLoad)) {
    return; // abandon here
  } else if (pNextAction > (1 - pAbandonAfterHomeLoad - pAddChallenge)) {
    sleep(getRandomInt(challengeThinkTime.min, challengeThinkTime.max)); // think time before creating challenge
    let startMonth = getRandomInt(5, 9), endMonth = startMonth + getRandomInt(1, 2),
      challengeRes = http.post(baseURL + "api/challenges", JSON.stringify({
        "name": "Test Challenge",
        "starts_at": "2018-0" + startMonth + "-01 00:00:00",
        "ends_at": "2018-" + (endMonth >= 10 ? endMonth : ("0" + endMonth)) + "-01 00:00:00",
        "duration": Math.random() > pIncludeChallengeDuration ? null :
          secondsToTime(getRandomInt(challengeMinHalfHours, challengeMaxHalfHours) * 1800),
        "distance_miles": Math.random() > pIncludeChallengeMileage ? null :
          getRandomInt(challengeMinTenMiles, challengeMaxTenMiles) * 10
    }), params);

    check(challengeRes, {"challenge was created": (res) => res.status === 201 && res.json().id}) ||
      fail("challenge create failed");

    let challengeListRes = http.get(baseURL + "api/me/challenges", params);
    check(challengeListRes, {"challenge is in user challenge list": (res) => {
        let json = res.json();
        for (let i = 0; i < json.length; i++) {
          if (json[i].id === challengeRes.json().id) {
            return true;
          }
        }
        return false;
      }}) || fail("challenge was not in user challenge list");

    return;
  }

  sleep(getRandomInt(activityThinkTime.min, activityThinkTime.max)); // more think time

  let activitySeconds = getRandomInt(activityMinSeconds, activityMaxSeconds),
    resActivity = http.post(baseURL + "api/me/activities", JSON.stringify({
      "name": "My Activity",
      "duration": secondsToTime(activitySeconds),
      "distance_miles": getDistance(activitySeconds),
      "started_at": ymd(
        getRandomInt(activityDateBounds.year.min, activityDateBounds.year.max),
        getRandomInt(activityDateBounds.month.min, activityDateBounds.month.max),
        getRandomInt(activityDateBounds.day.min, activityDateBounds.day.max)
      )
  }), params);

  check(resActivity, {"activity was created": (res) => res.status === 201 && res.json().id}) ||
    fail("activity create failed");

  if (pAddAnotherActivity > Math.random()) {
    sleep(getRandomInt(secondActivityThinkTime.min, secondActivityThinkTime.max));

    let activitySeconds = getRandomInt(activityMinSeconds, activityMaxSeconds),
      resActivity = http.post(baseURL + "api/me/activities", JSON.stringify({
        "name": "My Second Activity",
        "duration": secondsToTime(activitySeconds),
        "distance_miles": getDistance(activitySeconds),
        "started_at": ymd(
          getRandomInt(activityDateBounds.year.min, activityDateBounds.year.max),
          getRandomInt(activityDateBounds.month.min, activityDateBounds.month.max),
          getRandomInt(activityDateBounds.day.min, activityDateBounds.day.max)
        )
      }), params);

    check(resActivity, {"activity was created": (res) => res.status === 201 && res.json().id}) ||
    fail("activity create failed");
  }
}
