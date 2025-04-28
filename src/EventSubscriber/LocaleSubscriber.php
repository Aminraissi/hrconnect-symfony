<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Psr\Log\LoggerInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $defaultLocale = 'en')
    {
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->query->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
            $this->logger->info('Locale set from query _locale param: '.$locale);
        } else {
            $locale = $request->getSession()->get('_locale', $this->defaultLocale);
            $this->logger->info('Locale set from session: '.$locale);
        }

        $request->setLocale($locale);
    }
}
