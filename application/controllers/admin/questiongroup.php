<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
* LimeSurvey (tm)
* Copyright (C) 2011 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*
*
*/

/**
* questiongroup
*
* @package LimeSurvey
* @author
* @copyright 2011
* @version $Id$
* @access public
*/
class questiongroup extends Survey_Common_Controller {

    /**
    * questiongroup::__construct()
    * Constructor
    * @return
    */
    function __construct()
    {
        parent::__construct();
    }

    /**
    * questiongroup::import()
    * Function responsible to import a question group.
    * @return
    */
    function import()
    {
        $action = $this->input->post('action');
        $surveyid = $this->input->post('sid');
        $clang = $this->limesurvey_lang;
        $css_admin_includes[] = $this->config->item('styleurl')."admin/default/superfish.css";
        $this->config->set_item("css_admin_includes", $css_admin_includes);
        self::_getAdminHeader();
        self::_showadminmenu();
        self::_surveybar($surveyid,NULL);
        self::_surveysummary($surveyid,"importgroup");
        if ($action == 'importgroup')
        {
            $importgroup = "<div class='header ui-widget-header'>".$clang->gT("Import question group")."</div>\n";
            $importgroup .= "<div class='messagebox ui-corner-all'>\n";

            $sFullFilepath = $this->config->item('tempdir') . DIRECTORY_SEPARATOR . $_FILES['the_file']['name'];
            $aPathInfo = pathinfo($sFullFilepath);
            $sExtension = $aPathInfo['extension'];

            if (!@move_uploaded_file($_FILES['the_file']['tmp_name'], $sFullFilepath))
            {
                $fatalerror = sprintf ($clang->gT("An error occurred uploading your file. This may be caused by incorrect permissions in your %s folder."),$this->config->item('tempdir'));
            }

            // validate that we have a SID
            if (!returnglobal('sid'))
            {
                $fatalerror .= $clang->gT("No SID (Survey) has been provided. Cannot import question.");
            }
            /**else
            {
            $surveyid=returnglobal('sid');
            }*/

            if (isset($fatalerror))
            {
                $importgroup .= "<div class='warningheader'>".$clang->gT("Error")."</div><br />\n";
                $importgroup .= $fatalerror."<br /><br />\n";
                $importgroup .= "<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\" /><br /><br />\n";
                $importgroup .= "</div>\n";
                @unlink($sFullFilepath);
                show_error($importgroup);
                return;
            }
            $this->load->helper('admin/import');
            // IF WE GOT THIS FAR, THEN THE FILE HAS BEEN UPLOADED SUCCESFULLY
            $importgroup .= "<div class='successheader'>".$clang->gT("Success")."</div>&nbsp;<br />\n"
            .$clang->gT("File upload succeeded.")."<br /><br />\n"
            .$clang->gT("Reading file..")."<br /><br />\n";
            if (strtolower($sExtension)=='csv')
            {
                $aImportResults=CSVImportGroup($sFullFilepath, $surveyid);
            }
            elseif (strtolower($sExtension)=='lsg')
            {
                $aImportResults=XMLImportGroup($sFullFilepath, $surveyid);
            }
            else die('Unknown file extension');
            FixLanguageConsistency($surveyid);

            if (isset($aImportResults['fatalerror']))
            {
                $importgroup .= "<div class='warningheader'>".$clang->gT("Error")."</div><br />\n";
                $importgroup .= $aImportResults['fatalerror']."<br /><br />\n";
                $importgroup .= "<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\" />\n";
                $importgroup .=  "</div>\n";
                unlink($sFullFilepath);
                show_error($importgroup);
                return;
            }

            $importgroup .= "<div class='successheader'>".$clang->gT("Success")."</div><br />\n"
            ."<strong><u>".$clang->gT("Question group import summary")."</u></strong><br />\n"
            ."<ul style=\"text-align:left;\">\n"
            ."\t<li>".$clang->gT("Groups").": ".$aImportResults['groups']."</li>\n"
            ."\t<li>".$clang->gT("Questions").": ".$aImportResults['questions']."</li>\n"
            ."\t<li>".$clang->gT("Subquestions").": ".$aImportResults['subquestions']."</li>\n"
            ."\t<li>".$clang->gT("Answers").": ".$aImportResults['answers']."</li>\n"
            ."\t<li>".$clang->gT("Conditions").": ".$aImportResults['conditions']."</li>\n";
            if (strtolower($sExtension)=='csv')  {
                $importgroup.="\t<li>".$clang->gT("Label sets").": ".$aImportResults['labelsets']." (".$aImportResults['labels'].")</li>\n";
            }
            $importgroup.="\t<li>".$clang->gT("Question attributes:").$aImportResults['question_attributes']."</li>"
            ."</ul>\n";

            $importgroup .= "<strong>".$clang->gT("Question group import is complete.")."</strong><br />&nbsp;\n";
            $importgroup .= "<input type='submit' value='".$clang->gT("Go to question group")."' onclick=\"window.open('".site_url('admin/survey/view/'.$surveyid.'/'.$aImportResults['newgid'])."', '_top')\" />\n";
            $importgroup .= "</div><br />\n";

            unlink($sFullFilepath);

            $data['display'] = $importgroup;
            $this->load->view('survey_view',$data);


        }
        self::_loadEndScripts();

        self::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
    }


    /**
    * questiongroup::add()
    * Load add new question grup screen.
    * @return
    */
    function add($surveyid)
    {
        $surveyid = sanitize_int($surveyid);

        if(bHasSurveyPermission($surveyid,'surveycontent','read'))
        {
            $action = "addgroup";//$this->input->post('action');
            $clang = $this->limesurvey_lang;

            $css_admin_includes[] = $this->config->item('styleurl')."admin/default/superfish.css";
            $this->config->set_item("css_admin_includes", $css_admin_includes);
            self::_getAdminHeader();
            self::_showadminmenu();
            self::_surveybar($surveyid);
            self::_surveysummary($surveyid,"addgroup");
            if ($action == "addgroup")
            {
                $this->load->helper('admin/htmleditor');
                $this->load->helper('surveytranslator');
                $grplangs = GetAdditionalLanguagesFromSurveyID($surveyid);
                $baselang = GetBaseLanguageFromSurveyID($surveyid);
                $grplangs[] = $baselang;
                $grplangs = array_reverse($grplangs);

                $data['clang'] = $clang;
                $data['surveyid'] = $surveyid;
                $data['action'] = $action;
                $data['grplangs'] = $grplangs;
                $this->load->view('admin/survey/QuestionGroups/addGroup_view',$data);
            }

            self::_loadEndScripts();

            self::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
        }


    }

    /**
    * questiongroup::delete()
    * Function responsible for deleting a question group.
    * @return
    */
    function delete()
    {
        $action = $this->input->post("action");
        $surveyid = $this->input->post("sid");
        $gid = $this->input->post("gid");
        $clang = $this->limesurvey_lang;
        if ($action == "delgroup" && bHasSurveyPermission($surveyid, 'surveycontent','delete'))
        {
            $this->load->helper('database');
            if (!isset($gid)) $gid=returnglobal('gid');
            $query = "SELECT qid FROM ".$this->db->dbprefix."groups g, ".$this->db->dbprefix."questions q WHERE g.gid=q.gid AND g.gid=$gid AND q.parent_qid=0 group by qid";
            if ($result = db_execute_assoc($query)) // Checked
            {
                foreach ($result->result_array() as $row)
                {
                    db_execute_assoc("DELETE FROM ".$this->db->dbprefix."conditions WHERE qid={$row['qid']}");    // Checked
                    db_execute_assoc("DELETE FROM ".$this->db->dbprefix."question_attributes WHERE qid={$row['qid']}"); // Checked
                    db_execute_assoc("DELETE FROM ".$this->db->dbprefix."answers WHERE qid={$row['qid']}"); // Checked
                    db_execute_assoc("DELETE FROM ".$this->db->dbprefix."questions WHERE qid={$row['qid']} or parent_qid={$row['qid']}"); // Checked
                    db_execute_assoc("DELETE FROM ".$this->db->dbprefix."defaultvalues WHERE qid={$row['qid']}"); // Checked
                    db_execute_assoc("DELETE FROM ".$this->db->dbprefix."quota_members WHERE qid={$qid}");
                }
            }
            $query = "DELETE FROM ".$this->db->dbprefix."assessments WHERE sid=$surveyid AND gid=$gid";
            $result = db_execute_assoc($query) ; //or safe_die($connect->ErrorMsg());  // Checked

            $query = "DELETE FROM ".$this->db->dbprefix."groups WHERE sid=$surveyid AND gid=$gid";
            $result = db_execute_assoc($query); // or safe_die($connect->ErrorMsg());  // Checked
            if ($result)
            {
                $gid = "";
                $groupselect = getgrouplist($gid,$surveyid);
                fixSortOrderGroups($surveyid);
                $this->session->set_userdata('flashmessage', $clang->gT("The question group was deleted."));
            }
            else
            {
                $databaseoutput = "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Group could not be deleted","js")."\")\n //-->\n</script>\n";
            }

            if ($databaseoutput != '')
            {
                echo $databaseoutput;
            }
            else
            {
                redirect(site_url('admin/survey/view/'.$surveyid));
            }
        }
    }

    /**
    * questiongroup::edit()
    * Load editing of a question group screen.
    * @return
    */
    function edit($surveyid,$gid)
    {

        $this->load->model('groups_model');
        $surveyid = sanitize_int($surveyid);
        $gid = sanitize_int($gid);

        if(bHasSurveyPermission($surveyid,'surveycontent','read'))
        {

            $action = "editgroup";//$this->input->post('action');
            $clang = $this->limesurvey_lang;

            $css_admin_includes[] = $this->config->item('styleurl')."admin/default/superfish.css";
            $this->config->set_item("css_admin_includes", $css_admin_includes);
            self::_getAdminHeader();
            self::_showadminmenu();
            self::_surveybar($surveyid,$gid);

            if ($action == "editgroup")
            {

                $this->load->helper('admin/htmleditor');
                $this->load->helper('surveytranslator');
                $this->load->helper('database');

                $aAdditionalLanguages = GetAdditionalLanguagesFromSurveyID($surveyid);
                $aBaseLanguage = GetBaseLanguageFromSurveyID($surveyid);

                $aLanguages=array_merge(array($aBaseLanguage),$aAdditionalLanguages);

                $grplangs=array_flip($aLanguages);
                // Check out the intgrity of the language versions of this group
                $egquery = "SELECT * FROM ".$this->db->dbprefix."groups WHERE sid=$surveyid AND gid=$gid";
                $egresult = db_execute_assoc($egquery);
                foreach ($egresult->result_array() as $esrow)
                {
                    if(!in_array($esrow['language'], $aLanguages)) // Language Exists, BUT ITS NOT ON THE SURVEY ANYMORE.
                    {
                        $egquery = "DELETE FROM ".$this->db->dbprefix."groups WHERE sid='{$surveyid}' AND gid='{$gid}' AND language='".$esrow['language']."'";
                        $egresultD = db_execute_assoc($egquery);
                    } else {
                        $grplangs[$esrow['language']] = 'exists';
                    }
                    if ($esrow['language'] == $aBaseLanguage) $basesettings = $esrow;
                }

                // Create groups in missing languages
                while (list($key,$value) = each($grplangs))
                {
                    if ($value != 'exists')
                    {
                        $basesettings['language']=$key;
                        $this->groups_model->insertRecords($basesettings);
                    }
                }
                $first=true;
                foreach ($aLanguages as $sLanguage)
                {
                    $oResult=$this->groups_model->getAllRecords(array('sid'=>$surveyid,'gid'=>$gid,'language'=>$sLanguage));
                    $data['aGroupData'][$sLanguage]=$oResult->row_array();
                    $aTabTitles[$sLanguage] = getLanguageNameFromCode($sLanguage,false);
                    if($first){
                        $aTabTitles[$sLanguage].= ' ('.$clang->gT("Base language").')';
                        $first=false;
                    }

                }
                $data['action'] = "editgroup";
                $data['clang'] = $clang;
                $data['surveyid'] = $surveyid;
                $data['gid'] = $gid;
                $data['tabtitles']=$aTabTitles;
                $data['aBaseLanguage']=$aBaseLanguage;


                $this->load->view('admin/survey/QuestionGroups/editGroup_view',$data);
            }
        }
        self::_loadEndScripts();

        self::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
    }

    function update($gid)
    {
        $gid= (int)$gid;
        $this->load->model('groups_model');

        $surveyid= $this->groups_model->getSurveyIDFromGroup($gid);
        if (bHasSurveyPermission($surveyid, 'surveycontent','update'))
        {
            $this->load->helper('surveytranslator');
            $this->load->helper('database');

            $grplangs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            array_push($grplangs,$baselang);
            //require_once("../classes/inputfilter/class.inputfilter_clean.php");
            //$myFilter = new InputFilter('','',1,1,1);
            foreach ($grplangs as $grplang)
            {
                if (isset($grplang) && $grplang != "")
                {
                    /**if ($filterxsshtml)
                    {
                        $_POST['group_name_'.$grplang]=$myFilter->process($_POST['group_name_'.$grplang]);
                        $_POST['description_'.$grplang]=$myFilter->process($_POST['description_'.$grplang]);
                    }
                    else
                    {
                        $_POST['group_name_'.$grplang] = html_entity_decode($_POST['group_name_'.$grplang], ENT_QUOTES, "UTF-8");
                        $_POST['description_'.$grplang] = html_entity_decode($_POST['description_'.$grplang], ENT_QUOTES, "UTF-8");
                    } */

                    // Fix bug with FCKEditor saving strange BR types
                    $group_name = $this->input->post('group_name_'.$grplang);
                    $group_description = $this->input->post('description_'.$grplang);

                    $group_name=fix_FCKeditor_text($group_name);
                    $group_description=fix_FCKeditor_text($group_description);

                    $data = array (
                            'group_name' => $group_name,
                            'description' => $group_description,
                            'randomization_group' => $this->input->post('randomization_group')
                        );
                    $condition = array (
                        'gid' => $gid,
                        'sid' => $surveyid,
                        'language' => $grplang
                    );
                    $this->load->model('groups_model');
                    $ugresult = $this->groups_model->update($data,$condition); //$connect->Execute($ugquery);  // Checked
                    if ($ugresult)
                    {
                        $groupsummary = getgrouplist($gid,$surveyid);
                    }
                    else
                    {
                        $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Group could not be updated","js")."\")\n //-->\n</script>\n";

                    }
                }
            }
            $this->session->set_userdata('flashmessage', $this->limesurvey_lang->gT("Question group successfully saved."));
            redirect(site_url('admin/survey/view/'.$surveyid.'/'.$gid));
        }
    }

    /**
    * questiongroup::organize()
    * Load ordering of question group screen.
    * @return
    */
    function organize($iSurveyID)
    {
        $iSurveyID= (int)$iSurveyID;
        $this->load->model('groups_model');
        $this->load->model('questions_model');

        if ($this->input->post('orgdata') && bHasSurveyPermission($iSurveyID,'surveycontent','update'))
        {
            $AOrgData=array();
            parse_str($this->input->post('orgdata'),$AOrgData);
            $grouporder=0;
            foreach($AOrgData['list'] as $ID=>$parent)
            {
                if ($parent=='root' && $ID[0]=='g'){
                    $this->groups_model->update(array('group_order'=>$grouporder),array('gid'=>(int)substr($ID,1)));
                    $grouporder++;
                }
                elseif ($ID[0]=='q')
                {
                    if (!isset($questionorder[(int)substr($parent,1)])) $questionorder[(int)substr($parent,1)]=0;
                    $this->questions_model->update(array('question_order'=>$questionorder[(int)substr($parent,1)],'gid'=>(int)substr($parent,1)),array('qid'=>(int)substr($ID,1)));
                    $this->questions_model->update(array('gid'=>(int)substr($parent,1)),array('parent_qid'=>(int)substr($ID,1)));
                    $questionorder[(int)substr($parent,1)]++;
                }
            }
            $this->session->set_userdata('flashmessage', $this->limesurvey_lang->gT("The new question group/question order was successfully saved."));
            redirect('admin/survey/view/'.$iSurveyID);
        }

        // Prepare data for the view
        $sBaseLanguage=GetBaseLanguageFromSurveyID($iSurveyID);
        self::_js_admin_includes(base_url().'scripts/jquery/jquery.ui.nestedSortable.js');
        self::_js_admin_includes(base_url().'scripts/admin/organize.js');

        LimeExpressionManager::StartProcessingPage(false,true,false);
        $aGrouplist=$this->groups_model->getGroups($iSurveyID);
        foreach($aGrouplist as $iGID=>$aGroup)
        {
            LimeExpressionManager::StartProcessingGroup($aGroup['gid'],false,$iSurveyID);
            $oQuestionData=$this->questions_model->getQuestions($iSurveyID,$aGroup['gid'],$sBaseLanguage);
            $qs = array();
            $junk=array();
            foreach ($oQuestionData->result_array() as $q) {
                $question = '[{' . $q['relevance'] . '}] ' . $q['question'];
                LimeExpressionManager::ProcessString($question,$q['qid'],$junk,false,1,1);
                $q['question'] = LimeExpressionManager::GetLastPrettyPrintExpression();
//                log_message('debug',$q['question']);
                $qs[] = $q;
            }
            $aGrouplist[$iGID]['questions']=$qs;
        }
        LimeExpressionManager::FinishProcessingPage();
        $aViewData['aGroupsAndQuestions']=$aGrouplist;
        $aViewData['clang']=$this->limesurvey_lang;
        $aViewData['surveyid']=$iSurveyID;
        $css_admin_includes[] = $this->config->item('styleurl')."admin/default/superfish.css";
        $this->config->set_item("css_admin_includes", $css_admin_includes);
        self::_getAdminHeader();
        self::_showadminmenu();
        self::_surveybar($iSurveyID);
        $this->load->view('admin/survey/organizeGroupsAndQuestions_view',$aViewData);
        self::_loadEndScripts();
        self::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
    }


}