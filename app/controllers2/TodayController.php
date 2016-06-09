<?php

class TodayController extends BaseController {
	public function start() {
		global $user;

		$user->init();
		$user->setup();

		if (!$user->is('member')) {
			do_login();
		}

		$today = new today();
		$today->run();

		page_layout('NOTIFICATIONS', 'today');
	}
}