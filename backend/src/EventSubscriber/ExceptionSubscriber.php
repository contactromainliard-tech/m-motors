<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Souscripteur d événements d exception du kernel.
 *
 * Intercepte les exceptions non gérées et les log avec leur contexte complet
 * (URL, méthode HTTP, utilisateur connecté). Les erreurs 5xx sont loggées
 * en CRITICAL, ce qui déclenche l envoi d une alerte email en production
 * via le handler Monolog native_mailer configuré dans monolog.yaml.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    /**
     * Logue l exception avec son contexte HTTP.
     *
     * - Erreurs HTTP 4xx (ex: 404, 401) : niveau WARNING (pas d alerte)
     * - Erreurs HTTP 5xx ou exceptions non HTTP : niveau CRITICAL (alerte email en prod)
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request   = $event->getRequest();

        // Extraction de l identité de l utilisateur connecté (si disponible)
        $userIdentifier = 'anonyme';
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser()) {
            $userIdentifier = method_exists($token->getUser(), 'getUserIdentifier')
                ? $token->getUser()->getUserIdentifier()
                : (string) $token->getUser();
        }

        $context = [
            'url'        => $request->getPathInfo(),
            'method'     => $request->getMethod(),
            'ip'         => $request->getClientIp(),
            'user'       => $userIdentifier,
            'exception'  => $exception->getMessage(),
            'class'      => get_class($exception),
        ];

        // Les exceptions HTTP 4xx sont attendues (404 route inconnue, 401 non authentifié)
        // On les log en WARNING pour traçabilité sans déclencher d alerte
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();

            if ($statusCode < 500) {
                $this->logger->warning(
                    sprintf('[%d] %s %s', $statusCode, $context['method'], $context['url']),
                    $context
                );
                return;
            }
        }

        // Erreurs 5xx et exceptions non HTTP → CRITICAL → alerte email en production
        $this->logger->critical(
            sprintf(
                'Erreur critique : %s sur %s %s (utilisateur : %s)',
                get_class($exception),
                $context['method'],
                $context['url'],
                $context['user']
            ),
            $context
        );
    }
}
