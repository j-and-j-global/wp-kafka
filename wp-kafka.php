<?php
/**
 * Plugin Name:     WP Kafka
 * Description:     Push stuff into the thing
 * Author:          jspc
 * Text Domain:     wp-kafka
 * Domain Path:     /languages
 * Version:         0.0.1
 *
 * @package         Wp_Kafka
 */

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

use Kafka\Config;
use Kafka\Producer;
use Kafka\ProducerConfig;

add_action('publish_post', 'push_to_kafka', 10, 2);

function push_to_kafka($id, $post) {
    $logger = new Logger('my_logger');
    // Now add some handlers
    $logger->pushHandler(new ErrorLogHandler());

    try {
        $config = \Kafka\ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList(get_option('kafka_brokers'));
        $config->setBrokerVersion('1.0.0');
        $config->setRequiredAck(1);
        $config->setIsAsyn(false);
        $config->setProduceInterval(500);
    } catch (Throwable $e) {
        error_log($e->getMessage());

        return;
    }

    $author = $post->post_author; /* Post author ID. */
    $author_name = get_the_author_meta( 'display_name', $author );

    $slug = $post->post_name;
    $title = $post->post_title;
    $date  = get_the_date("Y-m-d\TH:i:sP", $post);
    $body = $post->post_content;

    $msg = array(
        'operation' => 'UPDATE',
        'message' => array(
            'slug' => $slug,
            'title' => $title,
            'author' => $author_name,
            'date' => $date,
            'body' => $body
        )
    );

    $payload = json_encode($msg, JSON_FORCE_OBJECT);
    error_log("Payload: " . $payload);

    $producer = new \Kafka\Producer(function() use ($payload) {
        return array(
            array(
                'topic' => get_option('kafka_topic'),
                'value' => $payload,
                'key' => 'payload',
            ),
        );
    });

    $producer->setLogger($logger);

    $producer->success(function($result) {
        error_log("Success: " . $result);
    });
    $producer->error(function($errorCode) {
        error_log("Error: " . $errorCode);
    });
    $producer->send();
}

add_action('admin_menu', 'kafka_menu');

function kafka_menu() {
    //create new top-level menu
    add_menu_page('Kafka Konfig', 'Kafka', 'administrator', __FILE__, 'kafka_plugin_settings_page' , plugins_url('/images/icon.png', __FILE__) );

    //call register settings function
    add_action('admin_init', 'register_kafka_settings');
}

function register_kafka_settings() {
    //register our settings
    register_setting('kafka-plugin-settings-group', 'kafka_brokers');
    register_setting('kafka-plugin-settings-group', 'kafka_topic');
}

function kafka_plugin_settings_page() {
    ?>
    <div class="wrap">
    <h1>Kafka Konfig yo</h1>

    <form method="post" action="options.php">
<?php settings_fields('kafka-plugin-settings-group'); ?>
<?php do_settings_sections('kafka-plugin-settings-group'); ?>
    <table class="form-table">
<tr valign="top">
<th scope="row">Brokers</th>
<td><input type="text" name="kafka_brokers" value="<?php echo esc_attr( get_option('kafka_brokers') ); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Topic</th>
<td><input type="text" name="kafka_topic" value="<?php echo esc_attr( get_option('kafka_topic') ); ?>" /></td>
</tr>
</table>

<?php submit_button(); ?>

</form>
</div>
<?php }

?>
