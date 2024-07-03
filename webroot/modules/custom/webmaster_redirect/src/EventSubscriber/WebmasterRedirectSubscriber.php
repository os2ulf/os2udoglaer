<?php

namespace Drupal\webmaster_redirect\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;

class WebmasterRedirectSubscriber implements EventSubscriberInterface {

  protected $currentUser;
  protected $pathMatcher;
  protected $urlGenerator;

  public function __construct(AccountProxyInterface $current_user, PathMatcherInterface $path_matcher, UrlGeneratorInterface $url_generator) {
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->urlGenerator = $url_generator;
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

  public function onKernelRequest(RequestEvent $event) {
    if ($event->isMainRequest()) { // Use isMainRequest instead of isMasterRequest
      $request = $event->getRequest();
      $path = $request->getPathInfo();
      if ($this->currentUser->hasRole('webmaster') && $path === '/admin/content') {
        $url = $this->urlGenerator->generateFromRoute('view.content.page_2'); // Replace 'view.content_all.page' with the actual route name of your destination view page
        $event->setResponse(new RedirectResponse($url));
      }
    }
  }
}
