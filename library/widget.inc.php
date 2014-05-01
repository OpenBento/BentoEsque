<?php

	class BentoEsqueWidget
	{

		// Callsign & Program IDs Variables
		protected $callsign = NULL;
		protected $program = array();
		protected $show = NULL;
		protected $portal = NULL;

		// COVE API Variables
		protected $episodes = array();
		protected $anything = array();
		protected $combined = array();

		// Error Handler
		protected $error = 0;

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
				$this->show = sanitize($_GET['program'], 4);
				switch ($this->show) {
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

		// Video Builder
		public function buildClips($api_key, $api_secret, $schedule_key) {
			// retrieve last 3 episodes (in case 1st one is the same as the one displayed)
			$params = array(
				'filter_program' => $this->program[1],
				'fields' => 'associated_images',
				'limit_stop' => '3',
				'filter_type' => 'Episode',
				'filter_availability_status' => 'Available',
				'order_by' => '-airdate');
			$temp = new coveApi('videos', $api_key, $api_secret);
			$this->episodes = $temp->addParams($params)->getArrayResult();

			// retrieve 3 additional clips, incase 0 episodes exist
			$params = array(
				'filter_program' => $this->program[1],
				'fields' => 'associated_images',
				'limit_stop' => '3',
				'filter_availability_status' => 'Available',
				'order_by' => '-airdate');
			$temp2 = new coveApi('videos', $api_key, $api_secret);
			$this->anything = $temp2->addParams($params)->getArrayResult();

			// combine unique results
			$this->episodes = $this->clipLoop($this->episodes['results']);
			$this->anything = $this->clipLoop($this->anything['results']);
			$this->combined = array_map("unserialize", array_unique(array_map("serialize", array_merge($this->episodes, $this->anything))));

			// remove video if it's the one shown in the main content area
			$bad = $this->calculateDuplicate($api_key, $api_secret, $schedule_key);

			if (count($this->combined) != 0 ) {
				$i = 0;
				$display_count = 0;
				while ($display_count < 2) {
					if ($this->combined[$i]['id'] != $bad) {
						$this->display .= "<p style=\"text-align:justify;\"><a href=\"" . $this->portal . "video/" . $this->combined[$i]['id'] . "\" target=\"_top\"><b>" . cleanTitles($this->combined[$i]['title']) . "</b></a><br />\n";
						if ($this->show != "pbs-newshour" && $this->show != "charlie-rose") {
							if (strlen($this->combined[$i]['description']) > 90)
								$this->combined[$i]['description'] = substr($this->combined[$i]['description'], 0, 90) . " [...]";
						} else {
							if (strlen($this->combined[$i]['long_description']) > 90)
								$this->combined[$i]['description'] = substr($this->combined[$i]['long_description'], 0, 90) . " [...]";
						}
						if (strlen($this->combined[$i]['title']) > 45)
							$this->combined[$i]['title'] = substr($this->combined[$i]['title'], 0, 45) . " [...]";
						$this->display .= $this->combined[$i]['description'] . "<br /><a href=\"" . $this->portal . "video/" . $this->combined[$i]['id'] . "\" target=\"_top\"><img src=\"". $this->combined[$i]['image'] . "\" border=\"0\" /></a></p>\n";
						$display_count++;
					}
					$i++;
				}
			} else
					$this->display = "<p style=\"text-align:justify;\">Unforunately, no other video clips could be found at this time.</p>";
		}

		public function calculateDuplicate($api_key, $api_secret, $schedule_key) {
			$schedule = new ScheduleAPI($this->callsign, $this->program[0], $schedule_key);
			$this->schedule = $schedule->getResults();
			if ($schedule->getShows() != 0) {
				$clip1 = array();
				$params = array(
				'filter_program' => $this->program[1],
				'limit_stop' => '1',
				'filter_title' => $this->schedule[0][0],
				'filter_type' => 'Episode',
				'filter_availability_status' => 'Available',
				'order_by' => '-airdate');
				$temp = new coveApi('videos', $api_key, $api_secret);
				$clip1 = $temp->addParams($params)->getArrayResult();
				if ($clip1 != NULL && $clip1['results'][0]['partner_player'] != NULL) {
					return $clip1['results'][0]['tp_media_object_id'];
				} else {
					$clip2 = array();
					$params = array(
						'filter_program' => $this->program[1],
						'limit_stop' => '1',
						'filter_type' => 'Promotion',
						'filter_availability_status' => 'Available',
						'order_by' => '-airdate');
					$temp2 = new coveApi('videos', $api_key, $api_secret);
					$clip2 = $temp2->addParams($params)->getArrayResult();
					if (time() < strtotime($clip2['results'][0]['airdate'])) {
						return $clip2['results'][0]['tp_media_object_id'];
					} else {
						$clip3 = array();
						$params = array(
							'filter_program' => $this->program[1],
							'limit_stop' => '1',
							'filter_type' => 'Episode',
							'filter_availability_status' => 'Available',
							'order_by' => '-airdate');
						$temp3 = new coveApi('videos', $api_key, $api_secret);
						$clip3 = $temp3->addParams($params)->getArrayResult();
						if ($temp3 != NULL && $clip3['results'][0]['partner_player'] != NULL) {
							return $clip3['results'][0]['tp_media_object_id'];
						} else {
							$clip4 = array();
							$params = array(
								'filter_program' => $this->program[1],
								'limit_stop' => '1',
								'filter_availability_status' => 'Available',
								'order_by' => '-airdate');
							$temp4 = new coveApi('videos', $api_key, $api_secret);
							$clip4 = $temp4->addParams($params)->getArrayResult();
							if ($clip4['results'][0]['title'] != NULL) {
								return $clip4['results'][0]['tp_media_object_id'];
							} else
								return -1;
						}
					}
				}
			} else
				return -1;
		}

		// Retrieve Image
		public function clipLoop($obj) {
			$videos = array();

			for ($i = 0; $i < 3; $i++) {
				if ($obj[$i]['tp_media_object_id'] != NULL)
					$image = NULL;
					for ($k = 0; $k < 4; $k++) {
						if ($obj[$i]['associated_images'][$k]['type']['usage_type'] == 'COVEStackCard')
							$image = $obj[$i]['associated_images'][$k]['url'];
						elseif ($obj[$i]['associated_images'][$k]['type']['usage_type'] == 'Mezzanine')
							$image = $obj[$i]['associated_images'][$k]['url'];
					}

					$videos[] = array(
						'title' => $obj[$i]['title'],
						'id' => $obj[$i]['tp_media_object_id'],
						'description' => trim($obj[$i]['short_description']),
						'long_description' => trim($obj[$i]['long_description']),
						'image' => $image
						);
			}
			return $videos;
		}

		// Display HTML and/or Errors
		public function displayResults() {
			if ($this->error != 0)
				$this->display = "<p style=\"text-align:center;\"><b>Debug #" . $this->error . ":</b> An unexpected error has occured.</p>";
			return "<div style=\"width:100%;margin:0px;padding:0;\">\n<h1 style=\"text-align:center\">Related Videos</h1>\n<hr />\n" . $this->display . "\n</div>";
		}

	}

?>