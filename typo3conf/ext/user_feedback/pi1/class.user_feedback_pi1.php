<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Marc-Antoine TREHIN <marcantoine.trehin@azeliz.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
if (!class_exists('SpreadSheetModel')) {
    require_once(t3lib_extMgm::extPath('user_feedback') . 'lib/class.user_feedback_spreadsheetmodel.php');
}
/**
 * Plugin 'FeedBack' for the 'user_feedback' extension.
 *
 * @author    Marc-Antoine TREHIN <marcantoine.trehin@azeliz.com>
 * @package    TYPO3
 * @subpackage    user_feedback
 */
class user_feedback_pi1 extends tslib_pibase {
    var $prefixId      = 'user_feedback_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.user_feedback_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey        = 'user_feedback';    // The extension key.
    
    /**
     * The main method of the PlugIn
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     * @return    The content that is displayed on the website
     */
    function main($content, $conf) {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        $this->pi_USER_INT_obj = 1;    // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
    
        if (isset($this->piVars['DATA']['commentaire'])) {
            //t3lib_div::debug($this->piVars);
            
            $ssModel = new SpreadSheetModel('marcantoine.trehin@azeliz.com', 'tev7g862');
            $newLine = array(
                'timestamp' => date("d/m/Y")." ".date("H:i"),
                'commentaire' => $this->piVars['DATA']['commentaire'],
                'page' => $this->piVars['DATA']['page']
            );
            
            $ssModel->insertRow($newLine,'od6', 't-1yLc2_WCI-9hCVioZwEkg');
            $content = null;
        
        } else {
            // BOF Erwand 26/01/11 dans une url, il ne faut pas mettre les caractère /.....
            $content = '<div id="feedback">'.
                '<p style="text-align: center;">Une remarque à faire sur cette page ?</p>'.
                        '<script type="text/javascript">'.
                            'function ajaxfeedback(link) {'.
                                'var dataString = "'.$this->prefixId.'[DATA][commentaire]=" + $(\'#'.$this->prefixId.'-commentaire\').val()'.
                                    '+ "&'.$this->prefixId.'[DATA][page]=" + encodeURIComponent(window.location.href);'.
                                '$.ajax({'.
                                    'url: link,'.
                                    'data: dataString,'.
                                    'success: function(data) {'.
                                        '$(\'#feedback\').css(\'display\', \'none\');'.
                                        '$(\'#feedback-result\').html(\'Votre commentaire a bien été envoyé !\');'.
                                    '}'.
                                '})'.
                            '}'.
                        '</script>'.
                        '<form style="text-align:center;" action="" method="post">'.
                        '    <textarea style="resize:none;" id="'.$this->prefixId.'-commentaire" name="'.$this->prefixId.'[DATA][commentaire]" cols="50" rows="10"></textarea><br />'.
                        '    <input id="'.$this->prefixId.'-page" name="'.$this->prefixId.'[DATA][page]" type="hidden">'.
                        '    <input style="margin-top:10px;" id="'.$this->prefixId.'-submit_button" name="'.$this->prefixId.'[DATA][submit_button]" type="button" value="Envoyer" onclick="javascript:ajaxfeedback(\''.$this->pi_getPageLink($GLOBALS["TSFE"]->id).'\')">'.
                        '</form>'.
                        '</div>'.
                        '<p id="feedback-result"></p>';
        }

        return $this->pi_wrapInBaseClass($content);
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_feedback/pi1/class.user_feedback_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_feedback/pi1/class.user_feedback_pi1.php']);
}

?>
