<?php

    /**
     * Pexels
     * 
     * PHP wrapper for Pexels
     * 
     * @author Oliver Nassar <onassar@gmail.com>
     * @see    https://github.com/onassar/PHP-Pexels
     * @see    https://www.pexels.com/api/
     */
    class Pexels
    {
        /**
         * _associative
         * 
         * @var    boolean
         * @access protected
         */
        protected $_associative;

        /**
         * _base
         * 
         * @var    string
         * @access protected
         */
        protected $_base = 'https://api.pexels.com/';

        /**
         * _key
         * 
         * @var    false|string (default: false)
         * @access protected
         */
        protected $_key = false;

        /**
         * _page
         * 
         * @var    string (default: '1')
         * @access protected
         */
        protected $_page = '1';

        /**
         * _photosPerPage
         * 
         * @var    string (default: '20')
         * @access protected
         */
        protected $_photosPerPage = '20';

        /**
         * __construct
         * 
         * @access public
         * @param  string $key
         * @param  boolean $associative (default: true)
         * @return void
         */
        public function __construct($key, $associative = true)
        {
            $this->_key = $key;
            $this->_associative = $associative;
        }

        /**
         * _get
         * 
         * @access protected
         * @param  array $args
         * @return false|array|stdClass
         */
        public function _get(array $args)
        {
            // Path to request
            $responseGroup = 'image_details';
            if ($this->_hd === true) {
                $responseGroup = 'high_resolution';
            }
            $args = array_merge(
                array(
                    'key' => $this->_key,
                    'response_group' => $responseGroup,
                ),
                $args
            );
            $path = http_build_query($args);
            $url = ($this->_base) . '?' . ($path);

            // Stream (to ignore 400 errors)
            $opts = array(
                'http' => array(
                    'method' => 'GET',
                    'ignore_errors' => true
                )
            );

            // Make the request
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            $headers = $this->_getRateLimits($http_response_header);

            // Attempt request; fail with false if it bails
            json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_decode(
                    $response,
                    $this->_associative
                );
            }

            // Fail
            error_log('Pexels:    failed response');
            // error_log($response);
            return false;
        }

        /**
         * query
         * 
         * @access public
         * @param  string $query
         * @param  array $args (default: array())
         * @return false|array|stdClass
         */
        public function query($query, array $args = array())
        {
            $args = array_merge(
                array(
                    'query' => $query,
                    'page' => $this->_page,
                    'per_page' => $this->_photosPerPage
                ),
                $args
            );
            $response = $this->_get($args);
            if ($response === false) {
                return false;
            }

            // Add original query
            if ($this->_associative === true) {
                foreach ($response['hits'] as $index => $hit) {
                    $response['hits'][$index]['original_query'] = $query;
                }
            } else {
                foreach ($response->hits as $index => $hit) {
                    $response->hits[$index]->original_query = $query;
                }
            }
            return $response;
        }

        /**
         * setPage
         * 
         * @access public
         * @param  string $page
         * @return void
         */
        public function setPage($page)
        {
            $this->_page = $page;
        }

        /**
         * setPhotosPerPage
         * 
         * @access public
         * @param  string $photosPerPage
         * @return void
         */
        public function setPhotosPerPage($photosPerPage)
        {
            $this->_photosPerPage = $photosPerPage;
        }
    }
