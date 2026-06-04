<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Tests unitaires de l ExceptionSubscriber.
 * Verifie que le bon niveau de log est emis selon le type d exception,
 * et que le contexte HTTP (URL, methode, utilisateur) est bien inclus.
 */
class ExceptionSubscriberTest extends TestCase
{
    private LoggerInterface $logger;
    private TokenStorageInterface $tokenStorage;
    private ExceptionSubscriber $subscriber;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->logger       = $this->createMock(LoggerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->subscriber   = new ExceptionSubscriber($this->logger, $this->tokenStorage);
        $this->kernel       = $this->createMock(HttpKernelInterface::class);

        // Par defaut : pas de token (utilisateur anonyme)
        $this->tokenStorage->method('getToken')->willReturn(null);
    }

    public function testIsSubscribedToKernelException(): void
    {
        $events = ExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertEquals('onKernelException', $events[KernelEvents::EXCEPTION][0]);
    }

    public function testHttp404LogsWarningNotCritical(): void
    {
        $event = $this->makeEvent(
            new NotFoundHttpException('Route introuvable'),
            '/api/vehicles/999',
            'GET'
        );

        $this->logger->expects($this->once())->method('warning')
            ->with($this->stringContains('404'));
        $this->logger->expects($this->never())->method('critical');

        $this->subscriber->onKernelException($event);
    }

    public function testHttp401LogsWarningNotCritical(): void
    {
        $event = $this->makeEvent(
            new HttpException(401, 'Non authentifie'),
            '/api/dossiers',
            'GET'
        );

        $this->logger->expects($this->once())->method('warning');
        $this->logger->expects($this->never())->method('critical');

        $this->subscriber->onKernelException($event);
    }

    public function testHttp403LogsWarningNotCritical(): void
    {
        $event = $this->makeEvent(
            new HttpException(403, 'Acces refuse'),
            '/api/documents/dossier/1',
            'GET'
        );

        $this->logger->expects($this->once())->method('warning');
        $this->logger->expects($this->never())->method('critical');

        $this->subscriber->onKernelException($event);
    }

    public function testHttp500LogsCritical(): void
    {
        $event = $this->makeEvent(
            new HttpException(500, 'Erreur serveur'),
            '/api/vehicles',
            'POST'
        );

        $this->logger->expects($this->once())->method('critical')
            ->with(
                $this->stringContains('Erreur critique'),
                $this->arrayHasKey('url')
            );
        $this->logger->expects($this->never())->method('warning');

        $this->subscriber->onKernelException($event);
    }

    public function testNonHttpExceptionLogsCritical(): void
    {
        $event = $this->makeEvent(
            new \RuntimeException('Erreur inattendue'),
            '/api/dossiers',
            'POST'
        );

        $this->logger->expects($this->once())->method('critical')
            ->with(
                $this->stringContains('Erreur critique'),
                $this->callback(function (array $ctx): bool {
                    return $ctx['url']    === '/api/dossiers'
                        && $ctx['method'] === 'POST'
                        && $ctx['user']   === 'anonyme'
                        && $ctx['class']  === \RuntimeException::class;
                })
            );

        $this->subscriber->onKernelException($event);
    }

    public function testContextContainsRequestInfo(): void
    {
        $event = $this->makeEvent(
            new \LogicException('Logic fail'),
            '/api/auth/profile',
            'PUT'
        );

        $this->logger->expects($this->once())->method('critical')
            ->with(
                $this->stringContains('anonyme'),
                $this->callback(function (array $ctx): bool {
                    return isset($ctx['url'], $ctx['method'], $ctx['ip'], $ctx['user'],
                                 $ctx['exception'], $ctx['class']);
                })
            );

        $this->subscriber->onKernelException($event);
    }

    public function testAuthenticatedUserIdentifierInContext(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('jean.dupont@email.fr');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->subscriber = new ExceptionSubscriber($this->logger, $this->tokenStorage);

        $event = $this->makeEvent(
            new \RuntimeException('Erreur avec user connecte'),
            '/api/vehicles',
            'DELETE'
        );

        $this->logger->expects($this->once())->method('critical')
            ->with(
                $this->stringContains('jean.dupont@email.fr'),
                $this->callback(fn(array $ctx): bool => $ctx['user'] === 'jean.dupont@email.fr')
            );

        $this->subscriber->onKernelException($event);
    }

    public function testTokenWithoutUserShowsAnonymous(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->subscriber = new ExceptionSubscriber($this->logger, $this->tokenStorage);

        $event = $this->makeEvent(
            new \RuntimeException('Erreur'),
            '/api/vehicles',
            'GET'
        );

        $this->logger->expects($this->once())->method('critical')
            ->with(
                $this->anything(),
                $this->callback(fn(array $ctx): bool => $ctx['user'] === 'anonyme')
            );

        $this->subscriber->onKernelException($event);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeEvent(\Throwable $exception, string $uri, string $method): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->kernel,
            Request::create($uri, $method),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
