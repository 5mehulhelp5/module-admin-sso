<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSso\Plugin\Backend;

use DmLab\AdminSso\Model\Config;
use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;

/**
 * Disables native username/password admin login while SSO is enforced.
 *
 * Hooks {@see User::authenticate()}, which both the admin login form and the REST
 * admin-token endpoint go through; SSO logins never call it (they establish the
 * session via {@see \DmLab\AdminSso\Model\AdminSessionCreator}). With break-glass
 * enabled, only allowlisted usernames may still sign in with a password.
 */
class EnforceSso
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Reject the native login before credentials are checked when SSO is enforced.
     *
     * @param User $subject
     * @param string $username
     * @param string $password
     * @return array{0: string, 1: string}
     * @throws AuthenticationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAuthenticate(User $subject, $username, $password): array
    {
        if ($this->isNativeLoginBlocked((string)$username)) {
            throw new AuthenticationException(
                __('Admin sign-in requires SSO. Use the "Sign in with SSO" button on the login page.')
            );
        }

        return [$username, $password];
    }

    /**
     * Whether a native login attempt for this username must be rejected.
     *
     * @param string $username
     * @return bool
     */
    private function isNativeLoginBlocked(string $username): bool
    {
        if (!$this->config->isEnabled() || !$this->config->isEnforceSso()) {
            return false;
        }

        return !$this->isBreakGlassAllowed($username);
    }

    /**
     * Whether break-glass is enabled and the username is on its allowlist.
     *
     * @param string $username
     * @return bool
     */
    private function isBreakGlassAllowed(string $username): bool
    {
        return $this->config->isBreakGlassEnabled()
            && in_array(mb_strtolower(trim($username)), $this->config->getBreakGlassUsernames(), true);
    }
}
