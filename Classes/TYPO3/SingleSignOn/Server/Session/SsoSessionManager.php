<?php
namespace TYPO3\SingleSignOn\Server\Session;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.SingleSignOn.Server".*
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Single sign-on session manager
 *
 * @Flow\Scope("singleton")
 */
class SsoSessionManager {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\SingleSignOn\Server\Domain\Repository\SsoClientRepository
	 */
	protected $ssoClientRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\SingleSignOn\Server\Domain\Service\SsoClientNotifierInterface
	 */
	protected $ssoClientNotifier;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\SingleSignOn\Server\Domain\Factory\SsoServerFactory
	 */
	protected $ssoServerFactory;

	/**
	 * @param \TYPO3\Flow\Session\SessionInterface $session
	 * @param \TYPO3\SingleSignOn\Server\Domain\Model\SsoClient $ssoClient
	 * @return void
	 */
	public function registerSsoClient(\TYPO3\Flow\Session\SessionInterface $session, \TYPO3\SingleSignOn\Server\Domain\Model\SsoClient $ssoClient) {
		$registeredClients = $session->getData('TYPO3_SingleSignOn_Clients');
		if (!is_array($registeredClients)) {
			$registeredClients = array();
		}
		$registeredClients[] = $ssoClient->getServiceBaseUri();
		$session->putData('TYPO3_SingleSignOn_Clients', array_unique($registeredClients));
	}

	/**
	 * @param \TYPO3\Flow\Session\SessionInterface $session
	 * @return array Array of \TYPO3\SingleSignOn\Server\Domain\Model\SsoClient
	 */
	public function getRegisteredSsoClients(\TYPO3\Flow\Session\SessionInterface $session) {
		$registeredClients = $session->getData('TYPO3_SingleSignOn_Clients');
		if (!is_array($registeredClients)) {
			$registeredClients = array();
		}
		$ssoClients = array();
		foreach ($registeredClients as $registeredClient) {
			$ssoClients[] = $this->ssoClientRepository->findByIdentifier($registeredClient);
		}
		return $ssoClients;
	}

	/**
	 * Destroy the given session on registered SSO clients
	 *
	 * @param \TYPO3\Flow\Session\SessionInterface $session
	 * @return void
	 */
	public function destroyRegisteredSsoClientSessions($session) {
		if (!$session->isStarted()) {
			return;
		}

		$ssoClients = $this->getRegisteredSsoClients($session);
		$sessionId = $session->getId();

		$ssoServer = $this->ssoServerFactory->create();
		$this->ssoClientNotifier->destroySession($ssoServer, $sessionId, $ssoClients);
	}
}
?>