<?php

class BoardController extends BaseController {
	public function start() {
		global $user;

		$user->init();
		$user->setup();

		$board = new board();
		$board->run();

		page_layout('FORUM_INDEX', 'board');
	}

	public function topics() {
		global $user;

		$user->init();
		$user->setup();

		$topics = new topics();
		$topics->run();

		page_layout($topics->get_title(), $topics->get_template());
	}

	public function topic() {
		global $user;
		
		$user->init();
		$user->setup();

		$topic = new topic();
		$topic->run();

		page_layout($topic->get_title(), $topic->get_template());
	}
}