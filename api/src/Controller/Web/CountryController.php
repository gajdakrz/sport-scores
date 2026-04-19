<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Controller\BaseController;
use App\Entity\Country;
use App\Entity\User;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/countries')]
final class CountryController extends BaseController
{
    #[Route('', name: 'country_index', methods: ['GET'])]
    public function index(CountryRepository $countryRepository): Response
    {
        return $this->render('country/index.html.twig', [
            'countries' => $countryRepository->findActiveSortedBy(),
        ]);
    }

    #[Route('/new', name: 'country_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $country = new Country();
        $country->setCreatedBy($user);
        $country->setModifiedBy($user);

        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('country/_modal.html.twig', [
                'form' => $form->createView(),
                'country' => $country,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->persist($country);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Country created.'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/edit', name: 'country_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Country $country, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $country->setModifiedBy($user);
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('country/_modal.html.twig', [
                'form' => $form->createView(),
                'country' => $country,
            ]);
        }

        if (!$form->isValid()) {
            $this->throwFormErrors($form);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Country updated.']);
    }

    #[Route('/{id}', name: 'country_delete', methods: ['POST'])]
    public function delete(Request $request, Country $country, EntityManagerInterface $em): Response
    {
        if (
            $response = $this->validateCsrfToken(
                'delete' . $country->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            return $response;
        }

        /** @var User $user */
        $user = $this->getUser();
        $country->setModifiedBy($user);
        $country->setIsActive(false);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Country deleted.']);
    }
}
