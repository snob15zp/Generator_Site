<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Utils\HashUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Lumen\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Controller extends BaseController
{
    /**
     * @param $status
     * @param $message
     * @throws HttpException
     */
    protected function raiseError($status, $message)
    {
        abort($status, $message);
    }

    protected function respondWithMessage($message = null): JsonResponse
    {
        return response()->json([
            'status' => 'OK',
            'message' => $message
        ]);
    }

    protected function respondWithResource(JsonResource $resource): JsonResponse
    {
        return response()->json($resource);
    }

    protected function verifyUserPrivileges(User $user, string $privileges)
    {
        if ($user->cannot($privileges)) {
            $this->raiseError(403, "Resource not available");
        }
    }

    protected function getUserById($encodedId): User
    {
        $user = User::query()->whereKey(HashUtils::decode($encodedId))->first();
        if ($user == null) {
            throw new ApiException(ErrorStatusCodes::$USER_NOT_FOUND);
        }
        return $user;
    }
}
