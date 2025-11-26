<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response as symfonyResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::latest()->paginate();
        $users = UserResource::collection($users);
        return Inertia::render("Users", compact("users"));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());
        $user->assignRole('user');

        logActivity(request: $request, description: "User created a new user", showable: true);
        return apiResponse(message: "User added successfully", data: UserResource::make($user), statusCode: symfonyResponse::HTTP_CREATED);
    }

    public function show(string $id): Response
    {
        return Inertia::render("users/show", [
            "user" => User::findOrFail($id)
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->fill($request->validated());
        $user->save();

        logActivity(request: $request, description: "User updated a user", showable: true);
        return apiResponse(message: "User updated successfully", data: UserResource::make($user));
    }

    public function destroy(Request $request, User $user)
    {
        if (!$user) {
            return apiResponse(errors: ["id" => ["User not found"]], statusCode: symfonyResponse::HTTP_NOT_FOUND);
        }
        $user->delete();

        logActivity(request: $request, description: "User deleted a user", showable: true);
        return apiResponse(message: "User deleted successfully", statusCode: symfonyResponse::HTTP_NO_CONTENT);
    }
}
