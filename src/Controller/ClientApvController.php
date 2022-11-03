<?php

namespace App\Controller;

use App\Entity\ClientApv;
use App\Form\ClientApvType;
use App\Repository\ClientApvRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ClientApvController extends AbstractController
{
    #[Route('/', name: 'app_client_apv', methods: ['GET', 'POST'])]
    public function index(Request $request, ClientApvRepository $clientApvRepository): Response
    {
        $form = $this->createForm(ClientApvType::class, null);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form['file']->getData(); // recuperation du fichier
            $fileFolder = $this->getParameter('files_directory');  //choisir le dossier dans lequel le fichier téléchargé sera stocké qu'on a defini comme service dans services.yaml

            $filePathName = md5(uniqid()) . $file->getClientOriginalName(); // appliquer la fonction md5 pour générer un identifiant unique pour le fichier et le concatener avec l’extension de fichier 
            try {
                $file->move($fileFolder, $filePathName); // deplacement du fichier vers le serveur
            } catch (FileException $e) {
                dd($e); // si il y a une erreur
            }
            $spreadsheet = IOFactory::load($fileFolder . $filePathName); // lire à partir du fichier Excel 
            $row = $spreadsheet->getActiveSheet()->removeRow(1); // On doit supprimer la première ligne de fichier pour ne pas enregistrer les titres (entete)
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true); // on transforme en tableau les données du fichier

            // parcourrir en fin le tableau pour l'enrigistrement dans la base de données
            foreach ($sheetData as $row) {
                $clientApv = new ClientApv();
                $clientApv->setBusinessAccount($row['A']);
                $clientApv->setEventAccount($row['B']);
                $clientApv->setLastEventAccount($row['C']);
                $clientApv->setSerialNumber($row['D']);
                $clientApv->setCivility($row['E']);
                $clientApv->setCurrentOwnership($row['F']);
                $clientApv->setLastName($row['G']);
                $clientApv->setFirstName($row['H']);
                $clientApv->setStreet($row['I']);
                $clientApv->setAdditionalAddress($row['J']);
                $clientApv->setZipCode($row['K']);
                $clientApv->setCity($row['L']);
                $clientApv->setHomephone($row['M']);
                $clientApv->setMobilePhone($row['N']);
                $clientApv->setJobPhone($row['O']);
                $clientApv->setEmail($row['P']);
                $clientApv->setRegistrationDate(new \DateTime($row['Q']));
                $clientApv->setPurchaseDate(new \DateTime($row['R']));
                $clientApv->setLastEventDate(new \DateTime($row['S']));
                $clientApv->setBrand($row['T']);
                $clientApv->setModele($row['U']);
                $clientApv->setVersion($row['V']);
                $clientApv->setVin($row['W']);
                $clientApv->setRegistration($row['X']);
                $clientApv->setProspect($row['Y']);
                $clientApv->setKilometrage($row['Z']);
                $clientApv->setEnergy($row['AA']);
                $clientApv->setSeller($row['AB']);
                $clientApv->setSellerVo($row['AC']);
                $clientApv->setComment($row['AD']);
                $clientApv->setTypeVnVo($row['AE']);
                $clientApv->setFileNumber($row['AF']);
                $clientApv->setSalesAgent($row['AG']);
                $clientApv->setEventDate(new \DateTime($row['AH']));
                $clientApv->setEventOrigin($row['AI']);
                $clientApvRepository->save($clientApv, true);
            }
            // on ajoute un message flash pour indiquer que les données sont bien enregistrées
            $this->addFlash('success', 'Le fichier est enregistré avec succès dans la base de données');
        }
        return $this->render('client_apv/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
