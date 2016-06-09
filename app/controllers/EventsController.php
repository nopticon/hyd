<?php

class EventsController extends BaseController {
	public function start() {
		global $user;

		$user->init();
		$user->setup();

		$events = new events();
		$events->run();

		page_layout($events->get_title('EVENTS'), $events->get_template('events'));
	}
}