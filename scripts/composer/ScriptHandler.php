<?php
namespace Premium\composer;

use Composer\Factory;
use Composer\Package\Link;
use Composer\Script\Event;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\MultiConstraint;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Setup of premium site.
 *
 * @package Premium\composer
 */
class ScriptHandler {

  /**
   * List of optional installations with list of composer packages and versions
   *
   * @var string[][][]
   */
  private static $optional_modules = [
    'None' => [],
    'Cookiebot' => [
      [
        'package' => 'drupal/cookiebot',
        'operator' => '^',
        'version' => '1.0.0-alpha8'
      ]
    ],
    'Cookie Info' => [
      [
        'package' => 'novicell/cookie_info',
        'operator' => '^',
        'version' => '1.0'
      ]
    ],
    'GTM' => [
      [
        'package' => 'drupal/gtm',
        'operator' => '^',
        'version' => '1.6'
      ]
    ],
    'Content Hierarchy' => [
      [
        'package' => 'novicell/content_hierarchy',
        'operator' => '^',
        'version' => '1.0'
      ]
    ],
    'IE Warning' => [
      [
        'package' => 'novicell/ie_warning',
        'operator' => '^',
        'version' => '1.0'
      ]
    ],
    'Premium Articles' => [
      [
        'package' => 'novicell/premium_articles',
        'operator' => '^',
        'version' => '2.0'
      ]
    ],
    'Premium Maps' => [
      [
        'package' => 'novicell/premium_maps',
        'operator' => '^',
        'version' => '1.0'
      ]
    ],
    'Drupal Commerce' => [
      [
        'package' => 'drupal/commerce',
        'operator' => '^',
        'version' => '2.0'
      ]
    ]
  ];

  /**
   * List of optional installations with list of composer packages and versions
   *
   * @var string[][][]
   */
  private static $headless_packages = [
    'Premium Headless' => [
      [
        'package' => 'novicell/premium_headless',
        'operator' => '^',
        'version' => '1.0'
      ]
    ],
    'Premium Headless theme' => [
      [
        'package' => 'novicell/premium_headless_theme',
        'operator' => '^',
        'version' => '1.0'
      ]
    ],
    'Pathologic' => [
      [
        'package' => 'drupal/pathologic',
        'operator' => '^',
        'version' => '2.0@alpha'
      ]
    ]
  ];

  /**
   * List of optional installations with list of composer packages and versions
   *
   * @var string[][][]
   */
  private static $monolith_packages = [
    'Drupal Premium theme' => [
      [
        'package' => 'novicell/drupal-premium-theme',
        'operator' => '^',
        'version' => '1.0'
      ]
    ]
  ];

  private static $deployment_options = [
    'None' => [
      'require-dev' => [],
      'dirs' => [],
      'copy' => [],
      'token_replace' => []
    ],
    'Deployer' => [
      'require-dev' => [
        [
          'package' => 'deployer/deployer',
          'operator' => '^',
          'version' => '7.0'
        ]
      ],
      'dirs' => [
        'deployer',
        'deployer/recipe'
      ],
      'copy' => [
        'examples/hosting/deployer/deploy.php'                  => 'deploy.php',
        'examples/hosting/deployer/config.yml'                  => 'deployer/config.yml',
        'examples/hosting/deployer/recipe/base.php'             => 'deployer/recipe/base.php',
        'examples/hosting/deployer/recipe/composer.php'         => 'deployer/recipe/composer.php',
        'examples/hosting/deployer/recipe/database_backup.php'  => 'deployer/recipe/database_backup.php',
        'examples/hosting/deployer/recipe/drupal_updates.php'   => 'deployer/recipe/drupal_updates.php',
        'examples/hosting/deployer/recipe/file_permissions.php' => 'deployer/recipe/file_permissions.php',
        'examples/hosting/deployer/recipe/maintenance_mode.php' => 'deployer/recipe/maintenance_mode.php',
        'examples/hosting/deployer/recipe/npm.php'              => 'deployer/recipe/npm.php',
        'examples/hosting/deployer/recipe/slack.php'            => 'deployer/recipe/slack.php'
      ],
      'token_replace' => [
        'deployer/config.yml'
      ]
    ]
  ];

  /**
   * Array of files in subtheme that needs renaming and token replacement
   *
   * @var string[]
   */
  private static $theme_files = [
    '.info.yml',
    '.theme',
    '.atoms.yml',
    '.styles.yml',
    '.breakpoints.yml',
    '.key_value.yml',
    '.libraries.yml'
  ];

  /**
   * Array of files in site configuration that needs token replacement
   *
   * @var string[]
   */
  private static $configuration_files = [
    'webroot/sites/sites.php',
    'drush/drush.yml',
    'drush/drushrc.php',
    'assets/robots-additions.txt',
    'webroot/modules/custom/premium_theme_helper/premium_theme_helper.install'
  ];

  /**
   * Steps done after recipe has been installed but before composer packages have been installed.
   *
   * @param \Composer\Script\Event $event
   *   Composer event.
   */
  public static function postRootPackageInstall(Event $event) {
    $event->getIO()->write([
      "<bg=blue;fg=white>                                                                                                                       ",
      "    ██████╗ ██████╗ ██╗   ██╗██████╗  █████╗ ██╗         ██████╗ ██████╗ ███████╗███╗   ███╗██╗██╗   ██╗███╗   ███╗    ",
      "    ██╔══██╗██╔══██╗██║   ██║██╔══██╗██╔══██╗██║         ██╔══██╗██╔══██╗██╔════╝████╗ ████║██║██║   ██║████╗ ████║    ",
      "    ██║  ██║██████╔╝██║   ██║██████╔╝███████║██║         ██████╔╝██████╔╝█████╗  ██╔████╔██║██║██║   ██║██╔████╔██║    ",
      "    ██║  ██║██╔══██╗██║   ██║██╔═══╝ ██╔══██║██║         ██╔═══╝ ██╔══██╗██╔══╝  ██║╚██╔╝██║██║██║   ██║██║╚██╔╝██║    ",
      "    ██████╔╝██║  ██║╚██████╔╝██║     ██║  ██║███████╗    ██║     ██║  ██║███████╗██║ ╚═╝ ██║██║╚██████╔╝██║ ╚═╝ ██║    ",
      "    ╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚═╝     ╚═╝  ╚═╝╚══════╝    ╚═╝     ╚═╝  ╚═╝╚══════╝╚═╝     ╚═╝╚═╝ ╚═════╝ ╚═╝     ╚═╝    ",
      "                                                                                                                       ",
      "</>"
    ]);

    $event->getIO()->write([
      "<fg=blue>                                                     ",
      "                                                          ____",
      "                                                        .'* *.'",
      "                                                     __/_*_*(_",
      "                                                    / _______ \\",
      "                                                   _\\_)/___\\(_/_",
      "                                                  / _((\\- -/))_ \\",
      "                                                  \\ \\())(-)(()/ /",
      "                                                   ' \\(((()))/ '",
      "                                                  / ' \\)).))/ ' \\",
      "                                                 / _ \\ - | - /_  \\",
      "                                                (   ( .;''';. .'  )",
      "                                                _\\\"__ /    )\\ __\"/_",
      "                                                  \\/  \\   ' /  \\/",
      "                                                   .'  '...' ' )",
      "                                                    / /  |  \\ \\",
      "                                                   / .   .   . \\",
      "                                                  /   .     .   \\",
      "                                                 /   /   |   \\   \\",
      "                                               .'   /    b    '.  '.",
      "                                           _.-'    /     Bb     '-. '-._",
      "                                       _.-'       /      BBb       '-.  '-.",
      "                                      (__________(_____.dBBBb.________)____)",
      "</>",
    ]);

    $event->getIO()->write([
      "<options=bold>Greetings! I am the amazing spin-up-a-new-premium-website-in-no-time WIZARD!",
      "I'm looking forward to helping you. This is going to be so much fun! YAY",
      "",
      "Answer these few questions and we will get you setup in no time...</>",
      ""
    ]);

    $in_ddev = (getenv('IS_DDEV_PROJECT') == 'true');
    $environment = [];
    if (!empty($project_name = $event->getIO()->ask('Project name: '))) {
      $environment['PROJECT_NAME'] = $project_name;
    }
    if (!empty($domain_name = $event->getIO()->ask('Domain name (without www.): '))) {
      $environment['DOMAIN_NAME'] = $domain_name;
    }
    $headless = $event->getIO()->askConfirmation('Is it a headless project? ', FALSE);
    $modules = $event->getIO()->select('Optional modules', array_keys(self::$optional_modules), 'none', FALSE, 'Value "%s" is invalid', TRUE);
    $deployment = $event->getIO()->select('Deployment method', array_keys(self::$deployment_options), 0, FALSE, 'Value "%s" is invalid', FALSE);

    if (!$in_ddev) {
      if (!empty($db_host = $event->getIO()->ask('Database host (localhost): ', 'localhost'))) {
        $environment['DB_HOST'] = $db_host;
      }
      if (!empty($db_port = $event->getIO()->ask('Database port (3306): ', '3306'))) {
        $environment['DB_PORT'] = $db_port;
      }
      if (!empty($db_name = $event->getIO()->ask('Database name (' . $project_name . '): ', $project_name))) {
        $environment['DB_SCHEMA'] = $db_name;
      }
      if (!empty($db_user = $event->getIO()->ask('Database user (' . $project_name . '): ', $project_name))) {
        $environment['DB_USER'] = $db_user;
      }
      if (!empty($db_pass = $event->getIO()->askAndHideAnswer('Database password: '))) {
        $environment['DB_PASS'] = $db_pass;
      }
      if (!empty($redis_host = $event->getIO()->ask('Redis host (localhost): ', 'localhost'))) {
        $environment['REDIS_HOST'] = $redis_host;
      }
    } else {
      $environment['DB_HOST'] = 'db';
      $environment['DB_PORT'] = getenv('DDEV_HOST_DB_PORT');
      $environment['DB_SCHEMA'] = 'db';
      $environment['DB_USER'] = 'db';
      $environment['DB_PASS'] = 'db';
      $environment['REDIS_HOST'] = 'redis';
    }

    $tokens = [
      'PROJECT_NAME' => $project_name,
      'DOMAIN_NAME' => $domain_name
    ];
    $environment['HASH_SALT'] = $hash_salt = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(random_bytes(55)));
    $environment['CONFIG_SPLIT_DEVELOPMENT'] = 'TRUE';
    $deployment_steps = array_values(self::$deployment_options)[$deployment];

    // TODO: find a way to handle second level domains
    $parts = explode('.', $domain_name);
    $tld = end($parts);
    unset($parts[count($parts) - 1]);
    $tokens['DOMAIN_ONLY'] = $domain_only = implode('.', $parts);

    $tokens['STAGING_SITE'] = "staging." . $domain_only . ".drupal.dk";
    $tokens['PROD_SITE'] = "prod." . $domain_only . ".drupal.dk";
    $tokens['LOCAL_SITE'] = $domain_only . ".localhost";
    $tokens['TRUSTED_HOST_PATTERNS'] = "[\n  '^$domain_only\.$tld',\n  '^.+\.$domain_only\.$tld'\n]";

    // Add optional modules to composer.json and current running instance
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Adding optional modules to composer.json...</>");
    $event->getIO()->write("");
    $json_file = Factory::getComposerFile();
    $json = json_decode(file_get_contents($json_file));
    $links = $event->getComposer()->getPackage()->getRequires();
    if ($headless) {
      foreach (self::$headless_packages as $name => $packages) {
        self::addPackagesToComposer($links, $json, $event, $packages);
      }
    } else {
      foreach (self::$monolith_packages as $name => $packages) {
        self::addPackagesToComposer($links, $json, $event, $packages);
      }
    }
    foreach ($modules as $choice) {
      $packages = array_values(self::$optional_modules)[$choice];
      self::addPackagesToComposer($links, $json, $event, $packages);
    }
    $event->getComposer()->getPackage()->setRequires($links);
    file_put_contents($json_file, str_replace('\/', '/', json_encode($json, JSON_PRETTY_PRINT)));

    // Writing environment file
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Writing environment file...</>");
    $event->getIO()->write("");
    $file = '';
    foreach ($environment as $key => $value) {
      $file .= $key . '=' . $value . "\n";
      putenv($key . '=' . $value);
    }
    file_put_contents('.env', $file);
    file_put_contents('tokens.json', json_encode($tokens, JSON_PRETTY_PRINT));

    // Renaming directories
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Renaming directories...</>");
    $event->getIO()->write("");
    $site_dir = 'webroot/sites/' . $domain_name;
    if ($headless) {
      self::deleteDirectory('webroot/sites/DOMAIN_NAME/themes/custom/PROJECT_NAME');
      rename('webroot/sites/DOMAIN_NAME/themes/custom/HEADLESS', 'webroot/sites/DOMAIN_NAME/themes/custom/' . $project_name);
    } else {
      self::deleteDirectory('webroot/sites/DOMAIN_NAME/themes/custom/HEADLESS');
      rename('webroot/sites/DOMAIN_NAME/themes/custom/PROJECT_NAME', 'webroot/sites/DOMAIN_NAME/themes/custom/' . $project_name);
    }
    rename('webroot/sites/DOMAIN_NAME', $site_dir);

    // Preparing site configuration
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Preparing site configuration...</>");
    $event->getIO()->write("");
    self::replaceAllTokensInFile('webroot/sites/' . $domain_name . '/settings.php', $tokens);
    foreach (self::$configuration_files as $filename) {
      self::replaceAllTokensInFile($filename, $tokens);
    }
    self::copyAndReplaceAllTokensInFile('webroot/sites/local.php', 'webroot/sites/sites.local.php', $tokens);
    self::copyAndReplaceAllTokensInFile('webroot/sites/' . $domain_name . '/local.php', 'webroot/sites/' . $domain_name . '/settings.local.php', $tokens);

    // Preparing deployment method
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Preparing deployment method...</>");
    $event->getIO()->write("");
    foreach ($deployment_steps['dirs'] ?? [] as $directory) {
      mkdir($directory);
    }
    foreach ($deployment_steps['copy'] ?? [] as $source => $destination) {
      $file = file_get_contents($source);
      file_put_contents($destination, $file);
    }
    foreach ($deployment_steps['token_replace'] ?? [] as $filename) {
      self::replaceAllTokensInFile($filename, $tokens);
    }
    if (!empty($deployment_steps['require-dev'])) {
      $json_file = Factory::getComposerFile();
      $json = json_decode(file_get_contents($json_file));
      $links = $event->getComposer()->getPackage()->getDevRequires();
      $packages = $deployment_steps['require-dev'];
      foreach ($packages as $requirement) {
        $package = $requirement['package'];
        $links[$package] = self::createComposerLink($event, $requirement['package'], $requirement['operator'], $requirement['version']);
        $json->{'require-dev'}->$package = $requirement['operator'] . $requirement['version'];
      }
      $event->getComposer()->getPackage()->setDevRequires($links);
      file_put_contents($json_file, str_replace('\/', '/', json_encode($json, JSON_PRETTY_PRINT)));
    }

    // Renaming files in subtheme and replacing token in subtheme files with actual project name
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Preparing \"" . $project_name . "\" subtheme...</>");
    $event->getIO()->write("");
    $theme_dir = 'webroot/sites/' . $domain_name . '/themes/custom/' . $project_name;
    foreach (self::$theme_files as $theme_file) {
      $filename = $theme_dir . '/' . $project_name . $theme_file;
      try {
        rename($theme_dir . '/PROJECT_NAME' . $theme_file, $filename);
        self::replaceAllTokensInFile($filename, $tokens);
      } catch (Throwable) {

      }
    }
    self::replaceAllTokensInDirectory($theme_dir, $tokens);

    // Preparing a DDEV configuration so it's ready get started up after installation
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Preparing DDEV configuration...</>");
    $event->getIO()->write("");
    self::replaceAllTokensInDirectory(".ddev", $tokens);

    // Clean up unused examples and remove scripthandler from composer
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Clean up examples and composer...</>");
    $event->getIO()->write("");
    self::deleteDirectory("examples");
    $json_file = Factory::getComposerFile();
    $json = json_decode(file_get_contents($json_file));
    unset($json->scripts);
    unset($json->autoload);
    file_put_contents($json_file, str_replace('\/', '/', json_encode($json, JSON_PRETTY_PRINT)));

    if (!$headless) {
      // Install node modules and build front end assets...
      $event->getIO()->write("");
      $event->getIO()->write("<options=bold>Install node modules and build frontend assets...</>");
      $event->getIO()->write("");
      exec('cd ' . $theme_dir . ' && npm ci && npm run build:prod');
    }

    // Now it's time to just let Composer install all the packages and we're done!
    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Installing composer packages...</>");
    $event->getIO()->write("");
  }

  /**
   * Steps done after recipe has been installed and composer packages have been installed.
   *
   * @param \Composer\Script\Event $event
   *   Composer event.
   */
  public static function postCreateProjectCmd(Event $event) {
    $tokens = json_decode(file_get_contents('tokens.json'), TRUE);

    //self::replaceAllTokensInDirectory('webroot/profiles/contrib/premium_profile', $tokens);
    //self::replaceAllTokensInFile('webroot/profiles/contrib/premium_profile/premium_profile.info.yml', $tokens);

    /*$event->getIO()->write("");
    $event->getIO()->write("<options=bold>Installing Drupal site...</>");
    $event->getIO()->write("");
    exec('vendor/drush/drush/drush si --account-name=novicell --site-name=' . $tokens['PROJECT_NAME'] . ' -y');

    $event->getIO()->write("");
    $event->getIO()->write("<options=bold>Exporting Drupal configuration...</>");
    $event->getIO()->write("");
    exec('vendor/drush/drush/drush cex -y');*/

    $event->getIO()->write([
      "",
      "<options=bold>YAY!! We set it all up. Lets have some Fireworks!</>",
      "",
      "<fg=yellow>",
      "                                   .''.",
      "       .''.      .        *''*    :_\/_:     .",
      "      :_\/_:   _\(/_  .:.*_\/_*   : /\ :  .'.:.'.",
      "  .''.: /\ :   ./)\   ':'* /\ * :  '..'.  -=:o:=-",
      " :_\/_:'.:::.    ' *''*    * '.\'/.' _\(/_'.':'.'",
      " : /\ : :::::     *_\/_*     -= o =-  /)\    '  *",
      "  '..'  ':::'     * /\ *     .'/.\'.   '",
      "      *            *..*         :",
      "        *",
            "        *",
      "</>",
      ""
    ]);

    $event->getIO()->write([
      "",
      "<options=bold>Now all that is left is for you to open your local development site and finish the installation of Drupal...</>",
      ""
    ]);
    $event->getIO()->write("http://" . $tokens['LOCAL_SITE']);

  }

  protected static function addPackagesToComposer(&$links, $json, $event, $packages) {
    foreach ($packages as $requirement) {
      $package = $requirement['package'];
      $links[$package] = self::createComposerLink($event, $requirement['package'], $requirement['operator'], $requirement['version']);
      $json->require->$package = $requirement['operator'] . $requirement['version'];
    }
  }

  /**
   * @param string $directory_name
   * @param array $tokens
   */
  protected static function deleteDirectory($directory_name) {
    $directory = new \RecursiveDirectoryIterator($directory_name, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new \RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $info) {
      if ($info->isDir()){
        rmdir($info->getRealPath());
      } else {
        unlink($info->getRealPath());
      }
    }
    rmdir($directory_name);
  }

  /**
   * @param string $directory_name
   * @param array $tokens
   */
  protected static function replaceAllTokensInDirectory($directory_name, array $tokens) {
    $directory = new \RecursiveDirectoryIterator($directory_name);
    $iterator = new \RecursiveIteratorIterator($directory);
    $files = array();
    foreach ($iterator as $info) {
      if ($info->isFile()) {
        $files[] = $info->getPathname();
      }
    }
    foreach ($files as $filename) {
      self::copyAndReplaceAllTokensInFile($filename, $filename, $tokens, FALSE);
    }
  }

  /**
   * @param $source
   * @param $destination
   * @param array $tokens
   * @param bool $deleteSource
   */
  protected static function copyAndReplaceAllTokensInFile($source, $destination, array $tokens, $deleteSource = TRUE) {
    $file = file_get_contents($source);
    $file = str_replace(array_keys($tokens), array_values($tokens), $file);
    file_put_contents($destination, $file);
    if ($deleteSource) {
      unlink($source);
    }
  }

  /**
   * @param string $filename
   * @param array $tokens
   */
  protected static function replaceAllTokensInFile($filename, array $tokens) {
    self::copyAndReplaceAllTokensInFile($filename, $filename, $tokens, FALSE);
  }

  /**
   * @param Event $event
   * @param string $package
   * @param string $operator
   * @param string $version
   * @return Link
   */
  protected static function createComposerLink(Event $event, $package, $operator, $version, $description = 'requires') {
    $prettyConstraint = $operator . $version;
    if ($operator == '=') {
      $prettyConstraint = $version;
    }
    if ($operator == '^') {
      $parts = explode('.', $version);
      $nextVersion = (intval($parts[0]) + 1) . '.0.0.0-dev';
      $upperConstraint = new Constraint('<', $nextVersion);
      $lowerConstraint = new Constraint('>=', $version);
      return new Link($event->getComposer()->getPackage()->getName(), $package, new MultiConstraint([$lowerConstraint, $upperConstraint]), $description, $prettyConstraint);
    } else {
      return new Link($event->getComposer()->getPackage()->getName(), $package, new Constraint($operator, $version), $description, $prettyConstraint);
    }
  }
}
