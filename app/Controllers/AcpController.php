<?php

class AcpController extends BaseController {
	public function start() {
		global $user;

		$user->init();
		$user->setup();

		$acp = new acp();
		$acp->run();

		page_layout($acp->get_title('ACP'), $acp->get_template('acp'));
	}
}