<?php
require_once 'core/components/antispambycleantalk/model/cleantalk.class.php';
require_once 'core/components/antispambycleantalk/model/cleantalkrequest.class.php';
require_once 'core/components/antispambycleantalk/model/cleantalkresponse.class.php';
require_once 'core/components/antispambycleantalk/model/cleantalkhelper.class.php';
require_once 'core/components/antispambycleantalk/model/cleantalk-php-patch.php';

class CleantalkTest extends \PHPUnit\Framework\TestCase 
{
	protected $ct;

	protected $ct_request;

	public function setUp()
	{
		$this->ct = new Cleantalk();
		$this->ct->server_url = 'http://moderate.cleantalk.org';
		$this->ct_request = new CleantalkRequest();
		$this->ct_request->auth_key = getenv("CLEANTALK_TEST_API_KEY");
	}

	public function testIsAllowMessage()
	{
		$this->ct_request->sender_email = 'good@mail.org';
		$this->ct_request->message = 'good message';
		$result = $this->ct->isAllowMessage($this->ct_request);
		$this->assertEquals(1, $result->allow);

		$this->ct_request->sender_email = 's@cleantalk.org';
		$this->ct_request->message = 'stop_word bad message';
		$result = $this->ct->isAllowMessage($this->ct_request);
		$this->assertEquals(0, $result->allow);					

		$this->ct_request->message = '';
		$this->ct_request->sender_email = '';
	}

	public function testIsAllowUser()
	{
		$this->ct_request->sender_email = 'good@mail.org';
		$result = $this->ct->isAllowUser($this->ct_request);
		$this->assertEquals(1, $result->allow);

		$this->ct_request->sender_email = 's@cleantalk.org';
		$result = $this->ct->isAllowUser($this->ct_request);
		$this->assertEquals(0, $result->allow);

		$this->ct_request->sender_email = '';
	}	
}