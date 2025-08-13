<?php
/**
 * Plugin Name: Simple Office 365 SMTP
 * Description: Secure SMTP configuration for Office 365
 * Version: 1.0
 * Author: ᴎξXṵ§
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SimpleOffice365SMTP {
    
    public function __construct() {
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    public function configure_smtp($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $this->get_option('simple_smtp_host', 'smtp.office365.com');
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = $this->get_option('simple_smtp_port', 587);
        $phpmailer->SMTPSecure = $this->get_option('simple_smtp_encryption', 'tls');
        $phpmailer->Username = $this->get_option('simple_smtp_username');
        $phpmailer->Password = $this->decrypt_password($this->get_option('simple_smtp_password'));
        $phpmailer->From = $this->get_option('simple_smtp_from_email');
        $phpmailer->FromName = $this->get_option('simple_smtp_from_name', get_bloginfo('name'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Simple SMTP Settings',
            'Simple SMTP',
            'manage_options',
            'simple-smtp',
            array($this, 'options_page')
        );
    }
    
    public function settings_init() {
        register_setting('simple_smtp', 'simple_smtp_host', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('simple_smtp', 'simple_smtp_port', array('sanitize_callback' => array($this, 'validate_port')));
        register_setting('simple_smtp', 'simple_smtp_encryption', array('sanitize_callback' => array($this, 'validate_encryption')));
        register_setting('simple_smtp', 'simple_smtp_username', array('sanitize_callback' => 'sanitize_email'));
        register_setting('simple_smtp', 'simple_smtp_password', array('sanitize_callback' => array($this, 'encrypt_password')));
        register_setting('simple_smtp', 'simple_smtp_from_email', array('sanitize_callback' => 'sanitize_email'));
        register_setting('simple_smtp', 'simple_smtp_from_name', array('sanitize_callback' => 'sanitize_text_field'));
    }
    
    public function validate_port($input) {
        $input = intval($input);
        return ($input >= 1 && $input <= 65535) ? $input : 587;
    }
    
    public function validate_encryption($input) {
        return in_array($input, array('tls', 'ssl')) ? $input : 'tls';
    }
    
    public function encrypt_password($password) {
        if (empty($password)) {
            return $this->get_option('simple_smtp_password');
        }
        
        if (function_exists('sodium_crypto_secretbox')) {
            $key = substr(wp_salt('auth'), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $encrypted = sodium_crypto_secretbox($password, $nonce, $key);
            return base64_encode($nonce . $encrypted);
        }
        
        return $password;
    }
    
    public function decrypt_password($encrypted) {
        if (empty($encrypted)) {
            return '';
        }
        
        if (function_exists('sodium_crypto_secretbox_open')) {
            try {
                $decoded = base64_decode($encrypted);
                if ($decoded === false) {
                    return $encrypted;
                }
                
                $key = substr(wp_salt('auth'), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
                $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
                
                $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
                if ($decrypted === false) {
                    return $encrypted;
                }
                
                return $decrypted;
            } catch (Exception $e) {
                return $encrypted;
            }
        }
        
        return $encrypted;
    }
    
    public function get_option($option_name, $default = '') {
        return get_option($option_name, $default);
    }
    
    public function options_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Process test email
        if (isset($_POST['send_test']) && !empty($_POST['test_email']) && check_admin_referer('simple_smtp_test_email')) {
            $this->handle_test_email();
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('simple_smtp');
                do_settings_sections('simple_smtp');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">SMTP Host</th>
                        <td><input type="text" name="simple_smtp_host" value="<?php echo esc_attr($this->get_option('simple_smtp_host', 'smtp.office365.com')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Port</th>
                        <td><input type="number" name="simple_smtp_port" value="<?php echo esc_attr($this->get_option('simple_smtp_port', '587')); ?>" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Encryption</th>
                        <td>
                            <select name="simple_smtp_encryption">
                                <option value="tls" <?php selected($this->get_option('simple_smtp_encryption'), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected($this->get_option('simple_smtp_encryption'), 'ssl'); ?>>SSL</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Username</th>
                        <td><input type="email" name="simple_smtp_username" value="<?php echo esc_attr($this->get_option('simple_smtp_username')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Password</th>
                        <td>
                            <input type="password" name="simple_smtp_password" value="" class="regular-text" placeholder="<?php echo $this->get_option('simple_smtp_password') ? '••••••••••••••••' : ''; ?>" />
                            <p class="description">Leave blank to keep existing password.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">From Email</th>
                        <td><input type="email" name="simple_smtp_from_email" value="<?php echo esc_attr($this->get_option('simple_smtp_from_email')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">From Name</th>
                        <td><input type="text" name="simple_smtp_from_name" value="<?php echo esc_attr($this->get_option('simple_smtp_from_name', get_bloginfo('name'))); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2>Test Email</h2>
            <form method="post">
                <?php wp_nonce_field('simple_smtp_test_email'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Send To</th>
                        <td>
                            <input type="email" name="test_email" placeholder="Enter test email address" required class="regular-text" />
                            <p class="submit">
                                <input type="submit" name="send_test" value="Send Test Email" class="button button-primary" />
                            </p>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }
    
    public function handle_test_email() {
        // Rate limiting
        $last_test = get_option('simple_smtp_last_test', 0);
        if (time() - $last_test < 30) {
            add_settings_error(
                'simple_smtp_messages',
                'simple_smtp_rate_limit',
                'Please wait 30 seconds between test emails.',
                'error'
            );
            return;
        }
        update_option('simple_smtp_last_test', time());
        
        // Sanitize email
        $to = sanitize_email($_POST['test_email']);
        if (!is_email($to)) {
            add_settings_error(
                'simple_smtp_messages',
                'simple_smtp_invalid_email',
                'Invalid email address.',
                'error'
            );
            return;
        }
        
        // Send test email
        $subject = 'SMTP Test from ' . get_bloginfo('name');
        $message = 'This is a test email from your WordPress site using Simple Office 365 SMTP plugin.';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Enable debug for test emails
        add_action('phpmailer_init', array($this, 'enable_debug_for_test'));
        
        // Send email
        try {
            $result = wp_mail($to, $subject, $message, $headers);
            
            if ($result) {
                add_settings_error(
                    'simple_smtp_messages',
                    'simple_smtp_test_success',
                    'Test email sent successfully!',
                    'success'
                );
            } else {
                global $phpmailer_error;
                $error_message = !empty($phpmailer_error) ? $phpmailer_error : 'Unknown error';
                add_settings_error(
                    'simple_smtp_messages',
                    'simple_smtp_test_failed',
                    'Test email failed: ' . $error_message,
                    'error'
                );
            }
        } catch (Exception $e) {
            add_settings_error(
                'simple_smtp_messages',
                'simple_smtp_test_exception',
                'Exception occurred: ' . $e->getMessage(),
                'error'
            );
        }
        
        // Remove debug action
        remove_action('phpmailer_init', array($this, 'enable_debug_for_test'));
    }
    
    public function enable_debug_for_test($phpmailer) {
        // Store errors for display
        $phpmailer->SMTPDebug = 1;
        $phpmailer->Debugoutput = function($str, $level) {
            global $phpmailer_error;
            $phpmailer_error = $str;
        };
    }
}

// Initialize the plugin
$simple_office365_smtp = new SimpleOffice365SMTP();

// Add uninstall hook to clean up options
register_uninstall_hook(__FILE__, 'simple_smtp_uninstall');
function simple_smtp_uninstall() {
    delete_option('simple_smtp_host');
    delete_option('simple_smtp_port');
    delete_option('simple_smtp_encryption');
    delete_option('simple_smtp_username');
    delete_option('simple_smtp_password');
    delete_option('simple_smtp_from_email');
    delete_option('simple_smtp_from_name');
    delete_option('simple_smtp_last_test');
}
