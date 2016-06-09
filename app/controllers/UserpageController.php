<?php

class UserpageController extends BaseController {
	public function start() {
		global $user;

		$user->init();
		$user->setup();

		$userpage = new userpage();
		$userpage->run();

		page_layout($userpage->get_title(), $userpage->get_template());
	}
}