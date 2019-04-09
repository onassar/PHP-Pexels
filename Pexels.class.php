<?php

    /**
     * Pexels
     * 
     * PHP wrapper for Pexels
     * 
     * @link    https://www.pexels.com/api/
     * @link    https://www.pexels.com/api/documentation/
     * @link    https://github.com/onassar/PHP-Pexels
     * @author  Oliver Nassar <onassar@gmail.com>
     */
    class Pexels
    {
        /**
         * _attemptSleepDelay
         * 
         * @var     int (default: 2000) in milliseconds
         * @access  protected
         */
        protected $_attemptSleepDelay = 2000;

        /**
         * _base
         * 
         * @var     string (default: 'https://api.pexels.com')
         * @access  protected
         */
        protected $_base = 'https://api.pexels.com';

        /**
         * _key
         * 
         * @var     false|string (default: false)
         * @access  protected
         */
        protected $_key = false;

        /**
         * _lastRemoteRequestHeaders
         * 
         * @var     array (default: array())
         * @access  protected
         */
        protected $_lastRemoteRequestHeaders = array();

        /**
         * _limit
         * 
         * @var     int (default: 40)
         * @access  protected
         */
        protected $_limit = 40;

        /**
         * _logClosure
         * 
         * @var     null|Closure (default: null)
         * @access  protected
         */
        protected $_logClosure = null;

        /**
         * _maxPerPage
         * 
         * @var     int (default: 40)
         * @access  protected
         */
        protected $_maxPerPage = 40;

        /**
         * _offset
         * 
         * @var     int (default: 0)
         * @access  protected
         */
        protected $_offset = 0;

        /**
         * _paths
         * 
         * @var     array
         * @access  protected
         */
        protected $_paths = array(
            'search' => '/v1/search'
        );

        /**
         * _rateLimits
         * 
         * @var     null|array
         * @access  protected
         */
        protected $_rateLimits = null;

        /**
         * _requestApproach
         * 
         * @var     string (default: 'streams')
         * @access  protected
         */
        protected $_requestApproach = 'streams';

        /**
         * _requestTimeout
         * 
         * @var     int (default: 10)
         * @access  protected
         */
        protected $_requestTimeout = 10;

        /**
         * __construct
         * 
         * @access  public
         * @param   string $key
         * @return  void
         */
        public function __construct(string $key)
        {
            $this->_key = $key;
        }

        /**
         * _addURLParams
         * 
         * @access  protected
         * @param   string $url
         * @param   array $params
         * @return  string
         */
        protected function _addURLParams(string $url, array $params): string
        {
            $query = http_build_query($params);
            $piece = parse_url($url, PHP_URL_QUERY);
            if ($piece === null) {
                $url = ($url) . '?' . ($query);
                return $url;
            }
            $url = ($url) . '&' . ($query);
            return $url;
        }

        /**
         * _attempt
         * 
         * Method which accepts a closure, and repeats calling it until
         * $attempts have been made.
         * 
         * This was added to account for requests failing (for a variety of
         * reasons).
         * 
         * @access  protected
         * @param   Closure $closure
         * @param   int $attempt (default: 1)
         * @param   int $attempts (default: 2)
         * @return  null|string
         */
        protected function _attempt(Closure $closure, int $attempt = 1, int $attempts = 2): ?string
        {
            try {
                $response = call_user_func($closure);
                if ($attempt !== 1) {
                    $msg = 'Subsequent success on attempt #' . ($attempt);
                    $this->_log($msg);
                }
                return $response;
            } catch (Exception $exception) {
                $msg = 'Failed closure';
                $this->_log($msg);
                $msg = $exception->getMessage();
                $this->_log($msg);
                if ($attempt < $attempts) {
                    $delay = $this->_attemptSleepDelay;
                    $msg = 'Going to sleep for ' . ($delay);
                    LogUtils::log($msg);
                    $this->_sleep($delay);
                    $response = $this->_attempt($closure, $attempt + 1, $attempts);
                    return $response;
                }
                $msg = 'Failed attempt';
                $this->_log($msg);
            }
            return null;
        }

        /**
         * _get
         * 
         * @access  protected
         * @param   array $requestData
         * @return  null|array
         */
        protected function _get(array $requestData): ?array
        {
            // Make the request
            $url = $this->_getSearchURL($requestData);
            $response = $this->_requestURL($url);
            if ($response === null) {
                return null;
            }
            $this->_rateLimits = $this->_getRateLimits();

            // Invalid json response
            json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            // Response formatting
            $response = json_decode($response, true);
            return $response;
        }

        /**
         * _getCURLRequestHeaders
         * 
         * @access  protected
         * @return  array
         */
        protected function _getCURLRequestHeaders(): array
        {
            $key = $this->_key;
            $headers = array(
                'Authorization: ' . ($key)
            );
            return $headers;
        }

        /**
         * _getFormattedSearchResponse
         * 
         * @access  protected
         * @param   string $query
         * @param   array $response
         * @return  array
         */
        protected function _getFormattedSearchResponse(string $query, array $response): array
        {
            $results = $response['photos'];
            foreach ($results as $index => $hit) {
                $results[$index]['original_query'] = $query;
            }
            return $results;
        }

        /**
         * _getPaginationData
         * 
         * @access  protected
         * @return  array
         */
        protected function _getPaginationData(): array
        {
            $perPage = $this->_getResultsPerPage();
            $offset = $this->_offset;
            $offset = $this->_roundToLower($offset, $perPage);
            $page = ceil($offset / $perPage) + 1;
            $paginationData = array(
                'page' => $page,
                'per_page' => $perPage
            );
            return $paginationData;
        }

        /**
         * _getQueryData
         * 
         * @access  protected
         * @param   string $query
         * @return  array
         */
        protected function _getQueryData(string $query): array
        {
            $queryData = array(
                'query' => $query,
                'size' => 1
            );
            return $queryData;
        }

        /**
         * _getRateLimits
         * 
         * @see     http://php.net/manual/en/reserved.variables.httpresponseheader.php
         * @access  protected
         * @return  null|array
         */
        protected function _getRateLimits(): ?array
        {
            $headers = $this->_lastRemoteRequestHeaders;
            if ($headers === null) {
                return null;
            }
            $formatted = array();
            foreach ($headers as $header) {
                $pieces = explode(':', $header);
                if (count($pieces) >= 2) {
                    $formatted[$pieces[0]] = $pieces[1];
                }
            }
            $rateLimits = array(
                'remaining' => false,
                'limit' => false,
                'reset' => false
            );
            if (isset($formatted['X-Ratelimit-Remaining']) === true) {
                $rateLimits['remaining'] = (int) trim($formatted['X-Ratelimit-Remaining']);
            }
            if (isset($formatted['X-Ratelimit-Limit']) === true) {
                $rateLimits['limit'] = (int) trim($formatted['X-Ratelimit-Limit']);
            }
            if (isset($formatted['X-Ratelimit-Reset']) === true) {
                $rateLimits['reset'] = (int) trim($formatted['X-Ratelimit-Reset']);
            }
            return $rateLimits;
        }

        /**
         * _getRequestData
         * 
         * @access  protected
         * @param   string $query
         * @return  array
         */
        protected function _getRequestData(string $query): array
        {
            $paginationData = $this->_getPaginationData();
            $queryData = $this->_getQueryData($query);
            $requestData = array_merge($paginationData, $queryData);
            return $requestData;
        }

        /**
         * _getRequestStreamContext
         * 
         * @access  protected
         * @return  resource
         */
        protected function _getRequestStreamContext()
        {
            $key = $this->_key;
            $requestTimeout = $this->_requestTimeout;
            $options = array(
                'http' => array(
                    'header' => 'Authorization: ' . ($key),
                    'ignore_errors' => true,
                    'method' => 'GET',
                    'timeout' => $requestTimeout
                )
            );
            $streamContext = stream_context_create($options);
            return $streamContext;
        }

        /**
         * _getResultsPerPage
         * 
         * @access  protected
         * @return  int
         */
        protected function _getResultsPerPage(): int
        {
            $resultssPerPage = min($this->_limit, $this->_maxPerPage);
            return $resultssPerPage;
        }

        /**
         * _getSearchURL
         * 
         * @access  protected
         * @param   array $requestData
         * @return  string
         */
        protected function _getSearchURL(array $requestData): string
        {
            $base = $this->_base;
            $path = $this->_paths['search'];
            $data = $requestData;
            $url = ($base) . ($path);
            $url = $this->_addURLParams($url, $data);
            return $url;
        }

        /**
         * _log
         * 
         * @access  protected
         * @param   string $msg
         * @return  bool
         */
        protected function _log(string $msg): bool
        {
            if ($this->_logClosure === null) {
                error_log($msg);
                return false;
            }
            $closure = $this->_logClosure;
            $args = array($msg);
            call_user_func_array($closure, $args);
            return true;
        }

        /**
         * _parseCURLResponse
         * 
         * This method was required because at times the cURL requests would not
         * return the headers, which would cause issues.
         * 
         * @access  protected
         * @param   string $response
         * @return  array
         */
        protected function _parseCURLResponse(string $response): array
        {
            $delimiter = "\r\n\r\n";
            $pieces = explode($delimiter, $response);
            if (count($pieces) === 1) {
                $headers = '';
                $body = $response;
                $response = array($headers, $body);
                return $response;
            }
            list($headers, $body) = explode("\r\n\r\n", $response, 2);
            $response = array($headers, $body);
            return $response;
        }

        /**
         * _requestURL
         * 
         * @throws  Exception
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURL(string $url): ?string
        {
            if ($this->_requestApproach === 'cURL') {
                $response = $this->_requestURLUsingCURL($url);
                return $response;
            }
            if ($this->_requestApproach === 'streams') {
                $response = $this->_requestURLUsingStreams($url);
                return $response;
            }
            $msg = 'Invalid request approach';
            throw new Exception($msg);
        }

        /**
         * _requestURLUsingCURL
         * 
         * @see     https://stackoverflow.com/a/9183272/115025
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURLUsingCURL(string $url): ?string
        {
            $closure = function() use ($url) {
                $headers = $this->_getCURLRequestHeaders();
                $requestTimeout = $this->_requestTimeout;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $requestTimeout);
                curl_setopt($ch, CURLOPT_TIMEOUT, $requestTimeout);
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            };
            $response = $this->_attempt($closure);
            if ($response === false) {
                return null;
            }
            if ($response === null) {
                return null;
            }
            list($headers, $body) = $this->_parseCURLResponse($response);
            $this->_setCURLResponseHeaders($headers);
            return $body;
        }

        /**
         * _requestURLUsingStreams
         * 
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURLUsingStreams(string $url): ?string
        {
            $closure = function() use ($url) {
                $streamContext = $this->_getRequestStreamContext();
                $response = file_get_contents($url, false, $streamContext);
                if (isset($http_response_header) === true) {
                    $headers = $http_response_header;
                    $this->_lastRemoteRequestHeaders = $headers;
                }
                return $response;
            };
            $response = $this->_attempt($closure);
            if ($response === false) {
                return null;
            }
            if ($response === null) {
                return null;
            }
            return $response;
        }

        /**
         * _roundToLower
         * 
         * @access  protected
         * @param   int $int
         * @param   int $interval
         * @return  int
         */
        protected function _roundToLower(int $int, int $interval): int
        {
            $int = (string) $int;
            $int = preg_replace('/[^0-9]/', '', $int);
            $int = (int) $int;
            $lowered = floor($int / $interval) * $interval;
            return $lowered;
        }

        /**
         * _setCURLResponseHeaders
         * 
         * @access  protected
         * @param   string $headers
         * @return  void
         */
        protected function _setCURLResponseHeaders(string $headers): void
        {
            $headers = explode("\n", $headers);
            $this->_lastRemoteRequestHeaders = $headers;
        }

        /**
         * _sleep
         * 
         * @access  protected
         * @param   int $duration in milliseconds
         * @return  void
         */
        protected function _sleep(int $duration): void
        {
            usleep($duration * 1000);
        }

        /**
         * getRateLimits
         * 
         * @access  public
         * @return  null|array
         */
        public function getRateLimits(): ?array
        {
            $rateLimits = $this->_rateLimits;
            return $rateLimits;
        }

        /**
         * search
         * 
         * @access  public
         * @param   string $query
         * @param   array &persistent (default: array())
         * @return  null|array
         */
        public function search(string $query, array &$persistent = array()): ?array
        {
            // Request results
            $requestData = $this->_getRequestData($query);
            $response = $this->_get($requestData);

            // Failed request
            if ($response === null) {
                return array();
            }
            if (isset($response['photos']) === false) {
                return array();
            }

            // Format + more than enough found
            $results = $this->_getFormattedSearchResponse($query, $response);
            $resultsCount = count($results);
            $mod = $this->_offset % $this->_getResultsPerPage();
            if ($mod !== 0) {
                array_splice($results, 0, $mod);
            }
            $persistent = array_merge($persistent, $results);
            $persistentCount = count($persistent);
            if ($persistentCount >= $this->_limit) {
                return array_slice($persistent, 0, $this->_limit);
            }
            if ($resultsCount < $this->_maxPerPage) {
                return array_slice($persistent, 0, $this->_limit);
            }

            // Recusively get more
            $this->_offset += count($results);
            return $this->search($query, $persistent);
        }

        /**
         * setLimit
         * 
         * @access  public
         * @param   string $limit
         * @return  void
         */
        public function setLimit($limit): void
        {
            $this->_limit = $limit;
        }

        /**
         * setLogClosure
         * 
         * @access  public
         * @param   Closure $closure
         * @return  void
         */
        public function setLogClosure(Closure $closure): void
        {
            $this->_logClosure = $closure;
        }

        /**
         * setOffset
         * 
         * @access  public
         * @param   string $offset
         * @return  void
         */
        public function setOffset($offset): void
        {
            $this->_offset = $offset;
        }

        /**
         * setRequestApproach
         * 
         * @access  public
         * @param   string $requestApproach
         * @return  void
         */
        public function setRequestApproach(string $requestApproach): void
        {
            $this->_requestApproach = $requestApproach;
        }

        /**
         * setRequestTimeout
         * 
         * @access  public
         * @param   int $requestTimeout
         * @return  void
         */
        public function setRequestTimeout(int $requestTimeout): void
        {
            $this->_requestTimeout = $requestTimeout;
        }
    }
