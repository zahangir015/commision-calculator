<?php

namespace App\Controller;

use App\Form\CommissionCalculateType;
use App\Manager\FileManager;
use App\Manager\TransactionManager;
use App\Service\ExchangeRateConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalculationController extends AbstractController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/', name: 'calculation')]
    public function index(Request $request, ExchangeRateConverter $converter): Response
    {
        $form = $this->createForm(CommissionCalculateType::class);
        $form->handleRequest($request);
        $response = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $response = TransactionManager::calculateCommission($file, $converter);
        }

        return $this->render('calculation/index.html.twig', [
            'form' => $form->createView(),
            'commissionList' => $response
        ]);
    }
}
