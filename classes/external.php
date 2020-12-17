<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is the external API for this tool.
 *
 * @package    mod_customcert
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_customcert;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * This is the external API for this tool.
 *
 * @copyright  2016 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api {

    /**
     * Returns the save_element() parameters.
     *
     * @return \external_function_parameters
     */
    public static function save_element_parameters() {
        return new \external_function_parameters(
            array(
                'templateid' => new \external_value(PARAM_INT, 'The template id'),
                'elementid' => new \external_value(PARAM_INT, 'The element id'),
                'values' => new \external_multiple_structure(
                    new \external_single_structure(
                        array(
                            'name' => new \external_value(PARAM_ALPHANUMEXT, 'The field to update'),
                            'value' => new \external_value(PARAM_RAW, 'The value of the field'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Handles saving element data.
     *
     * @param int $templateid The template id.
     * @param int $elementid The element id.
     * @param array $values The values to save
     * @return array
     */
    public static function save_element($templateid, $elementid, $values) {
        global $DB;

        $params = array(
            'templateid' => $templateid,
            'elementid' => $elementid,
            'values' => $values
        );
        self::validate_parameters(self::save_element_parameters(), $params);

        $template = $DB->get_record('customcert_templates', array('id' => $templateid), '*', MUST_EXIST);
        $element = $DB->get_record('customcert_elements', array('id' => $elementid), '*', MUST_EXIST);

        // Set the template.
        $template = new \mod_customcert\template($template);

        // Perform checks.
        if ($cm = $template->get_cm()) {
            self::validate_context(\context_module::instance($cm->id));
        } else {
            self::validate_context(\context_system::instance());
        }
        // Make sure the user has the required capabilities.
        $template->require_manage();

        // Set the values we are going to save.
        $data = new \stdClass();
        $data->id = $element->id;
        $data->name = $element->name;
        foreach ($values as $value) {
            $field = $value['name'];
            $data->$field = $value['value'];
        }

        // Get an instance of the element class.
        if ($e = \mod_customcert\element_factory::get_element_instance($element)) {
            return $e->save_form_elements($data);
        }

        return false;
    }

    /**
     * Returns the save_element result value.
     *
     * @return \external_value
     */
    public static function save_element_returns() {
        return new \external_value(PARAM_BOOL, 'True if successful, false otherwise');
    }

    /**
     * Returns get_element() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_element_html_parameters() {
        return new \external_function_parameters(
            array(
                'templateid' => new \external_value(PARAM_INT, 'The template id'),
                'elementid' => new \external_value(PARAM_INT, 'The element id'),
            )
        );
    }

    /**
     * Handles return the element's HTML.
     *
     * @param int $templateid The template id
     * @param int $elementid The element id.
     * @return string
     */
    public static function get_element_html($templateid, $elementid) {
        global $DB;

        $params = array(
            'templateid' => $templateid,
            'elementid' => $elementid
        );
        self::validate_parameters(self::get_element_html_parameters(), $params);

        $template = $DB->get_record('customcert_templates', array('id' => $templateid), '*', MUST_EXIST);
        $element = $DB->get_record('customcert_elements', array('id' => $elementid), '*', MUST_EXIST);

        // Set the template.
        $template = new \mod_customcert\template($template);

        // Perform checks.
        if ($cm = $template->get_cm()) {
            self::validate_context(\context_module::instance($cm->id));
        } else {
            self::validate_context(\context_system::instance());
        }

        // Get an instance of the element class.
        if ($e = \mod_customcert\element_factory::get_element_instance($element)) {
            return $e->render_html();
        }

        return '';
    }

    /**
     * Returns the get_element result value.
     *
     * @return \external_value
     */
    public static function get_element_html_returns() {
        return new \external_value(PARAM_RAW, 'The HTML');
    }

    /**
     * Returns the delete_issue() parameters.
     *
     * @return \external_function_parameters
     */
    public static function delete_issue_parameters() {
        return new \external_function_parameters(
            array(
                'certificateid' => new \external_value(PARAM_INT, 'The certificate id'),
                'issueid' => new \external_value(PARAM_INT, 'The issue id'),
            )
        );
    }

    /**
     * Handles deleting a customcert issue.
     *
     * @param int $certificateid The certificate id.
     * @param int $issueid The issue id.
     * @return bool
     */
    public static function delete_issue($certificateid, $issueid) {
        global $DB;

        $params = [
            'certificateid' => $certificateid,
            'issueid' => $issueid
        ];
        self::validate_parameters(self::delete_issue_parameters(), $params);

        $certificate = $DB->get_record('customcert', ['id' => $certificateid], '*', MUST_EXIST);
        $issue = $DB->get_record('customcert_issues', ['id' => $issueid, 'customcertid' => $certificateid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('customcert', $certificate->id, 0, false, MUST_EXIST);

        // Make sure the user has the required capabilities.
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/customcert:manage', $context);

        // Delete the issue.
        return $DB->delete_records('customcert_issues', ['id' => $issue->id]);
    }

    /**
     * Returns the delete_issue result value.
     *
     * @return \external_value
     */
    public static function delete_issue_returns() {
        return new \external_value(PARAM_BOOL, 'True if successful, false otherwise');
    }


    /*
    Devuelve el pdf del certificado cuando solo hay un certificado
    en el curso, pasandole como parámetro el curseid y el userid
    */

    /**
     * Returns the parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_customcert_course_pdf_parameters() {
        return new \external_function_parameters(
            array(
                'userid' => new \external_value(PARAM_INT, 'id of user'),
                'courseid' => new \external_value(PARAM_INT, 'id of course'),                    
            )
        );
    }
    public static function get_customcert_course_pdf_returns() {
        return new \external_value(PARAM_RAW, 'The Cert');
    }

    /**
     * Return PDF Cert.
     *
     * @param int $userid The template id
     * @param int $courseid The element id.
     * @return string
     */
    public static function get_customcert_course_pdf($userid, $courseid) {
        global $CFG, $DB;

        $result = array();
        $params = self::validate_parameters(self::get_customcert_course_pdf_parameters(), 
                                            array('userid'=>$userid,
                                                  'courseid'=>$courseid));
        
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $customcert = $DB->get_record('customcert', array('course' => $courseid), '*', MUST_EXIST);
        $template = $DB->get_record('customcert_templates', array('id' => $customcert->templateid), '*', MUST_EXIST);
        $template = new \mod_customcert\template($template);
        return $template->generate_pdf(false, $userid);
        
        
        
    }


    /*
    Devuelve el courseid y el certid para posteriormente descargar el certificado correcto
    */

    /**
     * Returns the parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_customcert_user_parameters() {
        return new \external_function_parameters(
            array(
                'userid' => new \external_value(PARAM_INT, 'id of user'),
            )
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.2
     */
    public static function get_customcert_user_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'courseid' => new \external_value(PARAM_INT, 'id of course'),
                    'certid' => new \external_value(PARAM_INT, 'id of template cert')
                    )
            )   
        );

    }

    /**
     * Returns the courseid's where the user has a certificate available..
     *
     * @param int $userid The user id
     * @return array
     */
    public static function get_customcert_user($userid) {
        global $CFG, $DB;

        $certs = array();
        $params = self::validate_parameters(self::get_customcert_user_parameters(), 
                                            array('userid'=>$userid));
        $user   = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $customcert_isues = $DB->get_records('customcert_issues', array('userid' => $userid));
        foreach ($customcert_isues as $issue){
            $customcert = $DB->get_record('customcert', array('id' => $issue->customcertid), '*', MUST_EXIST);
            $certs[] = array(
                'courseid' => $customcert->course,
                'certid' => $customcert->templateid
            );
        }
        return $certs;
    }


    /*
    Devuelve el pdf del certificado cuando hay más de un
    certificado en el curso. Pasandole el templateid (certid)
    */

    /**
     * Returns the parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_customcert_cert_pdf_parameters() {
        return new \external_function_parameters(
            array(
                'userid' => new \external_value(PARAM_INT, 'id of user'),
                'certid' => new \external_value(PARAM_FLOAT, 'id of cert'),                    
            )
        );
    }
    public static function get_customcert_cert_pdf_returns() {
        return new \external_value(PARAM_RAW, 'The Cert');
    }

    /**
     * Return PDF Cert.
     *
     * @param int $userid The template id
     * @param int $courseid The element id.
     * @return string
     */
    public static function get_customcert_cert_pdf($userid, $certid) {
        global $CFG, $DB;

        $result = array();
        $params = self::validate_parameters(self::get_customcert_cert_pdf_parameters(), 
                                            array('userid'=>$userid,
                                                  'certid'=>$certid));
        $template = $DB->get_record('customcert_templates', array('id' => $certid), '*', MUST_EXIST);
        $template = new \mod_customcert\template($template);
        return $template->generate_pdf(false, $userid);
    }


}
