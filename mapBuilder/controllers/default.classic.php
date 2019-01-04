<?php
/**
* @package   lizmap
* @subpackage mapBuilder
* @author    3liz
* @copyright 2011-2018 3liz
* @link      http://3liz.com
* @license    All rights reserved
*/

class defaultCtrl extends jController {
    /**
    *
    */
    function index() {

        $rep = $this->getResponse('html', true);// true désactive le template général

        // Get lizmap services
        $services = lizmap::getServices();

        $title = "Map Builder";
        $rep->title = $title;

        $rep->metaViewport = 'initial-scale=1.0, user-scalable=no, width=device-width, shrink-to-fit=no';
        // Assets
        $rep->addCSSLink(jApp::urlBasePath().'css/main.css');
        $rep->addCSSLinkModule('mapBuilder','css/ol-5.3.0.css');
        $rep->addCSSLink(jApp::urlBasePath().'mapBuilder/fontawesome-free-5.4.1-web/css/all.css');
        $rep->addCSSLinkModule('mapBuilder','css/bootstrap.min.css');
        $rep->addCSSLink(jApp::urlBasePath().'mapBuilder/skin-awesome/ui.fancytree.css');
        $rep->addCSSLinkModule('mapBuilder','css/main.css');

        $rep->addStyle('html, body, .map', 'height: 100%;width: 100%;margin: 0;padding: 0');

        $rep->addJSLinkModule('mapBuilder','js/es6-promise.auto.min.js');
        $rep->addJSLinkModule('mapBuilder','js/jquery-3.3.1.min.js');
        $rep->addJSLinkModule('mapBuilder','js/jquery.fancytree-all-deps.min.js');
        $rep->addJSLinkModule('mapBuilder','js/popper.min.js');
        $rep->addJSLinkModule('mapBuilder','js/bootstrap.min.js');
        $rep->addJSLinkModule('mapBuilder','js/mapbuilder.js');

        // Pass some configuration options to the web page through javascript var
        $lizUrls = array(
          "config" => jUrl::get('lizmap~service:getProjectConfig'),
          "wms" => jUrl::get('lizmap~service:index'),
          "media" => jUrl::get('view~media:getMedia'),
          "mapcontext" => jUrl::get('mapBuilder~mapcontext:save')
        );

        $rep->addJSCode("var lizUrls = ".json_encode($lizUrls).";");

        $nestedTree = array();
        
        $repositories = lizmap::getRepositoryList();

        // Build repository + project tree for FancyTree 
        foreach ($repositories as $key => $repositoryName) {
            $repository = lizmap::getRepository($repositoryName);
            if( jAcl2::check('lizmap.repositories.view', $repository->getKey() )){
                $projects = $repository->getProjects();

                $projectArray = array();
                foreach ($projects as $project) {
                    $projectArray[] = [
                        "title" => $project->getData('title'),
                        "folder" => true,
                        "lazy" => true,
                        "repository" => $repositoryName,
                        "project" => $project->getKey()
                    ];
                }

                $nestedTree[] = [
                    "title" => $repository->getData('label'),
                    "folder" => true,
                    "children" => $projectArray
                ];
            }
        }

        // Write tree as JSON
        $rep->addJSCode('var mapBuilder = {"layerStoreTree": '.json_encode($nestedTree).'};');

        // Read mapBuilder configuration
        $readConfigPath = parse_ini_file(jApp::configPath('mapBuilder.ini.php'), True);
        // Get original extent from ini file if set
        if(array_key_exists('extent', $readConfigPath)){
            $rep->addJSCode("mapBuilder.extent = ".$readConfigPath['extent'].";");
        }
        // Get base layer from ini file if set
        if(array_key_exists('baseLayer', $readConfigPath)){
            $rep->addJSCode("mapBuilder.baseLayer = '".$readConfigPath['baseLayer']."';");
        }
        // Get base layer key from ini file if set
        if(array_key_exists('baseLayerKey', $readConfigPath)){
            $rep->addJSCode("mapBuilder.baseLayerKey = '".$readConfigPath['baseLayerKey']."';");
        }

        // Get map context in $_SESSION if exists
        if(isset($_SESSION['mapcontext']) && $_SESSION['mapcontext'] != ''){
            $rep->addJSCode("mapBuilder.mapcontext = ".json_encode($_SESSION['mapcontext']).";");
        }

        $rep->body->assign('repositoryLabel', $title);
        $rep->body->assign('isConnected', jAuth::isConnected());
        $rep->body->assign('user', jAuth::getUserSession());
        $rep->body->assign('allowUserAccountRequests', $services->allowUserAccountRequests);

        // Add Google Analytics ID
        if($services->googleAnalyticsID != '' && preg_match("/^UA-\d+-\d+$/", $services->googleAnalyticsID) == 1 ) {
            $rep->body->assign('googleAnalyticsID', $services->googleAnalyticsID);
        }

        $rep->bodyTpl = 'mapBuilder~main';

        // Override default theme with color set in admin panel
        if($cssContent = jFile::read(jApp::varPath('lizmap-theme-config/') . 'theme.css') ){
          $css = '<style type="text/css">' . $cssContent . '</style>';
          $rep->addHeadContent($css);
        }

        return $rep;
    }
}
