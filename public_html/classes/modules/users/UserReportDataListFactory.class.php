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
 * @package Modules\Users
 */
class UserReportDataListFactory extends UserReportDataFactory implements IteratorAggregate
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

        return $this;
    }

    public function getByUserId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndId($user_id, $id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndScript($user_id, $script, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($script == '') {
            return false;
        } else {
            $script = self::handleScriptName($script);
        }

        if ($order == null) {
            $order = array('is_default' => 'desc', 'name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'script' => $script,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND script = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndScriptAndDefault($user_id, $script, $default = true, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($script == '') {
            return false;
        } else {
            $script = self::handleScriptName($script);
        }

        if ($order == null) {
            $order = array('updated_date' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'script' => $script,
            'default' => $this->toBool($default),
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND script = ?
						AND is_default = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndScriptArray($user_id, $script, $include_blank = true)
    {
        $ugdlf = new UserGenericDataListFactory();
        $ugdlf->getByUserIdAndScript($user_id, $script);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($ugdlf as $ugd_obj) {
            if ($ugd_obj->getDefault() == true) {
                $default = ' (Default)';
            } else {
                $default = null;
            }
            $list[$ugd_obj->getID()] = $ugd_obj->getName() . $default;
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    /*

        Company List Functions

    */
    public function getByCompanyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND user_id is NULL
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndId($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND user_id is NULL
						AND id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndScript($company_id, $script, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($script == '') {
            return false;
        } else {
            $script = self::handleScriptName($script);
        }

        if ($order == null) {
            $order = array('updated_date' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'script' => $script
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND user_id is NULL
						AND script = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndScriptAndDefault($company_id, $script, $default = true, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($script == '') {
            return false;
        } else {
            $script = self::handleScriptName($script);
        }

        if ($order == null) {
            $order = array('updated_date' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'script' => $script,
            'default' => $this->toBool($default)
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND user_id is NULL
						AND script = ?
						AND is_default = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getAPISearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('is_scheduled');

        $sort_column_aliases = array(
            'script_name' => 'script',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);
        if ($order == null) {
            $order = array('a.name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);
        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.* ';

        $query .= ', 0 as is_scheduled ';

        $query .= '	from ' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where a.company_id = ?
					';

        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['script'])) ? $this->getWhereClauseSQL('a.script', $filter_data['script'], 'text', $ph) : null;
        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;

        $query .= (isset($filter_data['is_default'])) ? $this->getWhereClauseSQL('a.is_default', $filter_data['is_default'], 'boolean', $ph) : null;
        $query .= (isset($filter_data['is_scheduled'])) ? $this->getWhereClauseSQL('is_scheduled', $filter_data['is_scheduled'], 'boolean', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= '
						AND a.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        return $this;
    }

    public function getArrayByListFactory($lf, $include_blank = true)
    {
        if (!is_object($lf)) {
            return false;
        }

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($lf as $obj) {
            $list[$obj->getID()] = $obj->getName();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getByCompanyIdAndScriptArray($company_id, $script, $include_blank = true)
    {
        $ugdlf = new UserGenericDataListFactory();
        $ugdlf->getByUserIdAndScript($company_id, $script);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($ugdlf as $ugd_obj) {
            if ($ugd_obj->getDefault() == true) {
                $default = ' (Default)';
            } else {
                $default = null;
            }
            $list[$ugd_obj->getID()] = $ugd_obj->getName() . $default;
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }
}
