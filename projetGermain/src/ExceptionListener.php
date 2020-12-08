<?php
namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        // Crée une réponse json
        $customResponse = new JsonResponse(['Message' => "Impossible.", 'Erreur' => $exception->getMessage() . "."], 403);

        // set it as response and it will be sent
        $event->setResponse($customResponse);

    }
}