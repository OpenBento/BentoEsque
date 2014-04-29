<?php

	// Originally written by Jim Stamper at KCTS 9 Seattle Public Television
	class CoveAPI
	{

		protected $api_id = NULL,
			$api_secret = NULL,
			$api_url = 'http://api.pbs.org/cove/v1/',
			$api_type = 'videos';
		private $nonce, $canonical, $string_to_sign, $url_with_sig, $params = array(), $url_query, $result = FALSE, $timestamp, $signature;

		public function __construct($api_type = FALSE, $api_id = FALSE, $api_secret = FALSE) {
			$this->timestamp = time();
			$this->nonce = md5(rand());

			($api_type) ? $this->api_type = $api_type : '';
			($api_id) ? $this->api_id = $api_id : '';
			($api_secret) ? $this->api_secret = $api_secret : '';

			return $this;
		}

		public function addParams(array $params) {
			if (!empty($params)) {
				foreach($params as $param => $value)
					$this->params[$param] = utf8_encode($value);
			}
			return $this;
		}

		public function buildUrl() {
			$this->params['consumer_key'] =  $this->api_id;
			$this->params['nonce'] =  $this->nonce;
			$this->params['timestamp'] = $this->timestamp;

			ksort($this->params);
			$this->api_url = $this->api_url.$this->api_type.'/?';
			$query = http_build_query($this->params);
			$this->url_query = $query;
			$this->canonical = $this->api_url.$query;

			return $this;
		}

		protected function setResult() {
			$this->buildUrl()->setSig();
			$opts = array(
				'http'=> array(
					'method'=>"GET",
					'header'=>"X-PBSAuth-Timestamp: ".$this->timestamp."\r\n" .
					"X-PBSAuth-Consumer-Key: ".$this->api_id."\r\n".
					"X-PBSAuth-Signature: ".$this->signature."\r\n".
					"X-PBSAuth-Nonce: ".$this->nonce."\r\n"));

			$context = stream_context_create($opts);
			$this->result = file_get_contents($this->canonical, FALSE, $context);
			return $this;
		}

		protected function setSig() {
			$this->string_to_sign = "GET".$this->canonical.$this->timestamp.$this->api_id.$this->nonce;
			$this->signature = hash_hmac('sha1', $this->string_to_sign, $this->api_secret);
			$this->url_with_sig = $this->canonical."&signature=".$this->signature;

			return $this;
		}

		public function getResult() {
			if (!$this->result)
				$this->setResult();
			return $this->result;
		}

		public function getArrayResult() {
			if (!$this->result)
				$this->setResult();
			return json_decode($this->result, true);
		}

	}

?>