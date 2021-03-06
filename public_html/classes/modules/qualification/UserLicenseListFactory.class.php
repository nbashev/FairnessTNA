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
class UserLicenseListFactory extends UserLicenseFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getById($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $this->rs = $this->getCache($id);
        if ($this->rs === false) {
            $ph = array(
                'id' => (int)$id,
            );

            $query = '
						select	*
						from	' . $this->getTable() . '
						where	id = ?
							AND deleted = 0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $id);
        }

        return $this;
    }

    public function getByUserId($user_id, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $qf = new QualificationFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $qf->getTable() . ' as b on a.qualification_id = b.id
					where	a.id = ?
						AND b.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        $qf = new QualificationFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $qf->getTable() . ' as b on a.qualification_id = b.id
					where	b.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndUserId($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $this->rs = $this->getCache($id . $user_id);
        if ($this->rs === false) {
            $ph = array(
                'id' => (int)$id,
                'user_id' => (int)$user_id,
            );

            $query = '
						select	*
						from	' . $this->getTable() . '
						where	id = ?
							AND user_id = ?
							AND deleted = 0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $id . $user_id);
        }

        return $this;
    }

    public function getByUserIdAndQualificationId($user_id, $qualification_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $this->rs = $this->getCache($user_id . $qualification_id);
        if ($this->rs === false) {
            $ph = array(
                'user_id' => (int)$user_id,
                'qualification_id' => (int)$qualification_id,
            );

            $query = '
						select	*
						from	' . $this->getTable() . '
						where	user_id = ?
							AND qualification_id = ?
							AND deleted = 0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $user_id . $qualification_id);
        }

        return $this;
    }

    public function getAPISearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (isset($filter_data['include_user_id'])) {
            $filter_data['user_id'] = $filter_data['include_user_id'];
        }
        if (isset($filter_data['exclude_user_id'])) {
            $filter_data['exclude_id'] = $filter_data['exclude_user_id'];
        }

        if (isset($filter_data['qualification_group_id'])) {
            $filter_data['group_id'] = $filter_data['qualification_group_id'];
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('first_name', 'last_name', 'qualification', 'qgf.name', 'default_branch', 'default_department', 'user_group', 'title');

        $sort_column_aliases = array();

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('qualification' => 'asc', 'qgf.name' => 'asc');
            $strict = false;
        } else {
            if (isset($order['group'])) {
                $order['qgf.name'] = $order['group'];
                unset($order['group']);
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();
        $qf = new QualificationFactory();
        $usf = new UserSkillFactory();
        $ulf = new UserLanguageFactory();
        $umf = new UserMembershipFactory();
        $qgf = new QualificationGroupFactory();
        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							uf.first_name as first_name,
							uf.last_name as last_name,
							qf.name as qualification,
							qgf.name as "group",

							bf.id as default_branch_id,
							bf.name as default_branch,
							df.id as default_department_id,
							df.name as default_department,
							ugf.id as user_group_id,
							ugf.name as user_group,
							utf.id as user_title_id,
							utf.name as title,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.user_id = uf.id AND uf.deleted = 0)
						LEFT JOIN ' . $usf->getTable() . ' as usf ON	( a.qualification_id = usf.qualification_id AND usf.deleted = 0)
						LEFT JOIN ' . $ulf->getTable() . ' as ulf ON ( a.qualification_id = ulf.qualification_id AND ulf.deleted =0 )
						LEFT JOIN ' . $umf->getTable() . ' as umf ON ( a.qualification_id = umf.qualification_id AND umf.deleted = 0 )
						LEFT JOIN ' . $qf->getTable() . ' as qf ON ( a.qualification_id = qf.id  AND qf.deleted = 0 )
						LEFT JOIN ' . $bf->getTable() . ' as bf ON ( uf.default_branch_id = bf.id AND bf.deleted = 0)
						LEFT JOIN ' . $df->getTable() . ' as df ON ( uf.default_department_id = df.id AND df.deleted = 0)
						LEFT JOIN ' . $ugf->getTable() . ' as ugf ON ( uf.group_id = ugf.id AND ugf.deleted = 0 )
						LEFT JOIN ' . $utf->getTable() . ' as utf ON ( uf.title_id = utf.id AND utf.deleted = 0 )
						LEFT JOIN ' . $qgf->getTable() . ' as qgf ON ( qf.group_id = qgf.id AND qgf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	qf.company_id = ?';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['qualification_id'])) ? $this->getWhereClauseSQL('a.qualification_id', $filter_data['qualification_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['qualification'])) ? $this->getWhereClauseSQL('qf.name', $filter_data['qualification'], 'text', $ph) : null;

        $query .= (isset($filter_data['proficiency_id'])) ? $this->getWhereClauseSQL('usf.proficiency_id', $filter_data['proficiency_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['fluency_id'])) ? $this->getWhereClauseSQL('ulf.fluency_id', $filter_data['fluency_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['competency_id'])) ? $this->getWhereClauseSQL('ulf.competency_id', $filter_data['competency_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['ownership_id'])) ? $this->getWhereClauseSQL('umf.ownership_id', $filter_data['ownership_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['license_number'])) ? $this->getWhereClauseSQL('a.license_number', $filter_data['license_number'], 'numeric', $ph) : null;

        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('qf.group_id', $filter_data['group_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['group'])) ? $this->getWhereClauseSQL('qgf.name', $filter_data['group'], 'text', $ph) : null;

        $query .= (isset($filter_data['qualification_type_id'])) ? $this->getWhereClauseSQL('qf.type_id', $filter_data['qualification_type_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('uf.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('uf.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['tag'])) ? $this->getWhereClauseSQL('a.id', array('company_id' => (int)$company_id, 'object_type_id' => 253, 'tag' => $filter_data['tag']), 'tag', $ph) : null;

        $query .= (isset($filter_data['license_issued_date'])) ? $this->getWhereClauseSQL('a.license_issued_date', $filter_data['license_issued_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['license_expiry_date'])) ? $this->getWhereClauseSQL('a.license_expiry_date', $filter_data['license_expiry_date'], 'date_range', $ph) : null;

        $query .= (isset($filter_data['license_expiry_start_date'])) ? $this->getWhereClauseSQL('a.license_expiry_date', $filter_data['license_expiry_start_date'], 'start_date', $ph) : null;
        $query .= (isset($filter_data['license_expiry_end_date'])) ? $this->getWhereClauseSQL('a.license_expiry_date', $filter_data['license_expiry_end_date'], 'end_date', $ph) : null;
        /*
                if ( isset($filter_data['license_expiry_start_date']) AND !is_array($filter_data['license_expiry_start_date']) AND trim($filter_data['license_expiry_start_date']) != '' ) {
                    $ph[] = (int)$filter_data['license_expiry_start_date'];
                    $query	.=	' AND a.license_expiry_date >= ?';
                }
                if ( isset($filter_data['license_expiry_end_date']) AND !is_array($filter_data['license_expiry_end_date']) AND trim($filter_data['license_expiry_end_date']) != '' ) {
                    $ph[] = (int)$filter_data['license_expiry_end_date'];
                    $query	.=	' AND a.license_expiry_date <= ?';
                }
        */
        $query .= (isset($filter_data['created_date'])) ? $this->getWhereClauseSQL('a.created_date', $filter_data['created_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['updated_date'])) ? $this->getWhereClauseSQL('a.updated_date', $filter_data['updated_date'], 'date_range', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
