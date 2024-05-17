<?php return array(
    'root' => array(
        'name' => 'campuspress/calendar-plus',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'campuspress/calendar-plus' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'johngrogg/ics-parser' => array(
            'pretty_version' => 'v3.4.0',
            'version' => '3.4.0.0',
            'reference' => 'de0c3a426bd5b7d6aae2d9f7367410672e633108',
            'type' => 'library',
            'install_path' => __DIR__ . '/../johngrogg/ics-parser',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'mexitek/phpcolors' => array(
            'pretty_version' => 'v1.0.4',
            'version' => '1.0.4.0',
            'reference' => '4043974240ca7dc3c2bec3c158588148b605b206',
            'type' => 'library',
            'install_path' => __DIR__ . '/../mexitek/phpcolors',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
