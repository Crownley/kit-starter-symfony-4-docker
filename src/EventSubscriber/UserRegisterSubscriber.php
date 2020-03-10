<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use Swift_Message;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Security\TokenGenerator;

class UserRegisterSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        TokenGenerator $tokenGenerator,
        \Swift_Mailer $mailer

    )
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['userRegistered', EventPriorities::PRE_WRITE]
        ];
    }

    public function userRegistered(ViewEvent $event)
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$user instanceof User ||
            !in_array($method, [Request::METHOD_POST])) {
            return;
        }

        // It is an User, we need to hash password here
        $user->setPassword(
            $this->passwordEncoder->encodePassword($user, $user->getPassword())
        );

        // Create confirmation token
        $user->setConfirmationToken(
            $this->tokenGenerator->getRandomSecureToken()
        );

        // Send e-mail here...
        $message = (new Swift_Message('Hello From API PLATFORM!'))
            ->setFrom('harrison.mctester@gmail.com')
            ->setTo('harrison.mctester@gmail.com')
            ->setBody('Hello, how are you?');

        $this->mailer->send($message);
    }
}