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
 * @package ChequeForms
 */

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ChequeForms_Base.class.php');

class ChequeForms_9085 extends ChequeForms_Base
{
    public function getTemplateSchema($name = null)
    {
        $template_schema = array(
            //Initialize page1
            array(
                'page' => 1,
                'template_page' => 1,

            ),

            // full name
            'full_name' => array(
                'coordinates' => array(
                    'x' => 17,
                    'y' => 50,
                    'h' => 5,
                    'w' => 100,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                )
            ),
            // address
            'address' => array(
                'function' => array('filterAddress', 'drawNormal'),
                'coordinates' => array(
                    'x' => 17,
                    'y' => 55,
                    'h' => 5,
                    'w' => 100,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                ),
            ),
            // province
            'province' => array(
                'function' => array('filterProvince', 'drawNormal'),
                'coordinates' => array(
                    'x' => 17,
                    'y' => 60,
                    'h' => 5,
                    'w' => 100,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                ),

            ),
            // amount words
            'amount_words' => array(
                'function' => array('filterAmountWords', 'drawNormal'),
                'coordinates' => array(
                    'x' => 17,
                    'y' => 37,
                    'h' => 5,
                    'w' => 100,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                )
            ),
            // amount cents
            'amount_cents' => array(
                'function' => array('filterAmountCents', 'drawNormal'),
                'coordinates' => array(
                    'x' => 117,
                    'y' => 37,
                    'h' => 5,
                    'w' => 15,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                )
            ),

            // date
            'date' => array(
                'function' => array('filterDate', 'drawNormal'),
                'coordinates' => array(
                    'x' => 130,
                    'y' => 45,
                    'h' => 5,
                    'w' => 25,
                    'halign' => 'C',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                )
            ),
            //date format label
            array(
                'function' => array('getDisplayDateFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 130,
                    'y' => 47.5,
                    'h' => 5,
                    'w' => 25,
                    'halign' => 'C',
                ),
                'font' => array(
                    'size' => 6,
                    'type' => ''
                )
            ),

            // amount padded
            'amount_padded' => array(
                'function' => array('filterAmountPadded', 'drawNormal'),
                'coordinates' => array(
                    'x' => 175,
                    'y' => 45,
                    'h' => 5,
                    'w' => 23,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                )
            ),
            // left column
            'stub_left_column' => array(
                'function' => 'drawPiecemeal',
                'coordinates' => array(
                    array(
                        'x' => 15,
                        'y' => 105,
                        'h' => 95,
                        'w' => 92,
                        'halign' => 'L',
                    ),
                    array(
                        'x' => 15,
                        'y' => 200,
                        'h' => 95,
                        'w' => 92,
                        'halign' => 'L',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                ),
                'multicell' => true,
            ),
            // right column
            'stub_right_column' => array(
                'function' => 'drawPiecemeal',
                'coordinates' => array(
                    array(
                        'x' => 107,
                        'y' => 105,
                        'h' => 95,
                        'w' => 91,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 107,
                        'y' => 200,
                        'h' => 95,
                        'w' => 91,
                        'halign' => 'R',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => '',
                ),
                'multicell' => true,
            ),

        );

        if (isset($template_schema[$name])) {
            return $name;
        } else {
            return $template_schema;
        }
    }
}
