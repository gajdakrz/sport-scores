<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Country;
use App\Entity\User;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/country')]
final class CountryController extends AbstractController
{
    #[Route('/', name: 'country_index', methods: ['GET'])]
    public function index(CountryRepository $countryRepository): Response
    {
        return $this->render('country/index.html.twig', [
            'countries' => $countryRepository->findIsActiveSortedBy(),
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

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($country);
            $em->flush();
            return $this->redirectToRoute('country_index');
        }

        return $this->render('country/_modal.html.twig', [
            'form' => $form->createView(),
            'country' => $country,
        ]);
    }

    #[Route('/{id}/edit', name: 'country_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Country $country, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $now = new DateTimeImmutable();
        $country->setModifiedBy($user);
        $country->setModifiedAt($now);
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('country_index');
        }

        return $this->render('country/_modal.html.twig', [
            'form' => $form->createView(),
            'country' => $country,
        ]);
    }

    #[Route('/{id}', name: 'country_delete', methods: ['POST'])]
    public function delete(Request $request, Country $country, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $country->getId(), (string) $request->request->get('_token'))) {
            $now = new DateTimeImmutable();
            $country->setModifiedBy($user);
            $country->setModifiedAt($now);
            $country->setIsActive(false);
            $em->flush();
        }
        return $this->redirectToRoute('country_index');
    }
}
