<?php
if (!class_exists('Memcache') && class_exists('Memcached')) {
	define('MEMCACHE_COMRESSED', 2);

	/**
	 * Memcache class emulation by extending Memcached
	 *
	 * @author gt <gt-kani.hu>
	 * @class Memcache
	 */
	class Memcache extends Memcached {

		private $failure_callback = null;

		public function addServer($host, $port = 11211, $persistent = true, $weight = 0, $timeout = 1, $retry_interval = 15, $status = true, $failure_callback = null, $timeoutms = 0) {
			$this->failure_callback = $failure_callback;
			$this->setOption(Memcached::OPT_CONNECT_TIMEOUT, $timeout*1000+$timeoutms);
			$this->setOption(Memcached::OPT_RECV_TIMEOUT, $timeout*1000+$timeoutms);
			$this->setOption(Memcached::OPT_SEND_TIMEOUT, $timeout*1000+$timeoutms);
			$this->setOption(Memcached::OPT_POLL_TIMEOUT, $timeout*1000+$timeoutms);
			$this->setOption(Memcached::OPT_RETRY_TIMEOUT, $retry_interval);
			$this->setOption(Memcached::OPT_BINARY_PROTOCOL, $retry_interval);
			return parent::addServer($host, $port, $weight);
		}
		public function handleError() {
			if (!in_array($this->getResultCode(), array(Memcached::RES_SUCCESS, Memcached::RES_NOTFOUND, Memcached::RES_NOTSTORED, Memcached::RES_DATA_EXISTS, Memcached::RES_END))) {
				$sl = $this->getServerList();
				call_user_func($this->failure_callback, $sl[0]['host'], $sl[0]['port']);
			}
		}
		public function get($key) {
			if (($result = parent::get($key)) && $result === false) self::handleError();
			return $result;
		}

		public function set($key, $var, $flag = 0, $expire = 0) {
			$compress = $this->getOption(Memcached::OPT_COMPRESSION);
			if (($compress && !($flag & MEMCACHE_COMRESSED)) || (!$compress && ($flag & MEMCACHE_COMRESSED))) $this->setOption(Memcached::OPT_COMPRESSION, !$compress);
			if (($result = parent::set($key, $var, $expire)) && $result === false) self::handleError();
			if (($compress && !($flag & MEMCACHE_COMRESSED)) || (!$compress && ($flag & MEMCACHE_COMRESSED))) $this->setOption(Memcached::OPT_COMPRESSION, $compress);
			return $result;
		}

		public function replace($key, $var, $flag = 0, $expire = 0) {
			$compress = $this->getOption(Memcached::OPT_COMPRESSION);
			if (($compress && !($flag & MEMCACHE_COMRESSED)) || (!$compress && ($flag & MEMCACHE_COMRESSED))) $this->setOption(Memcached::OPT_COMPRESSION, !$compress);
			if (($result = parent::set($key, $var, $expire)) && $result === false) self::handleError();
			if (($compress && !($flag & MEMCACHE_COMRESSED)) || (!$compress && ($flag & MEMCACHE_COMRESSED))) $this->setOption(Memcached::OPT_COMPRESSION, $compress);
			return $result;
		}
		
		public function getStats() {
			$stats = array_values(parent::getStats());
			return $stats[0];
		}
	}
} 
