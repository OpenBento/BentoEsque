<?php

	require('library/functions.inc.php');
	require('library/api.cove.inc.php');
	require('library/api.schedule.inc.php');

	$national = array(
			array("american-experience", 630, 408),
			array("american-masters", 1832, 409),
			array("antiques-roadshow", 1251, 411),
			array("call-the-midwife", 1467, 374604),
			array("charlie-rose", 3516, 376978),
			array("frontline", 122, 429),
			array("great-performances", 485, 433),
			array("independent-lens", 302, 439),
			array("masterpiece", 489, 455),
			array("nature", 620, 462),
			array("nova", 768, 466),
			array("pbs-newshour", 1822, 473),
			array("pov", 605, 483));

	$schedule_API = '';
	$cove_API = '';
	$cove_Secret = '';

	if (isset($_GET['view'])) {
		switch ((int) sanitize($_GET['view'], 3)) {
			case 2:
				require('library/widget.inc.php');

				// build the sidebar widget
				$asset = new BentoEsqueWidget($national);
				$asset->buildClips($cove_API, $cove_Secret, $schedule_API);

				// style and display
				echo "<link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,400,300,700,600' rel='stylesheet' type='text/css'>\n
					<style type=\"text/css\">\nbody, p, h1, h2, h3 {font-family: 'Open Sans', sans-serif;font-size:13px;color:#000}\nh1{font-size:22px;}\n</style>\n";
				echo $asset->displayResults();
				break;
			default:
				require('library/bentoesque.inc.php');

				// build main content area
				$asset = new BentoEsque($national);

				$asset->buildSchedule($schedule_API);
				$asset->buildPlayer($cove_API, $cove_Secret);

				// stylize and display
				echo "<link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,400,300,700,600' rel='stylesheet' type='text/css'>\n
					<style type=\"text/css\">\nbody, p, h1, h2, h3 {font-family: 'Open Sans', sans-serif;font-size:13px;color:#000}\nh1{font-size:22px;}\n</style>\n";
				echo $asset->displayResults();
				break;
		}
	}

?>