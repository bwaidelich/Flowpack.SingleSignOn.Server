<?php
namespace Flowpack\SingleSignOn\Server\Service;

/*                                                                               *
 * This script belongs to the TYPO3 Flow package "Flowpack.SingleSignOn.Server". *
 *                                                                               */

use TYPO3\Flow\Annotations as Flow;

/**
 * Server account manager
 *
 * The account manager gets accounts for SSO clients and the
 * authenticated account on the server. It also handles account switching to deliver
 * a different account to a client than the authenticated account.
 *
 * @Flow\Scope("session")
 */
class AccountManager {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @Flow\Inject
	 * @Flow\Transient
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \Flowpack\SingleSignOn\Server\Session\SsoSessionManager
	 */
	protected $singleSignOnSessionManager;

	/**
	 * The currently impersonated account (NULL if no account is impersonated)
	 * @var \TYPO3\Flow\Security\Account
	 */
	protected $impersonatedAccount;

	/**
	 * Get the currently active account for any SSO client (for the current session)
	 *
	 * @return \TYPO3\Flow\Security\Account
	 */
	public function getClientAccount() {
		if ($this->impersonatedAccount !== NULL) {
			return $this->impersonatedAccount;
		} else {
			return $this->authenticationManager->getSecurityContext()->getAccount();
		}
	}

	/**
	 * Get the currently authenticated account on the SSO server (for the current session)
	 *
	 * @return \TYPO3\Flow\Security\Account
	 */
	public function getServerAccount() {
		$account = $this->authenticationManager->getSecurityContext()->getAccount();
		return $account;
	}

	/**
	 * Impersonate another account
	 *
	 * Destroys registered client sessions to force re-authentication.
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 */
	public function impersonateAccount(\TYPO3\Flow\Security\Account $account = NULL) {
		if ($this->impersonatedAccount !== $account) {
			$this->impersonatedAccount = $account;

			$this->destroyRegisteredClientSessions();

			$this->emitAccountImpersonated($account);
		}
	}

	/**
	 * @return \TYPO3\Flow\Security\Account The impersonated account or NULL if no account was impersonated
	 */
	public function getImpersonatedAccount() {
		return $this->impersonatedAccount;
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $account The impersonated account
	 * @Flow\Signal
	 */
	protected function emitAccountImpersonated(\TYPO3\Flow\Security\Account $account) {}

	/**
	 * Destroy the SSO session on all registered SSO clients of the current session
	 *
 	 * Called on logout of the active account on the server through
	 * AuthenticationProviderManager->loggedOut signal.
	 *
	 * @return void
	 */
	public function destroyRegisteredClientSessions() {
		if ($this->session->isStarted()) {
			$this->singleSignOnSessionManager->destroyRegisteredSsoClientSessions($this->session);
		}
	}

}
?>