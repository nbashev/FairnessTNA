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
class AJAX_Server
{
    public function getCurrentUserFullName()
    {
        global $current_user;

        if (is_object($current_user)) {
            return $current_user->getFullName();
        }

        return false;
    }

    public function getCurrentCompanyName()
    {
        global $current_company;

        if (is_object($current_company)) {
            return $current_company->getName();
        }

        return false;
    }

    public function getProvinceOptions($country)
    {
        Debug::Arr($country, 'aCountry: ', __FILE__, __LINE__, __METHOD__, 10);

        if (!is_array($country) and $country == '') {
            return false;
        }

        if (!is_array($country)) {
            $country = array($country);
        }

        Debug::Arr($country, 'bCountry: ', __FILE__, __LINE__, __METHOD__, 10);

        $cf = TTnew('CompanyFactory');

        $province_arr = $cf->getOptions('province');

        $retarr = array();

        foreach ($country as $tmp_country) {
            if (isset($province_arr[strtoupper($tmp_country)])) {
                //Debug::Arr($province_arr[strtoupper($tmp_country)], 'Provinces Array', __FILE__, __LINE__, __METHOD__, 10);

                $retarr = array_merge($retarr, $province_arr[strtoupper($tmp_country)]);
                //$retarr = array_merge( $retarr, Misc::prependArray( array( -10 => '--' ), $province_arr[strtoupper($tmp_country)] ) );
            }
        }

        if (count($retarr) == 0) {
            $retarr = array('00' => '--');
        }

        return $retarr;
    }

    public function getJobItemOptions($job_id, $user_id, $login_time, $key, $include_disabled = true)
    {
        //This must work when not fully authenticated.
        if ($user_id != '' and $user_id > 0) {
            $ulf = TTnew('UserListFactory');
            $ulf->getById((int)$user_id);
            if ($ulf->getRecordCount() == 1) {
                Debug::Text('Found User, checking key!', __FILE__, __LINE__, __METHOD__, 10);
                $current_user = $ulf->getCurrent();

                //Only allow punches within 5 minutes of the original submit time.
                if ($login_time >= (time() - (60 * 5)) and trim($key) == md5($user_id . $login_time . $current_user->getPasswordSalt())) {
                    Debug::text('Job ID: ' . $job_id . ' Include Disabled: ' . (int)$include_disabled, __FILE__, __LINE__, __METHOD__, 10);

                    $jilf = TTnew('JobItemListFactory');
                    $jilf->getByCompanyIdAndJobId($current_user->getCompany(), $job_id);
                    //$jilf->getByJobId( $job_id );
                    $job_item_options = $jilf->getArrayByListFactory($jilf, true, $include_disabled);
                    if ($job_item_options != false and is_array($job_item_options)) {
                        return $job_item_options;
                    }
                }
            }
        }

        Debug::text('Returning FALSE!', __FILE__, __LINE__, __METHOD__, 10);

        $retarr = array('00' => '--');

        return $retarr;
    }

    public function strtotime($str)
    {
        return TTDate::strtotime($str);
    }

    public function parseDateTime($str)
    {
        return TTDate::parseDateTime($str);
    }

    public function getDate($format, $epoch)
    {
        return TTDate::getDate($format, $epoch);
    }

    public function getBeginMonthEpoch($epoch)
    {
        return TTDate::getBeginMonthEpoch($epoch);
    }

    public function getTimeZoneOffset($time_zone)
    {
        TTDate::setTimeZone($time_zone);
        return TTDate::getTimeZoneOffset();
    }
}
