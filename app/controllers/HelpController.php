<?php

class HelpController extends BaseController {
	public function start() {
		global $user;

		$user->init();
		$user->setup();

		$help = new help();
		$help->run();

		page_layout($help->get_title('HELP'), $help->get_template('help'));
	}
}