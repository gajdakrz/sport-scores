<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\BaseController;
use App\Dto\Request\RegistrationUserRequest;
use App\Exception\CustomBadRequestException;
use App\Service\RegistrationUserService;
use Doctrine\DBAL\Exception;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag('user')]
class UserController extends BaseController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @throws Exception
     */
    #[OA\RequestBody(
        description: "Create notification",
        required: true,
        content: new OA\JsonContent(
            ref: new Model(type: RegistrationUserRequest::class)
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'status',
                    type: 'string',
                    example: 'success',
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Account was created.',
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad Request',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'field', type: 'string', example:'plainPassword'),
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example:'Password must have min 6 characters.'
                            ),
                        ]
                    )
                ),
            ]
        )
    )]
    #[Route('/api/v1/user', name: 'app_register_submit', methods: 'POST')]
    public function submit(Request $request, RegistrationUserService $registrationService): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                RegistrationUserRequest::class,
                'json'
            );
        } catch (ExceptionInterface $e) {
            throw new CustomBadRequestException([['message' => 'Wrong json: ' . $e->getMessage(), 'field' => '']]);
        }

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'message' => (string) $violation->getMessage(),
                    'field' => $violation->getPropertyPath(),
                ];
            }
            throw new CustomBadRequestException($errors);
        }

        $registrationService->register($dto);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Account was created.',
        ], Response::HTTP_CREATED);
    }
}
