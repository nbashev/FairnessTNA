<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package API\Core
 */
class APIAuthorization extends APIFactory
{
    protected $main_class = 'AuthorizationFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default authorization data for creating new authorizations.
     * @return array
     */
    public function getAuthorizationDefaultData()
    {
        Debug::Text('Getting authorization default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array();

        return $this->returnHandler($data);
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonAuthorizationData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getAuthorization($data, true)));
    }

    /**
     * Get authorization data for one or more authorizations.
     * @param array $data filter data
     * @return array
     */
    public function getAuthorization($data = null, $disable_paging = false)
    {
        Debug::Arr($data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

        //Keep in mind administrators doing authorization often have access to ALL requests, or ALL users, so permission_children won't come into play.
        //Users should be able to see authorizations for their own requests.
        if (isset($data['filter_data']['object_type_id']) and in_array($data['filter_data']['object_type_id'], array(1010, 1020, 1030, 1040, 1100))) { //Requests
            Debug::Text('Request object_type_id: ' . $data['filter_data']['object_type_id'], __FILE__, __LINE__, __METHOD__, 10);

            if (!$this->getPermissionObject()->Check('request', 'enabled')
                or !($this->getPermissionObject()->Check('request', 'view') or $this->getPermissionObject()->Check('request', 'view_own') or $this->getPermissionObject()->Check('request', 'view_child'))
            ) {
                return $this->getPermissionObject()->PermissionDenied();
            }

            $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('request', 'view');
        } elseif (isset($data['filter_data']['object_type_id']) and in_array($data['filter_data']['object_type_id'], array(90))) { //Timesheets
            Debug::Text('TimeSheet object_type_id: ' . $data['filter_data']['object_type_id'], __FILE__, __LINE__, __METHOD__, 10);

            if (!$this->getPermissionObject()->Check('punch', 'enabled')
                or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_own') or $this->getPermissionObject()->Check('punch', 'view_child'))
            ) {
                return $this->getPermissionObject()->PermissionDenied();
            }

            $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('punch', 'view');
        } elseif (isset($data['filter_data']['object_type_id']) and in_array($data['filter_data']['object_type_id'], array(200))) { // Expense
            Debug::Text('Expense object_type_id: ' . $data['filter_data']['object_type_id'], __FILE__, __LINE__, __METHOD__, 10);

            if (!$this->getPermissionObject()->Check('user_expense', 'enabled')
                or !($this->getPermissionObject()->Check('user_expense', 'view') or $this->getPermissionObject()->Check('user_expense', 'view_own') or $this->getPermissionObject()->Check('user_expense', 'view_child'))
            ) {
                return $this->getPermissionObject()->PermissionDenied();
            }

            $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('user_expense', 'view');
        } else {
            //Invalid or not specified object_type_id
            Debug::Text('No valid object_type_id specified...', __FILE__, __LINE__, __METHOD__, 10);
            return $this->getPermissionObject()->PermissionDenied();
        }
        //Debug::Arr($data['filter_data']['permission_children_ids'], 'Permission Children: ', __FILE__, __LINE__, __METHOD__, 10);

        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $blf = TTnew('AuthorizationListFactory');
        $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Validate authorization data for one or more authorizations.
     * @param array $data authorization data
     * @return array
     */
    public function validateAuthorization($data)
    {
        return $this->setAuthorization($data, true);
    }

    /**
     * Set authorization data for one or more authorizations.
     * @param array $data authorization data
     * @return array
     */
    public function setAuthorization($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!($this->getPermissionObject()->Check('request', 'authorize') or $this->getPermissionObject()->Check('punch', 'authorize') or $this->getPermissionObject()->Check('user_expense', 'authorize'))) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' Authorizations', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = $tertiary_validator = new Validator();
                $lf = TTnew('AuthorizationListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get authorization object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('request', 'authorize')
                                or
                                $this->getPermissionObject()->Check('punch', 'authorize')
                                or
                                $this->getPermissionObject()->Check('user_expense', 'authorize')
                            )
                        ) {
                            Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();
                            $row = array_merge($lf->getObjectAsArray(), $row);
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Edit permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Edit permission denied, record does not exist'));
                    }
                } //else {
                //Adding new object, check ADD permissions.
                //$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('authorization', 'add'), TTi18n::gettext('Add permission denied') );
                //}
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    //Handle authorizing timesheets that have no PPTSVF records yet.
                    if (isset($row['object_type_id']) and $row['object_type_id'] == 90
                        and isset($row['object_id']) and $row['object_id'] == -1
                        and isset($row['user_id']) and isset($row['pay_period_id'])
                    ) {
                        $api_ts = new APITimeSheet();
                        $api_raw_retval = $api_ts->verifyTimeSheet($row['user_id'], $row['pay_period_id']);
                        Debug::Arr($api_raw_retval, 'API Retval: ', __FILE__, __LINE__, __METHOD__, 10);
                        $api_retval = $this->stripReturnHandler($api_raw_retval);
                        if ($api_retval > 0) {
                            $row['object_id'] = $api_retval;
                        } else {
                            $tertiary_validator = $this->convertAPIreturnHandlerToValidatorObject($api_raw_retval, $tertiary_validator);
                            $is_valid = $tertiary_validator->isValid($ignore_warning);
                        }
                    }

                    if ($is_valid == true) {
                        Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);
                        $lf->setObjectFromArray($row);

                        //Set the current user so we know who is doing the authorization.
                        $lf->setCurrentUser($this->getCurrentUserObject()->getId());

                        $is_valid = $lf->isValid($ignore_warning);
                        if ($is_valid == true) {
                            Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
                            if ($validate_only == true) {
                                $save_result[$key] = true;
                                $validator_stats['valid_records']++;
                            } else {
                                $save_result[$key] = $lf->Save(false);

                                //Make sure we test for validation failures after Save() is called, especially in cases of advanced requests, as the addRelatedSchedules() could fail.
                                if ($lf->isValid($ignore_warning) == false) {
                                    Debug::Arr($lf->Validator->getErrors(), 'PostSave() returned a validation error!', __FILE__, __LINE__, __METHOD__, 10);
                                    $is_valid = false;
                                } else {
                                    $validator_stats['valid_records']++;
                                }
                            }
                        }
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf, $tertiary_validator);
                } elseif ($validate_only == true) {
                    $lf->FailTransaction();
                }

                $lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more authorizations.
     * @param array $data authorization data
     * @return array
     */
    public function deleteAuthorization($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!($this->getPermissionObject()->Check('request', 'authorize') or $this->getPermissionObject()->Check('punch', 'authorize') or $this->getPermissionObject()->Check('user_expense', 'authorize'))) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' Authorizations', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('AuthorizationListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get authorization object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $this->getPermissionObject()->Check('request', 'authorize')
                            or
                            $this->getPermissionObject()->Check('punch', 'authorize')
                            or
                            $this->getPermissionObject()->Check('user_expense', 'authorize')
                        ) {
                            Debug::Text('Record Exists, deleting record: ', $id, __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Delete permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, record does not exist'));
                    }
                } else {
                    $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, record does not exist'));
                }

                //Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid();
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10);
                    $lf->setDeleted(true);

                    $is_valid = $lf->isValid();
                    if ($is_valid == true) {
                        Debug::Text('Record Deleted...', __FILE__, __LINE__, __METHOD__, 10);
                        $save_result[$key] = $lf->Save();
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf);
                }

                $lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }
}
