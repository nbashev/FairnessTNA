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
 * @package Core
 */
class UserDateTotalFactory extends Factory
{
public static $calc_future_week = false;
    public $alternate_date_stamps = null; //PK Sequence name
    protected $table = 'user_date_total';
protected $pk_sequence_name = 'user_date_total_id_seq';
    protected $user_obj = null;
    protected $pay_period_obj = null;
    protected $punch_control_obj = null;
    protected $job_obj = null;
    protected $job_item_obj = null; //Stores alternate date stamps that also need to be recalculated.
    protected $pay_code_obj = null;
    protected $calc_system_total_time = false;
        protected $timesheet_verification_check = false; //Used for BiWeekly overtime policies to schedule future week recalculating.

    public static function getEnableCalcFutureWeek()
    {
        if (isset(self::$calc_future_week)) {
            return self::$calc_future_week;
        }

        return false;
    }

    public static function setEnableCalcFutureWeek($bool)
    {
        self::$calc_future_week = $bool;

        return true;
    }

    public static function reCalculateDay($user_obj, $date_stamps, $enable_exception = false, $enable_premature_exceptions = false, $enable_future_exceptions = true, $enable_holidays = false)
    {
        if (!is_object($user_obj)) {
            return false;
        }

        Debug::text('Re-calculating User ID: ' . $user_obj->getId() . ' Enable Exception: ' . (int)$enable_exception, __FILE__, __LINE__, __METHOD__, 10);

        if (!is_array($date_stamps)) {
            $date_stamps = array($date_stamps);
        }
        Debug::Arr($date_stamps, 'bDate Stamps: ', __FILE__, __LINE__, __METHOD__, 10);

        $cp = TTNew('CalculatePolicy');

        $cp->setFlag('exception', $enable_exception);
        $cp->setFlag('exception_premature', $enable_premature_exceptions);
        $cp->setFlag('exception_future', $enable_future_exceptions);

        $cp->setUserObject($user_obj);
        $cp->addPendingCalculationDate($date_stamps);
        $cp->calculate(); //This sets timezone itself.
        return $cp->Save();
    }

    public static function sortAccumulatedTimeByOrder($a, $b)
    {
        if ($a['order'] == $b['order']) {
            return strnatcmp($a['label'], $b['label']);
        } else {
            return ($a['order'] - $b['order']);
        }
    }

    public static function calcAccumulatedTime($data, $include_daily_totals = true)
    {
        if (is_array($data) and count($data) > 0) {
            $retval = array();
            //Keep track of item ids for each section type so we can decide later on if we can eliminate unneeded data.
            $section_ids = array('branch' => array(), 'department' => array(), 'job' => array(), 'job_item' => array());

            //Sort data by date_stamp at the top, so it works for multiple days at a time.
            //Keep a running total of all days, mainly for 'weekly total" purposes.
            //
            //The 'order' array element is used by JS to sort the rows displayed to the user.
            foreach ($data as $row) {
                //Skip rows with a 0 total_time.
                if ($row['total_time'] == 0 and ((isset($row['override']) and $row['override'] == false) or !isset($row['override']))) {
                    continue;
                }

                switch ($row['object_type_id']) {
                    //Section: Accumulated Time:
                    //	Includes: Total Time, Regular Time, Overtime, Meal Policy Time, Break Policy Time.
                    case 5: //System Total Time row.
                        $order = 80;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'total';
                        $label_suffix = '';
                        break;
                    case 10: //System Worked Time row.
                        $order = 10;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'worked_time';
                        $label_suffix = '';
                        break;
                    case 20: //Regular Time row.
                        $order = 50;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'regular_time_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    //Section: Absence Time:
                    //	Includes: All Absence Time
                    case 25: //Absence Policy Row.
                        $order = 75;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'absence_time_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    case 30: //Over Time row.
                        $order = 60;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'over_time_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    case 100: //Meal Policy Row.
                        $order = 30;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'meal_time_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    case 110: //Break Policy Row.
                        $order = 20;
                        $primary_array_key = 'accumulated_time';
                        $secondary_array_key = 'break_time_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    //Section: Premium Time:
                    //	Includes: All Premium Time
                    case 40: //Premium Policy Row.
                        $order = 85;
                        $primary_array_key = 'premium_time';
                        $secondary_array_key = 'premium_time_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    //Section: Absence Time (Taken):
                    //	Includes: All Absence Time
                    case 50: //Absence Time (Taken) Row.
                        $order = 90;
                        $primary_array_key = 'absence_time_taken';
                        $secondary_array_key = 'absence_' . $row['pay_code_id'];
                        $label_suffix = '';
                        break;
                    default:
                        //Skip Lunch/Break Taken records, as those are handled in a different section.
                        //Debug::text('Skipping Object Type ID... User Date ID: '. $row['date_stamp'] .' Total Time: '. $row['total_time'] .' Object Type ID: '. $row['object_type_id'], __FILE__, __LINE__, __METHOD__, 10);
                        continue 2; //Must continue(2) to break out of the switch statement and foreach() loop.
                        break;
                }
                //Debug::text('User Date ID: '. $row['date_stamp'] .' Total Time: '. $row['total_time'] .' Object Type ID: '. $row['object_type_id'] .' Keys: Primary: '. $primary_array_key .' Secondary: '. $secondary_array_key, __FILE__, __LINE__, __METHOD__, 10);

                if ($include_daily_totals == true) {
                    if (!isset($retval[$row['date_stamp']][$primary_array_key][$secondary_array_key])) {
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key] = array('label' => $row['name'] . $label_suffix, 'total_time' => 0, 'total_time_amount' => 0, 'hourly_rate' => 0, 'order' => $order);
                    }
                    $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['total_time'] += $row['total_time'];
                    if ($row['object_type_id'] == 10) {
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['total_time_amount'] = false;
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['hourly_rate'] = false;
                    } else {
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['total_time_amount'] += (isset($row['total_time_amount'])) ? $row['total_time_amount'] : 0;
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['hourly_rate'] = ($retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['total_time_amount'] / (($retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['total_time'] > 0) ? TTDate::getHours($retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['total_time']) : 1));

                        //Calculate Accumulated Time Total.
                        if (in_array($row['object_type_id'], array(20, 25, 30))) {
                            if (!isset($retval[$row['date_stamp']]['accumulated_time']['total']['label'])) {
                                $retval[$row['date_stamp']]['accumulated_time']['total']['label'] = TTi18n::getText('Total Time');
                            }
                            if (!isset($retval[$row['date_stamp']]['accumulated_time']['total']['order'])) {
                                $retval[$row['date_stamp']]['accumulated_time']['total']['order'] = 999; //Always goes at the end.
                            }

                            if (!isset($retval[$row['date_stamp']]['accumulated_time']['total']['total_time_amount'])) {
                                $retval[$row['date_stamp']]['accumulated_time']['total']['total_time_amount'] = 0;
                            }
                            if (!isset($retval[$row['date_stamp']]['accumulated_time']['total']['total_time'])) {
                                $retval[$row['date_stamp']]['accumulated_time']['total']['total_time'] = 0;
                            }

                            $retval[$row['date_stamp']]['accumulated_time']['total']['total_time_amount'] += (isset($row['total_time_amount'])) ? $row['total_time_amount'] : 0;
                            $retval[$row['date_stamp']]['accumulated_time']['total']['hourly_rate'] = ($retval[$row['date_stamp']]['accumulated_time']['total']['total_time_amount'] / (($retval[$row['date_stamp']]['accumulated_time']['total']['total_time'] > 0) ? TTDate::getHours($retval[$row['date_stamp']]['accumulated_time']['total']['total_time']) : 1));

                            //$retval[$row['date_stamp']]['accumulated_time']['worked_time']['total_time_amount'] += ( isset($row['total_time_amount']) ) ? $row['total_time_amount'] : 0;
                            //$retval[$row['date_stamp']]['accumulated_time']['worked_time']['hourly_rate'] = ( $retval[$row['date_stamp']]['accumulated_time']['worked_time']['total_time_amount'] / ( ($retval[$row['date_stamp']]['accumulated_time']['worked_time']['total_time'] > 0 ) ? TTDate::getHours( $retval[$row['date_stamp']]['accumulated_time']['worked_time']['total_time'] ) : 1 ) );
                        }
                    }

                    if (isset($row['override']) and $row['override'] == true) {
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['override'] = true;
                    }
                    if (isset($row['note']) and $row['note'] == true) {
                        $retval[$row['date_stamp']][$primary_array_key][$secondary_array_key]['note'] = true;
                    }
                }

                if ($row['object_type_id'] != 50) { //Don't show Absences (Taken) in Weekly/Pay Period totals.
                    if (!isset($retval['total'][$primary_array_key][$secondary_array_key])) {
                        $retval['total'][$primary_array_key][$secondary_array_key] = array('label' => $row['name'] . $label_suffix, 'total_time' => 0, 'total_time_amount' => 0, 'hourly_rate' => 0, 'order' => $order);
                    }
                    $retval['total'][$primary_array_key][$secondary_array_key]['total_time'] += $row['total_time'];
                    if ($row['object_type_id'] == 10) {
                        $retval['total'][$primary_array_key][$secondary_array_key]['total_time_amount'] = false;
                        $retval['total'][$primary_array_key][$secondary_array_key]['hourly_rate'] = false;
                    } else {
                        $retval['total'][$primary_array_key][$secondary_array_key]['total_time_amount'] += (isset($row['total_time_amount'])) ? $row['total_time_amount'] : 0;
                        $retval['total'][$primary_array_key][$secondary_array_key]['hourly_rate'] = ($retval['total'][$primary_array_key][$secondary_array_key]['total_time_amount'] / (($retval['total'][$primary_array_key][$secondary_array_key]['total_time'] > 0) ? TTDate::getHours($retval['total'][$primary_array_key][$secondary_array_key]['total_time']) : 1));

                        //Calculate Accumulated Time Total.
                        if (in_array($row['object_type_id'], array(20, 25, 30))) {
                            //If there is no time on the 2nd (current) week in the pay period, but there is time on the first week, we need to make sure there is a label.
                            if (!isset($retval['total']['accumulated_time']['total']['label'])) {
                                $retval['total']['accumulated_time']['total']['label'] = TTi18n::getText('Total Time');
                            }
                            if (!isset($retval['total']['accumulated_time']['total']['order'])) {
                                $retval['total']['accumulated_time']['total']['order'] = 999; //Always goes at the end.
                            }

                            if (!isset($retval['total']['accumulated_time']['total']['total_time_amount'])) {
                                $retval['total']['accumulated_time']['total']['total_time_amount'] = 0;
                            }
                            if (!isset($retval['total']['accumulated_time']['total']['total_time'])) {
                                $retval['total']['accumulated_time']['total']['total_time'] = 0;
                            }
                            $retval['total']['accumulated_time']['total']['total_time_amount'] += (isset($row['total_time_amount'])) ? $row['total_time_amount'] : 0;
                            $retval['total']['accumulated_time']['total']['hourly_rate'] = ($retval['total']['accumulated_time']['total']['total_time_amount'] / (($retval['total']['accumulated_time']['total']['total_time'] > 0) ? TTDate::getHours($retval['total']['accumulated_time']['total']['total_time']) : 1));

                            //$retval['total']['accumulated_time']['worked_time']['total_time_amount'] += ( isset($row['total_time_amount']) ) ? $row['total_time_amount'] : 0;
                            //$retval['total']['accumulated_time']['worked_time']['hourly_rate'] = ( $retval['total']['accumulated_time']['worked_time']['total_time_amount'] / ( ($retval['total']['accumulated_time']['worked_time']['total_time'] > 0 ) ? TTDate::getHours( $retval['total']['accumulated_time']['worked_time']['total_time'] ) : 1 ) );
                        }
                    }
                }


                //Section: Accumulated Time by Branch, Department, Job, Task
                if ($include_daily_totals == true and $row['object_type_id'] == 20 or $row['object_type_id'] == 30) {
                    //Branch
                    $branch_name = $row['branch'];
                    if ($branch_name == '') {
                        $branch_name = TTi18n::gettext('No Branch');
                    }
                    if (!isset($retval[$row['date_stamp']]['branch_time']['branch_' . $row['branch_id']])) {
                        $retval[$row['date_stamp']]['branch_time']['branch_' . $row['branch_id']] = array('label' => $branch_name, 'total_time' => 0, 'total_time_amount' => 0, 'hourly_rate' => 0, 'order' => $order);
                    }
                    $retval[$row['date_stamp']]['branch_time']['branch_' . $row['branch_id']]['total_time'] += $row['total_time'];
                    $retval[$row['date_stamp']]['branch_time']['branch_' . $row['branch_id']]['total_time_amount'] += (isset($row['total_time_amount'])) ? $row['total_time_amount'] : 0;
                    //$retval[$row['date_stamp']]['branch_time']['branch_'.$row['branch_id']]['hourly_rate'] = ( $retval[$row['date_stamp']]['branch_time']['branch_'.$row['branch_id']]['total_time_amount'] / ( ($retval[$row['date_stamp']]['branch_time']['branch_'.$row['branch_id']]['total_time'] > 0 ) ? TTDate::getHours( $retval[$row['date_stamp']]['branch_time']['branch_'.$row['branch_id']]['total_time'] ) : 1 ) );
                    $section_ids['branch'][] = (int)$row['branch_id'];

                    //Department
                    $department_name = $row['department'];
                    if ($department_name == '') {
                        $department_name = TTi18n::gettext('No Department');
                    }
                    if (!isset($retval[$row['date_stamp']]['department_time']['department_' . $row['department_id']])) {
                        $retval[$row['date_stamp']]['department_time']['department_' . $row['department_id']] = array('label' => $department_name, 'total_time' => 0, 'total_time_amount' => 0, 'hourly_rate' => 0, 'order' => $order);
                    }
                    $retval[$row['date_stamp']]['department_time']['department_' . $row['department_id']]['total_time'] += $row['total_time'];
                    $retval[$row['date_stamp']]['department_time']['department_' . $row['department_id']]['total_time_amount'] += (isset($row['total_time_amount'])) ? $row['total_time_amount'] : 0;
                    //$retval[$row['date_stamp']]['department_time']['department_'.$row['department_id']]['hourly_rate'] = ( $retval[$row['date_stamp']]['department_time']['department_'.$row['department_id']]['total_time_amount'] / ( ($retval[$row['date_stamp']]['department_time']['department_'.$row['department_id']]['total_time'] > 0 ) ? TTDate::getHours( $retval[$row['date_stamp']]['department_time']['department_'.$row['department_id']]['total_time'] ) : 1 ) );
                    $section_ids['department'][] = (int)$row['department_id'];

                    //Debug::text('ID: '. $row['id'] .' User Date ID: '. $row['date_stamp'] .' Total Time: '. $row['total_time'] .' Branch: '. $branch_name .' Job: '. $job_name, __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            if (empty($retval) == false) {
                //Remove any unneeded data, such as "No Branch" for all dates in the range
                foreach ($section_ids as $section => $ids) {
                    $ids = array_unique($ids);
                    sort($ids);
                    if (isset($ids[0]) and $ids[0] == 0 and count($ids) == 1) {
                        foreach ($retval as $date_stamp => $day_data) {
                            unset($retval[$date_stamp][$section . '_time']);
                        }
                    } else {
                        foreach ($retval as $date_stamp => $day_data) {
                            if (isset($retval[$date_stamp]['accumulated_time'])) {
                                uasort($retval[$date_stamp]['accumulated_time'], array('self', 'sortAccumulatedTimeByOrder')); //Sort by Order then label.
                            }
                        }
                        unset($day_data);
                    }
                }

                //Sort the accumulated time so its always in the same order.
                if (isset($retval['total']['accumulated_time'])) {
                    uasort($retval['total']['accumulated_time'], array('self', 'sortAccumulatedTimeByOrder')); //Sort by Order then label.
                }

                return $retval;
            }
        }

        return false;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'start_type':
            case 'end_type':
                $retval = array(
                    10 => TTi18n::gettext('Normal'),
                    20 => TTi18n::gettext('Lunch'),
                    30 => TTi18n::gettext('Break')
                );
                break;
            case 'object_type':
                //In order to not have to dig into punches when calculating policies, we would need to create user_date_total rows for lunch/break
                //time taken.

                //We have to continue to use two columns to determine the type of hours and the pay code its associated with.
                //Otherwise we have no idea what is Lunch Time vs Total Time vs Break Time, since they could all go to one pay code.
                $retval = array(
                    5 => TTi18n::gettext('System'),
                    10 => TTi18n::gettext('Worked'), //Used to be "Total"
                    20 => TTi18n::gettext('Regular'),
                    25 => TTi18n::gettext('Absence'),
                    30 => TTi18n::gettext('Overtime'),
                    40 => TTi18n::gettext('Premium'),

                    //We need to treat Absence time like Worked Time, and calculate policies (ie: Overtime) based on it, without affecting the original entry.
                    //As it can be split between regular,overtime policies just like worked time can.
                    50 => TTi18n::gettext('Absence (Taken)'),

                    100 => TTi18n::gettext('Lunch'), //Lunch Policy (auto-add/deduct)
                    101 => TTi18n::gettext('Lunch (Taken)'), //Time punched out for lunch.

                    110 => TTi18n::gettext('Break'), //Break Policy (auto-add/deduct)
                    111 => TTi18n::gettext('Break (Taken)'), //Time punched out for break.
                );
                break;
            case 'columns':
                $retval = array(
                    '-1000-first_name' => TTi18n::gettext('First Name'),
                    '-1002-last_name' => TTi18n::gettext('Last Name'),
                    '-1005-user_status' => TTi18n::gettext('Employee Status'),
                    '-1010-title' => TTi18n::gettext('Title'),
                    '-1039-group' => TTi18n::gettext('Group'),
                    '-1040-default_branch' => TTi18n::gettext('Default Branch'),
                    '-1050-default_department' => TTi18n::gettext('Default Department'),
                    '-1160-branch' => TTi18n::gettext('Branch'),
                    '-1170-department' => TTi18n::gettext('Department'),

                    '-1200-object_type' => TTi18n::gettext('Type'),
                    '-1205-name' => TTi18n::gettext('Pay Code'),
                    '-1210-date_stamp' => TTi18n::gettext('Date'),
                    '-1290-total_time' => TTi18n::gettext('Time'),

                    '-1300-quantity' => TTi18n::gettext('QTY'),
                    '-1300-bad_quantity' => TTi18n::gettext('Bad QTY'),

                    '-1800-note' => TTi18n::gettext('Note'),

                    '-1900-override' => TTi18n::gettext('O/R'), //Override

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );

                ksort($retval);
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'date_stamp',
                    'total_time',
                    'object_type',
                    'name',
                    'branch',
                    'department',
                    'note',
                    'override',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
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
            'user_id' => 'User',
            'date_stamp' => 'DateStamp',
            'pay_period_id' => 'PayPeriod',

            //Legacy status/type functions.
            'status_id' => 'Status',
            'type_id' => 'Type',

            'object_type_id' => 'ObjectType',
            'object_type' => false,
            'pay_code_id' => 'PayCode',
            'src_object_id' => 'SourceObject', //This must go after PayCodeID, so if the user is saving an absence we overwrite any previously selected PayCode
            'policy_name' => false,

            'punch_control_id' => 'PunchControlID',
            'branch_id' => 'Branch',
            'branch' => false,
            'department_id' => 'Department',
            'department' => false,
            'job_id' => 'Job',
            'job' => false,
            'job_item_id' => 'JobItem',
            'job_item' => false,
            'quantity' => 'Quantity',
            'bad_quantity' => 'BadQuantity',
            'start_type_id' => 'StartType',
            'start_time_stamp' => 'StartTimeStamp',
            'end_type_id' => 'EndType',
            'end_time_stamp' => 'EndTimeStamp',
            'total_time' => 'TotalTime',
            'actual_total_time' => 'ActualTotalTime',

            'currency_id' => 'Currency',
            'currency_rate' => 'CurrencyRate',
            'base_hourly_rate' => 'BaseHourlyRate',
            'hourly_rate' => 'HourlyRate',
            'total_time_amount' => 'TotalTimeAmount',
            'hourly_rate_with_burden' => 'HourlyRateWithBurden',
            'total_time_amount_with_burden' => 'TotalTimeAmountWithBurden',

            'name' => false,
            'override' => 'Override',
            'note' => 'Note',

            'first_name' => false,
            'last_name' => false,
            'user_status_id' => false,
            'user_status' => false,
            'group_id' => false,
            'group' => false,
            'title_id' => false,
            'title' => false,
            'default_branch_id' => false,
            'default_branch' => false,
            'default_department_id' => false,
            'default_department' => false,

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getPunchControlObject()
    {
        return $this->getGenericObject('PunchControlListFactory', $this->getPunchControlID(), 'punch_control_obj');
    }

    public function getPunchControlID()
    {
        if (isset($this->data['punch_control_id'])) {
            return (int)$this->data['punch_control_id'];
        }

        return false;
    }

    public function getJobObject()
    {
        return false;
    }

    public function getJobItemObject()
    {
        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        //Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid User')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getStatus()
    {
        if (in_array($this->getObjectType(), array(5, 20, 25, 30, 40, 100, 110))) {
            return 10;
        } elseif ($this->getObjectType() == 20) {
            return 20;
        } elseif ($this->getObjectType() == 50) {
            return 30;
        }
    }

    public function getObjectType()
    {
        if (isset($this->data['object_type_id'])) {
            return (int)$this->data['object_type_id'];
        }

        return false;
    }

    public function getType()
    {
        if (in_array($this->getObjectType(), array(5, 10, 50))) {
            return 10;
        } else {
            return $this->getObjectType();
        }
    }

    public function setObjectType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('object_type',
            $value,
            TTi18n::gettext('Incorrect Object Type'),
            $this->getOptions('object_type'))
        ) {
            $this->data['object_type_id'] = $value;

            return true;
        }

        return false;
    }

    //Legacy functions for now:

    public function setSourceObject($id)
    {
        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        //Debug::Text('Object Type: '. $this->getObjectType() .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
        $lf = $this->getSourceObjectListFactory($this->getObjectType());

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('src_object_id',
                (is_object($lf)) ? $lf->getByID($id) : false,
                TTi18n::gettext('Invalid Source Object')
            )
        ) {
            $this->data['src_object_id'] = $id;

            //Absences need to have pay codes set for the user created entry, then other policies can also be calculated on them too.
            //This is so they can be linked directly with accrual policies rather than having to go through regular time policies first.
            //But in cases where OT is calculated on absence time it may need to not have any pay code and just go through regular/OT policies instead.
            //Do this here rather than in preSave() like it used to be since that could cause the validation checks to fail and the user wouldnt see the message.
            //However we have to setSourceObject *after* setPayCode(), otherwise there is potential for the wrong pay code to be used.
            if ($this->getSourceObject() != 0) {
                if ($this->getObjectType() == 50) {
                    $lf = TTNew('AbsencePolicyListFactory');
                } else {
                    $lf = null;
                }

                if (is_object($lf)) {
                    $lf->getByID($this->getSourceObject());
                    if ($lf->getRecordCount() > 0) {
                        $obj = $lf->getCurrent();
                        Debug::text('Setting PayCode To: ' . $obj->getPayCode(), __FILE__, __LINE__, __METHOD__, 10);
                        $this->setPayCode($obj->getPayCode());
                    }
                }
            }

            return true;
        }

        return false;
    }

    public function getSourceObjectListFactory($object_type_id)
    {
        //Debug::Text('Object Type: '. $object_type_id, __FILE__, __LINE__, __METHOD__, 10);
        switch ($object_type_id) {
            case 20:
                $lf = TTNew('RegularTimePolicyListFactory');
                break;
            case 30:
                $lf = TTNew('OverTimePolicyListFactory');
                break;
            case 40:
                $lf = TTNew('PremiumPolicyListFactory');
                break;
            case 25:
            case 50:
                $lf = TTNew('AbsencePolicyListFactory');
                break;
            case 100:
            case 101:
                $lf = TTNew('MealPolicyListFactory');
                break;
            case 110:
            case 111:
                $lf = TTNew('BreakPolicyListFactory');
                break;
            default:
                $lf = false;
                Debug::Text('Invalid Object Type: ' . $object_type_id, __FILE__, __LINE__, __METHOD__, 10);
                break;
        }

        return $lf;
    }

    public function getSourceObject()
    {
        if (isset($this->data['src_object_id'])) {
            return (int)$this->data['src_object_id'];
        }

        return false;
    }

    public function setPayCode($id)
    {
        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $lf = TTNew('PayCodeListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('pay_code_id',
                $lf->getByID($id),
                TTi18n::gettext('Invalid Pay Code')
            )
        ) {
            $this->data['pay_code_id'] = $id;

            return true;
        }

        return false;
    }

    public function getTimeCategory($include_total = true, $report_columns = false)
    {
        $retarr = array();
        switch ($this->getObjectType()) {
            case 5: //System Time
                if ($include_total == true) {
                    $retarr[] = 'total';
                }
                break;
            case 10: //Worked
                $retarr[] = 'worked';
                break;
            case 20: //Regular
                $retarr[] = 'regular';
                break;
            case 25: //Absence
                $retarr[] = 'absence';
                break;
            case 30: //Overtime
                $retarr[] = 'overtime';
                break;
            case 40: //Premium
                $retarr[] = 'premium';
                break;
            case 50: //Absence (Taken)
                $retarr[] = 'absence_taken';
                break;
            case 100: //Lunch
                $retarr[] = 'worked';
                break;
            case 101: //Lunch (Taken)
                //During the transition from v7 -> v8, Lunch/Break time wasnt being assigned to branch/departments in v7, so it caused
                //blank lines to appear on reports. This prevents Lunch/Break time taken from causing blank lines or even being included
                //unless the report displays these columns.
                if ($report_columns == false or isset($report_columns['lunch_time'])) {
                    $retarr[] = 'lunch';
                }
                break;
            case 110: //Break
                $retarr[] = 'worked';
                break;
            case 111: //Break (Taken)
                //During the transition from v7 -> v8, Lunch/Break time wasnt being assigned to branch/departments in v7, so it caused
                //blank lines to appear on reports. This prevents Lunch/Break time taken from causing blank lines or even being included
                //unless the report displays these columns.
                if ($report_columns == false or isset($report_columns['break_time'])) {
                    $retarr[] = 'break';
                }
                break;
        }

        //Don't include Absence Time Taken (ID: 50) with other 'pay_code-' categories, as that will double up on the absence time often. (ID:25 + ID:50).
        //Include Lunch(100)/Break(110) so they can be displayed as their own separate column on reports.
        if (in_array($this->getObjectType(), array(20, 25, 30, 40, 100, 110))) {
            $retarr[] = 'pay_code-' . $this->getColumn('pay_code_id');
        } elseif ($this->getObjectType() == 50) { //Break out absence time taken so we can have separate columns for it in reports. Prevents doubling up as described above.
            $retarr[] = 'absence_taken_pay_code-' . $this->getColumn('pay_code_id');
        }

        //Make sure we don't include Absence (Taken) [50] in gross time, use Absence [25] instead so we don't double up on absence time.
        if ($this->getObjectType() != 50 and $this->getColumn('pay_code_type_id') != '' and in_array($this->getColumn('pay_code_type_id'), array(10, 12, 30))) {
            $retarr[] = 'gross'; //Use 'gross' instead of 'paid' so we don't have to special case it in each report.
        }

        return $retarr;
    }

    public function getStartType()
    {
        if (isset($this->data['start_type_id'])) {
            return (int)$this->data['start_type_id'];
        }

        return false;
    }

    public function setStartType($value)
    {
        $value = (int)$value;

        if ($value === 0) {
            $value = '';
        }

        if ($value == ''
            or
            $this->Validator->inArrayKey('start_type',
                $value,
                TTi18n::gettext('Incorrect Start Type'),
                $this->getOptions('start_type'))
        ) {
            $this->data['start_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function getEndType()
    {
        if (isset($this->data['end_type_id'])) {
            return (int)$this->data['end_type_id'];
        }

        return false;
    }

    public function setEndType($value)
    {
        $value = (int)$value;

        if ($value === 0) {
            $value = '';
        }

        if ($value == ''
            or
            $this->Validator->inArrayKey('end_type',
                $value,
                TTi18n::gettext('Incorrect End Type'),
                $this->getOptions('end_type'))
        ) {
            $this->data['end_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setTotalTime($int)
    {
        $int = (int)$int;

        if ($this->Validator->isNumeric('total_time',
            $int,
            TTi18n::gettext('Incorrect total time'))
        ) {
            $this->data['total_time'] = $int;

            return true;
        }

        return false;
    }

    //Returns an array of time categories that the object_type fits in.

    public function calcTotalTime()
    {
        if ($this->getEndTimeStamp() != '' and $this->getStartTimeStamp() != '') {
            $retval = ($this->getEndTimeStamp() - $this->getStartTimeStamp());
            return $retval;
        }

        return false;
    }

    public function getEndTimeStamp($raw = false)
    {
        if (isset($this->data['end_time_stamp'])) {
            if ($raw === true) {
                return $this->data['end_time_stamp'];
            } else {
                if (!is_numeric($this->data['end_time_stamp'])) { //Optmization to avoid converting it when run in CalculatePolicy's loops
                    $this->data['end_time_stamp'] = TTDate::strtotime($this->data['end_time_stamp']);
                }
                return $this->data['end_time_stamp'];
            }
        }

        return false;
    }

    public function getStartTimeStamp($raw = false)
    {
        if (isset($this->data['start_time_stamp'])) {
            if ($raw === true) {
                return $this->data['start_time_stamp'];
            } else {
                //return $this->db->UnixTimeStamp( $this->data['start_date'] );
                //strtotime is MUCH faster than UnixTimeStamp
                //Must use ADODB for times pre-1970 though.
                if (!is_numeric($this->data['start_time_stamp'])) { //Optmization to avoid converting it when run in CalculatePolicy's loops
                    $this->data['start_time_stamp'] = TTDate::strtotime($this->data['start_time_stamp']);
                }
                return $this->data['start_time_stamp'];
            }
        }

        return false;
    }

    public function getActualTotalTime()
    {
        if (isset($this->data['actual_total_time'])) {
            return (int)$this->data['actual_total_time'];
        }
        return false;
    }

    public function setActualTotalTime($int)
    {
        $int = (int)$int;

        if ($this->Validator->isNumeric('actual_total_time',
            $int,
            TTi18n::gettext('Incorrect actual total time'))
        ) {
            $this->data['actual_total_time'] = $int;

            return true;
        }

        return false;
    }

    public function setCurrency($id, $disable_rate_lookup = false)
    {
        $id = trim($id);

        //Debug::Text('Currency ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
        $culf = TTnew('CurrencyListFactory');

        $old_currency_id = $this->getCurrency();

        if (
        $this->Validator->isResultSetWithRows('currency',
            $culf->getByID($id),
            TTi18n::gettext('Invalid Currency')
        )
        ) {
            $this->data['currency_id'] = $id;

            if ($disable_rate_lookup == false
                and $culf->getRecordCount() == 1
                and ($this->isNew() or $old_currency_id != $id)
            ) {
                $crlf = TTnew('CurrencyRateListFactory');
                $crlf->getByCurrencyIdAndDateStamp($id, $this->getDateStamp());
                if ($crlf->getRecordCount() > 0) {
                    $this->setCurrencyRate($crlf->getCurrent()->getReverseConversionRate());
                }
            }

            return true;
        }

        return false;
    }

    public function getCurrency()
    {
        if (isset($this->data['currency_id'])) {
            return (int)$this->data['currency_id'];
        }

        return false;
    }

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                if (!is_numeric($this->data['date_stamp'])) { //Optmization to avoid converting it when run in CalculatePolicy's loops
                    $this->data['date_stamp'] = TTDate::strtotime($this->data['date_stamp']);
                }
                return $this->data['date_stamp'];
            }
        }

        return false;
    }

    public function setCurrencyRate($value)
    {
        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($value == 0) {
            $value = 1;
        }

        if ($this->Validator->isFloat('currency_rate',
            $value,
            TTi18n::gettext('Incorrect Currency Rate'))
        ) {
            $this->data['currency_rate'] = $value;

            return true;
        }

        return false;
    }

    public function getCurrencyRate()
    {
        if (isset($this->data['currency_rate'])) {
            return $this->data['currency_rate'];
        }

        return false;
    }

    public function getBaseHourlyRate()
    {
        if (isset($this->data['base_hourly_rate'])) {
            return $this->data['base_hourly_rate'];
        }

        return false;
    }

    public function setBaseHourlyRate($value)
    {
        if ($value === false or $value === '' or $value === null) {
            $value = 0;
        }

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isFloat('base_hourly_rate',
            $value,
            TTi18n::gettext('Incorrect Base Hourly Rate'))
        ) {
            $this->data['base_hourly_rate'] = number_format($value, 4, '.', ''); //Always make sure there are 4 decimal places.

            return true;
        }

        return false;
    }

    public function setHourlyRate($value)
    {
        if ($value === false or $value === '' or $value === null) {
            $value = 0;
        }

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isFloat('hourly_rate',
            $value,
            TTi18n::gettext('Incorrect Hourly Rate'))
        ) {
            $this->data['hourly_rate'] = number_format($value, 4, '.', ''); //Always make sure there are 4 decimal places.

            return true;
        }

        return false;
    }

    public function getTotalTimeAmount()
    {
        if (isset($this->data['total_time_amount'])) {
            return $this->data['total_time_amount'];
        }

        return false;
    }

    public function setHourlyRateWithBurden($value)
    {
        if ($value === false or $value === '' or $value === null) {
            $value = 0;
        }

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isFloat('hourly_rate_with_burden',
            $value,
            TTi18n::gettext('Incorrect Hourly Rate with Burden'))
        ) {
            $this->data['hourly_rate_with_burden'] = number_format($value, 4, '.', ''); //Always make sure there are 4 decimal places.

            return true;
        }

        return false;
    }

    public function getTotalTimeAmountWithBurden()
    {
        if (isset($this->data['total_time_amount_with_burden'])) {
            return $this->data['total_time_amount_with_burden'];
        }

        return false;
    }

    public function setOverride($bool)
    {
        $this->data['override'] = $this->toBool($bool);

        return true;
    }

    public function getNote()
    {
        if (isset($this->data['note'])) {
            return $this->data['note'];
        }

        return false;
    }

    public function setNote($val)
    {
        $val = trim($val);

        if ($val == ''
            or
            $this->Validator->isLength('note',
                $val,
                TTi18n::gettext('Note is too long'),
                0,
                1024)
        ) {
            $this->data['note'] = $val;

            return true;
        }

        return false;
    }

    public function getIsPartialShift()
    {
        if (isset($this->is_partial_shift)) {
            return $this->is_partial_shift;
        }

        return false;
    }

    public function setIsPartialShift($bool)
    {
        $this->is_partial_shift = $bool;

        return true;
    }

    public function setEnableCalcSystemTotalTime($bool)
    {
        $this->calc_system_total_time = $bool;

        return true;
    }

    public function setEnableCalcWeeklySystemTotalTime($bool)
    {
        $this->calc_weekly_system_total_time = $bool;

        return true;
    }

    public function setEnableCalcException($bool)
    {
        $this->calc_exception = $bool;

        return true;
    }

    public function setEnablePreMatureException($bool)
    {
        $this->premature_exception = $bool;

        return true;
    }

    public function getEnableCalcAccrualPolicy()
    {
        if (isset($this->calc_accrual_policy)) {
            return $this->calc_accrual_policy;
        }

        return false;
    }

    public function setEnableCalcAccrualPolicy($bool)
    {
        $this->calc_accrual_policy = $bool;

        return true;
    }

    public function setEnableTimeSheetVerificationCheck($bool)
    {
        $this->timesheet_verification_check = $bool;

        return true;
    }

    public function calcWeeklySystemTotalTime()
    {
        if ($this->getEnableCalcWeeklySystemTotalTime() == true) {
            //Used to call reCalculateRange() for the remainder of the week, but this is handled automatically now.
            return true;
        }

        return false;
    }

    public function getEnableCalcWeeklySystemTotalTime()
    {
        if (isset($this->calc_weekly_system_total_time)) {
            return $this->calc_weekly_system_total_time;
        }

        return false;
    }

    //This the base hourly rate used to obtain the final hourly rate from. Primarily used for FLSA calculations when adding overtime wages.

    public function Validate($ignore_warning = true)
    {
        if ($this->getUser() == false) {
            $this->Validator->isTRUE('user_id',
                false,
                TTi18n::gettext('Employee is invalid'));
        }

        if ($this->getObjectType() == false) {
            $this->Validator->isTRUE('object_type_id',
                false,
                TTi18n::gettext('Type is invalid'));
        }

        //Check to make sure if this is an absence row, the absence policy is actually set.
        if ($this->getDeleted() == false and $this->getObjectType() == 50) {
            if ((int)$this->getSourceObject() == 0) {
                $this->Validator->isTRUE('src_object_id',
                    false,
                    TTi18n::gettext('Please specify an absence type'));
            }

            if (is_object($this->getUserObject()) and $this->getUserObject()->getHireDate() != '' and TTDate::getBeginDayEpoch($this->getDateStamp()) < TTDate::getBeginDayEpoch($this->getUserObject()->getHireDate())) {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Absence is before employees hire date'));
            }

            if (is_object($this->getUserObject()) and $this->getUserObject()->getTerminationDate() != '' and TTDate::getEndDayEpoch($this->getDateStamp()) > TTDate::getEndDayEpoch($this->getUserObject()->getTerminationDate())) {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Absence is after employees termination date'));
            }
        }

        //Check to make sure if this is an absence row, the absence policy is actually set.
        //if ( $this->getObjectType() == 50 AND $this->getPayCode() == FALSE ) {
        if ($this->getObjectType() == 50 and (int)$this->getSourceObject() == 0 and $this->getOverride() == false) {
            $this->Validator->isTRUE('src_object_id',
                false,
                TTi18n::gettext('Please specify an absence type'));
        }
        //Check to make sure if this is an overtime row, the overtime policy is actually set.
        if ($this->getObjectType() == 30 and (int)$this->getSourceObject() == 0 and $this->getOverride() == false) {
            $this->Validator->isTRUE('over_time_policy_id',
                false,
                TTi18n::gettext('Invalid Overtime Policy'));
        }
        //Check to make sure if this is an premium row, the premium policy is actually set.
        if ($this->getObjectType() == 40 and (int)$this->getSourceObject() == 0 and $this->getOverride() == false) {
            $this->Validator->isTRUE('premium_policy_id',
                false,
                TTi18n::gettext('Invalid Premium Policy'));
        }
        //Check to make sure if this is an meal row, the meal policy is actually set.
        if ($this->getObjectType() == 100 and (int)$this->getSourceObject() == 0 and $this->getOverride() == false) {
            $this->Validator->isTRUE('meal_policy_id',
                false,
                TTi18n::gettext('Invalid Meal Policy'));
        }
        //Check to make sure if this is an break row, the break policy is actually set.
        if ($this->getObjectType() == 110 and (int)$this->getSourceObject() == 0 and $this->getOverride() == false) {
            $this->Validator->isTRUE('break_policy_id',
                false,
                TTi18n::gettext('Invalid Break Policy'));
        }

        //Check that the user is allowed to be assigned to the absence policy
        if ($this->getObjectType() == 50 and (int)$this->getSourceObject() != 0 and $this->getUser() != false) {
            $pglf = TTNew('PolicyGroupListFactory');
            $pglf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), array('user_id' => array($this->getUser()), 'absence_policy' => array($this->getSourceObject())));
            if ($pglf->getRecordCount() == 0) {
                $this->Validator->isTRUE('absence_policy_id',
                    false,
                    TTi18n::gettext('This absence policy is not available for this employee'));
            }
        }

        //This is likely caused by employee not being assigned to a pay period schedule?
        //Make sure to allow entries in the future (ie: absences) where no pay period exists yet.
        if ($this->getDeleted() == false and $this->getDateStamp() == false) {
            $this->Validator->isTRUE('date_stamp',
                false,
                TTi18n::gettext('Date is incorrect, or pay period does not exist for this date. Please create a pay period schedule and assign this employee to it if you have not done so already'));
        } elseif (($this->getOverride() == true or ($this->getOverride() == false and $this->getObjectType() == 50))
            and $this->getDateStamp() != false and is_object($this->getPayPeriodObject()) and $this->getPayPeriodObject()->getIsLocked() == true
        ) {
            //Make sure we only check for pay period being locked if override is TRUE, otherwise it can prevent recalculations from occurring
            //after the pay period is locked (ie: recalculating exceptions each day from maintenance jobs?)
            //We need to be able to stop absences (non-overridden ones too) from being deleted in closed pay periods.
            $this->Validator->isTRUE('date_stamp',
                false,
                TTi18n::gettext('Pay Period is Currently Locked'));
        }

        //Make sure that we aren't trying to overwrite an already overridden entry made by the user for some special purpose.
        if ($this->getDeleted() == false
            and $this->isNew() == true
            //AND in_array( $this->getStatus(), array(10, 20, 30) )
        ) {

            //Debug::text('Checking for already existing overridden entries ... User ID: '. $this->getUser() .' DateStamp: '. $this->getDateStamp() .' Object Type ID: '. $this->getObjectType(), __FILE__, __LINE__, __METHOD__, 10);

            $udtlf = TTnew('UserDateTotalListFactory');
            if ($this->getObjectType() == 10 and $this->getPunchControlID() > 0) {
                $udtlf->getByUserIdAndDateStampAndObjectTypeAndPunchControlIdAndOverride($this->getUser(), $this->getDateStamp(), $this->getObjectType(), $this->getPunchControlID(), true);
            } elseif ($this->getObjectType() == 50 or $this->getObjectType() == 25) {
                //Allow object_type_id=50 (absence taken) entries to override object_type_id=25 entries.
                //So users can create an absence schedule shift, then override it to a smaller number of hours.
                //However how do we handle cases where an undertime absence policy creates a object_type_id=25 record and the user wants to override it?

                //Allow employee to have multiple absence entries on the same day as long as the branch, department, job, task are all different.
                if ($this->getDateStamp() != false and $this->getUser() != false) {
                    $filter_data = array('user_id' => (int)$this->getUser(),
                        'date_stamp' => $this->getDateStamp(),
                        //'object_type_id' => array( (int)$this->getObjectType(), 25 ),
                        'object_type_id' => (int)$this->getObjectType(),

                        //Restrict based on src_object_id when entering absences as well.
                        //This allows multiple absence policies to point to the same pay code
                        //and still have multiple entries on the same day with the same branch/department/job/task.
                        //Some customers have 5-10 UNPAID absence policies all going to the same UNPAID pay code.
                        //This is required to allow more than one to be used on the same day.
                        'src_object_id' => (int)$this->getSourceObject(),
                        'pay_code_id' => (int)$this->getPayCode(),

                        'branch_id' => (int)$this->getBranch(),
                        'department_id' => (int)$this->getDepartment(),
                        'job_id' => (int)$this->getJob(),
                        'job_item_id' => (int)$this->getJobItem()
                    );
                    $udtlf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
                }
            } elseif ($this->getObjectType() == 30) {
                $udtlf->getByUserIdAndDateStampAndObjectTypeAndPayCodeIdAndOverride($this->getUser(), $this->getDateStamp(), $this->getObjectType(), $this->getPayCode(), true);
            } elseif ($this->getObjectType() == 40) {
                $udtlf->getByUserIdAndDateStampAndObjectTypeAndPayCodeIdAndOverride($this->getUser(), $this->getDateStamp(), $this->getObjectType(), $this->getPayCode(), true);
            } elseif ($this->getObjectType() == 100) {
                $udtlf->getByUserIdAndDateStampAndObjectTypeAndPayCodeIdAndOverride($this->getUser(), $this->getDateStamp(), $this->getObjectType(), $this->getPayCode(), true);
            } elseif ($this->getObjectType() == 5 or ($this->getObjectType() == 20 and $this->getPunchControlID() > 0)) {
                $udtlf->getByUserIdAndDateStampAndObjectTypeAndPunchControlIdAndOverride($this->getUser(), $this->getDateStamp(), $this->getObjectType(), $this->getPunchControlID(), true);
            }

            //Debug::text('Record Count: '. (int)$udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            if ($udtlf->getRecordCount() > 0) {
                Debug::text('Found an overridden row... NOT SAVING: ' . $udtlf->getCurrent()->getId(), __FILE__, __LINE__, __METHOD__, 10);
                $this->Validator->isTRUE('absence_policy_id',
                    false,
                    TTi18n::gettext('Similar entry already exists, not overriding'));
            }
            unset($udtlf);
        }

        if ($ignore_warning == false) {
            //Check to see if timesheet is verified, if so unverify it on modified punch.
            //Make sure exceptions are calculated *after* this so TimeSheet Not Verified exceptions can be triggered again.
            if ($this->getDateStamp() != false
                and is_object($this->getPayPeriodObject())
                and is_object($this->getPayPeriodObject()->getPayPeriodScheduleObject())
                and $this->getPayPeriodObject()->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10
            ) {
                //Find out if timesheet is verified or not.
                $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $pptsvlf->getByPayPeriodIdAndUserId($this->getPayPeriod(), $this->getUser());
                if ($pptsvlf->getRecordCount() > 0) {
                    //Pay period is verified, delete all records and make log entry.
                    $this->Validator->Warning('date_stamp', TTi18n::gettext('Pay period is already verified, saving these changes will require it to be reverified'));
                }
            }
        }

        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getOverride()
    {
        if (isset($this->data['override'])) {
            return $this->fromBool($this->data['override']);
        }
        return false;
    }

    public function getPayPeriodObject()
    {
        return $this->getGenericObject('PayPeriodListFactory', $this->getPayPeriod(), 'pay_period_obj');
    }

    public function getPayPeriod()
    {
        if (isset($this->data['pay_period_id'])) {
            return (int)$this->data['pay_period_id'];
        }

        return false;
    }

    public function getPayCode()
    {
        if (isset($this->data['pay_code_id'])) {
            return (int)$this->data['pay_code_id'];
        }

        return false;
    }

    public function getBranch()
    {
        if (isset($this->data['branch_id'])) {
            return (int)$this->data['branch_id'];
        }

        return false;
    }

    public function getDepartment()
    {
        if (isset($this->data['department_id'])) {
            return (int)$this->data['department_id'];
        }

        return false;
    }

    public function getJob()
    {
        if (isset($this->data['job_id'])) {
            return (int)$this->data['job_id'];
        }

        return false;
    }

    public function getJobItem()
    {
        if (isset($this->data['job_item_id'])) {
            return (int)$this->data['job_item_id'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getPayPeriod() == false) {
            $this->setPayPeriod(); //Not specifying pay period forces it to be looked up.
        }

        if ($this->getPayCode() === false) {
            $this->setPayCode(0);
        }

        if ($this->getPunchControlID() === false) {
            $this->setPunchControlID(0);
        }

        if ($this->getBranch() === false) {
            $this->setBranch(0);
        }

        if ($this->getDepartment() === false) {
            $this->setDepartment(0);
        }

        if ($this->getJob() === false) {
            $this->setJob(0);
        }

        if ($this->getJobItem() === false) {
            $this->setJobItem(0);
        }

        if ($this->getQuantity() === false) {
            $this->setQuantity(0);
        }

        if ($this->getBadQuantity() === false) {
            $this->setBadQuantity(0);
        }

        $this->setTotalTimeAmount($this->calcTotalTimeAmount());
        $this->setTotalTimeAmountWithBurden($this->calcTotalTimeAmountWithBurden());

        if ($this->getEnableTimeSheetVerificationCheck()) {
            //Check to see if timesheet is verified, if so unverify it on modified punch.
            //Make sure exceptions are calculated *after* this so TimeSheet Not Verified exceptions can be triggered again.
            if ($this->getDateStamp() != false
                and is_object($this->getPayPeriodObject())
                and is_object($this->getPayPeriodObject()->getPayPeriodScheduleObject())
                and $this->getPayPeriodObject()->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10
            ) {
                //Find out if timesheet is verified or not.
                $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $pptsvlf->getByPayPeriodIdAndUserId($this->getPayPeriod(), $this->getUser());
                if ($pptsvlf->getRecordCount() > 0) {
                    //Pay period is verified, delete all records and make log entry.
                    //These can be added during the maintenance jobs, so the audit records are recorded as user_id=0, check those first.
                    Debug::text('Pay Period is verified, deleting verification records: ' . $pptsvlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($pptsvlf as $pptsv_obj) {
                        if ($this->getObjectType() == 50 and is_object($this->getSourceObjectObject())) {
                            TTLog::addEntry($pptsv_obj->getId(), 500, TTi18n::getText('TimeSheet Modified After Verification') . ': ' . UserListFactory::getFullNameById($this->getUser()) . ' ' . TTi18n::getText('Absence') . ': ' . $this->getSourceObjectObject()->getName() . ' - ' . TTDate::getDate('DATE', $this->getDateStamp()), null, $pptsvlf->getTable());
                        }
                        $pptsv_obj->setDeleted(true);
                        if ($pptsv_obj->isValid()) {
                            $pptsv_obj->Save();
                        }
                    }
                }
            }
        }

        return true;
    }

    public function setPayPeriod($id = null)
    {
        $id = trim($id);

        if ($id == null) {
            $id = (int)PayPeriodListFactory::findPayPeriod($this->getUser(), $this->getDateStamp());
        }

        $pplf = TTnew('PayPeriodListFactory');

        //Allow NULL pay period, incase its an absence or something in the future.
        //Cron will fill in the pay period later.
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('pay_period',
                $pplf->getByID($id),
                TTi18n::gettext('Invalid Pay Period')
            )
        ) {
            $this->data['pay_period_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPunchControlID($id)
    {
        $id = trim($id);

        $pclf = TTnew('PunchControlListFactory');

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('punch_control_id',
                $pclf->getByID($id),
                TTi18n::gettext('Invalid Punch Control ID')
            )
        ) {
            $this->data['punch_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function setBranch($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $blf = TTnew('BranchListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('branch_id',
                $blf->getByID($id),
                TTi18n::gettext('Branch does not exist')
            )
        ) {
            $this->data['branch_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDepartment($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $dlf = TTnew('DepartmentListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('department_id',
                $dlf->getByID($id),
                TTi18n::gettext('Department does not exist')
            )
        ) {
            $this->data['department_id'] = $id;

            return true;
        }

        return false;
    }

    public function setJob($id)
    {
        $this->data['job_id'] = $id;

        return true;
    }

    public function setJobItem($id)
    {
        $this->data['job_item_id'] = $id;

        return true;
    }

    public function getQuantity()
    {
        if (isset($this->data['quantity'])) {
            return (float)$this->data['quantity'];
        }

        return false;
    }

    public function setQuantity($val)
    {
        $val = TTi18n::parseFloat($val);

        if ($val == false or $val == 0 or $val == '') {
            $val = 0;
        }

        if ($val == 0
            or
            $this->Validator->isFloat('quantity',
                $val,
                TTi18n::gettext('Incorrect quantity'))
        ) {
            $this->data['quantity'] = $val;

            return true;
        }

        return false;
    }

    public function getBadQuantity()
    {
        if (isset($this->data['bad_quantity'])) {
            return (float)$this->data['bad_quantity'];
        }

        return false;
    }

    public function setBadQuantity($val)
    {
        $val = TTi18n::parseFloat($val);

        if ($val == false or $val == 0 or $val == '') {
            $val = 0;
        }

        if ($val == 0
            or
            $this->Validator->isFloat('bad_quantity',
                $val,
                TTi18n::gettext('Incorrect bad quantity'))
        ) {
            $this->data['bad_quantity'] = $val;

            return true;
        }

        return false;
    }

    public function setTotalTimeAmount($value)
    {
        if ($value === false or $value === '' or $value === null) {
            $value = 0;
        }

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isFloat('total_time_amount',
            $value,
            TTi18n::gettext('Incorrect Total Time Amount'))
        ) {
            $this->data['total_time_amount'] = $value;

            return true;
        }

        return false;
    }

    public function calcTotalTimeAmount()
    {
        //Before switching to *not* setting setLocale() LC_NUMERIC, calculating in es_ES locale, it returns float value using comma decimal symbol which causes a SQL error on insert.
        $retval = (TTDate::getHours($this->getTotalTime()) * $this->getHourlyRate());
        //$retval = bcmul( TTDate::getHours( $this->getTotalTime() ), $this->getHourlyRate() );
        return $retval;
    }

    public function getTotalTime()
    {
        if (isset($this->data['total_time'])) {
            return (int)$this->data['total_time'];
        }
        return false;
    }

    public function getHourlyRate()
    {
        if (isset($this->data['hourly_rate'])) {
            return $this->data['hourly_rate'];
        }

        return false;
    }

    public function setTotalTimeAmountWithBurden($value)
    {
        if ($value === false or $value === '' or $value === null) {
            $value = 0;
        }

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isFloat('total_time_amount_with_burden',
            $value,
            TTi18n::gettext('Incorrect Total Time Amount with Burden'))
        ) {
            $this->data['total_time_amount_with_burden'] = $value;

            return true;
        }

        return false;
    }

    public function calcTotalTimeAmountWithBurden()
    {
        $retval = (TTDate::getHours($this->getTotalTime()) * $this->getHourlyRateWithBurden());
        return $retval;
    }

    public function getHourlyRateWithBurden()
    {
        if (isset($this->data['hourly_rate_with_burden'])) {
            return $this->data['hourly_rate_with_burden'];
        }

        return false;
    }

    public function getEnableTimeSheetVerificationCheck()
    {
        if (isset($this->timesheet_verification_check)) {
            return $this->timesheet_verification_check;
        }

        return false;
    }

    public function getSourceObjectObject()
    {
        $lf = $this->getSourceObjectListFactory($this->getObjectType());
        if (is_object($lf)) {
            $lf->getByID($this->getSourceObject());
            if ($lf->getRecordCount() == 1) {
                return $lf->getCurrent();
            }
        }

        return false;
    }

    public function postSave()
    {
        if ($this->getEnableCalcSystemTotalTime() == true) {
            Debug::text('Calc System Total Time Enabled: ', __FILE__, __LINE__, __METHOD__, 10);
            $this->calcSystemTotalTime();
        } else {
            Debug::text('Calc System Total Time Disabled: ', __FILE__, __LINE__, __METHOD__, 10);
        }

        return true;
    }

    public function getEnableCalcSystemTotalTime()
    {
        if (isset($this->calc_system_total_time)) {
            return $this->calc_system_total_time;
        }

        return false;
    }

    public function calcSystemTotalTime()
    {
        global $profiler;

        $profiler->startTimer('UserDateTotal::calcSystemTotalTime() - Part 1');

        if ($this->getUser() == false or $this->getDateStamp() == false) {
            Debug::text(' User/DateStamp not found!', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if (is_object($this->getPayPeriodObject())
            and $this->getPayPeriodObject()->getStatus() == 20
        ) {
            Debug::text(' Pay Period is closed!', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }


        //$this->deleteSystemTotalTime(); //Handled in calculatePolicy now.

        $cp = TTNew('CalculatePolicy');
        $cp->setFlag('exception', $this->getEnableCalcException());
        $cp->setFlag('exception_premature', $this->getEnablePreMatureException());
        $cp->setUserObject($this->getUserObject());
        $cp->addPendingCalculationDate(array_merge((array)$this->getDateStamp(), (array)$this->alternate_date_stamps));
        $cp->calculate(); //This sets timezone itself.
        $cp->Save();

        return true;
    }

    public function getEnableCalcException()
    {
        if (isset($this->calc_exception)) {
            return $this->calc_exception;
        }

        return false;
    }

    public function getEnablePreMatureException()
    {
        if (isset($this->premature_exception)) {
            return $this->premature_exception;
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
                        case 'pay_period_id': //Ignore this if its set, as its should be determined in preSave().
                            break;
                        case 'date_stamp':
                            $this->setDateStamp(TTDate::parseDateTime($data[$key]));
                            break;
                        case 'start_time_stamp':
                            $this->setStartTimeStamp(TTDate::parseDateTime($data[$key]));
                            break;
                        case 'end_time_stamp':
                            $this->setEndTimeStamp(TTDate::parseDateTime($data[$key]));
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

    public function setDateStamp($epoch)
    {
        $epoch = (int)$epoch;

        if ($this->Validator->isDate('date_stamp',
            $epoch,
            TTi18n::gettext('Incorrect date'))
        ) {
            if ($epoch > 0) {
                //Use middle day epoch to help avoid confusion with different timezones/DST.
                //See comments about timezones in CalculatePolicy->_calculate().
                $this->data['date_stamp'] = TTDate::getMiddleDayEpoch($epoch);

                $this->setPayPeriod(); //Force pay period to be set as soon as the date is.
                return true;
            } else {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Incorrect date'));
            }
        }

        return false;
    }

    public function setStartTimeStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == ''
            or
            $this->Validator->isDate('start_time_stamp',
                $epoch,
                TTi18n::gettext('Incorrect start time stamp'))
        ) {
            $this->data['start_time_stamp'] = $epoch;

            return true;
        }

        return false;
    }

    public function setEndTimeStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == ''
            or
            $this->Validator->isDate('end_time_stamp',
                $epoch,
                TTi18n::gettext('Incorrect end time stamp'))

        ) {
            $this->data['end_time_stamp'] = $epoch;

            return true;
        }

        return false;
    }

    //Takes UserDateTotal rows, and calculate the accumlated time sections

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $uf = TTnew('UserFactory');

        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'first_name':
                        case 'last_name':
                        case 'group':
                        case 'title':
                        case 'default_branch':
                        case 'default_department':
                        case 'branch':
                        case 'department':
                        case 'over_time_policy':
                        case 'absence_policy':
                        case 'premium_policy':
                        case 'meal_policy':
                        case 'break_policy':
                        case 'job':
                        case 'job_item':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'title_id':
                        case 'user_id':
                        case 'user_status_id':
                        case 'group_id':
                        case 'pay_period_id':
                        case 'default_branch_id':
                        case 'default_department_id':
                        case 'absence_policy_type_id':
                            $data[$variable] = (int)$this->getColumn($variable);
                            break;
                        case 'object_type':
                            $data[$variable] = Option::getByKey($this->getObjectType(), $this->getOptions($variable));
                            break;
                        case 'user_status':
                            $data[$variable] = Option::getByKey((int)$this->getColumn('user_status_id'), $uf->getOptions('status'));
                            break;
                        case 'date_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getDateStamp());
                            break;
                        case 'start_time_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE+TIME', $this->$function()); //Include both date+time
                            break;
                        case 'end_time_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE+TIME', $this->$function()); //Include both date+time
                            break;
                        case 'name':
                            $data[$variable] = $this->getName();
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }

                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getColumn('user_id'), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function getName()
    {
        switch ($this->getObjectType()) {
            case 5:
                $name = TTi18n::gettext('Total Time');
                break;
            case 10: //Worked Time
                $name = TTi18n::gettext('Worked Time');
                break;
            case 20: //Regular Time
            case 25:
            case 30:
            case 40:
            case 100:
            case 110:
                if (is_object($this->getPayCodeObject())) {
                    $name = $this->getPayCodeObject()->getName();
                } elseif ($this->getObjectType() == 20) { //Regular Time
                    $name = TTi18n::gettext('ERROR: UnAssigned Regular Time'); //No regular time policies to catch all worked time.
                } else {
                    $name = TTi18n::gettext('ERROR: INVALID PAY CODE');
                }
                break;
            case 101: //Lunch (Taken)
                $name = TTi18n::gettext('Lunch (Taken)');
                break;
            case 111: //Break (Taken)
                $name = TTi18n::gettext('Break (Taken)');
                break;
            case 50:
                //Absence taken time use the policy name, *not* pay code name.
                $lf = TTNew('AbsencePolicyListFactory');
                $lf->getByID($this->getSourceObject());
                if ($lf->getRecordCount() == 1) {
                    $name = $lf->getCurrent()->getName();
                } else {
                    $name = TTi18n::gettext('ERROR: Invalid Absence Policy'); //No regular time policies to catch all worked time.
                }
                break;
            default:
                $name = TTi18n::gettext('N/A');
                break;
        }

        if (isset($name)) {
            return $name;
        }

        return false;
    }

    public function getPayCodeObject()
    {
        return $this->getGenericObject('PayCodeListFactory', $this->getPayCode(), 'pay_code_obj');
    }

    public function addLog($log_action)
    {
        if ($this->getOverride() == true and $this->getDateStamp() != false) {
            if ($this->getObjectType() == 50) { //Absence
                return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Absence') . ' - ' . TTi18n::getText('Date') . ': ' . TTDate::getDate('DATE', $this->getDateStamp()) . ' ' . TTi18n::getText('Total Time') . ': ' . TTDate::getTimeUnit($this->getTotalTime()), null, $this->getTable(), $this);
            } else {
                return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Accumulated Time') . ' - ' . TTi18n::getText('Date') . ': ' . TTDate::getDate('DATE', $this->getDateStamp()) . ' ' . TTi18n::getText('Total Time') . ': ' . TTDate::getTimeUnit($this->getTotalTime()), null, $this->getTable(), $this);
            }
        }
    }
}
