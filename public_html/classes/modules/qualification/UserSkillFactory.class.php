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
 * @package Modules\Qualification
 */
class UserSkillFactory extends Factory
{
    protected $table = 'user_skill';
    protected $pk_sequence_name = 'user_skill_id_seq'; //PK Sequence name
    protected $qualification_obj = null;

    //protected $experience_validator_regex = '/^[0-9]{1,250}$/i';
    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'proficiency':
                $retval = array(
                    10 => TTi18n::gettext('Excellent'),
                    20 => TTi18n::gettext('Very Good'),
                    30 => TTi18n::gettext('Good'),
                    40 => TTi18n::gettext('Above Average'),

                    50 => TTi18n::gettext('Average'),

                    60 => TTi18n::gettext('Below Average'),
                    70 => TTi18n::gettext('Fair'),
                    80 => TTi18n::gettext('Poor'),
                    90 => TTi18n::gettext('Bad'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),
                    '-2050-qualification' => TTi18n::gettext('Skill'),
                    '-2040-group' => TTi18n::gettext('Group'),
                    '-2060-proficiency' => TTi18n::gettext('Proficiency'),
                    '-2070-experience' => TTi18n::gettext('Experience'),
                    '-2080-first_used_date' => TTi18n::gettext('First Used Date'),
                    '-2090-last_used_date' => TTi18n::gettext('Last Used Date'),
                    '-3010-enable_calc_experience' => TTi18n::gettext('Automatic Experience'),
                    '-3020-expiry_date' => TTi18n::gettext('Expiry Date'),
                    '-1040-description' => TTi18n::getText('Description'),

                    '-1300-tag' => TTi18n::gettext('Tags'),


                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-user_group' => TTi18n::gettext('Employee Group'),
                    '-1100-default_branch' => TTi18n::gettext('Branch'),
                    '-1110-default_department' => TTi18n::gettext('Department'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'qualification',
                    'proficiency',
                    'experience',
                    'first_used_date',
                    'last_used_date',
                    //'enable_calc_experience',
                    'expiry_date',
                    'description',
                );
                break;

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'user_id' => 'User',
            'first_name' => false,
            'last_name' => false,
            'qualification_id' => 'Qualification',
            'qualification' => false,
            'group' => false,
            'proficiency_id' => 'Proficiency',
            'proficiency' => false,
            'experience' => 'Experience',
            'first_used_date' => 'FirstUsedDate',
            'last_used_date' => 'LastUsedDate',
            'enable_calc_experience' => 'EnableCalcExperience',
            'expiry_date' => 'ExpiryDate',
            'description' => 'Description',
            'tag' => 'Tag',

            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('user_id',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid Employee')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setQualification($id)
    {
        $id = trim($id);

        $qlf = TTnew('QualificationListFactory');

        if ($this->Validator->isResultSetWithRows('qualification_id',
            $qlf->getById($id),
            TTi18n::gettext('Invalid Qualification')
        )
        ) {
            $this->data['qualification_id'] = $id;

            return true;
        }

        return false;
    }

    public function getProficiency()
    {
        if (isset($this->data['proficiency_id'])) {
            return (int)$this->data['proficiency_id'];
        }
        return false;
    }

    public function setProficiency($proficiency_id)
    {
        $proficiency_id = trim($proficiency_id);

        if ($this->Validator->inArrayKey('proficiency_id',
            $proficiency_id,
            TTi18n::gettext('Proficiency is invalid'),
            $this->getOptions('proficiency'))
        ) {
            $this->data['proficiency_id'] = $proficiency_id;

            return true;
        }

        return false;
    }

    public function getExperience()
    {
        if (isset($this->data['experience']) and $this->data['experience'] != '') {

            //Because experience is stored in a different column in the database, it doesn't get updated
            //in real-time. So each time this function is called and EnableCalcExperience is enabled,
            //calculate the experience again to its always accurate.
            //This is especially required when no last_used_date is set.
            $retval = ($this->getEnableCalcExperience() == true) ? $this->calcExperience() : ($this->data['experience'] / 1000); //Divide by 1000 to convert to non-float value.

            return Misc::removeTrailingZeros(round($retval, 4), 2);
        }

        return false;
    }

    public function getEnableCalcExperience()
    {
        if (isset($this->data['enable_calc_experience'])) {
            return $this->fromBool($this->data['enable_calc_experience']);
        }

        return false;
    }

    public function calcExperience()
    {
        if ($this->getFirstUsedDate() != '') {
            $last_used_date = $this->getLastUsedDate();
            if ($this->getLastUsedDate() == '') {
                $last_used_date = TTDate::getEndDayEpoch(time());
            }

            $total_time = round(TTDate::getYears(($last_used_date - TTDate::getBeginDayEpoch($this->getFirstUsedDate()))), 2);
            if ($total_time < 0) {
                $total_time = 0;
            }

            Debug::text(' First Used Date: ' . $this->getFirstUsedDate() . ' Last Used Date: ' . $last_used_date . ' Total Yrs: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

            return $total_time;
        }

        return false;
    }

    public function getFirstUsedDate($raw = false)
    {
        if (isset($this->data['first_used_date'])) {
            return (int)$this->data['first_used_date'];
        }

        return false;
    }

    public function getLastUsedDate($raw = false)
    {
        if (isset($this->data['last_used_date'])) {
            return (int)$this->data['last_used_date'];
        }

        return false;
    }

    public function setEnableCalcExperience($bool)
    {
        $this->data['enable_calc_experience'] = $this->toBool($bool);

        return true;
    }

    public function getDescription()
    {
        if (isset($this->data['description'])) {
            return $this->data['description'];
        }
        return false;
    }

    public function setDescription($description)
    {
        $description = trim($description);

        if ($description == ''
            or
            $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                2, 255)
        ) {
            $this->data['description'] = $description;
            return true;
        }

        return false;
    }

    public function setTag($tags)
    {
        $tags = trim($tags);

        //Save the tags in temporary memory to be committed in postSave()
        $this->tmp_data['tags'] = $tags;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        return true;
    }

    public function preSave()
    {
        if ($this->getEnableCalcExperience() == true) {
            $this->setExperience($this->calcExperience());
        }

        return true;
    }

    public function setExperience($value)
    {
        //This should always be set as years.
        $value = $this->Validator->stripNonFloat(trim($value));

        //Assume they passed in number of seconds, convert to years.
        if ($value >= 1000) {
            $value = TTDate::getYears($value);
        }

        if ($value < 0) {
            $value = 0;
        }

        if (($value != ''
                and
                $this->Validator->isNumeric('experience',
                    $value,
                    TTi18n::gettext('Experience number must only be digits')
                )
                and
                $this->Validator->isLessThan('experience',
                    $value,
                    TTi18n::gettext('Years experience is too high'),
                    110
                )
            )
            or $value == ''
        ) {
            $this->data['experience'] = $this->Validator->stripNon32bitInteger($value * 1000); //Multiply by 1000 to convert to non-float value.

            return true;
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());
        $this->removeCache($this->getUser() . $this->getQualification());

        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getQualificationObject()->getCompany(), 251, $this->getID(), $this->getTag());
        }

        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
        return false;
    }

    public function getQualification()
    {
        if (isset($this->data['qualification_id'])) {
            return (int)$this->data['qualification_id'];
        }
        return false;
    }

    public function getQualificationObject()
    {
        return $this->getGenericObject('QualificationListFactory', $this->getQualification(), 'qualification_obj');
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif (is_object($this->getQualificationObject()) and $this->getQualificationObject()->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getQualificationObject()->getCompany(), 251, $this->getID());
        }

        return false;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'first_used_date':
                            $this->setFirstUsedDate(TTDate::parseDateTime($data['first_used_date']));
                            break;
                        case 'last_used_date':
                            $this->setLastUsedDate(TTDate::parseDateTime($data['last_used_date']));
                            break;
                        case 'expiry_date':
                            $this->setExpiryDate(TTDate::parseDateTime($data['expiry_date']));
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    public function setFirstUsedDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('first_used_date',
                $epoch,
                TTi18n::gettext('First used date is invalid'))
        ) {
            $this->data['first_used_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setLastUsedDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('last_used_date',
                $epoch,
                TTi18n::gettext('Last used date is invalid'))
        ) {
            $this->data['last_used_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setExpiryDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('expiry_date',
                $epoch,
                TTi18n::gettext('Expiry time stamp is invalid'))

        ) {
            $this->data['expiry_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();

        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;

                    switch ($variable) {
                        case 'qualification':
                        case 'group':
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'user_group':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'proficiency':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'first_used_date':
                            $data['first_used_date'] = TTDate::getAPIDate('DATE', $this->getFirstUsedDate());
                            break;
                        case 'last_used_date':
                            $data['last_used_date'] = TTDate::getAPIDate('DATE', $this->getLastUsedDate());
                            break;
                        case 'expiry_date':
                            $data['expiry_date'] = TTDate::getAPIDate('DATE', $this->getExpiryDate());
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getUser(), $this->getCreatedBy(), $permission_children_ids, $include_columns);

            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function getExpiryDate($raw = false)
    {
        if (isset($this->data['expiry_date'])) {
            return (int)$this->data['expiry_date'];
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Skill'), null, $this->getTable(), $this);
    }
}
