<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\RegistrationUserRequest;
use App\Exception\CustomBadRequestException;
use App\Form\RegistrationFormType;
use App\Service\RegistrationUserService;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag('user')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(Request $request, RegistrationUserService $registrationService): Response
    {
        $dto = new RegistrationUserRequest();
        $form = $this->createForm(RegistrationFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registrationService->register($dto);
            $this->addFlash('success', 'Account created.!');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @throws ExceptionInterface
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
    public function submit(
        Request $request,
        RegistrationUserService $registrationService
    ): JsonResponse {
        $errors = [];
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                RegistrationUserRequest::class,
                'json'
            );
        } catch (ExceptionInterface $exceptionSerializer) {
            $exceptionMessage = $exceptionSerializer->getMessage();
            $errors[] = [
                'message' => 'Wrong json: ' . $exceptionMessage,
                'field' => '',
            ];
            throw new CustomBadRequestException($errors);
        }

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
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
