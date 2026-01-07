<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Mail\SubscriptionMailerInterface;
use App\Service\UserService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SubscriptionMailerInterface $mailer,
        private readonly UserService $userService,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $lastLoginAt = $user->getLastLoginAt();
        $now = new \DateTime();

        // Check if this is the first login ever
        $isFirstLogin = $lastLoginAt === null;

        // Check if this is the first login of the day
        $isFirstLoginOfDay = false;
        if ($lastLoginAt !== null) {
            $isFirstLoginOfDay = $lastLoginAt->format('Y-m-d') !== $now->format('Y-m-d');
        }

        // Collect login information
        $loginInfo = $this->collectLoginInfo($request);

        // Record the login
        $ip = $request?->getClientIp();
        $this->userService->recordLogin($user, $ip);

        // Send emails on first login OR first login of the day
        if ($isFirstLogin) {
            try {
                $this->mailer->sendWelcomeEmail($user);
            } catch (\Throwable $e) {
                // Silent fail
            }
        } elseif ($isFirstLoginOfDay) {
            try {
                $this->mailer->sendLoginNotification($user, $loginInfo);
            } catch (\Throwable $e) {
                // Silent fail
            }
        }
    }

    private function collectLoginInfo(?\Symfony\Component\HttpFoundation\Request $request): array
    {
        if ($request === null) {
            return [
                'ip' => 'Inconnu',
                'userAgent' => 'Inconnu',
                'os' => 'Inconnu',
                'browser' => 'Inconnu',
                'device' => 'Inconnu',
                'datetime' => new \DateTime(),
            ];
        }

        $userAgent = $request->headers->get('User-Agent', 'Inconnu');
        $ip = $request->getClientIp() ?? 'Inconnu';

        return [
            'ip' => $ip,
            'userAgent' => $userAgent,
            'os' => $this->parseOS($userAgent),
            'browser' => $this->parseBrowser($userAgent),
            'device' => $this->parseDevice($userAgent),
            'datetime' => new \DateTime(),
        ];
    }

    private function parseOS(string $userAgent): string
    {
        $osPatterns = [
            '/windows nt 10/i' => 'Windows 10/11',
            '/windows nt 6\.3/i' => 'Windows 8.1',
            '/windows nt 6\.2/i' => 'Windows 8',
            '/windows nt 6\.1/i' => 'Windows 7',
            '/windows/i' => 'Windows',
            '/macintosh|mac os x/i' => 'macOS',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iOS (iPhone)',
            '/ipad/i' => 'iOS (iPad)',
            '/ipod/i' => 'iOS (iPod)',
            '/android/i' => 'Android',
            '/webos/i' => 'WebOS',
        ];

        foreach ($osPatterns as $pattern => $os) {
            if (preg_match($pattern, $userAgent)) {
                return $os;
            }
        }

        return 'Inconnu';
    }

    private function parseBrowser(string $userAgent): string
    {
        $browserPatterns = [
            '/edge|edg/i' => 'Microsoft Edge',
            '/opr|opera/i' => 'Opera',
            '/chrome|crios|crmo/i' => 'Google Chrome',
            '/firefox|fxios/i' => 'Mozilla Firefox',
            '/safari/i' => 'Safari',
            '/msie|trident/i' => 'Internet Explorer',
            '/samsung/i' => 'Samsung Internet',
        ];

        // Check Edge first (contains Chrome in UA)
        if (preg_match('/edg/i', $userAgent)) {
            return 'Microsoft Edge';
        }

        // Check Opera (contains Chrome in UA)
        if (preg_match('/opr|opera/i', $userAgent)) {
            return 'Opera';
        }

        // Check Chrome
        if (preg_match('/chrome|crios/i', $userAgent) && !preg_match('/edg|opr/i', $userAgent)) {
            return 'Google Chrome';
        }

        // Check Safari (contains Safari but not Chrome)
        if (preg_match('/safari/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
            return 'Safari';
        }

        foreach ($browserPatterns as $pattern => $browser) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Inconnu';
    }

    private function parseDevice(string $userAgent): string
    {
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'Mobile';
        }

        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'Tablette';
        }

        return 'Ordinateur';
    }
}
