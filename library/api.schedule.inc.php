<?php

	// $schedule = new ScheduleAPI($asset->getCallsign, $asset->getScheduleID);

	class ScheduleAPI
	{

		protected $result;
		protected $data = array();
		protected $shows = 0;
		protected $description = NULL;
		protected $title = NULL;

		public function __construct($callsign, $schedule_id, $api_key) {
			$temp = curl_init();
			curl_setopt($temp, CURLOPT_URL, 'http://services.pbs.org/tvss/' . $callsign . '/upcoming/program/' . $schedule_id . '/');
			curl_setopt($temp, CURLOPT_HTTPHEADER, array('X-PBSAUTH: ' . $api_key));
			curl_setopt($temp, CURLOPT_RETURNTRANSFER, true);
			$this->result = json_decode(curl_exec($temp), true);
			curl_close($temp);
		}

		public function countEpisodes() {
			return count($this->result['upcoming_episodes']);
		}

		public function getShows() {
			return $this->shows;
		}

		public function getDescription() {
			return $this->description;
		}

		public function getTitle() {
			return $this->title;
		}

		public function getResults() {
			$shows = $this->countEpisodes();
			$pushed = 0;

			if ($shows > 3)
				$this->shows = 4;
			else
				$this->shows = $shows;
			$this->description = $this->result['description'];
			$this->title = $this->result['title'];
			foreach($this->result['upcoming_episodes'] as $info) {
				if ($pushed < $this->shows) {
					array_push($this->data, array($info['episode_title'], date('m/d/Y', strtotime($info['day'])), date('g:i a', strtotime($info['start_time'])), $info['feed']['analog_channel'], $info['feed']['digital_channel'], $info['feed']['full_name']));
					$pushed++;
				}
       		}
       		return $this->data;
		}

	}

?>