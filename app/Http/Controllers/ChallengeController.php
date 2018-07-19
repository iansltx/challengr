<?php

namespace Challengr\Http\Controllers;

use Challengr\Challenge;
use Challengr\Rules\Time;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends Controller
{
    public function forCurrentUser(Request $request)
    {
        return [
            'created' => Challenge::whereUserId($userId = $request->user()->id)->orderByDesc('created_at')->get(),
            'joined' => Challenge::whereHas('users_joined', function(Builder $builder) use ($userId) {
                return $builder->whereKey($userId);
            })->orderByDesc('ends_at')->get()
        ];
    }

    /**
     * @param Request $request
     * @return Challenge|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        Validator::make($data = $request->all(), [
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'distance_miles', 'numeric|min:0.001',
            'duration' => new Time(),
        ])->validate();

        $challenge = new Challenge();
        $challenge->user_id = $request->user()->id;

        $challenge->fill([
            'name' => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'duration' => $data['duration'] ?? null,
            'distance_miles' => $data['distance_miles'] ?? null
        ])->save();

        $challenge->users_joined()->attach($request->user()->id);

        return $challenge;
    }

    public function getAll(Request $request)
    {
        return Challenge::query()->orderByDesc('starts_at')->limit($request->input('limit', 50))->get();
    }

    public function get(int $id, Request $request)
    {
        try {
            return Challenge::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }
    }

    public function join(int $id, Request $request)
    {
        try {
            /** @var Challenge $challenge */
            $challenge = Challenge::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $challenge->users_joined()->attach($request->user()->id);

        return $challenge;
    }

    public function leave(int $id, Request $request)
    {
        try {
            /** @var Challenge $challenge */
            $challenge = Challenge::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $challenge->users_joined()->detach($request->user()->id);

        return $challenge;
    }
}
