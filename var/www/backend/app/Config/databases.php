<?php

/**
 * database
 */
return [

    //default device
    'default'   => 'gui',

    //sqlite database
    'sqlite'    => [

        //gui db
        'gui'   => [
            'driver'    => 'sqlite',
            'database' => '/tmp/gui.sqlite',
            'prefix'    => '',
        ],

        //other

    ],

];