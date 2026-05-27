<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

trait ApiResponseTrait
{
    private function success(array $data = [], string $message = 'OK', int $status = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return $this->json($payload, $status);
    }

    private function requestData(string $content): ?array
    {
        if (trim($content) === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }
}
