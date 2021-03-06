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
class InstallSchema_1040A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Loop through all permission control rows and set the levels as best we can.
        $pclf = TTnew('PermissionControlListFactory');
        $pclf->getAll();
        if ($pclf->getRecordCount() > 0) {
            $pf = TTnew('PermissionFactory');
            $preset_options = $pf->getOptions('preset');
            $preset_level_options = $pf->getOptions('preset_level');

            foreach ($pclf as $pc_obj) {
                $name = $pc_obj->getName();

                $closest_preset_id = Misc::findClosestMatch($name, $preset_options, 75);
                if (isset($preset_level_options[$closest_preset_id])) {
                    $preset_level = $preset_level_options[$closest_preset_id];
                } else {
                    $preset_level = 1; //Use the lowest level if we can't find one, so by default they can't add a new administrator/supervisor.

                    //Try to count the number of permissions and match them to the number of permissions in each preset and use the closest level?
                    $permission_user_data = $pc_obj->getPermission();
                    if (is_array($permission_user_data)) {
                        $preset_match = array();
                        foreach ($preset_options as $preset => $preset_name) {
                            $tmp_preset_permissions = $pf->getPresetPermissions($preset, array());
                            $preset_permission_diff_arr = Misc::arrayDiffAssocRecursive($permission_user_data, $tmp_preset_permissions);

                            $preset_permission_diff_count = count($preset_permission_diff_arr, COUNT_RECURSIVE);
                            Debug::text('Preset Permission Diff Count...: ' . $preset_permission_diff_count . ' Preset ID: ' . $preset, __FILE__, __LINE__, __METHOD__, 10);
                            $preset_match[$preset] = $preset_permission_diff_count;
                        }
                        unset($preset_name);//code standards
                        unset($tmp_preset_permissions);

                        krsort($preset_match);
                        //Flip the array so if there are more then one preset with the same match_count, we use the smallest preset value.
                        $preset_match = array_flip($preset_match);
                        //Flip the array back so the key is the match_preset again.
                        $preset_match = array_flip($preset_match);
                        foreach ($preset_match as $best_match_preset => $match_value) {
                            break;
                        }

                        Debug::Arr($preset_match, 'Preset Match: Best Match: ' . $best_match_preset . ' Value: ' . $match_value . ' Current Name: ' . $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);

                        if (isset($preset_options[$best_match_preset])) {
                            $preset_level = $preset_level_options[$best_match_preset]; //Use the preset level minus one, so they don't match exactly.
                            if ($preset_level > 1) {
                                $preset_level--;
                            }
                            Debug::Text('Closest PreSet Match Level: ' . $preset_level . ' Tmp: ' . $preset_options[$best_match_preset], __FILE__, __LINE__, __METHOD__, 10);
                        }
                    }
                }
                Debug::Text('Closest Match For: ' . $name . ' ID: ' . (int)$closest_preset_id . ' Level: ' . $preset_level, __FILE__, __LINE__, __METHOD__, 10);

                //Update level for permission group.
                $pc_obj->setLevel($preset_level);
                if ($pc_obj->isValid()) {
                    $pc_obj->Save();
                }
                unset($pc_obj);
            }
        }
        unset($pclf);

        return true;
    }
}
