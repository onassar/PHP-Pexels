<?php

    /**
     * Pexels
     * 
     * PHP wrapper for Pexels
     * 
     * @link    https://www.pexels.com/api/
     * @link    https://github.com/onassar/PHP-Pexels
     * @author  Oliver Nassar <onassar@gmail.com>
     */
    class Pexels
    {
        /**
         * _base
         * 
         * @var     string
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
         * _page
         * 
         * @var     int (default: 1)
         * @access  protected
         */
        protected $_page = 1;

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
         * _photosPerPage
         * 
         * @var     int (default: 20)
         * @access  protected
         */
        protected $_photosPerPage = 20;

        /**
         * _rateLimits
         * 
         * @var     null|array
         * @access  protected
         */
        protected $_rateLimits = null;

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
         * _addUrlParams
         * 
         * @access  protected
         * @param   string $url
         * @param   array $params
         * @return  string
         */
        protected function _addUrlParams(string $url, array $params): string
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
         * _get
         * 
         * @access  protected
         * @param   array $args
         * @return  null|array
         */
        protected function _get(array $args): ?array
        {
            // Make the request
            $url = $this->_getSearchUrl($args);
            $response = $this->_requestUrl($url);
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
         * _getFormattedSearchResponse
         * 
         * @access  protected
         * @param   string $query
         * @param   array $response
         * @return  array
         */
        protected function _getFormattedSearchResponse(string $query, array $response): array
        {
            foreach ($response['photos'] as $index => $hit) {
                $response['photos'][$index]['original_query'] = $query;
            }
            return $response;
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
         * _getRequestArguments
         * 
         * @access  protected
         * @param   string $query
         * @param   array $args (default: array())
         * @return  array
         */
        protected function _getRequestArguments(string $query, array $args = array()): array
        {
            $defaults = array(
                'query' => $query,
                'size' => 1,
                'page' => $this->_page,
                'per_page' => $this->_photosPerPage
            );
            $args = array_merge($defaults, $args);
            return $args;
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
         * _getSearchUrl
         * 
         * @access  protected
         * @param   array $args
         * @return  string
         */
        protected function _getSearchUrl(array $args): string
        {
            $base = $this->_base;
            $path = $this->_paths['search'];
            $data = $args;
            $url = ($base) . ($path);
            $url = $this->_addUrlParams($url, $data);
            return $url;
        }

        /**
         * _requestUrl
         * 
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestUrl(string $url): ?string
        {
            $streamContext = $this->_getRequestStreamContext();
            $response = file_get_contents($url, false, $streamContext);
            if ($response === false) {
                return null;
            }
            if (isset($http_response_header) === true) {
                $this->_lastRemoteRequestHeaders = $http_response_header;
            }
            return $response;
        }

        /**
         * getRateLimits
         * 
         * @access  public
         * @return  null|array
         */
        public function getRateLimits(): ?array
        {
            return $this->_rateLimits;
        }

        /**
         * search
         * 
         * @access  public
         * @param   string $query
         * @param   array $args (default: array())
         * @return  null|array
         */
        public function search(string $query, array $args = array()): ?array
        {
            $args = $this->_getRequestArguments($query, $args);
            $response = $this->_get($args);
            if ($response === null) {
                return null;
            }
            if (isset($response['photos']) === false) {
                return null;
            }
            $response = $this->_getFormattedSearchResponse($query, $response);
            return $response;
        }

        /**
         * setPage
         * 
         * @access  public
         * @param   string $page
         * @return  void
         */
        public function setPage($page)
        {
            $this->_page = $page;
        }

        /**
         * setPhotosPerPage
         * 
         * @access  public
         * @param   string $photosPerPage
         * @return  void
         */
        public function setPhotosPerPage($photosPerPage)
        {
            $this->_photosPerPage = $photosPerPage;
        }
    }
