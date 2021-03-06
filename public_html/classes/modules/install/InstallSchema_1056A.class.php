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
 * @package Modules\Install
 */
class InstallSchema_1056A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Make sure Medicare Employer uses the same include/exclude accounts as Medicare Employee.
        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                Debug::text('Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                if ($c_obj->getStatus() != 30) {
                    $ppslf = TTNew('PayPeriodScheduleListFactory');
                    $ppslf->getByCompanyID($c_obj->getId());
                    if ($ppslf->getRecordCount() > 0) {
                        $minimum_time_between_shifts = $ppslf->getCurrent()->getNewDayTriggerTime();
                    }

                    if (isset($minimum_time_between_shifts)) {
                        $pplf = TTNew('PremiumPolicyListFactory');
                        $pplf->getAPISearchByCompanyIdAndArrayCriteria($c_obj->getID(), array('type_id' => 50));
                        if ($pplf->getRecordCount() > 0) {
                            foreach ($pplf as $pp_obj) {
                                $pp_obj->setMinimumTimeBetweenShift($minimum_time_between_shifts);
                                if ($pp_obj->isValid()) {
                                    $pp_obj->Save();
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }
}
