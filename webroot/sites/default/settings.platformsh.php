<?php
// Config sync directory
// $settings['config_sync_directory'] = '../config/config_xxxxxxxxxx/sync';

// Database
$databases['default']['default'] = array(
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASS'),
  'prefix' => '',
  'host' => getenv('DB_HOST'),
  'port' => getenv('DB_PORT'),
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

// Trusted host pattern
if (!empty(getenv('TRUSTED_HOSTS'))) {
    $settings['trusted_host_patterns'] = explode(',', getenv('TRUSTED_HOSTS'));
}

if (!empty(getenv('REDIS_HOST')) && !empty(getenv('REDIS_PORT'))) {
    // Redis configuration
    $settings['redis.connection']['interface'] = 'PhpRedis';
    // The host and port that the redis service is accessible on.
    $settings['redis.connection']['host']      = getenv('REDIS_HOST');
    $settings['redis.connection']['port']      = getenv('REDIS_PORT');

    // Enable redis caching
    // $settings['cache']['default'] = 'cache.backend.redis';
    // $settings['cache']['bins']['render'] = 'cache.backend.redis';
    // $settings['cache_prefix'] = 'stream';
    // $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
}

// Elasticsearch configuration
if (!empty(getenv('ELASTIC_HOST')) && !empty(getenv('ELASTIC_PORT'))) {
    $config['search_api.server.elasticsearch']['backend_config']['host'] = getenv('ELASTIC_HOST');
    $config['search_api.server.elasticsearch']['backend_config']['port'] = getenv('ELASTIC_PORT');
    $config['elasticsearch_connector.cluster.content']['url'] = 'http://' . getenv('ELASTIC_HOST') . ':' . getenv('ELASTIC_PORT');
}

# Solr configuration override
if (!empty(getenv('SOLR_HOST')) && !empty(getenv('SOLR_PORT'))) {
    $config['search_api.server.solr']['backend_config']['connector_config']['host'] = getenv('SOLR_HOST');
    $config['search_api.server.solr']['backend_config']['connector_config']['port'] = getenv('SOLR_PORT') ?: 8983;
    $config['search_api.server.solr']['backend_config']['connector_config']['core'] = getenv('SOLR_CORE') ?: 'dev';
}

// Hash salt
$settings['hash_salt'] = getenv('HASH_SALT');
