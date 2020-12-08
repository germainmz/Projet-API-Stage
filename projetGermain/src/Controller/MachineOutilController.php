<?php

namespace App\Controller;

use App\Entity\MachineOutil;
use App\Repository\MachineOutilRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;

class MachineOutilController extends AbstractFOSRestController
{

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var MachineOutilRepository
     */
    private $machineOutilRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Constructeur
     * @param UserRepository $userRepository
     * @param MachineOutilRepository $machineOutilRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(UserRepository $userRepository,
                                MachineOutilRepository $machineOutilRepository,
                                EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->machineOutilRepository = $machineOutilRepository;
    }

    /**
     * @Rest\Post("/creer", name="creerMachine")
     * @RequestParam(name="nom", description="Nom de la nouvelle machine outil", nullable=false)
     * @RequestParam(name="description", description="Description de la nouvelle machine outil", nullable=false)
     * @RequestParam(name="login", description="Username de l'utilisateur", nullable=true)
     * @param ParamFetcher $paramFetcher
     * @return View
     */
    public function createMachine(ParamFetcher $paramFetcher)
    {
        // On récupère les données de la requête avec le paramFetcher
        $machine = new MachineOutil();
        $nom = $paramFetcher->get('nom');
        $description = $paramFetcher->get('description');
        $login = $paramFetcher->get('login');

        // Si login est entré en paramètre
        if (!is_null($login)) {

            // Si l'utilisateur n'existe pas dans la bdd
            if(!is_null($this->userRepository->findOneBy(['login' => $login])))
            {
                $utilisateurConcerne = $this->userRepository->findOneBy(['login' => $login]);
            } else {
                // Si l'utilisateur n'existe pas dans la bdd
                $utilisateurConcerne = $this->getUser();
            }
        } else {
            // par défaut l'utilisateur est celui connecté
            $utilisateurConcerne = $this->getUser();
        }

        // On vérifie que le nom ne soit pas nul
        if(!is_null($nom)){
            $machine->setNom($nom);
        } else {
            // Sinon, on retourne un message d'erreur
            return $this->view(["Message" =>
                "Création impossible. Le nom de la machine outil ne peut pas être nul"], Response::HTTP_BAD_REQUEST);
        }

        // On vérifie que la description ne soit pas nulle
        if(!is_null($description)){
            $machine->setDescription($description);
        } else {
            // Sinon, on retourne un message d'erreur
            return $this->view(["Message" =>
                "Création impossible. La description de la machine outil ne peut pas être nulle"], Response::HTTP_BAD_REQUEST);
        }

        // On attribut l'utilisateur effectuant l'action à la machine outil
        $machine->setUtilisateur($utilisateurConcerne);

        // On utilise persist sur la nouvelle instance d'entité créée
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        // Message de succès
        return $this->view([
            "Message" => "Ajout de la machine outil réussi. Elle est associée à l'utilisateur : " . $utilisateurConcerne->getLogin() . ".",
            "Données de la nouvelle machine outil" => $machine,
            "Conseil" => "Vous pouvez à présent consulter votre liste pour voir vos modifications (/liste)."
        ], Response::HTTP_CREATED)->setContext((new Context())->setGroups(['infosMachines']));
        // On récupère seulement le groupe de données publiques des machines (id, nom et description)
    }

    /**
     * @Rest\Post("/voir/{id}", name="voirMachine", requirements={"id" = "\d+"})
     * @param $id
     * @return View
     */
    public function getMachine($id)
    {
        $idRequete = $id;

        $machine = $this->machineOutilRepository->findOneBy(["id" => $idRequete]);

        // La machine doit exister dans la base de données
        if (!is_null($machine)) {

            // On vérifie que la machine outil appartienne à l'utilisateur avant de l'afficher
            if ($machine->getUtilisateur()->getId() == $this->getUser()->getId()) {

                $resultat = ["Message" => "Voici les informations de la machine outil avec un ID : " . $machine->getId() . ".",
                    "Informations" => $machine
                    ];

                // On établit un contexte pour contrôler les données machines (et utilisateurs) affichées
                return $this->view($resultat, Response::HTTP_OK)->setContext((new Context())->setGroups(['infosMachines']));

            } else {

                // L'utilisateur n'a pas le droit à cette machine
                $resultat = [
                    "Erreur" => "Cette machine outil n'est pas associée à cet utilisateur.",
                    "Conseil" => "Voir /liste pour accéder à la liste des machines outils de cet utilisateur."
                ];
                return $this->view($resultat, Response::HTTP_UNAUTHORIZED);
            }
        } else {
            // La machine n'existe pas dans la base de données
            $resultat = [
                "Erreur" => "Cette machine n'existe pas.",
                "Conseil" => "Voir /liste pour accéder à la liste des machines outils de cet utilisateur."
            ];
            return $this->view($resultat, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Post("/modifier/{id}", name="modifierMachine")
     * @param ParamFetcher $paramFetcher
     * @RequestParam(name="nom", description="Nouveau nom de la machine outil", nullable=true)
     * @RequestParam(name="description", description="Nouvelle description de la nouvelle machine outil", nullable=true)
     * @param $id
     * @return View
     */
    public function updateMachine(ParamFetcher $paramFetcher, $id)
    {
        // On récupère les données de la requête avec le paramFetcher
        $machine = $this->machineOutilRepository->findOneBy(["id" => $id]);

        // La machine doit exister dans la base de données avant de commencer les traitements
        if (!is_null($machine)) {

            // On récupère les données anciennes pour les afficher plus tard
            $oldmachine = new MachineOutil();
            $oldmachine->setNom($machine->getNom());
            $oldmachine->setDescription($machine->getDescription());
            $oldmachine->setUtilisateur($machine->getUtilisateur());

            $nom = $paramFetcher->get('nom');
            $description = $paramFetcher->get('description');

            // On vérifie que la machine outil appartienne à l'utilisateur avant de l'afficher
            if ($machine->getUtilisateur()->getId() == $this->getUser()->getId()) {

                // Le nom doit être différent de nul pour être modifié
                if(!is_null($nom) || $nom!="") {
                    $machine->setNom($nom);
                }

                // La description doit être différente de nulle pour être modifié
                if(!is_null($description) || $description!="") {
                    $machine->setDescription($description);
                }

                // On utilise persist sur pour modifier l'instance d'entité
                $this->entityManager->persist($machine);
                $this->entityManager->flush();

                $resultat = ["Message" => "Voici les modifications de la machine outil à l'id " . $machine->getId(),
                    "Anciennes informations" => $oldmachine,
                    "Nouvelles informations" => $machine
                ];
                // On établit un contexte pour contrôler les données machines (et utilisateurs) affichées
                return $this->view($resultat, Response::HTTP_OK)->setContext((new Context())->setGroups(['infosMachines']));

            } else {
                // L'utilisateur n'a pas le droit à cette machine
                $resultat = [
                    "Erreur" => "Cette machine outil n'est pas associée à cet utilisateur.",
                    "Conseil" => "Voir /liste pour accéder à la liste des machines outils de cet utilisateur."
                ];
                return $this->view($resultat, Response::HTTP_UNAUTHORIZED);
            }
        } else {
            // La machine n'existe pas
            $resultat = ["Erreur" => "Cette machine n'existe pas.",
                "Conseil" => "Vérifier les id des machines sur la liste (/liste)."];
            return $this->view($resultat, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Post("/supprimer/{id}", name="supprimerMachine", requirements={"id" = "\d+"})
     * @param $id
     * @return View
     */
    public function deleteMachine($id)
    {
        // On récupère l'id de la route
        $idRequete = $id;

        // On cherche une machine dans la bdd
        $machine = $this->machineOutilRepository->findOneBy(["id" => $idRequete]);

        // Si la machine est bien existante dans la bdd
        if (!is_null($machine)) {
            // On vérifie que la machine outil appartienne à l'utilisateur avant de l'afficher
            if ($machine->getUtilisateur()->getId() == $this->getUser()->getId()) {
                $resultat = [
                    "Message" => "Voici les informations de la machine outil supprimée " . $machine->getNom() . ".",
                    "Informations" => $machine
                ];

                // On supprime la machine de la base de données
                $this->entityManager->remove($machine);
                $this->entityManager->flush();

                // On établit un contexte pour contrôler les données machines (et utilisateurs) affichées
                return $this->view($resultat, Response::HTTP_OK)->setContext((new Context())->setGroups(['infosMachines']));

            } else {
                // L'utilisateur n'a pas le droit d'accéder à cette machine
                $resultat = [
                    "Erreur" => "Cette machine outil n'est pas associée à cet utilisateur et ne peux pas être supprimée.",
                    "Conseil" => "Voir /liste pour accéder à la liste des machines outils de cet utilisateur."
                ];
                return $this->view($resultat, Response::HTTP_UNAUTHORIZED);
            }
        } else {
            // La machine avec cette id n'existe pas dans la bdd
            $resultat = [
                "Erreur" => "Cette machine n'existe pas.",
                "Conseil" => "Créez une machine outil avec /creer et à l'aide du client REST, et un body de type {'username': 'données', 'password': 'données'} ou {'username': 'données', 'password': 'données', 'idUsername': 'données'}."
            ];
            return $this->view($resultat, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Post("/liste", name="listeMachines")
     * @return View
     */
    public function getListe()
    {
        $idUtilisateur = $this->getUser()->getId();

        // On prend l'ensemble des machines pour l'utilisateur connecté
        $lesMachines = $this->machineOutilRepository->findBy([
            "utilisateur" => $idUtilisateur
        ]);

        // On affiche les messages
        $resultat = ["Message" =>
            "On cherche les machines outils associées à l'utilisateur : ". $this->getUser()->getUsername() . "."];

        if (count($lesMachines) > 0) {

            $resultat += ["Machines Outils" => $lesMachines];
            // On établit un contexte pour contrôler les données machines (et utilisateurs) affichées
            return $this->view($resultat, Response::HTTP_OK)->setContext((new Context())->setGroups(['infosMachines']));

        } else {
            $resultat += ["Remarque" => "Aucune machine outil n'est associée à cet utilisateur."];
            return $this->view($resultat, Response::HTTP_OK);
        }
    }
}
