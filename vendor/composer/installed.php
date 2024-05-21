<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'meta-box/mb-pods-migration',
        'dev' => true,
    ),
    'versions' => array(
        'meta-box/mb-pods-migration' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
        'meta-box/mbb-parser' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'library',
            'install_path' => __DIR__ . '/../meta-box/mbb-parser',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => '8355db0b86c0de552a7e5b33c897c2835fa92bd6',
            'dev_requirement' => false,
        ),
        'meta-box/support' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'library',
            'install_path' => __DIR__ . '/../meta-box/support',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => '21ac638244f63c9372ff9958810552a1a26135d9',
            'dev_requirement' => false,
        ),
        'riimu/kit-phpencoder' => array(
            'pretty_version' => 'v2.4.2',
            'version' => '2.4.2.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../riimu/kit-phpencoder',
            'aliases' => array(),
            'reference' => '72ff7825de193b272e17b228394819dbfc638e72',
            'dev_requirement' => false,
        ),
    ),
);
