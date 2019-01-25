<?php
/**
* @package   lizmap
* @subpackage mapBuilder
* @author    your name
* @copyright 2011-2018 3liz
* @link      http://3liz.com
* @license    All rights reserved
*/


class mapBuilderModuleInstaller extends jInstallerModule {

    function install() {
        // Copy conf file
        $this->copyDirectoryContent('conf', jApp::configPath());

        // SQL for map context
        if ($this->firstDbExec()) {
            // Add mapcontext table
            $this->useDbProfile('jauth');
            $this->execSQLScript('sql/mapcontext');
        }
    }
}