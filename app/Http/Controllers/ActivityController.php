<?php

namespace Challengr\Http\Controllers;

use Challengr\Activity;
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
            'name' => 'required|string|max:255'
            // TODO
        ])->validate();

        return Activity::create([
            'user_id' => $request->user()->id,
            // TODO
        ]);
    }

    public function get(int $id, Request $request)
    {
        try {
            return Activity::query()->where('user_id', '=', $request->user()->id)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
    }

    public function update(int $id, Request $request)
    {
        try {
            $activity = Activity::query()->where('user_id', '=', $request->user()->id)->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        // TODO add update validation

        return $activity;
    }

    public function delete(int $id, Request $request)
    {
        Activity::query()->where('user_id', '=', $request->user()->id)->whereKey($id)->delete();
        return new Response('', 205);
    }
}
