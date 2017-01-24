<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 ********************************************************************************/


/**
 * @package Modules\Install
 */
class InstallSchema_1052A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Initialize the company_generic_map_seq table so it can be updated in the SQL file.
        if (strncmp($this->db->databaseType, 'mysql', 5) == 0) {
            $this->db->GenID('company_generic_map_id_seq'); //Make sure the sequence exists so it can be updated.
        }

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Go through each permission group, and enable exception report for anyone who can see timesheet summary report.
        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                Debug::text('Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                if ($c_obj->getStatus() != 30) {
                    $pclf = TTnew('PermissionControlListFactory');
                    $pclf->getByCompanyId($c_obj->getId(), null, null, null, array('name' => 'asc')); //Force order to avoid referencing column that was added in a later version (level)
                    if ($pclf->getRecordCount() > 0) {
                        foreach ($pclf as $pc_obj) {
                            Debug::text('Permission Group: ' . $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                            $plf = TTnew('PermissionListFactory');
                            $plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($c_obj->getId(), $pc_obj->getId(), 'report', 'view_timesheet_summary', 1);
                            if ($plf->getRecordCount() > 0) {
                                Debug::text('Found permission group with timesheet report enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                                $pc_obj->setPermission(array('report' => array('view_exception_summary' => true)));
                            } else {
                                Debug::text('Permission group does NOT have timesheet report enabled...', __FILE__, __LINE__, __METHOD__, 9);
                            }
                        }
                    }
                    unset($pc_obj);
                }
            }
        }

        return true;
    }
}