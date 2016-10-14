<?php

if (!defined('IN_APP')) exit;

class _awards {
	public function __construct() {
		return;
	}

	public function run() {
		$sql = 'SELECT *
			FROM _awards_type
			ORDER BY type_order';
		$types = sql_rowset($sql);

		foreach ($types as $i => $row) {
			if (!$i) _style('awards');

			_style('awards.row', array(
				'NAME' => $row['type_name'],
				'DESC' => $row['type_desc'])
			);
		}

		return;
	}
}
