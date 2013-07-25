<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 8371 $
 * $Id: NS.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_NS extends PayrollDeduction_CA {
	function getProvincialSurtax() {
		/*
			V1 =
			For NS
				Where T4 <= 10000
				V1 = 0

				Where T4 > 10000
				V1 = 0.10 * ( T4 - 10000 )
		*/

		$T4 = $this->getProvincialBasicTax();
		$V1 = 0;

		if ( $this->getDate() >= strtotime('01-Jan-2008') ) {
			if ( $T4 <= 10000 ) {
				$V1 = 0;
			} elseif ( $T4 > 10000 ) {
				$V1 = bcmul( 0.10, bcsub( $T4, 10000 ) );
			}
		}

		Debug::text('V1: '. $V1, __FILE__, __LINE__, __METHOD__, 10);

		return $V1;
	}
}
?>
