API REST LinkPart — Evaluation stage — Germain MANTEZOLO
————————————————————————————————————————————————————————




—————————————————————————————
"Installation/Déploiement" :
—————————————————————————————

--> L'intégration de l'API nécessite un serveur MySQL en 127:0:0:1:3606. La base de données est nommée stageGermain et possède deux tables

--> Les requêtes nécessaires à la création de la base de données sont retrouvables dans le fichier bddProjetGermain.sql

--> L'API est RESTful. Elle fonctionne facilement avec un cilent REST (Postman, par exemple) navigable par le biais de requêtes.

--> Exécuter la commande "bin/console server:start" à la racine de API "/ProjetGermain" permet d'ouvrir un serveur en 127.0.0.1:8000

--> Exécuter la commande "bin/console server:stop" parmet de stopper le serveur
    ( composer require symfony/web-server-bundle --dev si les packages sont manquants)

--> L'API est développée en Symfony 4.4.17 et testée sur du php 7.4



——————————————
Utilisation :
——————————————

/!\ Remarque : l'ensemble des requêtes se fait par méthode POST pour priviligier la confidentialité



———————————————————
||||||| Connexion :
———————————————————

La connexion se fait par le biais de "/connexion".

L'utilisateur doit renseigner dans le body de sa requête un username et un paswword.

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/connexion
Avec un body en raw (format json) -> {"username":"Germain", "password":"Pass"}

La connexion donne un token en réponse.
Ce token permet la connexion en tant que "Germain" dans l'ensemble de l'application.
Le token doit être renseigné dans le header de la requête en tant que valeur de l'attribut "Authorization".

Par exemple :
"Authorization" : "BEARER [insérer token ici]"

Remarque : Les mots de passes des utilisateurs dans la bdd sont "pass"

La connexion utilise jwt_athentication. Cependant, il reste à configurer le refresh des tokens jwt.
Pour des raisons de tests, on a choisi de créer des tokens avec une durée de vie de 1 mois.



—————————————————————
||||||| Inscription :
—————————————————————

L'inscription se fait par le biais de "/inscription".

L'utilisateur doit renseigner dans le body de sa requête un username et un paswword.

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/inscription
Avec un body en raw (format json) -> {"username":"Thomas", "password":"Pass"}

L'utilisateur est alors créé dans la base de données. Cependant, pour obtenir un token, l'utilisateur doit se rediriger vers /connexion.

Tout les utilisateurs se voient attribuer le même rôle.

Les utilisateurs possèdent aucune, une ou plusieurs machines outils. Il peuvent consulter, supprimer ou modifier leurs et uniquement leurs machines.

Pour des raisons de tests, les utilisateurs peuvent créer des machines et décider à qui celles-ci sont attribuées.



——————————————————————————————————————————————————————
||||||| Consultation de la liste des machines outils :
——————————————————————————————————————————————————————

/!\ ATTENTION : L'utilisateur doit être authentifié pour cette option (Token en Header)

La liste des machines outils propres à l'utilisatuer se fait par le biais de "/liste".

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/liste

L'utilisateur n'est pas contraint de renseigner des champs dans le body de sa requête.

La requête retourne une vue json. Si la liste est vide, un message s'affiche, si non, la liste est affichée.

Seules les machines outils appartenant à l'utilisateur connecté sont affichées.



——————————————————————————————————————
||||||| Création d'une machine outil :
——————————————————————————————————————

/!\ ATTENTION : L'utilisateur doit être authentifié pour cette option (Token en Header)

L'utilisateur peut créer une machine et l'attribuer à l'utilisateur de son choix

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/creer
Avec un body en raw (format json) -> {"nom":"Rouleau compresseur", "description":"Très lourd."}

L'utilisateur est contraint de renseigner les champs nom et description dans le body de sa requête.

La requête retourne une vue json. Si la liste est vide, un message s'affiche, si non, la liste est affichée.

Seules les machines outils appartenant à l'utilisateur connecté sont affichées.



——————————————————————————————————————————
||||||| Consultation d'une machine outil :
——————————————————————————————————————————

/!\ ATTENTION : L'utilisateur doit être authentifié pour cette option (Token en Header)

L'accès aux détails d'une machine outil se fait par le biais de "/voir/{idDeLaMachine}".

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/voir/14

L'utilisateur n'est pas contraint de renseigner des champs dans le body de sa requête.

Seul l'ID de la machine outil désiré doit être renseigné dans l'URL la requête.

Les IDs des machines outils sont visibles lors de l'affichage de la liste des machines.

Seules les machines appartenant à l'utilisateur connecté sont accessibles de cette manière.



——————————————————————————————————————————
||||||| Modification d'une machine outil :
——————————————————————————————————————————

/!\ ATTENTION : L'utilisateur doit être authentifié pour cette option (Token en Header)

L'accès aux détails d'une machine outil se fait par le biais de "/modifier/{idDeLaMachine}".
Il est préférable que l'utilisateur renseigne des champs dans le body de sa requête.

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/modifier/14
Avec un body en raw (format json) -> {"nom":"Tournevis", "description":"Très dangereux."}
// ou {"description":"Très dangereux."} ou encore {"nom":"Tournevis"}

L'utilisateur est contraint de renseigner les champs nom et/ou description dans le body de sa requête.

Seuls les champs noms et descriptions sont modifiables. Il ne peuvent pas être nuls.

Les champs sont modifiables selon les exigences de l'utilisateur.



—————————————————————————————————————————
||||||| Suppression d'une machine outil :
—————————————————————————————————————————

/!\ ATTENTION : L'utilisateur doit être authentifié pour cette option (Token en Header)

La suppression d'une machine outil se fait par le biais de "/supprimer/{idDeLaMachine}".

Par exemple :
Par méthode POST -> http://127.0.0.1:8000/supprimer/17

L'utilisateur n'est pas contraint de renseigner des champs dans le body de sa requête.

Seules les machines appartenant à l'utilisateur connecté sont supprimables de cette manière.

