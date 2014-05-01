<?php

	class BentoEsque
	{

		// Callsign & Program IDs Variables
		protected $callsign = NULL;
		protected $program = array(0, 0);

		// Schedule and COVE API Variables
		protected $schedule = array();
		protected $clip = array();

		// Error Handlers
		protected $error = 0;
		protected $empty = 0;

		// HTML Generated
		protected $display = NULL;

		// Class Constructor
		public function __construct($national) {
			if (isset($_GET['portal'])) {
				$this->portal = sanitize($_GET['portal']);
				if ($this->portal == null) {
					$this->portal = "http://video.pbs.org/";
				}
				if (substr($this->portal, -1) != '/')
					$this->portal = $this->portal . '/';
			} else
				$this->error = 6;

			if (isset($_GET['callsign'])) {
				$this->callsign = sanitize($_GET['callsign']);
			} else
				$this->error = 1;

			if (isset($_GET['program'])) {
				$type = sanitize($_GET['program'], 4);
				switch ($type) {
					case "Other":
						if (isset($_GET['schedule_id']) && isset($_GET['cove_id'])) {
							$this->program[0] = (int) sanitize($_GET['schedule_id'], 3);
							$this->program[1] = (int) sanitize($_GET['cove_id'], 3);
						} else
							$this->error = 3;
						break;
					default:
						$match = nationalMatch($national, sanitize($_GET['program']));
						if ($match != -1) {
							$this->program[0] = (int) $national[$match][1];
							$this->program[1] = (int) $national[$match][2];
						} else
							$this->error = 4;
						break;
				}
			} else
				$this->error = 2;
		}

		// Schedule Logic & Builder
		public function buildSchedule($api_key) {
			$schedule = new ScheduleAPI($this->callsign, $this->program[0], $api_key);
			$this->schedule = $schedule->getResults();

			if (isset($_GET['title'])) {
				if (sanitize($_GET['title'], 2) == "True")
					$this->display = "<h1 style=\"text-align:center\">" . $schedule->getTitle() . "</h1>\n<hr />\n";
			}
			if (isset($_GET['description'])) {
				if (sanitize($_GET['description'], 2) == "True")
					$this->display .= "<p style=\"text-align:justify;\">" . $schedule->getDescription() . "</p>\n";
			}

			$this->display .= "<p style=\"text-align:center;font-weight:800\">" . $schedule->getShows() . " Upcoming Show";
			if ($schedule->getShows() == 0)
				$this->empty++;
			if ($schedule->getShows() != 1)
				$this->display .= "s";
			$this->display .= " Scheduled</p>\n";
			if ($schedule->getShows() != 0) {
				$this->display .= "<table style=\"border:0;width:100%;padding:0px;\"><tr>\n";
				for ($i = 0; $i < $schedule->getShows(); $i++) {
					$title = "";
					if ($this->schedule[$i][0] != null)
						$title = $this->schedule[$i][0] . "<br />";
					$this->display .= "<td width=\"" . (int) (100 / $schedule->getShows()) . "%\" valign=\"top\"><p style=\"padding:0;margin:0;text-align:center;\">" . $title . $this->schedule[$i][1] . " at " . $this->schedule[$i][2] . "<br />on " . cleanChannels($this->schedule[$i][5]) . "</p></td>\n";
				}
			}
			$this->display .= "</tr></table>\n";
		}

		// Video Logic
		public function buildPlayer($api_key, $api_secret) {
			$params = array(
				'filter_program' => $this->program[1],
				'limit_stop' => '1',
				'filter_title' => $this->schedule[0][0],
				'filter_type' => 'Episode',
				'filter_availability_status' => 'Available',
				'order_by' => '-airdate');
			$temp = new coveApi('videos', $api_key, $api_secret);
			$this->clip = $temp->addParams($params)->getArrayResult();
			if ($this->clip != NULL && $this->clip['results'][0]['partner_player'] != NULL) {
				$this->switchVideo(" Episode", $this->clip);
			} else {
				$params = array(
					'filter_program' => $this->program[1],
					'limit_stop' => '1',
					'filter_type' => 'Promotion',
					'filter_availability_status' => 'Available',
					'order_by' => '-airdate');
				$temp2 = new coveApi('videos', $api_key, $api_secret);
				$this->clip = $temp2->addParams($params)->getArrayResult();
				if (time() < strtotime($this->clip['results'][0]['airdate'])) {
					$this->switchVideo(" Preview", $this->clip);
				} else {
					$params = array(
						'filter_program' => $this->program[1],
						'limit_stop' => '1',
						'filter_type' => 'Episode',
						'filter_availability_status' => 'Available',
						'order_by' => '-airdate');
					$temp3 = new coveApi('videos', $api_key, $api_secret);
					$this->clip = $temp3->addParams($params)->getArrayResult();

					if ($temp3 != NULL && $this->clip['results'][0]['partner_player'] != NULL) {
						$this->switchVideo(" Episode", $this->clip);
					} else {
						$params = array(
							'filter_program' => $this->program[1],
							'limit_stop' => '1',
							'filter_availability_status' => 'Available',
							'order_by' => '-airdate');
						$temp4 = new coveApi('videos', $api_key, $api_secret);
						$this->clip = $temp4->addParams($params)->getArrayResult();
						if ($this->clip['results'][0]['title'] != NULL) {
							$this->switchVideo("", $this->clip);
						} else {
							$this->empty++;
							$this->display .= "<p style=\"text-align:center;\">No video clips could be found.</p>";
						}
					}
				}
			}
		}

		// Video Builder
		public function switchVideo($word, $obj) {
			$this->display .= "<p style=\"text-align:justify\"><b>Watch" . $word . ": <a href=\"" . $this->portal . "video/" . $obj['results'][0]['tp_media_object_id'] . "\" target=\"_top\">" . cleanTitles($obj['results'][0]['title']) . "</a></b><br />";
			$this->display .= trim($obj['results'][0]['long_description']) . "</p>\n<div style=\"text-align:center;\">\n";
			$this->display .= $obj['results'][0]['partner_player'] . "</div>\n";
		}

		// Display HTML and/or Errors
		public function displayResults() {
			if ($this->empty == 2)
				$this->error = 5;

			if ($this->error != 0)
				$this->display = "<p style=\"text-align:center;\"><b>Debug #" . $this->error . ":</b> An unexpected error has occured.</p>";

			return "<div style=\"width:100%;margin:0px;\">\n" . $this->display . "\n</div>";
		}

	}

?>