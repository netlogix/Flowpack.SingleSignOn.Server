<?php
namespace Flowpack\SingleSignOn\Server\Tests\Unit\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.SingleSignOn.Server".*
 *                                                                        *
 *                                                                        */

use \TYPO3\Flow\Http\Request;
use \TYPO3\Flow\Http\Response;
use \TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Party\Domain\Repository\PartyRepository;
use TYPO3\Party\Domain\Service\PartyService;

/**
 *
 */
class SimpleClientAccountMapperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Setup function
	 */
	public function setUp() {
		$mockPartyService = $this->getMock(PartyService::class);
		$mockObjectManager = $this->getMock(ObjectManagerInterface::class);
		$mockObjectManager->expects($this->any())->method('isRegistered')->with('TYPO3\Party\Domain\Service\PartyService')->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(function($objectName) use ($mockPartyService) {
			switch ($objectName) {
				case 'TYPO3\Party\Domain\Service\PartyService':
					return $mockPartyService;
					break;
			}
		}));
		$this->account = new Account();
		$this->inject($this->account, 'objectManager', $mockObjectManager);
	}

	/**
	 * @test
	 */
	public function getAccountDataMapsAccountInformation() {
		$ssoClient = new \Flowpack\SingleSignOn\Server\Domain\Model\SsoClient();
		$this->account->setAccountIdentifier('jdoe');
		$this->account->setRoles(array(new \TYPO3\Flow\Security\Policy\Role('Flowpack.SingleSignon:Administrator')));

		$mapper = new \Flowpack\SingleSignOn\Server\Service\SimpleClientAccountMapper();
		$this->inject($mapper, 'partyService', $this->getMock(PartyService::class));
		$data = $mapper->getAccountData($ssoClient, $this->account);

		$this->assertEquals(array(
			'accountIdentifier' => 'jdoe',
			'roles' => array('Flowpack.SingleSignon:Administrator'),
			'party' => NULL
		), $data);
	}

	/**
	 * @test
	 */
	public function getAccountDataMapsPublicPartyProperties() {
		$ssoClient = new \Flowpack\SingleSignOn\Server\Domain\Model\SsoClient();
		$this->account->setAccountIdentifier('jdoe');
		$this->account->setRoles(array(new \TYPO3\Flow\Security\Policy\Role('Flowpack.SingleSignon:Administrator')));

		$party = new \TYPO3\Party\Domain\Model\Person();
		$party->setName(new \TYPO3\Party\Domain\Model\PersonName('', 'John', '', 'Doe'));
		$this->account->setParty($party);

		$mockPartyService = $this->getMock(PartyService::class);
		$mockPartyService->expects($this->any())->method('getAssignedPartyOfAccount')->will($this->returnValue($party));

		$mapper = new \Flowpack\SingleSignOn\Server\Service\SimpleClientAccountMapper();
		$this->inject($mapper, 'partyService', $mockPartyService);
		$data = $mapper->getAccountData($ssoClient, $this->account);

		$this->assertArrayHasKey('party', $data);
		$this->assertArrayHasKey('name', $data['party']);
		$this->assertArrayHasKey('firstName', $data['party']['name']);
		$this->assertEquals('John', $data['party']['name']['firstName']);
	}

	/**
	 * @test
	 */
	public function getAccountDataExposesTypeIfConfigured() {
		$ssoClient = new \Flowpack\SingleSignOn\Server\Domain\Model\SsoClient();
		$this->account->setAccountIdentifier('jdoe');
		$this->account->setRoles(array(new \TYPO3\Flow\Security\Policy\Role('Flowpack.SingleSignon:Administrator')));

		$party = new \TYPO3\Party\Domain\Model\Person();
		$party->setName(new \TYPO3\Party\Domain\Model\PersonName('', 'John', '', 'Doe'));
		$this->account->setParty($party);

		$mockPartyService = $this->getMock(PartyService::class);
		$mockPartyService->expects($this->any())->method('getAssignedPartyOfAccount')->will($this->returnValue($party));

		$mapper = new \Flowpack\SingleSignOn\Server\Service\SimpleClientAccountMapper();
		$this->inject($mapper, 'partyService', $mockPartyService);
		$mapper->setConfiguration(array(
			'party' => array('_exposeType' => TRUE)
		));
		$data = $mapper->getAccountData($ssoClient, $this->account);

		$this->assertArrayHasKey('party', $data);
		$this->assertArrayHasKey('__type', $data['party']);
		$this->assertEquals('TYPO3\Party\Domain\Model\Person', $data['party']['__type']);
	}

}

?>