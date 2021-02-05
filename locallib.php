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
 * @package    local_cohortrole
 * @copyright  2013 Paul Holden (pholden@greenhead.ac.uk)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('LOCAL_COHORTROLE_ROLE_COMPONENT', 'local_cohortrole');

/**
 * Assign users to a role; using local role component
 *
 * @param integer $cohortid the id of a cohort
 * @param integer $roleid the id of a role
 * @param array $userids an array of user ids to assign
 * @return void
 */
function local_cohortrole_role_assign($cohortid, $roleid, array $userids) {
    global $DB;

    $context = context_system::instance();
    foreach ($userids as $userid) {
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        try {
            $active = core_user::require_active_user($user);
            role_assign($roleid, $userid, $context->id, LOCAL_COHORTROLE_ROLE_COMPONENT, $cohortid);
        } catch (Exception $e) {
            // Exception is caught. Do nothing.
        }
    }
}

/**
 * Unassign users from a role; using local role component
 *
 * @param integer $cohortid the id of a cohort
 * @param integer $roleid the id of a role
 * @param array $userids an array of user ids to unassign
 * @return void
 */
function local_cohortrole_role_unassign($cohortid, $roleid, array $userids) {
    $context = context_system::instance();

    foreach ($userids as $userid) {
        role_unassign($roleid, $userid, $context->id, LOCAL_COHORTROLE_ROLE_COMPONENT, $cohortid);
    }
}

/**
 * Add users to a role that synchronizes from a cohort
 *
 * @param integer $cohortid the id of a cohort
 * @param integer $roleid the id of a role
 * @return void
 */
function local_cohortrole_synchronize($cohortid, $roleid) {
    global $DB;

    $userids = $DB->get_records_menu('cohort_members', array('cohortid' => $cohortid), null, 'id, userid');

    local_cohortrole_role_assign($cohortid, $roleid, $userids);
}

/**
 * Remove users from a role that was synchronized from a cohort
 *
 * @param integer $cohortid the id of a cohort
 * @param integer|null $roleid the id of a role, all roles if null
 * @return void
 */
function local_cohortrole_unsynchronize($cohortid, $roleid = null) {
    $params = array(
        'contextid' => context_system::instance()->id, 'component' => LOCAL_COHORTROLE_ROLE_COMPONENT, 'itemid' => $cohortid);

    if ($roleid === null) {
        $roleids = local_cohortrole_get_cohort_roles($cohortid);
    } else {
        $roleids = array($roleid);
    }

    foreach ($roleids as $roleid) {
        $params['roleid'] = $roleid;

        role_unassign_all($params, false, false);
    }
}

/**
 * Get roles defined as being populated by a cohort
 *
 * @param integer $cohortid the id of a cohort
 * @return array role ids
 */
function local_cohortrole_get_cohort_roles($cohortid) {
    global $DB;

    return $DB->get_records_menu('local_cohortrole', array('cohortid' => $cohortid), null, 'id, roleid');
}
