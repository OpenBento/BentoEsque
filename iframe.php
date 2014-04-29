<?php

	require('library/functions.inc.php');
	require('library/bentoesque.inc.php');
	require('library/api.cove.inc.php');
	require('library/api.schedule.inc.php');

	$schedule_API = '';
	$cove_API = '';
	$cove_Secret = '';

	$asset = new BentoEsque;

	$asset->buildSchedule($schedule_API);
	$asset->buildPlayer($cove_API, $cove_Secret);
	$asset->displayResults();

?>