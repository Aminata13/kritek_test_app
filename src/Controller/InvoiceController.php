<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use App\Form\InvoiceType;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvoiceController extends AbstractController
{
    #[Route('/', name: 'app_invoice')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();

        $invoice = new Invoice();
        $line = new InvoiceLine();
        $vat = 0.18;
        $invoice->addInvoiceLine($line);

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $last_id_record =  $entityManager->getRepository(Invoice::class)->findOneBy([], ['id' => 'desc']);
            $invoice->setNumber($last_id_record ?  (int)$last_id_record->getId() + 1 : 1);
            $invoice->setDate(new DateTime());

            $entityManager->persist($invoice);

            $lines = $invoice->getInvoiceLines();
            foreach($lines as $l ){
                $totalAmount = $l->getQuantity() * $l->getAmount();
                $vatAmount = $totalAmount * $vat;
                $total = $vatAmount + $totalAmount;
                $l->setTotal($total);
                $l->setVatAmount($vatAmount);
                $l->setInvoice($invoice);
                $entityManager->persist($l);
            }

            $entityManager->flush();

            
            $invoice = new Invoice();
            $line = new InvoiceLine();
            $invoice->addInvoiceLine($line);
            $form = $this->createForm(InvoiceType::class, $invoice);
        }

        return $this->render('invoice/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
