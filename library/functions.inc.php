<?php

	function sanitize($input, $type = 4) {
		$output = filter_var(trim($input), FILTER_SANITIZE_STRING);
		$output = htmlspecialchars(strip_tags($output), ENT_QUOTES, "UTF-8");
		switch ($type)
		{
			case 1:
				$output = preg_replace('/[^A-Za-z0-9]/', "", $output);
				break;
			case 2:
				$output = preg_replace('/[^A-Za-z]/', "", $output);
				break;
			case 3:
				$output = preg_replace('/[^0-9]/', "", $output);
				break;
			case 4:
				$output = preg_replace ('/[^A-Za-z0-9._\- ]/', "", $output);
				break;
		}
		return $output;
	}

	function cleanTitles($input) {
		$remove = array(
			"See the Preview for ",
			" - Preview",
			" - Trailer",
			" - Full Program",
			" - Full Film");
		$output = str_replace($remove, "", $input);
		$output = preg_replace('/ Preview$/', '', $output);
		return $output;
	}

	function cleanChannels($input) {
		$remove = array(
			" - HDTV",
			" - SD");
		$output = str_replace($remove, "", $input);
		return $output;
	}

	function nationalMatch($haystack, $needle) {
		foreach ($haystack as $searchable => $find) {
			if (in_array($needle, $find))
				return $searchable;
		}
		return -1;
	}

?>