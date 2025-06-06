<?php
namespace Drupal\realtimepagecounter\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;

class NodeViewCounterSubscriber implements EventSubscriberInterface {

  protected $database;
  protected $currentUser;

  public function __construct(Connection $database, AccountInterface $current_user) {
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $route_match = \Drupal::routeMatch();

    if ($node = $route_match->getParameter('node')) {
      if ($node instanceof Node && $node->access('view')) {
        $nid = $node->id();
        $this->database->merge('node_counter')
          ->key(['nid' => $nid])
          ->fields([
            'totalcount' => 1,
            'daycount' => 1,
            'timestamp' => \Drupal::time()->getRequestTime(),
          ])
          ->expression('totalcount', 'totalcount + 1')
          ->expression('daycount', 'daycount + 1')
          ->execute();
      }
    }
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 35],
    ];
  }
}
