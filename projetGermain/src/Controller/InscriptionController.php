<?php

namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class InscriptionController extends AbstractFOSRestController
{

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * Constructeur
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(UserRepository $userRepository,
                                UserPasswordEncoderInterface $encoder,
                                EntityManagerInterface $entityManager)
    {
        // On récupère l'ensemble des users et on les stocke dans les attributs
        $this->userRepository = $userRepository;
        // On récupère le cryptage de mdps
        $this->encoder = $encoder;
        // On récupère un manager d'entités
        $this->entityManager = $entityManager;
    }

    /**
     * @Rest\Post("/inscription", name="inscription")
     * @param ParamFetcher $paramFetcher
     * @RequestParam(name="username", description="Nom de l'utilisateur", nullable=false)
     * @RequestParam(name="password", description="Mot de passe de l'utilisateur", nullable=false)
     * @return View
     */
    public function inscription(ParamFetcher $paramFetcher)
    {
        // AJOUTER PLUS DE CONTROLES D'ENTREES POUR NE PAS AAVOIR D'ERREURS, LES !is_null ne sont pas suffisant

        $login = $paramFetcher->get('username');
        $mdp = $paramFetcher->get('password');

        // On vérifie si un user avec ce login existe déjà dans la bdd
        $utilisateurExistant = $this->userRepository->findOneBy([
            "login" => $login
        ]);

        // Si un utilisateur avec ce login existe
        if(!is_null($utilisateurExistant))
        {
            return $this->view([
                'Erreur' => 'Un utilisateur utilisant ce login existe déjà (Veuillez modifier le champs username).'
            ], Response::HTTP_CONFLICT);
        } else {

            if(!is_null($login) && !is_null($mdp))
            {
                // Les deux champs sont différents de nulls
                $nouveauUtilisateur = new User();
                $nouveauUtilisateur->setLogin($login);
                $nouveauUtilisateur->setPassword(
                    $this->encoder->encodePassword($nouveauUtilisateur, $mdp)
                );
                // Par défaut tous des utilisateurs simples
                $nouveauUtilisateur->setRoles(['ROLE_USER']);

                // On utilise persist sur la nouvelle instance d'entité créée
                $this->entityManager->persist($nouveauUtilisateur);
                $this->entityManager->flush();

                return $this->view([
                    "Message" => "Ajout d'utilisateur réussi.",
                    "Données du nouvel utilisateur" => $nouveauUtilisateur,
                    "Conseil" => "Veuillez à présent vous connecter pour obtenir votre token (/connexion).",
                    "Instructions" => "Avec votre client REST, copiez ensuite votre token dans un HEADER 'Authorization' = 'BEARER votreToken' pour conserver votre état de connexion."
                ], Response::HTTP_CREATED)->setContext((new Context())->setGroups(['infosPubliques']));
            // On affiche seulement le groupe de données publiques des utilisateurs (id et login)
            } else {
                // Les champs sont vides
                return $this->view([
                    'Erreur' => 'Les champs username et password ne peuvent pas être nuls.'
                ], Response::HTTP_NOT_ACCEPTABLE);
            }
        }
    }
}
