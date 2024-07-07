<?php

namespace Drupal\custom_rest_validation\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Custom exception handler for class.
 */
class CustomExceptionSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // Handle the exceptions we want for JSON before
    // core subscribers do this with priority -70/-75.
    return -69;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['json'];
  }

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($event->getThrowable() instanceof UnprocessableEntityHttpException) {
      $request = $event->getRequest();
      $route = $request->get('_route');
      // Check if the request is for user registration.
      if ($route == 'rest.user_registration.POST') {
        $requestData = json_decode($request->getContent(), TRUE);
        // Check if the request data contains the email field.
        if (isset($requestData['mail'])) {
          // Get original msg.
          $originalMessage = $exception->getMessage();
          if (strpos($originalMessage, 'mail') !== FALSE && strpos($originalMessage, 'is already taken') !== FALSE) {
            // Parse the original message to extract the specific error details.
            $parsedMessage = $this->parseErrorMessage($originalMessage);
            // Customize the validation error messages.
            $newException = new UnprocessableEntityHttpException($parsedMessage);
            // Throw exception.
            $event->setThrowable($newException);
          }
        }
      }
    }
  }

  /**
   * Returns user friendly error msg.
   */
  private function parseErrorMessage($errorMessage) {
    // Parse the error message to extract specific details.
    // Example implementation: extracting the email address.
    preg_match('/mail: email address ([^\s]+) is already taken\./', $errorMessage, $matches);
    $email = $matches[1] ?? 'unknown';

    // Customize the parsed error message as needed.
    $parsedMessage = "The email address $email is already taken.";

    return $parsedMessage;
  }

}
