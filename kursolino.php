<?php
/**
 * Plugin Name: Kursolino
 * Plugin URI: https://www.kursolino.de/wordpress-plugin/
 * Description: Plugin to integrate your content from the course management software of Kursolino.
 * Text Domain: kursolino_plugin
 * Domain Path: /languages
 * Author: Kursolino
 * Author URI: https://www.kursolino.de
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly.

class Kursolino_Plugin
{
    /**
     * error messages
     */
    const messages = array(
        -1 => 'Your login data are invalid.',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Your login data are invalid or you are not the owner of this Kursolino account.',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone'
    );

    /**
     * base api url
     * @var string string
     */
    protected $api = 'https://api.kurs.software';

    /**
     * Holds the values to be used in the fields callbacks
     * @var array $options
     */
    protected $options;

	/**
	 * @var string $baseDir
	 */
    protected $baseDir;

    /**
     * Register Plugin
     * @uses load_assets
     * @uses load_languages
     * @uses add_plugin_page
     * @uses add_meta_box
     * @uses plugin_page_init
     */
    public function __construct()
    {
        // set base dir
        $this->baseDir = basename(dirname(__FILE__));
        if($this->baseDir == 'trunk') {
	        $this->baseDir = basename(dirname(dirname(__FILE__) . '../'));

        }

        // register actions
        add_action('admin_enqueue_scripts', array($this, 'load_assets'));
        add_action('plugins_loaded', array($this, 'load_languages'));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'plugin_page_init'));

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_shortcode('kursolino', array($this, 'shortcode'));

        // register ajax
        add_action('wp_ajax_kursolino_ajax', array($this, 'ajax'));

        // read options
        $this->options = get_option('kursolino_settings');
    }

    public function load_languages()
    {
        load_plugin_textdomain($this->ns(), false, $this->baseDir . '/languages/');
    }

    /**
     * Load Assets
     */
    public function load_assets()
    {
        $screen = get_current_screen();

        if (in_array($screen->base, array('post', 'page', 'toplevel_page_kursolino'))) {
            // css
            wp_register_style('kursolino_css', plugins_url('/assets/css/style.css', __FILE__), array(), '1.0', 'all');
            wp_enqueue_style('kursolino_css');

            // js
            wp_register_script('kursolino_script', plugins_url('/assets/js/script.js', __FILE__), array('jquery'));
            wp_enqueue_script('kursolino_script');
        }
    }

    /**
     * Add plugin page
     */
    public function add_plugin_page()
    {
        $icon = 'data:image/svg+xml;base64,' . base64_encode('<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 220 220" xml:space="preserve" width="146.86421" height="227.5966"><g transform="translate(0.0448341,-1.5721078)"><path class="st0" d="m 142.23748,6.8440353 -3.79,-3.13 c -7.59999,-6.26 -18.41997,-6.74 -26.53995,-1.19 L 48.597545,45.754035 c -6.040001,4.13 -9.650001,10.96 -9.650001,18.28 v 3.68 c 0,5.5 0,14.5 0,20 v 59.970005 c 0,6.57 2.92,12.79 7.960001,17 l 1.88,1.57 c 8.79,7.33 21.72,6.74 29.81,-1.35 l 16.57,-16.57 c 8.639995,-8.64 8.639995,-22.65 0,-31.29 v 0 c -8.8,-8.8 -8.61,-23.130005 0.42,-31.700005 l 47.809935,-45.36 c 9.65,-9.16 9.1,-24.69 -1.16,-33.1399997 z" fill="#ffffff" /></g><g transform="translate(38.992378,-4.5680725)"><path d="m 43.63,221.16 3.79,3.12 c 7.6,6.26 18.42,6.74 26.54,1.19 l 63.3,-43.23 c 6.04,-4.12 9.65,-10.96 9.65,-18.27 v -3.68 c 0,-5.5 0,-14.5 0,-20 V 80.33 c 0,-6.57 -2.92,-12.79 -7.96,-17 l -1.88,-1.57 c -8.79,-7.33 -21.72,-6.74 -29.81,1.35 L 90.69,79.68 c -8.64,8.64 -8.64,22.65 0,31.29 v 0 c 8.8,8.8 8.61,23.13 -0.42,31.7 l -47.81,45.36 c -9.64,9.15 -9.1,24.68 1.17,33.13 z" fill="#ffffff" /></g></svg>');
        add_menu_page(__('Kursolino', $this->ns()), __('Kursolino', $this->ns()), 'manage_options', 'kursolino', array($this, 'settings_form'), $icon, 999);
    }

    /**
     * Add meta box to post editor
     */
    public function add_meta_box()
    {
        add_meta_box('kursolino-generator', __('Kursolino Shortcode Generator', $this->ns()), array($this, 'meta_box_contents'), 'post');
        add_meta_box('kursolino-generator', __('Kursolino Shortcode Generator', $this->ns()), array($this, 'meta_box_contents'), 'page');
    }

    function meta_box_contents($post)
    {
        ?>
        <div id="kursolino_meta_box" class="kursolino_meta_box">
            <p class="meta-options kursolino_meta_field">
                <label for="kursolino_iframe_module"><?php _e('Module', $this->ns()); ?></label>
                <select id="kursolino_iframe_module" name="module">
                    <option value=""><?php _e('Please Choose...', $this->ns()); ?></option>
                </select>
            </p>
        </div>
        <div id="kursolino_shortcode" class="kursolino_meta_box">
            <p class="meta-options kursolino_meta_field">
                <label for="kursolino_iframe_module"><?php _e('Shortcode', $this->ns()); ?></label>
                <input type="text" value=""/>
            </p>
        </div>
        <?php
    }

    /**
     * Settings Form
     */
    public function settings_form()
    {
        $this->detect_admin_notice();

        ?>
        <div id="kursolino-options" class="wrap">
            <h1><?php _e('Kursolino Wordpress Plugin', $this->ns()) ?></h1>
            <?php if ($this->options['token']): ?>
                <?php $this->license_info(); ?>
            <?php else: ?>
                <form method="post" action="options.php">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields('kursolino');
                    do_settings_sections('kursolino-settings');
                    submit_button(__('Sign In', $this->ns()));
                    ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Register Plugin Settings
     */
    public function plugin_page_init()
    {
        // register settings
        register_setting(
            'kursolino',
            'kursolino_settings',
            array($this, 'login')
        );

        // register sections
        add_settings_section(
            'kursolino_login',
            __('Login', $this->ns()),
            array($this, 'print_section_info'),
            'kursolino-settings'
        );

        // register input fields
        add_settings_field(
            'token',
            'Token',
            array($this, 'input_token'),
            'kursolino-settings',
            'kursolino_login'
        );
        add_settings_field(
            'url',
            'URL',
            array($this, 'input_url'),
            'kursolino-settings',
            'kursolino_login'
        );

        add_settings_field(
            'email',
            __('E-Mail Address', $this->ns()),
            array($this, 'input_email'),
            'kursolino-settings',
            'kursolino_login'
        );

        add_settings_field(
            'password',
            __('Password', $this->ns()),
            array($this, 'input_password'),
            'kursolino-settings',
            'kursolino_login'
        );
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        echo '<div class="section">';
        _e('Please login with your Kursolino login credentials.', $this->ns());
        echo '<br />';
        echo sprintf(
            __('You don\'t have an account yet? Register %s for free!', $this->ns()),
            '<a href="https://www.kursolino.de/registrieren/" target="_blank">' . __('here', $this->ns()) . '</a>'
        );
        echo '</div>';
    }

    /**
     * <input> Token
     */
    public function input_token()
    {
        printf(
            '<input type="text" id="' . $this->ns() . 'token" name="kursolino_settings[token]" value="%s" />',
            isset($this->options['token']) ? esc_attr($this->options['token']) : ''
        );

	    echo '<script>jQuery("#' . $this->ns() . 'token").closest("tr").hide();</script>';
    }

    /**
     * <input> Token
     */
    public function input_url()
    {
        printf(
            '<input type="url" id="' . $this->ns() . 'url" name="kursolino_settings[url]" value="%s" />',
            isset($this->options['url']) ? esc_attr($this->options['url']) : ''
        );

	    echo '<script>jQuery("#' . $this->ns() . 'url").closest("tr").hide();</script>';
    }

    /**
     * <input> E-Mail Address
     */
    public function input_email()
    {
        printf(
            '<input type="email" id="' . $this->ns() . 'email" name="kursolino_settings[email]" value="%s" />',
            isset($this->options['email']) ? esc_attr($this->options['email']) : ''
        );
    }

    /**
     * <input> Password
     */
    public function input_password()
    {
        printf(
            '<input type="password" id="' . $this->ns() . 'password" name="kursolino_settings[password]" value="%s" />',
            isset($this->options['password']) ? esc_attr($this->options['password']) : ''
        );
    }

    /**
     * Login & validate token & save each setting field
     * @param array $inputs
     * @return array
     */
    public function login($inputs)
    {
        // sanitize values
        foreach ($inputs AS &$input) {
            $input = sanitize_text_field($input);
        }

        // reset data
        if (isset($inputs['reset'])) {
            unset($inputs['reset']);
            return $inputs;
        }

        // simple data validation
        if (isset($inputs['email']) && is_email($inputs['email'])
            && isset($inputs['password']) && strlen($inputs['password']) > 3) {

            // get login token
            $response = $this->api('login', $inputs);

            // get api token
            $token = isset($response['token']) ? $response['token'] : '';

            // validate token length
            if (strlen($token) == 32) {
                $inputs['token'] = $token;
                $inputs['url'] = $response['url'];
            } else {
                $this->redirect(-2);
            }
            return $inputs;
        }

        // fallback
        $this->redirect(-1);
        return array();
    }

    /**
     * print license info by current token
     */
    public function license_info()
    {
        // get license data by token
        $response = $this->api('info');

        $this->admin_notice(__('You can now access and integrate your contents from Kursolino on editing a page.', $this->ns()), 'info');
        ?>
        <table class="wp-list-table license widefat striped">
            <thead>
            <tr>
                <th colspan="3">
                    <b><?php _e('Your Kursolino Account', $this->ns()); ?></b>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><b><?php _e('Domain', $this->ns()); ?></b></td>
                <td>
                    <a href="<?php echo $response['url']; ?>" target="_blank">
                        <?php echo $response['domain']; ?>
                    </a>
                </td>
                <td style="width:1%">
                    <a href="<?php echo $response['url']; ?>/admin/" target="_blank"
                       class="button button-small button-primary">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Kursolino Backend', $this->ns()); ?>
                    </a>
                </td>
            </tr>
            <tr>
                <td><b><?php _e('Owner', $this->ns()); ?></b></td>
                <td colspan="2"><?php echo $response['owner']; ?></td>
            </tr>
            <tr>
                <td><b><?php _e('Status', $this->ns()); ?></b></td>
                <td colspan="2"
                    style="color:<?php echo $response['status_color']; ?>"><?php echo $response['status']; ?></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('kursolino');
                        ?>
                        <input type="hidden" name="kursolino_settings[reset]" value=""/>
                        <input type="hidden" name="kursolino_settings[token]" value=""/>
                        <input type="hidden" name="kursolino_settings[email]" value=""/>
                        <input type="hidden" name="kursolino_settings[password]" value=""/>
                        <?php submit_button(__('Re-Login', $this->ns()), 'small') ?>
                    </form>
                </td>
            </tr>
            </tfoot>
        </table>
        <?php
    }

    /**
     * Shortcode Handler [kursolino module="courses|blogs|contacts|..." id="1"]
     * @param array $attributes
     * @return string
     */
    public function shortcode($attributes)
    {
        // validate attributes
        $params = array();
        foreach ($attributes AS $key => $value) {
            if (strstr($key, '--')) {
                list($k) = explode('--', $key, 2);
                if (!isset($params[$k])) {
                    $params[$k] = array();
                }
                $params[$k][] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        // build iframe url
        $src = $this->options['url'];
        $src .= '/iframe/?' . http_build_query($params);

        // build iframe
        $iframe = '';

        // build iframe markup
        $iframe .= '<iframe class="kursolino_frame" width="100%" height="500" src="' . $src . '" frameborder="0"></iframe>';

        // load iframe assets
	    wp_register_script('kursolino_iframe', plugins_url('/assets/js/iframe.min.js', __FILE__));
	    wp_enqueue_script('kursolino_iframe');

        // replace shortcode with iframe
        ob_start();
        echo do_shortcode($iframe);
        return ob_get_clean();
    }

    /**
     * redirect with message code
     * @param int|bool $message_code
     */
    protected function redirect($message_code = 0)
    {
        $screen = get_current_screen();
        $option_page = sanitize_text_field(isset($_POST['option_page']) ? $_POST['option_page'] : '');

        if ($screen->base == 'toplevel_page_kursolino' || $option_page == 'kursolino'
        ) {
            wp_redirect(admin_url('admin.php?page=kursolino' . (is_bool($message_code) ? '' : '&m=' . $message_code)));
            exit;
        }
    }

    /**
     * render admin notice
     * @param string $message
     * @param string $type
     */
    protected function admin_notice($message = '', $type = 'success')
    {
        ?>
        <div class="notice notice-<?php echo $type; ?> is-dismissible">
            <p><?php _e($message, $this->ns()); ?></p>
        </div>
        <?php
    }

    /**
     * detect admin notice by message code $_GET['m']
     */
    protected function detect_admin_notice()
    {
        if (!$this->options['token'] && isset($_GET['m'])) {
            $message_id = sanitize_text_field($_GET['m']);
            if (isset(self::messages[$message_id])) {
                $type = $message_id < 0 || $message_id > 400 ? 'error' : 'success';
                $this->admin_notice(__(self::messages[$message_id], $this->ns()), $type);
            } else {
                $this->admin_notice(__('Unknown Error Code', $this->ns()) . ': ' . $message_id, 'error');
            }
        }
    }

    /**
     * Ajax Handler
     */
    public function ajax()
    {
        $method = sanitize_text_field(isset($_POST['method']) ? $_POST['method'] : '');

        switch ($method):
            case 'get-modules':
                wp_send_json($this->api('moduleoptions'));
                break;
            case 'get-module-options':
                $module = sanitize_text_field($_POST['module']);
                wp_send_json($this->api('moduleoptions', array(
                    'module' => $module
                )));
                break;
        endswitch;

        // fallback: empty response
        wp_send_json(array());
    }

    /**
     * Kursolino API Wrapper
     * @param $method
     * @param array $data
     * @return array|mixed
     */
    protected function api($method, $data = array())
    {
        // append token, if present
        if ($token = $this->options['token']) {
            $data['token'] = $token;
        }

        // send api request & get response
        $response = wp_remote_post($this->api . '/' . $method . '/', array(
            'timeout' => 20,
            'blocking' => true,
            'body' => $data
        ));

        // validate response code
        if ($response['response']['code'] != 200) {
            $this->redirect($response['response']['code']);
        }

        // decode json response to array
        $json_data = @json_decode($response['body'], true);

        // return data
        if (is_array($json_data)) {
            return $json_data;
        }

        // fallback
        return array();
    }

    /**
     * get namespace
     * @return string
     */
    protected function ns()
    {
        return strtolower(__CLASS__);
    }
}

new Kursolino_Plugin();