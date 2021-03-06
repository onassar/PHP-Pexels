<?php

    // Namespace overhead
    namespace onassar\Pexels;
    use onassar\RemoteRequests;

    /**
     * Base
     * 
     * PHP wrapper for Pexels.
     * 
     * @link    https://github.com/onassar/PHP-Pexels
     * @link    https://www.pexels.com/api/
     * @link    https://www.pexels.com/api/documentation/
     * @author  Oliver Nassar <onassar@gmail.com>
     * @extends RemoteRequests\Base
     */
    class Base extends RemoteRequests\Base
    {
        /**
         * Traits
         * 
         */
        use RemoteRequests\Traits\Pagination;
        use RemoteRequests\Traits\RateLimits;
        use RemoteRequests\Traits\SearchAPI;

        /**
         * _host
         * 
         * @access  protected
         * @var     string (default: 'api.pexels.com')
         */
        protected $_host = 'api.pexels.com';

        /**
         * _paths
         * 
         * @access  protected
         * @var     array
         */
        protected $_paths = array(
            'search' => '/v1/search'
        );

        /**
         * __construct
         * 
         * @link    https://www.pexels.com/api/documentation/#photos-search__per_page
         * @see     https://i.imgur.com/JUkbKpC.png
         * @access  public
         * @return  void
         */
        public function __construct()
        {
            $this->_maxResultsSupportedPerRequest = 80;
            $this->_responseResultsIndexKey = 'photos';
        }

        /**
         * _getAuthorizationHeader
         * 
         * @access  protected
         * @return  string
         */
        protected function _getAuthorizationHeader(): string
        {
            $apiKey = $this->_apiKey;
            $header = 'Authorization: ' . ($apiKey);
            return $header;
        }

        /**
         * _getCURLRequestHeaders
         * 
         * @access  protected
         * @return  array
         */
        protected function _getCURLRequestHeaders(): array
        {
            $headers = parent::_getCURLRequestHeaders();
            $header = $this->_getAuthorizationHeader();
            array_push($headers, $header);
            return $headers;
        }

        /**
         * _getRequestStreamContextOptions
         * 
         * @access  protected
         * @return  array
         */
        protected function _getRequestStreamContextOptions(): array
        {
            $options = parent::_getRequestStreamContextOptions();
            $header = $this->_getAuthorizationHeader();
            $options['http']['header'] = $header;
            return $options;
        }
    }
