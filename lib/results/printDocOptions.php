<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later.
 *  
 * @filesource $RCSfile: printDocOptions.php,v $
 * @version $Revision: 1.11 $
 * @modified $Date: 2008/09/29 19:48:11 $ $Author: schlundus $
 * @author 	Martin Havlat
 * 
 *  Settings for generated documents
 * 	- Structure of a document 
 *	- It builds the javascript tree that allow the user select a required part 
 *		Test specification/ Test plan.
 *
 * rev :
 *      20080819 - franciscom - fixed internal bug due to changes in return
 *                              values of generate*tree()
 *                              IMPORTANT: 
 *                                        TEMPLATE DO NOT WORK YET with EXTJS tree
 * 
 *      20070509 - franciscom - added contribution BUGID
 *
 */
 
require('../../config.inc.php');
require("common.php");
require_once("treeMenu.inc.php");
testlinkInitPage($db);
$templateCfg = templateConfiguration();

$args=init_args();
$gui=initializeGui($db,$args,$_SESSION['basehref']);

$arrFormat = array('html' => 'HTML', 'msword' => 'MS Word');

// Important Notice:
// If you made add/remove elements from this array, you must update
// $printingOptions in printData.php

$arrCheckboxes = array(
	array( 'value' => 'toc', 'description' => lang_get('opt_show_toc'), 'checked' => 'n'),
	array( 'value' => 'header', 'description' => lang_get('opt_show_doc_header'), 'checked' => 'n'),
	array( 'value' => 'summary', 'description' => lang_get('opt_show_tc_summary'), 'checked' => 'y'),
	array( 'value' => 'body', 'description' => lang_get('opt_show_tc_body'), 'checked' => 'n'),
 	array( 'value' => 'author',     'description' => lang_get('opt_show_tc_author'), 'checked' => 'n'),
	array( 'value' => 'requirement', 'description' => lang_get('opt_show_tc_reqs'), 'checked' => 'n'),
	array( 'value' => 'keyword', 'description' => lang_get('opt_show_tc_keys'), 'checked' => 'n')
);

if( $gui->report_type == 'testplan')
{
  $arrCheckboxes[]=	array( 'value' => 'passfail', 'description' => lang_get('opt_show_passfail'), 'checked' => 'n');
}

//process setting for print
if(isset($_REQUEST['setPrefs']))
{
  foreach($arrCheckboxes as $key => $elem)
  {
   $field_name=$elem['value'];
   if(isset($_REQUEST[$field_name]) )
   {
    $arrCheckboxes[$key]['checked'] = 'y';   
   }  
  }
}

// generate tree for product test specification
$workPath = 'lib/results/printDocument.php';
$getArguments = "&type=" . $gui->report_type;

// generate tree for Test Specification
$treeString=null;
$tree=null;
$treemenu_type=config_get('treemenu_type');
switch($gui->report_type)
{
    case 'testspec':
        if($treemenu_type != 'EXTJS')
        {
	          $treeString = generateTestSpecTree($db,$args->tproject_id, $args->tproject_name,$workPath,
	                                             FOR_PRINTING,HIDE_TESTCASES,ACTION_TESTCASE_DISABLE,$getArguments);
        }
    break;

    case 'testplan':
    	$tplan_mgr = new testplan($db);
	    $latestBuild = $tplan_mgr->get_max_build_id($args->tplan_id);
	      
	    $filters = new stdClass();
  	    $additionalInfo = new stdClass();
        
	    $filters->keyword_id = FILTER_BY_KEYWORD_OFF;
  	    $filters->keywordsFilterType=null;
  	    $filters->tc_id = FILTER_BY_TC_OFF;
  	    $filters->build_id = $latestBuild;
  	    $filters->hide_testcases=HIDE_TESTCASES;
  	    $filters->assignedTo = FILTER_BY_ASSIGNED_TO_OFF;
  	    $filters->status = FILTER_BY_TC_STATUS_OFF;
  	    $filters->cf_hash = SEARCH_BY_CUSTOM_FIELDS_OFF;
  	    $filters->include_unassigned=1;
  	    $filters->show_testsuite_contents=1;
        
  	    $additionalInfo->useCounters=CREATE_TC_STATUS_COUNTERS_OFF;
  	    $additionalInfo->useColours=COLOR_BY_TC_STATUS_OFF;
        
	    $treeContents = generateExecTree($db,$workPath,$args->tproject_id,$args->tproject_name,
	                                       $args->tplan_id,$args->tplan_name,$getArguments,$filters,$additionalInfo);
        
        $treeString = $treeContents->menustring;
        $gui->ajaxTree = null;
        if($treemenu_type == 'EXTJS')
        {
            $gui->ajaxTree->root_node = $treeContents->rootnode;
            $gui->ajaxTree->children = $treeContents->menustring;
            $gui->ajaxTree->cookiePrefix .= $gui->ajaxTree->root_node->id . "_" ;
        }
    break;

    default:
	      tLog("Argument _REQUEST['type'] has invalid value", 'ERROR');
	      exit();
    break;
}
$tree = ($treemenu_type == 'EXTJS') ? $treeString :invokeMenu($treeString);

$smarty = new TLSmarty();
$smarty->assign('gui', $gui);
$smarty->assign('treeKind', TL_TREE_KIND);
$smarty->assign('arrCheckboxes', $arrCheckboxes);
$smarty->assign('arrFormat', $arrFormat);
$smarty->assign('selFormat', $args->format);
$smarty->assign('tree', $tree);
$smarty->assign('menuUrl', $workPath);
$smarty->assign('args', $getArguments);
$smarty->display($templateCfg->template_dir . $templateCfg->default_template);



/*
  function: init_args
            get user input and create an object with properties representing
            this inputs.
            You can think in ths like some sort of namespace.

  args:
  
  returns: stdClass() object 

*/
function init_args()
{
    $args=new stdClass();
    $_REQUEST = strings_stripSlashes($_REQUEST);

    $args->tproject_id   = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
    $args->tproject_name = isset($_SESSION['testprojectName']) ? $_SESSION['testprojectName'] : '';
    $args->tplan_name = isset($_SESSION['testPlanName']) ? $_SESSION['testPlanName'] : '';

    $args->tplan_id   = isset($_REQUEST['tplan_id']) ? $_REQUEST['tplan_id'] : 0;
    $args->format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'html';
    $args->report_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
    
    return $args;
}

/*
  function: initializeGui
            initialize gui (stdClass) object that will be used as argument
            in call to Template Engine.
 
  args: argsObj: object containing User Input and some session values
        basehref: URL to web home of your testlink installation.
        tprojectMgr: test project manager object.
        treeDragDropEnabled: true/false. Controls Tree drag and drop behaivor.
        
  
  
  returns: stdClass object
  
  rev: 20080817 - franciscom
       added code to get total number of testcases in a test project, to display
       it on root tree node.

*/
function initializeGui(&$dbHandler,$argsObj,$basehref)
{
    $tcaseCfg=config_get('testcase_cfg');
        
    $gui = new stdClass();
    $tprojectMgr = new testproject($dbHandler);
    $tcasePrefix=$tprojectMgr->getTestCasePrefix($argsObj->tproject_id);

    $gui->tree_title='';
    $gui->ajaxTree=new stdClass();
    $gui->ajaxTree->root_node=new stdClass();
    $gui->ajaxTree->dragDrop=new stdClass();
    $gui->ajaxTree->dragDrop->enabled=false;
    $gui->ajaxTree->dragDrop->BackEndUrl=null;
    $gui->ajaxTree->children='';
     
    // Prefix for cookie used to save tree state
    $gui->ajaxTree->cookiePrefix='print' . str_replace(' ', '_', $argsObj->report_type) . '_';
    
    switch($argsObj->report_type)
    {
        case 'testspec':
	          $gui->tree_title=lang_get('title_tc_print_navigator');
            
            $gui->ajaxTree->loader=$basehref . 'lib/ajax/gettprojectnodes.php?' .
                                   "root_node={$argsObj->tproject_id}&" .
                                   "show_tcases=0&operation=print&" .
                                   "tcprefix=".urlencode($tcasePrefix.$tcaseCfg->glue_character)."}";
	          
	          $gui->ajaxTree->loadFromChildren=0;
	          $gui->ajaxTree->root_node->href="javascript:TPROJECT_PTP({$argsObj->tproject_id})";
            $gui->ajaxTree->root_node->id=$argsObj->tproject_id;

            $tcase_qty = $tprojectMgr->count_testcases($argsObj->tproject_id);
            $gui->ajaxTree->root_node->name=$argsObj->tproject_name . " ($tcase_qty)";
            
            $gui->ajaxTree->cookiePrefix .=$gui->ajaxTree->root_node->id . "_" ;
	      break;
	      
        case 'testplan':
	          $gui->tree_title=lang_get('title_tp_print_navigator');
	          $gui->ajaxTree->loadFromChildren=1;
	          $gui->ajaxTree->loader='';
	      break;
    }

    $gui->report_type=$argsObj->report_type;    
    return $gui;  
}
?>
