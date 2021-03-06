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
 * @package Modules\Policy
 */
class PayCodeFactory extends Factory
{
    protected $table = 'pay_code';
    protected $pk_sequence_name = 'pay_code_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $pay_stub_entry_account_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type': //Should this be status? This could be useful for overtime/premium and such as well, so it needs to stay here.
                $retval = array(
                    10 => TTi18n::gettext('Paid'),
                    12 => TTi18n::gettext('Paid (Above Salary)'),
                    20 => TTi18n::gettext('Unpaid'),
                    30 => TTi18n::gettext('Dock'),
                );
                break;
            case 'paid_type': //Types that are considered paid.
                $retval = array(10, 12);
                break;
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1020-description' => TTi18n::gettext('Description'),

                    '-1030-code' => TTi18n::gettext('Code'),

                    '-1900-in_use' => TTi18n::gettext('In Use'),

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
                    'name',
                    'description',
                    'updated_date',
                    'updated_by',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                );
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();
                break;

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'name' => 'Name',
            'description' => 'Description',

            'code' => 'Code',

            'type_id' => 'Type',
            'type' => false,

            'pay_formula_policy_id' => 'PayFormulaPolicy',
            'pay_formula_policy' => false,

            'pay_stub_entry_account_id' => 'PayStubEntryAccountId',

            'in_use' => false,
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getPayStubEntryAccountObject()
    {
        return $this->getGenericObject('PayStubEntryAccountListFactory', $this->getPayStubEntryAccountID(), 'pay_stub_entry_account_obj');
    }

    public function getPayStubEntryAccountId()
    {
        if (isset($this->data['pay_stub_entry_account_id'])) {
            return (int)$this->data['pay_stub_entry_account_id'];
        }

        return false;
    }

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        Debug::Text('Company ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Company is invalid')
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is too short or too long'),
                2, 70) //Needs to be long enough for upgrade procedure when converting from other policies.
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name is already in use'))
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND lower(name) = ? AND deleted=0';
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
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
            or $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                1, 250)
        ) {
            $this->data['description'] = $description;

            return true;
        }

        return false;
    }

    public function getCode()
    {
        if (isset($this->data['code'])) {
            return $this->data['code'];
        }

        return false;
    }

    public function setCode($code)
    {
        $code = trim($code);
        if ($this->Validator->isLength('code',
            $code,
            TTi18n::gettext('Code is too short or too long'),
            1, 50)
        ) {
            $this->data['code'] = $code;

            return true;
        }

        return false;
    }

    public function setType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('type_id',
            $value,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $value;

            return true;
        }

        return false;
    }

    public function isPaid()
    {
        if ($this->getType() == 10 or $this->getType() == 12) {
            return true;
        }

        return false;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function setPayFormulaPolicy($id)
    {
        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $pfplf = TTnew('PayFormulaPolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('pay_formula_policy_id',
                $pfplf->getByID($id),
                TTi18n::gettext('Pay Formula Policy is invalid')
            )
        ) {
            $this->data['pay_formula_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPayStubEntryAccountId($id)
    {
        $id = trim($id);

        Debug::text('Entry Account ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $psealf = TTnew('PayStubEntryAccountListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('pay_stub_entry_account_id',
                $psealf->getById($id),
                TTi18n::gettext('Invalid Pay Stub Account')
            )
        ) {
            $this->data['pay_stub_entry_account_id'] = $id;

            return true;
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() == true) {
            //Check to make sure there are no hours using this PayCode.
            $udtlf = TTnew('UserDateTotalListFactory');
            $udtlf->getByPayCodeId($this->getId(), 1); //Limit 1
            if ($udtlf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by employee timesheets'));
            }

            $rtplf = TTNew('RegularTimePolicyListFactory');
            $rtplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($rtplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by regular time policies'));
            }

            $otplf = TTNew('OverTimePolicyListFactory');
            $otplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($otplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by overtime policies'));
            }

            $pplf = TTNew('PremiumPolicyListFactory');
            $pplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($pplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by premium policies'));
            }

            $aplf = TTNew('AbsencePolicyListFactory');
            $aplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($aplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by absence policies'));
            }

            $mplf = TTNew('MealPolicyListFactory');
            $mplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($mplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by meal policies'));
            }

            $bplf = TTNew('BreakPolicyListFactory');
            $bplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($bplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by break policies'));
            }

            $csplf = TTNew('ContributingPayCodePolicyListFactory');
            $csplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
            if ($csplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay code is currently in use') . ' ' . TTi18n::gettext('by contributing shift policies'));
            }
        } else {
            if ($this->Validator->getValidateOnly() == false) { //Don't check the below when mass editing.
                if ($this->getName() === false) {
                    $this->Validator->isTRUE('name',
                        false,
                        TTi18n::gettext('Please specify a name'));
                }
            }

            if ($this->getId() > 0 and $this->getPayFormulaPolicy() == 0) { //Defined by Policy
                //Check to make sure all policies associated with this pay code have a pay formula defined
                $rtplf = TTNew('RegularTimePolicyListFactory');
                $rtplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
                if ($rtplf->getRecordCount() > 0) {
                    $this->Validator->isTRUE('pay_formula_policy_id',
                        false,
                        TTi18n::gettext('Regular Time Policy: %1 requires this Pay Formula Policy to be defined', array($rtplf->getCurrent()->getName())));
                }

                $otplf = TTNew('OverTimePolicyListFactory');
                $otplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
                if ($otplf->getRecordCount() > 0) {
                    $this->Validator->isTRUE('pay_formula_policy_id',
                        false,
                        TTi18n::gettext('Overtime Policy: %1 requires this Pay Formula Policy to be defined', array($otplf->getCurrent()->getName())));
                }

                $pplf = TTNew('PremiumPolicyListFactory');
                $pplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
                if ($pplf->getRecordCount() > 0) {
                    $this->Validator->isTRUE('pay_formula_policy_id',
                        false,
                        TTi18n::gettext('Premium Policy: %1 requires this Pay Formula Policy to be defined', array($pplf->getCurrent()->getName())));
                }

                $aplf = TTNew('AbsencePolicyListFactory');
                $aplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
                if ($aplf->getRecordCount() > 0) {
                    $this->Validator->isTRUE('pay_formula_policy_id',
                        false,
                        TTi18n::gettext('Absence Policy: %1 requires this Pay Formula Policy to be defined', array($aplf->getCurrent()->getName())));
                }

                $mplf = TTNew('MealPolicyListFactory');
                $mplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
                if ($mplf->getRecordCount() > 0) {
                    $this->Validator->isTRUE('pay_formula_policy_id',
                        false,
                        TTi18n::gettext('Meal Policy: %1 requires this Pay Formula Policy to be defined', array($mplf->getCurrent()->getName())));
                }

                $bplf = TTNew('BreakPolicyListFactory');
                $bplf->getByCompanyIdAndPayCodeId($this->getCompany(), $this->getId());
                if ($bplf->getRecordCount() > 0) {
                    $this->Validator->isTRUE('pay_formula_policy_id',
                        false,
                        TTi18n::gettext('Break Policy: %1 requires this Pay Formula Policy to be defined', array($bplf->getCurrent()->getName())));
                }
            }
        }

        return true;
    }

    //Don't require a pay stub entry account to be defined, as there may be some cases
    //in job costing situations where the rate of pay should be 1.0, but going to no pay stub account so their reports can reflect
    //proper rates of pay but not have it actually appear on pay stubs.

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function getPayFormulaPolicy()
    {
        if (isset($this->data['pay_formula_policy_id'])) {
            return (int)$this->data['pay_formula_policy_id'];
        }

        return false;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        if ($this->getDeleted() == true) {
            Debug::Text('UnAssign PayCode from ContributingShiftPolicies: ' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            $cgmf = TTnew('CompanyGenericMapFactory');

            $query = 'delete from ' . $cgmf->getTable() . ' where company_id = ' . (int)$this->getCompany() . ' AND object_type_id = 90 AND map_id = ' . (int)$this->getID();
            $this->db->Execute($query);
        }

        $this->removeCache($this->getId());

        return true;
    }

    //Migrate data from one pay code to another, without recalculating timesheets.
    public function migrate($company_id, $src_ids, $dst_id)
    {
        $dst_id = (int)$dst_id;
        $src_ids = array_unique((array)$src_ids);

        if (empty($dst_id)) {
            return false;
        }

        $pclf = TTnew('PayCodeListFactory');
        $pclf->getByIdAndCompanyID($dst_id, $company_id);
        if ($pclf->getRecordCount() != 1) {
            Debug::Text('Destination PayCode not valid: ' . $dst_id, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if (is_array($src_ids) and count($src_ids) > 0) {
            $pclf->getByIdAndCompanyID($src_ids, $company_id);
            if ($pclf->getRecordCount() != count($src_ids)) {
                Debug::Arr($src_ids, 'Source PayCode(s) not valid: ', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            }
        }

        $ph = array(
            'dst_pay_code_id' => (int)$dst_id,
        );

        $udtf = TTNew('UserDateTotalFactory');

        $query = 'update ' . $udtf->getTable() . ' set pay_code_id = ? where pay_code_id in (' . $this->getListSQL($src_ids, $ph) . ') AND deleted = 0';
        $this->db->Execute($query, $ph);

        return true;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
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

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'in_use':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Pay Code'), null, $this->getTable(), $this);
    }
}
