<?php

namespace Challengr\Http\Controllers;

use Challengr\Challenge;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends Controller
{
    public function forCurrentUser(Request $request)
    {
        return [
            'created' => Challenge::whereUserId($userId = $request->user()->id)->orderByDesc('created_at')->get(),
            'joined' => Challenge::whereHas('users_joined', function(Builder $query) use ($userId) {
                return $query->where('id', $userId);
            })->orderByDesc('ends_at')
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
            'name' => 'required|string|max:255'
            // TODO
        ])->validate();

        $challenge = Challenge::create([
            'user_id' => $request->user()->id,
            // TODO
        ]);

        $challenge->users_joined()->attach($request->user()->id);

        return $challenge;
    }

    public function getAll(Request $request)
    {
        return Challenge::all();
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
