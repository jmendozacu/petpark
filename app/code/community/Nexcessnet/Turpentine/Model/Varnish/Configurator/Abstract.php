<?php

/**
 * Nexcess.net Turpentine Extension for Magento
 * Copyright (C) 2012  Nexcess.net L.L.C.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

abstract class Nexcessnet_Turpentine_Model_Varnish_Configurator_Abstract {

    const VCL_CUSTOM_C_CODE_FILE = 'uuid.c';

    /**
     * Get the correct version of a configurator from a socket
     *
     * @param  Nexcessnet_Turpentine_Model_Varnish_Admin_Socket $socket
     * @return Nexcessnet_Turpentine_Model_Varnish_Configurator_Abstract
     */
    static public function getFromSocket($socket) {
        try {
            $version = $socket->getVersion();
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('core/session')
                ->addError('Error determining Varnish version: '.
                    $e->getMessage());
            return null;
        }
        switch ($version) {
            case '4.0':
                return Mage::getModel(
                    'turpentine/varnish_configurator_version4',
                    array('socket' => $socket) );

            case '3.0':
                return Mage::getModel(
                    'turpentine/varnish_configurator_version3',
                        array('socket' => $socket) );
            case '2.1':
                return Mage::getModel(
                    'turpentine/varnish_configurator_version2',
                        array('socket' => $socket) );
            default:
                Mage::throwException('Unsupported Varnish version');
        }
    }

    /**
     * The socket this configurator is based on
     *
     * @var Nexcessnet_Turpentine_Model_Varnish_Admin_Socket
     */
    protected $_socket = null;
    /**
     * options array
     *
     * @var array
     */
    protected $_options = array(
        'vcl_template'  => null,
    );

    public function __construct($options = array()) {
        $this->_options = array_merge($this->_options, $options);
    }

    abstract public function generate($doClean = true);
    // abstract protected function _getTemplateVars();

    /**
     * Save the generated config to the file specified in Magento config
     *
     * @param  string $generatedConfig config generated by @generate
     * @return null
     */
    public function save($generatedConfig) {
        $filename = $this->_getVclFilename();
        $dir = dirname($filename);
        if ( ! is_dir($dir)) {
            // this umask is probably redundant, but just in case...
            if ( ! mkdir($dir, 0777 & ~umask(), true)) {
                $err = error_get_last();
                return array(false, $err);
            }
        }
        if (strlen($generatedConfig) !==
                file_put_contents($filename, $generatedConfig)) {
            $err = error_get_last();
            return array(false, $err);
        }
        return array(true, null);
    }

    /**
     * Get the full path for a given template filename
     *
     * @param  string $baseFilename
     * @return string
     */
    protected function _getVclTemplateFilename($baseFilename) {
           $extensionDir = Mage::getModuleDir('', 'Nexcessnet_Turpentine');
           return sprintf('%s/misc/%s', $extensionDir, $baseFilename);
    }

    /**
     * Get the name of the file to save the VCL to
     *
     * @return string
     */
    protected function _getVclFilename() {
        return $this->_formatTemplate(
            Mage::getStoreConfig('turpentine_varnish/servers/config_file'),
            array('root_dir' => Mage::getBaseDir()) );
    }

    /**
     * Get the name of the custom include VCL file
     *
     * @return string
     */
    protected function _getCustomIncludeFilename($position='') {
        $key = 'custom_include_file';
        $key .= ($position) ? '_'.$position : '';
        return $this->_formatTemplate(
            Mage::getStoreConfig('turpentine_varnish/servers/'.$key),
            array('root_dir' => Mage::getBaseDir()) );
    }


    /**
     * Get the custom VCL template, if it exists
     * Returns 'null' if the file doesn't exist
     *
     * @return string
     */
    protected function _getCustomTemplateFilename() {
        $filePath = $this->_formatTemplate(
            Mage::getStoreConfig('turpentine_varnish/servers/custom_vcl_template'),
            array('root_dir' => Mage::getBaseDir())
        );
        if (is_file($filePath)) { return $filePath; }
        else { return null; }
    }


    /**
     * Format a template string, replacing {{keys}} with the appropriate values
     * and remove unspecified keys
     *
     * @param  string $template template string to operate on
     * @param  array  $vars     array of key => value replacements
     * @return string
     */
    protected function _formatTemplate($template, array $vars) {
        $needles = array_map(create_function('$k', 'return "{{".$k."}}";'),
            array_keys($vars));
        $replacements = array_values($vars);
        // do replacements, then delete unused template vars
        return preg_replace('~{{[^}]+}}~', '',
            str_replace($needles, $replacements, $template));
    }

    /**
     * Format a VCL subroutine call
     *
     * @param  string $subroutine subroutine name
     * @return string
     */
    protected function _vcl_call($subroutine) {
        return sprintf('call %s;', $subroutine);
    }

    /**
     * Get the Magento admin frontname
     *
     * This is just the plain string, not in URL format. ex:
     * http://example.com/magento/admin -> admin
     *
     * @return string
     */
    protected function _getAdminFrontname() {
        if (Mage::getStoreConfig('admin/url/use_custom_path')) {
            if(Mage::getStoreConfig('web/url/use_store')) {
                return Mage::getModel('core/store')->load(0)->getCode() . "/" . Mage::getStoreConfig('admin/url/custom_path');
            } else {
                return Mage::getStoreConfig('admin/url/custom_path');
            }
        } else {
            return (string) Mage::getConfig()->getNode(
                'admin/routers/adminhtml/args/frontName' );
        }
    }

    /**
     * Get the hostname for host normalization from Magento's base URL
     *
     * @return string
     */
    protected function _getNormalizeHostTarget() {
        $configHost = trim(Mage::getStoreConfig(
            'turpentine_vcl/normalization/host_target' ));
        if ($configHost) {
            return $configHost;
        } else {
            $baseUrl = parse_url(Mage::getBaseUrl());
            if (isset($baseUrl['port'])) {
                return sprintf('%s:%d', $baseUrl['host'], $baseUrl['port']);
            } else {
                return $baseUrl['host'];
            }
        }
    }

    /**
     * Get hosts as regex
     *
     * ex: base_url: example.com
     *     path_regex: (example.com|example.net)
     *
     * @return string
     */
    public function getAllowedHostsRegex() {
        $hosts = array();
        foreach (Mage::app()->getStores() as $store) {
            $hosts[] = parse_url($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false), PHP_URL_HOST);
        }

        $hosts = array_values(array_unique($hosts));

        $pattern = '('.implode('|', array_map("preg_quote", $hosts)).')';
        return $pattern;
    }

    /**
     * Get the Host normalization sub routine
     *
     * @return string
     */
    protected function _vcl_sub_allowed_hosts_regex() {
        $tpl = <<<EOS
# if host is not allowed in magento pass to backend
        if (req.http.host !~ "{{allowed_hosts_regex}}") {
            return (pass);
        }
EOS;
        return $this->_formatTemplate($tpl, array(
            'allowed_hosts_regex' => $this->getAllowedHostsRegex() ));
    }

    /**
     * Get the base url path regex
     *
     * ex: base_url: http://example.com/magento/
     *     path_regex: /magento/(?:(?:index|litespeed)\.php/)?
     *
     * @return string
     */
    public function getBaseUrlPathRegex() {
        $pattern = '^(%s)(?:(?:index|litespeed)\\.php/)?';
        return sprintf($pattern, implode('|',
            array_map(create_function('$x', 'return preg_quote($x,"|");'),
                $this->_getBaseUrlPaths())));
    }

    /**
     * Get the path part of each store's base URL and static file URLs
     *
     * @return array
     */
    protected function _getBaseUrlPaths() {
        $paths = array();
        $linkTypes = array(Mage_Core_Model_Store::URL_TYPE_LINK,
                            Mage_Core_Model_Store::URL_TYPE_JS,
                            Mage_Core_Model_Store::URL_TYPE_SKIN,
                            Mage_Core_Model_Store::URL_TYPE_MEDIA);
        foreach (Mage::app()->getStores() as $store) {
            foreach ($linkTypes as $linkType) {
                $paths[] = parse_url($store->getBaseUrl($linkType, false),
                    PHP_URL_PATH);
                $paths[] = parse_url($store->getBaseUrl($linkType, true),
                    PHP_URL_PATH);
            }
        }
        $paths = array_unique($paths);
        usort($paths, create_function('$a, $b',
            'return strlen( $b ) - strlen( $a );'));
        return array_values($paths);
    }

    /**
     * Format the URL exclusions for insertion in a regex. Admin frontname and
     * API are automatically added.
     *
     * @return string
     */
    protected function _getUrlExcludes() {
        $urls = Mage::getStoreConfig('turpentine_vcl/urls/url_blacklist');
        return implode('|', array_merge(array($this->_getAdminFrontname(), 'api'),
            Mage::helper('turpentine/data')->cleanExplode(PHP_EOL, $urls)));
    }

    /**
     * Get the default cache TTL from Magento config
     *
     * @return string
     */
    protected function _getDefaultTtl() {
        return Mage::helper('turpentine/varnish')->getDefaultTtl();
    }

    /**
     * Get the default backend configuration string
     *
     * @return string
     */
    protected function _getDefaultBackend() {
        $timeout = Mage::getStoreConfig('turpentine_vcl/backend/frontend_timeout');
        $default_options = array(
            'first_byte_timeout'    => $timeout.'s',
            'between_bytes_timeout' => $timeout.'s',
        );
        if (Mage::getStoreConfig('turpentine_vcl/backend/load_balancing') != 'no') {
            return $this->_vcl_director('default', $default_options);
        } else {
            return $this->_vcl_backend('default',
                Mage::getStoreConfig('turpentine_vcl/backend/backend_host'),
                Mage::getStoreConfig('turpentine_vcl/backend/backend_port'),
                $default_options);
        }
    }

    /**
     * Get the admin backend configuration string
     *
     * @return string
     */
    protected function _getAdminBackend() {
        $timeout = Mage::getStoreConfig('turpentine_vcl/backend/admin_timeout');
        $admin_options = array(
            'first_byte_timeout'    => $timeout.'s',
            'between_bytes_timeout' => $timeout.'s',
        );
        if (Mage::getStoreConfig('turpentine_vcl/backend/load_balancing') != 'no') {
            return $this->_vcl_director('admin', $admin_options);
        } else {
            return $this->_vcl_backend('admin',
                Mage::getStoreConfig('turpentine_vcl/backend/backend_host'),
                Mage::getStoreConfig('turpentine_vcl/backend/backend_port'),
                $admin_options);
        }
    }

    /**
     * Get the grace period for vcl_fetch
     *
     * This is curently hardcoded to 15 seconds, will be configurable at some
     * point
     *
     * @return string
     */
    protected function _getGracePeriod() {
        return Mage::getStoreConfig('turpentine_vcl/ttls/grace_period');
    }

    /**
     * Get whether debug headers should be enabled or not
     *
     * @return string
     */
    protected function _getEnableDebugHeaders() {
        return Mage::getStoreConfig('turpentine_varnish/general/varnish_debug')
            ? 'true' : 'false';
    }

    /**
     * Format the GET variable excludes for insertion in a regex
     *
     * @return string
     */
    protected function _getGetParamExcludes() {
        return implode('|', Mage::helper('turpentine/data')->cleanExplode(',',
            Mage::getStoreConfig('turpentine_vcl/params/get_params')));
    }

    protected function _getIgnoreGetParameters()
    {
        /** @var Nexcessnet_Turpentine_Helper_Data $helper */
        $helper = Mage::helper('turpentine');
        $ignoredParameters = $helper->cleanExplode(',', Mage::getStoreConfig('turpentine_vcl/params/ignore_get_params'));
        return implode('|', $ignoredParameters);
    }

    /**
     * @return boolean
     */
    protected function _sendUnModifiedUrlToBackend()
    {
        return Mage::getStoreConfigFlag('turpentine_vcl/params/transfer_unmodified_url');
    }

    /**
     * Get the Generate Session
     *
     * @return string
     */
    protected function _getGenerateSessionStart() {
        return Mage::getStoreConfig('turpentine_varnish/general/vcl_fix')
            ? '/* -- REMOVED' : '';
    }

    /**
     * Get the Generate Session
     *
     * @return string
     */
    protected function _getGenerateSessionEnd() {
        return Mage::getStoreConfig('turpentine_varnish/general/vcl_fix')
            ? '-- */' : '';
    }


    /**
     * Get the Generate Session
     *
     * @return string
     */
    protected function _getGenerateSession() {
        return Mage::getStoreConfigFlag('turpentine_varnish/general/vcl_fix')
            ? 'return (pipe);' : 'call generate_session;';
    }


    /**
     * Get the Generate Session Expires
     *
     * @return string
     */
    protected function _getGenerateSessionExpires() {
        return Mage::getStoreConfig('turpentine_varnish/general/vcl_fix')
            ? '# call generate_session_expires' : 'call generate_session_expires;';
    }

    /**
     * Get the Force Static Caching option
     *
     * @return string
     */
    protected function _getForceCacheStatic() {
        return Mage::getStoreConfig('turpentine_vcl/static/force_static')
            ? 'true' : 'false';
    }

    /**
     * Get the Force Static Caching option
     *
     * @return string
     */
    protected function _getSimpleHashStatic() {
        return Mage::getStoreConfig('turpentine_vcl/static/simple_hash')
            ? 'true' : 'false';
    }

    /**
     * Format the list of static cache extensions
     *
     * @return string
     */
    protected function _getStaticExtensions() {
        return implode('|', Mage::helper('turpentine/data')->cleanExplode(',',
            Mage::getStoreConfig('turpentine_vcl/static/exts')));
    }

    /**
     * Get the static caching TTL
     *
     * @return string
     */
    protected function _getStaticTtl() {
        return Mage::getStoreConfig('turpentine_vcl/ttls/static_ttl');
    }

    /**
     * Format the by-url TTL value list
     *
     * @return string
     */
    protected function _getUrlTtls() {
        $str = array();
        $configTtls = Mage::helper('turpentine/data')->cleanExplode(PHP_EOL,
            Mage::getStoreConfig('turpentine_vcl/ttls/url_ttls'));
        $ttls = array();
        foreach ($configTtls as $line) {
            $ttls[] = explode(',', trim($line));
        }
        foreach ($ttls as $ttl) {
            $str[] = sprintf('if (bereq.url ~ "%s%s") { set beresp.ttl = %ds; }',
                $this->getBaseUrlPathRegex(), $ttl[0], $ttl[1]);
        }
        $str = implode(' else ', $str);
        if ($str) {
            $str .= sprintf(' else { set beresp.ttl = %ds; }',
                $this->_getDefaultTtl());
        } else {
            $str = sprintf('set beresp.ttl = %ds;', $this->_getDefaultTtl());
        }
        return $str;
    }

    /**
     * Get the Enable Caching value
     *
     * @return string
     */
    protected function _getEnableCaching() {
        return Mage::helper('turpentine/varnish')->getVarnishEnabled() ?
            'true' : 'false';
    }

    /**
     * Get the list of allowed debug IPs
     *
     * @return array
     */
    protected function _getDebugIps() {
        return Mage::helper('turpentine/data')->cleanExplode(',',
            Mage::getStoreConfig('dev/restrict/allow_ips'));
    }

    /**
     * Get the list of crawler IPs
     *
     * @return array
     */
    protected function _getCrawlerIps() {
        return Mage::helper('turpentine/data')->cleanExplode(',',
            Mage::getStoreConfig('turpentine_vcl/backend/crawlers'));
    }

    /**
     * Get the regex formatted list of crawler user agents
     *
     * @return string
     */
    protected function _getCrawlerUserAgents() {
        return implode('|', Mage::helper('turpentine/data')
            ->cleanExplode(',',
                Mage::getStoreConfig(
                    'turpentine_vcl/backend/crawler_user_agents' )));
    }

    /**
     * Get the time to increase a cached objects TTL on cache hit (in seconds).
     *
     * This should be set very low since it gets added to every hit.
     *
     * @return string
     */
    protected function _getLruFactor() {
        return Mage::getStoreConfig('turpentine_vcl/ttls/lru_factor');
    }

    /**
     * Get the advanced session validation restrictions
     *
     * Note that if User-Agent Normalization is on then the normalized user-agent
     * is used for user-agent validation instead of the full user-agent
     *
     * @return string
     */
    protected function _getAdvancedSessionValidationTargets() {
        $validation = array();
        if (Mage::getStoreConfig('web/session/use_remote_addr')) {
            $validation[] = 'client.ip';
        }
        if (Mage::getStoreConfig('web/session/use_http_via')) {
            $validation[] = 'req.http.Via';
        }
        if (Mage::getStoreConfig('web/session/use_http_x_forwarded_for')) {
            $validation[] = 'req.http.X-Forwarded-For';
        }
        if (Mage::getStoreConfig(
                    'web/session/use_http_user_agent' ) &&
                ! Mage::getStoreConfig(
                    'turpentine_vcl/normalization/user_agent' )) {
            $validation[] = 'req.http.User-Agent';
        }
        return $validation;
    }

    /**
     * Remove empty and commented out lines from the generated VCL
     *
     * @param  string $dirtyVcl generated vcl
     * @return string
     */
    protected function _cleanVcl($dirtyVcl) {
        return implode(PHP_EOL,
            array_filter(
                Mage::helper('turpentine/data')
                    ->cleanExplode(PHP_EOL, $dirtyVcl),
                array($this, '_cleanVclHelper')
            )
        );
    }

    /**
     * Helper to filter out blank/commented lines for VCL cleaning
     *
     * @param  string $line
     * @return bool
     */
    protected function _cleanVclHelper($line) {
        return $line &&
            ((substr($line, 0, 1) != '#' &&
            substr($line, 0, 2) != '//') ||
            substr($line, 0, 8) == '#include');
    }

    /**
     * Format a VCL backend declaration
     *
     * @param  string $name    name of the backend
     * @param  string $host    backend host
     * @param  string $port    backend port
     * @param  array  $options options
     * @return string
     */
    protected function _vcl_backend($name, $host, $port, $options = array()) {
        $tpl = <<<EOS
backend {{name}} {
    .host = "{{host}}";
    .port = "{{port}}";

EOS;
        $vars = array(
            'host'  => $host,
            'port'  => $port,
            'name'  => $name,
        );
        $str = $this->_formatTemplate($tpl, $vars);
        foreach ($options as $key => $value) {
            $str .= sprintf('   .%s = %s;', $key, $value).PHP_EOL;
        }
        $str .= '}'.PHP_EOL;
        return $str;
    }

    /**
     * Format a VCL director declaration, for load balancing
     *
     * @param string $name           name of the director, also used to select config settings
     * @param array  $backendOptions options for each backend
     * @return string
     */
    protected function _vcl_director($name, $backendOptions) {
        $tpl = <<<EOS
director {{name}} round-robin {
{{backends}}
}
EOS;
        if ('admin' == $name && 'yes_admin' == Mage::getStoreConfig('turpentine_vcl/backend/load_balancing')) {
            $backendNodes = Mage::helper('turpentine/data')->cleanExplode(PHP_EOL,
                Mage::getStoreConfig('turpentine_vcl/backend/backend_nodes_admin'));
            $probeUrl = Mage::getStoreConfig('turpentine_vcl/backend/backend_probe_url_admin');
        } else {
            $backendNodes = Mage::helper('turpentine/data')->cleanExplode(PHP_EOL,
                Mage::getStoreConfig('turpentine_vcl/backend/backend_nodes'));
            $probeUrl = Mage::getStoreConfig('turpentine_vcl/backend/backend_probe_url');
        }
        $backends = '';
        foreach ($backendNodes as $backendNode) {
            $parts = explode(':', $backendNode, 2);
            $host = (empty($parts[0])) ? '127.0.0.1' : $parts[0];
            $port = (empty($parts[1])) ? '80' : $parts[1];
            $backends .= $this->_vcl_director_backend($host, $port, $probeUrl, $backendOptions);
        }
        $vars = array(
            'name' => $name,
            'backends' => $backends
        );
        return $this->_formatTemplate($tpl, $vars);
    }

    /**
     * Format a VCL backend declaration to put inside director
     *
     * @param string $host     backend host
     * @param string $port     backend port
     * @param string $probeUrl URL to check if backend is up
     * @param array  $options  extra options for backend
     * @return string
     */
    protected function _vcl_director_backend($host, $port, $probeUrl = '', $options = array()) {
        $tpl = <<<EOS
    {
        .backend = {
            .host = "{{host}}";
            .port = "{{port}}";
{{probe}}

EOS;
        $vars = array(
            'host'  => $host,
            'port'  => $port,
            'probe' => ''
        );
        if ( ! empty($probeUrl)) {
            $vars['probe'] = $this->_vcl_get_probe($probeUrl);
        }
        $str = $this->_formatTemplate($tpl, $vars);
        foreach ($options as $key => $value) {
            $str .= sprintf('            .%s = %s;', $key, $value).PHP_EOL;
        }
        $str .= <<<EOS
        }
    }
EOS;
        return $str;
    }

    /**
     * Format a VCL probe declaration to put in backend which is in director
     *
     * @param string $probeUrl URL to check if backend is up
     * @return string
     */
    protected function _vcl_get_probe($probeUrl) {
        $urlParts = parse_url($probeUrl);
        if (empty($urlParts)) {
            // Malformed URL
            return '';
        } else {
            $tpl = <<<EOS
            .probe = {
                .request =
                    "GET {{probe_path}} HTTP/1.1"
                    "Host: {{probe_host}}"
                    "Connection: close";
            }
EOS;
            $vars = array(
                'probe_host' => $urlParts['host'],
                'probe_path' => $urlParts['path']
            );
            return $this->_formatTemplate($tpl, $vars);
        }
    }

    /**
     * Format a VCL ACL declaration
     *
     * @param  string $name  ACL name
     * @param  array  $hosts list of hosts to add to the ACL
     * @return string
     */
    protected function _vcl_acl($name, array $hosts) {
        $tpl = <<<EOS
acl {{name}} {
    {{hosts}}
}
EOS;
        $fmtHost = create_function('$h', 'return sprintf(\'"%s";\',$h);');
        $vars = array(
            'name'  => $name,
            'hosts' => implode("\n    ", array_map($fmtHost, $hosts)),
        );
        return $this->_formatTemplate($tpl, $vars);
    }

    /**
     * Get the User-Agent normalization sub routine
     *
     * @return string
     */
    protected function _vcl_sub_normalize_user_agent() {
        /**
         * Mobile regex from
         * @link http://magebase.com/magento-tutorials/magento-design-exceptions-explained/
         */
        $tpl = <<<EOS
if (req.http.User-Agent ~ "iP(?:hone|ad|od)|BlackBerry|Palm|Googlebot-Mobile|Mobile|mobile|mobi|Windows Mobile|Safari Mobile|Android|Opera (?:Mini|Mobi)") {
        set req.http.X-Normalized-User-Agent = "mobile";
    } else {
        set req.http.X-Normalized-User-Agent = "other";
    }

EOS;
        return $tpl;
    }

    /**
     * Get the Accept-Encoding normalization sub routine
     *
     * @return string
     */
    protected function _vcl_sub_normalize_encoding() {
        $tpl = <<<EOS
if (req.http.Accept-Encoding) {
        if (req.http.Accept-Encoding ~ "\*|gzip") {
            set req.http.Accept-Encoding = "gzip";
        } else if (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            # unknown algorithm
            unset req.http.Accept-Encoding;
        }
    }

EOS;
        return $tpl;
    }

    /**
     * Get the Host normalization sub routine
     *
     * @return string
     */
    protected function _vcl_sub_normalize_host() {
        $tpl = <<<EOS
set req.http.Host = "{{normalize_host_target}}";

EOS;
        return $this->_formatTemplate($tpl, array(
            'normalize_host_target' => $this->_getNormalizeHostTarget() ));
    }

    /**
     * Get the hostname for cookie normalization
     *
     * @return string
     */
    protected function _getNormalizeCookieTarget() {
        return trim(Mage::getStoreConfig(
            'turpentine_vcl/normalization/cookie_target' ));
    }

    /**
     * Get the regex for cookie normalization
     *
     * @return string
     */
    protected function _getNormalizeCookieRegex() {
        return trim(Mage::getStoreConfig(
            'turpentine_vcl/normalization/cookie_regex' ));
    }

    /**
     * Get the allowed IPs when in maintenance mode
     *
     * @return string
     */
    protected function _vcl_sub_maintenance_allowed_ips() {
        if (( ! $this->_getDebugIps()) || ! Mage::getStoreConfig('turpentine_vcl/maintenance/custom_vcl_synth')) {
            return false;
        }

        switch (Mage::getStoreConfig('turpentine_varnish/servers/version')) {
            case 4.0:
                $tpl = <<<EOS
if (req.http.X-Forwarded-For) {
    if (req.http.X-Forwarded-For !~ "{{debug_ips}}") {
        return (synth(999, "Maintenance mode"));
    }
}
else {
    if (client.ip !~ debug_acl) {
        return (synth(999, "Maintenance mode"));
    }
}

EOS;
                break;
            default:
                $tpl = <<<EOS
if (req.http.X-Forwarded-For) {
    if(req.http.X-Forwarded-For !~ "{{debug_ips}}") {
        error 503;
    }
} else {
    if (client.ip !~ debug_acl) {
        error 503;
    }
}
EOS;
        }

        return $this->_formatTemplate($tpl, array(
            'debug_ips' => Mage::getStoreConfig('dev/restrict/allow_ips') ));
    }

    /**
     * When using Varnish as front door listen on port 80 and Nginx/Apache listen on port 443 for HTTPS, the fix will keep the url parameters when redirect from HTTP to HTTPS.
     *
     * @return string
     */
    protected function _vcl_sub_https_redirect_fix() {
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $baseUrl = str_replace(array('http://','https://'), '', $baseUrl);
        $baseUrl = rtrim($baseUrl,'/');
        
        switch (Mage::getStoreConfig('turpentine_varnish/servers/version')) {
            case 4.0:
                $tpl = <<<EOS
if ( (req.http.host ~ "^(?i)www.$baseUrl" || req.http.host ~ "^(?i)$baseUrl") && req.http.X-Forwarded-Proto !~ "(?i)https") {
        return (synth(750, ""));
    }
EOS;
                break;
            default:
                $tpl = <<<EOS
if ( (req.http.host ~ "^(?i)www.$baseUrl" || req.http.host ~ "^(?i)$baseUrl") && req.http.X-Forwarded-Proto !~ "(?i)https") {
        error 750 "https://" + req.http.host + req.url;
    }
EOS;
        }

        return $tpl;
    }

    /**
     * Get the allowed IPs when in maintenance mode
     *
     * @return string
     */
    protected function _vcl_sub_synth()
    {
        if (( ! $this->_getDebugIps()) || ! Mage::getStoreConfig('turpentine_vcl/maintenance/custom_vcl_synth')) {
            return false;
        }

        switch (Mage::getStoreConfig('turpentine_varnish/servers/version')) {
            case 4.0:
                $tpl = <<<EOS
sub vcl_synth {
    if (resp.status == 999) {
        set resp.status = 404;
        set resp.http.Content-Type = "text/html; charset=utf-8";
        synthetic({"{{vcl_synth_content}}"});
        return (deliver);
    }
    return (deliver);
}

EOS;
                break;
            default:
                $tpl = <<<EOS
sub vcl_error {
    set obj.http.Content-Type = "text/html; charset=utf-8";
    synthetic {"{{vcl_synth_content}}"};
    return (deliver);
}
EOS;
        }

        return $this->_formatTemplate($tpl, array(
            'vcl_synth_content' => Mage::getStoreConfig('turpentine_vcl/maintenance/custom_vcl_synth')));
    }

    /**
     * vcl_synth for fixing https
     *
     * @return string
     */
    protected function _vcl_sub_synth_https_fix()
    {
        $tpl = $this->_vcl_sub_synth();

        if(!$tpl){
            $tpl = <<<EOS
sub vcl_synth {
    if (resp.status == 750) {
        set resp.status = 301;
        set resp.http.Location = "https://" + req.http.host + req.url;
        return(deliver);
    }
}
EOS;
        }else{
            $tpl_750 = '
sub vcl_synth {
    if (resp.status == 750) {
        set resp.status = 301;
        set resp.http.Location = "https://" + req.http.host + req.url;
        return(deliver);
    }';

        $tpl = str_ireplace('sub vcl_synth {', $tpl_750, $tpl);
        }

        return $tpl;
    }



    /**
     * Build the list of template variables to apply to the VCL template
     *
     * @return array
     */
    protected function _getTemplateVars() {
        $vars = array(
            'default_backend'   => $this->_getDefaultBackend(),
            'admin_backend'     => $this->_getAdminBackend(),
            'admin_frontname'   => $this->_getAdminFrontname(),
            'normalize_host_target' => $this->_getNormalizeHostTarget(),
            'url_base_regex'    => $this->getBaseUrlPathRegex(),
            'allowed_hosts_regex'   => $this->getAllowedHostsRegex(),
            'url_excludes'  => $this->_getUrlExcludes(),
            'get_param_excludes'    => $this->_getGetParamExcludes(),
            'get_param_ignored' => $this->_getIgnoreGetParameters(),
            'default_ttl'   => $this->_getDefaultTtl(),
            'enable_get_excludes'   => ($this->_getGetParamExcludes() ? 'true' : 'false'),
            'enable_get_ignored' => ($this->_getIgnoreGetParameters() ? 'true' : 'false'),
            'send_unmodified_url' => ($this->_sendUnModifiedUrlToBackend() ? 'true' : 'false'),
            'debug_headers' => $this->_getEnableDebugHeaders(),
            'grace_period'  => $this->_getGracePeriod(),
            'force_cache_static'    => $this->_getForceCacheStatic(),
            'simple_hash_static'    => $this->_getSimpleHashStatic(),
            'generate_session_expires'    => $this->_getGenerateSessionExpires(),
            'generate_session'    => $this->_getGenerateSession(),
            'generate_session_start'    => $this->_getGenerateSessionStart(),
            'generate_session_end'    => $this->_getGenerateSessionEnd(),
            'static_extensions' => $this->_getStaticExtensions(),
            'static_ttl'    => $this->_getStaticTtl(),
            'url_ttls'      => $this->_getUrlTtls(),
            'enable_caching'    => $this->_getEnableCaching(),
            'crawler_acl'   => $this->_vcl_acl('crawler_acl',
                $this->_getCrawlerIps()),
            'esi_cache_type_param'  =>
                Mage::helper('turpentine/esi')->getEsiCacheTypeParam(),
            'esi_method_param'  =>
                Mage::helper('turpentine/esi')->getEsiMethodParam(),
            'esi_ttl_param' => Mage::helper('turpentine/esi')->getEsiTtlParam(),
            'secret_handshake'  => Mage::helper('turpentine/varnish')
                ->getSecretHandshake(),
            'crawler_user_agent_regex'  => $this->_getCrawlerUserAgents(),
            // 'lru_factor'    => $this->_getLruFactor(),
            'debug_acl'     => $this->_vcl_acl('debug_acl',
                $this->_getDebugIps()),
            'custom_c_code' => file_get_contents(
                $this->_getVclTemplateFilename(self::VCL_CUSTOM_C_CODE_FILE) ),
            'esi_private_ttl'   => Mage::helper('turpentine/esi')
                ->getDefaultEsiTtl(),
        );

        if ((bool) Mage::getStoreConfig('turpentine_vcl/urls/bypass_cache_store_url')) {
            $vars['allowed_hosts'] = $this->_vcl_sub_allowed_hosts_regex();
        }

        if (Mage::getStoreConfig('turpentine_vcl/normalization/encoding')) {
            $vars['normalize_encoding'] = $this->_vcl_sub_normalize_encoding();
        }
        if (Mage::getStoreConfig('turpentine_vcl/normalization/user_agent')) {
            $vars['normalize_user_agent'] = $this->_vcl_sub_normalize_user_agent();
        }
        if (Mage::getStoreConfig('turpentine_vcl/normalization/host')) {
            $vars['normalize_host'] = $this->_vcl_sub_normalize_host();
        }
        if (Mage::getStoreConfig('turpentine_vcl/normalization/cookie_regex')) {
            $vars['normalize_cookie_regex'] = $this->_getNormalizeCookieRegex();
        }
        if (Mage::getStoreConfig('turpentine_vcl/normalization/cookie_target')) {
            $vars['normalize_cookie_target'] = $this->_getNormalizeCookieTarget();
        }

        if (Mage::getStoreConfig('turpentine_vcl/maintenance/enable')) {
            // in vcl_recv set the allowed IPs otherwise load the vcl_error (v3)/vcl_synth (v4)
            $vars['maintenance_allowed_ips'] = $this->_vcl_sub_maintenance_allowed_ips();
            // set the vcl_error from Magento database
            $vars['vcl_synth'] = $this->_vcl_sub_synth();
        }
        
        if (Mage::getStoreConfig('turpentine_varnish/general/https_redirect_fix')) {
            $vars['https_redirect'] = $this->_vcl_sub_https_redirect_fix();
            if(Mage::getStoreConfig('turpentine_varnish/servers/version') == '4.0'){
                $vars['vcl_synth'] = $this->_vcl_sub_synth_https_fix();
            }
        }

        foreach (array('','top') as $position) {
            $customIncludeFile = $this->_getCustomIncludeFilename($position);
            if (is_readable($customIncludeFile)) {
                $key = 'custom_vcl_include';
                $key .= ($position) ? '_'.$position : '';
                $vars[$key] = file_get_contents($customIncludeFile);
            }
        }

        return $vars;
    }
}
