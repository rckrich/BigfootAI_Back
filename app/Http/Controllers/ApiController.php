<?php

namespace App\Http\Controllers;

use App\Models\ThreadBundle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    /**
     * Log in user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function login(Request $request)
    {
        $validator = $this->isValidCredentials($request);
        if($validator -> fails()){
            return response()->json(['message' => $validator->errors()], 422);
        }

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'api.error.unauthorized'], 401);
        }

        $user = User::where('id', $request->user()->id)->first();

        $token = $user->createToken('auth_token')->plainTextToken;

        return Response([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user' => $user,
            'open_ia_key' => env('OPEN_IA_KEY')
        ], 200);
    }

    /**
     * Logout user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function logout(Request $request)
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json(array(), 204);
    }

    /**
     * Get Threads by user
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function getThreadsByUser($limit, $offset)
    {
        $total = ThreadBundle::whereRelation('user', 'user_id', Auth::user()->id)
            ->count();

        $results = ThreadBundle::whereRelation('user', 'user_id', Auth::user()->id)
            ->take($limit)
            ->skip($offset)
            ->get();

        return Response([
            'thread_bundles' => $results,
            'total' => $total,
            'next' => (intval($offset) + intval($limit)) < $total ? '/v1/threads/'.$limit.'/'.(intval($offset) + intval($limit)) : null,
            'previous' => (intval($offset) - intval($limit)) >= 0 ? '/v1/threads/'.$limit.'/'.(intval($offset) - intval($limit)) : null,
        ], 200);
    }

    /**
     * Create thread
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function createThread(Request $request)
    {
        $result = ThreadBundle::where('thread_id', $request->thread_id)->count();
        if($result > 0) {
            return response()->json(['message' => 'Ya existe este thread'], 403);
        }

        $thread =  new ThreadBundle();
        $thread->thread_id = $request->thread_id;
        $thread->title = $request->title;
        $thread->last_message = $request->last_message;
        $thread->user_id = Auth::user()->id;
        $thread->save();

        return Response([
        ], 204);
    }

    /**
     * Update thread
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function updateThread(Request $request)
    {
        $thread = ThreadBundle::where('thread_id', $request->thread_id)->first();
        if($thread == null) {
            return response()->json(['message' => 'No existe este thread'], 403);
        }
        $thread->title = $request->title;
        $thread->last_message = $request->last_message;
        $thread->save();

        return Response([
        ], 204);
    }

    /**
     * Delete thread
     *
     * @return \Illuminate\Http\Client\Response
     */
    public function deleteThread(Request $request)
    {
        $thread = ThreadBundle::where('thread_id', $request->thread_id)->first();
        if($thread == null) {
            return response()->json(['message' => 'No existe este thread'], 403);
        }
        $thread->delete();

        return Response([
        ], 204);
    }

    private function isValidCredentials($request){
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);

        return $validator;
    }
}
