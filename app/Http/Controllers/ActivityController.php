<?php

namespace Challengr\Http\Controllers;

use Challengr\Activity;
use Challengr\Rules\Time;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    public function forCurrentUser(Request $request)
    {
        return Activity::whereUserId($request->user()->id)->orderByDesc('started_at')->get();
    }

    /**
     * @param Request $request
     * @return Activity|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        Validator::make($data = $request->all(), [
            'name' => 'required|string|max:255',
            'distance_miles' => 'required|numeric|min:0.001',
            'duration' => ['required', new Time()],
            'started_at' => 'required|date'
        ])->validate();

        $activity = new Activity();
        $activity->user_id = $request->user()->id;

        $activity->fill([
            'name' => $data['name'],
            'distance_miles' => $data['distance_miles'],
            'duration' => $data['duration'],
            'started_at' => $data['started_at']
        ])->save();

        return $activity;
    }

    public function get(int $id, Request $request)
    {
        try {
            return Activity::query()->where('user_id', '=', $request->user()->id)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
    }

    /**
     * @param int $id
     * @param Request $request
     * @return Activity|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(int $id, Request $request)
    {
        try {
            $activity = Activity::query()->where('user_id', '=', $request->user()->id)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        Validator::make($data = $request->all(), [
            'name' => 'string|max:255',
            'distance_miles' => 'numeric|min:0.001',
            'duration' => 'time',
            'started_at' => 'date'
        ])->validate();

        $activity->fill($request->all())->save();

        return $activity;
    }

    public function delete(int $id, Request $request)
    {
        Activity::query()->where('user_id', '=', $request->user()->id)->whereKey($id)->delete();
        return new Response('', 205);
    }
}
