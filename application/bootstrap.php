<?php
// application/bootstrap.php
// 
// Step 1: APPLICATION CONSTANTS - Set the constants to use in this application.
// These constants are accessible throughout the application, even in ini
// files. We optionally set APPLICATION_PATH here in case our entry point
// isn't index.php (e.g., if required from our test suite or a script).
defined('APPLICATION_PATH')
    or define('APPLICATION_PATH', dirname(__FILE__));

defined('APPLICATION_ENVIRONMENT')
    or define('APPLICATION_ENVIRONMENT', 'development');


// Setup Doctrine
Doctrine_Manager::connection('mysql://root:@localhost/datagrid');
set_include_path(
    APPLICATION_PATH . '/models'
    . PATH_SEPARATOR .
    APPLICATION_PATH . '/models/generated'
    . PATH_SEPARATOR . get_include_path()
);

// Step 2: FRONT CONTROLLER - Get the front controller.
// The Zend_Front_Controller class implements the Singleton pattern, which is a
// design pattern used to ensure there is only one instance of
// Zend_Front_Controller created on each request.
$frontController = Zend_Controller_Front::getInstance();

// Step 3: CONTROLLER DIRECTORY SETUP - Point the front controller to your action
// controller directory.
$frontController->setControllerDirectory(APPLICATION_PATH . '/controllers');
$frontController->throwExceptions(true);
error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

// Step 4: APPLICATION ENVIRONMENT - Set the current environment.
// Set a variable in the front controller indicating the current environment --
// commonly one of development, staging, testing, production, but wholly
// dependent on your organization's and/or site's needs.
$frontController->setParam('env', APPLICATION_ENVIRONMENT);

Zend_Layout::startMvc(array('layoutPath' => '../application/layouts'));

// Step 5: CLEANUP - Remove items from global scope.
// This will clear all our local boostrap variables from the global scope of
// this script (and any scripts that called bootstrap).  This will enforce
// object retrieval through the applications's registry.
unset($frontController);